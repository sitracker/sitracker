<?php
// transactions.php - List of transactions
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//          Paul Heaney <paulheaney[at]users.sourceforge.net>


// included by billable_incidents.php

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    require ('core.php');
    $permission = PERM_BILLING_TRANSACTION_VIEW; // View Transactions
    require_once(APPLICATION_LIBPATH . 'functions.inc.php');
    include_once (APPLICATION_LIBPATH . 'billing.inc.php');

    // This page requires authentication
    require_once(APPLICATION_LIBPATH.'auth.inc.php');
}

$title = $strTransactions;

// External variables
$serviceid = clean_int($_REQUEST['serviceid']);
$startdate = date('Y-m-d', strtotime($_REQUEST['startdate']));
$enddate = date('Y-m-d', strtotime($_REQUEST['enddate']));

$site = clean_int($_REQUEST['site']);
$sites = clean_int($_REQUEST['sites']);
$display = clean_fixed_list($_REQUEST['display'], array('html', 'csv'));
$showfoc = clean_fixed_list($_REQUEST['foc'], array('', 'show'));
$focaszero = clean_fixed_list($_REQUEST['focaszero'], array('', 'show'));

if (!empty($showfoc) AND $showfoc != 'show') $showfoc = FALSE;
else $showfoc = TRUE;

if (!empty($site) AND empty($sites)) $sites = array($site);

$sitebreakdown =  clean_fixed_list($_REQUEST['sitebreakdown'], array('', 'on'));

if (!empty($enddate))
{
    $a = explode("-", $enddateorig);

    $m = mktime(0, 0, 0, $a[1], $a[2]+1, $a[0]);
    $enddate = date("Y-m-d", $m);
}

if ($sitebreakdown == 'on') $sitebreakdown = TRUE;
else $sitebreakdown = FALSE;

$text = transactions_report($serviceid, $startdate, $enddate, $sites, $display, $sitebreakdown, $showfoc, $focaszero);

if ($display == 'html')
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>{$strTransactions}</h2>";

    echo $text;
    echo "<p class='return'><a href='" . html_specialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES, $i18ncharset) . "'>{$strReturnToPreviousPage}</a></p>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($display == 'csv')
{
    header("Content-type: text/csv\r\n");
    header("Content-disposition-type: attachment\r\n");
    header("Content-disposition: filename=transactions.csv");
    echo $text;
}

?>