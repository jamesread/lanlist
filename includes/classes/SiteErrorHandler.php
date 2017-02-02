<?php

class SiteErrorHandler extends \libAllure\ErrorHandler {
	protected function renderSfe(SimpleFatalError $e) {
		require_once 'includes/widgets/header.php';
		echo '<p><span class = "karmaBad">Error! ' . $e->getMessage() . '</span></p>';
		echo '<p>A fatal error has been thrown. This is probably something that you did.</p>';

		require_once 'includes/widgets/footer.php';
	}
}

?>
