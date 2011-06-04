<?php
// feedback.php - Displays a listing of all feedback forms awaiting
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Carsten Jensen <carsten[at]sitracker.org>

require ('..'.DIRECTORY_SEPARATOR.'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
$accesslevel = 'any';
require (APPLICATION_LIBPATH . 'portalauth.inc.php');

if (($CONFIG['portal_feedback_enabled'] == FALSE) OR ($CONFIG['feedback_enabled'] == FALSE AND $CONFIG['portal_feedback_enabled'] == TRUE))
{
    header("Location: index.php");
}

$sql = "SELECT formid, incidentid FROM `{$dbFeedbackRespondents}` ";
$sql .= "WHERE contactid = '{$_SESSION['contactid']}' ";
$sql .= "AND completed = 'no'";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
if (mysql_num_rows($result) < 1)
{
    $html = $strNoFeedbackFormsAvailable;
}
else
{
    $html = "<table align='center'>";
    while ($row = mysql_fetch_object($result))
    {
        $hashcode = feedback_hash($row->formid, $_SESSION['contactid'], $row->incidentid);
        $html .= "<tr><td><a target='_blank' href='" . application_url() . "feedback.php?ax={$hashcode}'>{$strFeedbackFormForIncidentX} : {$row->incidentid}</a></td></tr><br />";
    }
    $html .= "</table>";
}



include (APPLICATION_INCPATH . 'portalheader.inc.php');

echo "<h2>".icon('reports', 32)." {$strFeedbackForms}</h2>";
echo $html;

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>