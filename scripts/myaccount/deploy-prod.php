<?php
/**
 * Main deploy script for My Account. Pushes the latest working copy of the trunk to the SVN server
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
$build = 'prod'; // Use test for testing the script or prod for production use

// Common configs for test/prod
$workingCopy = $maoWcTrunk;
$revisionToCheckOut = 0;
$syncDestinationDir1 = $maoDevTempDir;
$syncDestinationDir2 = $maoDev1Dir;

if ($build === 'prod') {
	// Prod setup
	$syncDestinationDir1 = $maoProdTempDir;
	$syncDestinationDir2 = $maoProdDir;
	$maoReleaseEmail->to = $maoReleaseEmailRecipients;
}

$rsyncOptions1 = new RsyncOptions($maoWcTrunk->dir, $syncDestinationDir1);
$rsyncOptions1
	//->dryRun()
	->excludesAppend(array('/user_guide/', '/license.txt', 'application/logs/', '/dev/'));

$rsyncOptions2 = new RsyncOptions($syncDestinationDir1, $syncDestinationDir2);
$rsyncOptions2
	//->dryRun()
	->useSsh(FALSE)
	->excludesAppend(array('/user_guide/', '/license.txt', 'application/logs/', '/dev/'
	,'/application/config/production/routes.php'));


// *******************************
// Start the build script
// *******************************

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


// ********************
// *** SVN checkout
//
SvnTask::checkoutClean($workingCopy, $revisionToCheckOut);
SvnTask::createManifestFile($workingCopy);


// ********************
// *** Validate build
//

$lastTagRevision = SvnTask::getLastTagRevision($maoTag);
Task::log("- Current revision: {$workingCopy->info->commitRevision}\n");
Task::log("- Last tag commit revision: {$lastTagRevision}\n");

if ($build === 'prod' && $workingCopy->info->commitRevision <= $lastTagRevision) {
	Task::log("The revision you want to tag is lower than or equal to the last tagged release revision\n");
	Task::log("Custom tags should be created manually\n\n");
	exit;
}

// ********************
// *** Prep notification email
//
$logEntries = SvnTask::getLogEntries($workingCopy, $lastTagRevision + 1, $workingCopy->info->commitRevision);
// Convert log entries into text and html
$changesText = ViewTask::getLogEntriesText($logEntries);
$changesHtml = ViewTask::getLogEntriesHtml($logEntries);
// Load the email templates with the changes
$emailText = ViewTask::load('views/template-release-text.php', array('changes' => $changesText), TRUE);
$emailHtml = ViewTask::load('views/template-release-html.php', array('changes' => $changesHtml), TRUE);
// Create the emails as files
$emailFileBaseName = "release-prod-r{$workingCopy->info->commitRevision}";
EmailTask::createEmailFiles($emailFileBaseName, $emailHtml, $emailText);


// ********************
// *** Deploy intermediate
//
// Sync files from the working copy to the temporary prod directory
FileSyncTask::sync($rsyncOptions1);
//
Task::log("- Syncing intermediate directory {$syncDestinationDir1->path} with {$syncDestinationDir2->path}\n\n");
//
$rsyncCommand = FileSyncTask::getRsyncCommand($rsyncOptions2);
SshTask::exec($syncDestinationDir2->getHost(), $rsyncCommand);
Task::log("\n");

// ********************
// *** Tag release
//
if ($build === 'prod') {
	SvnTask::tagRelease($maoTag, $workingCopy->dir->path, "Release to production - Revision: {$workingCopy->info->commitRevision}");
}


// ********************
// *** Notify
//
$maoReleaseEmail->bodyHtml = $emailHtml;
$maoReleaseEmail->bodyText = $emailText;
EmailTask::sendEmail($emailConnector, $maoReleaseEmail);
