<?php

require_once('../lib/boot.php');
require_once('../applications/backend/config/backend_configuration.class.php');

$configuration = new BackendConfiguration(realpath(dirname(__FILE__) . '/../'), new ProjectConfiguration());

@include_once('../config/boot_config.php');

lcApp::bootstrap($configuration)->dispatch();
