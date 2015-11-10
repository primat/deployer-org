<?php
/**
 * Activates maintenance mode and deploys the My Account front end application
 */

use \Cogeco\Build\Config;
use \Cogeco\Build\Entity\File;
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
$build = 'prod'; // Use test for testing the script or prod for production use

// Common configs for test/prod
$workingCopy = $maoWcTrunk;
$revisionToCheckOut = 0;
$syncDestinationDir1 = $maoDevTempDir;
$syncDestinationDir2 = $maoDev1Dir;
$configDirName = 'development';
$server = 'dev1';

if ($build === 'prod') {
	// Prod setup
	$syncDestinationDir1 = $maoProdTempDir;
	$syncDestinationDir2 = $maoProdDir;
	$configDirName = 'production';
	$server = $build;
}

$maintenanceFileSource = new File(
	$workingCopy->getDir()->getPath() . 'application/config/production/',
	'maintenance_routes.php');

$maintenanceFileDestination = new File(
	$syncDestinationDir2->getPath() . 'application/config/' . $configDirName,
	'routes.php',
	$syncDestinationDir2->getHost());

$rsyncOptions1 = new RsyncOptions($maoWcTrunk->dir, $syncDestinationDir1);
$rsyncOptions1
	//->dryRun()
	->excludesAppend(array('/user_guide/', '/license.txt', 'application/logs/', '/dev/'));

$rsyncOptions2 = new RsyncOptions($syncDestinationDir1, $syncDestinationDir2);
$rsyncOptions2
	//->dryRun()
	->useSsh(FALSE)
	->excludesAppend(array('/user_guide/', '/license.txt', 'application/logs/', '/dev/',
		"/application/config/{$configDirName}/routes.php"));


// *******************************
// Start the build script
// *******************************

Config::enableLogging(TRUE, TRUE);


// ********************
// *** Initial output
//
Task::log("\n---------------------------------------\n");
Task::log("-------- Activate maintenance mode and deploy\n\n");

// Chicken quit
if ($build === 'prod') {
	CliTask::promptQuit('Deploying to production! Continue? [y/n]: ');
}

// Prompt for user password, if it's not hardcoded
CliTask::promptAccountPassword($cogecoAccount);


// ********************
// *** SVN checkout
//
SvnTask::checkoutClean($workingCopy, $revisionToCheckOut);
SvnTask::createManifestFile($workingCopy);


// ********************
// *** Activate maintenance mode
//
// Upload the maintenance route file
SshTask::uploadFile($maintenanceFileSource, $maintenanceFileDestination);


// ********************
// *** Deploy files
//
// Sync files from the deployer working copy to the temporary prod directory
FileSyncTask::sync($rsyncOptions1);

// Sync files from the intermediate prod dir to the live prod directory
Task::log("- Syncing intermediate directory {$syncDestinationDir1->path} with {$syncDestinationDir2->path}\n\n");
$rsyncCommand = FileSyncTask::getRsyncCommand($rsyncOptions2);
SshTask::exec($syncDestinationDir2->getHost(), $rsyncCommand);
Task::log("\n");


// ***
// Activate the .htaccess maintenance mode and allow users from the Cogeco network to see the app
$command = "php /var/www/scripts/maintenance.php on {$server}";
SshTask::exec($syncDestinationDir2->getHost(), $command);

// Delete the production/routes.php
SshTask::deleteFile($maintenanceFileDestination);

// And let the sanity checking begin...
