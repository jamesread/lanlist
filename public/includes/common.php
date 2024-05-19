<?php

require_once 'includes/config.php';
require_once '../vendor/autoload.php';

require_once 'includes/classes/SiteErrorHandler.php';

$eh = new SiteErrorHandler();
$eh->beGreedy();

use \libAllure\Logger;

Logger::setLogName('lanlist.org');
Logger::open();
Logger::addListener('logMessageToDatabase');

require_once 'includes/functionality/misc.php';
require_once 'includes/functionality/dbal.php';

use \libAllure\Database;
use \libAllure\DatabaseFactory;

$db = new Database(DB_DSN, DB_USER, DB_PASS);
DatabaseFactory::registerInstance($db);

use \libAllure\AuthBackendDatabase;

$authBackend = new AuthBackendDatabase();
$authBackend->registerAsDefault();

use \libAllure\Session;

Session::setSessionName('lanlistUser');
Session::start();

use \libAllure\Template;

$tpl = new Template('/var/cache/httpd/smarty/lanlist.org/');
$tpl->registerModifier('count', 'count');
$tpl->registerModifier('floatToMoney', 'floatToMoney');
$tpl->registerModifier('stripslashes', 'stripslashes');
$tpl->registerModifier('boolToString', 'boolToString');

