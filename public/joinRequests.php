<?php

require_once 'includes/common.php';

use libAllure\Session;

Session::requirePriv('JOIN_REQUESTS');

if (isset($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'approve':
            $id = fromRequestRequireInt('id');

            $sql = 'SELECT r.organizer AS organization, r.user AS uid FROM organization_join_requests r WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->execute();

            if ($stmt->numRows() == 0) {
                redirect('account.php', 'Request not found.');
            }

            $request = $stmt->fetchRow();

            $sql = 'UPDATE users u SET u.organization = :organizationId WHERE u.id = :uid LIMIT 1 ';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':organizationId', $request['organization']);
            $stmt->bindValue(':uid', $request['uid']);
            $stmt->execute();

            $sql = 'DELETE FROM organization_join_requests WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->execute();

            redirect('joinRequests.php', 'Approve');
            break;
        case 'deny':
            $id = fromRequestRequireInt('id');

            $sql = 'DELETE FROM organization_join_requests WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->execute();

            redirect('joinRequests.php', 'Denied');
            break;
    }
}

require_once 'includes/widgets/header.php';

$sql = 'SELECT r.id, o.id AS organizerId, o.title AS organizerTitle, o.websiteUrl AS organizerUrl, u.id AS userId, u.username FROM organization_join_requests r JOIN organizers o ON r.organizer = o.id JOIN users u ON r.user = u.id';
$stmt = $db->prepare($sql);
$stmt->execute();

$tpl->assign('requests', $stmt->fetchAll());
$tpl->display('joinRequests.tpl');

require_once 'includes/widgets/footer.php';
