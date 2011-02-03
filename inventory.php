<?php
// inventory.php - Browse inventory items
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

$permission = 0;

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');
$title = $strInventory;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');


if(!$CONFIG['inventory_enabled'])
{
    html_redirect('index.php', FALSE);
    exit;
}

echo "<h2>".icon('inventory', 32)." {$strInventory}</h2>";
echo "<p align='center'>{$strInventoryDesc}</p>";

$sql = "SELECT COUNT(*) AS count, s.* FROM `{$dbInventory}` AS i, `{$dbSites}` AS s ";
$sql .= "WHERE siteid=s.id ";
$sql .= "GROUP BY siteid ";
$result = mysql_query($sql);

if (mysql_num_rows($result) > 0)
{
    echo "<table class='vertical' align='center'>";
    echo "<tr><th>{$strSite}</th><th>{$strCount}</th></tr>";
    while ($row = mysql_fetch_object($result))
    {
        echo "<tr><td>".icon('site', 16);
        echo " <a href='inventory_site.php?id={$row->id}'>{$row->name}</a></td>";
        echo "<td>{$row->count}</td></tr>";
    }
    echo "</table>";
}
else
{
    echo "<p class='info'>{$strNoRecords}</p>";
}

echo "<p align='center'><a href='inventory_new.php?newsite=1'>";
echo "{$strSiteNotListed}</a></p>";
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');



?>
