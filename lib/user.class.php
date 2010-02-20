<?php
// user.class.php - The user class for SiT
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney <paul[at]sitracker.org>


// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


/**
 * Represents a user adding the additional details possible for a user
 * @author Paul Heaney
 */
class User extends Person{
    var $realname;
    var $roleid;
    var $rolename;
    var $group;
    var $signature;
    var $status;
    var $message;
    var $accepting;
    var $holiday_entitlement;
    var $holiday_resetdate;
    var $qualifications;
    var $incident_refresh;
    var $update_order;
    var $num_updates_view;
    var $style;
    var $hide_auto_updates;
    var $hideheader;
    var $monitor;
    var $i18n;
    var $utc_offset;
    var $emoticons;
    var $startdate;

    // Legacy
    var $icq;
    var $aim;
    var $msn;

    function User($id=0)
    {
        if ($id > 0)
        {
            $this->id = $id;
            $this->retrieveDetails();
        }
    }

    function retrieveDetails()
    {
        trigger_error("User.retrieveDetails() not yet implemented");
        $sql = "SELECT u.*, r.rolename ";
        $sql .= "FROM `{$GLOBALS['dbUsers']}` AS u, `{$GLOBALS['dbRoles']}` AS r ";
        $sql .= "WHERE u.id = {$this->id} AND u.roleid = r.id";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

        if (mysql_num_rows($result) == 1)
        {
            $obj = mysql_fetch_object($result);
            $this->username = $obj->username;
            $this->realname = $obj->realname;
            $this->roleid = $obj->roleid;
            $this->rolename = $obj->rolename;
            $this->group = new Group($obj->groupid);
            $this->jobtitle = $obj->title;
            $this->signature = $obj->signature;
            $this->email = $obj->email;
            $this->icq = $obj->icq;
            $this->aim = $obj->aim;
            $this->msn = $obj->msn;
            $this->phone = $obj->phone;
            $this->mobile = $obj->mobile;
            $this->fax = $obj->fax;
            $this->status = $obj->status;
            $this->message = $obj->message;
            $this->accepting = $obj->accepting;
            $this->startdate = $obj->user_startdate;
            $this->incident_refresh = $obj->var_incident_refresh;
            $this->update_order = $obj->var_update_order;
            $this->num_updates_view = $obj->var_num_updates_view;
            $this->style = $obj->var_style;
            $this->hide_auto_updates = $obj->var_hideautoupdates;
            $this->hideheader = $obj->var_hideheader;
            $this->monitor = $obj->var_monitor;
            $this->i18n = $obj->var_i18n;
            $this->utc_offset = $obj->var_utc_offset;
            $this->emoticons = $obj->var_emoticons;
            $this->holiday_entitlement = $obj->holiday_entitlement;
            $this->holiday_resetdate = $obj->holiday_resetdate;
            $this->qualifications = $obj->qualifications;
            $this->source = $obj->user_source;
        }
        else
        {
        	$this->id = 0;
        }
    }
    
    
    /**
     * Adds a user to SiT! this performs a number of checks to ensure uniqueness and mandertory details are present
     *
     * @return mixed int for user ID if sucessful else FALSE
     * @author Paul Heaney
     */
    function add()
    {
        global $CONFIG, $now;

        $this->style = $CONFIG['default_interface_style'];
        $this->startdate = $now;
        if (empty($this->source)) $this->source = 'sit';

        if (empty($this->password)) $this->password = generate_password(16);

        $toReturn = FALSE;

        $sql = "SELECT * FROM `{$GLOBALS['dbUsers']}` WHERE username = '{$this->username}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

        if (mysql_num_rows($result) != 0)
        {
            // Already exists
            trigger_error($GLOBALS['strUsernameNotUnique'], E_USER_ERROR);
            $toReturn = FALSE;
        }
        else
        {
            // Insert
            $sql = "INSERT INTO `{$GLOBALS['dbUsers']}` (username, password, realname, roleid, ";
            $sql .= "groupid, title, email, phone, mobile, fax, status, var_style, ";
            $sql .= "holiday_entitlement, user_startdate, lastseen, user_source) ";
            $sql .= "VALUES ('{$this->username}', MD5('{$this->password}'), '{$this->realname}', '{$this->roleid}', ";
            $sql .= "'{$this->group->id}', '{$this->jobtitle}', '{$this->email}', '{$this->phone}', '{$this->mobile}', '{$this->fax}', ";
            $sql .= "{$this->status}, '{$this->style}', ";
            $sql .= "'{$this->holiday_entitlement}', '{$this->startdate}', 0, '{$this->source}')";
            $result = mysql_query($sql);
            if (mysql_error())
            {
                trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                $toReturn = false;
            }
            $toReturn = mysql_insert_id();

            if ($toReturn != FALSE)
            {
                // Create permissions (set to none)
                $sql = "SELECT * FROM `{$GLOBALS['dbPermissions']}`";
                $result = mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                while ($perm = mysql_fetch_object($result))
                {
                    $psql = "INSERT INTO `{$GLOBALS['dbUserPermissions']}` (userid, permissionid, granted) ";
                    $psql .= "VALUES ('{$toReturn}', '{$perm->id}', 'false')";
                    mysql_query($psql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                }

                setup_user_triggers($toReturn);
                trigger('TRIGGER_NEW_USER', array('userid' => $toReturn));
            }
        }

        return $toReturn;
    }


    /**
     * Updates the details of a user within SiT!
     * @author Paul Heaney
     * @return mixed True if updated sucessfully, String if data validity errors encountered,  FALSE otherwise
     */
    function edit()
    {
        global $now;
        $toReturn = false;

        if (!empty($this->id) AND is_number($this>id))
        {
            $sql = "SELECT username, status, accepting FROM `{$GLOBALS['dbUsers']}` WHERE id = {$this->id}";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

            if (mysql_num_rows($result) == 1)
            {
                // Exists
                $oldUser = mysql_fetch_object($result); 
                $s = array();
                $s[] = "lastseen = NOW()";

                $errors = 0;
                $error_string = '';

                if (!empty($this->password)) $s[] = "password = MD5('{$this->password}')";
                if (!empty($this->realname)) $s[] = "realname = '{$this->realname}'";
                if (!empty($this->roleid)) $s[] = "roleid = {$this->roleid}";
                if (!empty($this->group) AND !empty($this->group->id)) $s[] = "groupid = {$this->group->id}";
                if (!empty($this->jobtitle)) $s[] = "title = '{$this->jobtitle}'";
                if (!empty($this->signature)) $s[] = "signature = '{$this->signature}'";
                if (!empty($this->email))
                {
                    $sql = "SELECT COUNT(id) FROM `{$GLOBALS['dbUsers']}` WHERE status > 0 AND email='{$this->email}' AND id != {$this->id}";
                    $result = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    list($countexisting) = mysql_fetch_row($result);
                    if ($countexisting > 1)
                    {
                        $errors++;
                        $error_string .= "<h5 class='error'>{$GLOBALS['strEmailMustBeUnique']}</h5>\n";
                    }
                    $s[] = "email = '{$this->email}'";	
                }
                if (!empty($this->phone)) $s[] = "phone = '{$this->phone}'";
                if (!empty($this->mobile)) $s[] = "mobile = '{$this->mobile}'";
                if (!empty($this->fax)) $s[] = "fax = '{$this->fax}'";
                if (!empty($this->status))
                {
                    if ($oldUser->status != $this->status)
                    {
                        // reassign the users incidents if appropriate
                        if (empty($this->accepting)) $this->accepting = $oldUser->accepting; // Set accepting to the DB level if one isn't set'
                        incident_backup_switchover($this->id, $ths->accepting);
                    }
                    $s[] = "status = {$this->status}";
                }
                if (!empty($this->message)) $s[] = "message = '{$this->message}'";
                if (is_bool($this->accepting))
                {
                    if ($this->accepting) $s[] = "accepting = 'Yes'";
                    else $s[] = "accepting = 'No'";
                }
                if (!empty($this->holiday_entitlement)) $s[] = "holiday_entitlement = {$this->holiday_entitlement}";
                if (!empty($this->holiday_resetdate)) $s[] = "holiday_restdate = '{$this->holiday_resetdate}'";
                if (!empty($this->qualifications)) $s[] = "qualifications = '{$this->qualifications}'";
                if (!empty($this->incident_refresh) OR $this->incident_refresh === 0) $s[] = "var_incident_refresh = {$this->incident_refresh}";
                if (!empty($this->update_order)) $s[] = "var_update_order = '{$this->update_order}'";
                if (!empty($this->num_updates_view)) $s[] = "var_num_updates_view = {$this->num_updates_view}";
                if (!empty($this->style)) $s[] = "var_style = {$this->style}";
                if (!empty($this->hide_auto_updates)) $s[] = "var_hideautoupdates = '{$this->hide_auto_updates}'";
                if (!empty($this->hideheader)) $s[] = "var_hideheader = '{$this->hideheader}'";
                if (!empty($this->monitor)) $s[] = "var_monitor = '{$this->monitor}'";
                if (!empty($this->i18n)) $s[] = "var_i18n = '{$this->i18n}'";
                if (!empty($this->utc_offset) OR $this->utc_offset === 0) $s[] = "var_utc_offset = {$this->utc_offset}";
                if (!empty($this->emoticons)) $s[] = "var_emoticons = '{$this->emoticons}'";
                if (!empty($this->startdate)) $s[] = "user_startdate = '{$this->startdate}'";
                if (!empty($this->icq)) $s[] = "icq = '{$this->icq}'";
                if (!empty($this->aim)) $s[] = "aim = '{$this->aim}'";
                if (!empty($this->msn)) $s[] = "msn = '{$this->msn}'";

                if ($errors == 0)
                {
                    $sql = "UPDATE `{$GLOBALS['dbUsers']}` SET ".implode(", ", $s)." WHERE id = {$this->id}";
                    $result = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    if (mysql_affected_rows() != 1)
                    {
                        trigger_error("Failed to update user", E_USER_WARNING);
                        $toReturn = FALSE;
                    }
                    else
                    {
                        $toReturn = TRUE;
                    }
                }
                else
                {
                	$toReturn = $error_string;
                }
            }
            else
            {
                $toReturn = FALSE;
            }
        }

        return $toReturn;
    }


    /**
     * Disabled this user in SiT!
     * @author Paul Heaney
     * @return bool
     * @retval TRUE user disabled
     * @retval FALSE user not disabled
     */
    function disable()
    {
        $toReturn = true;
        if (!empty($this->id) AND $this->status != 0)
        {
            $sql = "UPDATE `{$GLOBALS['dbUsers']}` SET status = 0 WHERE id = {$this->id}";

            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            if (mysql_affected_rows() != 1)
            {
                trigger_error("Failed to disable user {$this->username}", E_USER_WARNING);
                $toReturn = FALSE;
            }
            else
            {
                $toReturn = TRUE;
            }
        }

        return $toReturn;
    }
    
    
    function getSOAPArray()
    {
        trigger_error("User.getSOAPArray() not yet implemented");
    }
}

?>