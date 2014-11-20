<?php
// dashboard_incoming.php - List of incoming updates
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Author: Martin Cosgrave - apto Solutions Ltd - 20080512
// updated: Matt Feider - 20090603
// Updated for SiT 3.90 Inbox - Ivan Lucas - 20141120
//
// hacked from dashboard_tasks, updated to match format of dashboard_watch_incidents.php

$dashboard_incoming_version = 2;

function dashboard_incoming($dashletid)
{
    global $sit, $CONFIG, $iconset;
    global $dbUpdates, $dbTempIncoming;
    
    $content = "<p align='center'><img src='{$CONFIG['application_webpath']}images/ajax-loader.gif' alt='Loading icon' /></p>";
    echo dashlet('incoming', $dashletid, icon('email', 16), $GLOBALS['strInbox'], 'inbox.php', $content);
}

function dashboard_incoming_display($dashletid)
{
    global $sit, $CONFIG, $iconset;
    global $dbUpdates, $dbTempIncoming;
    // extract updates (query copied from review_incoming_email.php)
    $sql  = "SELECT u.id AS id, u.bodytext AS bodytext, ti.emailfrom AS emailfrom, ti.subject AS subject, ";
    $sql .= "u.timestamp AS timestamp, ti.incidentid AS incidentid, ti.id AS tempid, ti.locked AS locked, ";
    $sql .= "ti.reason AS reason, ti.contactid AS contactid, ti.`from` AS fromaddr ";
    $sql .= "FROM `{$dbUpdates}` AS u, `{$dbTempIncoming}` AS ti ";
    $sql .= "WHERE u.incidentid = 0 AND ti.updateid = u.id ";
    $sql .= "ORDER BY timestamp ASC, id ASC";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    
    if (user_permission($sit[2], PERM_UPDATE_DELETE))
    {
        //echo "<div class='window'>";

        if (mysql_num_rows($result) >=1 )
        {
            echo "<table align='center' width='100%'>";
            echo "<tr>";

    #        echo colheader('from', $GLOBALS['strFrom']);
            echo colheader('subject', $GLOBALS['strSubject']);
            echo colheader('message', $GLOBALS['strMessage']);
            echo "</tr>\n";
            $shade = 'shade1';
            while ($incoming = mysql_fetch_object($result))
            {
                $date = mysql2date($incoming->date);
                echo "<tr class='$shade'>";
    #            echo "<td><a href='holding_queue.php' class='info'>".truncate_string($incoming->emailfrom, 15);
    #            echo "</a></td>";
                echo "<td><a href='inbox.php?id=" . $incoming->id . "' class='info'>".truncate_string($incoming->subject, 30);
                echo "</a></td>";
                echo "<td>".truncate_string($incoming->reason, 25);
                echo "/td>";
                echo "</tr>\n";
                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }
            echo "</table>\n";
        }
        else
        {
            echo user_alert($GLOBALS['strNoRecords'], E_USER_NOTICE);
        }
    }
    else
    {
        echo "<p class='error'>{$GLOBALS['strPermissionDenied']}</p>";
    }
}

function dashboard_incoming_upgrade()
{    
    $upgrade_schema[2] ="";
    return $upgrade_schema;
}


function dashboard_incoming_get_version()
{
    global $dashboard_incoming_version;
    return $dashboard_incoming_version;
}
    

?>
