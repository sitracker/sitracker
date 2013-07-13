<?php
// kb_rss.php - Output an RSS representation showing new kb articles
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2006-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

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

// $userid = cleanvar($_REQUEST['user']);

if (!is_numeric($userid))
{
    header("HTTP/1.1 403 Forbidden");
    echo "<html><head><title>403 Forbidden</title></head><body><h1>403 Forbidden</h1></body></html>\n";
    exit;
}

if (!empty($_SESSION['lang'])) $lang = $_SESSION['lang'];
else $lang = $CONFIG['default_i18n'];

// Feed stuff goes here (obviously) ;)
$sql = "SELECT * FROM `{$dbKBArticles}` ORDER BY docid DESC LIMIT 20";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

$count = 0;
$pubdate = $now;

$items = array();

while ($kbarticle = mysql_fetch_object($result))
{
    if (empty($kbarticle->title)) $kbarticle->title = $strUntitled;
    else $kbarticle->title = $kbarticle->title;
    $fi = new FeedItem();
    $fi->title = $kbarticle->title;
    $fi->author = $kbarticle->author;
    $fi->link = "{$CONFIG['application_uriprefix']}{$CONFIG['application_webpath']}kb_view_article.php?id={$kbarticle->docid}";
    $fi->description = "{$strKeywords}: {$kbarticle->keywords}";
    $fi->pubdate = mysql2date($kbarticle->published);
    $items[] = $fi;
}

$feed = new Feed();
$feed->title = "{$CONFIG['application_shortname']} {$strKnowledgeBase}: {$strArticlesPublishedRecently}";
$feed->feedurl = "{$CONFIG['application_uriprefix']}{$CONFIG['application_webpath']}kb.php?mode=RECENT";
$feed->description = "{$CONFIG['application_name']}: {$strKnowledgeBase} {$strFor} ".user_realname($userid)." ({$strActionNeeded})";
$feed->pubdate = $pubdate;
$feed->items = $items;

$feed->generate_feed();

?>