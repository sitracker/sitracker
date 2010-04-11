<?php
// triggers.php - Page for setting user trigger preferences
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

$adminuser = user_permission($sit[2],22); // Admin user
$title = 'New Triggers Interface';
include (APPLICATION_INCPATH . 'htmlheader.inc.php');
($_GET['user'] == 'admin') ? $user = 0 : $user = $sit[2];

echo "<h2>".icon('trigger', 32)." {$title}</h2>";
// BEGIN TESTING CODE
$t = new TriggerEvent('TRIGGER_SIT_UPGRADED', array());

//$t = new Trigger('TRIGGER_INCIDENT_CLOSED', 1, 'EMAIL_INCIDENT_CLOSURE', 'ACTION_EMAIL', '', '');
//echo $t->debug();
// $t2 = Trigger::byID(5);
// $t2->debug();
echo triggers_to_html($user);

//END TESTING CODE
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');


?>
