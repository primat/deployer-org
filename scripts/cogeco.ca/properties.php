<?php
/**
 * Properties for cogeco.ca
 */

use \Cogeco\Build\Entity\Database\Mysql;
use \Cogeco\Build\Entity\Account;
use \Cogeco\Build\Entity\Dir;
use \Cogeco\Build\Entity\Email;
use \Cogeco\Build\Entity\Host;
use \Cogeco\Build\Entity\WorkingCopy;


// ******************
// The list of various accounts
// SSH accounts

// DB accounts
$localDevDbAccount = new Account('root');
$deployDbDevAccount = new Account('upd_cogeco');
$deployDbProdAccount = new Account('upd_cogeco');


// ******************
// Servers / Clients / Hosts / Machines / etc
// Web servers
$devHost = new Host('', $cogecoAccount, 'uat/dev');
$prodHost = new Host('', $cogecoAccount, 'prod/preprod');
$prodDrHost = new Host('', $cogecoAccount, 'prod/preprod DR');

// Database servers
$devDbHost = new Host('', $deployDbDevAccount, 'dev/uat/preprod');
$prodDbHost = new Host('', $deployDbProdAccount, 'prod');
$prodDrDbHost = new Host('', $deployDbProdAccount, 'prod DR');


// ******************
// Directories and files
$devDir = new Dir('/var/www/dev/docroot/', $devHost);
$dev1Dir = new Dir('/var/www/dev1/docroot/', $devHost);
$dev2Dir = new Dir('/var/www/dev2/docroot/', $devHost);
$dev3Dir = new Dir('/var/www/dev3/docroot/', $devHost);
$dev4Dir = new Dir('/var/www/dev4/docroot/', $devHost);
$dev5Dir = new Dir('/var/www/dev5/docroot/', $devHost);
$dev6Dir = new Dir('/var/www/dev6/docroot/', $devHost);
$dev7Dir = new Dir('/var/www/dev7/docroot/', $devHost);
$dev8Dir = new Dir('/var/www/dev8/docroot/', $devHost);
$dev9Dir = new Dir('/var/www/dev9/docroot/', $devHost);
$dev10Dir = new Dir('/var/www/dev10/docroot/', $devHost);
$dev11Dir = new Dir('/var/www/dev11/docroot/', $devHost);
$dev12Dir = new Dir('/var/www/dev12/docroot/', $devHost);
$uatDir = new Dir('/var/www/uat/docroot/', $devHost);
$uat1Dir = new Dir('/var/www/uat1/docroot/', $devHost);
$uat2Dir = new Dir('/var/www/uat2/docroot/', $devHost);
$uat3Dir = new Dir('/var/www/uat3/docroot/', $devHost);
$uat4Dir = new Dir('/var/www/uat4/docroot/', $devHost);
$uat5Dir = new Dir('/var/www/uat5/docroot/', $devHost);
$uat6Dir = new Dir('/var/www/uat6/docroot/', $devHost);
$uat7Dir = new Dir('/var/www/uat7/docroot/', $devHost);
$uat8Dir = new Dir('/var/www/uat8/docroot/', $devHost);
$uat9Dir = new Dir('/var/www/uat9/docroot/', $devHost);
$uat10Dir = new Dir('/var/www/uat10/docroot/', $devHost);
$uat11Dir = new Dir('/var/www/uat11/docroot/', $devHost);
$uat12Dir = new Dir('/var/www/uat12/docroot/', $devHost);
$preprodDir = new Dir('/var/www/preprod/docroot/', $prodHost);
$prodDir = new Dir('/var/www/prod/docroot/', $prodHost);
$preprodDrDir = new Dir('/var/www/preprod/docroot/', $prodDrHost);
$prodDrDir = new Dir('/var/www/prod/docroot/', $prodDrHost);


// ******************
// Cogeco.ca working copy
$svnBaseUrl = '';

//OLD 'https://source.cogeco.com/repository/corp/marketingweb/trunk',
$cogecoCaWorkingCopy = new WorkingCopy(
	'cogeco.ca-trunk',
	$svnBaseUrl . '/trunk',
	'',
	$cogecoAccount
);
//OLD 'https://source.cogeco.com/repository/corp/marketingweb/branches/phoenix',
$phoenixWorkingCopy = new WorkingCopy(
	'cogeco.ca-phoenix',
	$svnBaseUrl . '/branches/phoenix/',
	'',
	$cogecoAccount
);
$TiVoContestQC = new WorkingCopy(
	'cogeco.ca-contest-qc',
	$svnBaseUrl . '/branches/LA_contest_widget_20150324',
	'',
	$cogecoAccount
);


// ******************
// Databases
// LOCAL DEV
$localPublicDb = new Mysql('cogecouat', $localDevDbAccount, $localDevHost);
//$localCmsDb = new Mysql('cms', $localDevDbAccount, $devHost);

// DEV
$devPublicDb = new Mysql('cogeco_dev', $deployDbDevAccount, $devDbHost);
$dev1PublicDb = new Mysql('cogeco_dev1', $deployDbDevAccount, $devDbHost);
$dev2PublicDb = new Mysql('cogeco_dev2', $deployDbDevAccount, $devDbHost);
$dev3PublicDb = new Mysql('cogeco_dev3', $deployDbDevAccount, $devDbHost);
$dev4PublicDb = new Mysql('cogeco_dev4', $deployDbDevAccount, $devDbHost);
$dev5PublicDb = new Mysql('cogeco_dev5', $deployDbDevAccount, $devDbHost);
$dev6PublicDb = new Mysql('cogeco_dev6', $deployDbDevAccount, $devDbHost);
$dev7PublicDb = new Mysql('cogeco_dev7', $deployDbDevAccount, $devDbHost);
$dev8PublicDb = new Mysql('cogeco_dev8', $deployDbDevAccount, $devDbHost);
$dev9PublicDb = new Mysql('cogeco_dev9', $deployDbDevAccount, $devDbHost);
$dev10PublicDb = new Mysql('cogeco_dev10', $deployDbDevAccount, $devDbHost);
$dev11PublicDb = new Mysql('cogeco_dev11', $deployDbDevAccount, $devDbHost);
$dev12PublicDb = new Mysql('cogeco_dev12', $deployDbDevAccount, $devDbHost);
//$devCmsDb = new Mysql('cms_dev', $localDevDbAccount, $devDbHost);

// UAT
$uatPublicDb = new Mysql('cogeco_uat', $deployDbDevAccount, $devDbHost);
$uat1PublicDb = new Mysql('cogeco_uat1', $deployDbDevAccount, $devDbHost);
$uat2PublicDb = new Mysql('cogeco_uat2', $deployDbDevAccount, $devDbHost);
$uat3PublicDb = new Mysql('cogeco_uat3', $deployDbDevAccount, $devDbHost);
$uat4PublicDb = new Mysql('cogeco_uat4', $deployDbDevAccount, $devDbHost);
$uat5PublicDb = new Mysql('cogeco_uat5', $deployDbDevAccount, $devDbHost);
$uat6PublicDb = new Mysql('cogeco_uat6', $deployDbDevAccount, $devDbHost);
$uat7PublicDb = new Mysql('cogeco_uat7', $deployDbDevAccount, $devDbHost);
$uat8PublicDb = new Mysql('cogeco_uat8', $deployDbDevAccount, $devDbHost);
$uat9PublicDb = new Mysql('cogeco_uat9', $deployDbDevAccount, $devDbHost);
$uat10PublicDb = new Mysql('cogeco_uat10', $deployDbDevAccount, $devDbHost);
$uat11PublicDb = new Mysql('cogeco_uat11', $deployDbDevAccount, $devDbHost);
$uat12PublicDb = new Mysql('cogeco_uat12', $deployDbDevAccount, $devDbHost);

// PREPROD
$preprodPublicDb = new Mysql('cogeco', $deployDbDevAccount, $devDbHost);
//$preprodCmsDb = new Mysql('cms_dev', $deployDbAccount, $prodDbHost);

// PROD
$prodPublicDb = new Mysql('cogeco', $deployDbProdAccount, $prodDbHost);
$prodDrPublicDb = new Mysql('cogeco', $deployDbProdAccount, $prodDrDbHost);
//$prodCmsDb = new Mysql('cms', $deployDbAccount, $prodHost);


// ******************
// Email related data
$cogecoCaReleaseEmail = new Email(
	array('web-marketing-build@cogeco.com', 'Web Marketing'), // from
	array( // to

	),
	"Cogeco.ca deployment - Déploiement cogeco.ca" // Subject
);

// Send orders
$sendOrdersEmail = new Email(
	array('', 'Web Marketing'), // from
	array( // to
	),
	"Commandes web - Web orders | Cogeco.ca" // Subject
);

// Email related data
$deployRequestEmail = new Email(
	array('', 'Web Marketing'), // from
	//'', // from
	array(
		''
	),
	"Demande de déploiement www.cogeco.ca" // Subject
);
