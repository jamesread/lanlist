<div class = "infobox">
	<h2>Next LAN Parties in the world...</h2>

	<?php 
	
	echo '<p>The next few events in the world are; </p>';
	echo '<ul>';
	foreach (getListOfNextEvents() as $event) {
		$date = strtotime($event['dateStart']);
		$date = date('Y-m-d', $date);

		echo '<li>' . $date . ' <a href = "viewEvent.php?id=' . $event['id'] . '">' . $event['title'] . '</a>, ' . $event['country'] . '</li>';
	}

	echo '</ul>';
	echo '<p>You can also view a list of <a href = "eventsList.php">all upcomming events</a> if that tickles your fancy.</p>'
	?>
</div>

