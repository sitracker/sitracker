<?php
// billable_incidents.php - List of all billable incidents between two optional datess
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Report Type: Billing

// Author:  Paul Heaney Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_REPORT_RUN; // Run Reports
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strBillableIncidents;
$startdate = strtotime(cleanvar($_REQUEST['startdate']));
$enddate = strtotime(cleanvar($_REQUEST['enddate']));
$mode = clean_fixed_list($_REQUEST['mode'], array('','report'));
$output = clean_fixed_list($_REQUEST['output'], array('html','csv'));

if (empty($mode))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('reports', 32)." {$strBillableIncidentsReport}</h2>";

    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' id='billableincidents'>";
    echo "<table class='vertical'>";
    echo "<tr><th>{$strStartDate}:</th>";
    echo "<td><input type='text' name='startdate' id='startdate' size='10' /> ";
    echo date_picker('billableincidents.startdate');
    echo "</td></tr>\n";
    echo "<tr><th>{$strEndDate}:</th>";
    echo "<td><input type='text' name='enddate' id='enddate' size='10' /> ";
    echo date_picker('billableincidents.enddate');
    echo "</td></tr>\n";

    echo "</table>";

    echo "<p class='formbuttons'>";
    echo "<input type='reset' value=\"{$strReset}\" /> ";
    echo "<input type='submit' name='runreport' value='{$strRunReport}' /></p>";
    echo "<input type='hidden' name='mode' id='mode' value='report' />";
    echo "</form>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($mode == 'report')
{
    // Loop around all active sites - those with contracts

    // Need a breakdown of incidents so loop though each site and list the incidents

    /*
     SITE (total: x):
        Incident a - c
        Incident b - d
    */

    if ($output == 'html')
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>".icon('reports', 32)." {$strBillableIncidentsReport}</h2>";
    }

    $sqlsite = "SELECT DISTINCT m.site FROM `{$dbMaintenance}` AS m ";
    if ($startdate > 0) $sqlsite .= "WHERE expirydate >= {$startdate}";
    $resultsite = mysqli_query($db, $sqlsite);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

    $datadisplayed = false;

    if (mysqli_num_rows($resultsite) > 0)
    {
        while ($objsite = mysqli_fetch_object($resultsite))
        {
            $used = false;

            $sql = "SELECT i.id, i.title FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbContacts']}` AS c WHERE c.id = i.contact AND c.siteid = {$objsite->site} ";
            if ($startdate != 0)
            {
                $sql .= "AND closed >= {$startdate} ";
            }

            if ($enddate != 0)
            {
                $sql .= "AND closed <= {$enddate} ";
            }

            $result = mysqli_query($db, $sql);
            if (mysqli_error($db))
            {
                trigger_error(mysqli_error($db), E_USER_WARNING);
                return FALSE;
            }
            $units = 0;

            // TODO improve the print out for multiple billing types
            
            if (mysqli_num_rows($result) > 0)
            {
                while ($obj = mysqli_fetch_object($result))
                {
                    $b = get_billable_object_from_incident_id($obj->id);
                    
                    if ($b)
                    {
                        $amount = $b->amount_used_incident($obj->id);
                        
                        if ($amount > 0)
                        {
                            $str .= "<tr><td>{$obj->id}</td><td>{$obj->title}</td><td>{$amount}</td></tr>";
                            $used = true;
                        }
                    }
                }
            }

            if ($used)
            {
                if ($output == 'html')
                {
                    $datadisplayed = true;
                    echo "<table class='maintable'>";
                    echo "<tr><th colspan='3'>".site_name($objsite->site)."</th></tr>";
                    echo "<tr><th>{$strIncidentID}</th><th>{$strTitle}</th><th>{$strBillingCustomerPeriod}</th></tr>";
                    echo $str;
                    echo "</table>";
                }
            }
        }
    }
    else
    {
        echo "<p align='center'>{$strNoResults}</p>";
    }

    if (!$datadisplayed)
    {
        if ($output == 'html')
        {
            echo "<p align='center'>{$strNoResults}</p>";
        }
    }

    if ($output == 'html')
    {
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }

}

?>
