<?php
// plugins.inc.php - functions relating to plugins
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
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
        if (endsWith($name, '.php'))
        {
            $name = substr($name, 0, -4);
            $ondisk_pluginname = APPLICATION_PLUGINPATH . $name . '.php';
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
                $ondisk_plugins[$name]['path'] = APPLICATION_PLUGINPATH . $name . DIRECTORY_SEPARATOR;
            }
        }
    }
    
    closedir($dir_handle);
    
    return $ondisk_plugins;
}