<?php
// planner_schedule_commit.php - check and commit timesheet (user)
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Tom Gerrard <tom.gerrard[at]salfordsoftware.co.uk>

$permission = 27; // View your calendar
require ('..'.DIRECTORY_SEPARATOR.'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'auth.inc.php');

header('Content-Type: text/plain');

foreach (array('week', 'id' ) as $var)
{
    $var = cleanvar($_REQUEST['$var']);
    eval("\$$var=$var;");
}
$startdate = $week / 1000;
$enddate = $startdate + 86400 * 7;

// TODO: check for overlapping tasks and any other invalidness

$sql = "UPDATE `{$dbTasks}` SET completion = 1 ";
$sql.= "WHERE startdate >= '" . date("Y-m-d H:i:s",$startdate) . "' ";
$sql.= "AND     enddate <  '" . date("Y-m-d H:i:s",$enddate) . "' ";
$sql.= "AND completion = 0";

mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

echo "OK";  // This is parsed later so don't internationalise
?>
