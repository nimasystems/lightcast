<?php
/*
 * Lightcast - A PHP MVC Framework
* Copyright (C) 2005 Nimasystems Ltd
*
* This program is NOT free software; you cannot redistribute and/or modify
* it's sources under any circumstances without the explicit knowledge and
* agreement of the rightful owner of the software - Nimasystems Ltd.
*
* This program is distributed WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
* PURPOSE.  See the LICENSE.txt file for more information.
*
* You should have received a copy of LICENSE.txt file along with this
* program; if not, write to:
* NIMASYSTEMS LTD
* Plovdiv, Bulgaria
* ZIP Code: 4000
* Address: 95 "Kapitan Raycho" Str.
* E-Mail: info@nimasystems.com
*/

/**
 * File Description
 * @package FileCategory
 * @subpackage FileSubcategory
 * @changed $Id: bootstrap.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */

// setup proper initial error reporting before booting
if (defined('DO_DEBUG')) {
    // Enable showing all errors until app boots
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Disable showing all errors until app boots
    error_reporting(0);
    ini_set('display_errors', 0);
}

// check the PHP Version Requirement
if (!version_compare(PHP_VERSION, '5.3.0', '>=')) {
    echo 'Lightcast PHP Framework requires PHP Version 5.3.0 or higher';
    exit(2);
}

// define the current lightcast version
define('LIGHTCAST_VER', '1.5.1.1469');
define('LC_VER_MAJOR', 1);
define('LC_VER_MINOR', 5);
define('LC_VER_BUILD', 1);
define('LC_VER_REVISION', 1469);
define('LC_VER', LIGHTCAST_VER);

// verify if an older app is trying to boot into the framework
if (defined('APP_VER') && version_compare(APP_VER, LIGHTCAST_VER, '<=')) {
    echo 'The application is too old to run on Lightcast ' . LC_VER . '. It must be upgraded first.';
    exit(2);
}

// check min allowed framework version
if (defined('MIN_LC_VER')) {
    $v = version_compare(LC_VER, MIN_LC_VER);

    if ($v < 0) {
        echo 'Application requires the minimum version of Lightcast to be ' . MIN_LC_VER . ' but the current one is ' . LC_VER;
        exit(2);
    }
}

// check max allowed framework version
if (defined('MAX_LC_VER')) {
    $v = version_compare(LC_VER, MAX_LC_VER);

    if ($v > 0) {
        echo 'Application requires the maximum version of Lightcast to be ' . MAX_LC_VER . ' but the current one is ' . LC_VER;
        exit(2);
    }
}

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

define('PHP_GREATER_EQUAL_54', (PHP_VERSION_ID >= 50400));

// default timezone
date_default_timezone_set('UTC');

// set mb_string encoding
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding("UTF-8");
}

define('ROOT', realpath(dirname(__FILE__) . '/../../../'));
define('DS', DIRECTORY_SEPARATOR);

// enable profiling
if (defined('PROFILER_ENABLED') && PROFILER_ENABLED) {
    // include the 'prepend' profile start file
    require(ROOT . DS . 'source' . DS . 'libs' . DS . 'debug' . DS . 'profile_prepend.php');
}

// include lightcast in the filepath
set_include_path(get_include_path() . PATH_SEPARATOR . ROOT . DS . 'source' . DS . 'libs' . PATH_SEPARATOR .
    ROOT . DS . 'source' . DS . '3rdparty' . DS . 'propel' . DS . 'runtime' . DS . 'lib');

// load shortcut funcs
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'base' . DS . 'shortcuts.php');

// debugging
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'debug' . DS . 'iDebuggable.class.php');

// load utils
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'utils' . DS . 'lcSys.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'utils' . DS . 'lcInflector.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'utils' . DS . 'lcStrings.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'utils' . DS . 'lcFiles.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'utils' . DS . 'lcArrays.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'utils' . DS . 'lcMisc.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'utils' . DS . 'lcDirs.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'utils' . DS . 'lcVm.class.php');

// interfaces

require(ROOT . DS . 'source' . DS . 'libs' . DS . 'base' . DS . 'iProvidesCapabilities.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'base' . DS . 'iRequiresCapabilities.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'logger' . DS . 'iLoggable.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'i18n' . DS . 'iI18nProvider.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'database' . DS . 'iSupportsDbModelOperations.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'caching' . DS . 'iCacheable.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'app' . DS . 'iAppDelegate.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'app' . DS . 'iSupportsVersions.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'autoload' . DS . 'iSupportsAutoload.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'database' . DS . 'iSupportsDbModels.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'database' . DS . 'iSupportsDbViews.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'database' . DS . 'iDatabaseModelManager.class.php');

// load base classes
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'base' . DS . 'lcObj.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'base' . DS . 'lcSysObj.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'app' . DS . 'lcApp.class.php');

// profiler
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'profiler' . DS . 'lcProfiler.class.php');

// configuration
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcConfigHandler.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcEnvConfigHandler.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'lcConfiguration.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'lcProjectConfiguration.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'lcApplicationConfiguration.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'lcWebConfiguration.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'lcWebManagementConfiguration.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'lcConsoleConfiguration.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'lcWebServiceConfiguration.class.php');

// exceptions
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'exceptions' . DS . 'iDomainException.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'exceptions' . DS . 'iHTTPException.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'exceptions' . DS . 'lcException.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'exceptions' . DS . 'lcSystemException.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'exceptions' . DS . 'lcConfigException.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'exceptions' . DS . 'lcPHPException.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'exceptions' . DS . 'lcAssertException.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'exceptions' . DS . 'lcIOException.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'exceptions' . DS . 'lcAuthException.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'exceptions' . DS . 'lcAccessDeniedException.class.php');
require(ROOT . DS . 'source' . DS . 'libs' . DS . 'exceptions' . DS . 'lcInvalidArgumentException.class.php');

// 3rdParty widely used libraries

// PHPMailer
require_once(ROOT . DS . 'source' . DS . '3rdparty' . DS . 'PHPMailer' . DS . 'class.phpmailer.php');
require_once(ROOT . DS . 'source' . DS . '3rdparty' . DS . 'PHPMailer' . DS . 'class.pop3.php');
require_once(ROOT . DS . 'source' . DS . '3rdparty' . DS . 'PHPMailer' . DS . 'class.smtp.php');

// Browser Detection
require_once(ROOT . DS . 'source' . DS . '3rdparty' . DS . 'browser_detection' . DS . 'Browser.php');

// GlobToRegex
require_once(ROOT . DS . 'source' . DS . '3rdparty' . DS . 'GlobToRegex' . DS . 'sfGlobToRegex.class.php');

// Spyc
require_once(ROOT . DS . 'source' . DS . '3rdparty' . DS . 'spyc' . DS . 'spyc.php');

// Thumbnail GD
require_once(ROOT . DS . 'source' . DS . '3rdparty' . DS . 'thumbnailGD' . DS . 'thumbnail.inc.php');

// uagent_info
require_once(ROOT . DS . 'source' . DS . '3rdparty' . DS . 'uagent_info' . DS . 'uagent_info.class.php');

// UTF8Compat - too heavy on memory - must be included separately
//require_once(ROOT . DS . 'source' . DS . '3rdparty' . DS . 'UTF8Compat' . DS . 'UTF8Compat.php');