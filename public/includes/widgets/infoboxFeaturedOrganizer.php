<?php

$sql = 'SELECT o.title, o.id, o.blurb FROM organizers o ORDER BY rand() LIMIT 1';
$stmt = $db->prepare($sql);
$stmt->execute();
$org = $stmt->fetchRow();

?>

<div class = "infobox">
        <h2>Featured organizer: <a href = "viewOrganizer.php?id=<?php echo $org['id']; ?>"><?php echo $org['title']; ?></a></h2>

<?php
    $tpl->assign('organizerId', $org['id']);
    $tpl->assign('logoUrl', 'resources/images/organizer-logos/' . $org['id'] . '.jpg');
    $tpl->assign('skipLogoBox', true);

    $tpl->display('infobox.organizerLogo.tpl');
?>
        <p><?php echo $org['blurb']; ?></p>
</div>
