<?php
// ldap_browse.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul[at]sitracker.org>

$permission = 22; // Administrate

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

include (APPLICATION_INCPATH . 'minimal_header.inc.php');

echo "<h2>{$strLDAP}</h2>";

$base = cleanvar($_REQUEST['base']);
$field = cleanvar($_REQUEST['field']);

$entries = ldapGroupBrowse($base);

echo "Current Level: {$base}<br /><br />";

echo "<table>";

if (!empty($base))
{
    $context = explode(',', $base);
    
    array_shift($context);
    
    $up_one_level = implode(',', $context);
    
    echo "<tr>";
    echo "<td>".icon('navup', 16)."</td>";
    echo "<td>..</td>";
    echo "<td><a href='{$_SERVER['PHP_SELF']}?base={$up_one_level}'>Up</a></td>";
    echo "</tr>";
}

foreach ($entries AS $entry)
{
    $name = explode(',', $entry['dn']);
    $n = explode('=', $name[0]);
    
    echo "<tr>";
    
    if ($entry['type'] == 'container')
    {
        $a = urlencode($entry['dn']);
        "";
        echo "<td><a href='{$_SERVER['PHP_SELF']}?base={$a}&amp;field={$field}'>".icon('navdown', 16)."</a></td>";
        echo "<td><a href='{$_SERVER['PHP_SELF']}?base={$a}&amp;field={$field}'>".icon('kb', 16)."</a></td>";
        echo "<td><a href='{$_SERVER['PHP_SELF']}?base={$a}&amp;field={$field}'>{$n[1]}</a></td>";
    }
    else 
    {
        echo "<td></td>";
        echo "<td><a onclick=\"ldap_browse_update_group('{$entry['dn']}', '{$field}');\" href='javascript:void(0)'>".icon('site', 16)."</a></td>";
        echo "<td><a onclick=\"ldap_browse_update_group('{$entry['dn']}', '{$field}');\" href='javascript:void(0)'>{$n[1]}</a></td>";
    }
    
    echo "</tr>";
}

echo "</table>";

include (APPLICATION_INCPATH . 'minimal_footer.inc.php');

?>