<?php
// dashboard_tasks.php - List of tasks
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

$dashboard_tasks_version = 1;

function dashboard_tasks($dashletid)
{
    global $sit, $CONFIG, $iconset, $dbTasks;
    $user = $sit[2];

    if ($CONFIG['tasks_enabled'] == TRUE)
    {
        $sql = "SELECT * FROM `{$dbTasks}` WHERE owner='$user' AND (completion < 100 OR completion='' OR completion IS NULL) AND ";
        $sql .= "(distribution = 'public' OR distribution = 'private') ";

        if (!empty($sort))
        {
            if ($sort=='id') $sql .= "ORDER BY id ";
            elseif ($sort=='name') $sql .= "ORDER BY name ";
            elseif ($sort=='priority') $sql .= "ORDER BY priority ";
            elseif ($sort=='completion') $sql .= "ORDER BY completion ";
            elseif ($sort=='startdate') $sql .= "ORDER BY startdate ";
            elseif ($sort=='duedate') $sql .= "ORDER BY duedate ";
            elseif ($sort=='distribution') $sql .= "ORDER BY distribution ";
            else $sql = "ORDER BY id ";
            if ($order=='a' OR $order=='ASC' OR $order='') $sql .= "ASC";
            else $sql .= "DESC";
        }
        else $sql .= "ORDER BY IF(duedate,duedate,99999999) ASC, duedate ASC, startdate DESC, priority DESC, completion ASC";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

        if (mysql_num_rows($result) >=1 )
        {
            $content .= "<table align='center' width='100%'>";
            $content .= "<tr>";
            $content .= colheader('id', $GLOBALS['strID']);
            $content .= colheader('name', $GLOBALS['strTask']);
            $content .= colheader('priority', $GLOBALS['strPriority']);
            $content .= colheader('completion', $GLOBALS['strCompletion']);
            $content .= "</tr>\n";
            $shade='shade1';
            while ($task = mysql_fetch_object($result))
            {
                $duedate = mysql2date($task->duedate);
                $startdate = mysql2date($task->startdate);
                if (empty($task->name)) $task->name = $GLOBALS['strUntitled'];
                $content .= "<tr class='$shade'>";
                $content .= "<td>{$task->id}</td>";
                $content .= "<td><a href='view_task.php?id={$task->id}' class='info'>".truncate_string($task->name, 23);
                if (!empty($task->description)) $content .= "<span>".nl2br($task->description)."</span>";
                $content .= "</a></td>";
                $content .= "<td>".priority_icon($task->priority).priority_name($task->priority)."</td>";
                $content .= "<td>".percent_bar($task->completion)."</td>";
                $content .= "</tr>\n";
                if ($shade=='shade1') $shade='shade2';
                else $shade='shade1';
            }
            $content .= "</table>\n";
        }
        else
        {
            $content .= "<p align='center'>{$GLOBALS['strNoRecords']}</p>";
        }
    }
    else
    {
        $content .= "<p class='warning'>{$GLOBALS['strDisabled']}</p>";
    }

    echo dashlet('tasks', $dashletid, icon('task', 16), sprintf($GLOBALS['strXsTasks'], user_realname($user,TRUE)), 'tasks.php', $content);
}
function dashboard_tasks_get_version()
{
    global $dashboard_tasks_version;
    return $dashboard_tasks_version;
}

?>
