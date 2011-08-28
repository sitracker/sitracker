<?php
// incidents_by_vendor.php - List the number of incidents for each vendor
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Paul Heaney <paulheaney[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_REPORT_RUN; // Run Reports
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strIncidentsByVendor;

$mode = clean_fixed_list($_REQUEST['mode'], array('', 'report'));

if (empty($mode))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('reports', 32)." {$title}</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}' id='incidentsbyvendor' method='post'>";
    echo "<table class='vertical'>";
    echo "<tr><th>{$strStartDate}:</th>";
    echo "<td><input type='text' name='startdate' id='startdate' size='10' /> ";
    echo date_picker('incidentsbyvendor.startdate');
    echo "</td></tr>";
    echo "<tr><th>{$strEndDate}:</th>";
    echo "<td class='shade2'><input type='text' name='enddate' id='enddate' size='10' /> ";
    echo date_picker('incidentsbyvendor.enddate');
    echo "</td></tr>";
    echo "</table>";
    echo "<p class='formbuttons'>";
    echo "<input type='hidden' name='mode' value='report' />";
    echo "<input type='reset' value=\"{$strReset}\" /> ";
    echo "<input type='submit' value=\"{$strRunReport}\" />";
    echo "</p>";
    echo "</form>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    $startdate = strtotime($_REQUEST['startdate']);
    $enddate = strtotime($_REQUEST['enddate']);

    if (empty($startdate))
    {
        if (empty($enddate)) $startdate = strtotime(date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y")-1))); // 1 year ago
        else $startdate = strtotime ("-1 Year", $enddate); // 1 year before the start date
    }

    if (empty($enddate)) $enddate = $now;

    $sql = "SELECT COUNT(i.id) AS volume, p.vendorid, p.name ";
    $sql .= "FROM `{$dbIncidents}` AS i, `{$dbProducts}` AS p, `{$dbVendors}` AS v WHERE i.product = p.id AND i.opened >= '{$startdate}' AND i.opened <= '{$enddate}' ";
    $sql .= "AND p.vendorid = v.id GROUP BY p.vendorid";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('reports', 32)." {$title}</h2>";

    echo "<p align='center'>".sprintf($strForThePeriodXToY, ldate($CONFIG['dateformat_date'], $startdate), ldate($CONFIG['dateformat_date'], $enddate))."</p>";

    if (mysql_num_rows($result) > 0)
    {
        echo "<p>";
        echo "<table class='vertical maintable'>";
        echo "<tr><th>{$strVendor}</th><th>{$strIncidents}</th></tr>";
        while ($obj = mysql_fetch_object($result))
        {
            echo "<tr><td class='shade1'>{$obj->name}</td><td class='shade1'>{$obj->volume}</td></tr>";
        }
        echo "</table>";
        echo "</p>";
    }

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

}


?>