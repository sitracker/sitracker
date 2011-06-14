<?php
// portal/close.inc.php - Request incident closure in the portal included by ../portal.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Kieran Hogg <kieran[at]sitracker.org>

require ('..'.DIRECTORY_SEPARATOR.'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

$accesslevel = 'any';

include (APPLICATION_LIBPATH . 'portalauth.inc.php');

// External vars
$id = intval($_REQUEST['id']);

// First check the portal user is allowed to access this incident
$sql = "SELECT contact FROM `{$dbIncidents}` WHERE id = $id LIMIT 1";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
list($incidentcontact) = mysql_fetch_row($result);
if ($incidentcontact == $_SESSION['contactid'])
{
    $id = clean_int($_REQUEST['id']);
    
    if (empty($_REQUEST['reason']))
    {
        include (APPLICATION_INCPATH . 'portalheader.inc.php');
        echo "<h2>".icon('close', 32, $strClosureRequestForIncident);
        echo " {$strClosureRequestForIncident} {$id}</h2>";
        echo "<div id='update' align='center'><form action='{$_SERVER[PHP_SELF]}?page=close&amp;id={$id}' method='post'>";
        echo "<p>{$strReason}:</p><textarea name='reason' cols='50' rows='10'></textarea><br />";
        echo "<p><input type='submit' value=\"{$strRequestClosure}\" /></p></form></div>";

        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
    else
    {
        $usersql = "SELECT forenames, surname FROM `{$dbContacts}` WHERE id={$_SESSION['contactid']}";
        $result = mysql_query($usersql);
        $user = mysql_fetch_object($result);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        // FIXME i18n ? In db ?
        $reason = "Incident closure requested via the portal by [b]{$user->forenames} {$user->surname}[/b]\n\n";
        $reason .= "<b>{$SYSLANG['strReason']}:</b> ".cleanvar($_REQUEST['reason']);
        $owner = incident_owner($id);
        $sql = "INSERT into `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp, customervisibility) ";
        $sql .= "VALUES({$id}, '0', 'customerclosurerequest',  '{$owner}', '1', '{$reason}',
        '{$now}', 'show')";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        //set incident back to active
        $sql = "UPDATE `{$dbIncidents}` SET status=".STATUS_ACTIVE.", lastupdated={$now} WHERE id={$id}";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        html_redirect("index.php");
    }
}
else
{
    include (APPLICATION_INCPATH . 'portalheader.inc.php');
    echo "<p class='warning'>$strNoPermission.</p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    exit;
}

?>