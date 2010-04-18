<?php
// user.inc.php - functions relating to users / user profiles
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
 * Returns TRUE or FALSE to indicate whether a given user has a given permission
 * @author Ivan Lucas
 * @param int $userid. The userid to check
 * @param int $permission or array. The permission id to check, or an array of id's to check
 * @return boolean. TRUE if the user has the permission (or all the permissions in the array), otherwise FALSE
 */
function user_permission($userid,$permission)
{
    // Default is no access
    $accessgranted = FALSE;

    if (!is_array($permission))
    {
        $permission = array($permission);
    }

    foreach ($permission AS $perm)
    {
        if (@in_array($perm, $_SESSION['permissions']) == TRUE) $accessgranted = TRUE;
        else $accessgranted = FALSE;
        // Permission 0 is always TRUE (general acess)
        if ($perm == 0) $accessgranted = TRUE;
    }
    return $accessgranted;
}


/**
 * Returns an integer representing the id of the user identified by his/her username and password
 * @author Ivan Lucas
 * @param string $username. A username
 * @param string $password. An MD5 hashed password
 * @return integer. the users ID or 0 if the user does not exist (username/password did not match)
 * @retval int 0 The user did not exist
 * @retval int >=1 The userid of the matching user
 * @note Returns 0 if the given user does not exist
 */
function user_id($username, $password)
{
    global $dbUsers;
    $sql  = "SELECT id FROM `{$dbUsers}` ";
    $sql .= "WHERE username='$username' AND password='{$password}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) == 0)
    {
        $userid= 0;
    }
    else
    {
        $user = mysql_fetch_object($result);
        $userid = $user->id;
    }
    return $userid;
}


/**
 * Return a users password
 * @author Ivan Lucas
 * @param int $id User ID
 * @return string
 * @note this is an MD5 hash
 */
function user_password($id)
{
    global $dbUsers;
    return db_read_column('password', $dbUsers, $id);
}


/**
 * Return a users real name
 * @author Ivan Lucas
 * @param int $id. A user ID
 * @param bool $allowhtml. may return HTML if TRUE, only ever returns plain text if FALSE
 * @return string
 * @note If $allowhtml is TRUE disabled user accounts are returned as HTML with span class 'deleted'
 */
function user_realname($id, $allowhtml = FALSE)
{
    global $update_body;
    global $incidents;
    global $CONFIG;
    global $dbUsers, $dbEscalationPaths;
    if ($id >= 1)
    {
        if ($id == $_SESSION['userid'])
        {
            return $_SESSION['realname'];
        }
        else
        {
            $sql = "SELECT realname, status FROM `{$dbUsers}` WHERE id='{$id}' LIMIT 1";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            list($realname, $status) = mysql_fetch_row($result);
            if ($allowhtml == FALSE OR $status > 0)
            {
                return $realname;
            }
            else
            {
                return "<span class='deleted'>{$realname}</span>";
            }
        }
    }
    elseif (!empty($incidents['email']))
    {
        // TODO this code does not belong here
        // The SQL is also looking at all escalation paths not just the relevant
        // one.
        //an an incident
        preg_match('/From:[ A-Za-z@\.]*/', $update_body, $from);
        if (!empty($from))
        {
            $frommail = strtolower(substr(strstr($from[0], '@'), 1));
            $customerdomain = strtolower(substr(strstr($incidents['email'], '@'), 1));

            if ($frommail == $customerdomain) return $GLOBALS['strCustomer'];

            $sql = "SELECT name, email_domain FROM `{$dbEscalationPaths}`";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            while ($escpath = mysql_fetch_object($result))
            {
                if (!empty($escpath->email_domain))
                {
                    if (strstr(strtolower($frommail), strtolower($escpath->email_domain)))
                    {
                        return $escpath->name;
                    }
                }
            }
        }
    }

    //Got this far not returned anything so
    return $CONFIG['application_shortname']; // No from email address
}


/**
 * Return a users email address
 * @author Ivan Lucas
 * @param id int. User ID
 * @note Obtained from session if possible
 */
function user_email($id)
{
    global $dbUsers;
    if ($id == $_SESSION['userid'])
    {
        return $_SESSION['email'];
    }
    else
    {
        return db_read_column('email', $dbUsers, $id);
    }
}


/**
 * Return a users phone number
 * @author Ivan Lucas
 * @param id int. User ID
 */
function user_phone($id)
{
    return db_read_column('phone', $GLOBALS['dbUsers'], $id);
}


/**
 * Return a users mobile phone number
 * @author Ivan Lucas
 * @param id int. User ID
 */
function user_mobile($id)
{
    return db_read_column('mobile', $GLOBALS['dbUsers'], $id);
}


/**
 * Return a users email signature
 * @author Ivan Lucas
 * @param id int. User ID
 */
function user_signature($id)
{
    return db_read_column('signature', $GLOBALS['dbUsers'], $id);
}


/**
 * Return a users away message
 * @author Ivan Lucas
 * @param id int. User ID
 */
function user_message($id)
{
    return db_read_column('message', $GLOBALS['dbUsers'], $id);
}


/**
 * Return a users current away status
 * @author Ivan Lucas
 * @param id int. User ID
 * @note 0 means user account disabled
 */
function user_status($id)
{
    return db_read_column('status', $GLOBALS['dbUsers'], $id);
}


/**
 * Check whether the given user is accepting
 * @author Ivan Lucas
 * @param int $id The userid of the user to check
 * @return string
 * @retval 'Yes' User is accepting
 * @retval 'No' User is not accepting
 * @retval 'NoSuchUser' The given user does not exist
 */
function user_accepting($id)
{
    $accepting = db_read_column('accepting', $GLOBALS['dbUsers'], $id);
    if ($accepting == '')  $accepting = "NoSuchUser";

    return $accepting;
}


/**
 * Count the number of active incidents for a given user
 * @author Ivan Lucas
 * @param int $id The userid of the user to check
 * @return int
 */
function user_activeincidents($userid)
{
    global $CONFIG, $now, $dbIncidents, $dbContacts, $dbPriority;
    $count = 0;

    // This SQL must match the SQL in incidents.php
    $sql = "SELECT COUNT(i.id)  ";
    $sql .= "FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c, `{$dbPriority}` AS pr WHERE contact = c.id AND i.priority = pr.id ";
    $sql .= "AND (owner='{$userid}' OR towner='{$userid}') ";
    $sql .= "AND (status!='2') ";  // not closed
    // the "1=2" obviously false else expression is to prevent records from showing unless the IF condition is true
    $sql .= "AND ((timeofnextaction > 0 AND timeofnextaction < $now) OR ";
    $sql .= "(IF ((status >= 5 AND status <=8), ($now - lastupdated) > ({$CONFIG['regular_contact_days']} * 86400), 1=2 ) ";  // awaiting
    $sql .= "OR IF (status='1' OR status='3' OR status='4', 1=1 , 1=2) ";  // active, research, left message - show all
    $sql .= ") AND timeofnextaction < $now ) ";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    else list($count) = mysql_fetch_row($result);

    return ($count);
}


/**
 * Counts a users open incidents
 * @author Ivan Lucas
 * @param int $id The userid of the user to check
 * @return int
 * @note This number will never match the number shown in the active queue and is not meant to
 */
function user_countincidents($id)
{
    global $dbIncidents;
    $count = 0;

    $sql = "SELECT COUNT(id) FROM `{$dbIncidents}` WHERE (owner='{$id}' OR towner='{$id}') AND (status!=2)";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    else list($count) = mysql_fetch_row($result);

    return ($count);
}


/**
 * Counts number of incidents and priorty for a given user
 * @author Ivan Lucas
 * @param int $id The userid of the user to check
 * @return array
 */
function user_incidents($id)
{
    global $dbIncidents;
    $sql = "SELECT priority, count(priority) AS num FROM `{$dbIncidents}` ";
    $sql .= "WHERE (owner = $id OR towner = $id) AND status != 2 ";
    $sql .= "GROUP BY priority";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    $arr = array('1' => '0', '2' => '0', '3' => '0', '4' => '0');

    if (mysql_num_rows($result) > 0)
    {
        while ($obj = mysql_fetch_object($result))
        {
            $arr[$obj->priority] = $obj->num;
        }
    }
    return $arr;
}


/**
 * gets users holiday information for a certain day given an optional type
 * and optional length returns both type and length and approved as an array
 * @author Ivan Lucas
 * @param int $userid. The userid of the holiday to retrieve
 * @param int $type. The holiday type. e.g. sickness
 * @param int $year. Year. eg. 2008
 * @param int $month. Month. eg. 11 = November
 * @param int $day. Day
 * @param string $length. 'am', 'pm', 'day' or FALSE to list all
 * @return array
 */
function user_holiday($userid, $type= 0, $year, $month, $day, $length = FALSE)
{
    global $dbHolidays;
    $sql = "SELECT * FROM `{$dbHolidays}` WHERE `date` = '{$year}-{$month}-{$day}' ";
    if ($type !=0 )
    {
        $sql .= "AND (type='$type' OR type='".HOL_PUBLIC."' OR type='".HOL_FREE."') ";
        $sql .= "AND IF(type!=".HOL_PUBLIC.", userid='$userid', 1=1) ";
    }
    else
    {
        $sql .= " AND userid='$userid' ";
    }

    if ($length != FALSE)
    {
        $sql .= "AND length='$length' ";
    }
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) == 0)
    {
        return FALSE;
    }
    else
    {
        $totallength = 0;
        while ($holiday = mysql_fetch_object($result))
        {
            $type = $holiday->type;
            $length = $holiday->length;
            $approved = $holiday->approved;
            $approvedby = $holiday->approvedby;
            // hmm... not sure these next lines are required.
            if ($length == 'am' && $totallength == 0) $totallength = 'am';
            if ($length == 'pm' && $totallength == 0) $totallength = 'pm';
            if ($length == 'am' && $totallength == 'pm') $totallength = 'day';
            if ($length == 'pm' && $totallength == 'am') $totallength = 'day';
            if ($length == 'day') $totallength = 'day';
        }
        return array($type, $totallength, $approved, $approvedby);
    }
}


/**
 * Count a users holidays of specified type
 * @author Ivan Lucas
 * @param integer $userid . User ID
 * @param integer $type. Holiday type
 * @param integer $date. (optional) UNIX timestamp. Only counts holidays before this date
 * @param array $approved (optional) An array of approval statuses to include
                   when ommitted a default is used
 * @return integer. Number of days holiday
 */
function user_count_holidays($userid, $type, $date=0,
                             $approved = array(HOL_APPROVAL_NONE, HOL_APPROVAL_GRANTED, HOL_APPROVAL_DENIED))
{
    global $dbHolidays;
    $sql = "SELECT id FROM `{$dbHolidays}` WHERE userid='$userid' ";
    $sql .= "AND type='$type' AND length='day' ";
    if ($date > 0) $sql .= "AND `date` < FROM_UNIXTIME({$date})";
    if (is_array($approved))
    {
        $sql .= "AND (";

        for ($i = 0; $i < sizeof($approved); $i++)
        {
            $sql .= "approved = {$approved[$i]} ";
            if ($i < sizeof($approved)-1) $sql .= "OR ";
        }

        $sql .= ") ";
    }
    else
    {
        $sql .= "AND (approved = ".HOL_APPROVAL_NONE." OR approved = ".HOL_APPROVAL_GRANTED.") ";
    }
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $full_days = mysql_num_rows($result);

    $sql = "SELECT id FROM `{$dbHolidays}` ";
    $sql .= "WHERE userid='{$userid}' AND type='{$type}' AND (length='pm' OR length='am') ";
    if (is_array($approved))
    {
        $sql .= "AND (";

        for ($i = 0; $i < sizeof($approved); $i++)
        {
            $sql .= "approved = {$approved[$i]} ";
            if ($i < sizeof($approved)-1) $sql .= "OR ";
        }

        $sql .= ") ";
    }
    else
    {
        $sql .= "AND (approved = ".HOL_APPROVAL_NONE." OR approved = ".HOL_APPROVAL_GRANTED.") ";
    }

    if ($date > 0)
    {
        $sql .= "AND `date` < {$date}";
    }

    debug_log($sql); // ###INL###

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $half_days = mysql_num_rows($result);

    $days_holiday = $full_days + ($half_days / 2);
    return $days_holiday;
}


/**
 * Return the users holiday entitlement
 * @author Ivan Lucas
 * @param integer $userid. User ID
 * @return integer. Number of days holiday a user is entitled to
 */
function user_holiday_entitlement($userid)
{
    return db_read_column('holiday_entitlement', $GLOBALS['dbUsers'], $userid);
}


/**
 * Return the users holiday entitlement reset/rollover date
 * @author Ivan Lucas
 * @param integer $userid. User ID
 * @return integer. UNIX Timestamp date
 */
function user_holiday_resetdate($userid)
{
    return mysql2date(db_read_column('holiday_resetdate', $GLOBALS['dbUsers'], $userid) . ' 17:00:00');
}


/**
 * Returns the HTML for a drop down list of  users, with the given name and with the given id selected.
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. User ID to pre-select
 * @param bool $accepting. when true displays the accepting status. hides it when false
 * @param int $exclude. User ID not to list
 * @param string $attribs. Extra attributes for the select control
 * @return string HTML
 */
function user_drop_down($name, $id, $accepting = TRUE, $exclude = FALSE, $attribs= '', $return = FALSE)
{
    // INL 1Jul03 Now only shows users with status > 0 (ie current users)
    // INL 2Nov04 Optional accepting field, to hide the status 'Not Accepting'
    // INL 19Jan05 Option exclude field to exclude a user, or an array of
    // users
    global $dbUsers;
    $sql  = "SELECT id, realname, accepting FROM `{$dbUsers}` WHERE status > 0 ORDER BY realname ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    $html .= "<select name='{$name}' id='{$name}' ";
    if (!empty($attribs))
    {
        $html .= " $attribs";
    }

    $html .= ">\n";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($users = mysql_fetch_object($result))
    {
        $show = TRUE;
        if ($exclude != FALSE)
        {
            if (is_array($exclude))
            {
                if (!in_array($users->id, $exclude)) $show = TRUE;
                else $show = FALSE;
            }
            else
            {
                if ($exclude!=$users->id) $show = TRUE;
                else $show = FALSE;
            }
        }
        if ($show == TRUE)
        {
            $html .= "<option ";
            if ($users->id == $id) $html .= "selected='selected' ";
            if ($users->accepting == 'No' AND $accepting == TRUE)
            {
                $html .= " class='expired' ";
            }

            $html .= "value='{$users->id}'>";
            $html .= "{$users->realname}";
            if ($users->accepting == 'No' AND $accepting == TRUE)
            {
                $html .= ", {$GLOBALS['strNotAccepting']}";
            }
            $html .= "</option>\n";
        }
    }
    $html .= "</select>\n";

    if ($return)
    {
        return $html;
    }
    else
    {
        echo $html;
    }
}


/**
 * @author Paul Heaney
 * @param int $userid - userid to find group for
 * @return int the groupid
 */
function user_group_id($userid)
{
    global $dbUsers;
    // get groupid
    $sql = "SELECT groupid FROM `{$dbUsers}` WHERE id='{$userid}' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    list($groupid) = mysql_fetch_row($result);
    return $groupid;
}


/**
 * An icon showing a users online status
 * @author Kieran Hogg
 * @param int $user The user ID of the user to check
 * @return string. HTML of a 16x16 status icon.
 */
function user_online_icon($user)
{
    global $iconset, $now, $dbUsers, $strOffline, $strOnline, $startofsession;
    $sql = "SELECT lastseen FROM `{$dbUsers}` WHERE id={$user}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $users = mysql_fetch_object($result);
    if (mysql2date($users->lastseen) > $startofsession)
    {
        return icon('online', 16, $strOnline);
    }
    else
    {
        return icon('offline', 16, $strOffline);
    }
}


/**
 * Returns users online status
 * @author Kieran Hogg
 * @param int $user The user ID of the user to check
 * @return boolean. TRUE if online, FALSE if not
 */
function user_online($user)
{
    global $iconset, $now, $dbUsers, $startofsession;
    $sql = "SELECT lastseen FROM `{$dbUsers}` WHERE id={$user}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $users = mysql_fetch_object($result);
    if (mysql2date($users->lastseen) > $startofsession)
    {
        return TRUE;
    }
    else
    {
        return FALSE;
    }
}


/**
 * Returns whether the user is accepting or not
 *
 * @param int $userid ID of the user
 * @return string 'accepting'|'not accepting'
 * @author Kieran Hogg
 */
function user_accepting_status($userid)
{
    if (user_accepting($userid) == 'Yes')
    {
        return 'accepting';
    }
    else
    {
        return 'not accepting';
    }
}

/**
 * Returns the status of a user
 *
 * @param int $userid ID of the user
 * @return string user status
 * @author Kieran Hogg
 */
function user_status_name($userid)
{
    $status = db_read_column('name', $GLOBALS['dbUserStatus'], $userid);
    return $GLOBALS[$status];
}


// Returns HTML for an icon to indicate priority
function priority_icon($id)
{
    global $CONFIG;
    switch ($id)
    {
        case 1:
            $html = "<img src='{$CONFIG['application_webpath']}images/low_priority.gif' width='10' height='16' alt='{$GLOBALS['strLowPriority']}' title='{$GLOBALS['strLowPriority']}' />";
            break;
        case 2:
            $html = "<img src='{$CONFIG['application_webpath']}images/med_priority.gif' width='10' height='16' alt='{$GLOBALS['strMediumPriority']}' title='{$GLOBALS['strMediumPriority']}' />";
            break;
        case 3:
            $html = "<img src='{$CONFIG['application_webpath']}images/high_priority.gif' width='10' height='16' alt='{$GLOBALS['strHighPriority']}' title='{$GLOBALS['strHighPriority']}' />";
            break;
        case 4:
            $html = "<img src='{$CONFIG['application_webpath']}images/crit_priority.gif' width='16' height='16' alt='{$GLOBALS['strCriticalPriority']}' title='{$GLOBALS['strCriticalPriority']}' />";
            break;
        default:
            $html = '?';
            break;
    }
    return $html;
}


/**
 * Returns a string representation of the user status
 * @param int $id The user status to get the string for
 * @return string User status name, empty string if status doesn't exit
 */
function userstatus_name($id)
{
    $status = db_read_column('name', $GLOBALS['dbUserStatus'], $id);
    return $GLOBALS[$status];
}


function holiday_type ($id)
{
    switch ($id)
    {
        case HOL_HOLIDAY:
            $holidaytype = $GLOBALS['strHoliday'];
            break;
        case HOL_SICKNESS:
            $holidaytype = $GLOBALS['strAbsentSick'];
            break;
        case HOL_WORKING_AWAY:
            $holidaytype = $GLOBALS['strWorkingAway'];
            break;
        case HOL_TRAINING:
            $holidaytype = $GLOBALS['strTraining'];
            break;
        case HOL_FREE:
            $holidaytype = $GLOBALS['strCompassionateLeave'];
            break;
        case HOL_PUBLIC:
            $holidaytype = $GLOBALS['strPublicHoliday'];
            break;
        default:
            $holidaytype = $GLOBALS['strUnknown'];
            break;
    }
    return ($holidaytype);
}


function holiday_approval_status($approvedid, $approvedby=-1)
{
    global $strApproved, $strApprovedFree, $strRequested, $strNotRequested, $strDenied;
    global $strArchivedDenied, $strArchivedNotRequested, $strArchivedRequested;
    global $strArchivedApproved, $strArchivedApprovedFree, $strApprovalStatusUnknown;

    // We add 10 to normal status when we archive holiday
    switch ($approvedid)
    {
        case -2:
            $status = $strNotRequested;
            break;
        case -1:
            $status = $strDenied;
            break;
        case 0:
            if ($approvedby == 0)
            {
                $status = $strNotRequested;
            }
            else
            {
                $status = $strRequested;
            }
            break;
        case 1:
            $status = $strApproved;
            break;
        case 2:
            $status = $strApprovedFree;
            break;
        case 8:
            $status = $strArchivedNotRequested;
            break;
        case 9:
            $status = $strArchivedDenied;
            break;
        case 10:
            $status = $strArchivedRequested;
            break;
        case 11:
            $status = $strArchivedApproved;
            break;
        case 12:
            $status = $strArchivedApprovedFree;
            break;
        default:
            $status = $strApprovalStatusUnknown;
            break;
    }
    return $status;
}


/**
 * check to see if any fellow group members have holiday on the date specified
 * @author Ivan Lucas
 * @param int $userid - user ID
 * @param int $date - UNIX Timestamp
 * @param string $length - 'day', 'pm' or 'am'
 * @return HTML space seperated list of users that are away on the date specified
 */
function check_group_holiday($userid, $date, $length='day')
{
    global $dbUsers, $dbHolidays;

    $namelist = '';
    $groupid = user_group_id($userid);
    if (!empty($groupid))
    {
        // list group members
        $msql = "SELECT id AS userid FROM `{$dbUsers}` ";
        $msql .= "WHERE groupid='{$groupid}' AND id != '$userid' ";
        $mresult = mysql_query($msql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        while ($member = mysql_fetch_object($mresult))
        {
            // check to see if this group member has holiday
            $hsql = "SELECT id FROM `{$dbHolidays}` WHERE userid='{$member->userid}' AND date = FROM_UNIXTIME({$date}) ";
            if ($length == 'am' OR $length == 'pm')
            {
                $hsql .= "AND (length = '{$length}' OR length = 'day') ";
            }

            $hresult = mysql_query($hsql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($hresult) >= 1)
            {
                $namelist .= user_realname($member->userid)." ($length)";
                $namelist .= "&nbsp;&nbsp;";
            }
        }
    }
    return $namelist;
}


/**
 * Checks is a given username is unique
 * @author Kieran Hogg
 * @param string $username - username
 * @return bool TRUE if valid, FALSE if not
 */
function valid_username($username)
{
    $username = cleanvar($username);
    $valid = TRUE;

    $tables = array('dbUsers', 'dbContacts');

    foreach ($tables AS $table)
    {
        $sql = "SELECT username FROM `{$GLOBALS[$table]}` WHERE username='{$username}'";
        if ($result = mysql_query($sql) AND mysql_num_rows($result) != 0)
        {
            $valid = FALSE;
        }
    }

    return $valid;
}


/**
 * Generates and returns a random alphanumeric password
 * @author Ivan Lucas
 * @note Some characters (0 and 1) are not used to avoid user confusion
 */
function generate_password($length=8)
{
    $possible = '0123456789'.'abcdefghijkmnpqrstuvwxyz'.'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.'-';
    // $possible = '23456789'.'abcdefghjkmnpqrstuvwxyz'.'ABCDEFGHJKLMNPQRSTUVWXYZ'.'-';
                // not using 1's 0's etc. to save confusion
                // '-=!&';
    $str = '';
    while (strlen($str) < $length)
    {
        $str .= substr($possible, (rand() % strlen($possible)),1);
    }
    return $str;
}


/**
 *
 * @author Ivan Lucas
 */
function software_backup_userid($userid, $softwareid)
{
    global $dbUserSoftware;
    $backupid = 0; // default
    // Find out who is the substitute for this user/skill
    $sql = "SELECT backupid FROM `{$dbUserSoftware}` WHERE userid = '{$userid}' AND softwareid = '{$softwareid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    list($backupid) = mysql_fetch_row($result);
    $backup1 = $backupid;

    // If that substitute is not accepting then try and find another
    if (empty($backupid) OR user_accepting($backupid) != 'Yes')
    {
        $sql = "SELECT backupid FROM `{$dbUserSoftware}` WHERE userid='{$backupid}' AND userid!='{$userid}' ";
        $sql .= "AND softwareid='{$softwareid}' AND backupid!='{$backup1}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        list($backupid) = mysql_fetch_row($result);
        $backup2 = $backupid;
    }

    // One more iteration, is the backup of the backup accepting?  If not try another
    if (empty($backupid) OR user_accepting($backupid)!='Yes')
    {
        $sql = "SELECT backupid FROM `{$dbUserSoftware}` WHERE userid='{$backupid}' AND userid!='{$userid}' ";
        $sql .= "AND softwareid='{$softwareid}' AND backupid!='{$backup1}' AND backupid!='{$backup2}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        list($backupid) = mysql_fetch_row($result);
    }
    return ($backupid);
}

/**
 *
 * @author Ivan Lucas
 * @param string $newstatus - Either a user status ID or 'Yes' or 'No'
 * @returns FALSE on failure, or HTML to display status
 * @note Toggles accepting status only when passed 'yes' or 'no'
*/
function set_user_status($newstatus)
{
    global $dbUsers;
    switch ($newstatus)
    {
        case USERSTATUS_IN_OFFICE:
            $accepting = 'Yes';
            break;
        case USERSTATUS_NOT_IN_OFFICE:
            $accepting = 'No';
            break;
        case USERSTATUS_IN_MEETING:
            // don't change
            $accepting = '';
            break;
        case USERSTATUS_AT_LUNCH:
            $accepting = '';
            break;
        case USERSTATUS_ON_HOLIDAY:
            $accepting = 'No';
            break;
        case USERSTATUS_WORKING_FROM_HOME:
            $accepting = 'Yes';
            break;
        case USERSTATUS_ON_TRAINING_COURSE:
            $accepting = 'No';
            break;
        case USERSTATUS_ABSENT_SICK:
            $accepting = 'No';
            break;
        case USERSTATUS_WORKING_AWAY:
            // don't change
            $accepting = '';
            break;
        case 'Yes':
        case 'yes':
            $accepting = 'Yes';
            $newstatus = '';
            break;
        case 'No':
        case 'no':
            $accepting = 'No';
            $newstatus = '';
            break;
    }
    $sql  = "UPDATE `{$dbUsers}` SET ";
    if (!empty($newstatus)) $sql .= "status='$newstatus'";
    if (!empty($newstatus) AND !empty($accepting)) $sql .= ', ';
    if (!empty($accepting)) $sql .= "accepting='$accepting'";
    $sql .= " WHERE id='{$_SESSION['userid']}' LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    debug_log("setting status: $sql");
    incident_backup_switchover($_SESSION['userid'], $accepting);

    $t = new TriggerEvent("TRIGGER_USER_CHANGED_STATUS", array('userid' => $_SESSION['userid']));

    return userstatus_summaryline($newstatus, $accepting);
}

/**
 * Return the current users status and accepting incidents statuses
 * for use in the header bar
 * @author Ivan Lucas
 * @param int $userstatus - User status ID
 * @param string $accepting - Accepting incidents 'Yes' or 'No'
 * @return string HTML
*/
function userstatus_summaryline($userstatus = '', $accepting = '')
{
    if ($userstatus == '')
    {
        $userstatus = user_status($_SESSION['userid']);
    }
    if ($accepting == '')
    {
        $accepting = user_accepting($_SESSION['userid']);
    }
    $html = "<span id='userstatus_summaryline'>";
    $html .= userstatus_name($userstatus);
    if ($accepting != 'Yes')
    {
        $html .= " | {$GLOBALS['strNotAcceptingIncidents']}";
    }
    $html .= "</span>";
    return $html;
}

?>
