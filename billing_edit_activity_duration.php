<?php
// edit_activity_duration.php - Edit the duration of an activity
// Page to adjust the duration of a timed activity
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// TODO should this update the tasks table?
// Author:  Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission =  PERM_BILLING_DURATION_EDIT;
require_once (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require_once (APPLICATION_LIBPATH . 'auth.inc.php');

$mode = cleanvar($_REQUEST['mode']);
$updateid = clean_int($_REQUEST['updateid']);
$incidentid = clean_int($_REQUEST['incidentid']);
$id = $incidentid; // So he header works
$title = $strAdjustActivityDuration;

switch ($mode)
{
    case 'edit':
        $sql = "SELECT bodytext, duration FROM `{$dbUpdates}` WHERE id = {$updateid} AND duration IS NOT NULL AND duration != 0";

        $oldduration = clean_int($_REQUEST['oldduration']);
        $reason = cleanvar($_REQUEST['reason']);
        $newduration = clean_int($_REQUEST['newduration']); // In minutes

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

        if (mysql_num_rows($result) == 1)
        {
            $obj = mysql_fetch_object($result);
            if ($obj->duration == $oldduration)
            {
            	// Double check the oldduration thats been passed is whats in the DB
                $text = "{$obj->bodytext}\n\n[b]Duration adjusted[/b] by ".user_realname($sit[2]) . 
                        " on ".ldate($CONFIG['dateformat_datetime'], $now)." from ".ceil($obj->duration) . 
                        "minutes to {$newduration}minutes, reason given:\n---\n{$reason}\n---"; // FIXME should this be i18n? If so which language
                // $newduration *= 60;
                $usql = "UPDATE `{$dbUpdates}` SET bodytext = '".mysql_real_escape_string($text)."', duration = '{$newduration}' WHERE id = '{$updateid}'";
                mysql_query($usql);
                if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

                // Some error checking
                if (mysql_affected_rows() < 1)
                {
                    html_redirect("{$CONFIG['application_webpath']}incident_details.php?id={$incidentid}", FALSE, $strFailed);
                }
                else
                {
                    html_redirect("{$CONFIG['application_webpath']}incident_details.php?id={$incidentid}", TRUE, $strDurationUpdated);
                }
            }
            else
            {
                // The value we've been passed isn't whats in the DB
                html_redirect("{$CONFIG['application_webpath']}incident_details.php?id={$incidentid}", FALSE, $strDurationMismatch);
            }
        }
        else
        {
            // No matching incident found (updateID and a duration with a value)
            html_redirect("{$CONFIG['application_webpath']}incident_details.php?id={$incidentid}", FALSE, $strNoDurationOnActivity);
        }

        break;
    case 'showform':
    default:
        $sql = "SELECT duration FROM `{$dbUpdates}` WHERE id = {$updateid} AND duration IS NOT NULL AND duration != 0";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

        if (mysql_num_rows($result) == 1)
        {
            include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
            $obj = mysql_fetch_object($result);

            echo "<h2>{$strAdjustActivityDuration}</h2>";

            echo "<form name='editduration' action='{$_SERVER['PHP_SELF']}?mode=edit' method='post' onsubmit='return confirm_submit(\"{$strAreYouSureMakeTheseChanges}\");'>";
            echo "<table class='maintable vertical'>";

            echo "<tr><th>{$strDuration}</th><td>".sprintf($strXMinutes, ceil($obj->duration))."</d></tr>";
            echo "<tr><th>{$strNewDuration}</th><td><input type='text' size='10' name='newduration' id='newduration' />{$strMinutes}</d></tr>";
            echo "<tr><th>{$strReason}</th><td><textarea rows='3' cols='6' name='reason' id='reason' ></textarea></td></tr>";

            echo "</table>";
            echo "<p align='center'><input type='submit' name='editduration' value='{$strEdit}' /></p>";

            echo "<input type='hidden' name='oldduration' id='oldduration' value='{$obj->duration}' />";
            echo "<input type='hidden' name='updateid' id='updateid' value='{$updateid}' />";
            echo "<input type='hidden' name='incidentid' id='incidentid' value='{$incidentid}' />";
            echo "</form>";

            include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
        }
        else
        {
            html_redirect("{$CONFIG['application_webpath']}incident_details.php?id={$incidentid}", FALSE, $strNoDurationOnActivity);
        }
}

?>