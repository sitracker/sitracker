<?php
// dashboard_user_incidents.php - List of users active incidents
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

$dashboard_user_incidents_version = 1;

function dashboard_user_incidents($dashletid)
{
    $title = sprintf($GLOBALS['strUserIncidents'], user_realname($_SESSION['userid'], TRUE));
    //"({$GLOBALS['strActionNeeded']})";

    echo dashlet('user_incidents', $dashletid, icon('support', 16), $title, 'incidents.php?user=current&amp;queue=1&amp;type=support', $content);

}


function dashboard_user_incidents_display($dashletid)
{
    global $user;
    global $sit;
    global $now;
    global $GLOBALS;
    global $CONFIG;
    global $iconset;
    global $dbIncidents, $dbContacts, $dbPriority;
    $user = "current";

    // Create SQL for chosen queue
    // If you alter this SQL also update the function user_activeincidents($id)
    if ($user == 'current') $user = $sit[2];
    // If the user is passed as a username lookup the userid
    if (!is_number($user) AND $user != 'current' AND $user != 'all')
    {
        $usql = "SELECT id FROM `{$dbUsers}` WHERE username='{$user}' LIMIT 1";
        $uresult = mysql_query($usql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        if (mysql_num_rows($uresult) >= 1) list($user) = mysql_fetch_row($uresult);
        else $user = $sit[2]; // force to current user if username not found
    }
    $sql =  "WHERE i.contact = c.id AND i.priority = p.id ";
    if ($user != 'all') $sql .= "AND (owner='{$user}' OR towner='{$user}') ";


    $queue = 1; //we still need this for the included page so the incidents are coloured correctly
    //the only case we're really interested in
    $sql .= "AND (status!='2') ";  // not closed
    // the "1=2" obviously false else expression is to prevent records from showing unless the IF condition is true
    $sql .= "AND ((timeofnextaction > 0 AND timeofnextaction < $now) OR ";
    $sql .= "(IF ((status >= 5 AND status <=8), ($now - lastupdated) > ({$CONFIG['regular_contact_days']} * 86400), 1=2 ) ";  // awaiting
    $sql .= "OR IF (status='1' OR status='3' OR status='4', 1=1 , 1=2) ";  // active, research, left message - show all
    $sql .= ") AND timeofnextaction < {$now} ) ";

    $selectsql = "SELECT i.id, externalid, title, owner, towner, priority, status, siteid, forenames, surname, email, i.maintenanceid, ";
    $selectsql .= "servicelevel, softwareid, lastupdated, timeofnextaction, ";
    $selectsql .= "(timeofnextaction - {$now}) AS timetonextaction, opened, ({$now} - opened) AS duration, closed, (closed - opened) AS duration_closed, type, ";
    $selectsql .= "($now - lastupdated) AS timesincelastupdate ";
    $selectsql .= "FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c, `{$dbPriority}` AS p ";
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
    $sql = $selectsql.$sql;
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

    if ($user == 'all')
    {
        //echo "<p align='center'>There are <strong>{$rowcount}</strong> incidents in this list.</p>";
        echo "<p align='center'>".sprintf($strThereAreXIncidentsInThisList, $rowcount)."</p>";
    }
    $mode = "min";
    // Print message if no incidents were listed
    if (mysql_num_rows($result) >= 1)
    {
        // Incidents Table
        $incidents_minimal = true;
        //include ('incidents_table.inc.php');
        $shade='shade1';
        echo "<table summary=\"{$strIncidents}\">";
        while ($obj = mysql_fetch_object($result))
        {
            list($update_userid, $update_type, $update_currentowner, $update_currentstatus, $update_body, $update_timestamp, $update_nextaction, $update_id) = incident_lastupdate($obj->id);
            $update_body = parse_updatebody($update_body);
            echo "<tr><td class='{$shade}'>";
            if ($_SESSION['userconfig']['incident_popup_onewindow'] == 'FALSE')
            {
                $windowname = "incident{$obj->id}";
            }
            else
            {
                $windowname = "sit_popup";
            }
            $tooltip = '';
            if (!empty($update_body) AND $update_body != '...')
            {
                $tooltip = $update_body;
            }
            echo html_incident_popup_link($obj->id, "{$obj->id} - {$obj->title} {$GLOBALS['strFor']} {$obj->forenames}   {$obj->surname}", $tooltip);
            echo "</td></tr>\n";
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        echo "</table>";
    }
    else
    {
        echo "<p align='center'>{$GLOBALS['strNoRecords']}</p>";
    }

}
function dashboard_user_incidents_get_version()
{
    global $dashboard_user_incidents_version;
    return $dashboard_user_incidents_version;
}

?>
