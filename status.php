<?php

echo '<?xml version="1.0" ?>';

?>
<status xmlns:xs="http://www.w3.org/2001/XMLSchema">
	<instance title = "lanlist.org">
		<link title = "lanlist.org Homepage" url = "http://lanlist.org" />
	</instance>

	<resources>
		<resource id = "event0" title = "SaltLAN 15" url = "http://lanlist.org/viewEvent.php?id=1">
			<attribute key = "name" title = "Name" value = "SaltLAN 15" xs:type = "string" />
			<attribute key = "id" title = "ID" value = "1" xs:type = "uint" />
		</resource>

		<resource id = "event1" title = "SaltLAN 16" url = "http://lanlist.org/viewEvent.php?id=2">
			<attribute key = "name" title = "Name" value = "SaltLAN 16" xs:type = "string" />
			<attribute key = "id" title = "ID" value = "2" xs:type = "uint" />
		</resource>
	</resources>

	<issues>
		<issue title = "Event not published" severity = "warning">
			<resource ref = "event0" />
		</issue>
	</issues>

	<metrics>
		<metric key = "administrator.lastLogin" xs:type = "datetime" value = "00000000" />
		<metric key = "events.count.unpublished" xs:type = "uint" value = "3" />
		<metric key = "events.count.published" xs:type = "uint" value = "99" />
		<metric key = "users.count.inactive" xs:type = "uint" value = "2" />
		<metric key = "users.count.new" xs:type = "uint" value = "7" />
	</metrics>
</status>
