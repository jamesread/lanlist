function setupSortableTables() {
	$('table.sortable').dataTable({
		'aaSorting': [[ 3, "asc" ]],
		'sPaginationType': 'two_button',
		'sDom': 'flpitpil',
		'iDisplayLength': 20
	});
}
