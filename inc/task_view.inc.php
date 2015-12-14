<?php
// view_task.inc.php - Display existing task
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//          Kieran Hogg <kieran[at]sitracker.org>
// included by view_task.php

if ($mode != 'incident')
{
    echo "<h2>".icon('task', 32)." $title</h2>";
}
else
{
    echo "<h2><img
    src='{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/activities.png'
    width='32' height='32' alt='' /> $strViewActivity</h2>";
}

if ($mode != 'incident') echo "<div style='width: 90%; margin-left: auto; margin-right: auto;'>";

$sql = "SELECT * FROM `{$dbTasks}` WHERE id='{$taskid}'";
$result = mysqli_query($db, $sql);
if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
if (mysqli_num_rows($result) >= 1)
{
    $task = mysqli_fetch_object($result);
    if ($task->distribution == 'private' AND $task->owner != $sit[2])
    {
        echo user_alert($strTaskPrivateError, E_USER_ERROR);
    }
    elseif ($mode != 'incident')
    {
        echo "<div style='width: 48%; float: left;'>";
        $startdate = mysql2date($task->startdate);
        $duedate = mysql2date($task->duedate);
        $enddate = mysql2date($task->enddate);
        echo "<table class='vertical' width='100%'>";
        echo "<tr><th>{$strTitle}</th>";
        echo "<td>{$task->name}</td></tr>";
        echo "<tr><th>{$strDescription}</th>";
        echo "<td>".nl2br($task->description)."</td></tr>";
        if ($task->distribution == 'public')
        {
            echo "<tr><th>{$strTags}:</th><td>";
            echo list_tags($taskid, 4);
            echo "</td></tr>";
        }
        if ($task->owner != $sit[2])
        {
            echo "<tr><th>{$strOwner}</th>";
            echo "<td>".user_realname($task->owner, TRUE)."</td></tr>";
        }
        echo "<tr><th>{$strPriority}</th>";
        echo "<td>".priority_icon($task->priority).' '.priority_name($task->priority)."</td></tr>";
        echo "<tr><th>{$strStartDate}</th>";
        echo "<td>";
        if ($startdate > 0) echo ldate($CONFIG['dateformat_datetime'], $startdate);
        echo "</td></tr>";
        echo "<tr><th>{$strDueDate}</th>";
        echo "<td>";
        if ($duedate > 0) echo ldate($CONFIG['dateformat_datetime'], $duedate);
        echo "</td></tr>";
        echo "<tr><th>{$strCompletion}</th>";
        echo "<td>".percent_bar($task->completion)."</td></tr>";
        echo "<tr><th>{$strEndDate}</th>";
        echo "<td>";
        if ($enddate > 0) echo ldate($CONFIG['dateformat_datetime'], $enddate);
        echo "</td></tr>";
        echo "<tr><th>{$strValue}</th>";
        echo "<td>{$task->value}</td></tr>";
        echo "<tr><th>{$strPrivacy}</th>";
        echo "<td>";
        if ($task->distribution == 'public')
        {
            echo $strPublic;
        }
        if ($task->distribution == 'private')
        {
            echo "{$strPrivate} ";
            echo icon('private', 16, $strPrivate);
        }
        echo "</td></tr>";
        echo "</table>";
        $operations = array();
        $operations[$strEditTask] = array('url' => "task_edit.php?id={$taskid}", 'perm' => PERM_TASK_EDIT);
        if ($task->owner == $sit[2] AND $task->completion == 100)
        {
            $operations[$strDeleteTask] = array('url' => "task_edit.php?id={$taskid}&amp;action=delete", 'perm' => PERM_TASK_EDIT);
        }
        if ($task->completion < 100)
        {
            $operations[$strPostpone] = array('url' => "task_edit.php?id={$taskid}&amp;action=postpone", 'perm' => PERM_TASK_EDIT);
            $operations[$strMarkComplete] = array('url' => "task_edit.php?id={$taskid}&amp;action=markcomplete", 'perm' => PERM_TASK_EDIT);
        }

        echo "<p align='center'>";
        echo html_action_links($operations);
        echo "</p>";

//
//         echo "<div style='border: 1px solid #CCCCFF; padding: 5px;'>";
//         echo "<p><strong>{$strLinks}</strong>:</p>";
//         // Draw links tree
//         // Have a look what can be linked from tasks
//         echo show_links('tasks', $task->id);
//
//         echo "<p><strong>{$strReverseLinks}</strong>:</p>";
//         echo show_links('tasks', $task->id, 0, '', 'rl');
//
//         echo "</div>";
//
//         echo show_create_links('tasks', $task->id);
//
        echo "</div>";

        // Notes
        echo "<div style='width: 48%; float: right; border: 1px solid #CCCCFF;'>";
        echo new_note_form(NOTE_TASK, $taskid);
        echo show_notes(NOTE_TASK, $taskid);

        echo "</div>";
    }
    elseif ($mode == 'incident')
    {
        echo "<div style='width: 48%; margin-left: auto; margin-right: auto;border: 1px solid #CCCCFF;'>";
        echo new_note_form(NOTE_TASK, $taskid);
        echo show_notes(NOTE_TASK, $taskid, FALSE);

        echo "</div>";
    }
}
else
{
    echo user_alert($strNoMatchingTask, E_USER_WARNING);
}

if ($mode != 'incident') echo "</div>";
echo "<div style='clear:both; padding-top: 20px;'>";

if ($mode != 'incident') echo "<p align='center'><a href='tasks.php'>{$strTaskList}</a></p>";
else echo "<p align='center'><a href=task_edit.php?id={$taskid}&amp;action=markcomplete&amp;incident={$incidentid}>{$strMarkComplete}</a> | <a href='tasks.php?incident={$id}'>{$strActivityList}</a></p>";
echo "</div>";

?>
