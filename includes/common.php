<?php

require_once 'includes/config.php';

require_once 'jwrCommonsPhp/Exceptions.php';
require_once 'includes/classes/SiteErrorHandler.php';

$eh = new SiteErrorHandler();
$eh->beGreedy();

require_once 'jwrCommonsPhp/Database.php';

require_once 'jwrCommonsPhp/Logger.php';
require_once 'includes/classes/LocalEventType.php';

Logger::setLogName('lanlist.org');
Logger::open();
Logger::addListener('syslogListener');
Logger::addListener('logMessageToDatabase');

require_once 'includes/functionality/misc.php';
require_once 'includes/functionality/dbal.php';

$db = new Database(DB_DSN, DB_USER, DB_PASS);
DatabaseFactory::registerInstance($db);

require_once 'jwrCommonsPhp/User.php';
require_once 'jwrCommonsPhp/AuthBackend.php';
require_once 'jwrCommonsPhp/AuthBackendDatabase.php';
require_once 'jwrCommonsPhp/HtmlLinksCollection.php';

$authBackend = new AuthBackendDatabase();
$authBackend->registerAsDefault();

require_once 'jwrCommonsPhp/Session.php';


Session::setSessionName('lanlistUser');
Session::start();

require_once 'jwrCommonsPhp/Template.php';

$tpl = new Template('/var/cache/apache2/smarty/lanlist.org/');

require_once 'jwrCommonsPhp/Sanitizer.php';

?>
