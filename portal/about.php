<?php
// about.php - About page (for the portal)
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Ivan Lucas <ivan[at]sitracker.org>

require ('..'.DIRECTORY_SEPARATOR.'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
$accesslevel = 'any';
require (APPLICATION_LIBPATH . 'portalauth.inc.php');

include (APPLICATION_INCPATH . 'portalheader.inc.php');

echo "<div id='aboutsit'>";
echo "<img src='../images/sitlogo_270x100.png' width='270' height='100' alt='SiT! Support Incident Tracker' />";
echo "<p class='footer'>{$strVersion}: {$application_version} {$application_revision}";
if ($CONFIG['debug'] == TRUE) echo " (debug mode)";
echo "</p>";
debug_log("{$strVersion}: {$application_version} {$application_revision}", TRUE);
echo "</div>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>