<?php
/**
 * Deploy script for deploying a working copy to the My Account production server
 */

use \Cogeco\Build\Task;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\MyAccount\MyAccountTask;

// Include the core of the build scripts and properties
include_once __DIR__ . '/../../source/php/bootstrap.php';

// **********
// Parse script args - Use the build ID to identify many builds in the same script
$buildId = 'uat';

if (isset($argv[1])) {
	if ($argv[1] === 'prod') {
		$buildId = 'prod';
	}
	else if ($argv[1] === 'uat') {
		$buildId = 'uat';
	}
	else if (strtolower($argv[1]) === '-help') {
		exit("Usage: {$argv[1]} [prod|uat]\n\n");
	}
}

// *******************************
// Initial output
Task::log("\n---------------------------------------\n");
Task::log("-------- Getting revision for My Account {$buildId}\n\n");

// ***
// Prompt for user password, if it's not hardcoded
CliTask::promptAccountPassword($cogecoAccount);

Task::log('Revision: ' . MyAccountTask::getRemoteRevision(($buildId === 'prod') ? $maoProdDir : $maoUatDir) . "\n\n");
