<?php

class FormDeleteUser extends Form {
	public function __construct() {
		parent::__construct('formDeleteUser', 'Delete user?');

		requirePriv('USER_DELETE');

		$this->addElement(Element::factory('hidden', 'uid', null, $_REQUEST['formDeleteUser-uid']));
		$this->addElement(Element::factory('html', 'msg', null, 'Sure?'));
		$this->addButtons(Form::BTN_SUBMIT);
	}

	public function process() {
		global $db;

		$sql = 'DELETE FROM users WHERE id = :id LIMIT 1';
		$stmt = $db->prepare($sql);
		$stmt->bindValue(':id', $this->getElementValue('uid'));
		$stmt->execute();

		redirect('listUsers.php', 'Dead and gone.');
	}
}

?>
