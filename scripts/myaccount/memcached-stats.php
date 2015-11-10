<?php
/**
 * Shows memcached server extended stats
 */

use \Cogeco\Build\Task;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\Task\SshTask;

// Include the build script core
include_once __DIR__ . '/../../bootstrap.php';


// *******************************
// Script arguments
// *******************************
if (! isset($argv[1]) || ($argv[1] !== 'dev' && $argv[1] !== 'uat' && $argv[1] !== 'prod') ) {
	echo "Usage: php " . $argv[0] . " dev|uat|prod\n";
	exit(1);
}


// *******************************
// Script configuration
// *******************************
$host = $maoDevHost;
if ($argv[1] == 'uat') {
	$host = $maoUatHost;
}
else if ($argv[1] == 'prod') {
	$host = $maoProdHost;
}


// *******************************
// Start the build script
// *******************************

// ***
// Initial output
Task::log("\n---------------------------------------\n");
Task::log("-------- Getting memcached stats from " . $host->getHostname() . " ({$argv[1]})\n\n");

// ***
// Prompt for user password, if it's not hardcoded
CliTask::promptAccountPassword($cogecoAccount);

// ***
$command = "php /var/www/scripts/memcached-stats.php";
SshTask::exec($host, $command);
