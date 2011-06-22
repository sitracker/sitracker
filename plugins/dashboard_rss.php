<?php
// dashboard_rss.php - Display your rss feeds on the dashboard
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paulheaney[at]users.sourceforge.net>

$dashboard_rss_version = 4;


function dashboard_rss($dashletid)
{
    global $sit, $CONFIG, $iconset;

    echo dashlet('rss', $dashletid, icon('feed-icon', 16), $GLOBALS['strFeeds'], '', $content);
}

function dashboard_rss_install()
{
    global $CONFIG;

    $schema = "CREATE TABLE `{$CONFIG['db_tableprefix']}dashboard_rss` (
    `owner` smallint(6) NOT NULL,
    `url` varchar(255) NOT NULL,
    `items` int(5) default NULL,
    `enabled` enum('true','false') NOT NULL,
    KEY `owner` (`owner`,`url`)
    ) ENGINE = MYISAM ;
    ";
    $result = mysql_query($schema);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_error())
    {
        echo "<p>Dashboard RSS failed to install, please run the following SQL statement on the SiT database to create the required schema.</p>";
        echo "<pre>{$schema}</pre>";
        $res = FALSE;
    }
    else $res = TRUE;

    $datasql = "INSERT INTO `{$CONFIG['db_tableprefix']}dashboard_rss` (`owner`, `url`, `items`, `enabled`) VALUES (1, 'http://sourceforge.net/export/rss2_projnews.php?group_id=160319', 3, 'true');";
    $result = mysql_query($datasql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    return $res;
}


function dashboard_rss_display($dashletid)
{
    global $CONFIG, $dbDashboardRSS, $sit, $lib_path;

    $iconset = $_SESSION['userconfig']['iconset'];
    /*
    Originally from dashboard/dashboard.inc.php
    */

    require_once(APPLICATION_LIBPATH . 'magpierss/rss_fetch.inc');

    $sql = "SELECT url, items FROM `{$CONFIG['db_tableprefix']}dashboard_rss` WHERE owner = {$sit[2]} AND enabled = 'true'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    define ('MAGPIE_CACHE_ON',TRUE);
    define ('MAGPIE_CACHE_DIR', $CONFIG['attachment_fspath'].'feeds');
    define ('MAGPIE_OUTPUT_ENCODING', $i18ncharset);

    $feedallowedtags = '<img><strong><em><br><p>';

    if (mysql_num_rows($result) > 0)
    {
        while ($row = mysql_fetch_row($result))
        {
            $url = $row[0];
            if ($rss = fetch_rss( $url ))
            {
                // if ($CONFIG['debug']) echo "<pre>".print_r($rss,true)."</pre>";
                echo "<table>";
                echo "<tr><th><span style='float: right;'><a href='".htmlspecialchars($url)."'>";
                echo "<img src='{$CONFIG['application_webpath']}images/icons/{$iconset}/12x12/feed-icon.png' alt='Feed Icon' />";
                echo "</a></span>";
                echo "<a href='{$rss->channel['link']}' class='direct info'>{$rss->channel['title']}";
                if (!empty($rss->image['url']) OR !empty($rss->channel['description']) OR !empty($rss->channel['icon']))
                {
                    echo "<span>";
                    if (!empty($rss->image['url'])) echo "<img src='{$rss->image['url']}' alt='{$rss->image['title']}' style='float: right; margin-right: 2px; margin-left: 5px; margin-top: 2px;' />";
                    elseif (!empty($rss->channel['icon'])) echo "<img src='{$rss->channel['icon']}' style='float: right; margin-right: 2px; margin-left: 5px; margin-top: 2px;' />";
                    echo "{$rss->channel['description']}</span>";
                }
                echo "</a>";
                echo "</th></tr>\n";
                $counter = 0;
                foreach ($rss->items as $item)
                {
                    // echo "<pre>".print_r($item,true)."</pre>";
                    echo "<tr><td>";
                    echo "<a href='{$item['link']}' class='info'>{$item['title']}";
                    if ($rss->feed_type == 'RSS')
                    {
                        if (!empty($item['pubdate'])) 
                        {
                            $itemdate = strtotime($item['pubdate']);
                        }
                        elseif (!empty($item['dc']['date'])) 
                        {
                            $itemdate = strtotime($item['dc']['date']);
                        }
                        else 
                        {
                            $itemdate = '';
                        }
                        $d = strip_tags($item['description'],$feedallowedtags);
                    }
                    elseif ($rss->feed_type == 'Atom')
                    {
                        if (!empty($item['issued'])) 
                        {
                            $itemdate = strtotime($item['issued']);
                        }
                        elseif (!empty($item['published'])) 
                        {
                            $itemdate = strtotime($item['published']);
                        }
                        $d = strip_tags($item['atom_content'],$feedallowedtags);
                    }
                    if ($itemdate > 10000) 
                    {
                        $itemdate = ldate($CONFIG['dateformat_datetime'], $itemdate);
                    }
                    echo "<span>";
                    if (!empty($itemdate)) 
                    {
                        echo "<strong>{$itemdate}</strong><br />";
                    }
                    echo "{$d}</span></a></td></tr>\n";
                    $counter++;
                    if (($row[1] > 0) AND $counter > $row[1]) break;
                }
                echo "</table>\n";
            }
            else
            {
                echo "Error: It's not possible to get $url...";
            }
        }
    }
    else
    {
        echo user_alert($GLOBALS['strNoRecords'], E_USER_NOTICE);
    }
}


function dashboard_rss_edit($dashletid)
{
    global $CONFIG, $sit;

    $action = $_REQUEST['editaction'];

    switch ($action)
    {
        case 'new':
            echo "<h2>".icon('feed-icon', 32)." {$GLOBALS['strAddRSSAtomFeed']}</h2>";
            echo "<form id='dashrssaddform' action='{$_SERVER['PHP_SELF']}?action=do_new' method='post'>";
            echo "<table class='vertical'>";
            echo "<tr><td><label>".icon('feed-icon', 12, $GLOBALS['strFeedIcon'])." ";
            echo "{$GLOBALS['strRSSAtomURL']}: <input type='text' name='url' size='45' /></label></td></tr>\n";
            echo "<tr><td><label>{$GLOBALS['strDisplay']}: <input type='text' name='items' size='3' value='0' /></label> ({$GLOBALS['str0MeansUnlimited']})</td></tr>";
            echo "</table>";
            // <input name='submit' type='submit' value='{$GLOBALS['strNew']}' />
            echo "<p align='center'>".dashlet_link('rss', $dashletid, $GLOBALS['strAdd'], 'save', array('editaction'=>'do_new'), false, 'dashrssaddform')."</p>";
            echo "</form>";
            break;
        case 'do_new':
            $url = cleanvar($_REQUEST['url']);
            $enable = cleanvar($_REQUEST['enable']);
            $items = cleanvar($_REQUEST['items']);
            $sql = "INSERT INTO `{$CONFIG['db_tableprefix']}dashboard_rss` (owner, url, items, enabled) VALUES ({$sit[2]},'{$url}','{$items}','true')"; //SET enabled = '{$enable}' WHERE url = '{$url}' AND owner = {$sit[2]}";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

            if (!$result)
            {
                echo "<p class='error'>{$GLOBALS['strFailed']}</p>";
            }
            else
            {
                echo "<p>{$GLOBALS['strAddedSuccessfully']}</p>";
                echo dashlet_link('rss', $dashletid, $GLOBALS['strBackToList'], '', '', TRUE);
            }
            break;
        case 'edit':
            $url = cleanvar(urldecode($_REQUEST['url']));
            $sql = "SELECT * FROM `{$CONFIG['db_tableprefix']}dashboard_rss` WHERE owner = {$sit[2]} AND url = '{$url}' LIMIT 1 ";
            if ($CONFIG['debug']) $dbg .= print_r($sql,true);
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            if (mysql_num_rows($result) > 0)
            {
                $feed = mysql_fetch_object($result);
                if ($feed->items=='')
                {
                    $feed->items=0;
                }

                echo "<h2>".icon('feed-icon', 32)." {$GLOBALS['strEditRSSAtomFeed']}</h2>";
                echo "<form id='dashrsseditform' action='{$_SERVER['PHP_SELF']}?action=do_edit' method='post'>";
                echo "<table class='vertical'>";
                echo "<tr><td><label><img src='{$CONFIG['application_webpath']}images/icons/{$iconset}/12x12/feed-icon.png' alt='Feed Icon' /> ";
                echo "{$GLOBALS['strRSSAtomURL']}: <input type='text' name='url' size='45' value='{$feed->url}' /></label></td></tr>\n";
                echo "<tr><td><label>{$GLOBALS['strDisplay']}: <input type='text' name='items' size='3' value='{$feed->items}' /></label> ({$GLOBALS['str0MeansUnlimited']})</td></tr>";
                echo "</table>";
                echo "<input type='hidden' name='oldurl' size='45' value='{$feed->url}' />";
                echo "<p align='center'>".dashlet_link('rss', $dashletid, $GLOBALS['strSave'], 'save', array('editaction'=>'do_edit'), false, 'dashrsseditform')."</p>";
                echo "</form>";
            }
            else
            {
                echo "<p class='error'>{$GLOBALS['strNoRecords']}</p>";
            }
            break;
        case 'do_edit':
            $url = cleanvar($_REQUEST['url']);
            $oldurl = cleanvar($_REQUEST['oldurl']);
            $items = cleanvar($_REQUEST['items']);
            $sql = "UPDATE `{$CONFIG['db_tableprefix']}dashboard_rss` SET url = '{$url}', items = '{$items}' WHERE url = '{$oldurl}' AND owner = {$sit[2]}";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

            if (!$result)
            {
                echo "<p class='error'>{$GLOBALS['strFailed']}</p>";
            }
            else
            {
                echo "<p>{$GLOBALS['strSuccess']}</p>";
                echo dashlet_link('rss', $dashletid, $GLOBALS['strBackToList'], '', '', TRUE);
            }
            break;
        case 'enable':
            $url = urldecode(cleanvar($_REQUEST['url']));
            $enable = cleanvar($_REQUEST['enable']);
            $sql = "UPDATE `{$CONFIG['db_tableprefix']}dashboard_rss` SET `enabled` = '{$enable}' WHERE `url` = '{$url}' AND `owner` = {$sit[2]}";
            mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

            if (mysql_affected_rows() < 1) html_redirect("edit_rss_feeds.php", FALSE, "Changed enabled state failed");
            if (mysql_affected_rows() < 1)
            {
                echo "<p class='error'>{$GLOBALS['strFailed']}</p>";
            }
            else
            {
                echo "<p>{$GLOBALS['strSuccess']}</p>";
                echo dashlet_link('rss', $dashletid, $GLOBALS['strBackToList'], '', '', TRUE);
            }
            break;
        case 'delete':
            $url = $_REQUEST['url'];
            $enable = $_REQUEST['enable'];
            $sql = "DELETE FROM `{$CONFIG['db_tableprefix']}dashboard_rss` WHERE url = '{$url}' AND owner = {$sit[2]}";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

            if (!$result)
            {
                echo "<p class='error'>{$GLOBALS['strFailed']}</p>";
            }
            else
            {
                echo "<p>{$GLOBALS['strSuccess']}</p>";
                echo dashlet_link('rss', $dashletid, $GLOBALS['strBackToList'], '', '', TRUE);
            }
            break;
        default:
            echo "<h2>".icon('feed-icon', 32)." {$GLOBALS['strEditRSSAtomFeed']}</h2>";

            $sql = "SELECT * FROM `{$CONFIG['db_tableprefix']}dashboard_rss` WHERE owner = {$sit[2]}";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

            if (mysql_num_rows($result) > 0)
            {
                echo "<table align='center'>\n";
                echo "<tr><th>URL</th><th>{$GLOBALS['strDisplay']}</th><th>{$GLOBALS['strEnabled']}</th><th>{$GLOBALS['strOperation']}</th></tr>\n";
                $shade = 'shade1';
                while ($obj = mysql_fetch_object($result))
                {
                    if ($obj->enabled == "true")
                    {
                        $opposite = "false";
                    }
                    else
                    {
                        $opposite = "true";
                    }

                    $urlparts = parse_url($obj->url);
                    if ($obj->enabled == 'false')
                    {
                        $shade = 'expired';
                    }

                    echo "<tr class='$shade'><td align='left'><a href=\"".htmlentities($obj->url,ENT_NOQUOTES, $GLOBALS['i18ncharset'])."\">";
                    echo icon('feed-icon', 12, $strFeedIcon);
                    echo "</a> <a href=\"{$obj->url}\">{$urlparts['host']}</a></td>";
                    echo "<td>";
                    if ($obj->items >= 1)
                    {
                        echo "{$obj->items}";
                    }
                    else
                    {
                        echo $GLOBALS['strUnlimited'];
                    }

                    echo "</td>";
                    echo "<td>".dashlet_link('rss', $dashletid, $obj->enabled, 'edit', array('editaction'=>'enable', 'enable'=>$opposite, 'url'=>urlencode($obj->url)))."</td>";
                    echo "<td>".dashlet_link('rss', $dashletid, $GLOBALS['strEdit'], 'edit', array('editaction'=>'edit', 'url'=>urlencode($obj->url)));
                    echo " | ".dashlet_link('rss', $dashletid, $GLOBALS['strRemove'], 'edit', array('editaction'=>'delete', 'url'=>urlencode($obj->url)));
                    echo "</td></tr>\n";
                    if ($shade == 'shade1') $shade = 'shade2';
                    else $shade = 'shade1';
                }
                echo "</table>\n";
            }
            else
            {
                echo "<p align='center'>{$GLOBALS['strNoFeedsCurrentlyPresent']}</p>";
            }

            echo "<p align='center'>".dashlet_link('rss', $dashletid, $GLOBALS['strAdd'], 'edit', array('editaction'=>'new'))."</p>";
            break;
    }

}



function dashboard_rss_upgrade()
{
    global $CONFIG;
    $upgrade_schema[2] = "
        -- INL 22Nov07
        ALTER TABLE `{$CONFIG['db_tableprefix']}dashboard_rss` ADD `items` INT( 5 ) NULL AFTER `url`;
    ";

    $upgrade_schema[3] = "
        -- INL 22May09
        ALTER TABLE `{$CONFIG['db_tableprefix']}dashboard_rss` CHANGE `owner` `owner` SMALLINT( 6 ) NOT NULL;";


    $upgrade_schema[4] = "
        -- CJ 14Mar10
        UPDATE `{$CONFIG['db_tableprefix']}dashboard_rss` SET url='http://sourceforge.net/export/rss2_projnews.php?group_id=160319' WHERE url='http://sourceforge.net/export/rss2_projfiles.php?group_id=160319'";

    return $upgrade_schema;


}

function dashboard_rss_get_version()
{
    global $dashboard_rss_version;
    return $dashboard_rss_version;
}


?>