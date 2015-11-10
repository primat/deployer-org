<?php
/**
 * Deploys the cogeco.ca trunk from SVN to production
 */

use \Cogeco\Build\Config;
use \Cogeco\Build\Task;
use \Cogeco\Build\Task\CliTask;
use \Cogeco\Build\Task\EmailTask;
use \Cogeco\Build\Task\SvnTask;
use \Cogeco\Build\Task\ViewTask;

// Include the build script core
include_once __DIR__ . '/../../bootstrap.php';


// *******************************
// Script configuration
// *******************************
$workingCopy = $cogecoCaWorkingCopy;
setlocale (LC_TIME, 'fr_CA.utf8','fra');

// *******************************
// Calculate the time of the next pre-approved deployment window
// *******************************
$timestamp = time();
$dateTime = getdate($timestamp);
$currentTime = (int)($dateTime['hours'] . $dateTime['minutes']);

if ($currentTime > 1430) {
	if ($dateTime['wday'] > 4) {
		$timestamp = strtotime("next Monday", $dateTime['0']);
	}
	else {
		$timestamp = strtotime("+1 day", $dateTime['0']);
	}
	$dateTime = getdate($timestamp);
	$dateTime['seconds'] = 0;
	$dateTime['minutes'] = 45;
	$dateTime['hours'] = 9;
}
else if ($currentTime > 930) {
	if ($dateTime['wday'] > 4) {
		$timestamp = strtotime("next Monday", $dateTime['0']);
		$dateTime = getdate($timestamp);
	}
	$dateTime['seconds'] = 0;
	$dateTime['minutes'] = 45;
	$dateTime['hours'] = 14;
}
else {
	$dateTime['seconds'] = 0;
	$dateTime['minutes'] = 45;
	$dateTime['hours'] = 9;
}
$dateTime['0'] = strtotime(sprintf("%s-%s-%s %s:%s:%s", $dateTime['year'], $dateTime['mon'], $dateTime['mday'], $dateTime['hours'], $dateTime['minutes'], $dateTime['seconds']));
$deployTime = @ucfirst(strftime("%A le %d %B %Y à %Hh%M", $dateTime['0']));

// *******************************
// Get the latest SVN entry logs up until the last release to production
// *******************************
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
// *** Prep notification email and send it
//
$emailHtml = ViewTask::load('views/template-deploy-request-html.php', array('svnEntries' => $projectSvnLogEntries, 'deployTime' => $deployTime), TRUE);
$deployRequestEmail->bodyHtml = $emailHtml;
EmailTask::sendEmail($emailConnector, $deployRequestEmail);
