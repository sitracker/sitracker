<?php
// exit_task.php - Edit existing task
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_TASK_EDIT;
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');
if (!$CONFIG['tasks_enabled'])
{
    header("Location: main.php");
}
$title = $strEditTask;

// External variables
$action = $_REQUEST['action'];
$id = clean_int($_REQUEST['id']);
$incident = clean_int($_REQUEST['incident']);
$SYSLANG = $_SESSION['syslang'];

switch ($action)
{
    case 'edittask':
        $name = cleanvar($_REQUEST['name']);
        $description = cleanvar($_REQUEST['description']);
        $priority = clean_int($_REQUEST['priority']);

        if (!empty($_REQUEST['duedate']))
        {
            $duedate = strtotime(cleanvar($_REQUEST['duedate']) . ' ' . $due_time_picker_hour . ':' . $due_time_picker_minute);
        }
        else
        {
            $duedate = '';
        }

        if (!empty($_REQUEST['startdate']))
        {
            $startdate = strtotime(cleanvar($_REQUEST['startdate']) . ' ' . $start_time_picker_hour . ':' . $start_time_picker_minute);
        }
        else
        {
            $startdate = '';
        }

        $completion = cleanvar(str_replace('%','',$_REQUEST['completion']));
        if ($completion != '' AND !is_numeric($completion)) $completion=0;
        if ($completion > 100) $completion = 100;
        if ($completion < 0) $completion = 0;
        if (!empty($_REQUEST['enddate']))
        {
            $enddate = strtotime($_REQUEST['enddate'] . ' ' . $end_time_picker_hour . ':' . $end_time_picker_minute);
        }
        else
        {
            $enddate = '';
        }

        if ($completion == 100 AND $enddate == '') $enddate = $now;
        $value = cleanvar($_REQUEST['value']);
        $owner = clean_int($_REQUEST['owner']);
        $distribution = cleanvar($_REQUEST['distribution']);
        $old_name = cleanvar($_REQUEST['old_name']);
        $old_description = cleanvar($_REQUEST['old_description']);
        $old_priority = clean_int($_REQUEST['old_priority']);
        $old_startdate = cleanvar($_REQUEST['old_startdate']);
        $old_duedate = cleanvar($_REQUEST['old_duedate']);
        $old_completion = cleanvar($_REQUEST['old_completion']);
        $old_enddate = cleanvar($_REQUEST['old_enddate']);
        $old_value = cleanvar($_REQUEST['old_value']);
        $old_owner = cleanvar($_REQUEST['old_owner']);
        $old_distribution = cleanvar($_REQUEST['old_distribution']);
        if ($distribution == 'public') $tags = cleanvar($_POST['tags']);
        else $tags = '';

        // Validate input
        $error = array();
        if ($name == '') $error[] = sprintf($strFieldMustNotBeBlank, $strName);
        if ($startdate > $duedate AND $duedate != '' AND $duedate > 0 ) $startdate = $duedate;

        plugin_do('task_edit_submitted');
        if (count($error) >= 1)
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            echo "<p class='error'>{$strPleaseCheckData}</p>";
            echo "<ul class='error'>";
            foreach ($error AS $err)
            {
                echo "<li>$err</li>";
            }
            echo "</ul>";
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        else
        {
            replace_tags(4, $id, $tags);
            if ($startdate > 0) $startdate = date('Y-m-d H:i', $startdate);
            else $startdate = '';
            if ($duedate > 0) $duedate = date('Y-m-d H:i', $duedate);
            else $duedate = '';
            if ($enddate > 0) $enddate = date('Y-m-d H:i', $enddate);
            else $enddate = '';
            if ($startdate < 1 AND $completion > 0) $startdate = date('Y-m-d H:i:s');
            $sql = "UPDATE `{$dbTasks}` ";
            $sql .= "SET name='{$name}', description='{$description}', priority='{$priority}', ";
            $sql .= "duedate='{$duedate}', startdate='{$startdate}', ";
            $sql .= "completion='{$completion}', enddate='{$enddate}', value='{$value}', ";
            $sql .= "owner={$owner}, distribution='{$distribution}' ";
            $sql .= "WHERE id='{$id}' LIMIT 1";
            mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

            // Add a note to say what changed (if required)
            $bodytext = '';
            if ($name != $old_name)
            {
                $bodytext .= "{$SYSLANG['strName']}: {$old_name} -&gt; [b]{$name}[/b]\n";
            }

            if ($description != $old_description)
            {
                $bodytext .= "{$SYSLANG['strDescription']}: {$old_description} -&gt; [b]{$description}[/b]\n";
            }

            if ($priority != $old_priority)
            {
                $bodytext .= "{$SYSLANG['strPriority']}: ".priority_name($old_priority)." -&gt; [b]".priority_name($priority)."[/b]\n";
            }

            $old_startdate = mb_substr($old_startdate, 0, 16);
            if ($startdate != $old_startdate AND ($startdate != '' AND $old_startdate != '0000-00-00 00:00'))
            {
                $bodytext .= "{$SYSLANG['strStartDate']}: {$old_startdate} -&gt; [b]{$startdate}[/b]\n";
            }

            $old_duedate = mb_substr($old_duedate, 0, 16);
            if ($duedate != $old_duedate AND ($duedate != '0000-00-00' AND $old_duedate != '0000-00-00 00:00'))
            {
                $bodytext .= "{$SYSLANG['strDueDate']}: {$old_duedate} -&gt; [b]{$duedate}[/b]\n";
            }

            if ($completion != $old_completion)
            {
                $bodytext .= "{$SYSLANG['strCompletion']}: {$old_completion}% -&gt; [b]{$completion}%[/b]\n";
            }

            $old_enddate = mb_substr($old_enddate, 0, 16);
            if ($enddate != $old_enddate AND ($enddate != '0000-00-00 00:00:00' AND $old_enddate != '0000-00-00 00:00'))
            {
                $bodytext .= "{$SYSLANG['strDueDate']}: {$old_enddate} -&gt; [b]{$enddate}[/b]\n";
            }

            if ($value != $old_value)
            {
                $bodytext .= "{$SYSLANG['strValue']}: {$old_value} -&gt; [b]{$value}[/b]\n";
            }

            if ($owner != $old_owner)
            {
                $bodytext .= "{$SYSLANG['strUser']}: ".user_realname($old_owner)." -&gt; [b]".user_realname($owner)."[/b]\n";
            }

            if ($distribution != $old_distribution)
            {
                $bodytext .= "{$SYSLANG['strPrivacy']}: {$old_distribution} -&gt; [b]{$distribution}[/b]\n";
            }

            if (!empty($bodytext))
            {
                $bodytext = sprintf($strEditedBy, $_SESSION['realname']).":\n\n{$bodytext}";
                // Link 10 = Tasks
                $sql = "INSERT INTO `{$dbNotes}` ";
                $sql .= "(userid, bodytext, link, refid) ";
                $sql .= "VALUES ('0', '{$bodytext}', '10', '{$id}')";
                mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
            }
            plugin_do('task_edit_saved');
            html_redirect("view_task.php?id={$id}", TRUE);
        }
        break;
    case 'markcomplete':
        //this task is for an incident, enter an update from all the notes
        if ($incident)
        {
            //get current incident status
            $sql = "SELECT status FROM `{$dbIncidents}` WHERE id='{$incident}'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            $status = mysql_fetch_object($result);
            $status = $status->status;

            //if we don't get an update from the incident, create one
            //shouldn't happen in sit, but 3rd party might not set one
            if (!isset($status) OR $status == 0)
            {
                $status = 1;
            }

            $sql = "SELECT * FROM `{$dbTasks}` WHERE id='{$id}'";

            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($result) >= 1)
            {
                $task = mysql_fetch_object($result);
                $startdate = mysql2date($task->startdate);
                $duedate = mysql2date($task->duedate);
                $enddate = mysql2date($task->enddate);
            }
            else
            {
                trigger_error("Couldn't find task, dying", E_USER_ERROR);
                die();
            }

            //get all the notes
            $notearray = array();
            $numnotes = 0;
            $sql = "SELECT * FROM `{$dbNotes}` WHERE link='10' AND refid='{$id}'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($result) >= 1)
            {
                while ($notes = mysql_fetch_object($result))
                {
                    $notesarray[$numnotes] = $notes;
                    $numnotes++;
                }
            }
            else
            {
                html_redirect("view_task.php?id={$id}&amp;mode=incident&amp;incident={$incident}", FALSE, $strActivityContainsNoNotes);
                exit();
            }
            //delete all the notes
            $sql = "DELETE FROM `{$dbNotes}` WHERE refid='{$id}'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

            $enddate = $now;
            $duration = ceil(($enddate - $startdate) / 60);

            $startdate = readable_date($startdate, 'system');
            $enddate = readable_date($enddate, 'system');

            $updatehtml = sprintf($SYSLANG['strActivityStarted'], $startdate)."\n\n";

            for ($i = $numnotes-1; $i >= 0; $i--)
            {
                $updatehtml .= "[b]";
                $updatehtml .= readable_date(mysql2date($notesarray[$i]->timestamp), 'system');
                $updatehtml .= "[/b]\n".mysql_real_escape_string($notesarray[$i]->bodytext)."\n\n";
            }

            $updatehtml .= sprintf($SYSLANG['strActivityCompleted'], $enddate, $duration);

            $owner = incident_owner($incident);

            //create update
            $sql = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, ";
            $sql .= "currentowner, currentstatus, bodytext, timestamp, duration) ";
            $sql .= "VALUES('{$incident}', '{$sit[2]}', 'fromtask', ";
            $sql .= "'{$owner}', '{$status}', '{$updatehtml}', '{$now}', '{$duration}')";
            mysql_query($sql);
            if (mysql_error())
            {
                trigger_error(mysql_error(), E_USER_ERROR);
                echo "<p class='error'>";
                echo "Couldn't add update, update will need to be done manually: {$sql}'</p>";
                die();
            }

            $sql = "UPDATE `{$dbIncidents}` SET lastupdated = '{$now}', status = 1 WHERE id = {$incident}";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            if (mysql_affected_rows() != 1)
            {
                trigger_error("No rows affected while updating incident", E_USER_ERROR);
            }

            mark_task_completed($id, TRUE);
        }
        else
        {
            mark_task_completed($id, FALSE);
        }

        // FIXME redundant i18n strings
        if ($incident) html_redirect("tasks.php?incident={$incident}", TRUE, $strActivityMarkedCompleteSuccessfully);
        else html_redirect("tasks.php", TRUE);
        break;
    case 'postpone':
        postpone_task($id);
        html_redirect("view_task.php?id={$id}", TRUE);
    break;

    case 'delete':
        $sql = "DELETE FROM `{$dbTasks}` ";
        $sql .= "WHERE id='{$id}' LIMIT 1";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

        $sql = "DELETE FROM `{$dbNotes}` ";
        $sql .= "WHERE link=10 AND refid='{$id}' ";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

        html_redirect("tasks.php", TRUE);
        break;
    case '':
    default:
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>".icon('task', 32)." ";
        echo "{$title}</h2>";
        plugin_do('task_edit');
        $sql = "SELECT * FROM `{$dbTasks}` WHERE id='{$id}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) >= 1)
        {
            while ($task = mysql_fetch_object($result))
            {
                $startdate = mysql2date($task->startdate);
                $duedate = mysql2date($task->duedate);
                $enddate = mysql2date($task->enddate);
                echo "<form id='edittask' action='{$_SERVER['PHP_SELF']}' method='post'>";
                echo "<table class='vertical'>";
                echo "<tr><th>{$strTitle}</th>";
                echo "<td><input type='text' name='name' size='35' maxlength='255' value=\"{$task->name}\" /></td></tr>";
                echo "<tr><th>{$strDescription}</th>";
                echo "<td><textarea name='description' rows='4' cols='30'>{$task->description}</textarea></td></tr>";
                if ($task->distribution == 'public')
                {
                    echo "<tr><th>{$strTags}:</th>";
                    echo "<td><textarea rows='2' cols='30' name='tags'>".list_tags($id, 4, false)."</textarea></td></tr>";
                }
                echo "<tr><th>{$strPriority}</th>";
                echo "<td>".priority_drop_down('priority',$task->priority)."</td></tr>";
                echo "<tr><th>{$strStartDate}</th>";
                echo "<td><input type='text' name='startdate' id='startdate' size='10' value='";
                if ($startdate > 0) echo date('Y-m-d', $startdate);
                echo "' /> ";
                echo date_picker('edittask.startdate');
                echo " ".time_picker(date('H', $startdate), date('i', $startdate), 'start_');
                echo "</td></tr>";
                echo "<tr><th>{$strDueDate}</th>";
                echo "<td><input type='text' name='duedate' id='duedate' size='10' value='";
                if ($duedate > 0) echo date('Y-m-d', $duedate);
                echo "' /> ";
                echo date_picker('edittask.duedate');
                echo " ".time_picker(date('H', $duedate), date('i', $duedate), 'due_');
                echo "</td></tr>";
                echo "<tr><th>{$strCompletion}</th>";
                echo "<td><input type='text' name='completion' size='3' maxlength='3' value='{$task->completion}' />&#037;</td></tr>";
                echo "<tr><th>{$strEndDate}</th>";
                echo "<td><input type='text' name='enddate' id='enddate' size='10' value='";
                if ($enddate > 0) echo date('Y-m-d',$enddate);
                echo "' /> ";
                echo date_picker('edittask.enddate');
                echo " ".time_picker(date('H', $enddate), date('i', $enddate), 'end_');
                echo "</td></tr>";
                echo "<tr><th>{$strValue}</th>";
                echo "<td><input type='text' name='value' size='6' maxlength='12' value='{$task->value}' /></td></tr>";
                echo "<tr><th>{$strUser}</th>";
                echo "<td>";
                echo user_drop_down('owner', $task->owner, FALSE);
                echo help_link('TaskUser')."</td></tr>";
                echo "<tr><th>{$strPrivacy}</th>";
                echo "<td>";
                echo "<input type='radio' name='distribution' ";
                if ($task->distribution == 'public') echo "checked='checked' ";
                echo "value='public' /> {$strPublic}<br />";
                echo "<input type='radio' name='distribution' ";
                if ($task->distribution == 'private') echo "checked='checked' ";
                echo "value='private' /> {$strPrivate} ".icon('private', 16, $strPrivate)."</td></tr>";
                plugin_do('task_edit_form');
                echo "</table>";
                echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
                echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
                echo "<input type='hidden' name='action' value='edittask' />";
                echo "<input type='hidden' name='id' value='{$id}' />";
                // Send copy of existing data so we can see when it is changed
                echo "<input type='hidden' name='old_name' value=\"{$task->name}\" />";
                echo "<input type='hidden' name='old_description' value=\"{$task->description}\" />";
                echo "<input type='hidden' name='old_priority' value=\"{$task->priority}\" />";
                echo "<input type='hidden' name='old_startdate' value='{$task->startdate}' />";
                echo "<input type='hidden' name='old_duedate' value='{$task->duedate}' />";
                echo "<input type='hidden' name='old_completion' value='{$task->completion}' />";
                echo "<input type='hidden' name='old_enddate' value='{$task->enddate}' />";
                echo "<input type='hidden' name='old_value' value='{$task->value}' />";
                echo "<input type='hidden' name='old_owner' value=\"{$task->owner}\" />";
                echo "<input type='hidden' name='old_distribution' value='{$task->distribution}' />";
                echo "</form>";
            }
        }
        else
        {
            echo "<p class='error'>{$strNoMatchingTaskFound}</p>";
        }

        echo "<p class='return'><a href='view_task.php?id={$id}'>{$strReturnWithoutSaving}</a></p>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>