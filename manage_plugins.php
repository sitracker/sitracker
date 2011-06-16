<?php
// manage_plugins.php - SiT! Plugin Manager
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

$permission = array(22,66); // Configure & Install dashboard components
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$seltab = clean_fixed_list($_REQUEST['tab'], array('installed', 'repository'));

// Make sure right array key is used, we use the translated string as the key
// if ($seltab == 'installed') $seltab = $strInstalled;
// elseif ($seltab == 'repository') $seltab = $strRepository;

$title = $strManagePlugins;

function getplugininfo($string)
{
    if (beginsWith($string, "\$PLUGININFO["))
    {
        return TRUE;
    }
    else
    {
        return FALSE;
    }
}


function getplugininfovalue($string)
{
    return trim(mb_substr($string, mb_strpos($string, '=', 12)+1)," \t\n\r\0\x0B;\'\"");
}


function gethtmlstring($body, $prefix, $suffix, $offset=0)
{
    $begin = @mb_strpos($body, $prefix, $offset);
    $begin += mb_strlen($prefix);
    $end = mb_strpos($body, $suffix, $begin);
    $length = $end - $begin;
    $htmlstring = mb_substr($body, $begin, $length);

    return $htmlstring;
}


include (APPLICATION_INCPATH . 'htmlheader.inc.php');
if (!is_array($CONFIG['plugins'])) $CONFIG['plugins'] = array();

echo "<h2>".icon('settings', 32, $title)." {$title}</h2>";
if ($_REQUEST['action'] != 'checkforupdates')
{
    echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?action=checkforupdates'>{$strCheckForUpdatesOnline}</a></p>";
}

// Actions
if ($_REQUEST['action'] == 'enable' OR $_REQUEST['action'] == 'disable')
{
    $actionplugin = cleanvar($_REQUEST['plugin']);
    if (!beginsWith($actionplugin, 'dashboard_'))
    {
        if ($_REQUEST['action'] == 'enable')
        {
            $newsetting['plugins'] = $CONFIG['plugins'];
            if (!in_array($actionplugin, $CONFIG['plugins']))
            {
                $newsetting['plugins'][] = $actionplugin;
            }
        }
        else
        {
            if (is_array($CONFIG['plugins']))
            {
                foreach($CONFIG['plugins'] AS $confplugin)
                {
                    if ($confplugin != $actionplugin) $newsetting['plugins'][] = $confplugin;
                }
            }
        }
        $CONFIG['plugins'] = $newsetting['plugins'];
        if (is_array($newsetting['plugins']) AND count($newsetting['plugins']) > 0) 
        {
            $savecfg['plugins'] = 'array(' . implode(',', $newsetting['plugins']) . ')';
        }
        else 
        {
            $savecfg['plugins'] = '';
        }
        cfgSave($savecfg, NAMESPACE_SIT);
    }
    else
    {
        // TODO
        // Enable/Disable dashlet
    }
}


if ($_REQUEST['action'] == 'checkforupdates')
{
    $plugins_directory = file_get_contents('http://sitracker.org/wiki/Plugins_directory');

    // $startloc = strpos($plugins_directory, '</caption>', 200);
    // $endloc = strpos($plugins_directory, '</table>', $startloc) - $startloc;
    $plugins_directory = gethtmlstring($plugins_directory, '</caption>', '</table>', 200);
    // echo "<pre>".htmlentities($plugins_directory)."</pre>";
    //preg_match_all("|<[^>]+>(.*)</[^>]+>|U", "<b>example: </b><div align=left>this is a test</div>",   $out, PREG_PATTERN_ORDER);

    // preg_match_all("/>(\w*)<\/a>\b<\/td><td>(\w*)<\/td>/msU", $plugins_directory, $out, &$pluginnames);
    preg_match_all("/<td>(.*)<\/td>\W?<td>(.*)<\/td>\W?<td>(.*)<\/td>\W?<td>(.*)<\/td>\W?<td>(.*)<\/td>\W?<td>(.*)<\/td>/msU", $plugins_directory, $out, &$pluginnames);
    // $out = $out[1];
    // echo "<pre>OUT:".print_r($out,true)."</pre>";
    $avail_count = count($out[1]);
    for ($i = 1; $i <= $avail_count; $i++)
    {
        preg_match("/<a href=\"(.*)\"/msU", $out[1][$i], $url);
        $name = trim(strip_tags($out[1][$i]));
        $sitminversion = trim(strip_tags($out[4][$i]));
        $sitmaxversion = trim(strip_tags($out[5][$i]));
        if (!empty($name) ) // AND $sitminversion <= $application_version AND $sitmaxversion >= $application_version
        {
            $_SESSION['available_plugins'][$name]['desc'] = trim(strip_tags($out[2][$i]));
            $_SESSION['available_plugins'][$name]['version'] = trim(strip_tags($out[3][$i]));
            $_SESSION['available_plugins'][$name]['sitminversion'] = $sitminversion;
            $_SESSION['available_plugins'][$name]['sitmaxversion'] = $sitmaxversion;
            $_SESSION['available_plugins'][$name]['author'] = trim(strip_tags($out[6][$i]));
            if (!empty($url[1])) $_SESSION['available_plugins'][$name]['url'] = "http://sitracker.org" . $url[1];
        }
    }
    ksort($_SESSION['available_plugins']);
}

// Evaluate plugins on disk
$path = APPLICATION_PLUGINPATH;
$dir_handle = @opendir($path) or trigger_error("Unable to open plugins directory $path", E_USER_ERROR);

while ($name = readdir($dir_handle))
{
    if (is_dir(APPLICATION_PLUGINPATH . $name) AND strpos($name, '.') === FALSE)
    {
        $ondisk_pluginname = APPLICATION_PLUGINPATH . $name . DIRECTORY_SEPARATOR . $name . '.php';
        //$ondisk_plugins[$ondisk_pluginname] = 1;
        $content = file($ondisk_pluginname);
        $content = array_filter($content, 'getplugininfo');
        foreach ($content AS $key => $value)
        {
            if (strrpos($value, '[\'version\']') !== FALSE) $ondisk_plugins[$name]['version'] = getplugininfovalue($value);
            if (strrpos($value, '[\'description\']') !== FALSE) $ondisk_plugins[$name]['desc'] = getplugininfovalue($value);
            if (strrpos($value, '[\'author\']') !== FALSE) $ondisk_plugins[$name]['author'] = getplugininfovalue($value);
            if (strrpos($value, '[\'legal\']') !== FALSE) $ondisk_plugins[$name]['legal'] = getplugininfovalue($value);
            if (strrpos($value, '[\'sitminversion\']') !== FALSE) $ondisk_plugins[$name]['sitminversion'] = getplugininfovalue($value);
            if (strrpos($value, '[\'sitmaxversion\']') !== FALSE) $ondisk_plugins[$name]['sitmaxversion'] = getplugininfovalue($value);
            if (strrpos($value, '[\'url\']') !== FALSE) $ondisk_plugins[$name]['url'] = getplugininfovalue($value);
            $ondisk_plugins[$name]['filepath'] = APPLICATION_PLUGINPATH . $name . DIRECTORY_SEPARATOR;
        }
    }
}

closedir($dir_handle);


$tabs[$strInstalled] = "{$_SERVER['PHP_SELF']}?tab=installed";
if (is_array($_SESSION['available_plugins']))
{
    $tabs[$strRepository] = "{$_SERVER['PHP_SELF']}?tab=repository";
}
echo draw_tabs($tabs, $seltab);

switch ($seltab)
{
    case 'repository':
        if (is_array($_SESSION['available_plugins']))
        {
            echo "<table align='center'>";
            echo "<tr><th>{$strPlugin}</th><th>{$strVersion}</th><th>{$strDescription}</th><th>{$strAuthor}</th><th>{$strOperation}</tr>";
            $shade = 'shade1';
            foreach($_SESSION['available_plugins'] AS $avail_plugin => $avail_plugin_details)
            {
                $operation = '';
                if (!empty($avail_plugin_details['url']))
                {
                    $operation .= "<a href=\"{$avail_plugin_details['url']}\">{$strVisitHomepage}</a>";
                }
//                 $operation .= "<a href=\"{$_SERVER['PHP_SELF']}?action=install&amp;plugin=".urlencode($avail_plugin)."\">{$strInstall}</a>";
                if (!in_array($avail_plugin, $ondisk_plugins))
                {
                    echo "<tr class='{$shade}'>";
                    echo "<td>{$avail_plugin}</td>";
                    echo "<td>{$avail_plugin_details['version']}</td>";
                    echo "<td>{$avail_plugin_details['desc']}</td>";
                    echo "<td>{$avail_plugin_details['author']}</td>";
                    echo "<td>{$operation}</td>";
                    echo "</tr>";
                    if ($shade == 'shade2') $shade = 'shade1';
                    else $shade = 'shade2';
                }
            }
            echo "</table>";
        }
        else
        {
            echo "<p>{$strNoAvailablePlugins}</p>";
        }
        break;

    case $strInstalled:
    default:
        if (is_array($ondisk_plugins))
        {
            ksort($ondisk_plugins);
            echo "<table align='center'>";
            echo "<tr><th>{$strPlugin}</th><th>{$strVersion}</th><th>{$strDescription}</th><th>{$strAuthor}</th><th>{$strOperation}</tr>";
            $shade = 'shade1';
            foreach($ondisk_plugins AS $ondisk_plugin => $ondisk_plugin_details)
            {
                $operation = '';
                if (!is_array($CONFIG['plugins'])) $CONFIG['plugins'] = array();
                if (in_array($ondisk_plugin, $CONFIG['plugins']))
                {
                    $installed = TRUE;
                    $shade = 'idle';
                }
                else
                {
                    $installed = FALSE;
                    $shade = 'expired';
                }

                echo "<tr class='{$shade}'>";
                echo "<td>{$ondisk_plugin}</td>";
                echo "<td>{$ondisk_plugin_details['version']}</td>";
                echo "<td>";
                echo "<em>{$ondisk_plugin_details['desc']}</em><br />";
                echo "<strong>{$strLicense}:</strong> {$ondisk_plugin_details['legal']}";
                if ($ondisk_plugin_details['sitminversion'] > $application_version)
                {
                    echo "<p class='warning'>This plugin was designed for {$CONFIG['application_name']} version {$ondisk_plugin_details['sitminversion']} or later</strong></p>";
                }
                if ($ondisk_plugin_details['sitmaxversion'] < $application_version)
                {
                    echo "<p class='warning'>This plugin was designed for {$CONFIG['application_name']} version {$ondisk_plugin_details['sitmaxversion']} or earlier</strong></p>";
                }
                if ($_SESSION['available_plugins'][$ondisk_plugin]['version'] > $ondisk_plugin_details['version'])
                {
                    echo "<p class='info'>A newer version is available: v{$_SESSION['available_plugins'][$ondisk_plugin]['version']}</p>";
                }
                echo "</td>";
                echo "<td>{$ondisk_plugin_details['author']}</td>";
                $operations = array();
                if (!beginsWith($ondisk_plugin, 'dashboard_'))
                {
                    if ($installed)
                    {
                        $operations[$strDisable] = "{$_SERVER['PHP_SELF']}?action=disable&amp;plugin={$ondisk_plugin}";
                    }
                    else
                    {
                        $operations[$strEnable] = "{$_SERVER['PHP_SELF']}?action=enable&amp;plugin={$ondisk_plugin}";
                    }
                }
                else
                {
                    $operations[$strManageDashlet] = "{$CONFIG['application_webpath']}manage_dashboard.php";
                }
                if (file_exists($ondisk_plugin_details['path'] . 'README'))
                {
                   $operations[$strHelp] = "{$CONFIG['application_webpath']}plugins/{$ondisk_plugin}/README";
                }
                if (!empty($ondisk_plugin_details['url']))
                {
                   $operations[$strVisitHomepage] = "{$ondisk_plugin_details['url']}";
                }
                echo "<td>".html_action_links($operations)."</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        else
        {
            echo "<p align='center'>{$strNone}</p>";
        }
        break;
}
$dbg .= "<pre>AVAIL:".print_r($_SESSION['available_plugins'],true)."</pre>";
$dbg .= "<pre>".print_r($ondisk_plugins,true)."</pre>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>