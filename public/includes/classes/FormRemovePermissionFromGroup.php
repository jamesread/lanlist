<?php

use libAllure\Form;
use libAllure\ElementHidden;
use libAllure\ElementHtml;
use libAllure\Logger;
use libAllure\Session;
use libAllure\Shortcuts;

if (!Session::hasPriv('SUPERUSER')) {
    die('Permission denied');
}

class FormRemovePermissionFromGroup extends Form
{
    public function __construct()
    {
        parent::__construct('formRemovePermissionFromGroup', 'Drop permission from group?');

        $groupId = Shortcuts::san()->filterUint('formRemovePermissionFromGroup-usergroup');
        $permissionId = Shortcuts::san()->filterUint('formRemovePermissionFromGroup-permission');

        global $db;

        $stmt = $db->prepare(
            'SELECT g.id, g.title, p.id AS permissionId, p.key AS permissionKey
             FROM privileges_g gp
             JOIN groups g ON gp.group = g.id
             JOIN permissions p ON gp.permission = p.id
             WHERE gp.group = :gid AND gp.permission = :pid
             LIMIT 1'
        );
        $stmt->bindValue(':gid', $groupId, \PDO::PARAM_INT);
        $stmt->bindValue(':pid', $permissionId, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetchRow();
        if ($row === false) {
            throw new Exception('That permission is not assigned to this group.');
        }

        $this->addElement(new ElementHidden('usergroup', null, $row['id']));
        $this->addElement(new ElementHidden('permission', null, $row['permissionId']));
        $this->addElementReadOnly('Group', $row['title'] . ' (#' . $row['id'] . ')', 'groupLabel');
        $this->addElementReadOnly('Permission', $row['permissionKey'], 'permissionLabel');
        $this->addElement(new ElementHtml('msg', null, 'Remove this permission from the group?'));
        $this->addDefaultButtons('Drop permission');
    }

    public function process()
    {
        global $db;

        $groupId = (int) $this->getElementValue('usergroup');
        $permissionId = (int) $this->getElementValue('permission');

        $pk = $db->prepare('SELECT p.key FROM permissions p WHERE p.id = :id LIMIT 1');
        $pk->bindValue(':id', $permissionId, \PDO::PARAM_INT);
        $pk->execute();
        $permKey = (string) $permissionId;
        if ($pk->numRows() > 0) {
            $permKey = $pk->fetchRow()['key'];
        }

        $stmt = $db->prepare(
            'DELETE FROM privileges_g WHERE `group` = :group AND permission = :permission LIMIT 1'
        );
        $stmt->bindValue(':group', $groupId, \PDO::PARAM_INT);
        $stmt->bindValue(':permission', $permissionId, \PDO::PARAM_INT);
        $stmt->execute();

        Logger::messageAudit(
            'Permission ' . $permKey . ' dropped from group #' . $groupId
            . ' by ' . Session::getUser()->getUsername(),
            'DROP_GROUP_PERMISSION'
        );

        redirect('viewGroup.php?id=' . $groupId, 'Permission dropped.');
    }
}
