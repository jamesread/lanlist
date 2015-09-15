<?php

class ElementDateTime extends ElementDate {
	public function __construct($name, $caption, $value = null) {
		parent::__construct($name, 'dateType', $caption, $value, '', '');
	}

	public function render() {
		$buf = '<label for = "' . $this->name . '">' . $this->caption . '</label><input name = "' . $this->name . '" />';
		$buf .= <<<JS
<script type = "text/javascript">
	$('{$this->name}').datepicker({
		firstDay: 1
	});
</script>
JS;

		return $buf;
	}
}
?>
