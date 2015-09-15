<?php

require_once 'includes/common.php';
require_once 'jwrCommonsPhp/Form.php';

function getFormUsingMagic() {
	foreach ($_REQUEST as $key => $value) {
		if (strpos($key, 'formClazz') !== false) {
			$form = $value;
			break;
		}
	}

	if (!isset($form)) {
		throw new SimpleFatalError('Uh oh, form not specified!');
	}

	if (!preg_match('/^[a-z]{6,32}$/i', $form)) {
		throw new Exception('That form name looks a bit funky, please kindly poke off.');
	}

	if (!file_exists('includes/classes/' . $form . '.php')) {
		throw new Exception('Oh, I looked for that form but could not find it. Imagine my disapointment.... ');
	}

	require_once 'includes/classes/' . $form . '.php';

	if (!class_exists($form)) {
		throw new Exception('Okay now that IS weird, the file exists, but there is no class, hmm.');
	}

	$form = new $form();

	if (!($form instanceof Form)) {
		throw new Exception('After all the work I went to of instanciating a form, it was not a Form.');
	}

	return $form;
}

$f = getFormUsingMagic();
$f->addElement(Element::factory('hidden', 'formClazz', null, get_class($f)));

if ($f->validate()) {
	$f->process();

	throw new Exception('Processed the form, but it didnt know what to do next... now I am sat here looking silly.');
}

define('TITLE', 'Form: ' . $f->getTitle());
require_once 'includes/widgets/header.php';

$f->display();

require_once 'includes/widgets/footer.php';


?>
