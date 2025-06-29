<div class = "infobox">
    <h2>Next LAN Parties in the world...</h2>

    <?php

    echo '<ul class = "nextEvents">';
    foreach (getListOfNextEvents() as $tag => $events) {
        echo "<li><strong>$tag</strong></li>";
        foreach ($events as $event) {
            echo '<li>';
            switch ($event['country']) {
                // https://symbl.cc/en/emoji/flags/country-flag/
                case 'United Kingdom':  echo '&#127468;&#127463;'; break;
                case 'Sweden':          echo '&#127480;&#127466;'; break;
                case 'Netherlands':     echo '&#127475;&#127473;'; break;
                case 'Germany':         echo '&#127465;&#127466;'; break;
                case 'United States':   echo '&#127482;&#127480;'; break;
				case 'Canada':          echo '&#127464;&#127462;'; break;
                default:
                    echo $event['country'];
            }
            echo ' ' . $event['dayStartHuman'] . ' <a href = "viewEvent.php?id=' . $event['id'] . '">' . $event['title'] . '</a>';
            echo '</li>';
        }
    }

    echo '</ul>';
    echo '<p>You can also view a list of <a href = "eventsList.php">all upcoming events</a> if that tickles your fancy.</p>'
    ?>
</div>

