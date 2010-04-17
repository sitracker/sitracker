<?php
// inventory_add.php - Add inventory items
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

$title = "$strInventory - $strAdd";

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

if(!$CONFIG['inventory_enabled'])
{
    html_redirect('index.php', FALSE);
    exit;
}

if (!empty($_GET['site']))
{
    $siteid = cleanvar($_GET['site']);
}
$newsite = cleanvar($_GET['newsite']);

if (!empty($_POST['submit']) AND !empty($_POST['name']) AND $_POST['site'] != 0)
{
    $post = cleanvar($_POST);

    $sql = "INSERT INTO `{$dbInventory}`(address, username, password, type,";
    $sql .= " notes, created, createdby, modified, modifiedby, active,";
    $sql .= " name, siteid, privacy, identifier, contactid) VALUES('{$post['address']}', ";
    $sql .= "'{$post['username']}', '{$post['password']}', ";
    $sql .= "'{$post['type']}', ";
    $sql .= "'{$post['notes']}', NOW(), '{$sit[2]}', NOW(), ";
    $sql .= "'{$sit[2]}', '1', '{$post['name']}', '{$post['site']}', ";
    $sql .= "'{$post['privacy']}', '{$post['identifier']}', '{$post['owner']}')";

    mysql_query($sql);
    $id = mysql_insert_id();
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    else html_redirect("inventory_view.php?id={$id}");
}
else
{
    if (!empty($_POST['submit']) AND empty($_POST['name']))
    {
        echo "<p class='error'>".sprintf($strFieldMustNotBeBlank, $strName)."</p>";
    }
    elseif (!empty($_POST['submit']) AND $_POST['site'] == 0)
    {
        echo "<p class='error'>".sprintf($strFieldMustNotBeBlank, $strSite)."</p>";
    }
    echo "<h2>".icon('add', 32)." {$strAdd}</h2>";

    $url = "{$_SERVER['PHP_SELF']}?action=new";
    if (!empty($_GET['site']))
    {
        $siteid = intval($_GET['site']);
        $url = $url."&amp;site={$siteid}";
    }

    echo "<form action='{$url}' method='post'>";
    echo "<table class='vertical' align='center'>";
    echo "<tr><th>{$strName}</th>";
    echo "<td><input class='required' name='name' value='{$row->name}' />";
    echo " <span class='required'>{$strRequired}</span></td></tr>";
    echo "<tr><th>{$strType}</th>";
    echo "<td>".array_drop_down($CONFIG['inventory_types'], 'type', $row->type, '', TRUE)."</td></tr>";

    if (!intval($siteid))
    {
        echo "<tr><th>{$strSite}</th><td>";
        echo site_drop_down('site', 0, TRUE);
        echo " <span class='required'>{$strRequired}</td>";
        echo "<tr><th>{$strOwner}</th><td>";
        echo contact_site_drop_down('owner', '');
        echo "</td></tr>";
    }
    else
    {
        echo "<tr><th>{$strOwner}</th><td>";
        echo "<input type='hidden' id='site' name='site' value='{$siteid}' />";
        echo contact_site_drop_down('owner', $row->contactid, $siteid, NULL, FALSE);
        echo "</td></tr>";
    }
    echo "<tr><th>{$strID} ".help_link('InventoryID')."</th>";
    echo "<td><input name='identifier' value='{$row->identifier}' /></td></tr>";
    echo "<tr><th>{$strAddress}</th>";
    echo "<td><input name='address' value='{$row->address}' /></td></tr>";
    echo "<tr><th>{$strUsername}</th>";
    echo "<td><input name='username' value='{$row->username}' /></td></tr>";
    echo "<tr><th>{$strPassword}</th>";
    echo "<td><input name='password' value='{$row->password}' /></td></tr>";


    echo "<tr><th>{$strNotes}</th>";
    echo "<td>";
    echo bbcode_toolbar('inventorynotes');
    echo "<textarea id='inventorynotes' rows='15' cols='60' name='notes'>{$row->notes}</textarea></td></tr>";

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
    echo "</td></tr>";
    echo "</table>";
    echo "<p align='center'>";
    echo "<input name='submit' type='submit' value='{$strAdd}' /></p>";
    echo "</form>";
    echo "<p align='center'>";

    if ($newsite)
    {
        echo icon('site', 16);
        echo " <a href='inventory.php'>{$strBackToSites}</a>";
    }
    else
    {
        echo "<a href='inventory_site.php?id={$siteid}'>{$strBackToList}</a>";
    }
    echo "</p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>
