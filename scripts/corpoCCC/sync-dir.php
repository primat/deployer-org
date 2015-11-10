<?php
/**
 * Deploys the cogeco.ca trunk from SVN to production, including the database
 */

use \Cogeco\Build\Config;
use \Cogeco\Build\Task;
use \Cogeco\Build\Entity\RsyncOptions;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\Task\FileSyncTask;
use \Cogeco\Build\Task\SshTask;

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
Task::log("-------- Sync directories\n\n");
// Prompt for user password, if it's not hardcoded
CliTask::promptAccountPassword($cogecoAccount);


// ********************
// Prompt user input
//
// Prompt user to input a local folder to sync
$localDir = CliTask::promptLocalSyncDir();
Task::log("You chose " . $localDir->getPath() . "\n\n");


// Prompt user to chose a remote folder to sync to
$selectedDir = CliTask::promptDir(array(
	$devDir
));
Task::log("You chose " . $selectedDir->getPath() . "\n\n");

CliTask::promptQuit('Continue? [y/n]: ');


// ********************
// Sync files and folders
//
$rsyncOptions = new RsyncOptions($localDir, $selectedDir);
$rsyncOptions
	//->dryRun()
	->chmod('Du=rwx,Dg=rwx,Do=rwx,Fu=rw,Fg=rw,Fo=r')
	->excludesAppend(array('/dev/', '/db/', '/vendor/'));
FileSyncTask::sync($rsyncOptions);
