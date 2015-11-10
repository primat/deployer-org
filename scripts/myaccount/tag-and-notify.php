<?php
/**
 * Tags a working copy (from a fresh checkout) and send a release notification email
 */

use \Cogeco\Build\Config;
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

$remoteHost = $maoDevHost;

// Common configs for test/prod
$workingCopy = $maoWcTrunk;
$revisionToCheckOut = 0;

if ($build === 'prod') {
	$maoReleaseEmail->to = $maoReleaseEmailRecipients;
	$remoteHost = $maoProdHost;
}


// *******************************
// Start the build script
// *******************************

Config::enableLogging(TRUE, TRUE);


// ********************
// *** Initial output
//
Task::log("\n---------------------------------------\n");
Task::log("-------- Tagging release, send email notification and deactivate maintenance mode\n\n");

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

if (/*$build === 'prod' && */ $workingCopy->info->commitRevision <= $lastTagRevision) {
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
// *** Tag release
//
if ($build === 'prod') {
	SvnTask::tagRelease($maoTag, $workingCopy->dir->path, "Release to production - Revision: {$workingCopy->info->commitRevision}\n");
}


// ********************
// *** Notify
//
$maoReleaseEmail->bodyHtml = $emailHtml;
$maoReleaseEmail->bodyText = $emailText;
try {
	EmailTask::sendEmail($emailConnector, $maoReleaseEmail);
}
catch(\Exception $e) {
	Task::log($e->getMessage());
}

// ********************
// *** Deactivate maintenance mode
//
$command = "php /var/www/scripts/maintenance-mode.php off prod";
SshTask::exec($remoteHost, $command);
