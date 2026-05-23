<?php

use libAllure\Form;
use libAllure\Session;
use libAllure\Shortcuts;
use libAllure\ElementSelect;
use libAllure\Logger;

if (!Session::hasPriv('SUPERUSER')) {
    die('Permission denied');
}

class FormAddPermissionToGroup extends Form
{
    public function __construct()
    {
        parent::__construct('formPrivsAddGroup', 'Grant permission for group');

                $sql = 'SELECT g.id FROM groups g WHERE g.id = :group';
                global $db;
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':group', Shortcuts::san()->filterUint('formPrivsAddGroup-usergroup'));
                $stmt->execute();

                $group = $stmt->fetchRowNotNull();

                $this->addElementReadOnly('Usergroup', $group['id'], 'usergroup');

                $this->addElementPermission();
        $this->addDefaultButtons('Grant');
    }

    public function addElementPermission()
    {
        global $db;

        $el = new ElementSelect('permission', 'Permission');

        $sql = 'SELECT p.key, p.id FROM permissions p ORDER BY p.key ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute();

        foreach ($stmt->fetchAll() as $perm) {
            $el->addOption($perm['key'], $perm['id']);
        }

        $this->addElement($el);
    }

    public function process()
    {
        global $db;
        $stmt = $db->prepare('INSERT INTO privileges_g (permission, `group`) values (:permission, :group) ');
        $stmt->bindValue(':permission', $this->getElementValue('permission'));
        $stmt->bindValue(':group', $this->getElementValue('usergroup'));
        $stmt->execute();

        $permId = (int) $this->getElementValue('permission');
        $permKey = (string) $permId;
        $pk = $db->prepare('SELECT p.key FROM permissions p WHERE p.id = :id LIMIT 1');
        $pk->bindValue(':id', $permId, \PDO::PARAM_INT);
        $pk->execute();
        if ($pk->numRows() > 0) {
            $permKey = $pk->fetchRow()['key'];
        }

        Logger::messageAudit(
            'Permission ' . $permKey . ' granted to group #' . $this->getElementValue('usergroup')
            . ' by ' . Session::getUser()->getUsername(),
            'GRANT_GROUP_PERMISSION'
        );

        redirect('viewGroup.php?id=' . $this->getElementValue('usergroup'), 'Permission created');
    }
}
