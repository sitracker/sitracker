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
    var $managerid;
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
    var $theme;
    var $iconset;
    var $i18n;
    var $utc_offset;
    var $emoticons;
    var $startdate;
    var $language;
    var $show_next_action;

    // Legacy
    var $icq;
    var $aim;
    var $msn;
    var $skype;

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
        $sql = "SELECT u.*, r.rolename ";
        $sql .= "FROM `{$GLOBALS['dbUsers']}` AS u, `{$GLOBALS['dbRoles']}` AS r ";
        $sql .= "WHERE u.id = {$this->id} AND u.roleid = r.id";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

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
            $this->skype = $obj->skype;
            $this->phone = $obj->phone;
            $this->mobile = $obj->mobile;
            $this->fax = $obj->fax;
            $this->status = $obj->status;
            $this->message = $obj->message;
            $this->accepting = $obj->accepting;
            $this->startdate = $obj->user_startdate;
            $this->holiday_entitlement = $obj->holiday_entitlement;
            $this->holiday_resetdate = $obj->holiday_resetdate;
            $this->qualifications = $obj->qualifications;
            $this->source = $obj->user_source;
            $this->managerid = $obj->managerid;

            $sql_userconfig = "SELECT config, value FROM `{$GLOBALS['dbUserConfig']}` WHERE userid = {$this->id}";
            $result_userconfig = mysql_query($sql_userconfig);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

            while ($obj_userconfig = mysql_fetch_object($result_userconfig))
            {
                switch ($obj_userconfig->config)
                {
                    case 'show_emoticons': $this->emoticons = $obj_userconfig->value;
                        break;
                    case 'utc_offset': $this->utc_offset = $obj_userconfig->value;
                        break;
                    case 'language': $this->i18n = $obj_userconfig->value;
                        break;
                    case 'incident_refresh': $this->incident_refresh = $obj_userconfig->value;
                        break;
                    case 'incident_log_order': $this->update_order = $obj_userconfig->value;
                        break;
                    case 'updates_per_page': $this->num_updates_view = $obj_userconfig->value;
                        break;
                    case 'show_next_action': $this->show_next_action = $obj_userconfig->value;
                        break;
                    case 'iconset': $this->iconset = $obj_userconfig->value;
                        break;
                    case 'theme': $this->theme = $obj_userconfig->value;
                        break;
                }
            }
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

        $sql = "SELECT * FROM `{$GLOBALS['dbUsers']}` WHERE username = '".cleanvar($this->username)."'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($result) != 0)
        {
            // Already exists
            trigger_error($GLOBALS['strUsernameNotUnique'] . " Username: '{$this->username}'", E_USER_ERROR);
            $toReturn = FALSE;
        }
        else
        {
            // Insert
            $sql = "INSERT INTO `{$GLOBALS['dbUsers']}` (username, password, realname, roleid, ";
            $sql .= "groupid, title, email, phone, mobile, fax, status, ";
            $sql .= "holiday_entitlement, user_startdate, lastseen, managerid, user_source) ";
            $sql .= "VALUES ('".cleanvar($this->username)."', MD5('".cleanvar($this->password)."'), '".cleanvar($this->realname)."', '".cleanvar($this->roleid)."', ";
            $sql .= "'".cleanvar($this->group->id)."', '".cleanvar($this->jobtitle)."', '".cleanvar($this->email)."', '".cleanvar($this->phone)."', '".cleanvar($this->mobile)."', '".cleanvar($this->fax)."', ";
            $sql .= "".cleanvar($this->status).", ";
            $sql .= "'".cleanvar($this->holiday_entitlement)."', '".cleanvar($this->startdate)."', 0, ".convert_string_null_safe($this->managerd).", '".cleanvar($this->source)."')";
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
                $t = new TriggerEvent('TRIGGER_NEW_USER', array('userid' => $toReturn));
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

        if (!empty($this->id) AND is_numeric($this->id))
        {
            $sql = "SELECT username, status, accepting FROM `{$GLOBALS['dbUsers']}` WHERE id = {$this->id}";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

            if (mysql_num_rows($result) == 1)
            {
                // Exists
                $oldUser = mysql_fetch_object($result);
                $s = array();

                $errors = 0;
                $error_string = '';

                if (!empty($this->password)) $s[] = "password = MD5('".cleanvar($this->password)."')";
                if (!empty($this->realname)) $s[] = "realname = '".cleanvar($this->realname)."'";
                if (!empty($this->roleid)) $s[] = "roleid = ".cleanvar($this->roleid)."";
                if (!empty($this->group) AND !empty($this->group->id)) $s[] = "groupid = ".cleanvar($this->group->id)."";
                if (!empty($this->managerid)) $s[] = "managerid = ".convert_string_null_safe($this->managerid);
                if (!empty($this->jobtitle)) $s[] = "title = '".cleanvar($this->jobtitle)."'";
                if (!empty($this->signature)) $s[] = "signature = '".cleanvar($this->signature)."'";
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
                if (!empty($this->phone)) $s[] = "phone = '".cleanvar($this->phone)."'";
                if (!empty($this->mobile)) $s[] = "mobile = '".cleanvar($this->mobile)."'";
                if (!empty($this->fax)) $s[] = "fax = '".cleanvar($this->fax)."'";
                if (!empty($this->status))
                {
                    if ($oldUser->status != $this->status)
                    {
                        // reassign the users incidents if appropriate
                        if (empty($this->accepting)) $this->accepting = $oldUser->accepting; // Set accepting to the DB level if one isn't set'
                        incident_backup_switchover($this->id, $this->accepting);
                    }
                    $s[] = "status = {$this->status}";
                }
                if (!empty($this->message)) $s[] = "message = '".cleanvar($this->message)."'";
                if (is_bool($this->accepting))
                {
                    if ($this->accepting) $s[] = "accepting = 'Yes'";
                    else $s[] = "accepting = 'No'";
                }
                if (!empty($this->holiday_entitlement)) $s[] = "holiday_entitlement = ".cleanvar($this->holiday_entitlement)."";
                if (!empty($this->holiday_resetdate)) $s[] = "holiday_restdate = '".cleanvar($this->holiday_resetdate)."'";
                if (!empty($this->qualifications)) $s[] = "qualifications = '".cleanvar($this->qualifications)."'";
                if (!empty($this->startdate)) $s[] = "user_startdate = '".cleanvar($this->startdate)."'";
                if (!empty($this->icq)) $s[] = "icq = '".cleanvar($this->icq)."'";
                if (!empty($this->aim)) $s[] = "aim = '".cleanvar($this->aim)."'";
                if (!empty($this->msn)) $s[] = "msn = '".cleanvar($this->msn)."'";
                if (!empty($this->skype)) $s[] = "skype = '".cleanvar($this->skype)."'";

                $userconfig = array();
                if (!empty($this->incident_refresh) OR $this->incident_refresh === 0) $userconfig[] = array("config" => "incident_refresh", "value" => $this->incident_refresh);
                if (!empty($this->update_order)) $userconfig[] = array("config" => "incident_log_order", "value" => $this->update_order);
                if (!empty($this->num_updates_view)) $userconfig[] = array("config" => "updates_per_page", "value" => $this->num_updates_view);
                if (!empty($this->theme)) $userconfig[] = array("config" => "theme", "value" => $this->theme);
                if (!empty($this->iconset)) $userconfig[] = array("config" => "iconset", "value" => $this->iconset);
                if (!empty($this->i18n)) $userconfig[] = array("config" => "language", "value" => $this->i18n);
                if (!empty($this->utc_offset) OR $this->utc_offset === 0) $userconfig[] = array("config" => "utc_offset", "value" => $this->utc_offset);
                if (!empty($this->emoticons)) $userconfig[] = array("config" => "show_emoticons", "value" => $this->emoticons);

                if ($errors == 0)
                {
                    $sql = "UPDATE `{$GLOBALS['dbUsers']}` SET ".implode(", ", $s)." WHERE id = {$this->id}";
                    $result = mysql_query($sql);
                    if (mysql_error())
                    {
                        trigger_error(mysql_error(), E_USER_WARNING);
                        $toReturn = FALSE;
                    }
                    else
                    {
                        $toReturn = TRUE;
                    }

                    foreach ($userconfig AS $u)
                    {
                        $sql = "INSERT INTO `{$GLOBALS['dbUserConfig']}` VALUES ({$this->id}, '{$u['config']}', '{$u['value']}') ON DUPLICATE KEY UPDATE value = '{$u['value']}'";
                        $result = mysql_query($sql);
                        if (mysql_error())
                        {
                            trigger_error(mysql_error(), E_USER_WARNING);
                            $toReturn = FALSE;
                        }
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
                $sql = "SELECT status FROM `{$GLOBALS['dbUsers']}` WHERE id = {$this->id} AND status = 0 ";
                $result = mysql_query($result);
                if (mysql_num_rows($result) == 0)
                {
                    trigger_error("Failed to disable user {$this->username}", E_USER_WARNING);
                    $toReturn = FALSE;
                }
                else
                {
                    // Already disabled
                    $toReturn = TRUE;
                }
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
        // trigger_error("User.getSOAPArray() not yet implemented");
        $a = array('userid' => $this->id,
                      'realname' => $this->realname);
        debug_log("A:".$a);
        return $a;
    }
}

?>
