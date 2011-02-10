<?php
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
// FIXME v3.90 wording
echo "<p align='center'>You can be notified in different ways when things occur, your current notifications are listed below.";
echo "<br /><br /><a href='action_details.php'>";
echo icon('add', 16). " {$strNewNotification}</a></p>";
echo "</div><br />";
echo triggers_to_html(1);
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>
