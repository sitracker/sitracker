<?php
// tasks.php - List tasks
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Kieran Hogg <kieran[at]sitracker.org
// This Page Is Valid XHTML 1.0 Transitional!

$permission = 69;

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require_once (APPLICATION_LIBPATH . 'billing.inc.php');
require (APPLICATION_LIBPATH . 'auth.inc.php');

if (!$CONFIG['tasks_enabled'])
{
    header("Location: main.php");
}

$id = clean_int($_REQUEST['incident']);
if (!empty($id))
{
    $title = $strActivities;
    include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
}
else
{
    $title = $strTasks;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
}


// External variables
$user = clean_int($_REQUEST['user']);
$show = cleanvar($_REQUEST['show']);
$sort = cleanvar($_REQUEST['sort']);
$order = cleanvar($_REQUEST['order']);
$incident = clean_int($_REQUEST['incident']);
$siteid = clean_int($_REQUEST['siteid']);

?>
<script type='text/javascript'>
//<![CDATA[

/**
  * @author Paul Heaney
**/
function submitform()
{
    document.tasks.submit();
}

setInterval("countUp()", 1000); //every 1 seconds

//]]>
</script>
<?php


$selected = $_POST['selected'];
if (!empty($selected))
{
    foreach ($selected as $taskid)
    {
        if ($_POST['action'] == 'markcomplete')
        {
            mark_task_completed($taskid, FALSE);
        }
        elseif ($_POST['action'] == 'postpone')
        {
            postpone_task($taskid);
        }
    }
}


if (!empty($incident))
{
    $mode = 'incident';

    //get info for incident-->task linktype
    $sql = "SELECT DISTINCT origcolref, linkcolref ";
    $sql .= "FROM `{$dbLinks}` AS l, `{$dbLinkTypes}` AS lt ";
    $sql .= "WHERE l.linktype = 4 ";
    $sql .= "AND linkcolref = {$incident} ";
    $sql .= "AND direction = 'left'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    //get list of tasks
    $sql = "SELECT * FROM `{$dbTasks}` WHERE 1=0 ";
    while ($tasks = mysql_fetch_object($result))
    {
        $sql .= "OR id={$tasks->origcolref} ";
    }
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if ($mode == 'incident')
    {
        echo "<h2>".icon('activities', 32)." ";
        echo "{$strActivities}</h2>";
    }
    echo "<p align='center'>{$strIncidentActivitiesIntro}</p>";
}
elseif (!empty($siteid))
{
    // Find all tasks for site
    $sql = "SELECT i.id FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c ";
    $sql .= "WHERE i.contact = c.id AND ";
    $sql .= "c.siteid = {$siteid} ";
    //$sql .= "AND (i.status != 2 AND i.status != 7)";
    $result = mysql_query($sql);

    //
    $sqlTask = "SELECT * FROM `{$dbTasks}` WHERE duedate IS NULL AND ";
    $taskIDs = array();

    if (mysql_num_rows($result) > 0)
    {
        while ($obj = mysql_fetch_object($result))
        {
            //get info for incident-->task linktype
            $sql = "SELECT DISTINCT origcolref, linkcolref ";
            $sql .= "FROM `{$dbLinks}` AS l, `{$dbLinkTypes}` AS lt ";
            $sql .= "WHERE l.linktype=4 ";
            $sql .= "AND linkcolref={$obj->id} ";
            $sql .= "AND direction='left'";
            $resultLinks = mysql_query($sql);
    
            //get list of tasks
            while ($tasks = mysql_fetch_object($resultLinks))
            {
                //$sqlTask .= "OR id={$tasks->origcolref} ";
                //if (empty($orSQL)) $orSQL = "(";
                //else $orSQL .= " OR ";
                //$orSQL .= "id={$tasks->origcolref} ";
                $taskIDs[] = $tasks->origcolref;
            }   
        }
    }

    if (!empty($taskIDs)) $sqlTask .= "id IN (".implode(',', $taskIDs).")";
    else $sqlTasks = "1=0";
    
    $result = mysql_query($sqlTask);

    $show = 'incidents';
    //$show = 'incidents';
    echo "<h2>".sprintf($strActivitiesForX, site_name($siteid))."</h2>";
}
else
{
    // Defaults
    if (empty($user) OR $user == 'current')
    {
        $user = $sit[2];
    }

    // If the user is passed as a username lookup the userid
    if (!is_numeric($user) AND $user != 'current' AND $user != 'all')
    {
        $usql = "SELECT id FROM `{$dbUsers}` WHERE username='{$user}' LIMIT 1";
        $uresult = mysql_query($usql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

        if (mysql_num_rows($uresult) >= 1)
        {
            list($user) = mysql_fetch_row($uresult);
        }
        else
        {
            $user = $sit[2]; // force to current user if username not found
        }
    }

    if ($show != 'incidents')
    {
        if ($user != 'all')
        {
            echo "<h2>".icon('task', 32)." ";
            echo sprintf($strXsTasks, user_realname($sit[2]))."</h2>";
            echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?user=all&amp;";
            echo "show={$show}&amp;sort={$sort}&amp;order={$order}'>{$strShowEverybodys}</a></p>";
        }
        else
        {
            echo "<h2>".icon('task', 32)." {$strEverybodysTasks}</h2>";
            echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?show={$show}";
            echo "&amp;sort={$sort}&amp;order={$order}'>{$strShowMine}</a></p>";
        }
    }
    else
    {
        if ($user != 'all')
        {
            echo "<h2>".icon('task', 32)." ";
            echo sprintf($strXsTasks, user_realname($sit[2]))."</h2>";
            echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?show=incidents";
            echo "&amp;user=all'>{$strShowAll}</a></p>";
        }
        else
        {
            echo "<h2>".icon('task', 32)." {$strAllActivities}</h2>";

            echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?show=incidents'>";
            echo "{$strShowMine}</a></p>";
        }
    }

    // show drop down select for task view options
    echo "<form id='tasksform' action='{$_SERVER['PHP_SELF']}'>";
    echo "{$strView}: <select class='dropdown' name='queue' ";
    echo "onchange='window.location.href=this.options[this.selectedIndex].value'>\n";
    echo "<option ";
    if ($show == '' OR $show == 'active')
    {
        echo "selected='selected' ";
    }

    echo "value='{$_SERVER['PHP_SELF']}?user=$user&amp;show=active";
    echo "&amp;sort=$sort&amp;order=$order'>{$strActive}</option>\n";
    echo "<option ";
    if ($show == 'completed')
    {
        echo "selected='selected' ";
    }

    echo "value='{$_SERVER['PHP_SELF']}?user=$user&amp;show=completed";
    echo "&amp;sort=$sort&amp;order=$order'>{$strCompleted}</option>\n";

    echo "</select>\n";
    echo "</form><br />";

    $sql = "SELECT * FROM `{$dbTasks}` WHERE ";
    if ($user != 'all')
    {
        $sql .= "owner='$user' AND ";
    }

    if ($show == '' OR $show == 'active' )
    {
        $sql .= "(completion < 100 OR completion='' OR completion IS NULL) ";
        $sql .= "AND (distribution = 'public' OR distribution = 'private') ";
    }
    elseif ($show == 'completed')
    {
        $sql .= " (completion = 100) AND (distribution = 'public' ";
        $sql .= "OR distribution = 'private') ";
    }
    elseif ($show == 'incidents')
    {
        $sql .= " distribution = 'incident' ";

        if (empty($incidentid))
        {
            $sql .= "AND (completion < 100 OR completion='' ";
            $sql .= "OR completion IS NULL) ";
        }
    }
    else
    {
        $sql .= "1=2 "; // force no results for other cases
    }

    if ($user == 'all' AND $show == 'incidents')
    {
        // ALL all incident tasks to be viewed
    }
    elseif ($user != $sit[2])
    {
        $sql .= "AND (distribution='public' OR (distribution='private' AND owner = {$sit[2]})) ";
    }

    if (!empty($sort))
    {
        if ($sort == 'id') $sql .= "ORDER BY id ";
        elseif ($sort == 'name') $sql .= "ORDER BY name ";
        elseif ($sort == 'priority') $sql .= "ORDER BY priority ";
        elseif ($sort == 'completion') $sql .= "ORDER BY completion ";
        elseif ($sort == 'startdate') $sql .= "ORDER BY startdate ";
        elseif ($sort == 'duedate') $sql .= "ORDER BY duedate ";
        elseif ($sort == 'enddate') $sql .= "ORDER BY enddate ";
        elseif ($sort == 'distribution') $sql .= "ORDER BY distribution ";
        else $sql .= "ORDER BY id ";

        if ($order == 'a' OR $order == 'ASC' OR $order == '') $sql .= "ASC";
        else $sql .= "DESC";
    }
    else
    {
        $sql .= "ORDER BY IF(duedate,duedate,99999999) ASC, duedate ASC, ";
        $sql .= "startdate DESC, priority DESC, completion ASC";
    }

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
}

//common code
if (mysql_num_rows($result) >=1 )
{
    if ($show) $filter = array('show' => $show);
    echo "<form action='{$_SERVER['PHP_SELF']}' name='tasks'  method='post'>";
    echo "<br /><table align='center'>";
    echo "<tr>";
    $filter['mode'] = $mode;
    $filter['incident'] = $incident;
    if ($mode != 'incident')
    {
        $totalduration = 0;
        $closedduration = 0;

        if ($show != 'incidents')
        {
            echo colheader('markcomplete', '', $sort, $order, $filter);
        }
        else
        {
            echo colheader('incidentid', $strIncident, $sort, $order, $filter);
        }

        if ($user == $sit[2])
        {
            echo colheader('distribution', icon('private', 16, $strPrivate),
                           $sort, $order, $filter);
        }
        else
        {
            $filter['user'] = $user;
        }

        echo colheader('id', $strID, $sort, $order, $filter);
        echo colheader('name', $strTask, $sort, $order, $filter);
        if ($show != 'incidents')
        {
            echo colheader('priority', $strPriority, $sort, $order, $filter);
            echo colheader('completion', $strCompletion, $sort, $order, $filter);
        }
        echo colheader('startdate', $strStartDate, $sort, $order, $filter);
        echo colheader('duedate', $strDueDate, $sort, $order, $filter);
        if ($show == 'completed')
        {
            echo colheader('enddate', $strEndDate, $sort, $order, $filter);
        }

        if ($show == 'incidents')
        {
            echo colheader('owner', $strOwner, $sort, $order, $filter);
        }
    }
    else
    {
        echo colheader('id', $strID);
        echo colheader('startdate', $strStartDate);
        echo colheader('completeddate', $strCompleted);
        echo colheader('duration', $strDuration);
        echo colheader('lastupdated', $strLastUpdated);
        echo colheader('owner', $strOwner);
        echo colheader('action', $strAction);
    }
    echo "</tr>\n";
    $shade = 'shade1';
    while ($task = mysql_fetch_object($result))
    {
        $duedate = mysql2date($task->duedate);
        $startdate = mysql2date($task->startdate);
        $enddate = mysql2date($task->enddate);
        $lastupdated = mysql2date($task->lastupdated);
        echo "<tr class='$shade'>";
        if ($mode != 'incident' AND $show != 'incidents')
        {
            echo "<td align='center'><input type='checkbox' name='selected[]' ";
            echo "value='{$task->id}' /></td>";
        }
        else if (empty($incidentid))
        {
            $sqlIncident = "SELECT DISTINCT origcolref, linkcolref, i.title ";
            $sqlIncident .= "FROM `{$dbLinks}` AS l, `{$dbLinkTypes}` AS lt, ";
            $sqlIncident .= "`{$dbIncidents}` AS i ";
            $sqlIncident .= "WHERE l.linktype=4 ";
            $sqlIncident .= "AND l.origcolref={$task->id} ";
            $sqlIncident .= "AND l.direction='left' ";
            $sqlIncident .= "AND i.id = l.linkcolref ";
            $resultIncident = mysql_query($sqlIncident);

            echo "<td>";
            if ($obj = mysql_fetch_object($resultIncident))
            {
                $incidentidL = $obj->linkcolref;
                echo "<a href=\"javascript:incident_details_window('{$obj->linkcolref}'
                      ,'incident{$obj->linkcolref}')\" class='info'>";
                echo $obj->linkcolref;
                echo "</a>";
                $incidentTitle = $obj->title;
            }
            echo "</td>";
        }

        if ($user == $sit[2])
        {
            echo "<td>";
            if ($task->distribution == 'private')
            {
                echo icon('private', 16, 'Private');
            }
            echo "</td>";
        }

        if ($mode == 'incident')
        {
            if ($enddate == '0')
            {
                echo "<td><a href='view_task.php?id={$task->id}&amp;mode=incident&amp;incident={$id}' class='info'>";
                echo icon('timer', 16)." {$task->id}</a></td>";
            }
            else
            {
                echo "<td>{$task->id}</td>";
            }
        }
        else
        {
            echo "<td>";
            echo "{$task->id}";
            echo "</td>";
            echo "<td>";
            if (empty($task->name))
            {
                $task->name = $strUntitled;
            }

            if (!empty($incidentTitle)){
                $task->name = $incidentTitle;
            }

            if ($show == 'incidents')
            {
                echo "<a href=\"javascript:incident_details_window('{$incidentidL}','incident{$incidentidL}')\" class='info'>";
            }
            else
            {
                echo "<a href='view_task.php?id={$task->id}' class='info'>";
            }

            echo truncate_string($task->name, 100);
            echo "</a>";

            echo "</td>";
            if ($show != 'incidents')
            {
                echo "<td>".priority_icon($task->priority).priority_name($task->priority)."</td>";
                echo "<td>".percent_bar($task->completion)."</td>";
            }
        }

        if ($mode != 'incident')
        {
            echo "<td";
            if ($startdate > 0 AND $startdate <= $now AND $task->completion <= 0)
            {
                echo " class='urgent'";
            }
            elseif ($startdate > 0 AND $startdate <= $now AND
                    $task->completion >= 1 AND $task->completion < 100)
            {
                echo " class='idle'";
            }

            echo ">";
            if ($startdate > 0)
            {
                echo ldate($CONFIG['dateformat_date'],$startdate);
            }

            echo "</td>";
            echo "<td";
            if ($duedate > 0 AND $duedate <= $now AND $task->completion < 100)
            {
                echo " class='urgent'";
            }

            echo ">";
            if ($duedate > 0)
            {
                echo ldate($CONFIG['dateformat_date'],$duedate);
            }
            echo "</td>";
        }
        else
        {
            $billing = make_incident_billing_array($incidentid);
            echo "<td>".format_date_friendly($startdate)."</td>";
            if ($enddate == '0')
            {
                echo "<td><script type='text/javascript'>\n//<![CDATA[\n";
                echo "var act = new Activity();";
                echo "act.id = {$task->id};";
                echo "act.start = {$startdate}; ";
                echo "addActivity(act);";
                echo "\n//]]>\n</script>";

                echo "$strNotCompleted</td>";
                $duration = $now - $startdate;

                //echo "<td id='duration{$task->id}'><em><div id='duration{$task->id}'>".format_seconds($duration)."</div></em></td>";
                echo "<td id='duration{$task->id}'>".format_seconds($duration)."</td>";
            }
            else
            {
                $duration = $enddate - $startdate;

                $a = $duration % ($billing[-1]['customerperiod']);
                $duration += ($billing[-1]['customerperiod'] - $a);

                echo "<td>".format_date_friendly($enddate)."</td>";
                echo "<td>".format_seconds($duration)."</td>";
                $closedduration += $duration;

                $temparray['owner'] = $task->owner;
                $temparray['starttime'] = $startdate;
                $temparray['duration'] = $duration;
                $billing[$task->owner][] = $temparray;
            }
            $totalduration += $duration;

            echo "<td>".format_date_friendly($lastupdated)."</td>";
        }

        if ($show == 'completed')
        {
            echo "<td>";
            if ($enddate > 0)
            {
                echo ldate($CONFIG['dateformat_date'],$enddate);
            }

            echo "</td>";
        }
        if ($mode == 'incident' OR $show == 'incidents')
        {
            echo "<td>".user_realname($task->owner)."</td>";
            if ($task->owner == $sit[2] AND $enddate == '0')
            {
                $engineerhasrunnintask = TRUE;
            }
        }

        if ($mode == 'incident' AND $enddate == '0')
        {
            echo "<td><a href='view_task.php?id={$task->id}&amp;mode=incident&amp;incident={$id}' class='info'>";
            echo "{$strViewActivity}</a></td>";
        }
        elseif ($mode == 'incident')
        {
            echo "<td></td>";
        }

        echo "</tr>\n";
        if ($shade == 'shade1') $shade = 'shade2';
        else $shade = 'shade1';
    }

    if ($mode == 'incident')
    {
        echo "<tr class='{$shade}'><td><strong>{$strTotal}:</strong></td>";
        echo "<td colspan='6' id='totalduration'>".exact_seconds($totalduration);

        echo "<script type='text/javascript'>\n//<![CDATA[\n";
        if (empty($closedduration)) $closedduration = 0;
        echo "setClosedDuration({$closedduration});";
        echo "\n//]]>\n</script>";
        echo "</td></tr>";
    }
    else if ($show != 'incidents')
    {
        echo "<tr>";
        echo "<td colspan='7'>";
        //echo "<label for='action'>{$strAction} ";
        echo "<select name='action' onchange='submitform();'>";
        echo "<option>{$strSelectAction}</option>";
        echo "<option value='markcomplete'>{$strMarkComplete}</option>";
        echo "<option value='postpone'>{$strPostpone}</option>";
        echo "</select>";
        //echo "<a href=\"javascript: submitform()\">{$strMarkComplete}</a>";
        //echo " <a href=\"javascript: submitform()\">{$strPostpone}</a>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</table>\n";
    echo "</form>";

    if ($mode == 'incident')
    {
        echo "<script type='text/javascript'>\n//<![CDATA[\ncountUp();\n//]]>\n</script>";  //force a quick udate
    }

    //echo "<pre>";
    //print_r($billing);
    //echo "</pre>";

    if ($mode == 'incident')
    {
        // Show add activity link if the incident is open
        if (incident_status($id) != 2 AND !$engineerhasrunnintask)
        {
            echo "<p align='center'><a href='task_add.php?incident={$id}'>{$strStartNewActivity}</a></p>";
        }
    }
    else if ($show != 'incidents')
    {
        echo "<p align='center'><a href='task_add.php'>{$strAddTask}</a></p>";
    }

    if ($mode == 'incident')
    {
        echo "<h3>".icon('billing', 32, $strActivityBilling)." ";
        echo "{$strActivityBilling}</h3>";
        echo "<p align='center'>{$strActivityBillingInfo}</p>";

        if (!empty($billing))
        {
            echo "<table align='center'>\n";
            echo "<tr><td></td><th>{$GLOBALS['strMinutes']}</th></tr>\n";
            echo "<tr><th>{$GLOBALS['strBillingEngineerPeriod']}</th>\n";
            echo "<td>".($billing[-1]['engineerperiod']/60)."</td></tr>\n";
            echo "<tr><th>{$GLOBALS['strBillingCustomerPeriod']}</th>\n";
            echo "<td>".($billing[-1]['customerperiod']/60)."</td></tr>\n";
            echo "</table>\n";

            echo "<br />";

            echo "<table align='center'>\n";

            echo "<tr><th>{$GLOBALS['strOwner']}</th><th>{$GLOBALS['strTotalMinutes']}</th>\n";
            echo "<th>{$GLOBALS['strBillingEngineerPeriod']}</th>\n";
            echo "<th>{$GLOBALS['strBillingCustomerPeriod']}</th></tr>\n";
            $shade = "shade1";

            foreach ($billing AS $engineer)
            {
                if (!empty($engineer['totalduration']))
                {
                    $totals = $engineer;
                }
                else
                {
                    echo "<tr class='{$shade}'><td>{$engineer['owner']}</td>";
                    echo "<td>".round($engineer['duration'])."</td>";
                    echo "<td>".sizeof($engineer['engineerperiods'])."</td>";
                    echo "<td>".sizeof($engineer['customerperiods'])."</td></tr>";
                }

                if ($shade == "shade1") $shade = "shade2";
                else $shade = "shade2";
            }
            echo "<tr><td>{$GLOBALS['strTOTALS']}</td><td>".round($totals['totalduration'])."</td>\n";
            echo "<td>{$totals['totalengineerperiods']}</td><td>{$totals['totalcustomerperiods']}</td></tr>\n";
            echo "</table>\n";
        }
        else
        {
            echo "<p align='center'><strong>{$strNoRecords}</strong></p>";
        }
    }
}
else
{
    echo "<br /><p align='center'>";
    echo "<strong>{$strNoRecords}</strong>";
    echo "</p>";

    if ($mode == 'incident')
    {
        echo "<p align='center'>";
        echo "<a href='task_add.php?incident={$id}'>{$strStartNewActivity}";
        echo "</a></p>";
    }
    else if ($show != 'incidents')
    {
        echo "<p align='center'><a href='task_add.php'>{$strAddTask}</a></p>";
    }
}

if (!empty($id))
{
    include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
}
else
{
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>