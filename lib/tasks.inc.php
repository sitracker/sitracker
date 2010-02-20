<?php
// tasks.inc.php - functions relating to Tasks (but not activities!)
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

/**
* Postpones a task's due date 24 hours
* @author Kieran Hogg
* @param int $taskid The ID of the task to postpone
*/
function postpone_task($taskid)
{
    global $dbTasks;
    if (is_numeric($taskid))
    {
        $sql = "SELECT duedate FROM `{$dbTasks}` AS t ";
        $sql .= "WHERE id = '{$taskid}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $task = mysql_fetch_object($result);
        if ($task->duedate != "0000-00-00 00:00:00")
        {
            $newtime = date("Y-m-d H:i:s", (mysql2date($task->duedate) + 60 * 60 * 24));
            $sql = "UPDATE `{$dbTasks}` SET duedate = '{$newtime}' WHERE id = '{$taskid}'";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        }
    }
}


function mark_task_completed($taskid, $incident)
{
    global $dbNotes, $dbTasks;
    if (!$incident)
    {
        // Insert note to say what happened
        $bodytext = sprintf($_SESSION['syslang']['strTaskMarkedCompleteByX'], $_SESSION['realname']) . ":\n\n" . $bodytext;
        $sql = "INSERT INTO `{$dbNotes}` ";
        $sql .= "(userid, bodytext, link, refid) ";
        $sql .= "VALUES ('0', '{$bodytext}', '10', '{$taskid}')";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    }

    $enddate = date('Y-m-d H:i:s');
    $sql = "UPDATE `{$dbTasks}` ";
    $sql .= "SET completion='100', enddate='$enddate' ";
    $sql .= "WHERE id='$taskid' LIMIT 1";
    mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
}




?>