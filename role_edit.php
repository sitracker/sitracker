<?php
// role_new.php - Page to add role to SiT!
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul@sitracker.org>

$permission = PERM_USER_PERMISSIONS_EDIT; // Edit User Permissions

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$roleid = clean_int($_REQUEST['roleid']);

$submit = cleanvar($_REQUEST['submit']);

if (empty($submit))
{
    $title = $strEditRole;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo show_form_errors('role_edit');
    clear_form_errors('role_edit');

    $sql = "SELECT * FROM `{$dbRoles}` WHERE id = {$roleid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    echo "<h2>{$strEditRole}</h2>";

    if (mysql_num_rows($result) > 0)
    {
        $obj = mysql_fetch_object($result);
        echo "<form action'{$_SERVER['PHP_SELF']}' name='role_edit' method='post' >";
        echo "<table class='vertical maintable'>";
        echo "<tr><th>{$strRole}</th><td>{$obj->id}</td></tr>";
        echo "<tr><th>{$strName}</th><td><input type='text' name='rolename' id='rolename' value='{$obj->rolename}' class='required' />";
        echo " <span class='required'>{$strRequired}</span></td></tr>";
        echo "<tr><th>{$strDescription}</th><td><textarea name='description' id='description' rows='5' cols='30'>{$obj->description}</textarea></td></tr>";
        echo "</table>";
        echo "<input type='hidden' name='roleid' id='roleid' value='{$roleid}' />";
        echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
        echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
        echo "</form>";
        echo "<p class='return'><a href='role.php?roleid={$roleid}'>{$strReturnWithoutSaving}</a></p>";
    }
    else
    {
        echo user_alert($strNoRecords, E_USER_NOTICE);
    }

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    $rolename = clean_dbstring($_REQUEST['rolename']);
    $description = clean_dbstring($_REQUEST['description']);
    $copyfrom = clean_dbstring($_REQUEST['copyfrom']);

    $_SESSION['formdata']['role_edit'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);

    if (empty($rolename))
    {
        $errors++;
        $_SESSION['formerrors']['role_edit']['rolename']= sprintf($strFieldMustNotBeBlank, $strName);
    }

    $sql = "SELECT * FROM `{$dbRoles}` WHERE rolename = '{$rolename}' AND id != {$roleid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    if (mysql_num_rows($result) > 0)
    {
        $errors++;
        $_SESSION['formerrors']['role_edit']['duplicaterole']= "{$strADuplicateAlreadyExists}</p>\n";
    }

    if ($errors == 0)
    {
        clear_form_data('role_new');
        clear_form_errors('role_new');

        $sql = "UPDATE `{$dbRoles}` SET rolename = '{$rolename}', description = '{$description}' WHERE id = {$roleid}";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        if (mysql_affected_rows() > 0) html_redirect("role.php?roleid={$roleid}", TRUE);
        else html_redirect($_SESSION['PHP_SELF'], FALSE);
    }
    else
    {
        html_redirect($_SESSION['PHP_SELF'], FALSE);
    }
}

?>