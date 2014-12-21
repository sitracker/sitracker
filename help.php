<?php
// help.php - Get context sensitive help
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

require ('core.php');
$permission = PERM_HELP_VIEW; // Help
require (APPLICATION_LIBPATH . 'functions.inc.php');
$title = "Help";

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$id = clean_int($_REQUEST['id']);
$title = $strHelp;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');
journal(CFG_LOGGING_MAX, 'Help Viewed', "Help document $id was viewed", CFG_JOURNAL_OTHER, $id);
echo "<h2>".icon('help', 32, $strHelp)." ";
if ($id > 0) echo permission_name($id).' ';
echo "{$strHelp}</h2>";
plugin_do('help');
echo "<div id='help'>";

$helpfile = APPLICATION_HELPPATH . "{$_SESSION['lang']}".DIRECTORY_SEPARATOR."help.html";
if (!file_exists($helpfile)) $helpfile = APPLICATION_HELPPATH . "{$_SESSION['lang']}".DIRECTORY_SEPARATOR ."en-GB/help.html";
if (file_exists($helpfile))
{
    $helptext = file_get_contents($helpfile);
}
else trigger_error("Error: Missing helpfile 'help.html'", E_USER_ERROR);

echo $helptext;

echo "</div>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>