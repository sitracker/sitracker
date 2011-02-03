<?php
// role_add.php - Page to add role to SiT!
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul@sitracker.org>

$permission = 9; // Edit User Permissions

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$roleid = clean_int($_REQUEST['roleid']);

$title = $strRole;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>{$strRole}</h2>";

if (!empty($roleid))
{
    $sql = "SELECT * FROM `{$dbRoles}` WHERE id = {$roleid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $obj = mysql_fetch_object($result);
        echo "<table class='vertical' align='center'>";
        echo "<tr><th>{$strRole}</th><td>{$roleid}</td></tr>";
        echo "<tr><th>{$strName}</th><td>{$obj->rolename}</td></tr>";
        echo "<tr><th>{$strDescription}</th><td>{$obj->description}</td></tr>";
        echo "</table>";

        echo "<p align='center'><a href='role_edit.php?roleid={$roleid}'>{$strEdit}</a></p>";

        echo "<h3>{$strUsers}</h3>";
        $sql = "SELECT id, realname FROM `{$dbUsers}` WHERE roleid = {$roleid}";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) == 0)
        {
            echo "<p align='center'>{$strNoRecords}</p>";
        }
        else
        {
            $class = 'shade1';
            echo "<table align='center' class='vertical'>";
            while ($obj = mysql_fetch_object($result))
            {
                echo "<tr class='{$class}'><th>{$obj->id}</th>";
                echo "<td>{$obj->realname}</td></tr>";
                if ($class == 'shade1') $class = 'shade2';
                else $class = 'shade1';
            }
            echo "</table>";
        }

        echo "<h3>{$strPermissions}</h3>";
        $sql = "SELECT p.* FROM `{$dbPermissions}` AS p, `{$dbRolePermissions}` AS rp WHERE ";
        $sql .= "p.id = rp.permissionid AND rp.roleid = {$roleid} AND granted = 'true'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($result) == 0)
        {
            echo "<p align='center'>{$strNoRecords}</p>";
        }
        else
        {
            $class = 'shade1';
            echo "<table align='center' class='vertical'>";
            while ($obj = mysql_fetch_object($result))
            {
                echo "<tr class='{$class}'><th>";
                echo "<a href='edit_user_permissions.php?action=check&amp;permid={$obj->id}' title='{$strCheckWhoHasThisPermission}'>{$obj->id}</a></th>";
                echo "<td>{$obj->name}</td></tr>";
                if ($class == 'shade1') $class = 'shade2';
                else $class = 'shade1';
            }
            echo "</table>";
        }
    }
    else
    {
        echo "<p class='error'>{$strNoRecords}</p>";
    }
}
else
{
    echo "<p class='error'>{$strNoRoleSpecified}</p>";
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>