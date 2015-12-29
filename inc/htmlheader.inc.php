<?php
// htmlheader.inc.php - Header html to be included at the top of pages
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// This Page Is Valid XHTML 1.0 Transitional! 27Oct05

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

// Use session language if available, else use default language
if (!empty($_SESSION['lang'])) $lang = $_SESSION['lang'];
else $lang = $CONFIG['default_i18n'];
plugin_do('before_page');
echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n";
echo "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"{$lang}\" lang=\"{$lang}\"";
if (isset($i18ndirection) AND !empty($i18ndirection))
{
    echo " dir=\"{$i18ndirection}\"";
}
echo ">\n";
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
echo "<link rel='SHORTCUT ICON' href='{$CONFIG['application_webpath']}images/sit_favicon.png' />\n";
if (!empty($rssfeedurl))
{
    if (empty($rssfeedtitle)) $rssfeedtitle = "{$CONFIG['application_shortname']}";
    echo "<link rel='alternate' type='application/rss+xml' title='{$rssfeedtitle}' href=\"{$rssfeedurl}\" />\n";
}
echo "<style type='text/css'>@import url('{$CONFIG['application_webpath']}styles/sitbase.css');</style>\n";
if ($_SESSION['auth'] == TRUE)
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
// To include a CSS file for a single page, add the filename to the $pagecss variable before including htmlheader.inc.php
if (is_array($pagecss))
{
    foreach ($pagecss AS $pcss)
    {
        echo "<link rel='stylesheet' href='{$CONFIG['application_webpath']}{$pcss}' />\n";
    }
    unset($pagecss, $pcss);
}

if (isset($refresh) && $refresh != 0)
{
   echo "<meta http-equiv='refresh' content='{$refresh}' />\n";
}
if ($_SESSION['auth'] == TRUE)
{
    echo "<script src='{$CONFIG['application_webpath']}scripts/prototype/prototype.js' type='text/javascript'></script>\n";
    echo "<script src='{$CONFIG['application_webpath']}scripts/scriptaculous/scriptaculous.js?load=effects,dragdrop,controls' type='text/javascript'></script>\n";
    echo "<script src='{$CONFIG['application_webpath']}scripts/sit.js.php' type='text/javascript'></script>\n";
    echo "<script src='{$CONFIG['application_webpath']}scripts/webtrack.js' type='text/javascript'></script>\n";
    echo "<script src='{$CONFIG['application_webpath']}scripts/activity.js' type='text/javascript'></script>\n";
    // To include a script for a single page, add the filename to the $pagescripts variable before including htmlheader.inc.php
    if (is_array($pagescripts))
    {
        foreach ($pagescripts AS $pscript)
        {
            echo "<script src='{$CONFIG['application_webpath']}{$pscript}' type='text/javascript'></script>\n";
        }
        unset($pagescripts, $pscript);
    }
    // javascript popup date library
    echo "<script src='{$CONFIG['application_webpath']}scripts/calendar.js' type='text/javascript'></script>\n";
    echo "<link rel='search' type='application/opensearchdescription+xml' title='{$CONFIG['application_shortname']} Search' href='{$CONFIG['application_webpath']}opensearch.php' />\n";
}
plugin_do('html_head');
echo "</head>\n";

$pagnename = substr(end(explode('/', $_SERVER['PHP_SELF'])), 0, -4);

echo "<body id='{$pagnename}_page'>\n";

plugin_do('page_start');
echo "<div id='masthead'>";
echo "<div id='mastheadcontent'>";
if (isset($sit[0]) && $sit[0] != '')
{
    echo "<div id='personaloptions'>";
    echo "<a href='user_profile_edit.php'>";
    if (!empty($_SESSION['realname']))
    {
        echo $_SESSION['realname'];
    }
    else
    {
        echo $_SESSION['username'];
    }
    echo "</a>";

    if (user_permission($sit[2], PERM_MYSTATUS_SET)) // edit my status
    {
        echo " | <span id='userstatus'>";
        echo userstatus_summaryline();
        echo " <a href='javascript:void(0)' onclick='show_status_drop_down()' onblur=\"$('status_drop_down').blur();\">";
        echo icon('configure', 12, $strSetYourStatus)."</a></span>";
        echo "<span id='status_drop_down' style='display:none;'>";
        echo userstatus_bardrop_down("status", user_status($sit[2])) . help_link("SetYourStatus");
        echo "</span>";
    }
    echo " | <a href='logout.php'>{$strLogout}</a></div>";
}

echo "<h1 id='apptitle'>{$CONFIG['application_name']}</h1>";
if (isset($sit[0]) && $sit[0] != '')
{
    echo "<div id='topsearch'>";
    echo "<form name='jumptoincident' action='{$CONFIG['application_webpath']}search.php' method='get'>";
    echo "<input type='text' name='q' id='searchfield' size='30' value='{$strIncidentNumOrSearchTerm}'
    onblur=\"if ($('searchfield').value == '') { if (!isIE) { $('searchfield').style.color='#888;'; } $('searchfield').value='{$strIncidentNumOrSearchTerm}';}\"
    onfocus=\"if ($('searchfield').value == '{$strIncidentNumOrSearchTerm}') { if (!isIE) { $('searchfield').style.color='#000;'; } $('searchfield').value=''; }\"
    onclick='clearjumpto()'/> ";
    // echo "<input type='image' src='{$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/find.png' alt='{$strGo}' onclick='jumpto()' />";
    echo "</form>";
    echo "</div>";
}
echo "</div></div>\n";

// Show menu if logged in
if (isset($sit[0]) && $sit[0] != '')
{
    // Build a heirarchical top menu
    $hmenu;
    if (!is_array($hmenu))
    {
        trigger_error("Menu array not defined", E_USER_ERROR);
    }
    echo html_hmenu($hmenu);

}

if (!isset($refresh) AND $_SESSION['auth'] === TRUE)
{
    global $db;
    //update last seen (only if this is a page that does not auto-refresh)
    $lastseensql = "UPDATE LOW_PRIORITY `{$GLOBALS['dbUsers']}` SET lastseen=NOW() WHERE id='{$_SESSION['userid']}' LIMIT 1";
    mysqli_query($db, $lastseensql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
}

if (isset($sit[0]) && $sit[0] != '')
{
    // Check this is current
    $sql = "SELECT version FROM `{$dbSystem}` WHERE id = 0";
    $versionresult = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    list($dbversion) = mysqli_fetch_row($versionresult);
    if ($dbversion < $application_version)
    {
        $msg = "<strong>IMPORTANT</strong> The SiT database schema needs to be updated";
        if (user_permission($sit[2], PERM_ADMIN))
        {
            $msg .= " from v{$dbversion} to v{$application_version}<br />";
            $msg2 = "Visit <a href='setup.php'>Setup</a> to update the schema.";
        }
        echo user_alert($msg, E_USER_ERROR);
        echo user_alert($msg2, E_USER_NOTICE);
    }

    if (user_permission($sit[2], PERM_ADMIN))
    {
        // Check if scheduler is running (bug 108)
        $failure = 0;

        $schedulersql = "SELECT `interval`, `lastran` FROM {$dbScheduler} WHERE status='enabled'";
        $schedulerresult = mysqli_query($db, $schedulersql);
        if (mysqli_error($db)) debug_log("scheduler_check: Failed to fetch data from the database", TRUE);

        while ($schedule = mysqli_fetch_object($schedulerresult))
        {
            $sqlinterval = ("$schedule->interval");
            $sqllastran = mysql2date("$schedule->lastran");
            $dateresult = $sqlinterval + $sqllastran + 60;
            if ($dateresult < date('U'))
            {
                $failure ++;
            }
        }
        $num = mysqli_num_rows($schedulerresult);
        $num = $num / 2;
        if ($failure > $num)
        {
            user_notice(sprintf("{$strSchedulerNotRunning} <a target='_blank' href='http://sitracker.org/wiki/Scheduler'> {$strTheDocumentation} </a>"), WARNING_NOTICE_TYPE, 'session');
        }
    }

    // Check users email address
    if (empty($_SESSION['email']))
    {
        echo user_notice("{$strInvalidEmailAddress} - <a href='user_profile_edit.php'>{$strEditEmail}</a>", NORMAL_NOTICE_TYPE, 'session');
    }

    //display (trigger) notices
    $noticesql = "SELECT * FROM `{$GLOBALS['dbNotices']}` ";
    // Don't show more than 20 notices, saftey cap
    $noticesql .= "WHERE userid={$sit[2]} ORDER BY timestamp DESC LIMIT 20";
    $noticeresult = mysqli_query($db, $noticesql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    if (mysqli_num_rows($noticeresult) > 0)
    {
        echo "<div id='noticearea'>\n";
        $keys = array_keys($_GET);

        foreach ($keys AS $key)
        {
            if ($key != 'noticeid')
            {
                $url .= "&amp;{$key}=".cleanvar($_GET[$key]);
            }
        }

        while ($notice = mysqli_fetch_object($noticeresult))
        {
            $notice->text = bbcode($notice->text);
            //check for the notice types
            if ($notice->type == TRIGGER_NOTICE_TYPE)
            {
                $class = 'trigger';
            }
            elseif ($notice->type == WARNING_NOTICE_TYPE)
            {
                $class = 'warning';
            }
            elseif ($notice->type == CRITICAL_NOTICE_TYPE)
            {
                echo "<div class='error'><p class='error'>";
                echo $notice->text;

                if ($notice->resolutionpage)
                {
                    $redirpage = $CONFIG['application_webpath'].$notice->resolutionpage;
                }
            }
            else
            {
                $class = 'info';
            }

            echo "<div class='noticebar {$class}' id='notice{$notice->id}'><p class='{$class}'>";
            if ($notice->type == TRIGGER_NOTICE_TYPE)
            {
                echo "<span><a href='{$CONFIG['application_webpath']}notifications.php'>";
                echo "{$strSettings}</a> | ";
                echo "<a href='javascript:void(0);' onclick=\"dismissNotice({$notice->id}, {$_SESSION['userid']})\">";
                echo "{$strDismiss}</a></span>";
            }
            else
            {
                echo "<span><a href='javascript:void(0);' onclick=\"dismissNotice({$notice->id}, {$_SESSION['userid']})\">";
                echo "{$strDismiss}</a></span>";
            }

            if (mb_substr($notice->text, 0, 4) == '$str')
            {
                $v = mb_substr($notice->text, 1);
                echo $GLOBALS[$v];
            }
            else
            {
                echo $notice->text;
            }

            if (!empty($notice->link))
            {
                echo " - <a href=\"{$notice->link}\">";
                if (mb_substr($notice->linktext, 0, 3) == 'str')
                {
                    echo $GLOBALS[$notice->linktext];
                }
                else
                {
                    echo $notice->linktext;
                }
                echo "</a>";
            }

            echo "<small>";
            echo "<em> (".format_date_friendly(mysql2date($notice->timestamp)).")</em>";
            echo "</small></p></div>\n";
        }

        if (mysqli_num_rows($noticeresult) > 1)
        {
            echo "\n<p id='dismissall'><a href='javascript:void(0);' onclick=\"dismissNotice('all', {$_SESSION['userid']})\">{$strDismissAll}</a></p>\n";
        }
        echo "</div>\n";
    }
}
$headerdisplayed = TRUE; // Set a variable so we can check to see if the header was included

echo "<div id='mainframe'>";

?>