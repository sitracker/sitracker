<?php
// feedback.inc.php - functions relating to feedback
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

/**
 * Identifies whether feedback should be send for this contract,
 * This checks against $CONFIG['no_feedback_contracts'] to see if the contract is set to receive no feedback
 * @param $contractid int The contract ID to check
 * @return bool TRUE if feedback should be sent, false otherwise
 * @author Paul Heaney
 */
function send_feedback($contractid)
{
    global $CONFIG;
    foreach ($CONFIG['no_feedback_contracts'] AS $contract)
    {
        if ($contract == $contractid)
        {
            return FALSE;
        }
    }

    return TRUE;
}

/**
 * Creates a blank feedback form response
 * @param $formid int The feedback form to use
 * @param $incidentid int The incident to generate the form for
 * @return int The form ID
 */
function create_incident_feedback($formid, $incidentid)
{
    global $dbFeedbackRespondents;
    $contactid = incident_contact($incidentid);
    $email = contact_email($contactid);

    $sql = "INSERT INTO `{$dbFeedbackRespondents}` (formid, contactid, email, incidentid) VALUES (";
    $sql .= "'".mysql_real_escape_string($formid)."', ";
    $sql .= "'".mysql_real_escape_string($contactid)."', ";
    $sql .= "'".mysql_real_escape_string($email)."', ";
    $sql .= "'".mysql_real_escape_string($incidentid)."') ";
    mysql_query($sql);
    if (mysql_error()) trigger_error ("MySQL Error: ".mysql_error(), E_USER_ERROR);
    $blankformid = mysql_insert_id();
    return $blankformid;
}


/**
 * Generates a feedback form hash
 * @author Kieran Hogg
 * @param $formid int ID of the form to use
 * @param $contactid int ID of the contact to send it to
 * @param $incidentid int ID of the incident the feedback is about
 * @return string the hash
 */
function feedback_hash($formid, $contactid, $incidentid)
{
    $hashtext = urlencode($formid)."&&".urlencode($contactid)."&&".urlencode($incidentid);
    $hashcode4 = str_rot13($hashtext);
    $hashcode3 = gzcompress($hashcode4);
    $hashcode2 = base64_encode($hashcode3);
    $hashcode1 = trim($hashcode2);
    $hashcode = urlencode($hashcode1);
    return $hashcode;
}

?>