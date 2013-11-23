<?php
// incident_types_edit.php - Incident Types Edit
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2013 The Support Incident Tracker Project
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_PRODUCT_ADD; // Add Products and Software
require (APPLICATION_LIBPATH.'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$title = $strEditIncidentType;

$action = cleanvar($_REQUEST['action']);
$id = clean_int($_REQUEST['id']);

if (empty($action))
{

    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo show_form_errors('edit_incident_edit');
    clear_form_errors('edit_incident_edit');

    echo "<h2>".icon('edit', 32)." {$title}</h2>";

    $sql = "SELECT name, prefix FROM `{$dbIncidentTypes}` WHERE id = {$id} AND type = 'user'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        list($name, $prefix) = mysql_fetch_row($result);
        echo "<form action='{$_SERVER['PHP_SELF']}?action=edit&amp;id={$id}' method='post'>";
        echo "<table align='center'>";
        echo "<tr><th>{$strName}</th><td><input name='name' id='name' value='{$name}' /></td></tr>";
        echo "<tr><th>{$strPrefix}</th><td><input name='prefix' id='prefix' value='{$prefix}' /></td></tr>";
        echo "</table>";
        echo "<p class='formbuttons'>";
        echo "<input type='submit' value='{$strEdit}' />";
        echo "</p>";
        echo "</form>";
    }
    else
    {
        echo user_alert($strNoRecords, E_USER_WARNING);
    }

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

}
else
{
    $name = clean_dbstring($_REQUEST['name']);
    $prefix = clean_dbstring($_REQUEST['prefix']);

    $errors = 0;

    if ($name == '')
    {
        $errors++;
        $_SESSION['formerrors']['edit_incident_edit']['name'] = sprintf($strFieldMustNotBeBlank, $strName);
    }

    if (preg_match('/\d/', $prefix))
    {
        $errors++;
        $_SESSION['formerrors']['edit_incident_edit']['name'] = sprintf($strFieldMustNotConatainNumbers, $strName);
    }

    if ($errors == 0)
    {
        $sql = "UPDATE `{$dbIncidentTypes}` SET name = '{$name}', prefix = '{$prefix}' WHERE id = {$id}";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (!$result) echo "<p class='error'>".sprintf($strEditXfailed, $strIncidentType)."\n";
        else
        {
            html_redirect("incident_types.php");
        }
    }
    else
    {
        html_redirect("{$_SERVER['PHP_SELF']}?id={$id}", FALSE);
    }
}