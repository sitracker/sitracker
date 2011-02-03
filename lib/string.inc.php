<?php
// string.inc.php - String functions
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


function strip_comma($string)
{
    // also strips Tabs, CR's and LF's
    $string = str_replace(",", " ", $string);
    $string = str_replace("\r", " ", $string);
    $string = str_replace("\n", " ", $string);
    $string = str_replace("\t", " ", $string);
    return $string;
}


function leading_zero($length,$number)
{
    $length = $length-strlen($number);
    for ($i = 0; $i < $length; $i++)
    {
        $number = "0" . $number;
    }
    return ($number);
}


/**
 * Determines whether a string starts with a given substring
 * @param string $str. Haystack
 * @param string $sub. Needle
 * @return bool. TRUE means the string was found
 */
function beginsWith($str, $sub)
{
   return ( substr( $str, 0, strlen( $sub ) ) === $sub );
}


/**
 * Determines whether a string ends with a given substring
 * @param string $str. Haystack
 * @param string $sub. Needle
 * @return bool. TRUE means the string was found
 */
function endsWith($str, $sub)
{
   return ( substr( $str, strlen( $str ) - strlen( $sub ) ) === $sub );
}


function remove_slashes($string)
{
    $string = str_replace("\\'", "'", $string);
    $string = str_replace("\'", "'", $string);
    $string = str_replace("\\'", "'", $string);
    $string = str_replace("\\\"", "\"", $string);

    return $string;
}


// This function doesn't exist for PHP4 so use this instead
if (!function_exists("stripos"))
{
    function stripos($str,$needle,$offset=0)
    {
        return strpos(strtolower($str),strtolower($needle),$offset);
    }
}


function string_find_all($haystack, $needle, $limit=0)
{
    $positions = array();
    $currentoffset = 0;

    $offset = 0;
    $count = 0;
    while (($pos = stripos($haystack, $needle, $offset)) !== false && ($count < $limit || $limit == 0))
    {
        $positions[] = $pos;
        $offset = $pos + strlen($needle);
        $count++;
    }
    return $positions;
}


/**
 * Trims a string so that it is not longer than the length given and
 * add ellipses (...) to the end
 * @author Ivan Lucas
 * @param string $text. Some plain text to shorten
 * @param int $maxlength. Length of the resulting string (in characters)
 * @param bool $html. Set to TRUE to include HTML in the output (for ellipsis)
 *                    Set to FALSE for plain text only
 * @return string. A shortned string (optionally with html)
 */
function truncate_string($text, $maxlength=255, $html = TRUE)
{
    global $strEllipsis;
    if (strlen($text) > $maxlength)
    {
        // Leave space for ellipses
        if ($html == TRUE)
        {
            $maxlength -= 1;
        }
        else
        {
            $maxlength -= 3;
        }

        $text = mb_substr($text, 0, $maxlength, 'UTF-8');

        if ($html == TRUE)
        {
            $text .= $strEllipsis;
        }
        else
        {
            $text .= '...';
        }
    }
    return $text;
}


/**
 * UTF8 substr() replacement
 * @author Anon / Public Domain
 * @note see http://www.php.net/manual/en/function.substr.php#57899
 */
function utf8_substr($str, $from, $len)
{
    # utf8 substr
    # www.yeap.lv
    return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$from.'}'.
                    '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$len.'}).*#s',
                    '$1',$str);
}


/**
 * UTF8 strlen() replacement
 * @author anpaza at mail dot ru / Public Domain
 * @note see http://www.php.net/manual/en/function.strlen.php#59258
 */
function utf8_strlen($str)
{
    $i = 0;
    $count = 0;
    $len = strlen ($str);
    while ($i < $len)
    {
    $chr = ord ($str[$i]);
    $count++;
    $i++;
    if ($i >= $len)
        break;

    if ($chr & 0x80)
    {
        $chr <<= 1;
        while ($chr & 0x80)
        {
        $i++;
        $chr <<= 1;
        }
    }
    }
    return $count;
}


/**
 * Array filter callback to list only valid language files
 * @author Ivan Lucas
 * @param string $var. Filename to check
 * @retval bool TRUE : valid
 * @retval bool FALSE : invalid
 */
function filter_i18n_filenames($var)
{
    $validity = FALSE;
    if (substr($var, -8) === '.inc.php') $validity = TRUE;
    else $validity = FALSE;

    return $validity;
}


/**
 * Array walk callback convert an i18n filename to a language code
 * @author Ivan Lucas
 * @param string $filename. Filename of i18n file (opt. with path)
 * @return nothing
 */
function i18n_filename_to_code(&$elem, $key)
{
    $elem = substr($elem, strrpos($elem,DIRECTORY_SEPARATOR)+1, -8);
}


/**
 * Array filter callback to list only valid css files
 * @author Ivan Lucas
 * @param string $var. Filename to check
 * @retval bool TRUE : valid
 * @retval bool FALSE : invalid
 */
function filter_css_filenames($var)
{
    $validity = FALSE;

//    if (substr($var, -4) === '.css') $validity = TRUE;
    //else $validity = FALSE;
//     echo "$var <br />";
    if (is_dir($var)) $validity = TRUE;
    else $validty = FALSE;

    return $validity;
}


/**
 * Array walk callback convert an css filename to a theme name
 * @author Ivan Lucas
 * @param string $filename. Filename of theme file (opt. with path)
 * @return nothing
 */
function css_filename_to_themename(&$elem, $key)
{
    $elem = substr($elem, strrpos($elem,DIRECTORY_SEPARATOR)+1, -4);
}


/**
 * Convert an i18n code to a localised language name
 * @author Ivan Lucas
 * @param mixed $code. string i18n code (e.g. 'en-GB'), or array of strings
 * @return mixed.
 * @note if working on an array returns a string Language name,
          or code if language not recognised
 * @note if working on an array, returns an associative array with code
 *       as the key and lang name as the value
 */
function i18n_code_to_name($code)
{
    global $i18n_codes;
    if (is_array($code))
    {
        foreach ($code AS $c => $v)
        {
            if (isset($i18n_codes[$v])) $codearr[$v] = $i18n_codes[$v];
            else $codearr[$v] = $c;
        }
        return $codearr;
    }
    else
    {
        if (isset($i18n_codes[$code])) return $i18n_codes[$code];
        else return $code;
    }
}

/**
 * Make a string quoted, that is prefix lines with >
 * and strip out irrelevant update headers
 * @author Ivan Lucas
 * @todo FIXME unfinished
 */
function quote_message($message)
{
    $lines = explode("\n", $message);
    $message = '';
    foreach ($lines AS $linenum => $line)
    {
        if (trim($line) == "<hr>") $endmeta = $linenum + 1;
    }
    if ($endmeta > 0) $lines = array_slice($lines,$endmeta);
    foreach ($lines AS $line)
    {
        $message .= "> {$line}";
    }
    return $message;
}

/**
 * Encode email subject as per RFC 2047
 * @author Ivan Lucas
 * @param string $subject. Non-encoded subject
 * @param string $charset. Character set that's in use
 * @return string. Encoded subject
 */
function encode_email_subject($subject, $charset)
{
    $encoded_subject = '';
    if ($subject && $charset)
    {
        $end = "?=";
        $start = "=?" . $charset . "?B?";
        $spacer = $end . "\r\n\t" . $start;
        $len = floor((75 - strlen($start) - strlen($end))/2) * 2;
        $encoded_subject = base64_encode($subject);
        // Don't split chunks doesn't seem to be necessary and in fact causes garbling of subjects - See Mantis bug 959
        //         $encoded_subject = chunk_split($encoded_subject, $len, $spacer);
        $spacer = preg_quote($spacer);
        $encoded_subject = preg_replace("/" . $spacer . "$/", "", $encoded_subject);
        $encoded_subject = $start . $encoded_subject . $end;
    }
    return $encoded_subject;
}


?>