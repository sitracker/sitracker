<?php
// releasenotes.php - Release notes summary
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

require ('core.php');
$permission = PERM_NOT_REQUIRED;
require (APPLICATION_LIBPATH . 'functions.inc.php');
$version = cleanvar($_GET['v']);
//as passed by triggers
$version = str_replace("v", "", $version);
if (!empty($version))
{
    header("Location: {$_SERVER['PHP_SELF']}#{$version}");
}

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');
include_once (APPLICATION_INCPATH . 'htmlheader.inc.php');
echo "<h2>Release Notes</h2>";
plugin_do('release_notes');

echo "<div id='help'>";
echo "<h4>This is a summary of the full release notes showing only the most important changes, for more detailed notes and the latest information on this release please <a href='http://sitracker.org/wiki/ReleaseNotes'>see the SiT website</a>:</h4>";

echo "<h3><a name='3.90'>v3.90 beta 1</a></h3>";
echo "<div>";
echo "<p>This is a beta edition, which means that bugs (and even serious bugs) are expected to exist. ";
echo "For this reason you should make backups often and be very careful when running this release in production.</p>";

echo "<p>Help us to make this software better by reporting bugs that you stumble across, see <a href='http://sitracker.org/wiki/Bugs'>http://sitracker.org/wiki/Bugs</a> and by discussing problems in <a href='http://sitracker.org/forum'>our forum</a>.</p>";

echo "<ul>";
echo "<li><a href='http://sitracker.org/wiki/ReleaseNotes390'>See the online release notes for information about this beta release</a></li>";
echo "</ul>";
echo "</div>";
echo "</div>";

plugin_do('release_notes_content');
echo "</div>";

include_once (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>