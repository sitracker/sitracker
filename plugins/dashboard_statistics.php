<?php
// dashboard_statistics.php - Display summary statistics on the dashboard
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Authors: Paul Heaney <paul[at]sitracker.org>
//          Ivan Lucas <ivan[at]sitracker.org>


$dashboard_statistics_version = 1;

function dashboard_statistics($dashletid)
{
    echo dashlet('statistics', $dashletid, icon('statistics', 16), $GLOBALS['strTodaysStats'], 'statistics.php', $content);
}


function dashboard_statistics_display()
{
    global $todayrecent, $dbIncidents, $dbKBArticles, $iconset, $db;

    // Count incidents logged today
    $sql = "SELECT id FROM `{$dbIncidents}` WHERE opened > '{$todayrecent}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $todaysincidents = mysqli_num_rows($result);
    mysqli_free_result($result);

    // Count incidents updated today
    $sql = "SELECT id FROM `{$dbIncidents}` WHERE lastupdated > '{$todayrecent}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $todaysupdated = mysqli_num_rows($result);
    mysqli_free_result($result);

    // Count incidents closed today
    $sql = "SELECT id FROM `{$dbIncidents}` WHERE closed > '{$todayrecent}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $todaysclosed = mysqli_num_rows($result);
    mysqli_free_result($result);

    // count total number of SUPPORT incidents that are open at this time (not closed)
    $sql = "SELECT id FROM `{$dbIncidents}` WHERE status != ".STATUS_CLOSED;
    $sql .= " AND status != ".STATUS_UNSUPPORTED." AND status != ";
    $sql .= STATUS_CLOSING." AND type='support'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $supportopen = mysqli_num_rows($result);
    mysqli_free_result($result);

    // Count kb articles published today
    $sql = "SELECT docid FROM `{$dbKBArticles}` WHERE published > '".date('Y-m-d')."'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $kbpublished = mysqli_num_rows($result);
    mysqli_free_result($result);
    echo "<strong><a href='statistics.php'>{$GLOBALS['strIncidents']}</a></strong><br />";
    echo "{$todaysincidents} {$GLOBALS['strLogged']}</a><br />";
    echo "{$todaysupdated} {$GLOBALS['strUpdated']}<br />";
    echo "{$todaysclosed} {$GLOBALS['strClosed']}<br />";
    echo "{$supportopen} {$GLOBALS['strCurrentlyOpen']}<br />";

    echo "<br /><strong><a href='kb.php?mode=today'>";
    echo "{$GLOBALS['strKnowledgeBaseArticles']}</a></strong><br />";
    echo "{$kbpublished} {$GLOBALS['strPublishedToday']}</a><br />";
}

function dashboard_statistics_get_version()
{
    global $dashboard_statistics_version;
    return $dashboard_statistics_version;
}


?>
