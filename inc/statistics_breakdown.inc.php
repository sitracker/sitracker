<?php
// breakdown.inc.php - Displays the incidents for a particular day and condition (i.e. closed, opened etc)
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paul[at]sitracker.org>


// Included by ../statistics.php

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

$sql = get_sql_statement($startdate, $enddate, $query, FALSE);
$result = mysqli_query($db, $sql);
if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

$start_str = date("Y-m-d", $startdate);
$end_str = date("Y-m-d", $enddate);

switch ($query)
{
    case 0:
        $type = 'opened';
        break;
    case 1:
        $type = 'closed';
        break;
    case 2:
        $type = 'updated';
        break;
}

if ($start_str == $end_str) echo "<h2>".sprintf($strIncidentsVerbOnDate, $type, $start_str)."</h2>";
else echo "<h2>".sprintf($strIncidentsVerbBetweenDates, $type, $start_str, $end_str)."</h2>";

echo "<table class='maintable'>";

if (mysqli_num_rows($result) > 0)
{
    echo "<tr><th>{$strID}</th><th>{$strTitle}</th><th>{$strPriority}</th><th>{$strOpened}</th><th>{$strClosed}</th><th>{$strOwner}</th><th>{$strCustomer}</th><th>{$strSite}</th></tr>";

    while ($obj = mysqli_fetch_object($result))
    {
        echo "<tr>";
        echo "<td>".html_incident_popup_link($obj->id, get_userfacing_incident_id($obj->id))."</td>";
        echo "<td>".html_incident_popup_link($obj->id, $obj->title)."</td>";
        echo "<td>".priority_name($obj->priority)."</td>";
        echo "<td>".date($CONFIG['dateformat_datetime'], $obj->opened)."</td>";
        if ($obj->status != 2)
        {
            echo "<td>{$strCurrentlyOpen}</td>";
        }
        else
        {
            echo "<td>".date($CONFIG['dateformat_datetime'], $obj->closed)."</td>";
        }
        echo "<td>".user_realname($obj->owner);
        if ($obj->towner > 0) echo " (".user_realname($obj->towner).") ";
        echo "</td>";

        $sql = "SELECT c.forenames, c.surname, s.name ";
        $sql .= "FROM `{$dbContacts}` AS c, `{$dbSites}` AS s ";
        $sql .= "WHERE s.id = c.siteid AND c.id = {$obj->contact}";
        $contactResult = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        $contact = mysqli_fetch_object($contactResult);
        echo "<td>{$contact->forenames} {$contact->surname}</td>";
        echo "<td>{$contact->name}</td>";
        echo "</tr>\n";
    }

    echo "</table>\n";
}
else
{
    echo user_alert($strNoRecords, E_USER_INFO);
}

?>
