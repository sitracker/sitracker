<?php
// approve_transaction.php - Page which does the approval of a transaction
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2007 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_INCIDENT_BILLING_APPROVE; // Approve billable incidents
require_once(APPLICATION_LIBPATH.'functions.inc.php');
include_once (APPLICATION_LIBPATH . 'billing.inc.php');
// This page requires authentication
require_once(APPLICATION_LIBPATH.'auth.inc.php');

$transactiond = clean_int($_REQUEST['transactionid']);
$title = $strBilling;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$sql = "SELECT * FROM `{$GLOBALS['dbTransactions']}` WHERE transactionid = {$transactiond}";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("Error getting transaction ".mysql_error());

if (mysql_num_rows($result) > 0)
{
    $obj = mysql_fetch_object($result);
    if ($obj->transactionstatus == BILLING_AWAITINGAPPROVAL)
    {
        // function update_contract_balance($contractid, $description, $amount, $serviceid='', $transactionid='', $totalunits=0, $totalbillableunits=0, $totalrefunds=0)
        $r = update_contract_balance('', '', $obj->amount, $obj->serviceid, $obj->transactionid);

        if ($r) html_redirect("billable_incidents.php", TRUE, "{$strTransactionApproved}");
        else html_redirect("billable_incidents.php", FALSE, "{$strFailedtoApproveTransactID} {$transactiond}");
    }
    else
    {
        html_redirect("billable_incidents.php", FALSE, "{$strTransactionXnotAwaitingApproval}", $transactiond);
    }
}
else
{
    html_redirect("billable_incidents.php", FALSE, "{$strNoTransactionsFoundWithID} {$transactiond}");
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>