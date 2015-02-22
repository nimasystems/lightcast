<?php

////////////////////////////////////////////////////////////////////////////
//////////////////////// BOOT CONFIGURATION BEFORE DISPATCH ////////////////
////////////////////////////////////////////////////////////////////////////

/*
 * Generic debugging enabled / disabled
 */
define(DO_DEBUG, false);
define(CONFIG_ENV, 'default');
//define(CONFIG_VER, 1);

/*
 * Configuration setup
 */
$configuration = isset($configuration) ? $configuration : null;
$configuration->setIsDebugging(DO_DEBUG);
$configuration->setConfigEnvironment(CONFIG_ENV);
//$configuration->setConfigVersion(CONFIG_VER);

?>