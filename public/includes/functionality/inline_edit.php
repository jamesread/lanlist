<?php

declare(strict_types=1);

use libAllure\Logger;
use libAllure\Session;

require_once __DIR__ . '/moderation.php';

class LanlistInlineEditException extends Exception
{
    public function __construct(string $message, private readonly int $httpStatus = 400)
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}

function lanlistUserCanEditOrganizer(int $organizerId): bool
{
    if (!Session::isLoggedIn()) {
        return false;
    }

    return Session::hasPriv('EDIT_ORGANIZER')
        || Session::hasPriv('MODERATOR')
        || (int) Session::getUser()->getData('organization') === $organizerId;
}

function lanlistUserCanPublishOrganizer(int $organizerId): bool
{
    if (!Session::isLoggedIn()) {
        return false;
    }

    return Session::hasPriv('PUBLISH_ORGANIZERS') || Session::hasPriv('MODERATOR');
}

/**
 * @return array<string, array<string, array<string, mixed>>>
 */
function lanlistInlineEditRegistry(): array
{
    return [
        'organizer' => [
            'discordInviteUrl' => [
                'table' => 'organizers',
                'column' => 'discordInviteUrl',
                'label' => 'Discord invite URL',
                'auditType' => 'INLINE_EDIT_ORGANIZER',
                'canEdit' => 'lanlistUserCanEditOrganizer',
                'normalize' => static function (string $value): string {
                    return trim($value);
                },
                'validate' => static function (string $value): ?string {
                    if (strlen($value) > 255) {
                        return 'Discord invite URL must be at most 255 characters.';
                    }

                    return null;
                },
                'buildDisplay' => static function (int $id): array {
                    $organizer = fetchOrganizer($id);
                    applyOrganizerPlatformInviteHrefs($organizer);

                    return [
                        'discordInviteUrl' => (string) ($organizer['discordInviteUrl'] ?? ''),
                        'discordInviteHref' => (string) ($organizer['discordInviteHref'] ?? ''),
                        'discordInviteRowClass' => moderationDiscordRowClass($organizer['discordInviteUrl'] ?? null),
                    ];
                },
            ],
            'published' => [
                'table' => 'organizers',
                'column' => 'published',
                'label' => 'Published',
                'auditType' => 'INLINE_PUBLISH_ORGANIZER',
                'canEdit' => 'lanlistUserCanPublishOrganizer',
                'normalize' => static function (string $value): string {
                    $normalized = strtolower(trim($value));
                    if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                        return '1';
                    }
                    if (in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                        return '0';
                    }

                    return $normalized;
                },
                'validate' => static function (string $value): ?string {
                    if ($value !== '0' && $value !== '1') {
                        return 'Published must be yes or no.';
                    }

                    return null;
                },
                'buildDisplay' => static function (int $id): array {
                    $organizer = fetchOrganizer($id);
                    $published = (int) ($organizer['published'] ?? 0);

                    return [
                        'published' => $published,
                        'publishedLabel' => $published ? 'yes' : 'no',
                    ];
                },
            ],
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function lanlistInlineEditApply(string $entity, int $id, string $field, string $value): array
{
    if (!Session::isLoggedIn()) {
        throw new LanlistInlineEditException('You are not logged in.', 401);
    }

    $registry = lanlistInlineEditRegistry();
    if (!isset($registry[$entity][$field])) {
        throw new LanlistInlineEditException('Unknown inline edit field.', 404);
    }

    $handler = $registry[$entity][$field];
    $canEditFn = $handler['canEdit'];
    if (!is_string($canEditFn) || !function_exists($canEditFn) || !$canEditFn($id)) {
        throw new LanlistInlineEditException('You do not have permission to edit this field.', 403);
    }

    /** @var callable(string): string $normalize */
    $normalize = $handler['normalize'];
    $normalized = $normalize($value);

    /** @var callable(string): ?string $validate */
    $validate = $handler['validate'];
    $validationError = $validate($normalized);
    if ($validationError !== null) {
        throw new LanlistInlineEditException($validationError, 422);
    }

    global $db;

    require_once __DIR__ . '/edit_notifications.php';

    $table = (string) $handler['table'];
    $column = (string) $handler['column'];
    if ($table !== 'organizers') {
        throw new LanlistInlineEditException('Unsupported entity table.', 500);
    }

    $organizerBefore = fetchOrganizer($id);
    $oldRaw = $organizerBefore[$column] ?? null;

    $check = $db->prepare('SELECT id FROM organizers WHERE id = :id LIMIT 1');
    $check->bindValue(':id', $id, \PDO::PARAM_INT);
    $check->execute();
    if ($check->fetchRow() === false) {
        throw new LanlistInlineEditException('Record not found.', 404);
    }

    if ($column === 'discordInviteUrl') {
        $stmt = $db->prepare('UPDATE organizers SET discordInviteUrl = :v WHERE id = :id LIMIT 1');
        $stmt->bindValue(':v', $normalized);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
    } elseif ($column === 'published') {
        $stmt = $db->prepare('UPDATE organizers SET published = :v WHERE id = :id LIMIT 1');
        $stmt->bindValue(':v', (int) $normalized, \PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
    } else {
        throw new LanlistInlineEditException('Unsupported column.', 500);
    }

    $username = Session::getUser()->getUsername();
    Logger::messageAudit(
        'Inline edit ' . $entity . '.' . $field . ' on #' . $id . ' by ' . $username,
        (string) $handler['auditType'],
        ['relatedOrganizer' => $id]
    );

    $fieldLabel = (string) ($handler['label'] ?? $field);
    $format = $column === 'published'
        ? static fn (mixed $value): string => lanlistEditFormatYesNo($value)
        : null;
    $newRaw = $column === 'published' ? (int) $normalized : $normalized;
    lanlistSendInlineOrganizerEditNotification($id, $fieldLabel, $oldRaw, $newRaw, $format, $username);

    /** @var callable(int): array<string, mixed> $buildDisplay */
    $buildDisplay = $handler['buildDisplay'];

    return [
        'ok' => true,
        'entity' => $entity,
        'id' => $id,
        'field' => $field,
        'value' => $normalized,
        'display' => $buildDisplay($id),
    ];
}
