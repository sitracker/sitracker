<?php
// planner_schedule_getprevious.php - read previous activities
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Tom Gerrard <tom.gerrard[at]salfordsoftware.co.uk>

$permission = 27; // View your calendar FIXME
require ('..'.DIRECTORY_SEPARATOR.'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'auth.inc.php');
include ('calendar.inc.php');

foreach (array('user') as $var)
    eval("\$$var=cleanvar(\$_REQUEST['$var']);");

header('Content-Type: text/xml');
echo '<?xml version="1.0" ?>' . "\n";

$items = array();

$endperiod = date("Y-m-d H:i:s", time() + (86400 * 7));
$startperiod = date("Y-m-d H:i:s", strtotime($endperiod . "-1 MONTH"));

$sql = "SELECT DISTINCT name, description FROM `{$dbTasks}` WHERE startdate >= '$startperiod' AND ";
$sql .= "enddate < '$endperiod' AND distribution = 'event' AND owner = '$user'";
$res = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
while($inf = mysql_fetch_array($res))
{
    $items[] = array ('id' => $inf['name'],
                    'name' => $inf['description'],
                    'editvalue' => '',
                    'optionvalue' => 0);
}

foreach ($items as $item)
{
    echo "<item>\n";
    foreach ($item as $key => $value)
    {
        echo "  <$key>$value</$key>\n";
    }
    echo "</item>\n";
}

?>
