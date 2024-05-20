<?php

class SiteErrorHandler extends \libAllure\ErrorHandler
{
    public function renderSfe($e)
    {
        require_once 'includes/widgets/header.minimal.php';

        echo '<p><span class = "karmaBad">Error: ' . $e->getMessage() . '</span></p>';
        echo '<p>This type of error is probably caused by something that you did. Try going back in your browser and try again.</p>';

        require_once 'includes/widgets/footer.minimal.php';
    }
}
