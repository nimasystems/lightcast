<?php

require_once('../lib/boot.php');
require_once('../config/api_configuration.class.php');

$configuration = new ApiConfiguration(realpath(dirname(__FILE__) . '/../'), new ProjectConfiguration());

@include_once('../config/boot_config.php');

lcApp::bootstrap($configuration)->dispatch();

