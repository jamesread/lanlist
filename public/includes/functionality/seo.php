<?php

/**
 * Canonical URLs, meta defaults, structured data helpers.
 */

function seoCurrentPageUrl(): string
{
    $base = rtrim(SITE_BASE_URL, '/');
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/';
    $query = '';

    if (!empty($_SERVER['QUERY_STRING'])) {
        $params = [];
        parse_str($_SERVER['QUERY_STRING'], $params);

        if (is_array($params) && $params !== []) {
            $trackingKeys = [
                'utm_source' => true,
                'utm_medium' => true,
                'utm_campaign' => true,
                'utm_term' => true,
                'utm_content' => true,
                'gclid' => true,
                'fbclid' => true,
                'msclkid' => true,
            ];

            foreach ($trackingKeys as $key => $_) {
                unset($params[$key]);
            }

            if ($params !== []) {
                $query = '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
            }
        }
    }

    return $base . $scriptName . $query;
}

function seoDefaultMetaDescription(): string
{
    return 'A list of LAN Parties for the community.';
}

function seoTruncateMeta(string $text, int $maxLength = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));

    if (strlen($text) <= $maxLength) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, $maxLength - 3, 'UTF-8')) . '...';
}

function seoPlainDescriptionFromEvent(array $event, int $maxLength): string
{
    $fromBlurb = strip_tags(stripslashes($event['blurb'] ?? ''));
    $fromBlurb = trim(preg_replace('/\s+/', ' ', $fromBlurb));

    if ($fromBlurb !== '') {
        return seoTruncateMeta(html_entity_decode($fromBlurb, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $maxLength);
    }

    $title = '';
    if (!empty($event['organizerTitle'])) {
        $title .= $event['organizerTitle'] . ' — ';
    }
    $title .= ($event['eventTitle'] ?? 'LAN party') . '. Dates: '
        . ($event['dateStartHuman'] ?? '') . '–' . ($event['dateFinishHuman'] ?? '');

    return seoTruncateMeta($title, $maxLength);
}

function seoEventMetaDescription(array $event): string
{
    return seoPlainDescriptionFromEvent($event, 160);
}

function seoEventPageTitle(array $event): string
{
    $organizer = trim((string)($event['organizerTitle'] ?? ''));
    $eventTitle = trim((string)($event['eventTitle'] ?? ''));
    $eventName = $eventTitle !== '' ? $eventTitle : 'LAN party';

    if ($organizer !== '') {
        return 'Event: ' . $organizer . ' - ' . $eventName;
    }

    return 'Event: ' . $eventName;
}

function buildWebSiteJsonLd(): array
{
    return seoJsonLdStripNulls([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => SITE_TITLE,
        'url' => rtrim(SITE_BASE_URL, '/') . '/',
        'description' => seoDefaultMetaDescription(),
    ]);
}

function seoOrganizerMetaDescription(array $organizer): string
{
    $fromBlurb = strip_tags(stripslashes($organizer['blurb'] ?? ''));
    $fromBlurb = trim(preg_replace('/\s+/', ' ', $fromBlurb));

    if ($fromBlurb !== '') {
        return seoTruncateMeta(html_entity_decode($fromBlurb, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 160);
    }

    $title = (string)($organizer['title'] ?? '');
    $fallback = $title !== ''
        ? 'LAN party organizer: ' . $title . '. Upcoming events and details on lanlist.'
        : 'LAN party organizer profile on lanlist.';

    return seoTruncateMeta($fallback, 160);
}

function seoVenueMetaDescription(array $venue): string
{
    $title = trim((string)($venue['title'] ?? ''));
    $country = trim((string)($venue['country'] ?? ''));

    $parts = ['LAN party venue'];

    if ($title !== '') {
        $parts[] = $title;
    }

    if ($country !== '') {
        $parts[] = $country;
    }

    return seoTruncateMeta(
        implode(' — ', $parts) . '. Find upcoming LAN events at this location on lanlist.',
        160
    );
}

function seoOrganizerOpenGraphAbsoluteImageUrl(?int $organizerId): ?string
{
    $organizerId = intval($organizerId);

    if ($organizerId < 1) {
        return null;
    }

    $publicRoot = dirname(__DIR__, 2);
    $path = $publicRoot . '/resources/images/organizer-logos/' . $organizerId . '.jpg';

    if (!is_readable($path)) {
        return null;
    }

    return rtrim(SITE_BASE_URL, '/') . '/' . getOrganizerLogoUrl($organizerId);
}

/**
 * Strip null subtree values for cleaner JSON-LD.
 *
 * @param array<string, mixed>|array<mixed>|mixed $node
 *
 * @return array<string, mixed>|array<mixed>|mixed
 */
function seoJsonLdStripNulls($node)
{
    if (!is_array($node)) {
        return $node;
    }

    foreach ($node as $k => $v) {
        if ($v === null) {
            unset($node[$k]);
        } elseif (is_array($v)) {
            $node[$k] = seoJsonLdStripNulls($v);

            if ($node[$k] === []) {
                unset($node[$k]);
            }
        }
    }

    return $node;
}

/**
 * @param array<mixed>|null $coords [ lat, lng ] or associative
 */
function buildEventJsonLdArray(array $event, ?array $coords = null): array
{
    $base = rtrim(SITE_BASE_URL, '/');
    $eventUrl = $base . '/viewEvent.php?id=' . (int)$event['id'];

    $nameParts = [];

    if (!empty($event['organizerTitle'])) {
        $nameParts[] = $event['organizerTitle'];
    }
    if (!empty($event['eventTitle'])) {
        $nameParts[] = $event['eventTitle'];
    }

    $name = implode(' — ', array_filter($nameParts)) ?: (($event['eventTitle'] ?? '') ?: 'LAN party');

    $description = seoPlainDescriptionFromEvent($event, 2800);

    $node = [
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => $name,
        'url' => $eventUrl,
        'startDate' => date('c', strtotime($event['dateStart'])),
        'endDate' => date('c', strtotime($event['dateFinish'])),
        'eventStatus' => 'https://schema.org/EventScheduled',
    ];

    if ($description !== '') {
        $node['description'] = $description;
    }

    $ogImage = seoOrganizerOpenGraphAbsoluteImageUrl(isset($event['organizerId']) ? (int)$event['organizerId'] : null);

    if ($ogImage !== null) {
        $node['image'] = [$ogImage];
    }

    if (!empty($event['organizerTitle'])) {
        $org = [
            '@type' => 'Organization',
            'name' => (string)$event['organizerTitle'],
        ];

        if (!empty($event['website']) && filter_var(preg_replace('/\s+/', '', (string)$event['website']), FILTER_VALIDATE_URL)) {
            $org['url'] = preg_replace('/\s+/', '', (string)$event['website']);
        }

        $node['organizer'] = $org;
    }

    if (!empty($event['venueTitle'])) {
        $place = [
            '@type' => 'Place',
            'name' => (string)$event['venueTitle'],
        ];

        if ($coords !== null) {
            $lat = isset($coords['lat']) ? $coords['lat'] : ($coords[0] ?? null);
            $lng = isset($coords['lng']) ? $coords['lng'] : ($coords[1] ?? null);

            if ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
                $place['geo'] = [
                    '@type' => 'GeoCoordinates',
                    'latitude' => (float)$lat,
                    'longitude' => (float)$lng,
                ];
            }
        }

        $node['location'] = $place;
    }

    return seoJsonLdStripNulls($node);
}
