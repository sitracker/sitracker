<?php
// manage_user_dashboard.php - Page for users to add components to their dashboard
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_NOT_REQUIRED; // not required
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$dashboardid = clean_int($_REQUEST['id']);
$title = $strManageYourDashboard;

$sql = "SELECT dashboard FROM `{$dbUsers}` WHERE id = '{$_SESSION['userid']}'";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

if (mysql_num_rows($result) > 0)
{
    $obj = mysql_fetch_object($result);
    $dashboardstr = $obj->dashboard;
    $dashboardcomponents = explode(",",$obj->dashboard);
}

if (empty($dashboardid))
{
    foreach ($dashboardcomponents AS $db)
    {
        $c = explode("-",$db);
        $ondashboard[$c[1]] = $c[1];
    }

    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    $sql = "SELECT * FROM `{$dbDashboard}` WHERE enabled = 'true'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    echo "<h2>".icon('dashboard', 32)." {$strDashboard}: ";
    echo user_realname($sit[2])."</h2>\n";
    plugin_do('manage_user_dashboard');

    if (mysql_num_rows($result) > 0)
    {
        echo "<table class='maintable'>\n";
        while ($obj = mysql_fetch_object($result))
        {
            if (empty($ondashboard[$obj->id]))
            {
                //not already on dashbaord
                echo "<tr><th>{$strName}:</th><td>{$obj->name}</td><td><a href='{$_SERVER['PHP_SELF']}?action=new&amp;id={$obj->id}'>{$strAdd}</a></td></tr>\n";
            }
            else
            {
                echo "<tr><th>{$strName}:</th><td>{$obj->name}</td><td><a href='{$_SERVER['PHP_SELF']}?action=remove&amp;id={$obj->id}'>{$strRemove}</a></td></tr>\n";
            }
        }
        echo "</table>\n";
    }
    else
    {
        echo "<p class='info'>{$strNoDashletsInstalled}</p>";
    }

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    plugin_do('manage_user_dashboard_submitted');
    $action = clean_fixed_list($_REQUEST['action'], array('new', 'remove'));
    switch ($action)
    {
        case 'new':
            // Find the emptiest column and add the dashlet there
            $col = array(0 => 0,1 => 0, 2 => 0);
            $dashlets = explode(',', $dashboardstr);
            foreach ($dashlets as $key => $value)
            {
                if ($value == '') unset($dashlets[$key]);
            }
            $dashlets = array_values($dashlets);
            foreach ($dashlets AS $dashlet)
            {
                $dp = explode('-', $dashlet);
                $col[$dp[0]] ++;
            }
            asort($col, SORT_NUMERIC);
            reset($col);
            $newposition = key($col);
            $dashboardstr = $dashboardstr.",".$newposition."-".$dashboardid;
            break;
        case 'remove':
            $regex = "/[012]-".$dashboardid."[,]?/";
            $dashboardstr = preg_replace($regex,"",$dashboardstr);
            break;
    }
    $sql = "UPDATE `{$dbUsers}` SET dashboard = '{$dashboardstr}' WHERE id = '{$_SESSION['userid']}'";
    $contactresult = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        html_redirect("manage_user_dashboard.php", FALSE);
    }
    else
    {
        plugin_do('manage_user_dashboard_saved');
        html_redirect("manage_user_dashboard.php");
    }
}

?>