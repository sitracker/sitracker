<?php
// notifications.php - Page for users to setup their trigger actions
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

$permission = 71;
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'trigger.class.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNotifications;
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('trigger', 32)." {$title}</h2>";
echo "<div id='pageintro'>";
echo "<p>Below is a list of your notifications.</p>";
echo "<a href='action_details.php'>";
echo icon('new', 16). " {$strNewNotification}</a>";
echo "</div><br />";
echo triggers_to_html($sit[2]);
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>
