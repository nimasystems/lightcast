<?php

////////////////////////////////////////////////////////////////////////////
//////////////////////// BOOT PROCESS BELOW - DO NOT CHANGE ////////////////
////////////////////////////////////////////////////////////////////////////

// include pre-booting project based file
@include_once('../config/preboot_config.php');

/*
 * Search for the framework to boot
 */
$framework_locations = [];

if (defined('FRAMEWORK_LOCATIONS'))
{
	$custom_locations = FRAMEWORK_LOCATIONS;
	$custom_locations = is_string($custom_locations) ? [$custom_locations] : (array)$custom_locations;
	$framework_locations = array_filter($custom_locations);
	unset($custom_locations);
}

$current_dir = realpath(dirname(__FILE__).'/../');

$framework_locations = array_unique(array_merge($framework_locations, [
		$current_dir . '/framework',
		$current_dir . '/lightcast',
		'/opt/lightcast',
]));

$framework_dir = null;

unset($current_dir);

foreach ($framework_locations as $path)
{
	if (@file_exists($path . '/source/libs/boot/bootstrap.php'))
	{
		$framework_dir = $path;
		break;
	}

	unset($path);
}

unset($framework_locations);

// check if there is a match
if (!isset($framework_dir))
{
	echo 'Cannot find the Lightcast (TM) framework. Please define its location in webroot/[your-boot-file].php';
	exit(1);
}

$bootstrap_file = $framework_dir . '/source/libs/boot/bootstrap.php';

// check if we have a framework file and if we can include it
if (!include_once($bootstrap_file))
{
	echo 'Cannot attach to the Lightcast (TM) framework';
	exit(1);
}

/*
 * If framework has been found - control over the
 * booting process is returned to the parent calling file
 */

// include project configuration
require_once('../config/project_configuration.php');


