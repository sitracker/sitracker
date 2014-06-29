<?php
// edit_profile.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


// This Page Is Valid XHTML 1.0 Transitional!  1Nov05

require ('core.php');
$permission = PERM_MYPROFILE_EDIT; // Edit your profile
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$mode = clean_fixed_list($_REQUEST['mode'], array('', 'save', 'savesessionlang'));
$edituserpermission = user_permission($sit[2], 23); // edit user

if (empty($_REQUEST['userid']) OR $_REQUEST['userid'] == 'current' OR $edituserpermission == FALSE)
{
    $edituserid = mysql_real_escape_string($sit[2]);
}
else
{
    if (empty($_REQUEST['userid']) === FALSE AND $edituserpermission === TRUE)
    {
        $edituserid = clean_int($_REQUEST['userid']);
    }
    else
    {
        html_redirect("noaccess.php?id={$permission}", FALSE);
        exit;
    }
}

if (empty($mode))
{
    $title = $strEditProfile;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    $user = new User($edituserid);

    echo "<h2>".icon('user', 32)." ";
    echo sprintf($strEditProfileFor, $user->realname).' '.gravatar($user->email)."</h2>";
    plugin_do('user_profile_edit');
    echo "<form id='edituser' action='{$_SERVER['PHP_SELF']}' method='post'>";
    echo "<table class='maintable vertical'>";
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
    if ($user->source != 'sit' AND !empty($CONFIG['ldap_realname']))
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
    echo "<tr><th>{$strSource}</th><td>{$user->source}</td></tr>";
    echo "<tr><th>{$strJobTitle}</th>";
    echo "<td>";
    if ($user->source != 'sit' AND !empty($CONFIG['ldap_jobtitle']))
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
    echo "<td><textarea name='signature' rows='4' cols='40'>".stripslashes(strip_tags($user->signature))."</textarea></td></tr>\n";
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
    echo "<tr><th>{$strManager}</th><td>".user_dropdown('managerid', $user->managerid, $edituserid)."</td></tr>";
    echo "<tr><th colspan='2'>{$strWorkStatus}</th></tr>";

    if ($edituserpermission AND $edituserid != $sit[2] AND $user->source == 'sit')
    {
        $userdisable = TRUE;
    }
    else
    {
        $userdisable = FALSE;
    }

    if (user_permission($sit[2], PERM_MYSTATUS_SET)) // edit my status
    {
        $userstatus = userstatus_drop_down("status", $user->status, $userdisable);
        $useraccepting = accepting_drop_down("accepting", $edituserid);
    }
    else
    {
        $sql = "SELECT us.name FROM `{$dbUserStatus}` AS us, `{$dbUsers}` AS u ";
        $sql .= "WHERE u.status = us.id AND u.id = '$sit[2]' LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

        list($userstatus) = mysql_fetch_row($result);
        $userstatus = $GLOBALS[$userstatus];

        $useraccepting = db_read_column('accepting', $dbUsers, $sit[2]);
        $useraccepting = str_replace('Yes', $strYes, $useraccepting);
    }

    echo "<tr><th>{$strStatus}</th><td>";
    echo $userstatus;
    echo "</td></tr>\n";
    echo "<tr><th>{$strAccepting} {$strIncidents}</th><td>";
    echo $useraccepting;
    echo "</td></tr>\n";
    echo "<tr><th>{$strMessage} ".help_link('MessageTip')."</th>";
    echo "<td><textarea name='message' rows='4' cols='40'>".stripslashes(strip_tags($user->message))."</textarea></td></tr>\n";
    echo "<tr><th colspan='2'>{$strContactDetails}</th></tr>";
    echo "<tr id='email'><th>{$strEmail}</th>";
    echo "<td>";
    if ($user->source != 'sit' AND !empty($CONFIG['ldap_email']))
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
    if ($user->source != 'sit' AND !empty($CONFIG['ldap_telephone']))
    {
        echo $user->phone;
    }
    else
    {
        echo "<input maxlength='50' name='phone' size='30' type='text' value='".strip_tags($user->phone)."' />";
    }
    echo "</td></tr>";
    echo "<tr><th>{$strFax}</th><td>";
    if ($user->source != 'sit' AND !empty($CONFIG['ldap_fax']))
    {
        echo $user->fax;
    }
    else
    {
        echo "<input maxlength='50' name='fax' size='30' type='text' value='".strip_tags($user->fax)."' />";
    }
    echo "</td></tr>";
    echo "<tr><th>{$strMobile}</th><td>";
    if ($user->source != 'sit' AND !empty($CONFIG['ldap_mobile']))
    {
        echo $user->mobile;
    }
    else
    {
        echo "<input maxlength='50' name='mobile' size='30' type='text' value='{$user->mobile}' />";
    }
    echo "</td></tr>";
    echo "<tr><th>".icon('aim', 16, 'AIM')." AIM</th>";
    echo "<td><input maxlength='50' name='aim' size='30' type='text' value='".strip_tags($user->aim)."' /></td></tr>";
    echo "<tr><th>".icon('icq', 16, 'ICQ')." ICQ</th>";
    echo "<td><input maxlength='50' name='icq' size='30' type='text' value='".strip_tags($user->icq)."' /></td></tr>";
    echo "<tr><th>".icon('msn', 16, 'MSN')." MSN</th>";
    echo "<td><input maxlength='50' name='msn' size='30' type='text' value='".strip_tags($user->msn)."' /></td></tr>";
    echo "<tr><th>".icon('skype', 16, 'SKYPE')." Skype</th>";
    echo "<td><input maxlength='50' name='skype' size='30' type='text' value='".strip_tags($user->skype)."' /></td></tr>";


    plugin_do('user_profile_edit_form');
    // Do not allow password change if using LDAP
    if ($user->source == 'sit')
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
    echo "<input type='hidden' name='formtoken' value='" . gen_form_token() . "' />";
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

    $user->realname = cleanvar($_POST['realname']);
    $user->qualifications = cleanvar($_POST['qualifications']);

    $user->email = cleanvar($_POST['email']);
    $user->jobtitle = cleanvar($_POST['jobtitle']);
    $user->phone = cleanvar($_POST['phone']);
    $user->mobile = cleanvar($_POST['mobile']);
    $user->aim = cleanvar($_POST['aim']);
    $user->icq = cleanvar($_POST['icq']);
    $user->msn = cleanvar($_POST['msn']);
    $user->skype = cleanvar($_POST['skype']);
    $user->fax = cleanvar($_POST['fax']);
    $user->status = cleanvar($_POST['status']);
    $user->managerid = cleanvar($_POST['managerid']);

    // PH 2014.06.29
    // We don't do a cleanvar on these as its already being done in the User::edit() function and we result in double escaping
    // Technically we don't need to escape any of these variables as they are all done in the class 
    $user->message = $_POST['message'];
    $user->signature = $_POST['signature'];

    if (cleanvar($_POST['accepting']) == 'Yes') $user->accepting = true;
    else $user->accepting = false;
    $user->roleid = clean_int($_POST['roleid']);
    $user->holiday_entitlement = clean_int($_POST['holiday_entitlement']);
    if (!empty($_POST['startdate']))
    {
        $user->startdate = date('Y-m-d', strtotime($_POST['startdate']));
    }
    else
    {
        $user->startdate = date('Y-m-d',0);
    }
    $password = cleanvar($_POST['oldpassword']);
    $newpassword1 = cleanvar($_POST['newpassword1']);
    $newpassword2 = cleanvar($_POST['newpassword2']);

    $formtoken = cleanvar($_POST['formtoken']);

    if (empty($user->emoticons)) $user->emoticons = 'false';

    // Some extra checking here so that users can't edit other peoples profiles
    $edituserpermission = user_permission($sit[2], PERM_USER_EDIT); // edit user
    if ($edituserid != $sit[2] AND $edituserpermission == FALSE)
    {
        trigger_error('Error: No permission to edit this users profile', E_USER_ERROR);
        exit;
    }

    if (!check_form_token($formtoken))
    {
        html_redirect("main.php", FALSE, $strFormInvalidExpired);
        exit;
    }

    // If users status is set to 0 (disabled) force 'accepting' to no
    if ($user->status == 0) $user->accepting = false;

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
    plugin_do('user_profile_edit_submitted');

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
            html_redirect("{$_SERVER['PHP_SELF']}?userid={$edituserid}", FALSE, $strAnUnknownErrorOccured);
        }
        elseif ($result === TRUE)
        {
            if ($edituserid == $sit[2]) $redirecturl = 'index.php';
            else $redirecturl = 'manage_users.php';
            plugin_do('user_profile_edit_saved');

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

    $sql = "INSERT INTO `{$GLOBALS['dbUserConfig']}` VALUES ({$sit[2]}, 'language', '{$_SESSION['lang']}') ON DUPLICATE KEY UPDATE value = '{$_SESSION['lang']}'";
    mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    
    $t = new Trigger('TRIGGER_LANGUAGE_DIFFERS', $sit[2], '', '');
    $t->revoke();
    
    html_redirect("main.php");
}

?>