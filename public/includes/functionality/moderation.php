<?php

declare(strict_types=1);

function moderationDiscordRowClass(?string $url): string
{
    $u = trim((string) $url);
    if ($u === '') {
        return 'bad';
    }
    $l = strtolower($u);
    if (
        strpos($l, 'discord.gg') !== false
        || strpos($l, 'discord.com') !== false
        || strpos($l, 'discordapp.com') !== false
    ) {
        return '';
    }

    return 'warn';
}

function moderationSteamRowClass(?string $url): string
{
    $u = trim((string) $url);
    if ($u === '') {
        return 'bad';
    }
    $l = strtolower($u);
    if (
        strpos($l, 'steamcommunity.com') !== false
        || strpos($l, 'steampowered.com') !== false
        || strpos($l, 's.team') !== false
        || strpos($l, 'steam://') !== false
    ) {
        return '';
    }

    return 'warn';
}

/**
 * Add moderation UI fields (file checks, URL validation classes, lastChecked).
 *
 * @param array<string, mixed> $organizer
 * @return array<string, mixed>
 */
function lanlistEnrichOrganizerForModeratorView(array $organizer): array
{
    global $db;

    $oid = (int) $organizer['id'];
    $stmt = $db->prepare('SELECT lastChecked FROM organizers WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $oid, \PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchRow();
    $organizer['lastChecked'] = ($row !== false && !empty($row['lastChecked'])) ? $row['lastChecked'] : null;

    $publicDir = dirname(__DIR__, 2);
    $faviconFs = $publicDir . '/resources/images/organizer-favicons/' . $oid . '.png';

    $organizer['logoFileExists'] = !empty((int) ($organizer['validBanner'] ?? 0));
    $organizer['faviconFileExists'] = is_file($faviconFs);
    $organizer['useFaviconEnabled'] = !empty((int) ($organizer['useFavicon'] ?? 0));
    $organizer['discordInviteRowClass'] = moderationDiscordRowClass($organizer['discordInviteUrl'] ?? null);
    $organizer['steamGroupRowClass'] = moderationSteamRowClass($organizer['steamGroupUrl'] ?? null);
    $organizer['logoRowClass'] = $organizer['logoFileExists'] ? '' : 'bad';
    $organizer['faviconRowClass'] = '';
    if ($organizer['useFaviconEnabled'] && !$organizer['faviconFileExists']) {
        $organizer['faviconRowClass'] = 'bad';
    }

    return $organizer;
}
