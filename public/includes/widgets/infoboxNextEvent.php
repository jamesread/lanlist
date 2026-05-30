<div class = "infobox">
    <h2>Next LAN Parties in the world...</h2>

    <?php

    echo '<ul class = "nextEvents">';
    foreach (getListOfNextEvents() as $tag => $events) {
        echo '<li class = "nextEvents-month"><strong>' . htmlspecialchars((string)$tag, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</strong></li>';
        foreach ($events as $event) {
            echo '<li class = "nextEvents-item">';
            echoCountryFlagOrName((string)$event['country'], true);
            echo ' ' . htmlspecialchars((string)$event['dayStartHuman'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . ' ';
            if (!empty($event['useFavicon']) && !empty($event['organizerId'])) {
                $organizerId = intval($event['organizerId']);
                $organizerTitle = htmlspecialchars((string)($event['organizerTitle'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $ariaLabel = $organizerTitle !== '' ? 'Organizer: ' . $organizerTitle : 'Organizer';
                echo '<a class = "nextEvents-organizer-link" href = "viewOrganizer.php?id=' . $organizerId . '" title = "' . $organizerTitle . '" aria-label = "' . $ariaLabel . '">';
                echo '<img class = "nextEvents-organizer-icon" src = "resources/images/organizer-favicons/' . $organizerId . '.png" width = "16" height = "16" alt = "" decoding = "async" />';
                echo '</a>&nbsp;';
            }
            echo '<a href = "viewEvent.php?id=' . intval($event['id']) . '">' . htmlspecialchars((string)$event['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</a>';
            echo '</li>';
        }
    }

    echo '</ul>';
    echo '<p>You can also view a list of <a href = "eventsList.php">all upcoming events</a> if that tickles your fancy.</p>';

    $geoCountry = getGeoIpCountryWithUpcomingEvents();
    if ($geoCountry !== null) {
        $countryUrl = 'eventsList.php?mode=country&amp;country=' . rawurlencode($geoCountry);
        echo '<p>Show events in <a href = "' . $countryUrl . '">';
        echo getCountryFlagHtml($geoCountry) . ' ';
        echo htmlspecialchars($geoCountry, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        echo '</a>.</p>';
    }
    ?>
</div>

