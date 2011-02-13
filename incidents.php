<?php
// incidents.php - Main Incidents Queue Display
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!   31Oct05


$permission = 6; // View Incidents


require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$type = cleanvar($_REQUEST['type']);
if (cleanvar($_REQUEST['user']) == 'current') $user = cleanvar($_REQUEST['user']); 
else $user = clean_var($_REQUEST['user']);
$softwareid = clean_int($_REQUEST['softwareid']);
$queue = cleanvar($_REQUEST['queue']);
$sort = cleanvar($_REQUEST['sort']);
$order = cleanvar($_REQUEST['order']);
$maintexclude = cleanvar($_REQUEST['maintexclude']);
$title = $strIncidentsList;

// Defaults
if (empty($type)) $type = 'support';
if (empty($user)) $user = 'current';
if (empty($sort)) $sort = 'priority';
if (empty($queue)) $queue = 1;

$refresh = $_SESSION['userconfig']['incident_refresh'];

if ($user == 'current' OR $user == $_SESSION['userid'])
{
    $rssfeedurl = "incidents_rss.php?c=".md5($_SESSION['username'] . md5($CONFIG['db_password']));
    $rssfeedtitle = $strIncidents;
}
include (APPLICATION_INCPATH . 'htmlheader.inc.php');
?>
<script type="text/javascript">
//<![CDATA[
function statusform_submit(user)
{
    URL = "incidents.php?status=" + window.document.statusform.status.options[window.document.statusform.status.selectedIndex].value + "&amp;user=" + user;
    window.confirm(URL);
    window.location.href = URL;
}
//]]>
</script>
<?php

// Extract escalation paths
$epsql = "SELECT id, name, track_url, home_url, url_title FROM `{$dbEscalationPaths}`";
$epresult = mysql_query($epsql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
if (mysql_num_rows($epresult) >= 1)
{
    while ($escalationpath = mysql_fetch_object($epresult))
    {
        $epath[$escalationpath->id]['name'] = $escalationpath->name;
        $epath[$escalationpath->id]['track_url'] = $escalationpath->track_url;
        $epath[$escalationpath->id]['home_url'] = $escalationpath->home_url;
        $epath[$escalationpath->id]['url_title'] = $escalationpath->url_title;
    }
}

// Generic bit of SQL, common to both queue types
$selectsql = "SELECT i.id, escalationpath, externalid, title, i.owner, towner, priority, status, closingstatus, siteid, s.name AS site, c.id AS contactid, forenames, surname, ";
$selectsql .= "IF(c.phone IS NULL, s.telephone, c.phone) AS phone, IF(c.email IS NULL, s.email, c.email) AS email, i.maintenanceid, ";
$selectsql .= "servicelevel, softwareid, lastupdated, timeofnextaction, ";
$selectsql .= "(timeofnextaction - {$now}) AS timetonextaction, opened, ({$now} - opened) AS duration, closed, (closed - opened) AS duration_closed, type, ";
$selectsql .= "({$now} - lastupdated) AS timesincelastupdate ";
$selectsql .= "FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c, `{$dbPriority}` AS pr, `{$dbSites}` AS s ";

echo "<div id='incidentqueues'>";

switch ($type)
{
    case 'support':
        // Create SQL for chosen queue
        // If you alter this SQL also update the function user_activeincidents($id)
        if ($user == 'current') $user = $sit[2];
        // If the user is passed as a username lookup the userid
        if (!is_number($user) AND $user != 'current' AND $user != 'all')
        {
            $usql = "SELECT id FROM `{$dbUsers}` WHERE username='{$user}' LIMIT 1";
            $uresult = mysql_query($usql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($uresult) >= 1) list($user) = mysql_fetch_row($uresult);
            else $user = $sit[2]; // force to current user if username not found
        }
        
        $sql = $selectsql . "WHERE contact = c.id AND i.priority = pr.id AND c.siteid = s.id ";
        $sql .= "AND i.owner > 0 ";  // We always need to have an owner which is not sit
        if ($user != 'all') $sql .= "AND (i.owner='{$user}' OR i.towner='{$user}') ";
        if (!empty($softwareid)) $sql .= "AND softwareid='{$softwareid}' ";

        if (!empty($maintexclude)) $sql .= "AND i.maintenanceid != '{$maintexclude}' ";

        echo "<h2>".icon('support', 32, $strSupport)." ";

        if ($user != 'all')
        {
            echo sprintf($strUserIncidents, user_realname($user, TRUE)).": ";
        }
        else
        {
            echo "{$strViewingAllIncidents} ";
        }

        switch ($queue)
        {
            case 1: // Action Needed
                echo "<span class='actionqueue'>{$strActionNeeded}</span>";
                $sql .= "AND (status!='2') ";  // not closed
                // the "1=2" obviously false else expression is to prevent records from showing unless the IF condition is true
                $sql .= "AND ((timeofnextaction > 0 AND timeofnextaction < {$now}) OR ";
                if ($user != 'all') $sql .= "(status='5' AND towner={$user}) OR ";
                $sql .= "(IF ((status >= 5 AND status <=8), ({$now} - lastupdated) > ({$CONFIG['regular_contact_days']} * 86400), 1=2 ) ";  // awaiting
                $sql .= "OR IF (status='1' OR status='3' OR status='4', 1=1 , 1=2) ";  // active, research, left message - show all
                $sql .= ") AND timeofnextaction < {$now} ) ";
                break;
            case 2: // Waiting
                echo "<span class='waitingqueue'>{$strWaiting}</span>";
                $sql .= "AND ((status >= 4 AND status <= 8) OR (timeofnextaction > 0 AND timeofnextaction > {$now})) ";
                break;
            case 3: // All Open
                echo "<span class='openqueue'>{$strAllOpen}</span>";
                $sql .= "AND status!='2' ";
                break;
            case 4: // All Closed
                echo "<span class='closedqueue'>{$strAllClosed}</span>";
                $sql .= "AND status='2' ";
                if ($CONFIG['hide_closed_incidents_older_than'] > -1 AND $_GET['show'] != 'all')
                {
                    $old = $now - ($CONFIG['hide_closed_incidents_older_than'] * 86400);
                    $sql .= "AND closed >= {$old} ";
                }
                break;
            default:
                trigger_error("Invalid queue ($queue) on query string", E_USER_NOTICE);
                break;
        }        // Create SQL for Sorting

        echo "</h2>\n";
        if (!empty($sort))
        {
            if ($order == 'a' OR $order == 'ASC') $sortorder = "ASC";
            else $sortorder = "DESC";
            switch ($sort)
            {
                case 'id':
                    $sql .= " ORDER BY id {$sortorder}";
                    break;
                case 'title':
                    $sql .= " ORDER BY title {$sortorder}";
                    break;
                case 'contact':
                    $sql .= " ORDER BY c.surname {$sortorder}, c.forenames {$sortorder}";
                    break;
                case 'priority':
                    $sql .=  " ORDER BY priority {$sortorder}, lastupdated ASC";
                    break;
                case 'status':
                    $sql .= " ORDER BY status {$sortorder}";
                    break;
                case 'lastupdated':
                    $sql .= " ORDER BY lastupdated {$sortorder}";
                    break;
                case 'duration':
                    $sql .= " ORDER BY duration {$sortorder}";
                    break;
                case 'nextaction':
                    $sql .= " ORDER BY timetonextaction {$sortorder}";
                    break;
                default:
                    $sql .= " ORDER BY priority DESC, lastupdated ASC";
                    break;
            }
        }

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $rowcount = mysql_num_rows($result);

        // Toggle Sorting Order
        if ($sortorder == 'ASC')
        {
            $newsortorder = 'DESC';
        }
        else
        {
            $newsortorder = 'ASC';
        }

        // build querystring for hyperlinks
        $querystring = "?user={$user}&amp;queue={$queue}&amp;type={$type}&amp;";

        // show drop down of incident status
        echo "<form action='{$_SERVER['PHP_SELF']}'>";
        echo "{$strQueue}: <select class='dropdown' name='queue' onchange='window.location.href=this.options[this.selectedIndex].value'>\n";
        echo "<option ";
        if ($queue == 1) echo "selected='selected' ";
        echo "value='{$_SERVER['PHP_SELF']}?user={$user}&amp;type={$type}&amp;queue=1'>{$strActionNeeded}</option>\n";
        echo "<option ";
        if ($queue == 2) echo "selected='selected' ";
        echo "value='{$_SERVER['PHP_SELF']}?user={$user}&amp;type={$type}&amp;queue=2'>{$strWaiting}</option>\n";
        echo "<option ";
        if ($queue == 3) echo "selected='selected' ";
        echo "value='{$_SERVER['PHP_SELF']}?user={$user}&amp;type={$type}&amp;queue=3'>{$strAllOpen}</option>\n";
        if ($user != 'all')
        {
            echo "<option ";
            if ($queue == 4) echo "selected='selected' ";
            echo "value='{$_SERVER['PHP_SELF']}?user={$user}&amp;type={$type}&amp;queue=4'>{$strAllClosed}</option>\n";
        }
        echo "</select>\n";
        echo "</form>";

        if ($queue == 4 AND $CONFIG['hide_closed_incidents_older_than'] != -1 AND $_GET['show'] != 'all')
        {
            echo "<p class='info'>".sprintf($strHidingIncidentsOlderThan, $CONFIG['hide_closed_incidents_older_than']);
            echo " - <a href='{$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']}&show=all'>{$strShowAll}</a></p>";
        }
        elseif ($queue == 4 AND $CONFIG['hide_closed_incidents_older_than'] != -1)
        {
            echo "<p class='info'>{$strShowingAllClosedIncidents} - ";
            echo "<a href='{$_SERVER['PHP_SELF']}?user=$user&amp;type=$type&amp;queue=4'>";
            echo sprintf($strOnlyShowNewerThan, $CONFIG['hide_closed_incidents_older_than'])."</a></p>";
        }

        if (!empty($softwareid)) echo "<p align='center'>".sprintf($strFilterActiveOnlyShowingIncidentsForX, software_name($softwareid))."</p>";
        if ($user == 'all') echo "<p align='center'>".sprintf($strThereAreXIncidentsInThisList, $rowcount)."</p>";
        else echo "<br />";

        // Print message if no incidents were listed
        if (mysql_num_rows($result) >= 1)
        {
            // Incidents Table
            include (APPLICATION_INCPATH . 'incidents_table.inc.php');
        }
        else
        {
            echo "<p class='info'>{$strNoIncidents}</p>";
        }

        if ($user == 'all') echo "<p align='center'>".sprintf($strNumOfIncidents, $rowcount)."</p>";


        // *********************************************************
        // EXPERTISE QUEUE
        // ***
        if ($user == 'current') $user = $sit[2];
        $softsql = "SELECT * FROM `{$dbUserSoftware}` WHERE userid='{$user}' ";
        $softresult = mysql_query($softsql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        $softcount = mysql_num_rows($softresult);

        if ($softcount >= 1)
        {
            // list expertise queues
            while ($software = mysql_fetch_object($softresult))
            {
                $expertise[] = $software->softwareid;
            }

            $incsql .= "(";
            for ($i = 0; $i < $softcount; $i++)
            {
                $incsql .= "softwareid='{$expertise[$i]}'";
                if ($i < ($softcount-1)) $incsql .= " OR ";
            }
            $incsql .= ")";

            // Create SQL for chosen queue
            $sql = $selectsql . "WHERE contact=c.id AND i.priority=pr.id ";
            $sql .= "AND i.owner!='{$user}' AND towner!='{$user}' AND i.owner > 0 ";
            $sql .= "AND $incsql ";

            switch ($queue)
            {
                case 1: // Action Needed
                    echo "<h2>{$strOtherIncidents}: <span class='actionqueue'>{$strActionNeeded}</span>".help_link("OtherIncidents")."</h2>\n";
                    $sql .= "AND (status!='2') ";  // not closed
                    // the "1=2" obviously false else expression is to prevent records from showing unless the IF condition is true
                    $sql .= "AND ((timeofnextaction > 0 AND timeofnextaction < {$now}) OR ";
                    $sql .= "(IF ((status >= 5 AND status <=8), ({$now} - lastupdated) > ({$CONFIG['regular_contact_days']} * 86400), 1=2 ) ";  // awaiting
                    $sql .= "OR IF (status='1' OR status='3' OR status='4', 1=1 , 1=2) ";  // active, research, left message - show all
                    $sql .= ") AND timeofnextaction < {$now} ) ";
                    // outstanding
                    break;
                case 2: // Waiting
                    echo "<h2>{$strOtherIncidents}: <span class='waitingqueue'>{$strWaiting}</span></h2>\n";
                    $sql .= "AND ((status >= 4 AND status <= 8) OR (timeofnextaction > 0 AND timeofnextaction > {$now})) ";
                    break;
                case 3: // All Open
                    echo "<h2>{$strOtherIncidents}: <span class='openqueue'>{$strAllOpen}</span></h2>\n";
                    echo "</h2><hr /><br />";
                    $sql .= "AND status!='2' ";
                  break;
                case 4: // All Closed
                    echo "<h2>{$strOtherIncidents}: <span class='closedqueue'>{$strAllClosed}</span></h2>\n";
                    echo "</h2><hr /><br />";
                    $sql .= "AND status='2' ";
                    if ($CONFIG['hide_closed_incidents_older_than'] > -1 AND $_GET['show'] != 'all')
                    {
                        $old = $now - ($CONFIG['hide_closed_incidents_older_than'] * 86400);
                        $sql .= "AND closed >= {$old} ";
                    }
                    break;
                default:
                    trigger_error("Invalid queue ($queue) on query string", E_USER_NOTICE);
                    break;
            }

            // Create SQL for Sorting
            switch ($sort)
            {
                case 'id':
                    $sql .= " ORDER BY id {$sortorder}";
                    break;
                case 'title':
                    $sql .= " ORDER BY title {$sortorder}";
                    break;
                case 'contact':
                    $sql .= " ORDER BY c.surname {$sortorder}, c.forenames {$sortorder}";
                    break;
                case 'priority':
                    $sql .=  " ORDER BY priority {$sortorder}, lastupdated ASC";
                    break;
                case 'status':
                    $sql .= " ORDER BY status {$sortorder}";
                    break;
                case 'lastupdated':
                    $sql .= " ORDER BY lastupdated {$sortorder}";
                    break;
                case 'duration':
                    $sql .= " ORDER BY duration {$sortorder}";
                    break;
                case 'nextaction':
                    $sql .= " ORDER BY timetonextaction {$sortorder}";
                    break;
                default:
                    $sql .= " ORDER BY priority DESC, lastupdated ASC";
                    break;
            }
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            $rowcount = mysql_num_rows($result);

            // expertise incident listing goes here
            // Print message if no incidents were listed
            if ($rowcount >= 1)
            {
                // Incidents Table
                include (APPLICATION_INCPATH . 'incidents_table.inc.php');
            }
            else
            {
                echo "<p class='info'>{$strNoIncidents}</p>";
            }

            // end of expertise queue
            // ***
        }
        echo "</div>";
}
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>