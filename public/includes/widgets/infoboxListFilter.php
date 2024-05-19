<div class = "infobox">
	<h2>Events List</h2>
<?php

$actions = array(
	'Upcoming events',
	'perOrganizer' => 'Next events per Organizer',
	'everything' => 'Everything',
);

$_REQUEST['mode'] = &$_REQUEST['mode'];

echo '<ul>';
foreach ($actions as $action => $title) {
	if (basename($_SERVER['PHP_SELF']) == "eventsList.php" && ((empty($_REQUEST['mode']) && $action === 0) || $action === $_REQUEST['mode'])) {
		echo '<li><strong>' . $title . '</strong></li>';
	} else {
		echo '<li><a href = "eventsList.php?mode=' . $action . '">' . $title . '</a></li>';
	}
}

echo '</ul>';
echo '<h2>Other</h2>';
echo '<ul>';
echo '<li><a href = "listOrganizers.php">List of organizers</a></li>';
echo '<li><a href = "listVenues.php">List of venues</a></li>';
echo '</ul>';

?>
</div>
