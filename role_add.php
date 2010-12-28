<?php
// role_add.php - Page to add role to SiT!
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
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

$submit = cleanvar($_REQUEST['submit']);

if (empty($submit))
{
    $title = $strAddRole;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo show_form_errors('role_add');
    clear_form_errors('role_add');

    echo "<h2>{$strAddRole}</h2>";
    echo "<form method='post' action='{$_SERVER['PHP_SELF']}'>";
    echo "<table class='vertical'>";
    echo "<tr><th>{$strName}</th>";
    echo "<td><input class='required' size='30' name='rolename' /><span class='required'>{$strRequired}</span></td></tr>";
    echo "<tr><th>{$strDescription}</th><td><textarea name='description' id='description' rows='5' cols='30'>{$_SESSION['formdata']['role_add']['description']}</textarea></td></tr>";
    echo "<tr><th>{$strCopyFrom}</th><td>";
    if ($_SESSION['formdata']['role_add']['roleid'] != '')
    {
        echo role_drop_down('copyfrom', $_SESSION['formdata']['role_add']['roleid']);
    }
    else
    {
        echo role_drop_down('copyfrom', 0);
    }
    echo "</td></tr>";

    echo "</table>";
    echo "<p><input name='submit' type='submit' value='{$strAddRole}' /></p>";
    echo "</form>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    clear_form_data('role_add');
}
else
{
    $rolename = clean_dbstring($_REQUEST['rolename']);
    $description = clean_dbstring($_REQUEST['description']);
    $copyfrom = clean_dbstring($_REQUEST['copyfrom']);

    $_SESSION['formdata']['role_add'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);

    if (empty($rolename))
    {
        $errors++;
        $_SESSION['formerrors']['role_add']['rolename'] = sprintf($strFieldMustNotBeBlank, $strName);
    }

    $sql = "SELECT * FROM `{$dbRoles}` WHERE rolename = '{$rolename}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    if (mysql_num_rows($result) > 0)
    {
        $errors++;
        $_SESSION['formerrors']['role_add']['duplicaterole']= "{$strADuplicateAlreadyExists}</p>\n";
    }

    if ($errors == 0)
    {
        $sql = "INSERT INTO `{$dbRoles}` (rolename, description) VALUES ('{$rolename}', '{$description}')";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        $roleid = mysql_insert_id();

        if ($roleid != 0)
        {
            clear_form_data('role_add');
            clear_form_errors('role_add');

            if (!empty($copyfrom))
            {
                $sql = "INSERT INTO `{$dbRolePermissions}` (roleid, permissionid, granted)  ";
                $sql .= "SELECT '{$roleid}', permissionid, granted FROM `{$dbRolePermissions}` WHERE roleid = {$copyfrom}";
                $result = mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

                // Note we dont check for affected rows as you could be copying from a permissionless role
                html_redirect('edit_user_permissions.php', TRUE);
            }
            else
            {
                html_redirect('edit_user_permissions.php', TRUE);
            }
        }
        else
        {
            html_redirect($_SERVER['PHP_SELF'], FALSE);
        }
    }
    else
    {
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}

?>