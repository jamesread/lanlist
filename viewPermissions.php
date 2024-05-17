<?php

require_once 'includes/widgets/header.php';

use \libAllure\Session;

if (Session::isLoggedIn()) {
    echo '<table>';
    echo '<tr><th>Priv</th><th>Source</th><th>Source Title</th></tr>';

    foreach (Session::getUser()->getPrivs() as $priv) {
        echo '<tr>';
        echo '<td>' . $priv['key'] . '</td>';
        echo '<td>' . $priv['source'] . '</td>';
        echo '<td>' . $priv['sourceTitle'] . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

require_once 'includes/widgets/footer.php';

?>


