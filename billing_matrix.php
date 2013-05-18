<?php
// billing_matrix.php - Page to view a billing matrix
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_BILLING_DURATION_EDIT;  // TODO we need a permission to administer billing matrixes
require_once (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require_once (APPLICATION_LIBPATH . 'auth.inc.php');

$seltab = cleanvar($_REQUEST['tab']);

$title = $strBillingMatrix;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('billing', 32)." {$title}</h2>";
plugin_do('billing_matrix');

if (empty($seltab)) $seltab = 'UnitBillable';
$billingObj = new $seltab();
if (!($billingObj instanceof Billable)) trigger_error("Billing type of {$seltab} not recognised");

$tabs = array();
$tabs[ (new UnitBillable())->display_name() ] = "{$_SERVER['PHP_SELF']}?tab=UnitBillable";
$tabs[ (new PointsBillable())->display_name() ] = "{$_SERVER['PHP_SELF']}?tab=PointsBillable";

echo draw_tabs($tabs, $billingObj->display_name());

echo "<p align='center'><a href='{$billingObj->new_billing_matrix_page}'>{$strAddNewBillingMatrix}</a></p>";

echo $billingObj->show_billing_matrix_details();

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');