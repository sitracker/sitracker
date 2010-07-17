<?php
// ??
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
//  Author:   Paul Heaney


$permission = 37;  // Run Reports // TODO perhaps should have own permission

require ('core.php');
include (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$mode = cleanvar($_REQUEST['mode']);

if (empty($mode) OR $mode == 'showform')
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('reports', 32)." {$strMonthlyActivityTotals}</h2>";
    echo "<form name='report' action='{$_SERVER['PHP_SELF']}' method='post'>";
    echo "<table class='vertical'>";

    echo "<tr><th>{$strStartDate}:</th>";
    echo "<td><input type='text' name='startdate' id='startdate' size='10' /> ";
    echo date_picker('report.startdate');
    echo "</td></tr>\n";
    echo "<tr><th>{$strEndDate}:</th>";
    echo "<td><input type='text' name='enddate' id='enddate' size='10' /> ";
    echo date_picker('report.enddate');
    echo "</td></tr>\n";

    echo "<tr><th>{$strCalculateUnits}</th><td>";
    echo "<input type='checkbox' name='calcote' value='yes' />\n";
    echo "</td></tr>";

    echo "</table>";

    echo "<p align='center'>";
    echo "<input type='hidden' name='mode' value='report' />";
    echo "<input type='submit' value=\"{$strRunReport}\" />";
    echo "</p>";
    echo "<input type='hidden' id='mode' name='mode' value='runreport' />";
    echo "</form>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($mode == 'runreport')
{
    $startdate = strtotime(cleanvar($_REQUEST['startdate']));
    $enddate = strtotime(cleanvar($_REQUEST['enddate']));

    if (empty($startdate)) $startdate = $now - 31536000; // 1 year ago
    if (empty($enddate)) $enddate = $now;

    $calcote = cleanvar($_REQUEST['calcote']);
    $sql = "SELECT userid, duration, timestamp FROM `{$dbUpdates}` WHERE timestamp >= '{$startdate}' AND timestamp <= '{$enddate}' AND duration != 0 AND duration IS NOT NULL ORDER BY timestamp";
    // echo $sql;
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    while ($obj = mysql_fetch_object($result))
    {
        $year = date('Y', $obj->timestamp);
        $month = date('F', $obj->timestamp);

        $value = 0;

        if ($calcote == 'yes')
        {
            // Engineer OTE each rounded to the nearest hour
            $mod = 60 - ($obj->duration % 60);
            $value = $obj->duration + $mod;
        }
        else
        {
            $value = $obj->duration;
        }

        $util[$year]['months'][$month]['name'] = $month;
        $util[$year]['months'][$month]['users'][$obj->userid]['userid'] = $obj->userid;
        if ($value > 0)
        {
            $util[$year]['months'][$month]['users'][$obj->userid]['valuepos'] += $value;
        }
        else
        {
            $util[$year]['months'][$month]['users'][$obj->userid]['valueneg'] += $value;
        }
        $util[$year]['months'][$month]['total'] += $obj->duration;
        $util[$year]['name'] = $year;
    }

    // echo "<pre>";
    // print_r($util);
    // echo "</pre>";

    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('reports', 32)." {$strMonthlyActivityTotals}</h2>";
    
    if (count($util) > 0)
    {
        foreach ($util AS $u)
        {
            echo "<h3>{$u['name']}</h3>";
            foreach ($u['months'] AS $month)
            {
                echo "<p><table class='vertical' align='center'>";
                echo "<tr><th colspan='3'>{$month['name']} {$u['name']}</th></tr>";
                echo "<tr><th>{$strEngineer}</th><th>{$strPositive}</th><th>{$strNegative}</th></tr>";
    
                $totalpos = 0;
                $totalneg = 0;
    
                $shade = 'shade1';
                foreach ($month['users'] AS $user)
                {
    
                    $minspos = 0;
                    $minsneg = 0;
    
                    if (!empty($user['valuepos']))
                    {
                        $minspos = ceil($user['valuepos']);
                        $totalpos += $minspos;
                    }
    
                    if (!empty($user['valueneg']))
                    {
                        $minsneg = ceil($user['valueneg']);
                        $totalneg += $minsneg;
                    }
    
                    echo "<tr class='{$shade}'><td>".user_realname($user['userid'])."</td><td>".sprintf($strXMinutes, $minspos)."</td><td>".sprintf($strXMinutes, $minsneg)."</td></tr>";
    
                    $grandtotals[$user['userid']]['userid'] = $user['userid'];
                    $grandtotals[$user['userid']]['totalpos'] += $minspos;
                    $grandtotals[$user['userid']]['totalneg'] += $minsneg;
    
                    if ($shade == 'shade1') $shade = 'shade2';
                    else $shade = 'shade1';
                }
    
                echo "<tr><td>{$strTotal}</td><td>". sprintf($strXMinutes, $totalpos)."</td><td>". sprintf($strXMinutes, $totalneg)."</td></tr>";
    
                echo "</table></p>";
            }
        }
    
        echo "<p align='center'><h3>{$strGrandTotal}</h3></p>";
    
        echo "<table class='vertical' align='center'>";
        echo "<tr><th>{$strEngineer}</th><th>{$strPositive}</th><th>{$strNegative}</th></tr>";
    
        $shade = 'shade1';
        foreach ($grandtotals AS $gt)
        {
            echo "<tr class='{$shade}'><td>".user_realname($gt['userid'])."</td><td>".sprintf($strXMinutes, $gt['totalpos'])."</td><td>".sprintf($strXMinutes, $gt['totalneg'])."</td></tr>";
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
    
        echo "</table>";
    }
    else
    {
        echo "<p align='center'>{$strNoBillableIncidents}</p>";
    }
    
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>