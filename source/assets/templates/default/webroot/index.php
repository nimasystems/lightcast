<?php

require_once('../lib/boot.php');
require_once('../applications/frontend/config/frontend_configuration.class.php');

$configuration = new FrontendConfiguration(realpath(dirname(__FILE__) . '/../'), new ProjectConfiguration());

@include_once('../config/boot_config.php');

lcApp::bootstrap($configuration)->dispatch();

?>