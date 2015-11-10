<?php
/**
 * Script for checking out a working copy
 */

use \Cogeco\Build\Config;
use \Cogeco\Build\Entity\RsyncOptions;
use \Cogeco\Build\Task;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\Task\EmailTask;
use \Cogeco\Build\Task\FileSyncTask;
use \Cogeco\Build\Task\SshTask;
use \Cogeco\Build\Task\SvnTask;
use \Cogeco\Build\Task\ViewTask;

// Include the build script core
include_once __DIR__ . '/../../bootstrap.php';


// *******************************
// Script configuration
// *******************************


// *******************************
// Start the build script
// *******************************


// ********************
// *** Prompt for user input
//
$workingCopy = CliTask::PromptWorkingCopy();
Task::log("Checking out " . $workingCopy->id . "\n\n");

// Prompt for user password, if it's not hardcoded
CliTask::promptAccountPassword($cogecoAccount);


// ********************
// *** SVN checkout
//
SvnTask::checkoutClean($workingCopy, 0);

// Create the supplemental files for non tag working copies
if (stripos($workingCopy->id, 'tag') === FALSE) {
	SvnTask::createManifestFile($workingCopy);

	// Duplicate the .htaccess file as a backup for when switching to maintenance mode
	Task::log("- Creating a duplicate of the root .htaccess\n");
	copy($workingCopy->dir->path . '.htaccess', $workingCopy->dir->path . '.htaccess-default');
	Task::log("Created file {$workingCopy->dir->path}.htaccess-default\n\n");
}
