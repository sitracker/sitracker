<?php
// releasenotes.php - Release notes summary
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.


$permission = 0;
require ('core.php');
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

echo "<div id='help'>";
echo "<h4>This is a summary of the full release notes showing only the most important changes, for more detailed notes and the latest information on this release please <a href='http://sitracker.org/wiki/ReleaseNotes'>see the SiT website</a>:</h4>";

echo "<h3><a name='3.60'>v3.60 LTS</a></h3>";
echo "<div>";
echo "<p>This is a Long Term Support edition, which means that we will be providing Technical Support and bug fixes for this ";
echo "release (as v3.61, v3.62... etc.) until around the time that v4.1 is released. Security fixes will be made available ";
echo "for longer than that - at least until v4.2 is released! We've decided to do this so that we can concentrate our main development ";
echo "efforts on exciting new features for 4.x without ignoring existing users who are currently using the 3.x versions of SiT! ";
echo "and to provide a stable upgrade path.</p>";
echo "<ul>";
echo "<li>Many minor and not-so-minor enhancements and bug fixes </li>";
echo "<li>Updated Slovenian sl-SL translation by Alen Grižonič </li>";
echo "<li>Updated Danish (da-DK) translation by Carsten Jensen </li>";
echo "<li>Updated Russian (ru-RU) translation by sancho78rus</li>";
echo "<li>Support for daylight savings time (DST)</li>";
echo "<li>More plugin contexts</li>";
echo "</ul>";
echo "</div>";

echo "<h3><a name='3.51'>v3.51</a></h3>";
echo "<div>";
echo "<ul>";
echo "<li>An important security fix (<a href='http://bugs.sitracker.org/view.php?id=1047'>Bug 1047</a>) for those people using the LDAP feature</li>";
echo "<li>Many minor and not-so-minor enhancements and bug fixes </li>";
echo "<li>Updates to the French (fr-FR) translation by Guillaume Clement, 100% of SiT! strings are now translated to French </li>";
echo "<li>Updates to the Dutch (nl-NL) translation by Arko Kroonen, 100% of SiT! strings are now translated to Dutch </li>";
echo "<li>Updated Danish (da-DK) translation by Carsten Jensen </li>";
echo "</ul>";
echo "</div>";


echo "<h3><a name='3.50'>v3.50</a></h3>";
echo "<div>";
echo "<ul>";
echo "<li>Improved LDAP authentication now works with Active Directory, eDirectory and more.</li>";
echo "<li>Improved permissions management, permissions are now grouped by category to make it easier to find the permissions you are interested in changing. It is also now possible to add your own Roles in addition to the three built in roles.</li>";
echo "<li>Improved internationalisation and updated Translations: French (Gilles Grenier), Danish (Carsten Jensen), Italian (Silvio Bogetto). Many more of the strings within SiT! are now able to be translated, we're working hard towards 100% internationalisation, we need your help with this, please report and any hardcoded English strings that you find.</li>";
echo "<li>Improved support for Inbound Email, this feature is no-longer being treated as experimental as in previous releases, so please feel free to give it a try.</li>";
echo "<li>Improved UTF-8 support, better handling of international characters.</li>";
echo "<li>SiT! was previously limited to supporting 128 users, this restriction has been removed.</li>";
echo "<li>Plugin improvements: Plugins can now be internationalised and SiT! strings can now be overridden by plugins.</li>";
echo "<li>Many minor and not-so-minor enhancements and bug fixes</li>";
echo "</ul>";
echo "</div>";

echo "<h3><a name='3.45'>v3.45</a></h3>";
echo "<div>";
echo "<ul>
<li>A long awaited configuration/settings interface</li>
<li>Much improved Inbound Email parsing and connection/error handling</li>
<li>Allow choice of 'inbox' and archive folder for inbound email</li>
<li>End of htdocs! We've rearranged the file layout of SiT; the 'htdocs' folder is no more, making it easier to deploy and to give you a nicer URL</li>
<li>Easier setup - no need to set the application path or include path any more and setup guides you through creating a directory to store attachments</li>
<li>Added ability for portal users to create emails in the holding queue rather than incidents</li>
<li>Added stub translations for: Catalan and Slovenian</li>
<li>Added partial translations for Russian and Mexican Spanish</li>
<li>Updated and improved German and Italian translations</li>
<li>Improved i18n</li>
<li>Improvements to billing</li>
<li>Debug Logging</li>
<li>When emails are received for closed incidents, there is now an option to reopen and add it straight from the holding queue</li>
<li>The list of available languages is now configurable and new languages can be added on-the-fly</li></ul>";
echo "</div>";

echo "<h3><a name='3.41'>v3.41</a></h3>";
echo "<div>";
echo "<ul><li>This was bugfix release and does not contain any new features over v3.40.</li></ul>";
echo "</div>";


echo "<h3><a name='3.40'>v3.40</a></h3>";
echo "<div>";

echo "<ul>
<li>New Danish (da-DK) Translation by Carsten Jensen</li>
<li>Portuguese (pt-PT) Translation by José Tomás</li>
<li>Updated Spanish (es-ES) Translation by Carlos Perez</li>
<li>Ability to receive incoming mail from a POP or IMAP email account</li>
<li>Gravatar support</li>
<li>Billing - highly customisable framework for charging based on incidents and time worked on incidents</li>
<li>Inventory - a cataloguing system for collecting information on remote access and/or assets (servers, PCs etc)</li>
<li>The help menu now has a link to 'Get Help Online' which takes the user to the Documentation page of the wiki, this was done to make it easier for users to find the latest help and also to make it easier for contributors to expand the documentation and translate it into other languages.</li>
</ul>";
echo "</div>";

echo "</div>";

include_once (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>