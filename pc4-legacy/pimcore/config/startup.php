<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

// configure some constants needed by pimcore
$pimcoreDocumentRoot = realpath(dirname(__FILE__) . '/../..');

$customConstants = $pimcoreDocumentRoot . "/constants.php";
if (file_exists($customConstants)) {
    include_once $customConstants;
}


if (!defined("PIMCORE_DOCUMENT_ROOT")) {
    define("PIMCORE_DOCUMENT_ROOT", $pimcoreDocumentRoot);
}
// frontend module, this is the module containing your website, please be sure that the module folder is in PIMCORE_DOCUMENT_ROOT and is named identically with this name
if (!defined("PIMCORE_FRONTEND_MODULE")) {
    define("PIMCORE_FRONTEND_MODULE", "website");
}
if (!defined("PIMCORE_PATH")) {
    define("PIMCORE_PATH", PIMCORE_DOCUMENT_ROOT . "/pimcore");
}
if (!defined("PIMCORE_PLUGINS_PATH")) {
    define("PIMCORE_PLUGINS_PATH", PIMCORE_DOCUMENT_ROOT . "/plugins");
}

// website module specific
if (!defined("PIMCORE_WEBSITE_PATH")) {
    define("PIMCORE_WEBSITE_PATH", PIMCORE_DOCUMENT_ROOT . "/" . PIMCORE_FRONTEND_MODULE);
}


if (is_array($_SERVER)
    && array_key_exists("HTTP_X_PIMCORE_UNIT_TEST_REQUEST", $_SERVER)
    && in_array($_SERVER["REMOTE_ADDR"], ["127.0.0.1", $_SERVER["SERVER_ADDR"]])) {
    // change the var directory for unit tests
    if (!defined("PIMCORE_WEBSITE_VAR")) {
        define("PIMCORE_WEBSITE_VAR", PIMCORE_DOCUMENT_ROOT . "/tests/tmp/var");
    }
} else {
    // use the default /website/var directory
    if (!defined("PIMCORE_WEBSITE_VAR")) {
        define("PIMCORE_WEBSITE_VAR", PIMCORE_WEBSITE_PATH . "/var");
    }
}

if (!defined("PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY")) {
    define("PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY", PIMCORE_WEBSITE_PATH . "/config");
}

if (!defined("PIMCORE_CONFIGURATION_DIRECTORY")) {
    define("PIMCORE_CONFIGURATION_DIRECTORY", PIMCORE_WEBSITE_VAR . "/config");
}
if (!defined("PIMCORE_ASSET_DIRECTORY")) {
    define("PIMCORE_ASSET_DIRECTORY", PIMCORE_WEBSITE_VAR . "/assets");
}
if (!defined("PIMCORE_VERSION_DIRECTORY")) {
    define("PIMCORE_VERSION_DIRECTORY", PIMCORE_WEBSITE_VAR . "/versions");
}
if (!defined("PIMCORE_WEBDAV_TEMP")) {
    define("PIMCORE_WEBDAV_TEMP", PIMCORE_WEBSITE_VAR . "/webdav");
}
if (!defined("PIMCORE_LOG_DIRECTORY")) {
    define("PIMCORE_LOG_DIRECTORY", PIMCORE_WEBSITE_VAR . "/log");
}
if (!defined("PIMCORE_LOG_DEBUG")) {
    define("PIMCORE_LOG_DEBUG", PIMCORE_LOG_DIRECTORY . "/debug.log");
}
if (!defined("PIMCORE_LOG_FILEOBJECT_DIRECTORY")) {
    define("PIMCORE_LOG_FILEOBJECT_DIRECTORY", PIMCORE_LOG_DIRECTORY . "/fileobjects");
}
if (!defined("PIMCORE_LOG_MAIL_TEMP")) {
    define("PIMCORE_LOG_MAIL_TEMP", PIMCORE_LOG_DIRECTORY . "/mail");
}
if (!defined("PIMCORE_TEMPORARY_DIRECTORY")) {
    define("PIMCORE_TEMPORARY_DIRECTORY", PIMCORE_WEBSITE_VAR . "/tmp");
}
if (!defined("PIMCORE_CACHE_DIRECTORY")) {
    define("PIMCORE_CACHE_DIRECTORY", PIMCORE_WEBSITE_VAR . "/cache");
}
if (!defined("PIMCORE_CLASS_DIRECTORY")) {
    define("PIMCORE_CLASS_DIRECTORY", PIMCORE_WEBSITE_VAR . "/classes");
}
if (!defined("PIMCORE_CUSTOMLAYOUT_DIRECTORY")) {
    define("PIMCORE_CUSTOMLAYOUT_DIRECTORY", PIMCORE_CLASS_DIRECTORY . "/customlayouts");
}
if (!defined("PIMCORE_BACKUP_DIRECTORY")) {
    define("PIMCORE_BACKUP_DIRECTORY", PIMCORE_WEBSITE_VAR . "/backup");
}
if (!defined("PIMCORE_RECYCLEBIN_DIRECTORY")) {
    define("PIMCORE_RECYCLEBIN_DIRECTORY", PIMCORE_WEBSITE_VAR . "/recyclebin");
}
if (!defined("PIMCORE_SYSTEM_TEMP_DIRECTORY")) {
    define("PIMCORE_SYSTEM_TEMP_DIRECTORY", PIMCORE_WEBSITE_VAR . "/system");
}
if (!defined("PIMCORE_LOG_MAIL_PERMANENT")) {
    define("PIMCORE_LOG_MAIL_PERMANENT", PIMCORE_WEBSITE_VAR . "/email");
}
if (!defined("PIMCORE_USERIMAGE_DIRECTORY")) {
    define("PIMCORE_USERIMAGE_DIRECTORY", PIMCORE_WEBSITE_VAR . "/user-image");
}


// setup include paths
// include paths defined in php.ini are ignored because they're causing problems with open_basedir, see PIMCORE-1233
// it also improves the performance when reducing the amount of include paths, you can of course add additional paths anywhere in your code (/website)
$includePaths = [
    PIMCORE_PATH . "/lib",
    PIMCORE_WEBSITE_PATH . "/lib",
    PIMCORE_WEBSITE_PATH . "/models",
    // we need to include the path to the ZF1, because we cannot remove all require_once() out of the source
    // see also: Pimcore\Composer::zendFrameworkOptimization()
    // actually the problem is 'require_once 'Zend/Loader.php';' in Zend/Loader/Autoloader.php
    PIMCORE_DOCUMENT_ROOT . "/vendor/zendframework/zendframework1/library/",
];
set_include_path(implode(PATH_SEPARATOR, $includePaths) . PATH_SEPARATOR);

// composer autoloader
/** @var $loader \Composer\Autoload\ClassLoader */
$loader = require PIMCORE_DOCUMENT_ROOT . "/vendor/autoload.php";
// tell the autoloader where to find Pimcore's generated class stubs
// this is primarily necessary for tests and custom class directories, which are not covered in composer.json
$loader->addPsr4('Pimcore\\Model\\Object\\', PIMCORE_CLASS_DIRECTORY . '/Object');

// helper functions
include(dirname(__FILE__) . "/helper.php");

// setup zend framework and pimcore
require_once PIMCORE_PATH . "/lib/Pimcore.php";
require_once PIMCORE_PATH . "/lib/Pimcore/Logger.php";

// register class map loader => speed
$autoloaderClassMapFiles = [
    PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/autoload-classmap.php",
    PIMCORE_CONFIGURATION_DIRECTORY . "/autoload-classmap.php",
];

foreach ($autoloaderClassMapFiles as $autoloaderClassMapFile) {
    if (file_exists($autoloaderClassMapFile)) {
        $classMapAutoLoader = new \Zend_Loader_ClassMapAutoloader([$autoloaderClassMapFile]);
        spl_autoload_register([$classMapAutoLoader, 'autoload'], true, false);
        break;
    }
}


$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->suppressNotFoundWarnings(true);
$autoloader->setFallbackAutoloader(false);
$autoloader->registerNamespace('Pimcore');


// re-register Composer's autoloader to put him on top of the stack, especially before the ZF Autoloader
$loader->unregister();
$loader->register(true);

// compatibility loader must have the top priority
$compatibilityClassLoader = new \Pimcore\Loader\CompatibilityAutoloader($loader);
$compatibilityClassLoader->register(true);


// generic pimcore startup
\Pimcore::setSystemRequirements();
\Pimcore::initAutoloader();
\Pimcore::initConfiguration();
\Pimcore::setupFramework();
\Pimcore::initLogger();

if (\Pimcore\Config::getSystemConfig()) {
    // we do not initialize plugins if pimcore isn't installed properly
    // reason: it can be the case that plugins use the database in isInstalled() witch isn't available at this time
    \Pimcore::initPlugins();
}

// do some general stuff
// this is just for compatibility reasons, pimcore itself doesn't use this constant anymore
if (!defined("PIMCORE_CONFIGURATION_SYSTEM")) {
    define("PIMCORE_CONFIGURATION_SYSTEM", \Pimcore\Config::locateConfigFile("system.php"));
}

$websiteStartup = \Pimcore\Config::locateConfigFile("startup.php");
if (@is_file($websiteStartup)) {
    include_once($websiteStartup);
}

// on pimcore shutdown
register_shutdown_function(function () {
    \Pimcore::getEventManager()->trigger("system.shutdown");
});

include_once("event-listeners.php");
