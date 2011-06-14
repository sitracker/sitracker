<?php
// inventory_edit.php - Edit inventory items
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
require (APPLICATION_LIBPATH . 'auth.inc.php');

if(!$CONFIG['inventory_enabled'])
{
    html_redirect('index.php', FALSE);
    exit;
}

$title = "$strInventory - $strEdit";


$id = clean_int($_GET['id']);
$siteid = clean_int($_REQUEST['site']);

// if (!empty($_GET['newsite']))
// {
//     $newsite = TRUE;
// }
// else
// {
//     $newsite = FALSE;
//     $siteid = intval($_GET['site']);
// }

if (isset($_POST['submit']))
{
    $post = cleanvar($_POST);

    if ($post['active'] == 'on')
    {
        $post['active'] = 1;
    }
    elseif (isset($post['active']))
    {
        $post['active'] = 0;
    }

    $sql = "UPDATE `{$dbInventory}` ";
    $sql .= "SET address='{$post['address']}', ";
    if (isset($post['username']))
    {
        $sql .= "username='{$post['username']}', ";
    }

    if (isset($post['password']))
    {
        $sql .= "password='{$post['password']}', ";
    }

    $sql .= "type='{$post['type']}', ";
    $sql .= "notes='{$post['notes']}', modified=NOW(), ";
    $sql .= "modifiedby='{$sit[2]}', ";
    $sql .= "name='{$post['name']}', ";
    $sql .= "siteid='{$post['site']}', ";
    $sql .= "contactid='{$post['owner']}', identifier='{$post['identifier']}' ";

    if (isset($post['privacy']))
    {
        $sql .= ", privacy='{$post['privacy']}' ";
    }

    if (isset($post['active']))
    {
        $sql .= ", active='{$post['active']}' ";
    }
    else
    {
        $sql .= ", active='0' ";
    }

    $sql .= " WHERE id='{$id}'";

    mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    else html_redirect("inventory_site.php?id={$siteid}");
}
else
{
    $sql = "SELECT * FROM `{$dbInventory}` WHERE id='{$id}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $row = mysql_fetch_object($result);

    if (($row->privacy == 'private' AND $sit[2] != $row->createdby) OR
            $row->privacy == 'adminonly' AND !user_permission($sit[2], 22))
    {
        html_redirect('inventory.php', FALSE);
        exit;
    }
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('edit', 32)." {$strEdit}</h2>";

    echo "<form action='{$_SERVER['PHP_SELF']}?id={$id}' method='post'>";

    echo "<table class='vertical' align='center'>";
    echo "<tr><th>{$strName}</th>";
    echo "<td><input class='required' name='name' value='{$row->name}' /> ";
    echo "<span class='required'>{$strRequired}</span></td></tr>";
    echo "<tr><th>{$strType}</th>";
    echo "<td>".array_drop_down($CONFIG['inventory_types'], 'type', $row->type, '', TRUE)."</td></tr>";

    echo "<tr><th>{$strSite}</th><td>";
    echo site_drop_down('site', $row->siteid, TRUE);
    echo " <span class='required'>{$strRequired}</span></td></tr>";
    echo "<tr><th>{$strOwner}</th><td>";
    echo contact_site_drop_down('owner', $row->contactid);
    echo "</td></tr>";

    echo "<tr><th>{$strID} ".help_link('InventoryID')."</th>";
    echo "<td><input name='identifier' value='{$row->identifier}' /></td></tr>";
    echo "<tr><th>{$strAddress}</th>";
    echo "<td><input name='address' value='{$row->address}' /></td></tr>";

    if (!is_numeric($id)
        OR (($row->privacy == 'adminonly' AND user_permission($sit[2], 22))
            OR ($row->privacy == 'private' AND ($row->createdby == $sit[2])) OR $row->privacy == 'none'))
    {
        echo "<tr><th>{$strUsername}</th>";
        echo "<td><input name='username' value='{$row->username}' /></td></tr>";
        echo "<tr><th>{$strPassword}</th>";
        echo "<td><input name='password' value='{$row->password}' /></td></tr>";
    }

    echo "<tr><th>{$strNotes}</th>";
    echo "<td>";
    echo bbcode_toolbar('inventorynotes');
    echo "<textarea id='inventorynotes' rows='15' cols='80' name='notes'>{$row->notes}</textarea></td></tr>";

    if (($row->privacy == 'adminonly' AND user_permission($sit[2], 22)) OR
        ($row->privacy == 'private' AND $row->createdby == $sit[2]) OR
        $row->privacy == 'none')
    {
        echo "<tr><th>{$strPrivacy} ".help_link('InventoryPrivacy')."</th>";
        echo "<td><input type='radio' name='privacy' value='private' ";
        if ($row->privacy == 'private')
        {
            echo " checked='checked' ";
            $selected = TRUE;
        }
        echo "/>{$strPrivate}<br />";

        echo "<input type='radio' name='privacy' value='adminonly'";
        if ($row->privacy == 'adminonly')
        {
            echo " checked='checked' ";
            $selected = TRUE;
        }
        echo "/>";
        echo "{$strAdminOnly}<br />";

        echo "<input type='radio' name='privacy' value='none'";
        if (!$selected)
        {
            echo " checked='checked' ";
        }
        echo "/>";
        echo "{$strNone}<br />";
    }

    echo "</td></tr>";

    echo "<tr><th>{$strActive}</th>";
    echo "<td><input type='checkbox' name='active' ";
    if ($row->active == '1')
    {
        echo "checked = 'checked' ";
    }
    echo "/>";

    echo "</td></tr>";
    echo "</table>";
    echo "<p class='formbuttons'>";
    echo "<input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "</form>";
    echo "<p align='center'>";

    echo "<a href='inventory_site.php?id={$row->siteid}'>{$strReturnWithoutSaving}</a>";

    echo "</p>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>