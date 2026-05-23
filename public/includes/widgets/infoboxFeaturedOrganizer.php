<?php

$sql = 'SELECT o.title, o.id, o.blurb FROM organizers o WHERE 1=1' . lanlistSqlPublicOrganizerVisible('o') . ' ORDER BY rand() LIMIT 1';
$stmt = $db->prepare($sql);
$stmt->execute();
$org = $stmt->fetchRow();

if ($org === false) {
    return;
}

?>

<div class = "infobox">
        <h2>Featured organizer: <a href = "viewOrganizer.php?id=<?php echo (int)$org['id']; ?>"><?php echo htmlspecialchars((string)$org['title'], ENT_QUOTES, 'UTF-8'); ?></a></h2>

<?php
    $tpl->assign('organizerId', $org['id']);
    $tpl->assign('logoUrl', 'resources/images/organizer-logos/' . $org['id'] . '.jpg');
    $tpl->assign('skipLogoBox', true);

    $tpl->display('infobox.organizerLogo.tpl');
?>
        <p><?php echo htmlspecialchars((string)$org['blurb'], ENT_QUOTES, 'UTF-8'); ?></p>
</div>
