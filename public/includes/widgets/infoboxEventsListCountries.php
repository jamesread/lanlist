<div class = "infobox">
    <h2>Future LAN parties per country</h2>
<?php

$countries = getCountriesWithPublishedEventCounts();
$selectedCountry = isset($_REQUEST['mode']) && $_REQUEST['mode'] === 'country' && !empty($_REQUEST['country'])
    ? (string)$_REQUEST['country']
    : '';

echo '<ul class = "eventsList-countries">';
foreach ($countries as $row) {
    $country = (string)$row['country'];
    $eventCount = intval($row['eventCount']);
    $flag = getCountryFlagHtml($country);

    if ($flag === '') {
        continue;
    }

    echo '<li>';
    if ($country === $selectedCountry) {
        echo '<strong>';
        echo $flag;
        echo ' ';
        echo htmlspecialchars($country, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        echo ' (';
        echo $eventCount;
        echo ')</strong>';
    } else {
        echo '<a href = "eventsList.php?mode=country&amp;country=';
        echo urlencode($country);
        echo '">';
        echo $flag;
        echo ' ';
        echo htmlspecialchars($country, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        echo ' (';
        echo $eventCount;
        echo ')';
        echo '</a>';
    }
    echo '</li>';
}
echo '</ul>';

if ($selectedCountry !== '') {
    echo '<p><a href = "eventsList.php?mode=country">All countries with flags</a></p>';
}

?>
</div>
