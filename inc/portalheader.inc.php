<?php
// portalheader.inc.php - Header html to be included at the top of portal pages
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// This Page Is Valid XHTML 1.0 Transitional! 27Oct05
//
// Authors: Ivan Lucas <ivanlucas[at]users.sourceforge.net>,
//          Kieran Hogg <kieran[at]sitracker.org>

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


// Use session language if available, else use default language
if (!empty($_SESSION['lang'])) $lang = $_SESSION['lang'];
else $lang = $CONFIG['default_i18n'];
$SYSLANG = $_SESSION['syslang'];
plugin_do('before_page');
echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n";
echo "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"{$lang}\" lang=\"{$lang}\">\n";
echo "<head>\n";
echo "<!-- SiT (Support Incident Tracker) - Support call tracking system\n";
echo "     Copyright (C) 2010-2014 The Support Incident Tracker Project\n";
echo "     Copyright (C) 2000-2009 Salford Software Ltd. and Contributors\n\n";
echo "     This software may be used and distributed according to the terms\n";
echo "     of the GNU General Public License, incorporated herein by reference. -->\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html;charset={$i18ncharset}\" />\n";
echo "<meta name=\"GENERATOR\" content=\"{$CONFIG['application_name']} {$application_version_string}\" />\n";
echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"/>\n";
echo "<title>";
if (isset($title))
{
    echo "$title - {$CONFIG['application_shortname']}";
}
else
{
    echo "{$CONFIG['application_name']}{$extratitlestring}";
}

echo "</title>\n";

if ($_SESSION['portalauth'] == TRUE)
{
    $theme = $_SESSION['userconfig']['theme'];
    $iconset = $_SESSION['userconfig']['iconset'];
}
else
{
    $theme = $CONFIG['portal_interface_style'];
    $iconset = $CONFIG['portal_iconset'];
}

if (empty($theme)) $theme = $CONFIG['portal_interface_style'];
if (empty($iconset)) $iconset = $CONFIG['portal_iconset'];

if (isset($refresh) && $refresh != 0)
{
   echo "<meta http-equiv='refresh' content='{$refresh}' />\n";
}
echo "<link rel='SHORTCUT ICON' href='{$CONFIG['application_webpath']}images/sit_favicon.png' />\n";
echo "<style type='text/css'>@import url('{$CONFIG['application_webpath']}styles/sitbase.css');</style>\n";
echo "<link rel='stylesheet' href='{$CONFIG['application_webpath']}styles/{$theme}/{$theme}.css' />\n";

echo "<script src='{$CONFIG['application_webpath']}scripts/prototype/prototype.js' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/sit.js.php' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/webtrack.js' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/calendar.js' type='text/javascript'></script>\n";
// To include a script for a single page, add the filename to the $pagescripts variable before including htmlheader.inc.php
if (is_array($pagescripts))
{
    foreach ($pagescripts AS $pscript)
    {
        echo "<script src='{$CONFIG['application_webpath']}{$pscript}' type='text/javascript'></script>\n";
    }
    unset($pagescripts, $pscript);
}

if (!empty($_SESSION['lang']) AND $_SESSION['lang'] != $CONFIG['default_i18n'])
{
    include (APPLICATION_I18NPATH . "{$_SESSION['lang']}.inc.php");
}
require (APPLICATION_LIBPATH . 'strings.inc.php');

// External variables
$page = clean_fixed_list($_REQUEST['page'], array('incident', 'incidents', 'close'));
$contractid = clean_int($_REQUEST['contractid']);

$filter = array('page' => $page);

plugin_do('html_head');
echo "</head>\n";

$pagnename = substr(end(explode('/', $_SERVER['PHP_SELF'])), 0, -4);
echo "<body id='portal_{$pagnename}_page' >";

plugin_do('page_start');
echo "<div id='masthead'><div id='masterheadcontent'>";
if (!empty($_SESSION['contactid']))
{
    echo "<div id='personaloptions'><a href='contactdetails.php'>";
    echo contact_realname($_SESSION['contactid']);
    echo ", ".contact_site($_SESSION['contactid']);
    echo "</a></div>";
}
echo "<h1 id='apptitle'>{$CONFIG['application_name']}</h1>";
echo "</div>";
echo "</div>\n";

if ($_SESSION['portalauth'] == TRUE OR ($_SERVER['PHP_SELF'] != 'kb.php'
    AND $CONFIG['portal_kb_enabled'] != 'Public'))
{

    echo html_hmenu($hmenu);

    echo "<div id='mainframe'>";
}

$headerdisplayed = TRUE; // Set a variable so we can check to see if the header was included

?>
