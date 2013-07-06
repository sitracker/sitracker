<?php
// portal/help.php - Get context sensitive portal help
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


require ('..' . DIRECTORY_SEPARATOR . 'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

$accesslevel = 'any';

include (APPLICATION_LIBPATH . 'portalauth.inc.php');
include (APPLICATION_INCPATH . 'portalheader.inc.php');

$title = $strHelp;

echo "<h2>".icon('help', 32, $strHelp)." ";
echo "{$strHelp}</h2>";
echo "<div id='help'>";

$helpfile = APPLICATION_HELPPATH . "{$_SESSION['lang']}".DIRECTORY_SEPARATOR."portal_help.html";
if (!file_exists($helpfile)) $helpfile = APPLICATION_HELPPATH . "{$CONFIG['default_i18n']}".DIRECTORY_SEPARATOR ."/portal_help.html";
if (!file_exists($helpfile)) $helpfile = APPLICATION_HELPPATH . "en-GB".DIRECTORY_SEPARATOR ."/portal_help.html";
if (file_exists($helpfile))
{
    $helptext = file_get_contents($helpfile);
}
else trigger_error("Error: Missing helpfile 'portal_help.html'", E_USER_ERROR);

echo $helptext;

echo "</div>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>