<?php
// login.php - processes the login
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
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

setcookie(session_name(), session_id(), ini_get("session.cookie_lifetime"), "/");

if (!empty($_REQUEST['lang']))
{
    $language = htmlspecialchars(mb_substr(strip_tags($_REQUEST['lang']), 0, 5), ENT_NOQUOTES, 'utf-8');
    if ((substr($language, 2, 1) != '-' OR mb_strpos('.', $language) !== FALSE) AND mb_strlen($language) != 2)
    {
        $language = 'xx-xx'; // default lang
    }
}

require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'trigger.class.php');

populate_syslang();
// External vars
$password = $_REQUEST['password'];  // We don't need to clean as its md5'ed else where
$username = cleanvar($_REQUEST['username']);
$public_browser = cleanvar($_REQUEST['public_browser']);
$page = clean_url($_REQUEST['page']);

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
elseif (authenticate($username, $password))
{
    // Valid user
    createUserSession($username);

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

    $authContact = authenticateContact($username, $password); 

    if ($authContact)
    {
        createContactSession($authContact);

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
