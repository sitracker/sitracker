<?php
// incidents_rss.php - Output an RSS representation of a users incident queue
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2006-2008 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//            Paul Heaney <paul[at]sitracker.org>

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This script requires no authentication
// The information it reveals should not be sensitive

$c = clean_dbstring($_GET['c']);
$salt = md5($CONFIG['db_password']);
$usql = "SELECT id FROM `{$dbUsers}` WHERE MD5(CONCAT(`username`, '{$salt}')) = '$c' LIMIT 1";
// $usql = "SELECT id FROM `{$dbUsers}` WHERE username = '$c' LIMIT 1";
$uresult = mysql_query($usql);

if ($uresult)
{
    list($userid) = mysql_fetch_row($uresult);
}

// $userid = clean_int($_REQUEST['user']);

if (!is_numeric($userid))
{
    header("HTTP/1.1 403 Forbidden");
    echo "<html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1></body></html>\n";
    exit;
}

$sql = "SELECT * FROM `{$dbIncidents}` WHERE (owner='$userid' OR towner='$userid') ";
$sql .= "AND (status!='".STATUS_CLOSED."') ORDER BY lastupdated DESC LIMIT 5";  // not closed
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

if (!empty($_SESSION['lang'])) $lang = $_SESSION['lang'];
else $lang = $CONFIG['default_i18n'];

$count = 0;
$pubdate = $now;

$items = array();

while ($incident = mysql_fetch_object($result))
{
    // Get Last Update
    list($update_userid, $update_type, $update_currentowner, 
        $update_currentstatus, $update_body, $update_timestamp, 
        $update_nextaction, $update_id) = incident_lastupdate($incident->id);

    if ($count == 0) $update_timestamp;

    $authorname = user_realname($update_userid);
    $author = user_email($update_userid)." (".$authorname. ")";

    $fi = new FeedItem();
    $fi->title = "[{$incident->id}] - {$incident->title} ({$update_type})";
    $fi->author = $author;
    $fi->link = "{$CONFIG['application_uriprefix']}{$CONFIG['application_webpath']}incident_details.php?id={$incident->id}";
    $fi->description = "{$strUpdated} ".date($CONFIG['dateformat_datetime'],$update_timestamp) ." {$strby} &lt;strong&gt;{$authorname}&lt;/strong&gt;. \n{$strStatus}: ".incidentstatus_name($update_currentstatus).". &lt;br /&gt;\n\n".strip_tags($update_body);
    $fi->pubdate =$update_timestamp; 
    
    $fi->guid = "{$CONFIG['application_uriprefix']}{$CONFIG['application_webpath']}incident_details.php?id={$incident->id}#{$update_id}";
    $count++;
    $items[] = $fi;
}

$feed = new Feed();
$feed->title = "{$CONFIG['application_shortname']} {$strIncidents}";
$feed->feedurl = "{$CONFIG['application_uriprefix']}{$CONFIG['application_webpath']}incident_details.php?id={$incident->id}";
$feed->description = "{$CONFIG['application_name']}: {$strIncidents} {$strFor} ".user_realname($userid)." ({$strActionNeeded})";
$feed->pubdate = $pubdate;
$feed->items = $items;

$feed->generate_feed();
?>