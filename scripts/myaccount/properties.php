<?php
/**
 * Common deployment script properties for My Account FE project
 */

use \Cogeco\Build\Entity\Dir;
use \Cogeco\Build\Entity\Email;
use \Cogeco\Build\Entity\Host;
use \Cogeco\Build\Entity\WorkingCopy;

// ******************************************
// There should be no need to change any of the variables below...
//
$projectRepoBaseUrl = '';
$projectRepoTagBaseUrl = $projectRepoBaseUrl . '/tags/prod';

// ***
// The mao hosts
$maoDevHost = new Host('budccwebweb6', $cogecoAccount, 'My Account Dev');
$maoUatHost = new Host('budccwebweb4', $cogecoAccount, 'My Account UAT');
$maoProdHost = new Host('bupccwebweb2', $cogecoAccount, 'My Account Prod');

// ***
// The directories were the files are deployed to
$maoDev1Dir = new Dir('/var/www/dev1', $maoDevHost);
$maoDev2Dir = new Dir('/var/www/dev2', $maoDevHost);
$maoDev3Dir = new Dir('/var/www/dev3', $maoDevHost);
$maoDevTempDir = new Dir('/var/www/deploy_temp', $maoDevHost);

$maoUat1Dir = new Dir('/var/www/uat1', $maoUatHost);
$maoUat2Dir = new Dir('/var/www/uat2', $maoUatHost);
$maoUat3Dir = new Dir('/var/www/uat3', $maoUatHost);
$maoUat4Dir = new Dir('/var/www/uat4', $maoUatHost);
$maoUat5Dir = new Dir('/var/www/uat5', $maoUatHost);
$maoUat6Dir = new Dir('/var/www/uat6', $maoUatHost);
$maoUat7Dir = new Dir('/var/www/uat7', $maoUatHost);
$maoUatTempDir = new Dir('/var/www/deploy_temp', $maoUatHost);

$maoEmptyDir = new Dir('/var/www/rev_proxy', $maoProdHost);
$maoProdDir = new Dir('/var/www/myaccount_prod', $maoProdHost);
$maoProdTempDir = new Dir('/var/www/deploy_temp', $maoProdHost);

// ***
// SVN properties
$maoWcTrunk = new WorkingCopy(
	'myaccount-trunk', // The name/id of the project, also used as a folder name for the working copy
	$projectRepoBaseUrl,
	'/trunk',
	$cogecoAccount // User access account
);

$maoFsecure = new WorkingCopy(
	'myaccount-fsecure', // The name/id of the project, also used as a folder name for the working copy
	$projectRepoBaseUrl,
	'/branches/fsecure',
	$cogecoAccount // User access account
);


//
// A working copy for tags - used for rolling back
$maoTag = new WorkingCopy(
	'myaccount-tag',
	$projectRepoTagBaseUrl,
	'',
	$cogecoAccount // User access account
);

// Email related data
$maoReleaseEmail = new Email(
	array('web-marketing-build@cogeco.com', 'Web Marketing'), // from
	array(),
	"My Account deployment - Déploiement Mon Compte" // Subject
);

if (! empty($cogecoAccount->emailAddress)) {
	$maoReleaseEmail->to[] = $cogecoAccount->emailAddress;
}

$maoReleaseEmailRecipients = array(

);
