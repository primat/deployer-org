<?php
/*
 * The path to the root of the build application
 */
define('BUILD_ROOT_DIR', __DIR__);


include_once __DIR__ . '/source/php/libs/Cogeco/Build.php';
\Cogeco\Build\Build::init();


// Include the global and project specific properties
if (file_exists(SCRIPT_DIR . '/../properties-global.php')) {
	include_once SCRIPT_DIR . '/../properties-global.php';
}
// Global custom properties
if (file_exists(SCRIPT_DIR . '/../properties-global-custom.php')) {
	include_once SCRIPT_DIR . '/../properties-global-custom.php';
}
// Project specific properties
if (file_exists(SCRIPT_DIR . '/properties.php')) {
	include_once SCRIPT_DIR . '/properties.php';
}
// Project specific custom properties
if (file_exists(SCRIPT_DIR . '/properties-custom.php')) {
	include_once SCRIPT_DIR . '/properties-custom.php';
}
// Script specific properties
if (file_exists(SCRIPT_DIR . '/properties-' . SCRIPT_FILE_BASENAME . '.php')) {
	include_once SCRIPT_DIR . '/properties-' . SCRIPT_FILE_BASENAME . '.php';
}
