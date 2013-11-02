<?php
// incidents.php - Main Incidents Queue Display
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!   31Oct05

require ('core.php');
$permission = PERM_INCIDENT_LIST; // View Incidents
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$type = cleanvar($_REQUEST['type']);
if (cleanvar($_REQUEST['user']) == 'current' or empty($_REQUEST['user'])) $user = clean_int($sit[2]);
else $user = cleanvar($_REQUEST['user']);
$softwareid = clean_int($_REQUEST['softwareid']);
$queue = clean_int($_REQUEST['queue']);
$sort = clean_fixed_list($_REQUEST['sort'], array('','id','title','contact','priority','status','lastupdated','duration','nextaction'));
$order = clean_fixed_list($_REQUEST['order'], array('','a','d','ASC','DESC'));
$maintexclude = cleanvar($_REQUEST['maintexclude']);
$title = $strIncidentsList;

// Defaults
if (empty($type)) $type = 'support';
if (empty($sort)) $sort = 'priority';
if (empty($queue)) $queue = 1;

$refresh = $_SESSION['userconfig']['incident_refresh'];

if ($user == 'current' OR $user == $_SESSION['userid'])
{
    $rssfeedurl = "incidents_rss.php?c=".md5($_SESSION['username'] . md5($CONFIG['db_password']));
    $rssfeedtitle = $strIncidents;
}
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

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
$selectsql .= "(timeofnextaction - {$now}) AS timetonextaction, opened, ({$now} - opened) AS duration, closed, (closed - opened) AS duration_closed, it.name AS type, ";
$selectsql .= "({$now} - lastupdated) AS timesincelastupdate, i.customerid ";
$selectsql .= "FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c, `{$dbPriority}` AS pr, `{$dbSites}` AS s, `{$dbIncidentTypes}` AS it ";
$selectsql .= "WHERE contact = c.id AND i.priority = pr.id AND c.siteid = s.id AND i.typeid = it.id ";

echo "<div id='incidentqueues'>";

switch ($type)
{
    case 'support':
        // Create SQL for chosen queue
        // If you alter this SQL also update the function user_activeincidents($id)
        // If the user is passed as a username lookup the userid
        if (!is_numeric($user) AND $user != 'current' AND $user != 'all')
        {
            $usql = "SELECT id FROM `{$dbUsers}` WHERE username='{$user}' LIMIT 1";
            $uresult = mysql_query($usql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($uresult) >= 1) list($user) = mysql_fetch_row($uresult);
            else $user = $sit[2]; // force to current user if username not found
        }

        $sql = "{$selectsql} AND i.owner > 0 ";  // We always need to have an owner which is not sit
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
            echo "{$strViewingAllIncidents}: ";
        }

        switch ($queue)
        {
            case QUEUE_ACTION_NEEDED:
                echo "<span class='actionqueue'>{$strActionNeeded}</span>";
                $sql .= "AND (status!='2') ";  // not closed
                // the "1=2" obviously false else expression is to prevent records from showing unless the IF condition is true
                $sql .= "AND ((timeofnextaction > 0 AND timeofnextaction < {$now}) OR ";
                if ($user != 'all') $sql .= "(status='5' AND towner={$user}) OR ";
                $sql .= "(IF ((status >= 5 AND status <=8), ({$now} - lastupdated) > ({$CONFIG['regular_contact_days']} * 86400), 1=2 ) ";  // awaiting
                $sql .= "OR IF (status='1' OR status='3' OR status='4', 1=1 , 1=2) ";  // active, research, left message - show all
                $sql .= ") AND timeofnextaction < {$now} ) ";
                break;
            case QUEUE_WAITING:
                echo "<span class='waitingqueue'>{$strWaiting}</span>";
                $sql .= "AND ((status >= 4 AND status <= 8) OR (timeofnextaction > 0 AND timeofnextaction > {$now})) ";
                break;
            case QUEUE_ALL_OPEN:
                echo "<span class='openqueue'>{$strAllOpen}</span>";
                $sql .= "AND status!='2' ";
                break;
            case QUEUE_ALL_CLOSED:
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
        }

        echo "</h2>\n";
        plugin_do('incidents');
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
        if ($queue == QUEUE_ACTION_NEEDED) echo "selected='selected' ";
        echo "value='{$_SERVER['PHP_SELF']}?user={$user}&amp;type={$type}&amp;queue=".QUEUE_ACTION_NEEDED."'>{$strActionNeeded}</option>\n";
        echo "<option ";
        if ($queue == QUEUE_WAITING) echo "selected='selected' ";
        echo "value='{$_SERVER['PHP_SELF']}?user={$user}&amp;type={$type}&amp;queue=".QUEUE_WAITING."'>{$strWaiting}</option>\n";
        echo "<option ";
        if ($queue == QUEUE_ALL_OPEN) echo "selected='selected' ";
        echo "value='{$_SERVER['PHP_SELF']}?user={$user}&amp;type={$type}&amp;queue=".QUEUE_ALL_OPEN."'>{$strAllOpen}</option>\n";
        if ($user != 'all')
        {
            echo "<option ";
            if ($queue == QUEUE_ALL_CLOSED) echo "selected='selected' ";
            echo "value='{$_SERVER['PHP_SELF']}?user={$user}&amp;type={$type}&amp;queue=".QUEUE_ALL_CLOSED."'>{$strAllClosed}</option>\n";
        }
        echo "</select>\n";
        echo "</form>";

        if ($queue == QUEUE_ALL_CLOSED AND $CONFIG['hide_closed_incidents_older_than'] != -1 AND $_GET['show'] != 'all')
        {
            echo "<p class='info'>".sprintf($strHidingIncidentsOlderThan, $CONFIG['hide_closed_incidents_older_than']);
            echo " - <a href='{$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']}&show=all'>{$strShowAll}</a></p>";
        }
        elseif ($queue == QUEUE_ALL_CLOSED AND $CONFIG['hide_closed_incidents_older_than'] != -1)
        {
            echo "<p class='info'>{$strShowingAllClosedIncidents} - ";
            echo "<a href='{$_SERVER['PHP_SELF']}?user={$user}&amp;type={$type}&amp;queue=".QUEUE_ALL_CLOSED."'>";
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

        if (($user == 'all') AND (count($rowcount) > 20)) echo "<p align='center'>".sprintf($strThereAreXIncidentsInThisList, $rowcount)."</p>";

        plugin_do('incidents_content_between_my_and_expertise'); // hack


        // *********************************************************
        // EXPERTISE QUEUE
        // ***
            // if ($user == 'current') $user = $sit[2];

        // Create SQL for chosen queue
        $sql = "{$selectsql} AND i.owner!='{$user}' AND towner!='{$user}' AND i.owner > 0 ";
        $sql .= "AND softwareid IN (SELECT softwareid FROM `{$dbUserSoftware}` WHERE userid='{$user}') ";

        if ($user != 'all')
        {

            switch ($queue)
            {
                case QUEUE_ACTION_NEEDED:
                    echo "<h2>{$strOtherIncidents}: <span class='actionqueue'>{$strActionNeeded}</span>".help_link("OtherIncidents")."</h2>\n";
                    $sql .= "AND (status!='2') ";  // not closed
                    // the "1=2" obviously false else expression is to prevent records from showing unless the IF condition is true
                    $sql .= "AND ((timeofnextaction > 0 AND timeofnextaction < {$now}) OR ";
                    $sql .= "(IF ((status >= 5 AND status <=8), ({$now} - lastupdated) > ({$CONFIG['regular_contact_days']} * 86400), 1=2 ) ";  // awaiting
                    $sql .= "OR IF (status='1' OR status='3' OR status='4', 1=1 , 1=2) ";  // active, research, left message - show all
                    $sql .= ") AND timeofnextaction < {$now} ) ";
                    // outstanding
                    break;
                case QUEUE_WAITING:
                    echo "<h2>{$strOtherIncidents}: <span class='waitingqueue'>{$strWaiting}</span></h2>\n";
                    $sql .= "AND ((status >= 4 AND status <= 8) OR (timeofnextaction > 0 AND timeofnextaction > {$now})) ";
                    break;
                case QUEUE_ALL_OPEN:
                    echo "<h2>{$strOtherIncidents}: <span class='openqueue'>{$strAllOpen}</span></h2>\n";
                    echo "</h2><hr /><br />";
                    $sql .= "AND status!='2' ";
                    break;
                case QUEUE_ALL_CLOSED:
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
                    trigger_error("Invalid queue ({$queue}) on query string", E_USER_NOTICE);
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
