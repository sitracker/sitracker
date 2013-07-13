<?php
// plugins.inc.php - functions relating to plugins
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


/**
*
* Enter description here ...
* @author Ivan Lucas
*/
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


/**
*
* Enter description here ...
* @author Ivan Lucas
*/
function getplugininfovalue($string)
{
    return trim(mb_substr($string, mb_strpos($string, '=', 12)+1)," \t\n\r\0\x0B;\'\"");
}


/**
*
* Enter description here ...
* @author Ivan Lucas
*/
function gethtmlstring($body, $prefix, $suffix, $offset=0)
{
    $begin = @mb_strpos($body, $prefix, $offset);
    $begin += mb_strlen($prefix);
    $end = mb_strpos($body, $suffix, $begin);
    $length = $end - $begin;
    $htmlstring = mb_substr($body, $begin, $length);

    return $htmlstring;
}


/**
 * Gets a list of all the plugins located in the plugin directory
 * @return array Array detailing the installed plugins
 * @author Ivan Lucas
 */
function get_plugins_on_disk()
{
    // Evaluate plugins on disk
    $path = APPLICATION_PLUGINPATH;
    $dir_handle = @opendir($path) or trigger_error("Unable to open plugins directory {$path}", E_USER_ERROR);
    
    $ondisk_plugins = array(); 
    
    while ($name = readdir($dir_handle))
    {
        if (is_dir(APPLICATION_PLUGINPATH . $name)) 
        {
            $ondisk_pluginname = APPLICATION_PLUGINPATH . $name . DIRECTORY_SEPARATOR . $name . '.php';
            if (is_file($ondisk_pluginname))
            {
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
                    $ondisk_plugins[$name]['path'] = APPLICATION_PLUGINPATH . $name . DIRECTORY_SEPARATOR;
                }
            }
        }
    }
    
    closedir($dir_handle);
    
    return $ondisk_plugins;
}



/**
 * Function to check plugin as part of a SiT upgrade, any incompatable updates
 * are disabled
 * NOTE; not i18n as setup.php is not i18n'ed
 * @author Paul Heaney
 * @param boolean $doupgrade If false only checks the updates, if true then disabled incompatable updates 
 * @param float $application_version The version of SiT being upgraded to
 * @return string A HTML table showing installed plugins and the corresponding actions
 */
function sit_upgrade_plugin_check($doupgrade, $application_version)
{
    global $CONFIG; 
    $return = '';
    
    // (Re)load plugins from database
    // During setup.php we've not loaded the DB settings

    // Read config from database (this overrides any config in the config files
    $sql = "SELECT * FROM `{$GLOBALS['dbConfig']}` WHERE config = 'plugins'";
    $result = @mysql_query($sql);
    if ($result AND mysql_num_rows($result) > 0)
    {
        while ($conf = mysql_fetch_object($result))
        {
            if ($conf->value === 'TRUE') $conf->value = TRUE;
            if ($conf->value === 'FALSE') $conf->value = FALSE;
            if (mb_substr($conf->value, 0, 6) == 'array(')
            {
                eval("\$val = {$conf->value};");
                $conf->value = $val;
            }
            $CONFIG[$conf->config] = $conf->value;
        }
    }
    
    if (!empty($CONFIG['plugins']))
    {
        $ondisk_plugins = get_plugins_on_disk();
        $todisable = array();

        $return .= "<h2>Plugin checks</h2>";
        
        $return .= "<table>";
        $return .= "<tr><th>{$GLOBALS['strPlugin']}</th><th>{$GLOBALS['strVersion']}</th><th>{$GLOBALS['strAction']}</th></tr>";
       
        foreach($ondisk_plugins AS $ondisk_plugin => $ondisk_plugin_details)
        {
            $disable = FALSE;

            if (!is_array($CONFIG['plugins'])) $CONFIG['plugins'] = array();

            if (in_array($ondisk_plugin, $CONFIG['plugins']))
            {
                if ($ondisk_plugin_details['sitminversion'] > $application_version)
                {
                    $s = "This plugin was designed for {$CONFIG['application_name']} version {$ondisk_plugin_details['sitminversion']} or later</strong>";
                    $disable = TRUE;
                }
                if ($ondisk_plugin_details['sitmaxversion'] < $application_version)
                {
                    $s = "This plugin was designed for {$CONFIG['application_name']} version {$ondisk_plugin_details['sitmaxversion']} or earlier</strong>";
                    $disable = TRUE;
                }

                // During an upgrade we're only interested if the plugin is installed
                $return .= "<tr>";

                $return .= "<td>{$ondisk_plugin}</td>";
                $return .= "<td>{$ondisk_plugin_details['version']}</td>";

                $return .= "<td>";
                if ($disable)
                {
                    $return .= $s;
                    $return .= "<br />This plugin will be disabled";
                    $todisable[] = $ondisk_plugin;
                }
                else
                {
                    $return .= $GLOBALS['strOK'];
                }
                $return .= "</td>";

                $return .= "</tr>";
            }
        }

        foreach ($CONFIG['plugins'] AS $p)
        {
            if (!array_key_exists($p, $ondisk_plugins))
            {
                $return .= "<tr>";
                $return .= "<td>{$p}</td>";
                $return .= "<td>Not on disk</td>";
                $return .= "<td>This plugin will be disabled</td>";
                $return .= "</tr>";
                
                $todisable[] = $p;
            }
        }

        $return .= "</table>";
        
        if ($doupgrade)
        {
            // Do the disable
            
            if (count($todisable) > 0)
            {
                echo "The following plugins have been disabled as they are incompatable with this version";
                echo "<ul>";
                foreach ($todisable AS $d)
                {
                    echo "<li>{$d}</li>";
                }
                echo "</ul>";
            }
            
            foreach ($todisable AS $d) 
            
            $newsetting['plugins'] = array_diff($CONFIG['plugins'], $todisable);
            
            $CONFIG['plugins'] = $newsetting['plugins'];
            if (is_array($newsetting['plugins']) AND count($newsetting['plugins']) > 0)
            {
                array_walk($newsetting['plugins'], 'enclose_array_values', "\'");
                $savecfg['plugins'] = 'array(' . implode(',', $newsetting['plugins']) . ')';
            }
            else
            {
                $savecfg['plugins'] = '';
            }
            
            cfgSave($savecfg, NAMESPACE_SIT);
        }
        
        $return .= "Any incompatable plugins will be disabled";
    }    
   
    return $return;
}