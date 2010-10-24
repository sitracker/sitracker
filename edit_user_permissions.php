<?php
// edit_user_permissions.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

$permission = 9; // Edit User Permissions

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$title = $strSetPermissions;

// Restrict resetting permissions in demo mode for all but the first user (usually admin)
if ($CONFIG['demo'] AND $_SESSION['userid'] != 1)
{
    html_redirect("manage_users.php", FALSE, $strCannotPerformOperationInDemo);
}

$pagescripts = array('FormProtector.js');
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

// External variables
$user = clean_int($_REQUEST['user']);
$role = clean_int($_REQUEST['role']);
$action = cleanvar($_REQUEST['action']);
$permselection = cleanvar($_REQUEST['perm']);
$permid = clean_int($_REQUEST['permid']);
$seltab = cleanvar($_REQUEST['tab']);

if (empty($action) OR $action == "showform")
{
    $sql = "SELECT * FROM `{$dbRoles}` ORDER BY id ASC";
    $result= mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) >= 1)
    {
        echo "<h2>".icon('trigger', 32)." {$strRolePermissions}</h2>";

        echo "<p align='center'><a href='role_add.php'>{$strAddRole}</a></p>";

        echo "<div class='tabcontainer'>";
        echo "<ul>";
        $csql = "SELECT * FROM `{$dbPermissionCategories}` ORDER BY id ASC";
        $cresult = mysql_query($csql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        if ($cresult AND mysql_num_rows($cresult) > 0)
        {
            if (empty($seltab)) $seltab = 1;
            while ($pcat = mysql_fetch_object($cresult))
            {
                echo "<li";
                if ($seltab == $pcat->id) echo " class='active'";
                echo "><a href='{$_SERVER['PHP_SELF']}?tab={$pcat->id}'>{$GLOBALS[$pcat->category]}</a></li>";
                $cat[$pcat->id] = $pcat->category;
            }
        }
        echo "</ul>";
        echo "</div>";

        echo "<div style='clear: both; margin-top:1em;'></div>";
        echo "<form id='permissionsform' action='{$_SERVER['PHP_SELF']}' method='post' onsubmit=\"return confirm_action('{$strAreYouSureMakeTheseChanges}')\">";
        echo "<fieldset><legend>{$GLOBALS[$cat[$seltab]]}</legend>";
        echo "<table>";
        $psql = "SELECT * FROM `{$dbPermissions}` WHERE categoryid = {$seltab} ORDER BY id ASC";
        $presult = mysql_query($psql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        $class = 'shade1';
        echo "<tr>";
        echo "<th>{$GLOBALS[$pcat->category]} {$strPermissions}</th>";
        while ($rolerow = mysql_fetch_object($result))
        {
            echo "<th style='min-width: 40px;'><a href='role.php?roleid={$rolerow->id}'>{$rolerow->rolename}</a></th>";
        }
        echo "</tr>\n";
        while ($perm = mysql_fetch_object($presult))
        {
            echo "<tr class='$class' onclick='trow(event);'>";
            echo "<td><a href='{$PHP_SELF}?action=check&amp;permid={$perm->id}' title='{$strCheckWhoHasThisPermission}'>{$perm->id}</a> {$GLOBALS[$perm->name]}</td>";
            mysql_data_seek($result, 0);
            while ($rolerow = mysql_fetch_object($result))
            {
                $rpsql = "SELECT * FROM `{$dbRolePermissions}` WHERE roleid='{$rolerow->id}' AND permissionid='{$perm->id}'";
                $rpresult = mysql_query($rpsql);
                $rp = mysql_fetch_object($rpresult);
                echo "<td style='text-align:center;'><input name='{$rolerow->id}perm[]' type='checkbox' value='{$perm->id}' ";
                if ($rp->granted=='true') echo " checked='checked'";
                echo " /></td>";
            }
            echo "</tr>\n";
            if ($class == 'shade2') $class = "shade1";
            else $class = "shade2";
        }
        echo "</table>";
        if (mysql_num_rows($presult) < 1) echo "<p>{$strNothingToDisplay}</p>";

        echo "</fieldset>";
        echo "<p><input name='reset' type='reset' value='{$strReset}' />";
        echo "<input type='hidden' name='action' value='update' />";
        echo "<input type='hidden' name='role' value='update' />";
        echo "<input type='hidden' name='tab' value='{$seltab}' />";
        echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
        echo "</form>";
        echo protectform('permissionsform');
    }
}
elseif ($action == "edit" && (!empty($user) OR !empty($role)))
{
    // Show form
    if (!empty($role) AND !empty($user))
    {
        trigger_error("{$strCannotEditUserAndRole}", E_USER_ERROR);
    }

    if (!empty($user))
    {
        echo "<h2>".icon('trigger', 32)." ".sprintf($strSetPermissionsForUserX, user_realname($user))."</h2>";
    }
    else
    {
        echo "<h2>".icon('trigger', 32)." ".sprintf($strSetPermissionsForRoleX, db_read_column('rolename', $dbRoles, $role))."</h2>";
    }
    if (!empty($user)) echo "<p align='center'>{$strPermissionsInhereitedCannotBeChanged}</p>";

    // Next lookup the permissions
    $sql = "SELECT * FROM `{$dbUsers}` AS u, `{$dbRolePermissions}` AS rp WHERE u.roleid = rp.roleid AND u.id = '$user' AND granted='true'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    $userrolepermission = array();
    if (mysql_num_rows($result) >= 1)
    {
        while ($roleperm = mysql_fetch_object($result))
        {
           $userrolepermission[] = $roleperm->permissionid;
        }
    }
    echo "<form action='{$_SERVER['PHP_SELF']}?action=update' method='post' onsubmit=\"return confirm_action('{$strAreYouSureMakeTheseChanges}')\">";
    echo "<table align='center'>
    <tr>
    <th>{$strID}</th>
    <th>{$strRolePermissions}</th>
    <th>{$strPermission}</th>
    </tr>\n";
    if (empty($role) AND !empty($user))
    {
        $sql = "SELECT id, name, up.granted AS granted FROM `{$dbPermissions}` AS p, `{$dbUserPermissions}` AS up ";
        $sql.= "WHERE p.id = up.permissionid ";
        $sql.= "AND up.userid='{$user}' ";
    }
    else
    {
        $sql = "SELECT id, name, rp.granted AS granted FROM `{$dbPermissions}` AS p, `{$dbRolePermissions}` AS rp ";
        $sql.= "WHERE p.id = rp.permissionid ";
        $sql.= "AND rp.roleid='{$role}' ";
    }
    $permission_result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    while ($row = mysql_fetch_array($permission_result))
    {
        $permission_array[$row['id']] = $row;
    }

    $sql = "SELECT * FROM `{$dbPermissions}`  ORDER BY id ASC";
    $result= mysql_query($sql);
    $class='shade1';

    while ($permissions = mysql_fetch_object($result))
    {
        echo "<tr class='{$class}' onclick='trow(event);'>";
        echo "<td><a href='{$_SERVER['PHP_SELF']}?action=check&amp;permid={$permissions->id}'  title='{$strCheckWhoHasPermission}'>";
        echo "{$permissions->id}</a> {$GLOBALS[$permissions->name]}</td>";
        if (!in_array($permissions->id, $userrolepermission))
        {
            echo "<td style='text-align:center;'><input name='dummy[]' type='checkbox' disabled='disabled' /></td>";
            echo "<td style='text-align:center;'>";
            echo "<input name=\"perm[]\" type=\"checkbox\" value=\"{$permissions->id}\"";
            if ($permission_array[$permissions->id]['granted'] == 'true') echo " checked='checked'";
            echo " />";
        }
        else
        {
            echo "<td style='text-align:center;'><input name='roledummy[]' type='checkbox' checked='checked' disabled='disabled' /></td>";
            echo "<td style='text-align:center;'><input name='dummy[]' type='checkbox' checked='checked' disabled='disabled' />";
            echo "<input type='hidden' name='perm[]' value='{$permissions->id}' />";
        }
        echo "</td></tr>\n";
        if ($class == 'shade2') $class = "shade1";
        else $class = "shade2";
    }
    echo "</table>";
    echo "<p><input name='user' type='hidden' value='{$user}' />";
    echo "<input name='role' type='hidden' value='{$role}' />";
    echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "</form>";
}
elseif ($action == "update")
{
    $errors = 0;
    // If no role or user is specified we're setting all role permissions
    if (empty($role) AND empty($user))
    {
        $sql = "SELECT * FROM `{$dbRoles}` ORDER BY id ASC";
        $result= mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        while ($rolerow = mysql_fetch_object($result))
        {
            // First pass, set all access to false
            $sql = "UPDATE `{$dbRolePermissions}` SET granted='false' WHERE roleid='{$rolerow->id}'";
            $aresult = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

            if (!$aresult) echo user_alert("{$strUpdateRolePermissionsFailed}", E_USER_WARNING);

            // Second pass, loop through checkbox array setting access to true where boxes are checked
            if (is_array($_POST["{$rolerow->id}perm"]))
            {
                reset ($_POST["{$rolerow->id}perm"]);
                while ($x = each($_POST["{$rolerow->id}perm"]))
                {
                    $sql = "UPDATE `{$dbRolePermissions}` SET granted='true' WHERE roleid='{$rolerow->id}' AND permissionid='{$x[1]}' ";
                    // echo "Updating permission ".$x[1]."<br />";
                    // flush();
                    $uresult = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
                    if (mysql_affected_rows() < 1 || $uresult == FALSE)
                    {
                        // Update failed, this could be because of a missing userpemissions record so try and create one
                        // echo "Update of permission ".$x[1]."failed, no problem, will try insert instead.<br />";
                        $isql = "INSERT INTO `{$dbRolePermissions}` (roleid, permissionid, granted) ";
                        $isql .= "VALUES ('{$rolerow->id}', '".$x[1]."', 'true')";
                        $iresult = mysql_query($isql);
                        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
                        if (mysql_affected_rows() < 1) echo user_alert("{$strUpdateUserPermission} ".$x[1]." {$strFailedOnPass2}", E_USER_WARNING);
                    }
                }
            }

        }
        html_redirect("manage_users.php");
        exit;
    }
    journal(CFG_LOGGING_NORMAL, '{$strUserPermissionsEdited}', "{$strUserXPermissionsEdited}", CFG_JOURNAL_USERS, $user);

    // Edit the users permissions
    if (empty($role) AND !empty($user))
    {
        // First pass, set all access to false
        $sql = "UPDATE `{$dbUserPermissions}` SET granted='false' WHERE userid='{$user}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

        // Second pass, loop through checkbox array setting access to true where boxes are checked
        if (is_array($permselection))
        {
            //reset ($permselection);
            while ($x = each($permselection))
            {
                $sql = "UPDATE `{$dbUserPermissions}` SET granted='true' WHERE userid='$user' AND permissionid='".$x[1]."' ";
                # echo "Updating permission ".$x[1]."<br />";
                # flush();
                $result = mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                if (mysql_affected_rows() < 1 || $result == FALSE)
                {
                    // Update failed, this could be because of a missing userpemissions record so try and create one
                    // echo "Update of permission ".$x[1]."failed, no problem, will try insert instead.<br />";
                    $isql = "INSERT INTO `{$dbUserPermissions}` (userid, permissionid, granted) ";
                    $isql .= "VALUES ('$user', '".$x[1]."', 'true')";
                    $iresult = mysql_query($isql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                    if (mysql_affected_rows() < 1)
                    {
                        echo user_alert("{$strUpdateUserPermission} ".$x[1]." {$strFailedOnPass2}", E_USER_WARNING);
                    }
                }
            }
        }
        html_redirect("manage_users.php");
        exit;
    }
    if ($role == 'update')
    {
        // Edit the role permissions

        // Get an array of roles
        $rsql = "SELECT id FROM `{$dbRoles}`";
        $rresult = mysql_query($rsql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        while (list($roleid) = mysql_fetch_row($rresult))
        {
            $roles[] = $roleid;
        }
        unset($roleid);

        foreach($roles AS $roleid)
        {
            // Get a a list of perms
            $psql = "SELECT permissionid FROM `{$dbRolePermissions}` AS rp, `{$dbPermissions}` AS p ";
            $psql .= "WHERE rp.permissionid = p.id AND rp.roleid={$roleid} AND p.categoryid = {$seltab}";
            $presult = mysql_query($psql);
            if ($presult AND mysql_num_rows($presult))
            {
                while (list($permid) = mysql_fetch_row($presult))
                {
                    $var = "{$roleid}perm";
                    if (in_array($permid, $_REQUEST[$var])) $granted = 'true';
                    else $granted = 'false';
                    $sql = "UPDATE `{$dbRolePermissions}` SET granted='{$granted}' WHERE roleid={$roleid} AND permissionid = {$permid}";
                    $result = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                }
            }
        }
        html_redirect("{$_SERVER['PHP_SELF']}?tab={$seltab}");
        exit;
    }
}
elseif ($action == "check")
{
    echo "<h2>".icon('trigger', 32)." {$strCheckUserAndRolePermissions}</h2>";
    if (!empty($permid))
    {
        // permission_names needs i18n bug 545
        echo "<h3>".sprintf($strRolePermissionsXY, $permid, permission_name($permid))."</h3>";
        $sql = "SELECT rp.roleid AS roleid, username, u.id AS userid, realname, rolename ";
        $sql .= "FROM `{$dbRolePermissions}` AS rp, `{$dbRoles}` AS r, `{$dbUsers}` AS u ";
        $sql .= "WHERE rp.roleid = r.id ";
        $sql .= "AND r.id = u.roleid ";
        $sql .= "AND permissionid = '$permid' AND granted='true' ";
        $sql .= "AND u.status > 0";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        if (mysql_num_rows($result) >= 1)
        {
            echo "<table align='center'>";
            echo "<tr><th>{$strUser}</th><th>{$strRole}</th></tr>";
            $shade = 'shade1';
            while ($user = mysql_fetch_object($result))
            {
                echo "<tr class='$shade'><td>&#10004; ";
                echo "<a href='user_profile_edit.php?userid={$user->userid}'>";
                echo "{$user->realname}";
                echo "</a>";
                echo " ({$user->username})</td><td>{$user->rolename}</td></tr>\n";
                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }
            echo "</table>";
        }
        else
        {
            echo "<p align='center'>{$strNone}</p>";
        }

        echo "<p align='center'><a href='edit_user_permissions.php'>{$strSetRolePermissions}</a></p>";

        echo "<h3>".sprintf($strUserPermissionXY, $permid, permission_name($permid))."</h3>";
        $sql = "SELECT up.userid AS userid, username, realname ";
        $sql .= "FROM `{$dbUserPermissions}` AS up, `{$dbUsers}` AS u ";
        $sql .= "WHERE up.userid = u.id ";
        $sql .= "AND permissionid = '$permid' AND granted = 'true' AND u.status > 0";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        if (mysql_num_rows($result) >= 1)
        {
            echo "<table align='center'>";
            echo "<tr><th>{$strUser}</th></tr>";
            $shade='shade1';
            while ($user = mysql_fetch_object($result))
            {
                echo "<tr class='$shade'><td>&#10004; <a href='{$_SERVER['PHP_SELF']}?action=edit&amp;userid={$user->userid}#perm{$perm}'>{$user->realname}</a> ({$user->username})</td></tr>\n";
                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }
            echo "</table>";
        } else echo "<p align='center'>{$strNone}</p>";
    }
    else
    {
        echo user_alert(sprintf($strFieldMustNotBeBlank, "'{$strPermission}'"), E_USER_ERROR);
    }
}
else
{
    echo user_alert("{$strNoChangesToMake}", E_USER_WARNING);
}
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>