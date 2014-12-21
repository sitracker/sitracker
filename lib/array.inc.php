<?php
// array.inc.php - functions relating to arrays
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
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
 * Numeric descending sort of multi array
 */
function ansort($x,$var,$cmp='strcasecmp')
{
    if ( is_string($var) ) $var = "'$var'";

    if ($cmp == 'numeric')
    {
        uasort($x, create_function('$a,$b', 'return '.'( $a['.$var.'] < $b['.$var.']);'));
    }
    else
    {
        uasort($x, create_function('$a,$b', 'return '.$cmp.'( $a['.$var.'],$b['.$var.']);'));
    }
    return $x;
}


function array_remove_duplicate($array, $field)
{
    foreach ($array as $sub)
    {
        $cmp[] = $sub[$field];
    }

    $unique = array_unique($cmp);
    foreach ($unique as $k => $rien)
    {
        $new[] = $array[$k];
    }
    return $new;
}


function array_multi_search($needle, $haystack, $searchkey)
{
    foreach ($haystack AS $thekey => $thevalue)
    {
        if ($thevalue[$searchkey] == $needle) return $thekey;
    }
    return FALSE;
}

/**
 * Implode assocative array
 */
function implode_assoc($glue1, $glue2, $array)
{
    foreach ($array as $key => $val)
    {
        $array2[] = $key.$glue1.$val;
    }

    return implode($glue2, $array2);
}

/**
 * Detect whether an array is associative
 * @param array $array
 * @note From http://uk.php.net/manual/en/function.is-array.php#77744
 */
function is_assoc($array)
{
    return is_array($array) && count($array) !== array_reduce(array_keys($array), 'is_assoc_callback', 0);
}


/**
 * Detect whether an array is associative
 * @param various $a
 * @param various $b
 * @note Callback function, Called by is_assoc()
         From http://uk.php.net/manual/en/function.is-array.php#77744
 */
function is_assoc_callback($a, $b)
{
    return $a === $b ? $a + 1 : 0;
}
