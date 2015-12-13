<?php
// tasks.inc.php - functions relating to Tasks (but not activities!)
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
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
    global $dbTasks, $db;
    if (is_numeric($taskid))
    {
        $sql = "SELECT duedate FROM `{$dbTasks}` AS t ";
        $sql .= "WHERE id = '{$taskid}'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        $task = mysqli_fetch_object($result);
        if ($task->duedate != "0000-00-00 00:00:00")
        {
            $newtime = date("Y-m-d H:i:s", (mysql2date($task->duedate) + 60 * 60 * 24));
            $sql = "UPDATE `{$dbTasks}` SET duedate = '{$newtime}' WHERE id = '{$taskid}'";
            mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        }
    }
}


function mark_task_completed($taskid, $incident)
{
    global $dbNotes, $dbTasks, $db;
    if (!$incident)
    {
        // Insert note to say what happened
        $bodytext = sprintf(clean_lang_dbstring($_SESSION['syslang']['strTaskMarkedCompleteByX']), $_SESSION['realname']) . ":\n\n" . $bodytext;
        $sql = "INSERT INTO `{$dbNotes}` ";
        $sql .= "(userid, bodytext, link, refid) ";
        $sql .= "VALUES ('0', '{$bodytext}', '10', '{$taskid}')";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);
    }

    $enddate = date('Y-m-d H:i:s');
    $sql = "UPDATE `{$dbTasks}` ";
    $sql .= "SET completion='100', enddate='$enddate' ";
    $sql .= "WHERE id='$taskid' LIMIT 1";
    mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);
}


/**
 * Returns an array of open activities/timed tasks for an incident
 * @author Paul Heaney
 * @param int $incidentid. Incident ID you want
 * @return array - with the task id
 */
function open_activities_for_incident($incientid)
{
    global $dbLinks, $dbLinkTypes, $dbTasks, $db;
    // Running Activities

    $sql = "SELECT DISTINCT origcolref, linkcolref ";
    $sql .= "FROM `{$dbLinks}` AS l, `{$dbLinkTypes}` AS lt ";
    $sql .= "WHERE l.linktype=4 ";
    $sql .= "AND linkcolref={$incientid} ";
    $sql .= "AND direction='left'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

    if (mysqli_num_rows($result) > 0)
    {
        //get list of tasks
        $sql = "SELECT * FROM `{$dbTasks}` WHERE enddate IS NULL ";
        while ($tasks = mysqli_fetch_object($result))
        {
            if (empty($orSQL)) $orSQL = "(";
            else $orSQL .= " OR ";
            $orSQL .= "id={$tasks->origcolref} ";
        }

        if (!empty($orSQL))
        {
            $sql .= "AND {$orSQL})";
        }
        $result = mysqli_query($db, $sql);

        while ($obj = mysqli_fetch_object($result))
        {
            $num[] = $obj->id;
        }
    }
    else
    {
        $num = null;
    }

    return $num;
}


/**
 * Returns the number of open activities/timed tasks for a site
 * @author Paul Heaney
 * @param int $siteid. Site ID you want
 * @return int. Number of open activities for the site (0 if non)
 */
function open_activities_for_site($siteid)
{
    global $dbIncidents, $dbContacts, $db;

    $openactivites = 0;

    if (!empty($siteid) AND $siteid != 0)
    {
        $sql = "SELECT i.id FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c ";
        $sql .= "WHERE i.contact = c.id AND ";
        $sql .= "c.siteid = {$siteid} AND ";
        $sql .= "(i.status != " . STATUS_CLOSED . " AND i.status != " . STATUS_CLOSING . ")";

        $result = mysqli_query($db, $sql);

        while ($obj = mysqli_fetch_object($result))
        {
            $openactivites += count(open_activities_for_incident($obj->id));
        }
    }

    return $openactivites;
}


?>