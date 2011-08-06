<?php
// inventory.php - Browse inventory items
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

require ('core.php');
$permission = PERM_NOT_REQUIRED;
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');
$title = $strInventory;

if(!$CONFIG['inventory_enabled'])
{
    html_redirect('index.php', FALSE);
    exit;
}

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('inventory', 32)." {$strInventory}</h2>";
echo "<p align='center'>{$strInventoryDesc}</p>";

$sql = "SELECT COUNT(*) AS count, s.* FROM `{$dbInventory}` AS i, `{$dbSites}` AS s ";
$sql .= "WHERE siteid=s.id ";
$sql .= "GROUP BY siteid ";
$result = mysql_query($sql);

if (mysql_num_rows($result) > 0)
{
    echo "<table class='maintable'>";
    echo "<tr><th>{$strSite}</th><th>{$strCount}</th><th>{$strActions}</th></tr>";
    $shade = 'shade1';
    while ($row = mysql_fetch_object($result))
    {
        echo "<tr class='{$shade}'><td>".icon('site', 16, $strSite);
        echo " {$row->name}</td>";
        echo "<td>{$row->count}</td>";
        $operations[$strView] = "inventory_site.php?id={$row->id}";
        echo "<td>".html_action_links($operations)."</td>";
        echo "</tr>";
        if ($shade == 'shade1') $shade = 'shade2';
        else $shade = 'shade1';
    }
    echo "</table>";
}
else
{
    echo user_alert($strNoRecords, E_USER_NOTICE);
}

echo "<p align='center'><a href='inventory_new.php?newsite=1'>";
echo "{$strSiteNotListed}</a></p>";
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>