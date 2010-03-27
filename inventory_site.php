<?php
// inventory_site.php - View site's inventory items
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

$permission = 0;

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = "$strInventory - $strSite";

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

if(!$CONFIG['inventory_enabled'])
{
    html_redirect('index.php', FALSE);
    exit;
}

if (is_numeric($_GET['id']))
{
    //View site inventory
    $siteid = cleanvar($_GET['id']);

    if (!empty($_REQUEST['filter']))
    {
        $filter = cleanvar($_REQUEST['filter']);
    }

    echo "<h2>".icon('site', 32)." ".site_name($siteid)."</h2>";
    echo "<p align='center'>";
    echo "<a href='inventory_add.php?site={$siteid}'>";
    echo icon('add', 16)." {$strAddNew}</a> | ";
    echo "<a href='inventory.php'>".icon('site', 16)." {$strBackToSites}</a></p>";
    $sql = "SELECT *, i.name AS name , i.id AS id, ";
    $sql .= "i.notes AS notes, ";
    $sql .= "i.active AS active ";
    $sql .= "FROM `{$dbInventory}` AS i, `{$dbSites}` AS s ";
    $sql .= "WHERE siteid='{$siteid}' ";
    $sql .= "AND siteid=s.id ";
    if (!empty($filter))
    {
//         $sql .= "AND type='{$filter}' ";
    }
    if ($_SESSION['userconfig']['show_inactive_data'] != 'TRUE')
    {
        $sql .= "AND i.active = 1 ";
    }
    $sql .= "ORDER BY i.active DESC, ";
    $sql .= "i.modified DESC";
    //$sql .= "GROUP BY type DESC ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

//     echo "<form action='{$_SERVER['PHP_SELF']}?site={$siteid}' method='post'>";
//     echo "<p align='center'>".icon('filter', 16)." {$strFilter}: ";
//     echo "<select name='filter' onchange='form.submit();'>";
//     echo "<option value=''></option>";
//     foreach ($CONFIG['inventory_types'] as $code => $name)
//     {
//         echo "<option value='{$code}'";
//         if ($filter == $code)
//         {
//             echo " selected='selected' ";
//         }
//         echo ">{$name}</option>";
//     }
//     echo "</select> <a href='{$_SERVER['PHP_SELF']}?site={$siteid}'>";
//     echo "{$strClearFilter}</a></p>";
//     echo "</form>";

    if (mysql_num_rows($result) > 0)
    {
        echo "<table align='center'>";
        echo "<tr><th>{$strInventoryItems}</th><th>{$strPrivacy}</th>";
        echo "<th>{$strCreatedBy}</th><th>{$strOwner}</th><th>{$strActions}</th></tr>";
        $shade = 'shade1';
        while ($row = mysql_fetch_object($result))
        {
            echo "<tr class='{$shade}'><td>".icon('inventory', 16);
            echo " {$row->name}, {$CONFIG['inventory_types'][$row->type]}";

            if ($row->active != 1)
            {
                echo " (inactive)";
            }
            echo "</td><td align='center'>";
            if ($row->privacy == 'private')
            {
                echo icon('private', 16);
            }
            elseif ($row->privacy == 'adminonly')
            {
                echo icon('review', 16, $strAdmin);
            }
            echo "</td><td>".user_realname($row->createdby)."</td><td>";
            echo contact_realname($row->contactid)."</td><td>";

            if (($row->privacy == 'private' AND $sit[2] != $row->createdby) OR
                 $row->privacy == 'adminonly' AND !user_permission($sit[2], 22))
            {
                echo "{$strView}</a> &nbsp; ";
                echo "{$strEdit}</td></tr>";
            }
            else
            {
                echo "<a href='inventory_view.php?id={$row->id}'>{$strView}</a> &nbsp; ";
                echo "<a href='inventory_edit.php?id={$row->id}'>{$strEdit}</td></tr>";
            }
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        echo "</table>";
        echo "<p align='center'>".icon('add', 16);
        echo " <a href='inventory_add.php?site={$siteid}'>";
        echo "{$strAddNew}</a></p>";
    }
    else
    {
        echo "<p class='info'>{$strNoRecords}</p>";
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>