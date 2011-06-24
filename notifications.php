<?php
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author Kieran Hogg <kieran[at]sitracker.org>

$permission = PERM_MYTRIGGERS_MANAGE;

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'trigger.class.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNotifications;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('trigger', 32)." {$title}</h2>";
echo "<div id='pageintro'>";
 
echo "<p align='center'>{$strNotificationDescription}";
echo "<br /><br /><a href='action_details.php'>";
echo icon('new', 16). " {$strNewNotification}</a></p>";
echo "</div><br />";

echo triggers_to_html(1);

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>