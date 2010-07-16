<?php
// detailed_stats.php - Report shows details of opened/closed incidents each day in period along with engineer break down and incident details
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
// Copyright (C) 2010 The Support Incident Tracker Project
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paulheaney[at]users.sourceforge.net>

// FIXME needs abit of tidying up
// Report Type: Management report


require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

$permission = 67; // Run Reports


// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strIncidentsDailySummary;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$submit = cleanvar($_REQUEST['submit']);
$startdate = cleanvar($_REQUEST['startdate']);
$enddate = cleanvar($_REQUEST['enddate']);

echo "<h2>".icon('reports', 32)." {$title}</h2>";

if (empty($submit))
{
    echo "<form action='{$_SERVER['PHP_SELF']}' id='incidentsbysoftware' method='post'>";
    echo "<table class='vertical'>";
    echo "<tr><th>{$strStartDate}:</th>";
    echo "<td><input type='text' name='startdate' id='startdate' size='10' /> ";
    echo date_picker('incidentsbysoftware.startdate');
    echo "</td></tr>\n";
    echo "<tr><th>{$strEndDate}:</th>";
    echo "<td><input type='text' name='enddate' id='enddate' size='10' /> ";
    echo date_picker('incidentsbysoftware.enddate');
    echo "</td></tr>\n";
    echo "</table>";
    echo "<p align='center'><input name='submit' type='submit' value='{$strRunReport}' /></p>";
    echo "</form>";
}
else
{
    if (empty($startdate))
    {
        if (empty($enddate)) $startdate = $now - 31556926; // 1 year
        else $startdate = strtotime($enddate) - 31556926; // 1 year
    }
    else
    {
        $startdate = strtotime($startdate);
    }

    if (empty($enddate)) $enddate = $now;
    else $enddate = strtotime($enddate);

    if ($startdate > $enddate)
    {
        // swap
        $s2 = $enddate;
        $enddate = $startdate;
        $startdate = $s2;
        unset($s2);
    }

    if ($startdate < $enddate)
    {
        // opened
        $sql = "SELECT id, owner, opened, title FROM `{$dbIncidents}` ";
        $sql .= "WHERE opened BETWEEN '{$startdate}' AND '{$enddate}'  ORDER BY opened";
        $result= mysql_query($sql);

        while ($incident = mysql_fetch_object($result))
        {
            $stats[date('Y-m-d', $incident->opened)]['date']=date('l d/m/Y', $incident->opened);
            $stats[date('Y-m-d', $incident->opened)][$incident->id]['opened']['id'] = $incident->id;
            $stats[date('Y-m-d', $incident->opened)][$incident->id]['opened']['owner'] = $incident->owner;
            $stats[date('Y-m-d', $incident->opened)][$incident->id]['opened']['title'] = $incident->title;
            $stats[date('Y-m-d', $incident->opened)][$incident->id]['opened']['date'] = $incident->opened;
            $stats[date('Y-m-d', $incident->opened)][$incident->id]['opened']['type'] = 'opened';
        }

        // opened
        $sql = "SELECT id, owner, closed, title FROM `{$dbIncidents}` ";
        $sql .= "WHERE closed BETWEEN '{$startdate}' AND '{$enddate}'  ORDER BY closed ";
        $result= mysql_query($sql);

        //$stats=array();

        while ($incident = mysql_fetch_object($result))
        {
            $stats[date('Y-m-d', $incident->closed)]['date']=date('l d/m/Y', $incident->closed);
            $stats[date('Y-m-d', $incident->closed)][$incident->id]['closed']['id'] = $incident->id;
            $stats[date('Y-m-d', $incident->closed)][$incident->id]['closed']['owner'] = $incident->owner;
            $stats[date('Y-m-d', $incident->closed)][$incident->id]['closed']['title'] = $incident->title;
            $stats[date('Y-m-d', $incident->closed)][$incident->id]['closed']['date'] = $incident->closed;
            $stats[date('Y-m-d', $incident->closed)][$incident->id]['closed']['type'] = 'closed';
        }


/*
        echo "<pre>";
        print_r($stats);
        echo "</pre>";
*/

        if (count($stats) > 0)
        {
            foreach ($stats AS $day)
            {
                /*
                echo "<pre>";
                print_r($day);
                echo "</pre>";
                */
                echo "<h2>".$day['date']."</h2>";
                echo "<table>";
                $opened=0;
                $closed=0;
                $owners=array();
                $right='';
                foreach ($day AS $d)
                {
                    if (is_array($d))
                    {
                        /*
                        echo "<pre>";
                        print_r($d);
                        echo "</pre>";
                        */
                        foreach ($d AS $a)
                        {
                            $right .= "<tr><td>".$a['type']."</td><td><a href='incident_details.php?id=".$a['id']."' class='direct'>".$a['id']."</td><td>".$a['title']."</a></td><td>".user_realname($a['owner'])."</td></tr>";
                            if ($a['type'] == 'opened')
                            {
                                $opened++;
                                $owners[$a['owner']]['owner']=$a['owner'];
                                $owners[$a['owner']]['opened']++;
                            }
                            else
                            {
                                $closed++;
                                $owners[$a['owner']]['owner']=$a['owner'];
                                $owners[$a['owner']]['closed']++;
                            }
                        }
                    }
                }
    
                echo "<tr><td valign='top'><table>";
                echo "<tr><td>{$strOpened}</td><td>{$opened}</td></tr>";
                echo "<tr><td>{$strClosed}</td><td>{$closed}</td></tr>";
                echo "<table><tr><th>{$strUser}</th><th>{$strOpened}</th><th>{$strClosed}</th></tr>";
                foreach ($owners AS $o)
                {
                    echo "<tr>";
                    echo "<td>".user_realname($o['owner'])."</td><td>";
                    if ($o['closed'] != 0) echo $o['closed'];
                    else echo "0";
    
                    echo "</td><td>";
                    if ($o['opened']!=0) echo $o['opened'];
                    else echo "0";
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table></td><td><table>";
                echo $right;
                echo "</table></td></tr>";
                echo "</table>";
            }
        }
        else
        {
        	echo "<p class='error'>{$strNoIncidents}</p>";
        }
    }
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>