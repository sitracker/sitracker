<?php
// user_new.php - Form for adding users
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

require ('core.php');
$permission = PERM_USER_ADD; // Add Users
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNewUser;

// External variables
$submit = cleanvar($_REQUEST['submit']);

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

if (empty($submit))
{
    // Show add user form
    $gsql = "SELECT * FROM `{$dbGroups}` ORDER BY name";
    $gresult = mysql_query($gsql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    while ($group = mysql_fetch_object($gresult))
    {
        $grouparr[$group->id] = $group->name;
    }

    $numgroups = count($grouparr);

    echo show_form_errors('new_user');
    clear_form_errors('new_user');

    echo "<h2>".icon('newuser', 32)." ";
    echo "{$strNewUser}</h2>";
    plugin_do('user_new');
    echo "<form id='adduser' action='{$_SERVER['PHP_SELF']}' method='post' ";
    echo "onsubmit='return confirm_action(\"{$strAreYouSureAdd}\");'>";
    echo "<table class='maintable vertical'>\n";
    echo "<tr><th>{$strRealName}</th>";
    echo "<td><input maxlength='50' name='realname' size='30' class='required' value='".show_form_value('new_user', 'realname', '')."'/> <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr><th>{$strUsername}</th>";
    echo "<td><input maxlength='50' name='username' size='30' class='required' value='".show_form_value('new_user', 'username', '')."' /> <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr id='password'><th>{$strPassword}</th>";
    echo "<td><input maxlength='50' name='password' size='30' type='password' class='required' value='".show_form_value('new_user', 'password', '')."' /> <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr><th>{$strGroup}</th>";
    echo "<td>".group_drop_down('groupid', show_form_value('new_user', 'groupid', 0))."</td>";
    echo "</tr>";
    echo "<tr><th>{$strManager}</th><td>".user_dropdown('managerid')."</td></tr>";

    echo "<tr><th>{$strRole}</th>";
    echo "<td>".role_drop_down('roleid', show_form_value('new_user', 'roleid', $CONFIG['default_roleid']))."</td>";
    echo "</tr>";

    echo "<tr><th>{$strJobTitle}</th><td><input maxlength='50' name='jobtitle' size='30' class='required' value='".show_form_value('new_user', 'jobtitle', '')."' /> <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr id='email'><th>{$strEmail}</th><td><input maxlength='50' name='email' size='30'  class='required' value='".show_form_value('new_user', 'email', '')."'/> <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr><th>{$strTelephone}</th><td><input maxlength='50' name='phone' size='30' value='".show_form_value('new_user', 'phone', '')."'/></td></tr>\n";

    echo "<tr><th>{$strMobile}</th><td><input maxlength='50' name='mobile' size='30' value='".show_form_value('new_user', 'mobile', '')."'/></td></tr>\n";

    echo "<tr><th>{$strFax}</th><td><input maxlength='50' name='fax' size='30' value='".show_form_value('new_user', 'fax', '')."' /></td></tr>\n";

    if ($CONFIG['holidays_enabled'])
    {
        echo "<tr><th>{$strHolidayEntitlement}</th><td><input maxlength='3' name='holiday_entitlement' size='3' value='".show_form_value('new_user', 'holiday_entitlement', $CONFIG['default_entitlement'])."' /> {$strDays}</td></tr>\n";

        echo "<tr><th>{$strStartDate} ".help_link('UserStartdate')."</th>";
        echo "<td><input type='text' name='startdate' id='startdate' size='10' ";
        echo "value='". show_form_value('new_user', 'startdate', '') . "' ";
        echo "/> ";
        echo date_picker('adduser.startdate');
        echo "</td></tr>\n";
    }
    plugin_do('user_new_form');
    echo "</table>\n";
    echo "<input type='hidden' name='formtoken' value='" . gen_form_token() . "' />";
    echo "<p class='formbuttons'><input type='submit' name='submit' value='{$strSave}' /></p>";
    echo "<p class='return'><a href='manage_users.php'>{$strReturnWithoutSaving}</a></p>";
    echo "</form>\n";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

    clear_form_data('new_user');
}
else
{
    // External variables
    $username = clean_dbstring(strtolower(trim(strip_tags($_REQUEST['username']))));
    $realname = cleanvar($_REQUEST['realname']);
    $password = clean_dbstring($_REQUEST['password']);
    $groupid = clean_int($_REQUEST['groupid']);
    $managerid = clean_int($_REQUEST['managerid']);
    $roleid = clean_int($_REQUEST['roleid']);
    $jobtitle = cleanvar($_REQUEST['jobtitle']);
    $email = cleanvar($_REQUEST['email']);
    $phone = cleanvar($_REQUEST['phone']);
    $mobile = cleanvar($_REQUEST['mobile']);
    $fax = cleanvar($_REQUEST['fax']);
    $holiday_entitlement = clean_int($_REQUEST['holiday_entitlement']);
    if (!empty($_POST['startdate']))
    {
        $startdate = date('Y-m-d', strtotime($_POST['startdate']));
    }
    else $startdate = '';
    $formtoken = cleanvar($_POST['formtoken']);

    $_SESSION['formdata']['new_user'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);

    $errors = 0;
    if (!check_form_token($formtoken))
    {
        $_SESSION['formerrors']['add_user']['formtoken'] = $strFormInvalidExpired;
        $errors++;
    }
    if ($realname == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_user']['realname'] = sprintf($strFieldMustNotBeBlank, $strRealName)."</p>\n";
    }

    if ($username == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_user']['username'] = sprintf($strFieldMustNotBeBlank, $strUsername)."</p>\n";
    }

    if ($password == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_user']['password'] = sprintf($strFieldMustNotBeBlank, $strPassword)."</p>\n";
    }

    if ($jobtitle == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_user']['jobtitle'] = sprintf($strFieldMustNotBeBlank, $strJobTitle)."</p>\n";
    }

    if ($email == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_user']['email'] = sprintf($strFieldMustNotBeBlank, $strEmail)."</p>\n";
    }

    $sql = "SELECT COUNT(id) FROM `{$dbUsers}` WHERE username='{$username}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    list($countexisting) = mysql_fetch_row($result);
    if ($countexisting >= 1)
    {
        $errors++;
        $_SESSION['formerrors']['new_user'][''] = "{$strUsernameNotUnique}</p>\n";
    }
    // Check email address is unique (discount disabled accounts)
    $sql = "SELECT COUNT(id) FROM `{$dbUsers}` WHERE status > 0 AND email='{$email}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($countexisting) = mysql_fetch_row($result);
    if ($countexisting >= 1)
    {
        $errors++;
        $_SESSION['formerrors']['new_user']['duplicate_email'] = "{$strEmailMustBeUnique}</p>\n";
    }
    plugin_do('user_new_submitted');

    // add information if no errors
    if ($errors == 0)
    {
        $password = md5($password);
        $sql = "INSERT INTO `{$dbUsers}` (username, password, realname, roleid,
                groupid, title, email, phone, mobile, fax, status,
                holiday_entitlement, user_startdate, managerid, lastseen) ";
        $sql .= "VALUES ('{$username}', '{$password}', '{$realname}', '{$roleid}',
                '{$groupid}', '{$jobtitle}', '{$email}', '{$phone}', '{$mobile}', '{$fax}',
                1, '{$holiday_entitlement}', '{$startdate}', ".convert_string_null_safe($managerid).", NOW())";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        $newuserid = mysql_insert_id();

        // Default user settings
        $sql = "INSERT INTO `{$dbUserConfig}` (`userid`, `config`, `value`) ";
        $sql .= "VALUES ('{$newuserid}', 'theme', '{$CONFIG['default_interface_style']}') ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        $sql = "INSERT INTO `{$dbUserConfig}` (`userid`, `config`, `value`) ";
        $sql .= "VALUES ('{$newuserid}', 'iconset', '{$CONFIG['default_iconset']}') ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        // Create permissions (set to none)
        $sql = "SELECT id FROM `{$dbPermissions}`";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        while ($perm = mysql_fetch_object($result))
        {
            $psql = "INSERT INTO `{$dbUserPermissions}` (userid, permissionid, granted) ";
            $psql .= "VALUES ('{$newuserid}', '{$perm->id}', 'false')";
            mysql_query($psql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        }

        if (!$result)
        {
            html_redirect("manage_users.php", FAIL, $strAdditionFail);
        }
        else
        {
            clear_form_data('new_user');
            clear_form_errors('new_user');

            setup_user_triggers($newuserid);
            plugin_do('user_new_saved');
            $t = new TriggerEvent('TRIGGER_NEW_USER', array('userid' => $newuserid));
            html_redirect("manage_users.php");
        }
    }
    else
    {
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}
?>