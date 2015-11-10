<?php
/**
 *
 */

use \Cogeco\Build\Config;
use \Cogeco\Build\Task;
use \Cogeco\Build\Entity\RsyncOptions;
use \Cogeco\Build\Entity\WorkingCopy;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\Task\FileSyncTask;
use \Cogeco\Build\Task\SvnTask;

// Include the build script core
include_once __DIR__ . '/../../bootstrap.php';


// *******************************
// Script configuration
// *******************************


// *******************************
// Start the build script
// *******************************

Config::enableLogging();


// ********************
// *** Initial output
//
Task::log("\n---------------------------------------\n");
Task::log("-------- Sync repository\n\n");
// Prompt for user password, if it's not hardcoded
CliTask::promptAccountPassword($cogecoAccount);


// ********************
// Prompt user input
//
// Prompt user to input a local folder to sync
$workingCopy = CliTask::promptRepo();
Task::log("You chose " . $workingCopy->getRepoUrl() . "\n\n");


// Prompt user to chose a remote folder to sync to
$selectedDir = CliTask::promptDir(array(
	$maoDev1Dir,
	$maoDev2Dir,
	$maoUat1Dir,
	$maoUat2Dir,
	$maoUat3Dir,
	$maoUat4Dir,
	$maoUat5Dir,
));
Task::log("You chose " . $selectedDir->getPath() . "\n\n");

CliTask::promptQuit('Continue? [y/n]: ');

// ********************
// *** Checkout
//
SvnTask::checkoutClean($workingCopy);
SvnTask::createManifestFile($workingCopy);


// ********************
// Sync files and folders
//
$rsyncOptions = new RsyncOptions($workingCopy->dir, $selectedDir);
$rsyncOptions
	//->dryRun()
	//->deleteExcluded()
	->excludesAppend(array('/user_guide/', '/license.txt', '/vendor', 'application/logs/', '/dev/'));
FileSyncTask::sync($rsyncOptions);
