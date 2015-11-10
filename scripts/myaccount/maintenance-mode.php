<?php
/**
 * Script for activating, or deactivating maintenance mode (shows a 503 page)
 */

use \Cogeco\Build\Task;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\Task\SshTask;

// Include the build script core
include_once __DIR__ . '/../../bootstrap.php';


// *******************************
// Script arguments
// *******************************
if (! isset($argv[1]) || ! isset($argv[2]) ||
	($argv[1] !== 'on' && $argv[1] !== 'off') ||
	($argv[2] !== 'dev1' && $argv[2] !== 'dev2' && $argv[2] !== 'uat1' && $argv[2] !== 'uat2' && $argv[2] !== 'uat3' &&
		$argv[2] !== 'uat4' && $argv[2] !== 'prod') ) {
	echo "Usage: php " . $argv[0] . " dev1|dev2|uat1|uat2|uat3|uat4|prod\n";
	exit(1);
}


// *******************************
// Script configuration
// *******************************
$enableMaintenanceMode = false;
$envName = $argv[2];
$remoteDir = $maoDev1Dir;
$action = 'Deactivate';

if ($argv[1] == 'on') {
	$enableMaintenanceMode = true;
	$action = 'Activate';
}

if ($argv[2] == 'prod') {
	$remoteDir = $maoProdDir;
}

$remoteHost = $remoteDir->getHost();


// *******************************
// Start the build script
// *******************************

// ***
// Initial output
Task::log("\n---------------------------------------\n");
Task::log("-------- {$action} maintenance mode\n\n");

// ***
// Prompt for user password, if it's not hardcoded
CliTask::promptAccountPassword($cogecoAccount);

// ***
//$command = "php /var/www/scripts/maintenance-mode.php " . $argv[1];
$command = "php /var/www/scripts/maintenance-mode.php " . $argv[1] . ' ' . $envName;
SshTask::exec($remoteHost, $command);
