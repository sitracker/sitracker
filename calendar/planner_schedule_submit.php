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

header('Content-Type: text/plain');

foreach (array('data', 'user') as $var) eval("\$$var=cleanvar(\$_REQUEST['$var']);");

$parts = explode('^', $data);
array_pop($parts);

foreach ($parts as $part)
{
    // TODO remove debugging
    $dbg .= print_r(explode('~', $part), TRUE);
    list($id, $schedule, $weekstart, $comments) = explode('~', $part);
    echo "id is $id, schedule is $schedule, weekstart = $weekstart, comments is $comments";
    $name = trim(implode(' ',explode('|',  $id)));
    $bodytext = "$name is ";

    switch ($schedule)
    {
        case -1:
            $bodytext .= 'behind schedule';
            break;
        case 0:
            $bodytext .= 'on schedule';
            break;
        case 1:
            $bodytext .= 'ahead of schedule';
        break;
    }

    $bodytext .= ".  $comments";

    if (($schedule == -1) || ($comments != ''))
    {
        //trigger('TRIGGER_TIMESHEET_CREATED_STATUS',
        //    "status=" . str_replace(array(',', '='), '', $bodytext));
    }
    else
    {
        //echo TRIGGER_TIMESHEET_CREATED;
        //trigger('TRIGGER_TIMESHEET_CREATED', array('notifyemail=tom@salfordsoftware.co.uk'));
    }

    if (($schedule != 0) || ($comments != ''))
    {

        $sql = "INSERT INTO `{$dbNotes}` (userid, bodytext, link, refid) VALUES ('";
        $sql.= $user. "', '" . $bodytext . "', '1001', $id)";
    }
}

$ws = floor($weekstart / 1000);

$sql = "UPDATE `{$dbTasks}` SET completion = 1 WHERE startdate >= FROM_UNIXTIME($ws) AND ";
$sql.= "startdate < (FROM_UNIXTIME($ws + 86400 * 7)) AND ";
$sql.= "distribution = 'event' AND ";
$sql.= "completion = 0 AND ";
$sql.= "owner = ". $user;

mysql_query($sql);
if (mysql_error())
{
    trigger_error(mysql_error(),E_USER_ERROR);
    $dbg .= $sql;
}

?>
