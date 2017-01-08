<?php

use \libAllure\LogEventType;

abstract class LocalEventType extends LogEventType {
	const CREATE_EVENT = 2001;
	const CREATE_USER = 2002;
	const CREATE_VENUE = 2003;
	const CREATE_ORGANIZER = 2004;
	const DELETE_EVENT = 2005;
	const DELETE_USER = 2006;
	const DELETE_VENUE = 2007;
	const DELETE_ORGANIZER = 2008;
	const EDIT_EVENT = 2009;
	const EDIT_USER = 2010;
	const REQUEST_ORGANIZER = 2011;
	const EDIT_ORGANIZER = 2012;
}

?>
