<?php
/**
 * Custom properties for cogeco.ca
 */

use \Cogeco\Build\Entity\Dir;

// ***
// Misc. accounts used in the build scripts
$localDevDbAccount->password = '';

$deployDbDevAccount->password = '';
$deployDbProdAccount->password = '';

$cogecoCaReleaseEmail->to[] = $cogecoAccount->emailAddress;
$sendOrdersEmail->to[] = $cogecoAccount->emailAddress;

$trunkWcDir = new Dir('');
