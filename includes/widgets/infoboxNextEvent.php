<div class = "infobox">
	<h2>Next LAN Parties in the UK...</h2>

	<?php 
	
	echo '<ul class = "nextEvents">';
	foreach (getListOfNextEvents() as $event) {
            echo '<li>' . $event['dateStartHuman'] . ' <a href = "viewEvent.php?id=' . $event['id'] . '">' . $event['title'] . '</a>, ';
            switch ($event['country']) {
            case 'United Kingdom':
                echo '&#127468;&#127463;'; break; 
            default: 
                echo $event['country'];
            }
            echo '</li>';
	}

	echo '</ul>';
	echo '<p>You can also view a list of <a href = "eventsList.php">all upcomming events</a> if that tickles your fancy.</p>'
	?>
</div>

