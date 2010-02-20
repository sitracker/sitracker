<?php
// logout.php - Removes cookies
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

session_name($CONFIG['session_name']);
session_start();

if ($_SESSION['portalauth'])
{
    journal(CFG_LOGGING_NORMAL, 'Logout', "Portal user ".contact_realname($_SESSION['contactid'])." logged out", CFG_JOURNAL_LOGIN, $_SESSION['contactid']);
}
else
{
	journal(CFG_LOGGING_NORMAL, 'Logout', "User {$_SESSION['userid']} logged out", CFG_JOURNAL_LOGIN, '');
}
// End the session, remove the cookie and destroy all data registered with the session
$_SESSION['auth'] = FALSE;
$_SESSION['portalauth'] = FALSE;
$_SESSION = array();

session_unset();
session_destroy();

if (isset($_COOKIE[session_name()]))
{
   setcookie(session_name(), '', time()-42000, '/');
}

// redirect
if (!empty($CONFIG['logout_url'])) $url = $CONFIG['logout_url'];
else $url = $CONFIG['application_webpath']."index.php";
header ("Location: $url");
exit;
?>
