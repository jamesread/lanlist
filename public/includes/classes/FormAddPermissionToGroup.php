<?php

use libAllure\Form;
use libAllure\Session;
use libAllure\Shortcuts;
use libAllure\ElementSelect;

if (!Session::hasPriv('SUPERUSER')) {
    die('Permission denied');
}

class FormAddPermissionToGroup extends Form
{
    public function __construct()
    {
        parent::__construct('formPrivsAddGroup');

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

        redirect('viewGroup.php?id=' . $this->getElementValue('usergroup'), 'Permission created');
    }
}
