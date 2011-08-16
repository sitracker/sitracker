<?php
// base.inc.php - core utility functions and files
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

// Note: Constants moved into lib/constants.inc.php for 3.90 (INL 2011-06-22)

/**
 * Begin global variable definitions
 **/
// Version number of the application, (numbers only)
$application_version = '3.90';

// Revision string, e.g. 'beta2' or 'git' or ''
$application_revision = 'git';

// The number of errors that have occurred
$siterrors = 0;

// Time settings
$now = time();
// next 16 hours, based on reminders being run at midnight this is today
$today = $now + (16 * 3600);
$lastweek = $now - (7 * 86400); // the previous seven days
$todayrecent = $now - (16 * 3600);  // past 16 hours
$startofsession = $now - ini_get("session.gc_maxlifetime");

$CONFIG['upload_max_filesize'] = return_bytes($CONFIG['upload_max_filesize']);

$iconset = $CONFIG['default_iconset'];

// Set a string to be the full version number and revision of the application
$application_version_string = trim("v{$application_version} {$application_revision}");

$ldap_conn = "";

/**
 * End global variable definitions
 **/

// Clean PHP_SELF server variable to avoid potential XSS security issue
$_SERVER['PHP_SELF'] = mb_substr($_SERVER['PHP_SELF'], 0,
                            (mb_strlen($_SERVER['PHP_SELF'])
                            - @mb_strlen($_SERVER['PATH_INFO'])));

// Report all PHP errors
error_reporting(E_ALL);
$oldeh = set_error_handler("sit_error_handler");

// Decide which language to use and setup internationalisation
require (APPLICATION_I18NPATH . 'en-GB.inc.php');
if ($CONFIG['default_i18n'] != 'en-GB')
{
    include (APPLICATION_I18NPATH . "{$CONFIG['default_i18n']}.inc.php");
}
if (!empty($_SESSION['lang'])
    AND $_SESSION['lang'] != $CONFIG['default_i18n'])
{
    include (APPLICATION_I18NPATH . "{$_SESSION['lang']}.inc.php");
}
ini_set('default_charset', $i18ncharset);
mb_internal_encoding($i18ncharset);


//**** Begin functions ****//

/**
 * Strip slashes from an array
 * @param $data an array
 * @return An array with slashes stripped
 */
function stripslashes_array($data)
{
    if (is_array($data))
    {
        foreach ($data as $key => $value)
        {
            $data[$key] = stripslashes_array($value);
        }
        return $data;
    }
    else
    {
        return stripslashes($data);
    }
}


/**
 * Make an external variable safe for database and HTML display
 * @author Ivan Lucas, Kieran Hogg
 * @param mixed $var variable to replace
 * @param bool $striphtml whether to strip html
 * @param bool $transentities whether to translate all aplicable chars (true) or just special chars (false) into html entites
 * @param bool $mysqlescape whether to mysql_escape()
 * @param array $disallowedchars array of chars to remove
 * @param array $replacechars array of chars to replace as $orig => $replace
 * @param bool $intval whether to get the integer value of the variable
 * @todo TODO this function could use a bit of tidy-up
 * @returns variable
 */
function cleanvar($vars, $striphtml = TRUE, $transentities = FALSE,
                $mysqlescape = TRUE, $disallowedchars = array(),
                $replacechars = array(), $intval = FALSE)
{
    if (is_array($vars))
    {
        foreach ($vars as $key => $singlevar)
        {
            $var[$key] = cleanvar($singlevar, $striphtml, $transentities, $mysqlescape,
                    $disallowedchars, $replacechars);
        }
    }
    else
    {
        $var = $vars;
        if ($striphtml === TRUE)
        {
            $var = strip_tags($var);
        }

        if (!empty($disallowedchars))
        {
            $var = str_replace($disallowedchars, '', $var);
        }

        if (!empty($replacechars))
        {
            foreach ($replacechars as $orig => $replace)
            {
                $var = str_replace($orig, $replace, $var);
            }
        }

        if ($transentities)
        {
            $var = htmlentities($var, ENT_COMPAT, $GLOBALS['i18ncharset']);
        }
        else
        {
            $var = htmlspecialchars($var, ENT_COMPAT, $GLOBALS['i18ncharset']);
        }

        if ($mysqlescape)
        {
            $var = mysql_real_escape_string($var);
        }

        if ($intval)
        {
            $var = intval($val);
        }

        $var = trim($var);
    }
    return $var;
}


/**
  * Make an external variable safe. Force it to be an integer.
  * @author Ivan Lucas
  * @param mixed $string variable to make safe
  * @returns int - safe variable
*/
function clean_int($vars)
{
    if (is_array($vars))
    {
        foreach ($vars as $key => $singlevar)
        {
            $var[$key] = clean_int($singlevar);
        }
    }
    elseif (!is_null($vars) AND $vars != '' AND !is_numeric($vars))
    {
        trigger_error("Input was expected to be numeric but received string instead", E_USER_WARNING);
    }
    else
    {
        $var = intval($vars);
    }

    return $var;
}


/**
  * Make an external variable safe. Force it to be a float.
  * @author Ivan Lucas
  * @param mixed $string variable to make safe
  * @returns int - safe variable
*/
function clean_float($vars)
{
    if (is_array($vars))
    {
        foreach ($vars as $key => $singlevar)
        {
            $var[$key] = clean_float($singlevar);
        }
    }
    elseif (!is_null($vars) AND $vars != '' AND !is_numeric($vars))
    {
        trigger_error("Input was expected to be numeric but received string instead", E_USER_WARNING);
    }
    else
    {
        $var = floatval($vars);
    }

    return $var;
}


/**
  * Make an external variable safe for use in a database query
  * @author Ivan Lucas
  * @param mixed $string variable to make safe
  * @returns string - DB safe variable
  * @note Strips HTML
*/
function clean_dbstring($string)
{
    $string = strip_tags($string);

    if (get_magic_quotes_gpc() == 1)
    {
        stripslashes($string);
    }

    $string = mysql_real_escape_string($string);

    return $string;
}


/**
  * Make an external variable safe by ensuring the value is one of a list
  * of predetermined values
  * @author Ivan Lucas
  * @param mixed $string variable to make safe
  * @param array $list list of safe values
  * @param bool $strict, also check the types of the values in the list
  * @returns mixed - DB safe variable
  * @note If the input string isn't found in the list, the first option is used
*/
function clean_fixed_list($string, $list, $strict = FALSE)
{
    if (is_array($list))
    {
        if (!in_array($string, $list, $strict))
        {
            if ($string != NULL AND $string != '')
            {
                trigger_error("Unexpected input", E_USER_WARNING);
            }
            $string = $list[0];
        }
    }
    else
    {
        trigger_error("Could not understand list of predetermined values for fixed_list()", E_USER_ERROR);
        return false;
    }
    return $string;
}


/**
 * Return an array of available languages codes by looking at the files
 * in the i18n directory
 * @author Ivan Lucas
 * @param bool $test - (optional) Include test language (zz) in results
 * @retval array Language codes
 **/
function available_languages($test = FALSE)
{
    $i18nfiles = list_dir('.'.DIRECTORY_SEPARATOR.'i18n');
    $i18nfiles = array_filter($i18nfiles, 'filter_i18n_filenames');
    array_walk($i18nfiles, 'i18n_filename_to_code');
    asort($i18nfiles);
    foreach ($i18nfiles AS $code)
    {
        if ($code != 'zz')
        {
            $available[$code] = i18n_code_to_name($code);
        }
        elseif ($code == 'zz' AND $test === TRUE)
        {
            $available[$code] = 'Test Language (zz)';
        }
    }

    return $available;
}

?>