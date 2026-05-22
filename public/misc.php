<?php

require_once 'includes/common.php';

use libAllure\Session;
use libAllure\Shortcuts;
use libAllure\ErrorHandler;
use OliveTin\Api\OliveTinApiException;

if (!isset($_REQUEST['action'])) {
    throw new InvalidArgumentException('action is required.');
}

switch ($_REQUEST['action']) {
    case 'toggleEvent':
        requirePriv('TOGGLE_EVENT_PUBLISHED');

        $sql = 'UPDATE events SET published = !published WHERE id = :id LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', fromRequestRequireInt('id'));
        $stmt->execute();

        $event = fetchEvent(fromRequestRequireInt('id'));

        $sql = 'SELECT u.id, u.username, u.email FROM users u WHERE u.organization = :organization';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':organization', $event['organizerId']);
        $stmt->execute();

        foreach ($stmt->fetchAll() as $orgieUser) {
            $tpl->assign('siteBaseUrl', SITE_BASE_URL);
            $tpl->assign('event', $event);
            $tpl->assign('publisherUsername', Session::getUser()->getUsername());
            $tpl->assign('user', $orgieUser);
            $content = $tpl->fetch('email.eventToggled.tpl');

            if ($event['published']) {
                $title = 'Event: ' . $event['eventTitle'] . ' has been published!';
            } else {
                $title = 'Event: ' . $event['eventTitle'] . ' has been unpublished.';
            }

            sendEmail($orgieUser['email'], $content, $title);
        }

        redirect('viewEvent.php?id=' . (int)$event['id'], 'Event toggled. Email sent to organizers.');
        break;
    case 'cloneEvent':
        $event = fetchEvent(fromRequestRequireInt('id'));

        if ($event == null) {
            throw new Exception('event not found');
        }

        if (!Session::getUser()->hasPriv('EVENT_CLONE')) {
            if ($event['organizerId'] != Session::getUser()->getData('organization')) {
                throw new libAllure\exceptions\SimpleFatalError('You cannot clone that event, because you are not the organizer.');
            }
        }

        $sql = 'INSERT INTO events (title, organizer, venue, urlImage, website, priceOnDoor, priceInAdv, showers, sleeping, currency, alcohol, smoking, numberOfSeats, networkMbps, internetMbps, blurb, dateStart, dateFinish, createdDate, createdBy) ';
        $sql .= 'SELECT title, organizer, venue, urlImage, website, priceOnDoor, priceInAdv, showers, sleeping, currency, alcohol, smoking, numberOfSeats, networkMbps, internetMbps, blurb, now(), now(), now(), :uid FROM events e2 WHERE e2.id = :id ';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $event['id']);
        $stmt->bindValue(':uid', Session::getUser()->getId());
        $stmt->execute();

        $newEventId = $db->lastInsertId();

        $sql = 'UPDATE events SET title = concat(title, " (cloned)"), createdDate = now(), createdBy = :user WHERE id = :id ';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $newEventId);
        $stmt->bindValue(':user', Session::getUser()->getId());
        $stmt->execute();

        redirect('viewEvent.php?id=' . $newEventId, 'Event Cloned');

        break;
    case 'deleteOrganizer':
        requirePriv('DELETE_ORGANIZER');

        $id = fromRequestRequireInt('id');

        $org = fetchOrganizer($id);

        $sql = 'DELETE FROM organizers WHERE id = :id LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
        ]);

        redirect('listOrganizers.php', 'Organizer deleted');

        break;
    case 'deleteEvent':
        $event = fetchEvent(fromRequestRequireInt('id'));

        if ($event == null) {
            throw new Exception('event not found');
        }

        if (!Session::getUser()->hasPriv('EVENT_DELETE')) {
            if ($event['organizerId'] != Session::getUser()->getData('organization')) {
                throw new libAllure\exceptions\SimpleFatalError('You cannot delete that event, because you are not the organizer.');
            }
        }


        $sql = 'DELETE FROM events WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $event['id']);
        $stmt->execute();

        redirect('index.php', 'Event deleted');
        break;
    case 'updateOrganizerLastChecked':
        requirePriv('SITE_CHECKS');

        $organizer = fetchOrganizer(fromRequestRequireInt('id'));

        $sql = 'UPDATE organizers SET lastChecked = now() WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $organizer['id']);
        $stmt->execute();

        redirect('siteChecks.php', 'Updated last checked field for organizer: ' . $organizer['title']);
        break;
    case 'enqueueOrganizerFaviconFetch':
        requirePriv('MODERATOR');

        require_once __DIR__ . '/includes/functionality/async_jobs.php';
        require_once __DIR__ . '/includes/functionality/olivetin.php';

        $organizerId = fromRequestRequireInt('organizerId');
        fetchOrganizer($organizerId);

        $active = lanlistSelectActiveOrganizerFaviconJob($organizerId);
        if ($active !== false) {
            redirect(
                'moderation.php',
                'Favicon fetch is already queued or running for this organizer (job #' . (int) $active['id'] . ').'
            );
            break;
        }

        $bindingId = lanlistOrganizerFaviconOliveTinBindingId();
        if ($bindingId === '') {
            redirect('moderation.php', 'OLIVETIN_BINDING_ORGANIZER_FAVICON_FETCH is not set in config.');
            break;
        }
        if (!lanlistOliveTinConfigured()) {
            redirect('moderation.php', 'OliveTin is not configured (set OLIVETIN_BASE_URL and OLIVETIN_API_KEY).');
            break;
        }

        $lockName = 'lanlist_ofav_' . $organizerId;

        global $db;
        $lockHeld = false;

        try {
            $lk = $db->prepare('SELECT GET_LOCK(:lk, 10)');
            $lk->bindValue(':lk', $lockName);
            $lk->execute();
            $lockResult = $lk->fetch(\PDO::FETCH_NUM);
            if (!$lockResult || (int) $lockResult[0] !== 1) {
                redirect('moderation.php', 'Could not obtain enqueue lock; try again in a moment.');
                break;
            }
            $lockHeld = true;

            $activeUnderLock = lanlistSelectActiveOrganizerFaviconJob($organizerId);
            if ($activeUnderLock !== false) {
                redirect(
                    'moderation.php',
                    'Favicon fetch is already queued or running for this organizer (job #' . (int) $activeUnderLock['id'] . ').'
                );
                break;
            }

            $userId = (int) Session::getUser()->getId();
            $metadata = [
                'enqueuedByUserId' => $userId,
                'bindingId' => $bindingId,
                'requestedFrom' => 'moderation.enqueueOrganizerFaviconFetch',
            ];
            $metadataJson = json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

            $db->beginTransaction();
            try {
                $ins = $db->prepare(
                    'INSERT INTO async_jobs (job_type, organizer_id, status, metadata)
                     VALUES (:jt, :oid, :st, :meta)'
                );
                $ins->bindValue(':jt', LANLIST_JOB_TYPE_ORGANIZER_FAVICON_FETCH);
                $ins->bindValue(':oid', $organizerId, \PDO::PARAM_INT);
                $ins->bindValue(':st', 'pending');
                $ins->bindValue(':meta', $metadataJson);
                $ins->execute();

                $jobId = (int) $db->lastInsertId();

                $upOrg = $db->prepare('UPDATE organizers SET faviconRefetch = 1 WHERE id = :oid LIMIT 1');
                $upOrg->bindValue(':oid', $organizerId, \PDO::PARAM_INT);
                $upOrg->execute();

                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e;
            }

            try {
                $client = lanlistOliveTinClient();
                $started = $client->startAction(
                    $bindingId,
                    [
                        'organizerId' => (string) $organizerId,
                        'jobId' => (string) $jobId,
                    ]
                );
                $traceId = $started['executionTrackingId'] ?? null;
                if (!is_string($traceId) || $traceId === '') {
                    $msg = 'OliveTin accepted the job but returned no executionTrackingId.';
                    $fail = $db->prepare(
                        'UPDATE async_jobs SET error_message = :em WHERE id = :jid AND job_type = :jt LIMIT 1'
                    );
                    $fail->bindValue(':em', substr($msg, 0, 62000));
                    $fail->bindValue(':jid', $jobId, \PDO::PARAM_INT);
                    $fail->bindValue(':jt', LANLIST_JOB_TYPE_ORGANIZER_FAVICON_FETCH);
                    $fail->execute();
                    redirect('moderation.php', 'Queued job #' . $jobId . ' but could not confirm OliveTin execution id.');
                    break;
                }

                $proc = $db->prepare(
                    'UPDATE async_jobs SET execution_tracking_id = :tid, status = \'processing\', '
                    . 'started_at = NOW(), error_message = NULL WHERE id = :jid AND job_type = :jt LIMIT 1'
                );
                $proc->bindValue(':tid', $traceId);
                $proc->bindValue(':jid', $jobId, \PDO::PARAM_INT);
                $proc->bindValue(':jt', LANLIST_JOB_TYPE_ORGANIZER_FAVICON_FETCH);
                $proc->execute();

                redirect('moderation.php', 'Favicon fetch job #' . $jobId . ' queued (OliveTin tracking: ' . $traceId . ').');
                break;
            } catch (OliveTinApiException | \InvalidArgumentException $e) {
                $msg = 'dispatch failed: ' . $e->getMessage();
                $fail = $db->prepare(
                    'UPDATE async_jobs SET error_message = :em WHERE id = :jid AND job_type = :jt LIMIT 1'
                );
                $fail->bindValue(':em', substr($msg, 0, 62000));
                $fail->bindValue(':jid', $jobId, \PDO::PARAM_INT);
                $fail->bindValue(':jt', LANLIST_JOB_TYPE_ORGANIZER_FAVICON_FETCH);
                $fail->execute();

                redirect(
                    'moderation.php',
                    'Job #' . $jobId . ' created but OliveTin dispatch failed. Check OliveTin configuration and error text on the job row.'
                );
                break;
            }
        } finally {
            if ($lockHeld) {
                $rl = $db->prepare('SELECT RELEASE_LOCK(:lk)');
                $rl->bindValue(':lk', $lockName);
                $rl->execute();
            }
        }
        break;
    default:
        throw new InvalidArgumentException('action not handled.');
}

require_once 'includes/widgets/footer.php';
