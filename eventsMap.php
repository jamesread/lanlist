<?php

require_once 'includes/common.php';
require_once 'includes/classes/FormSetLocation.php';

$f = new FormSetLocation();

if ($f->validate()) {
	$f->process();
	$currentLocation = $f->getElementValue('location');
} else if (Session::isLoggedIn()) {
	$currentLocation = Session::getUser()->getData('location');
} else if (isset($_COOKIE['mylocation'])) {
	$currentLocation = $_COOKIE['mylocation'];
} else {
	$currentLocation = null;
}

require_once 'includes/widgets/header.php';
require_once 'includes/functionality/misc.php';

?>
<h2>LAN Parties On A Map...</h2>
<div>
	<div class = "content">
		<div id = "map" style = "width: 100%; height: 600px;border: 1px solid LightGray;" >
			<noscript>
				<p>There should be a map here, but you dont have javascript...</p>
			</noscript>
		</div>

		<div>
			<p>
				<?php 
				if (Session::isLoggedIn()) {
					echo 'Go to your <a href = "account.php">account</a> to add events. ';
				} else {
					echo 'You can get the list in <a href = "eventsList.php">many different formats</a>, or you can add your own events if you <a href = "loginregister.php">login</a>. ';
				}
				?>
				For feature requests, bugs and whatnot, get in <a href = "contact.php">contact</a>.
			</p>
		</div>
	</div>
</div>

<?php startSidebar(); ?>

<script type = "text/javascript">
	renderMap();
	<?php echo jsForEvents(); ?>
</script>

<div class = "infobox">
	<div id = "eventInfo">
		<h2>Event info</h2>
		<p>Click an event on the map to get started...</p>
	</div>

	<p><button id = "btnDirections" disabled = "disabled">Get directions!</button></p>
<?php
$currentLocation = htmlentities($currentLocation, ENT_QUOTES);

if (empty($currentLocation)) {
	$f->display();
	echo '<small>Currently set to <strong>(nothing)</strong></small>';
} else {
	echo '<div id = "formSetLocationContainer" style = "display: none">';
	$f->display();
	echo '</div>';
	echo '<small>Currently set to <strong>', $currentLocation, '</strong><span class = "dummyLink" onclick = "javascript:showSetLocationForm()" id = "linkShowSetLocationForm">change...</span></small>';
}
?>
</div>

	<?php

require_once 'includes/widgets/infoboxNextEvent.php';

$tpl->display('infobox.addEvents.tpl');

require_once 'includes/widgets/footer.php'; 

?>
