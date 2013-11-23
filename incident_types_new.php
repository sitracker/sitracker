<?php
// incident_types_new.php - Incident Types  New
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

$title = $strNewIncidentType;

$action = cleanvar($_REQUEST['action']);

if (empty($action))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo show_form_errors('new_incident_type');
    clear_form_errors('new_incident_type');

    echo "<h2>".icon('new', 32)." {$title}</h2>";

    echo "<form action='{$_SERVER['PHP_SELF']}?action=new' method='post'>";
    echo "<table align='center'>";
    echo "<tr><th>{$strName}</th><td><input name='name' id='name' /></td></tr>";
    echo "<tr><th>{$strPrefix}</th><td><input name='prefix' id='prefix' /></td></tr>";
    echo "</table>";
    echo "<p class='formbuttons'>";
    echo "<input type='submit' value='{$strNew}' />";
    echo "</p>";
    echo "</form>";

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
        $_SESSION['formerrors']['new_incident_type']['name'] = sprintf($strFieldMustNotBeBlank, $strName);
    }

    if (preg_match('/\d/', $prefix))
    {
        $errors++;
        $_SESSION['formerrors']['edit_incident_edit']['name'] = sprintf($strFieldMustNotConatainNumbers, $strName);
    }

    if ($errors == 0)
    {
        $sql = "INSERT INTO `{$dbIncidentTypes}` (name, type, prefix) VALUES ('{$name}', 'user', '{$prefix}')";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (!$result) echo "<p class='error'>".sprintf($strNewXfailed, $strIncidentType)."\n";
        else
        {
            $id = mysql_insert_id();
            journal(CFG_LOGGING_NORMAL, 'Incident Type Added', "Incident Type{$id} was added", CFG_JOURNAL_ADMIN, $id);

            html_redirect("incident_types.php");
        }
    }
    else
    {
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}