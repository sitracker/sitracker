<?php
// bulk_modify.php - Modify items in bulk - mainly incidents
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_INCIDENT_EDIT; // Edit Incidents
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');
$title = $strBulkModify;

$action = cleanvar($_REQUEST['action']);

switch ($action)
{
    case 'external_esc': //show external escalation modification page
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>".icon('edit', 32)." {$strBulkModify}: {$strExternalEngineersName}</h2>";
        $sql = "SELECT DISTINCT(externalemail), externalengineer ";
        $sql .= "FROM `{$dbIncidents}` WHERE closed = '0' AND externalemail!=''";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($result) >= 1)
        {
            echo "<form action='{$_SERVER['PHP_SELF']}?action=change_external_esc' method='post'>";

            echo "<p align='center'>{$strChangeExternalDetailsOnAllOpenForSelected}</p>";
            echo "<table class='vertical'>";
            echo "<tr><th>{$strExternalEmail} {$strToChangeBrackets}:</th>";
            echo "<td><select name='oldexternalemail'>";
            while ($obj = mysql_fetch_object($result))
            {
                echo "<option value=\"{$obj->externalengineer},{$obj->externalemail}\">";
                echo "{$obj->externalengineer} - {$obj->externalemail}</option>\n";
            }
            echo "</select></td></tr>";
            echo "<tr><th>{$strExternalEngineersName}:</th>";
            echo "<td><input maxlength='80' name='externalengineer' size='30' type='text' value='' />";
            echo "</td></tr>";
            echo "<tr><th>{$strExternalEmail}:</th>";
            echo "<td><input maxlength='255' name='externalemail' size='30' type='text' value='' />";
            echo "</td></tr>";
            echo "</table>";
            echo "<p class='formbuttons'>";
            echo "<input name='reset' type='reset' value='{$strReset}' /> ";
            echo "<input name='submit' type='submit' value='{$strSave}' />";
            echo "</p></form>";
        }
        else
        {
            echo "<p align='center'>{$strCurrentlyNoOpenEscalatedIncidentsToModify}</p>";
        }
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        break;
    case 'change_external_esc': //omdify the extenal escalation info
        /*
        External Engineer:  -&gt; <b>Foo</b>
        External email:  -&gt; <b>foo@pheaney.co.uk</b>
        <hr>
    	*/
        list($old_external_engineer, $old_email_address) = split(',',  cleanvar($_REQUEST['oldexternalemail']));
        //$old_email_address = cleanvar($_REQUEST['oldexternalemail']);
        $new_external_email = cleanvar($_REQUEST['externalemail']);
        $new_extenal_engineer = cleanvar($_REQUEST['externalengineer']);

        //list incidents with this old email address so we can update them

        $sql = "SELECT id FROM `{$dbIncidents}` WHERE closed = '0' AND externalemail = '{$old_email_address}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

        while ($row = mysql_fetch_object($result))
        {
            $bodytext = "{$strExternalEngineer}: ".$old_external_engineer." -&gt; [b]". $new_extenal_engineer."[/b]\n";
            $bodytext .= "{$strExternalEmail}: ".$old_email_address." -&gt; [b]".$new_external_email."[/b]\n<hr>";
            $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, bodytext, timestamp, currentowner, currentstatus) ";
            $sql .= "VALUES ('{$row->id}', '{$sit[2]}', 'editing', '{$bodytext}', '".time()."', {$row->owner}, {$row->status})";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        }

        $sql = "UPDATE `{$dbIncidents}` SET externalengineer = '{$new_extenal_engineer}', externalemail = '{$new_external_email}' ";
        $sql .= " WHERE externalemail = '{$old_email_address}' AND closed = '0'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
        html_redirect("main.php");
        break;
    default:
        html_redirect("main.php", FALSE, $strNoActionSpecified);
        break;
}

?>