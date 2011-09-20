<?php
// login.php - processes the login
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


require ('core.php');

session_name($CONFIG['session_name']);
session_start();

$_SESSION['auth'] = FALSE;

if (function_exists('session_regenerate_id'))
{
    if (!version_compare(phpversion(),"5.1.0",">=")) session_regenerate_id(TRUE);
    else session_regenerate_id();
}

setcookie(session_name(), session_id(),ini_get("session.cookie_lifetime"), "/");

$language = htmlspecialchars(mb_substr(strip_tags($_REQUEST['lang']), 0, 5), ENT_NOQUOTES, 'utf-8');
if (mb_substr($language, 2, 1) != '-' OR mb_strpos('.', $language) !== FALSE)
{
    $language = 'xx-xx'; // default lang
}

require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'trigger.class.php');

populate_syslang();
// External vars
$password = $_REQUEST['password'];
$username = cleanvar($_REQUEST['username']);
$public_browser = cleanvar($_REQUEST['public_browser']);
$page = strip_tags(str_replace('..','',str_replace('//','',str_replace(':','',urldecode($_REQUEST['page'])))));

if (empty($_REQUEST['username']) AND empty($_REQUEST['password']) AND $language != $_SESSION['lang'])
{
    if ($language != 'xx-xx')
    {
        $_SESSION['lang'] = $language;
    }
    else
    {
        $_SESSION['lang'] = '';
    }
    header ("Location: index.php");
}
elseif (authenticate($username, $_REQUEST['password']))
{
    // Valid user
    $_SESSION['auth'] = TRUE;

    $password = md5($_REQUEST['password']);

    // Retrieve users profile
    $sql = "SELECT id, username, realname, email, groupid, user_source FROM `{$dbUsers}` WHERE username='{$username}' LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) < 1)
    {
        $_SESSION['auth'] = FALSE;
        trigger_error("No such user", E_USER_ERROR);
    }
    $user = mysql_fetch_object($result);
    // Profile
    $_SESSION['userid'] = $user->id;
    $_SESSION['username'] = $user->username;
    $_SESSION['realname'] = $user->realname;
    $_SESSION['email'] = $user->email;
    $_SESSION['groupid'] = is_null($user->groupid) ? 0 : $user->groupid;
    $_SESSION['portalauth'] = FALSE;
    $_SESSION['user_source'] = $user->user_source;
    if (!is_null($_SESSION['startdate'])) $_SESSION['startdate'] = $user->user_startdate;

    // Read user config from database
    $_SESSION['userconfig'] = get_user_config_vars($user->id);

    // Make sure utc_offset cannot be blank
    if ($_SESSION['userconfig']['utc_offset'] == '')
    {
        $_SESSION['userconfig']['utc_offset'] == 0;
    }
    // Defaults
    if (empty($_SESSION['userconfig']['theme']))
    {
        $_SESSION['userconfig']['theme'] = $CONFIG['default_interface_style'];
    }
    if (empty($_SESSION['userconfig']['iconset']))
    {
        $_SESSION['userconfig']['iconset'] = $CONFIG['default_iconset'];
    }

    // Delete any old session user notices
    $sql = "DELETE FROM `{$dbNotices}` WHERE durability='session' AND userid={$_SESSION['userid']}";
    mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

    //check if the session lang is different the their profiles
    if ($_SESSION['lang'] != '' AND !empty($_SESSION['userconfig']['language']) AND
        $_SESSION['lang'] != $_SESSION['userconfig']['language'])
    {
        $t = new triggerEvent('TRIGGER_LANGUAGE_DIFFERS', array('profilelang' => $_SESSION['userconfig']['language'],
                    'currentlang' => $_SESSION['lang'], 'user' => $_SESSION['userid']));
    }

    if ($_SESSION['userconfig']['language'] != $CONFIG['default_i18n'] AND $_SESSION['lang'] == '')
    {
        $_SESSION['lang'] = is_null($_SESSION['userconfig']['language']) ? '' : $_SESSION['userconfig']['language'];
    }

    // Make an array full of users permissions
    // The zero permission is added to all users, zero means everybody can access
    $userpermissions[] = 0;
    // First lookup the role permissions
    $sql = "SELECT * FROM `{$dbUsers}` AS u, `{$dbRolePermissions}` AS rp WHERE u.roleid = rp.roleid ";
    $sql .= "AND u.id = '{$_SESSION['userid']}' AND granted='true'";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        $_SESSION['auth'] = FALSE;
        trigger_error(mysql_error(), E_USER_ERROR);
    }
    if (mysql_num_rows($result) >= 1)
    {
        while ($perm = mysql_fetch_object($result))
        {
            $userpermissions[] = $perm->permissionid;
        }
    }

    // Next lookup the individual users permissions
    $sql = "SELECT * FROM `{$dbUserPermissions}` WHERE userid = '{$_SESSION['userid']}' AND granted='true' ";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        $_SESSION['auth'] = FALSE;
        trigger_error(mysql_error(),E_USER_ERROR);
    }

    if (mysql_num_rows($result) >= 1)
    {
        while ($perm = mysql_fetch_object($result))
        {
            $userpermissions[] = $perm->permissionid;
        }
    }


    $_SESSION['permissions'] = array_unique($userpermissions);

    // redirect
    if (empty($page))
    {
        header ("Location: main.php");
        exit;
    }
    else
    {
        header("Location: {$page}");
        exit;
    }
}
elseif ($CONFIG['portal'] == TRUE)
{
    // Invalid user and portal enabled
    if ($language != 'xx-xx')
    {
        $_SESSION['lang'] = $language;
    }

    if (authenticateContact($username, $password))
    {
        debug_log("PORTAL AUTH SUCESSFUL");
        $_SESSION['portalauth'] = TRUE;

        $sql = "SELECT * FROM `{$dbContacts}` WHERE username = '{$username}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) < 1)
        {
            $_SESSION['portalauth'] = FALSE;
            trigger_error("No such user", E_USER_ERROR);
        }
        $contact = mysql_fetch_object($result);

        // Customer session
        // Valid user
        $_SESSION['contactid'] = $contact->id;
        $_SESSION['siteid'] = $contact->siteid;
        $_SESSION['userconfig']['style'] = $CONFIG['portal_interface_style'];
        $_SESSION['contracts'] = array();
        $_SESSION['auth'] = FALSE;
        $_SESSION['contact_source'] = $contact->contact_source;

        //get admin contracts
        if (admin_contact_contracts($_SESSION['contactid'], $_SESSION['siteid']) != NULL)
        {
            $admincontracts = admin_contact_contracts($_SESSION['contactid'], $_SESSION['siteid']);
            $_SESSION['usertype'] = 'admin';
        }

        //get named contact contracts
        if (contact_contracts($_SESSION['contactid'], $_SESSION['siteid']) != NULL)
        {
            $contactcontracts = contact_contracts($_SESSION['contactid'], $_SESSION['siteid']);
            if (!isset($_SESSION['usertype']))
            {
               $_SESSION['usertype'] = 'contact';
            }
        }

        //get other contracts
        if (all_contact_contracts($_SESSION['contactid'], $_SESSION['siteid']) != NULL)
        {
            $allcontracts = all_contact_contracts($_SESSION['contactid'], $_SESSION['siteid']);
            if (!isset($_SESSION['usertype']))
            {
                $_SESSION['usertype'] = 'user';
            }
        }

        $_SESSION['contracts'] = array_merge((array)$admincontracts, (array)$contactcontracts, (array)$allcontracts);

        load_entitlements($_SESSION['contactid'], $_SESSION['siteid']);
        header("Location: portal/");
        exit;
    }
    else
    {
        // Login failure
        $_SESSION['auth'] = FALSE;
        $_SESSION['portalauth'] = FALSE;
        // log the failure
        if ($username != '')
        {
            $errdate = date('M j H:i');
            $errmsg = "$errdate Failed login for user '{$username}' from IP: " . substr($_SERVER['REMOTE_ADDR'],0, 15);
            $errmsg .= "\n";
            $errlog = @error_log($errmsg, 3, $CONFIG['access_logfile']);
            ## if (!$errlog) echo "Fatal error logging this problem<br />";
            unset($errdate);
            unset($errmsg);
            unset($errlog);
        }
        // redirect
        header ("Location: index.php?id=3");
        exit;
    }
}
else
{
    //invalid user and portal disabled
    header ("Location: index.php?id=3");
    exit;
}
?>