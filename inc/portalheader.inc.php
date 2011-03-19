<?php
// portalheader.inc.php - Header html to be included at the top of portal pages
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
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
echo "     Copyright (C) 2010-2011 The Support Incident Tracker Project\n";
echo "     Copyright (C) 2000-2009 Salford Software Ltd. and Contributors\n\n";
echo "     This software may be used and distributed according to the terms\n";
echo "     of the GNU General Public License, incorporated herein by reference. -->\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html;charset={$i18ncharset}\" />\n";
echo "<meta name=\"GENERATOR\" content=\"{$CONFIG['application_name']} {$application_version_string}\" />\n";
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
//some css for the KB
echo "<style type='text/css'>
    .kbprivate
    {
        color: #FFFFFF;
/*         background-color: #FF3300; */
        background-image:url({$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/private.png);
        background-repeat: no-repeat;
        background-position: top right;
        border: 2px dashed #FF3300;
        margin: 3px 0px;
        padding: 0px 2px;
    }

    .kbrestricted
    {
        background-color: #DDDDDD;
        background-image:url({$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/private.png);
        background-repeat: no-repeat;
        background-position: top right;
    }

    .keykbprivate
    {
        color: #FFFFFF;
        background-color: #FF3300;
    }

    .keykbrestricted
    {
        background-color: #DDDDDD;
    }

    </style>";

echo "<link rel='SHORTCUT ICON' href='{$CONFIG['application_webpath']}images/sit_favicon.png' />\n";
echo "<style type='text/css'>@import url('{$CONFIG['application_webpath']}styles/sitbase.css');</style>\n";
if ($_SESSION['portalauth'] == TRUE)
{
    $theme = $_SESSION['userconfig']['theme'];
    $iconset = $_SESSION['userconfig']['iconset'];
}
else
{
    $theme = $CONFIG['default_interface_style'];
    $iconset = $CONFIG['default_iconset'];
}

if (empty($theme)) $theme = $CONFIG['default_interface_style']; 
if (empty($iconset)) $iconset = $CONFIG['default_iconset'];
echo "<link rel='stylesheet' href='{$CONFIG['application_webpath']}styles/{$theme}/{$theme}.css' />\n";

echo "<script src='{$CONFIG['application_webpath']}scripts/prototype/prototype.js' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/sit.js.php' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/webtrack.js' type='text/javascript'></script>\n";
// To include a script for a single page, add the filename to the $pagescripts variable before including htmlheader.inc.php
if (is_array($pagescripts))
{
    foreach ($pagescripts AS $pscript)
    {
        echo "<script src='{$CONFIG['application_webpath']}scripts/{$pscript}' type='text/javascript'></script>\n";
    }
    unset($pagescripts, $pscript);
}

plugin_do('html_head');
echo "</head>\n";
echo "<body>\n";
echo "<div id='masthead'><h1 id='apptitle'>{$CONFIG['application_name']}</h1></div>\n";
if (!empty($_SESSION['lang']) AND $_SESSION['lang'] != $CONFIG['default_i18n'])
{
    include (APPLICATION_I18NPATH . "{$_SESSION['lang']}.inc.php");
}
require (APPLICATION_LIBPATH . 'strings.inc.php');

// External variables
$page = cleanvar($_REQUEST['page']);
$contractid = clean_int($_REQUEST['contractid']);

$filter = array('page' => $page);

////find contracts
//$sql = "SELECT DISTINCT m.*, p.name, ";
//$sql .= "(m.incident_quantity - m.incidents_used) AS availableincidents ";
//$sql .= "FROM `{$dbSupportContacts}` AS sc, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p ";
//$sql .= "WHERE m.product=p.id ";
//$sql .= "AND ((sc.contactid='{$_SESSION['contactid']}' AND sc.maintenanceid=m.id) ";
//$sql .= "OR m.allcontactssupported = 'yes') ";
//$sql .= "AND (expirydate > (UNIX_TIMESTAMP(NOW()) - 15778463) OR expirydate = -1) ";
//$sql .= "ORDER BY expirydate DESC";
//$contractresult = mysql_query($sql);
//if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
//$numcontracts = mysql_num_rows($contractresult);

if ($_SESSION['portalauth'] == TRUE OR ($_SERVER['PHP_SELF'] != 'kb.php'
    AND $CONFIG['portal_kb_enabled'] != 'Public'))
{
    echo "<div id='menu'>\n";
    echo "<ul id='menuList'>\n";
    echo "<li><a href='index.php'>{$strIncidents}</a></li>";
    if (sizeof($_SESSION['entitlement']) == 1 OR !$CONFIG['portal_creates_incidents'])
    {
        // This is needed so the code will unserialize
        $contractid = unserialize($_SESSION['entitlement'][0])->id;
        echo "<li><a href='new.php";
        if ($CONFIG['portal_creates_incidents'])
        {
            echo "?contractid={$contractid}";
        }
        echo "'>{$strNewIncident}</a></li>";
    }
    else
    {
        echo "<li><a href='entitlement.php'>{$strEntitlement}</a></li>";
    }
    $sql = "SELECT COUNT(docid) FROM `{$dbKBArticles}`";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($countkb) = mysql_fetch_row($result);
    if ($CONFIG['kb_enabled'] != FALSE AND $CONFIG['portal_kb_enabled'] !== 'Disabled' AND $countkb > 0)
    {
        echo "<li><a href='kb.php'>{$strKnowledgeBase}</a></li>";
    }

    if ($_SESSION['usertype'] == 'admin')
    {
        echo "<li><a href='admin.php'>{$strAdmin}</a></li>";
    }

    plugin_do('portal_header_menu');

    echo "<li><a href='help.php'>{$strHelp}</a></li>";

    echo "<li><a href='../logout.php'>{$strLogout}</a></li>";
    echo "</ul>";

    echo "<div id='portaluser'><a href='contactdetails.php'>";
    echo contact_realname($_SESSION['contactid']);
    echo ", ".contact_site($_SESSION['contactid']);
    echo "</a>";
    echo "</div>";
    echo "</div>";
    echo "<div id='mainframe'>";
}

$headerdisplayed = TRUE; // Set a variable so we can check to see if the header was included

?>