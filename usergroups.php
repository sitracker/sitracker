<?php
// usergroups.php - Manage user group membership
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


$permission = 23; // Edit user

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

$title = $strUserGroups;

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$action = cleanvar($_REQUEST['action']);

switch ($action)
{
    case 'savemembers':
        $sql = "SELECT * FROM `{$dbUsers}` ORDER BY realname";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        while ($user = mysql_fetch_object($result))
        {
            $usql = "UPDATE `{$dbUsers}` SET groupid = '".clean_int($_POST["group{$user->id}"])."' WHERE id='{$user->id}'";
            mysql_query($usql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
        }
        html_redirect("usergroups.php");
        break;
    case 'addgroup':
        $group = clean_dbstring($_REQUEST['group']);
        if (empty($group))
        {
            html_redirect("usergroups.php", FALSE, sprintf($strFieldMustNotBeBlank, "'{$strName}'"));
            exit;
        }
        $dsql = "SELECT name from `{$dbGroups}` WHERE name = '{$group}'";
        $dresult = mysql_query($dsql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        if (mysql_num_rows($dresult) > 0)
        {
            html_redirect("usergroups.php", FALSE, sprintf($strDoubletNameFound, "'{$group}'"));
            exit;
        }
        $sql = "INSERT INTO `{$dbGroups}` (name) VALUES ('{$group}')";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        html_redirect("usergroups.php");
        break;
    case 'deletegroup':
        $groupid = clean_int($_REQUEST['groupid']);
        // Remove group membership for all users currently assigned to this group
        $sql = "UPDATE `{$dbUsers}` SET groupid = '' WHERE groupid = '{$groupid}'";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

        // Remove the group
        $sql = "DELETE FROM `{$dbGroups}` WHERE id='{$groupid}' LIMIT 1";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
        html_redirect("usergroups.php");
        break;
    default:
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');

        echo "<h2>".icon('site', 32)." {$strUserGroups}</h2>";

        $gsql = "SELECT * FROM `{$dbGroups}` ORDER BY name";
        $gresult = mysql_query($gsql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        while ($group = mysql_fetch_object($gresult))
        {
            $grouparr[$group->id] = $group->name;
        }

        $numgroups = count($grouparr);

        echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
        echo "<table summary=\"{$strUserGroups}\" align='center'>";
        echo "<tr><th>{$strGroup}</th><th>{$strOperation}</th></tr>\n";
        if ($numgroups > 0)
        {
            foreach ($grouparr AS $groupid => $groupname)
            {
                echo "<tr><td>$groupname</td><td><a href='usergroups.php?groupid={$groupid}&amp;action=deletegroup'>{$strDelete}</a></td></tr>\n";
            }
        }
        echo "<tr><td><input type='text' name='group' value='' size='10' maxlength='255' />";
        echo "<input type='hidden' name='action' value='addgroup' />";
        echo "</td><td><input type='submit' name='add' value='{$strNew}' /></td></tr>\n";
        echo "</table>";
        echo "</form>";

        if ($numgroups > 0)
        {
            echo "<h3>{$strGroupMembership}</h3>";

            $sql = "SELECT * FROM `{$dbUsers}` WHERE status !=0 ORDER BY realname";  // status=0 means left company
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

            echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
            echo "<table summary='{$strGroupMembership}' align='center'>";
            echo "<tr><th>{$strUser}</th><th>{$strGroup}</th></tr>";
            while ($user = mysql_fetch_object($result))
            {
                echo "<tr><td>{$user->realname} ({$user->username})</td>";
                echo "<td>".group_drop_down("group{$user->id}",$user->groupid)."</td></tr>\n";
            }
            echo "</table>\n";

            echo "<p class='formbuttoms'>";
            echo "<input type='hidden' name='action' value='savemembers' />";
            echo "<input name='reset' type='reset' value='{$strReset}' /> ";
            echo "<input type='submit' value='{$strSave}' /></p>";
            echo "</form>";
        }
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>