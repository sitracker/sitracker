<?php
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author Kieran Hogg <kieran[at]sitracker.org>

require ('core.php');
$permission = PERM_MYTRIGGERS_MANAGE;
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'trigger.class.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strSystemActions;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('trigger', 32)." {$title}</h2>";
echo "<div id='pageintro'>";
plugin_do('notifications');

echo "<p align='center'>{$strNotificationDescription}</p>";

$operations = array();
$operations[$strNewNotification] = 'action_details.php?user=admin';
echo "<p align='center'>" . html_action_links($operations) . "</p>";

echo "</div><br />";

echo triggers_to_html(0);

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>