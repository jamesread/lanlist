<?php

require_once 'includes/config.php';

if (!defined('SITE_BASE_URL')) {
    define('SITE_BASE_URL', 'https://lanlist.info');
}

$baseDir = realpath(__DIR__ . '/../../');

require_once $baseDir . '/vendor/autoload.php';

require_once 'includes/classes/SiteErrorHandler.php';

$eh = new SiteErrorHandler();
//$eh->beGreedy();

use libAllure\Logger;

Logger::setLogName('lanlist.org');
Logger::open();
Logger::addListener('logMessageToDatabase');

require_once 'includes/functionality/misc.php';
require_once 'includes/functionality/apprise.php';
require_once 'includes/functionality/organizer_visibility.php';
require_once 'includes/functionality/seo.php';
require_once 'includes/functionality/dbal.php';
require_once 'includes/functionality/useful_related_sites.php';

$eh->addListener('notifyErrorViaApprise');

use libAllure\Database;
use libAllure\DatabaseFactory;

$db = new Database(DB_DSN, DB_USER, DB_PASS);
DatabaseFactory::registerInstance($db);

use libAllure\AuthBackendDatabase;

$authBackend = new AuthBackendDatabase();
$authBackend->registerAsDefault();

use libAllure\Session;

Session::setSessionName('lanlistUser');
Session::start();

use libAllure\Template;

$tpl = new Template('/var/cache/httpd/smarty/lanlist.org/');
$tpl->registerModifier('count', 'count');
$tpl->registerModifier('floatToMoney', 'floatToMoney');
$tpl->registerModifier('stripslashes', 'stripslashes');
$tpl->registerModifier('boolToString', 'boolToString');
$tpl->registerModifier('lookupField', 'lookupField');
$tpl->registerModifier('var_dump', 'var_dump');
