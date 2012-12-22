<?php
// incident_reopen.php - Form for re-opening a closed incident
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

require ('core.php');
$permission = PERM_INCIDENT_REOPEN; // Reopen Incidents
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$submit = cleanvar($_REQUEST['submit']);
$id = clean_int($_REQUEST['id']);
$newstatus = clean_int($_REQUEST['newstatus']);
$bodytext = cleanvar($_REQUEST['bodytext']);
$updateid = clean_int($_REQUEST['updateid']);

if (!empty($updateid))
{
    $returnurl = 'holding_queue.php';
}
else
{
    $returnurl = "incident_details.php?id={$id}";
}
$sql = "SELECT * FROM `{$dbIncidents}` WHERE id = '{$id}' LIMIT 1";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
if (mysql_num_rows($result) > 0)
{
    $incident = mysql_fetch_object($result);
}

// Find out whether the service level in use allows reopening
$slsql = "SELECT allow_reopen FROM `{$dbServiceLevels}` ";
$slsql .= "WHERE tag = '{$incident->servicelevel}' ";
$slsql .= "AND priority = '{$incident->priority}' LIMIT 1";
$slresult = mysql_query($slsql);

if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
if (mysql_num_rows($slresult) > 0)
{
    $allow_reopen_obj = mysql_fetch_object($slresult);
}
$allow_reopen = $allow_reopen_obj->allow_reopen;

if ($allow_reopen == 'yes')
{
    if (empty($submit))
    {
        // No submit detected show update form
        $incident_title = incident_title($id);
        $title = "{$strReopen}: ".$id . " - " . $incident_title;
        include (APPLICATION_INCPATH . 'incident_html_top.inc.php');

        echo "<h2>{$strReopenIncident}</h2>";
        if (!empty($updateid))
        {
            $action = "&updateid={$updateid}";
        }
        echo "<form action='{$_SERVER['PHP_SELF']}?id={$id}{$action}' method='post'>";
        if (empty($updateid))
        {
          echo "<table class='vertical'>";
          echo "<tr><th>{$strUpdate}</th><td><textarea name='bodytext' rows='20' ";
          echo "cols='60'></textarea></td></tr>";
          echo "<tr><th>{$strStatus}</th><td>".incidentstatus_drop_down("newstatus", 1);
          echo "</td></tr>\n";
          echo "</table>";
        }
        else
        {
            echo "<p align='center'>{$strReopenIncidentAndAddUpdate}</p>";
        }
        echo "<p><input name='submit' type='submit' value='{$strReopen}' /></p>";
        echo "</form>";
        include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
    }
    else
    {
        $reopen = reopen_incident($id);

        $move = true; // Default so we dont get an error if there is no updateid on a reopen (as is the case when reopening from incident_details)

        if (!empty($updateid))
        {
            $move = move_update_to_incident($updateid, $id) AND delete_holding_queue_update($updateid);
        }

        if (!($result AND $move))
        {
            include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
            echo "<p class='error'>{$strUpdateIncidentFailed}</p>\n";
            include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
        }
        else
        {
            html_redirect($returnurl);
        }
    }
}
else
{
    html_redirect($returnurl, FALSE, $strServiceLevelPreventsReopen);
}

?>