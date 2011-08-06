<?php
// inventory_view.php - View inventory items
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
require (APPLICATION_LIBPATH . 'auth.inc.php');

if(!$CONFIG['inventory_enabled'])
{
    html_redirect('index.php', FALSE);
    exit;
}

$title = "$strInventory - $strView";

if (is_numeric($_GET['id']))
{
    //View site inventory
    $id = clean_int($_GET['id']);

    if (!empty($_REQUEST['filter']))
    {
        $filter = cleanvar($_REQUEST['filter']);
    }

    $sql = "SELECT *, i.name AS name , i.id AS id, ";
    $sql .= "i.notes AS notes, ";
    $sql .= "i.active AS active ";
    $sql .= "FROM `{$dbInventory}` AS i ";
    $sql .= "WHERE i.id='{$id}' ";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);


    if (mysql_num_rows($result) > 0)
    {
        $row = mysql_fetch_object($result);
        if (($row->privacy == 'private' AND $sit[2] != $row->createdby) OR
             $row->privacy == 'adminonly' AND !user_permission($sit[2], PERM_ADMIN))
        {
            html_redirect('inventory.php', FALSE);
            exit;
        }

        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>".icon('inventory', 32)." {$strInventory}</h2>";

        echo "<div id='container' style='width: 40%'>";
        echo "<h3>{$row->name}";

        if ($row->active != 1)
        {
            echo " (inactive)";
        }
        echo " (<small><a href='inventory_edit.php?id={$id}'>";
        echo "{$strEdit}</a></small>)</h3>";
        echo "<p><strong>{$strType}:</strong> ";
        echo "{$CONFIG['inventory_types'][$row->type]}</p>";
        if (!empty($row->identifier))
        {
            echo "<p><strong>{$strID}:</strong> {$row->identifier}</p>";
        }

        echo "<p><strong>{$strAddress}:</strong> $row->address</p>";
        if (!empty($row->contactid))
        {
            echo "<p><strong>{$strOwner}:</strong> ";
            echo "<a href='contact_details.php?id={$row->contactid}'>";
            echo contact_realname($row->contactid)."</a></p>";
        }
        echo "<p><strong>{$strUsername}:</strong> ";
        if (($row->privacy == 'adminonly' AND !user_permission($sit[2], PERM_ADMIN)) OR
            ($row->privacy == 'private' AND $row->createdby != $sit[2]))
        {
            echo "<strong>{$strWithheld}</strong>";
        }
        else
        {
            echo $row->username;
        }
        echo "</p>";
        echo "<p><strong>{$strPassword}:</strong> ";
        if (($row->privacy == 'adminonly' AND !user_permission($sit[2], PERM_ADMIN)) OR
            ($row->privacy == 'private' AND $row->createdby != $sit[2]))
        {
            echo "<strong>{$strWithheld}</strong>";
        }
        else
        {
            echo $row->password;
        }
        echo "</p>";
        if (!empty($row->notes))
        {
            echo "<p><strong>{$strNotes}: </strong> ".bbcode($row->notes)."</p>";
        }
        echo "<p><strong>{$strCreatedBy}:</strong> ".user_realname($row->createdby);
        echo " {$row->created}, <strong>{$strLastModifiedBy}:</strong> ";
        echo user_realname($row->modifiedby)." {$row->modified}</p>";
        echo "</div>";

        echo "<p class='inventory'><a href='inventory_site.php?id={$row->siteid}'>";
        echo "{$strBackToList}</a></p>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
    else
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>".icon('inventory', 32)." {$strInventory}</h2>";
        echo "<table class='maintable'>";
        echo "<tr><td>" . user_alert($strNoRecords, E_USER_NOTICE) . "</td></tr>";
        echo "</table>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
}
else
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('inventory', 32)." {$strInventory}</h2>";
    echo "<table class='maintable'>";
    echo "<tr><td>" . user_alert($strNoRecords, E_USER_NOTICE) . "</td></tr>";
    echo "</table>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>