<?php
// edit_profile.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


// This Page Is Valid XHTML 1.0 Transitional!  1Nov05

$permission = 4; // Edit your profile
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$mode = cleanvar($_REQUEST['mode']);
$edituserpermission = user_permission($sit[2], 23); // edit user

if (empty($_REQUEST['userid']) OR $_REQUEST['userid'] == 'current' OR $edituserpermission == FALSE)
{
    $edituserid = mysql_real_escape_string($sit[2]);
}
else
{
    if (!empty($_REQUEST['userid']))
    {
        $edituserid = clean_int($_REQUEST['userid']);
    }
}

if (empty($mode))
{
    $title = $strEditProfile;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    $user = new User($edituserid);

    echo "<h2>".icon('user', 32)." ";
    echo sprintf($strEditProfileFor, $user->realname).' '.gravatar($user->email)."</h2>";
    echo "<form id='edituser' action='{$_SERVER['PHP_SELF']}' method='post'>";
    echo "<table align='center' class='vertical'>";
    echo "<col width='250'></col><col width='*'></col>";
    echo "<tr><th colspan='2'>";
    if ($edituserid == $sit[2])
    {
        echo sprintf($strAboutPerson, $strYou);
    }
    else
    {
        echo sprintf($strAboutPerson, $user->realname);
    }

    echo "</th></tr>\n";
    echo "<tr><th>{$strUsername}</th><td>{$user->username}</td></tr>";
    echo "<tr><th>{$strRole}</th>";
    if ($edituserid == $sit[2] OR $edituserid == 1)
    {
        echo "<td>{$user->rolename}</td>";
    }
    else
    {
        echo "<td>".role_drop_down('roleid', $user->roleid)."</td>";
    }

    echo "</tr>";
    echo "<tr><th>{$strRealName}</th><td>";
    if ($_SESSION['user_source'] != 'sit' AND !empty($CONFIG['ldap_realname']))
    {
        echo "<input name='realname' type='hidden' value=\"{$user->realname}\" />{$user->realname}";
    }
    else
    {
        echo "<input class='required' maxlength='50' name='realname' size='30'";
        echo " type='text' value=\"{$user->realname}\" />";
        echo " <span class='required'>{$strRequired}</span>";
    }
    echo "</td></tr>\n";
    echo "<tr><th>{$strSource}</th><td>{$user->user_source}</td></th>";
    echo "<tr><th>{$strJobTitle}</th>";
    echo "<td>";
    if ($_SESSION['user_source'] != 'sit' AND !empty($CONFIG['ldap_jobtitle']))
    {
        echo $user->jobtitle;
    }
    else
    {
        echo "<input maxlength='50' name='jobtitle' size='30' type='text' ";
        echo "value=\"{$user->jobtitle}\" />";
    }
    echo "</td></tr>\n";
    echo "<tr><th>{$strQualifications} ".help_link('QualificationsTip')."</th>";
    echo "<td><input maxlength='255' size='100' name='qualifications' value='{$user->qualifications}' /></td></tr>\n";
    echo "<tr><th>{$strEmailSignature} ".help_link('EmailSignatureTip')."</th>";
    echo "<td><textarea name='signature' rows='4' cols='40'>".strip_tags($user->signature)."</textarea></td></tr>\n";
    $entitlement = user_holiday_entitlement($edituserid);
    if ($edituserpermission && $edituserid != $sit[2])
    {
        echo "<tr><th>{$strHolidayEntitlement}</th><td>";
        echo "<input type='text' name='holiday_entitlement' value='{$entitlement}' size='2' /> {$strDays}";
        echo "</td></tr>\n";
        echo "<tr><th>{$strStartDate} ".help_link('UserStartdate')."</th>";
        echo "<td><input type='text' name='startdate' id='startdate' size='10' ";
        echo "value='{$user->user_startdate}'";
        echo "/> ";
        echo date_picker('edituser.startdate');
        echo "</td></tr>\n";
    }
    elseif ($entitlement > 0)
    {
        $holiday_resetdate = user_holiday_resetdate($edituserid);
        $holidaystaken = user_count_holidays($edituserid, HOL_HOLIDAY, $holiday_resetdate);
        echo "<tr><th>{$strHolidayEntitlement}</th><td>";
        echo "{$entitlement} {$strDays}, ";
        echo "{$holidaystaken} {$strtaken}, ";
        echo sprintf($strRemaining, $entitlement-$holidaystaken);
        echo "</td></tr>\n";
        echo "<tr><th>{$strOtherLeave}</th><td>";
        echo user_count_holidays($edituserid, HOL_SICKNESS)." {$strdayssick}, ";
        echo user_count_holidays($edituserid, HOL_WORKING_AWAY)." {$strdaysworkingaway}, ";
        echo user_count_holidays($edituserid, HOL_TRAINING)." {$strdaystraining}";
        echo "<br />";
        echo user_count_holidays($edituserid, HOL_FREE)." {$strdaysother}";
        echo "</td></tr>";
    }

    echo "<tr><th>{$strGroupMembership}</th><td valign='top'>";

    if ($user->groupid >= 1)
    {
        $sql = "SELECT name FROM `{$dbGroups}` WHERE id='{$user->groupid}' ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        $group = mysql_fetch_object($result);
        echo $group->name;
    }
    else
    {
        echo $strNotSet;
    }
    echo "</td></tr>";
    echo "<tr><th colspan='2'>{$strWorkStatus}</th></tr>";

    if ($edituserpermission AND $edituserid != $sit[2] AND $user->source == 'sit')
    {
        $userdisable = TRUE;
    }
    else
    {
        $userdisable = FALSE;
    }

    echo "<tr><th>{$strStatus}</th><td>";
    echo userstatus_drop_down("status", $user->status, $userdisable);
    echo "</td></tr>\n";
    echo "<tr><th>{$strAccepting} {$strIncidents}</th><td>";
    echo accepting_drop_down("accepting", $edituserid);
    echo "</td></tr>\n";
    echo "<tr><th>{$strMessage} ".help_link('MessageTip')."</th>";
    echo "<td><textarea name='message' rows='4' cols='40'>".strip_tags($user->message)."</textarea></td></tr>\n";
    echo "<tr><th colspan='2'>{$strContactDetails}</th></tr>";
    echo "<tr id='email'><th>{$strEmail}</th>";
    echo "<td>";
    if ($_SESSION['user_source'] != 'sit' AND !empty($CONFIG['ldap_email']))
    {
        echo "<input name='email' type='hidden'value='".strip_tags($user->email)."' />{$user->email}";
    }
    else
    {
        echo "<input class='required' maxlength='50' name='email' size='30' ";
        echo "type='text' value='".strip_tags($user->email)."' />";
        echo " <span class='required'>{$strRequired}</span>";
    }
    echo "</td></tr>";
    echo "<tr id='phone'><th>{$strTelephone}</th><td>";
    if ($_SESSION['user_source'] != 'sit' AND !empty($CONFIG['ldap_telephone']))
    {
        echo $user->phone;
    }
    else
    {
        echo "<input maxlength='50' name='phone' size='30' type='text' value='".strip_tags($user->phone)."' />";
    }
    echo "</td></tr>";
    echo "<tr><th>{$strFax}</th><td>";
    if ($_SESSION['user_source'] != 'sit' AND !empty($CONFIG['ldap_fax']))
    {
        echo $user->fax;
    }
    else
    {
        echo "<input maxlength='50' name='fax' size='30' type='text' value='".strip_tags($user->fax)."' />";
    }
    echo "</td></tr>";
    echo "<tr><th>{$strMobile}</th><td>";
    if ($_SESSION['user_source'] != 'sit' AND !empty($CONFIG['ldap_mobile']))
    {
        echo $user->mobile;
    }
    else
    {
        echo "<input maxlength='50' name='mobile' size='30' type='text' value='{$user->mobile}' />";
    }
    echo "</td></tr>";
    echo "<tr><th>AIM ".icon('aim', 16, 'AIM')."</th>";
    echo "<td><input maxlength=\"50\" name=\"aim\" size=\"30\" type=\"text\" value=\"".strip_tags($user->aim)."\" /></td></tr>";
    echo "<tr><th>ICQ ".icon('icq', 16, 'ICQ')."</th>";
    echo "<td><input maxlength=\"50\" name=\"icq\" size=\"30\" type=\"text\" value=\"".strip_tags($user->icq)."\" /></td></tr>";
    echo "<tr><th>MSN ".icon('msn', 16, 'MSN')."</th>";
    echo "<td><input maxlength=\"50\" name=\"msn\" size=\"30\" type=\"text\" value=\"".strip_tags($user->msn)."\" /></td></tr>";

    plugin_do('edit_profile_form');
    // Do not allow password change if using LDAP
    if ($_SESSION['user_source'] == 'sit')
    {
        if ($CONFIG['trusted_server'] == FALSE AND $edituserid == $sit[2])
        {
            echo "<tr class='password'><th colspan='2'>{$strChangePassword}</th></tr>";
            echo "<tr class='password'><th>&nbsp;</th><td>{$strToChangePassword}</td></tr>";
            echo "<tr class='password'><th>{$strOldPassword}</th><td><input maxlength='50' name='oldpassword' size='30' type='password' /></td></tr>";
            echo "<tr class='password'><th>{$strNewPassword}</th><td><input maxlength='50' name='newpassword1' size='30' type='password' /></td></tr>";
            echo "<tr class='password'><th>{$strConfirmNewPassword}</th><td><input maxlength='50' name='newpassword2' size='30' type='password' /></td></tr>";
        }
    }
    echo "</table>\n";
    echo "<input type='hidden' name='userid' value='{$edituserid}' />";
    echo "<input type='hidden' name='mode' value='save' />";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> <input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "</form>\n";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($mode == 'save')
{
    // External variables
    $user = new User();
    $user->id = clean_int($_POST['userid']);

    $edituserid = clean_int($_POST['userid']); // remove when tested

    $user->message = cleanvar($_POST['message']);
    $user->realname = cleanvar($_POST['realname']);
    $user->qualifications = cleanvar($_POST['qualifications']);

    $user->email = cleanvar($_POST['email']);
    $user->jobtitle = cleanvar($_POST['jobtitle']);
    $user->phone = cleanvar($_POST['phone']);
    $user->mobile = cleanvar($_POST['mobile']);
    $user->aim = cleanvar($_POST['aim']);
    $user->icq = cleanvar($_POST['icq']);
    $user->msn = cleanvar($_POST['msn']);
    $user->fax = cleanvar($_POST['fax']);
    $user->signature = cleanvar($_POST['signature']);
    $user->status = cleanvar($_POST['status']);

    if (cleanvar($_POST['accepting']) == 'Yes') $user->accepting = true;
    else $user->accepting = false;
    $user->roleid = clean_int($_POST['roleid']);
    $user->holiday_entitlement = clean_int($_POST['holiday_entitlement']);
    if (!empty($_POST['startdate']))
    {
        $user->startdate = date('Y-m-d',strtotime($_POST['startdate']));
    }
    else
    {
        $user->startdate = date('Y-m-d',0);
    }
    $password = cleanvar($_POST['oldpassword']);
    $newpassword1 = cleanvar($_POST['newpassword1']);
    $newpassword2 = cleanvar($_POST['newpassword2']);

    if (empty($user->emoticons)) $user->emoticons = 'false';

    // Some extra checking here so that users can't edit other peoples profiles
    $edituserpermission = user_permission($sit[2], 23); // edit user
    if ($edituserid != $sit[2] AND $edituserpermission == FALSE)
    {
        trigger_error('Error: No permission to edit this users profile', E_USER_ERROR);
        exit;
    }

    // If users status is set to 0 (disabled) force 'accepting' to no
    if ($user->status == 0) $user->accepting = 'No';

    // Update user profile
    $errors = 0;

    // check for change of password
    if ($password != '' && $newpassword1 != '' && $newpassword2 != '')
    {
        // verify password fields
        $passwordMD5 = md5($password);
        if ($newpassword1 == $newpassword2 AND strcasecmp($passwordMD5, user_password($edituserid)) == 0)
        {
            $user->password = $password;
        }
        else
        {
            $errors++;
            $error_string .= "<h5 class='error'>{$strPasswordsDoNotMatch}</h5>";
        }
    }

    // update database if no errors
    if ($errors == 0)
    {
        $result = $user->edit();

        // If this is the current user, update the profile in the users session
        if ($edituserid == $_SESSION['userid'])
        {
            $_SESSION['style'] = $user->style;
            $_SESSION['realname'] = $user->realname;
            $_SESSION['email'] = $user->email;
            $_SESSION['incident_refresh'] = $user->incident_refresh;
            $_SESSION['update_order'] = $user->update_order;
            $_SESSION['num_update_view'] = $user->num_updates_view;
            $_SESSION['lang'] = $user->i18n;
            $_SESSION['utcoffset'] = $user->utc_offset;
        }

        if ($result === FALSE)
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            trigger_error("!Error while updating users table", E_USER_ERROR);
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
            exit;
        }
        elseif ($result === TRUE)
        {
            if ($edituserid == $sit[2]) $redirecturl = 'index.php';
            else $redirecturl = 'manage_users.php';
            plugin_do('save_profile_form');

            // password was not changed
            if (isset($confirm_message)) html_redirect($redirecturl, TRUE, $confirm_message);
            else html_redirect($redirecturl);
            exit;
        }
        else
        {
            $errors++;
            $error_string .= $result;
        }
    }

    if ($errors > 0)
    {
        html_redirect($redirecturl, FALSE, $error_string);
    }
}
elseif ($mode == 'savesessionlang')
{

    $sql = "UPDATE `{$dbUsers}` SET var_i18n = '{$_SESSION['lang']}' WHERE id = {$sit[2]}";
    mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    // FIXME 3.35 use revoke instead
    $sql = "DELETE FROM `{$dbNotices}` WHERE type='".USER_LANG_DIFFERS_TYPE."' AND userid={$sit[2]}";
    mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    html_redirect("main.php");
}

?>