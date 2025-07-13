<?php
session_start();
// Load Config
require_once '../config/config.php';

// App Root
define('APPROOT', dirname(dirname(__FILE__)));
require_once '../config/Database.php';

// Load Helpers
require_once 'helpers/redirect.php';
require_once 'helpers/session_helper.php';

// Load Core Libraries
require_once 'Core.php';
require_once 'Controllers/Controller.php';
require_once 'Models/Model.php';
