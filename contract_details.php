<?php
// maintenance_details.php - Show contract details
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// Created: 20th August 2001
// Purpose: Show All Maintenance Contract Details
// This Page Is Valid XHTML 1.0 Transitional! 27Oct05

require ('core.php');
$permission = PERM_CONTRACT_VIEW;  // view Maintenance contracts
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$id = clean_int($_REQUEST['id']);
$title = ("$strContract - $strContractDetails");

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

// Display Maintenance
echo contract_details($id);

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>