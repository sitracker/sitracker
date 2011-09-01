<?php
// escalation_path_edit - Ability to edit escalation path
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paulheaney[at]users.sourceforge.net>

//// This Page Is Valid XHTML 1.0 Transitional!  (7 Oct 2006)

require ('core.php');
$permission = PERM_ESCALATION_MANAGE; // Manage escalation paths
require (APPLICATION_LIBPATH.'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$mode = clean_fixed_list($_REQUEST['mode'], array('','edit'));

if (empty($mode))
{
    $title = $strEditEscalationPath;
    //show page
    $id = clean_int($_REQUEST['id']);
    $sql = "SELECT * FROM `{$dbEscalationPaths}` WHERE id = {$id}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo show_form_errors('edit_escalation_path');
    clear_form_errors('edit_escalation_path');
    echo "<h2>{$title}</h2>";
    plugin_do('escalation_path_edit');

    if (mysql_num_rows($result) > 0)
    {
        while ($details = mysql_fetch_object($result))
        {
            echo "<form action='".$_SERVER['PHP_SELF']."' method='post' onsubmit=\"return confirm_action('{$strAreYouSureMakeTheseChanges}')\">";
            echo "<table class='vertical'>";
            echo "<tr><th>{$strName}:</th><td><input name='name' value='{$details->name}' class='required' /> ";
            echo "<span class='required'>{$strRequired}</span></td></tr>";
            echo "<tr><th>{$strTrackURL}:</th><td><input name='trackurl' value='{$details->track_url}' />";
            echo "<br />{$strNoteInsertExternalID}</td></tr>";
            echo "<tr><th>{$strHomeURL}:</th><td><input name='homeurl' value='{$details->home_url}' /></td></tr>";
            echo "<tr><th>{$strTitle}:</th><td><input name='title' value='{$details->url_title}' /></td></tr>";
            echo "<tr><th>{$strEmailDomain}:</th><td><input name='emaildomain' value='{$details->email_domain}' /></td></tr>";
            plugin_do('escalation_path_edit_form');
            echo "</table>";
            echo "<input type='hidden' value='{$id}' name='id' />";
            echo "<input type='hidden' value='edit' name='mode' />";
            echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' />  ";
            echo "<input type='submit' name='submit' value=\"{$strSave}\" /></p>";
            echo "<p class='return'><a href=\"escalation_paths.php\">{$strReturnWithoutSaving}</a></p>";
            echo "</form>";
        }
    }
    else 
    {
        echo user_alert($strNoRecords, E_USER_WARNING);
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    //make changes
    $id = clean_int($_REQUEST['id']);
    $name = clean_dbstring($_REQUEST['name']);
    $trackurl = clean_dbstring($_REQUEST['trackurl']);
    $homeurl = clean_dbstring($_REQUEST['homeurl']);
    $title = clean_dbstring($_REQUEST['title']);
    $emaildomain = clean_dbstring($_REQUEST['emaildomain']);

    $errors = 0;
    if (empty($name))
    {
        $errors++;
        $_SESSION['formerrors']['edit_escalation_path']['name'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strName}'"), E_USER_ERROR);
    }
    plugin_do('escalation_path_edit_submitted');

    if ($errors == 0)
    {
        $sql = "UPDATE `{$dbEscalationPaths}` SET name = '{$name}', track_url = '{$trackurl}', ";
        $sql .= " home_url = '{$homeurl}', url_title = '{$title}', email_domain = '{$emaildomain}' ";
        $sql .= " WHERE id = '{$id}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

        if (!$result)
        {
            html_redirect("escalation_paths.php", FALSE, $strEditEscalationPathFailed);
        }
        else
        {
            plugin_do('escalation_path_edit_saved');
            html_redirect("escalation_paths.php");
        }
    }
    else
    {
        html_redirect("escalation_path_edit.php?id={$id}", FALSE);
    }
}

?>