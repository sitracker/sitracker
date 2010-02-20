<?php
// planner_schedule_save.php - create or update tasks based on calendar
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
include ('calendar.inc.php');

header('Content-Type: text/plain');

foreach (array(
            'saveAnItem',
            'description',
            'newItem',
            'eventStartDate',
            'eventEndDate',
            'droptarget',
            'week',
            'id',
            'name',
            'user'
            ) as $var)
{
    eval("\$$var=cleanvar(\$_REQUEST['$var']);");
}

$startDate = strtotime($eventStartDate);
$endDate = strtotime($eventEndDate);

if (isset($_GET['saveAnItem']))
{
    switch ($newItem)
    {
        case 2:
            $day = substr($droptarget,-1) - 1;
            $startDate = $week / 1000 + 86400 * $day + $CONFIG['start_working_day'] - 3600;
            $endDate = $week / 1000 + 86400 * $day + $CONFIG['end_working_day'] - 3600;

        case 1:
            echo book_appointment($name, $description, $user, $startDate, $endDate);
        break;

        case 0:
            $sql = "UPDATE `{$dbTasks}` SET description='" . mysql_escape_string($description)."' ";
            if (!empty($name) AND $name !== 'undefined') $sql .= ",name='". mysql_escape_string($name)."' ";
            $sql .= ",startdate='".date("Y-m-d H:i:s",strtotime($eventStartDate))."' ";
            $sql .= ",enddate='".date("Y-m-d H:i:s",strtotime($eventEndDate))."' ";
            $sql .= "WHERE id='{$id}' AND completion < '1'";
            mysql_query($sql);
            echo $sql;
            if (mysql_error())
            {
                trigger_error(mysql_error(),E_USER_ERROR);
                $dbg = $sql;
            }
        break;
    }
}

?>