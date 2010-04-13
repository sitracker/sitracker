<?php
// htmlheader.inc.php - Header html to be included at the top of pages
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
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
echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n";
echo "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"{$lang}\" lang=\"{$lang}\">\n";
echo "<head>\n";
echo "<!-- SiT (Support Incident Tracker) - Support call tracking system\n";
echo "     Copyright (C) 2010 The Support Incident Tracker Project\n";
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
if (empty($iconset)) $iconset = 'sit';
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

echo "<script src='{$CONFIG['application_webpath']}scripts/prototype/prototype.js' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/scriptaculous/scriptaculous.js?load=effects,dragdrop' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/sit.js.php' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/webtrack.js' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/activity.js' type='text/javascript'></script>\n";
// To include a script for a single page, add the filename to the $pagescripts variable before including htmlheader.inc.php
if (is_array($pagescripts))
{
    foreach ($pagescripts AS $pscript)
    {
        echo "<script src='{$CONFIG['application_webpath']}scripts/{$pscript}' type='text/javascript'></script>\n";
    }
    unset($pagescripts, $pscript);
}
// javascript popup date library
echo "<script src='{$CONFIG['application_webpath']}scripts/calendar.js' type='text/javascript'></script>\n";

if ($sit[0] != '')
{
    echo "<link rel='search' type='application/opensearchdescription+xml' title='{$CONFIG['application_shortname']} Search' href='{$CONFIG['application_webpath']}opensearch.php' />\n";
}

echo "</head>\n";
echo "<body>\n";

echo "<div id='masthead'>";
echo "<div id='mastheadcontent'>";
if ($sit[0] != '')
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
    echo " | ";
    echo userstatus_name(user_status($sit[2]));
    if (user_accepting($sit[2]) != 'Yes')
    {
        echo " | {$strNotAcceptingIncidents}";
    }
    echo " | ";
    echo "<a href='logout.php'>{$strLogout}</a></div>";
}

echo "<h1 id='apptitle'>{$CONFIG['application_name']}</h1>";
if ($sit[0] != '')
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
if ($sit[0] != '')
{
    // Build a heirarchical top menu
    $hmenu;
    if (!is_array($hmenu))
    {
        trigger_error("Menu array not defined", E_USER_ERROR);
    }

//     if ($CONFIG['debug'])
//     {
//         $dbg .= 'permissions'.print_r($_SESSION['permissions'],true);
//     }
    echo "<div id='menu'>\n";
    echo "<ul id='menuList'>\n";
    foreach ($hmenu[0] as $top => $topvalue)
    {
        if ((!empty($topvalue['enablevar']) AND $CONFIG[$topvalue['enablevar']] === TRUE
            AND $CONFIG[$topvalue['enablevar']] !== 'disabled')
            OR empty($topvalue['enablevar']))
        {
            echo "<li class='menuitem'>";
            // Permission Required: ".permission_name($topvalue['perm'])."
            if ($topvalue['perm'] >=1 AND !in_array($topvalue['perm'], $_SESSION['permissions']))
            {
                echo "<a href='javascript:void(0);' class='greyed'>{$topvalue['name']}</a>";
            }
            else
            {
                echo "<a href='{$topvalue['url']}'>{$topvalue['name']}</a>";
            }

            // Do we need a submenu?
            if ($topvalue['submenu'] > 0 AND in_array($topvalue['perm'], $_SESSION['permissions']))
            {
                echo "\n<ul>"; //  id='menuSub'
                foreach ($hmenu[$topvalue['submenu']] as $sub => $subvalue)
                {
                    if ((!empty($subvalue['enablevar']) AND $CONFIG[$subvalue['enablevar']] == TRUE
                        AND $CONFIG[$subvalue['enablevar']] !== 'disabled')
                        OR empty($subvalue['enablevar']))
                    {
                        if (array_key_exists('submenu', $subvalue) AND $subvalue['submenu'] > 0)
                        {
                            echo "<li class='submenu'>";
                        }
                        else
                        {
                            echo "<li>";
                        }

                        if ($subvalue['perm'] >=1 AND !in_array($subvalue['perm'], $_SESSION['permissions']))
                        {
                            echo "<a href='javascript:void(0);' class='greyed'>{$subvalue['name']}</a>";
                        }
                        else
                        {
                            echo "<a href=\"{$subvalue['url']}\">{$subvalue['name']}</a>";
                        }

                        if (array_key_exists('submenu', $subvalue) AND $subvalue['submenu'] > 0 AND in_array($subvalue['perm'], $_SESSION['permissions']))
                        {
                            echo "<ul>"; // id ='menuSubSub'
                            foreach ($hmenu[$subvalue['submenu']] as $subsub => $subsubvalue)
                            {
                                if ((!empty($subsubvalue['enablevar']) AND $CONFIG[$subsubvalue['enablevar']] == TRUE
                                    AND $CONFIG[$subsubvalue['enablevar']] !== 'disabled')
                                    OR empty($subsubvalue['enablevar']))
                                {
                                    if (array_key_exists('submenu', $subsubvalue) AND $subsubvalue['submenu'] > 0)
                                    {
                                        echo "<li class='submenu'>";
                                    }
                                    else
                                    {
                                        echo "<li>";
                                    }

                                    if ($subsubvalue['perm'] >=1 AND !in_array($subsubvalue['perm'], $_SESSION['permissions']))
                                    {
                                        echo "<a href=\"javascript:void(0);\" class='greyed'>{$subsubvalue['name']}</a>";
                                    }
                                    else
                                    {
                                        echo "<a href='{$subsubvalue['url']}'>{$subsubvalue['name']}</a>";
                                    }

                                    if (array_key_exists('submenu', $subsubvalue) AND $subsubvalue['submenu'] > 0 AND in_array($subsubvalue['perm'], $_SESSION['permissions']))
                                    {
                                        echo "<ul>"; // id ='menuSubSubSub'
                                        foreach ($hmenu[$subsubvalue['submenu']] as $subsubsub => $subsubsubvalue)
                                        {
                                             if ((!empty($subsubsubvalue['enablevar']) AND $CONFIG[$subsubsubvalue['enablevar']])
                                                OR empty($subsubsubvalue['enablevar']))
                                            {
                                                if ($subsubsubvalue['submenu'] > 0)
                                                {
                                                    echo "<li class='submenu'>";
                                                }
                                                else
                                                {
                                                    echo "<li>";
                                                }

                                                if ($subsubsubvalue['perm'] >=1 AND !in_array($subsubsubvalue['perm'], $_SESSION['permissions']))
                                                {
                                                    echo "<a href='javascript:void(0);' class='greyed'>{$subsubsubvalue['name']}</a>";
                                                }
                                                else
                                                {
                                                    echo "<a href='{$subsubsubvalue['url']}'>{$subsubsubvalue['name']}</a>";
                                                }
                                                echo "</li>\n";
                                            }
                                        }
                                        echo "</ul>\n";
                                    }
                                    echo "</li>\n";
                                }
                            }
                            echo "</ul>\n";
                        }
                        echo "</li>\n";
                    }
                }
               echo "</ul>\n";
            }
            echo "</li>\n";
        }
    }
    echo "</ul>\n\n";

    echo "</div>\n";
}

if (!isset($refresh) AND $_SESSION['auth'] === TRUE)
{
    //update last seen (only if this is a page that does not auto-refresh)
    $lastseensql = "UPDATE LOW_PRIORITY `{$GLOBALS['dbUsers']}` SET lastseen=NOW() WHERE id='{$_SESSION['userid']}' LIMIT 1";
    mysql_query($lastseensql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
}

if ($sit[0] != '')
{

    // Check this is current
    $sql = "SELECT version FROM `{$dbSystem}` WHERE id = 0";
    $versionresult = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    list($dbversion) = mysql_fetch_row($versionresult);
    if ($dbversion < $application_version)
    {
        echo "<p class='error'><strong>IMPORTANT</strong> The SiT database schema needs to be updated";
        if (user_permission($sit[2], 22))
        {
            echo " from v{$dbversion} to v{$application_version}</p>";
            echo "<p class='tip'>Visit <a href='setup.php'>Setup</a> to update the schema";
        }
        echo "</p>";
    }

    // Check users email address
    if (empty($_SESSION['email']))
    {
        echo user_alert("{$strInvalidEmailAddress} - <a href='user_profile_edit.php'>{$strEditEmail}</a>", E_USER_ERROR);
    }

    //display (trigger) notices
    $noticesql = "SELECT * FROM `{$GLOBALS['dbNotices']}` ";
    // Don't show more than 20 notices, saftey cap
    $noticesql .= "WHERE userid={$sit[2]} ORDER BY timestamp DESC LIMIT 20";
    $noticeresult = mysql_query($noticesql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($noticeresult) > 0)
    {
        echo "<div id='noticearea'>\n";
        $keys = array_keys($_GET);

        foreach ($keys AS $key)
        {
            if ($key != 'noticeid')
            {
                $url .= "&amp;{$key}=".$_GET[$key];
            }
        }

        while ($notice = mysql_fetch_object($noticeresult))
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
                echo "<span><a href='{$CONFIG['application_webpath']}triggers.php'>";
                echo "{$strSettings}</a> | ";
                echo "<a href='javascript:void(0);' onclick=\"dismissNotice({$notice->id}, {$_SESSION['userid']})\">";
                echo "{$strDismiss}</a></span>";
            }
            else
            {
                echo "<span><a href='javascript:void(0);' onclick=\"dismissNotice({$notice->id}, {$_SESSION['userid']})\">";
                echo "{$strDismiss}</a></span>";
            }

            if (substr($notice->text, 0, 4) == '$str')
            {
                $v = substr($notice->text, 1);
                echo $GLOBALS[$v];
            }
            else
            {
                echo $notice->text;
            }

            if (!empty($notice->link))
            {
                echo " - <a href='{$notice->link}'>";
                if (substr($notice->linktext, 0, 3) == 'str')
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
            echo "<em> (".format_date_friendly(strtotime($notice->timestamp)).")</em>";
            echo "</small></p></div>\n";
        }

        if (mysql_num_rows($noticeresult) > 1)
        {
            echo "\n<p id='dismissall'><a href='javascript:void(0);' onclick=\"dismissNotice('all', {$_SESSION['userid']})\">{$strDismissAll}</a></p>\n";
        }
        echo "</div>\n";
    }
}
$headerdisplayed = TRUE; // Set a variable so we can check to see if the header was included

// FIXME @@@ BUGBUG @@@ experimental ivan 10July2008 & 11April2010
// echo "<div id='menupanel'>";
// echo "<h3>Menu</h3>";
// echo "<ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul>";
// echo "</div>";
//
// // FIXME @@@ BUGBUG @@@ experimental ivan 10July2008
// echo "<p id='menutoggle'><a href='javascript:void(0);' onclick='toggleMenuPanel();' title='{$strMenu}'>";
// echo "".icon('auto', 16)."</a></p>";


echo "<div id='mainframe'>";
?>