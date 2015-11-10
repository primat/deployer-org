<?php
/**
 * Deploys the cogeco.ca trunk from SVN to production
 */

use \Cogeco\Build\Config;
use \Cogeco\Build\Task;
use \Cogeco\Build\Entity\Dir;
use \Cogeco\Build\Entity\File;
use \Cogeco\Build\Entity\RsyncOptions;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\Task\EmailTask;
use \Cogeco\Build\Task\FileSyncTask;
use \Cogeco\Build\Task\MysqlTask;
use \Cogeco\Build\Task\SshTask;
use \Cogeco\Build\Task\SvnTask;
use \Cogeco\Build\Task\TimerTask;
use \Cogeco\Build\Task\ViewTask;

// Include the build script core
include_once __DIR__ . '/../../bootstrap.php';


// *******************************
// Script configuration
// *******************************
setlocale (LC_TIME, 'fr_CA.utf8','fra');
$build = 'prod'; // Use test for testing the script or prod for production use

// Common configs
$workingCopy = $cogecoCaWorkingCopy;
$revisionToCheckOut = 0;
$sourceDb = $localPublicDb;
$destinationDb = $prodPublicDb;
$syncDestinationDir1 = $preprodDir;
$syncDestinationDir2 = $prodDir;
$deployDatabase = TRUE;

if ($build !== 'prod') {
	// Non-prod testing
	$cogecoCaReleaseEmail->to = array(
		'mathieu.price@cogeco.com'
	);
}

$sourceDbSchema = $localPublicDb->getDbName();
$destinationDbSchema =  $preprodPublicDb->getDbName();

$rsyncOptions1 = new RsyncOptions($workingCopy->dir, $syncDestinationDir1);
$rsyncOptions1
	//->dryRun()
	->chmod('Du=rwx,Dg=rwx,Do=rwx,Fu=rw,Fg=rw,Fo=r')
	->excludesAppend(array('/dev/', '/db/', '/build.xml', '/build.properties'));

$rsyncOptions2 = new RsyncOptions(new Dir($syncDestinationDir1->getPath()), new Dir($syncDestinationDir2->getPath()));
$rsyncOptions2
	//->dryRun()
	->chmod('Du=rwx,Dg=rwx,Do=rwx,Fu=rw,Fg=rw,Fo=r')
	->useSsh(FALSE)
	->excludesAppend(array('/dev/', '/db/', '/build.xml', '/build.properties'));


// *******************************
// Start the build script
// *******************************
//
Config::enableLogging(TRUE, TRUE);


// ********************
// *** Initial output
//
Task::log("\n---------------------------------------\n");
Task::log("-------- Deploy Prod\n\n");

// Chicken quit
if ($build === 'prod') {
	CliTask::promptQuit('Deploying to production! Continue? [y/n]: ');
}

// Prompt for user password, if it's not hardcoded
CliTask::promptAccountPassword($cogecoAccount);


// *******************************
// Get the latest SVN entry logs up until the last release to production
//
$latestSvnLogEntries = SvnTask::getLatestLogEntries($workingCopy);
$projectSvnLogEntries = array();
foreach ($latestSvnLogEntries as $revision => $entry) {
	if (stripos($entry->message, "Release to Production") !== FALSE) {
		break;
	}
	else {
		$projectSvnLogEntries[$revision] = $entry;
	}
}
unset($latestSvnLogEntries);


// ********************
// *** Checkout
//
SvnTask::checkoutClean($workingCopy, $revisionToCheckOut, 0);
SvnTask::createManifestFile($workingCopy, FALSE, TRUE);


// ********************
// *** Dump DB
//
if ($deployDatabase) {
	Task::log("\n- Deploy schema {$sourceDbSchema} from {$sourceDb->host->hostname} ");
	Task::log("to {$destinationDbSchema} on {$destinationDb->getHost()->hostname}\n\n");

    // Export DB
    $mysqlDumpFile = new File($workingCopy->getDir()->getPath() . '/db/dump', 'cogeco.sql');
    MysqlTask::mysqlDump($sourceDb, $sourceDbSchema, array(), $mysqlDumpFile);
}

// ********************
// *** Prep sync to intermediate remote location
//
Task::log("- Syncing revision {$workingCopy->info->commitRevision} with {$syncDestinationDir1->getPath()} on {$syncDestinationDir1->getHost()->hostname}\n\n");

//
FileSyncTask::sync($rsyncOptions1);


// ********************
// *** Deploy
//
TimerTask::start();

// Import DB
if ($deployDatabase) {
	MysqlTask::importDump($destinationDb, $mysqlDumpFile->getPath(), $destinationDbSchema);
}

// Sync files from preprod
Task::log("- Syncing intermediate directory {$syncDestinationDir1->path} with {$syncDestinationDir2->path}\n\n");
//
$rsyncCommand = FileSyncTask::getRsyncCommand($rsyncOptions2);
SshTask::exec($syncDestinationDir1->getHost(), $rsyncCommand);

// Stop the deploy timer and log it
TimerTask::stop();

Task::log("Deploy time: " . TimerTask::getLastElapsedTime() . "\n\n");

// ********************
// *** Tag
//
if ($build === 'prod') {
	SvnTask::commit($workingCopy, "Release to Production");
}


// ********************
// *** Notify
//
$cogecoCaReleaseEmail->bodyHtml = ViewTask::load('views/template-release-html.php', array('svnEntries' => $projectSvnLogEntries, 'deployeur' => $cogecoAccount->username), TRUE);
$cogecoCaReleaseEmail->bodyText = ViewTask::load('views/template-release-text.php', array('svnEntries' => $projectSvnLogEntries, 'deployeur' => $cogecoAccount->username), TRUE);
EmailTask::sendEmail($emailConnector, $cogecoCaReleaseEmail);
