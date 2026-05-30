<?php

declare(strict_types=1);

require_once __DIR__ . '/../classes/FormHelpers.php';

/**
 * @return array<int, string>
 */
function lanlistCountryNameList(): array
{
    return FormHelpers::getCountryList();
}

/**
 * @return array<int, string>
 */
function lanlistParseUsefulRelatedSiteCountriesText(string $text): array
{
    $allowed = array_flip(lanlistCountryNameList());
    $countries = [];

    foreach (preg_split('/\R/', $text) as $line) {
        $country = trim($line);
        if ($country === '' || !isset($allowed[$country])) {
            continue;
        }
        $countries[$country] = $country;
    }

    return array_values($countries);
}

/**
 * @return array<int, string>
 */
function lanlistFetchUsefulRelatedSiteCountries(int $siteId): array
{
    global $db;

    $stmt = $db->prepare(
        'SELECT country FROM useful_related_site_countries WHERE site_id = :siteId ORDER BY country ASC'
    );
    $stmt->bindValue(':siteId', $siteId, \libAllure\Database::PARAM_INT);
    $stmt->execute();

    return array_column($stmt->fetchAll(), 'country');
}

function lanlistReplaceUsefulRelatedSiteCountries(int $siteId, array $countries): void
{
    global $db;

    $delete = $db->prepare('DELETE FROM useful_related_site_countries WHERE site_id = :siteId');
    $delete->bindValue(':siteId', $siteId, \libAllure\Database::PARAM_INT);
    $delete->execute();

    if ($countries === []) {
        return;
    }

    $insert = $db->prepare(
        'INSERT INTO useful_related_site_countries (site_id, country) VALUES (:siteId, :country)'
    );
    foreach ($countries as $country) {
        $insert->bindValue(':siteId', $siteId, \libAllure\Database::PARAM_INT);
        $insert->bindValue(':country', $country);
        $insert->execute();
    }
}

/**
 * @return array<string, mixed>
 */
function lanlistFetchUsefulRelatedSite(int $siteId): array
{
    global $db;

    $stmt = $db->prepare(
        'SELECT id, url, title, description, sortOrder FROM useful_related_sites WHERE id = :id LIMIT 1'
    );
    $stmt->bindValue(':id', $siteId, \libAllure\Database::PARAM_INT);
    $stmt->execute();
    $site = $stmt->fetch();

    if ($site === false) {
        throw new Exception('Related site link not found');
    }

    $site['countries'] = lanlistFetchUsefulRelatedSiteCountries($siteId);

    return $site;
}

/**
 * @return array<string, true>
 */
function lanlistCountriesWithUpcomingEventsMap(): array
{
    $map = [];
    foreach (getCountriesWithUpcomingEventCounts() as $row) {
        $map[(string) $row['country']] = true;
    }

    return $map;
}

/**
 * @return array<int, array{
 *     isGlobal: bool,
 *     label: string,
 *     country: string|null,
 *     flagHtml: string,
 *     eventsListUrl: string|null,
 *     sites: array<int, array{id: int|string, url: string, title: string, description: string}>
 * }>
 */
function lanlistFetchUsefulRelatedSiteGroupsForDisplay(): array
{
    global $db;

    $stmt = $db->prepare(
        'SELECT id, url, title, description, sortOrder FROM useful_related_sites ORDER BY sortOrder ASC, title ASC'
    );
    $stmt->execute();
    $sites = $stmt->fetchAll();
    $countriesWithUpcomingEvents = lanlistCountriesWithUpcomingEventsMap();

    $globalSites = [];
    $sitesByCountry = [];

    foreach ($sites as $site) {
        $siteDisplay = [
            'id' => (int) $site['id'],
            'url' => (string) $site['url'],
            'title' => (string) $site['title'],
            'description' => (string) $site['description'],
        ];
        $countries = lanlistFetchUsefulRelatedSiteCountries((int) $site['id']);

        if ($countries === []) {
            $globalSites[] = $siteDisplay;
            continue;
        }

        foreach ($countries as $country) {
            $sitesByCountry[$country][] = $siteDisplay;
        }
    }

    $groups = [];
    if ($globalSites !== []) {
        $groups[] = [
            'isGlobal' => true,
            'label' => 'Global / all',
            'country' => null,
            'flagHtml' => '',
            'eventsListUrl' => null,
            'sites' => $globalSites,
        ];
    }

    $countries = array_keys($sitesByCountry);
    sort($countries, SORT_STRING);

    foreach ($countries as $country) {
        $groups[] = [
            'isGlobal' => false,
            'label' => $country,
            'country' => $country,
            'flagHtml' => getCountryFlagHtml($country),
            'eventsListUrl' => isset($countriesWithUpcomingEvents[$country])
                ? 'eventsList.php?mode=country&country=' . rawurlencode($country)
                : null,
            'sites' => $sitesByCountry[$country],
        ];
    }

    return $groups;
}

/**
 * Country-specific useful related sites (excludes global / all entries).
 *
 * @return array<int, array{id: int, url: string, title: string, description: string}>
 */
function lanlistFetchUsefulRelatedSitesForCountry(string $country): array
{
    global $db;

    $stmt = $db->prepare(
        'SELECT s.id, s.url, s.title, s.description
         FROM useful_related_sites s
         INNER JOIN useful_related_site_countries c ON c.site_id = s.id
         WHERE c.country = :country
         ORDER BY s.sortOrder ASC, s.title ASC'
    );
    $stmt->bindValue(':country', $country);
    $stmt->execute();

    $sites = [];
    foreach ($stmt->fetchAll() as $row) {
        $sites[] = [
            'id' => (int) $row['id'],
            'url' => (string) $row['url'],
            'title' => (string) $row['title'],
            'description' => (string) $row['description'],
        ];
    }

    return $sites;
}

/**
 * @param array<int, string> $countries
 */
function lanlistFormatUsefulRelatedSiteCountries(array $countries): string
{
    if ($countries === []) {
        return '';
    }

    $parts = [];
    foreach ($countries as $country) {
        $flag = getCountryFlagHtml($country);
        $parts[] = trim($flag . ' ' . $country);
    }

    return implode(', ', $parts);
}

/**
 * @return array<int, array<string, mixed>>
 */
function lanlistFetchUsefulRelatedSitesForAdmin(): array
{
    global $db;

    $stmt = $db->prepare(
        'SELECT id, url, title, description, sortOrder FROM useful_related_sites ORDER BY sortOrder ASC, title ASC'
    );
    $stmt->execute();
    $sites = $stmt->fetchAll();

    foreach ($sites as $k => $site) {
        $countries = lanlistFetchUsefulRelatedSiteCountries((int) $site['id']);
        $sites[$k]['countries'] = $countries;
        $sites[$k]['countrySummary'] = $countries === []
            ? 'All / unspecified'
            : lanlistFormatUsefulRelatedSiteCountries($countries);
    }

    return $sites;
}

function lanlistUsefulRelatedSiteCountriesToText(array $countries): string
{
    return implode("\n", $countries);
}
