<?php
// role.php - Page to add role to SiT!
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul@sitracker.org>

require ('core.php');
$permission = PERM_USER_PERMISSIONS_EDIT; // Edit User Permissions
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$roleid = clean_int($_REQUEST['roleid']);

$title = $strViewRole;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>{$title}</h2>";

if (!empty($roleid))
{
    $sql = "SELECT * FROM `{$dbRoles}` WHERE id = {$roleid}";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

    if (mysqli_num_rows($result) > 0)
    {
        $obj = mysqli_fetch_object($result);
        echo "<table class='vertical maintable'>";
        echo "<tr><th>{$strRole}</th><td>{$roleid}</td></tr>";
        echo "<tr><th>{$strName}</th><td>{$obj->rolename}</td></tr>";
        echo "<tr><th>{$strDescription}</th><td>{$obj->description}</td></tr>";
        echo "</table>";

        echo "<p align='center'><a href='role_edit.php?roleid={$roleid}'>{$strEdit}</a></p>";

        echo "<h3>{$strUsers}</h3>";
        $sql = "SELECT id, realname FROM `{$dbUsers}` WHERE roleid = {$roleid}";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        if (mysqli_num_rows($result) == 0)
        {
            echo user_alert($strNoRecords, E_USER_NOTICE);
        }
        else
        {
            $class = 'shade1';
            echo "<table class='maintable'>";
            echo "<tr>";
            echo colheader('userid', $strID);
            echo colheader('name', $strName);
            echo "</tr>";
            while ($obj = mysqli_fetch_object($result))
            {
                echo "<tr class='{$class}'><td>{$obj->id}</td>";
                echo "<td>{$obj->realname}</td></tr>";
                if ($class == 'shade1') $class = 'shade2';
                else $class = 'shade1';
            }
            echo "</table>";
        }

        echo "<h3>{$strPermissions}</h3>";
        $sql = "SELECT p.* FROM `{$dbPermissions}` AS p, `{$dbRolePermissions}` AS rp WHERE ";
        $sql .= "p.id = rp.permissionid AND rp.roleid = {$roleid} AND granted = 'true'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

        if (mysqli_num_rows($result) == 0)
        {
            echo user_alert($strNoRecords, E_USER_NOTICE);
        }
        else
        {
            $class = 'shade1';
            echo "<table class='maintable'>";
            echo "<tr>";
            echo colheader('permissionid', $strID);
            echo colheader('permissionname', $strName);
            echo "</tr>";
            while ($obj = mysqli_fetch_object($result))
            {
                echo "<tr class='{$class}'><th>";
                echo "<a href='edit_user_permissions.php?action=check&amp;permid={$obj->id}' title='{$strCheckWhoHasPermission}'>{$obj->id}</a></th>";
                echo "<td>{$GLOBALS["{$obj->name}"]}</td></tr>";
                if ($class == 'shade1') $class = 'shade2';
                else $class = 'shade1';
            }
            echo "</table>";
        }
        echo "<p clasS='return'><a href='edit_user_permissions.php'>{$strReturnToPreviousPage}</a></p>";
    }
    else
    {
        echo user_alert($strNoRecords, E_USER_NOTICE);
    }
}
else
{
    echo user_alert($strNoRoleSpecified, E_USER_ERROR);
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>