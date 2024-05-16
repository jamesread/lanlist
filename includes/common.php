<?php

require_once 'includes/config.php';
require_once 'vendor/autoload.php';

set_include_path(__DIR__ . "/..//vendor/jwread/lib-allure/src/main/php/" . PATH_SEPARATOR . get_include_path());
set_include_path(__DIR__ . "/..//vendor/smarty/" . PATH_SEPARATOR . get_include_path());

require_once 'includes/classes/SiteErrorHandler.php';

$eh = new SiteErrorHandler();
$eh->beGreedy();

require_once 'libAllure/Database.php';

require_once 'libAllure/Logger.php';
require_once 'includes/classes/LocalEventType.php';

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

require_once 'libAllure/User.php';
require_once 'libAllure/AuthBackend.php';
require_once 'libAllure/AuthBackendDatabase.php';
require_once 'libAllure/HtmlLinksCollection.php';

use \libAllure\AuthBackendDatabase;

$authBackend = new AuthBackendDatabase();
$authBackend->registerAsDefault();

require_once 'libAllure/Session.php';

use \libAllure\Session;

Session::setSessionName('lanlistUser');
Session::start();

use \libAllure\Template;

$tpl = new Template('/var/cache/httpd/smarty/lanlist.org/');
$tpl->registerModifier('count', 'count');
$tpl->registerModifier('floatToMoney', 'floatToMoney');
$tpl->registerModifier('stripslashes', 'stripslashes');
$tpl->registerModifier('boolToString', 'boolToString');

require_once 'libAllure/Sanitizer.php';

?>
