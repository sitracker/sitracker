<?php
// incident.inc.php - functions relating to incidents
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

require_once (APPLICATION_LIBPATH . 'base.inc.php');
require_once (APPLICATION_LIBPATH . 'contract.inc.php');


/**
 * Gets incident details
 *
 * This function emulates a SQL query to the incident table while abstracting
 * SQL details
 * @param int $incident ID of the incident
 * @return object an object containing all parameters contained in the table
 * @author Kieran Hogg
 */
function incident($incident)
{
    global $dbIncidents;

    $incident = intval($incident);
    $sql = "SELECT * FROM `{$dbIncidents}` WHERE id = '$incident'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    $row = mysql_fetch_object($result);
    return $row;
}

/**
 * Creates a new incident
 * @param string $title The title of the incident
 * @param int $contact The ID of the incident contact
 * @param int $servicelevel The ID of the servicelevel to log the incident under
 * @param int $contract The ID of the contract to log the incident under
 * @param int $product The ID of the product the incident refers to
 * @param int $skill The ID of the skill the incident refers to
 * @param int $priority (Optional) Priority of the incident (Default: 1 = Low)
 * @param int $owner (Optional) Owner of the incident (Default: 0 = SiT)
 * @param int $status (Optional) Incident status (Default: 1 = Active)
 * @param string $productversion (Optional) Product version field
 * @param string $productservicepacks (Optional) Product service packs field
 * @param int $opened (Optional) Timestamp when incident was opened (Default: now)
 * @param int $lastupdated (Optional) Timestamp when incident was updated (Default: now)
 * @return int|bool Returns FALSE on failure, an incident ID on success
 * @author Kieran Hogg
 */
function create_incident($title, $contact, $servicelevel, $contract, $product,
                         $software, $priority = 1, $owner = 0, $status = 1,
                         $productversion = '', $productservicepacks = '',
                         $opened = '', $lastupdated = '')
{
    global $now, $dbIncidents;

    if (empty($opened))
    {
        $opened = $now;
    }

    if (empty($lastupdated))
    {
        $lastupdated = $now;
    }

    $sql  = "INSERT INTO `{$dbIncidents}` (title, owner, contact, priority, ";
    $sql .= "servicelevel, status, maintenanceid, product, softwareid, ";
    $sql .= "productversion, productservicepacks, opened, lastupdated) ";
    $sql .= "VALUES ('{$title}', '{$owner}', '{$contact}', '{$priority}', ";
    $sql .= "'{$servicelevel}', '{$status}', '{$contract}', ";
    $sql .= "'{$product}', '{$software}', '{$productversion}', ";
    $sql .= "'{$productservicepacks}', '{$opened}', '{$lastupdated}')";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        return FALSE;
    }
    else
    {
        $incident = mysql_insert_id();
        increment_incidents_used($contract);
        return $incident;
    }
}


/**
 * Creates an incident based on an 'tempincoming' table entry
 * @author Kieran Hogg
 * @param int $incomingid the ID of the tempincoming entry
 * @return int|bool returns either the ID of the contract or FALSE if none
 */
function create_incident_from_incoming($incomingid)
{
    global $dbTempIncoming, $dbMaintenance, $dbServiceLevels,
        $dbSoftwareProducts, $CONFIG;
    $rtn = TRUE;

    $incomingid = intval($incomingid);
    $sql = "SELECT * FROM `{$dbTempIncoming}` ";
    $sql .= "WHERE id = '{$incomingid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

    $row = mysql_fetch_object($result);
    $contact = $row->contactid;
    $contract = guess_contract_id($contact);
    if (!$contract)
    {
        // we have no contract to log against, update stays in incoming
        return TRUE;
    }
    $subject = $row->subject;
    $update = $row->updateid;

    $sql = "SELECT servicelevelid, tag, product, softwareid ";
    $sql .= "FROM `{$dbMaintenance}` AS m, `{$dbServiceLevels}` AS s, ";
    $sql .= "`{$dbSoftwareProducts}` AS sp ";
    $sql .= "WHERE m.id = '{$contract}' ";
    $sql .= "AND m.servicelevelid = s.id ";
    $sql .= "AND m.product = sp.productid LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_ERROR);
        $rtn = FALSE;
    }

    $row = mysql_fetch_object($result);
    $sla = $row->tag;
    $product = $row->product;
    $software = $row->softwareid;
    $incident = create_incident($subject, $contact, $row->tag, $contract,
                                $product, $software);

    if (!move_update_to_incident($update, $incident))
    {
        $rtn = FALSE;
    }

    if ($CONFIG['auto_assign_incidents'])
    {
        $user = suggest_reassign_userid($incident);
        if (!reassign_incident($incident, $user))
        {
            $rtn = FALSE;
        }
    }

    return $rtn;
}


/**
 * Move an update to an incident
 * @author Kieran Hogg
 * @param int $update the ID of the update
 * @param int $incident the ID of the incident
 * @return bool returns TRUE on success, FALSE on failure
 */
function move_update_to_incident($update, $incident)
{
    global $dbUpdates;
    $update = intval($update);
    $incident = intval($incident);

    $sql = "UPDATE `{$dbUpdates}` SET incidentid = '{$incident}' ";
    $sql .= "WHERE id = '{$update}'";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_ERROR);
        return FALSE;
    }
    else
    {
        return TRUE;
    }
}


/**
 * Gets update details
 *
 * This function emulates a SQL query to the update table while abstracting
 * SQL details
 * @param int $update ID of the update
 * @return object an object containing all parameters contained in the table
 * @author Kieran Hogg
 */
function update($update)
{
    global $dbUpdates;

    $update = intval($update);
    $sql = "SELECT * FROM `{$dbUpdates}` WHERE id = '{$update}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    $row = mysql_fetch_object($result);
    return $row;
}


/**
    * Suggest the userid of a suitable person to handle the given incident
    * @author Ivan Lucas
    * @param int $incidentid. An incident ID to suggest a new owner for
    * @param int $exceptuserid. This user ID will not be suggested (e.g. the existing owner)
    * @returns A user ID of the suggested new owner
    * @retval bool FALSE failure.
    * @retval int The user ID of the suggested new owner
    * @note Users are chosen randomly in a weighted lottery depending on their
    * avilability and queue status
*/
function suggest_reassign_userid($incidentid, $exceptuserid = 0)
{
    global $now, $dbUsers, $dbIncidents, $dbUserSoftware, $startofsession;
    $sql = "SELECT product, softwareid, priority, contact, owner FROM `{$dbIncidents}` WHERE id={$incidentid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (!$result)
    {
        $userid = FALSE;
    }
    else
    {
        $incident = mysql_fetch_object($result);
        // If this is a critical incident the user we're assigning to must be online
        if ($incident->priority >= 4) $req_online = TRUE;
        else $req_online = FALSE;

        // Find the users with this skill (or all users)
        if (!empty($incident->softwareid))
        {
            $sql = "SELECT us.userid, u.status, u.lastseen FROM `{$dbUserSoftware}` AS us, `{$dbUsers}` AS u ";
            $sql .= "WHERE u.id = us.userid AND u.status > 0 AND u.accepting='Yes' ";
            if ($exceptuserid > 0) $sql .= "AND u.id != '$exceptuserid' ";
            $sql .= "AND softwareid = {$incident->softwareid}";
        }
        else
        {
            $sql = "SELECT id AS userid, status, lastseen FROM `{$dbUsers}` AS u WHERE status > 0 AND u.accepting='Yes' ";
            if ($exceptuserid > 0) $sql .= "AND id != '$exceptuserid' ";
        }
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

        // Fallback to all users if we have no results from above
        if (mysql_num_rows($result) < 1)
        {
            $sql = "SELECT id AS userid, status, lastseen FROM `{$dbUsers}` AS u WHERE status > 0 AND u.accepting='Yes' ";
            if ($exceptuserid > 0) $sql .= "AND id != '$exceptuserid' ";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        }

        while ($user = mysql_fetch_object($result))
        {
            // Get a ticket for being skilled
            // Or in the case we don't know the skill, just get a ticket for accepting
            $ticket[] = $user->userid;

            // Get a ticket for being seen within the current session time
            if (mysql2date($user->lastseen) > $startofsession) $ticket[] = $user->userid;

            // Get two tickets for being marked in-office or working at home
            if ($user->status == 1 OR $user->status == 6)
            {
                $ticket[] = $user->userid;
                $ticket[] = $user->userid;
            }

            // Get one ticket for being marked at lunch or in meeting
            // BUT ONLY if the incident isn't critical
            if ($incident->priority < 4 AND ($user->status == 3 OR $user->status == 4))
            {
                $ticket[] = $user->userid;
            }

            // Have a look at the users incident queue (owned)
            $qsql = "SELECT id, priority, lastupdated, status, softwareid FROM `{$dbIncidents}` WHERE owner={$user->userid}";
            $qresult = mysql_query($qsql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            $queue_size = mysql_num_rows($qresult);
            if ($queue_size > 0)
            {
                $queued_critical = 0;
                $queued_high = 0;
                $queue_lastupdated = 0;
                $queue_samecontact = FALSE;
                while ($queue = mysql_fetch_object($qresult))
                {
                    if ($queue->priority == 3) $queued_high++;
                    if ($queue->priority >= 4) $queued_critical++;
                    if ($queue->lastupdated > $queue_lastupdated) $queue_lastupdated = $queue->lastupdated;
                    if ($queue->contact == $incident->contact) $queue_samecontact = TRUE;
                }
                // Get one ticket for your queue being updated in the past 4 hours
                if ($queue_lastupdated > ($now - 14400)) $user->userid;

                // Get two tickets for dealing with the same contact in your queue
                if ($queue_samecontact == TRUE)
                {
                    $ticket[] = $user->userid;
                    $ticket[] = $user->userid;
                }

                // Get one ticket for having five or less incidents
                if ($queue_size <=5) $ticket[] = $user->userid;

                // Get up to three tickets, one less ticket for each critical incident in queue
                for ($c = 1; $c < (3 - $queued_critical); $c++)
                {
                    $ticket[] = $user->userid;
                }

                // Get up to three tickets, one less ticket for each high priority incident in queue
                for ($c = 1; $c < (3 - $queued_high); $c++)
                {
                    $ticket[] = $user->userid;
                }
            }
            else
            {
                // Get one ticket for having an empty queue
                $ticket[] = $user->userid;
            }
        }

        // Do the lottery - "Release the balls"
        $numtickets = count($ticket)-1;
        $rand = mt_rand(0, $numtickets);
        $userid = $ticket[$rand];
    }
    if (empty($userid)) $userid = FALSE;
    return $userid;
}


/**
 * Reassigns an incident
 * @param int $incident incident ID to reassign
 * @param int $user user to reassign the incident to
 * @param string $type 'full' to do a full reassign, 'temp' for a temp
 * @return bool TRUE on success, FALSE on failure
 * @author Kieran Hogg
 */
function reassign_incident($incident, $user, $tuser = '', $nextaction = '', $type = 'full')
{
    global $dbIncidents, $dbUpdates, $now, $sit;
    $rtn = TRUE;

    if ($nextaction != '') {
        $incident->nextaction = $nextaction;
    }

    if ($type == 'temp')
    {
        $sql = "UPDATE `{$dbIncidents} SET towner = '{$tuser}'";
    }
    else
    {
        $sql = "UPDATE `{$dbIncidents}` SET owner = '{$user}'";
    }
    $sql .= "WHERE id = '{$incident}'";

    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_WARNING);
        $rtn = FALSE;
    }

    $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, nextaction) ";
    $sql .= "VALUES ('{$incidentid}', '{$sit[2]}', 'reassigning', '{$now}', '{$user}', '1', '{$incident->nextaction}')";
    $result = mysql_query($sql);
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_WARNING);
        $rtn = FALSE;
    }

    return $rtn;
}


/**
 * Reopens an incident
 * @param int $incident incident ID to reopen
 * @param int $newstatus (optional) status to set the incident to, defaults to active
 * @param string $message (optional) message to insert when reopening
 * @return bool TRUE on success, FALSE on failure$dbIncidents
 * @author Kieran Hogg
 */
function reopen_incident($incident, $newstatus = STATUS_ACTIVE, $message = '')
{
    global $dbIncidents, $dbUpdates, $now, $sit, $bodytext;
    $rtn = TRUE;

    $time = time();
    $sql = "UPDATE `{$dbIncidents}` SET status='{$newstatus}', ";
    $sql .= "lastupdated='{$time}', closed='0' WHERE id='{$incident}' LIMIT 1";
    mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

    $owner = incident_owner($incident);
    // add update
    $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, ";
    $sql .= "bodytext, timestamp, currentowner, currentstatus) ";
    $sql .= "VALUES ({$incident}, '{$sit[2]}', 'reopening', '{$bodytext}', '{$time}', ";
    $sql .= "'{$owner}', '{$newstatus}')";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_ERROR);
        $rtn = FALSE;
    }

    // Insert the first SLA update for the reopened incident, this indicates
    // the start of an sla period
    // This insert could possibly be merged with another of the 'updates'
    // records, but for now we keep it seperate for clarity
    $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, ";
    $sql .= "timestamp, currentowner, currentstatus, customervisibility, ";
    $sql .= "sla, bodytext) ";
    $sql .= "VALUES ('{$incident}', '{$sit[2]}', 'slamet', '{$now}', '{$owner}', ";
    $sql .= STATUS_ACTIVE.", 'show', 'opened','{$GLOBALS['strIncidentIsOpen']}')";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_ERROR);
        $rtn = FALSE;
    }

    // Insert the first Review update, this indicates the review period of an incident has restarted
    // This insert could possibly be merged with another of the 'updates' records, but for now we keep it seperate for clarity
    $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, sla, bodytext) ";
    $sql .= "VALUES ('{$incident}', '0', 'reviewmet', '{$now}', '{$owner}', ".STATUS_ACTIVE.", 'hide', 'opened','')";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_ERROR);
        $rtn = FALSE;
    }

    return $rtn;
}


/**
    Send a template email without using a trigger
    @author Ivan Lucas
    @param int $templateid: The ID number of the template to use
    @param array $paramarray. An associative array of template parameters
                 This should at the very least be
                 array('incidentid' => $id, 'triggeruserid' => $sit[2])
    @param string $attach. Path and filename of file to attach
    @param string $attachtype. Type of file to attach (Default 'OCTET')
    @param string $attachdesc. Description of the attachment, (Default, same as filename)
    @retval bool TRUE: The email was sent successfully
    @retval bool FALSE: There was an error sending the mail
    @note This is v2 of this function, it has different paramters than v1
**/
function send_email_template($templateid, $paramarray, $attach='', $attachtype='', $attachdesc='')
{
    global $CONFIG, $application_version_string, $sit;

    if (!is_array($paramarray))
    {
        trigger_error("Invalid Parameter Array", E_USER_NOTICE);
        $paramarray = array('triggeruserid' => $sit[2]);
    }

    if (!is_numeric($templateid))
    {
        trigger_error("Invalid Template ID '{$templateid}'", E_USER_NOTICE);
    }

    // Grab the template
    $tsql = "SELECT * FROM `{$dbEmailTemplates}` WHERE id=$templateid LIMIT 1";
    $tresult = mysql_query($tsql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($tresult) > 0) $template = mysql_fetch_object($tresult);
    $paramarray = array('incidentid' => $paramarray['incidentid'], 'triggeruserid' => $sit[2]);
    $from = replace_specials($template->fromfield, $paramarray);
    $replyto = replace_specials($template->replytofield, $paramarray);
    $ccemail = replace_specials($template->ccfield, $paramarray);
    $bccemail = replace_specials($template->bccfield, $paramarray);
    $toemail = replace_specials($template->tofield, $paramarray);
    $subject = replace_specials($template->subjectfield, $paramarray);
    $body = replace_specials($template->body, $paramarray);
    $extra_headers = "Reply-To: {$replyto}\nErrors-To: ".user_email($sit[2])."\n";
    $extra_headers .= "X-Mailer: {$CONFIG['application_shortname']} {$application_version_string}/PHP " . phpversion() . "\n";
    $extra_headers .= "X-Originating-IP: {$_SERVER['REMOTE_ADDR']}\n";
    if ($ccemail != '')  $extra_headers .= "CC: $ccemail\n";
    if ($bccemail != '') $extra_headers .= "BCC: $bccemail\n";
    $extra_headers .= "\n"; // add an extra crlf to create a null line to separate headers from body
                        // this appears to be required by some email clients - INL

    // Removed $mailerror as MIME_mail expects 5 args and not 6 of which is it not expect errors
    $mime = new MIME_mail($from, $toemail, html_entity_decode($subject), '', $extra_headers);
    $mime -> attach($body, '', "text-plain; charset={$GLOBALS['i18ncharset']}", 'quoted-printable');

    if (!empty($attach))
    {
        if (empty($attachdesc)) $attachdesc = "Attachment named {$attach}";
        $disp = "attachment; filename=\"{$attach}\"; name=\"{$attach}\";";
        $mime -> fattach($attach, $attachdesc, $attachtype, 'base64', $disp);
    }

    // actually send the email
    $rtnvalue = $mime -> send_mail();
    return $rtnvalue;
}

/**
 * Identified if there are drafts waiting to be sent/updated on an incident
 * @author Paul Heaney
 * @param int $incidentid - The incidentID to check for
 * @param string $type - The type of draft either all/email/update
 * @return bool TRUE of there are drafts waiting false otherwise
 */
function drafts_waiting_on_incident($incidentid, $type='all')
{
    $rtn = FALSE;
	$sql = "SELECT count(id) AS count FROM `{$GLOBALS['dbDrafts']}` WHERE incidentid = {$incidentid} ";
    if ($type == "update") $sql .= "AND type = 'update'";
    elseif ($type == "email") $sql .= "AND type = 'email'";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_ERROR);
        $rtn = FALSE;
    }

    list($count) = mysql_fetch_array($result);
    if ($count > 0) $rtn = TRUE;

    return $rtn;
}


/**
 * Gets the incident ID for an email based on its subject
 * @author Kierna Hogg
 * @param string $subject The email subject
 * @param string $from The email address it was sent from
 * @return int ID of the incident, 0 if none
 */
function incident_id_from_subject($subject, $from)
{
    $incident_id = 0;
    $from_parts = explode($from, "@");
    $domain = $from_parts[2];

    if (preg_match('/\[(\d{1,5})\]/', $subject, $m))
    {
        $incident_id = $m[1];
    }
    else
    {
        preg_match('/\d{1,12}/', $subjectm, $external_id);
        $external_id = $external_id[0];
        $sql = "SELECT name, email_domain FROM `{$dbEscalationPaths}`";
        $result = mysql_query($sql);
        if ($result)
        {
            while ($row = mysql_fetch_object($result))
            {
                if ($row->email_domain == $domain)
                {
                    $sql = "SELECT id FROM `{$dbIncidents}` ";
                    $sql .= "WHERE externalid";
                }
            }
        }
    }

}

/**
    * @author Ivan Lucas
*/
function count_incident_stats($incidentid)
{
    global $dbUpdates;
    $sql = "SELECT count(DISTINCT currentowner),count(id) FROM `{$dbUpdates}` WHERE incidentid='$incidentid' AND userid!=0 GROUP BY userid";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($unique_users,$num_updates) = mysql_fetch_row($result);
    return array($unique_users,$num_updates);;
}


/**
    * Returns number of closed incidents that were opened within the period giving
    * the average duration in minutes and the average worked time in minutes
    * @author Ivan Lucas
*/
function average_incident_duration($start,$end,$states)
{
    global $dbIncidents;
    $sql = "SELECT opened, closed, (closed - opened) AS duration_closed, i.id AS incidentid ";
    $sql .= "FROM `{$dbIncidents}` AS i ";
    $sql .= "WHERE status='2' ";
    if ($start > 0) $sql .= "AND opened >= $start ";
    if ($end > 0) $sql .= "AND opened <= $end ";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $totalduration = 0;
    $totalworkingduration = 0;
    $countclosed = 0;
    $total_unique_owners= 0;
    while ($row = mysql_fetch_object($result))
    {
        $working_time = calculate_incident_working_time($row->incidentid, $row->opened, $row->closed, $states);
        if ($row->duration_closed > 0)
        {
            $totalduration = $totalduration+$row->duration_closed;
            $totalworkingduration += $working_time;
            $cio = count_incident_stats($row->incidentid);
            $total_unique_owners += $cio[0];
            $total_updates += $cio[1];
            $countclosed++;
        }
    }
    $total_number_updates = number_format(($countclosed == 0) ? 0 : ($total_updates / $countclosed),1);
    $average_owners = number_format(($countclosed == 0) ? 0 : ($total_unique_owners / $countclosed),1);
    $average_incident_duration = ($countclosed == 0) ? 0 : ($totalduration / $countclosed) / 60;
    $average_worked_minutes = ($countclosed == 0) ? 0 : $totalworkingduration / $countclosed;

    return array($countclosed, $average_incident_duration, $average_worked_minutes,$average_owners, $total_updates, $total_number_updates);
}


/**
 * Returns the contents of an SLA target update, mostly for problem definition and action plan to pre-fill the close form
 * @author Kieran Hogg
 * @param $incidentid int The incident to get the update of
 * @param $target string The SLA target, initialresponse, probdef etc
 * @return string The updates of the message, stripped of line breaks
 */
function sla_target_content($incidentid, $target)
{
    $rtn = '';
    global $dbUpdates;
    $incidentid = cleanvar($incidentid);
    $target = cleanvar($target);

    $sql = "SELECT bodytext FROM `{$dbUpdates}` ";
    $sql .= "WHERE incidentid = '{$incidentid}' ";
    $sql .= "AND sla = '{$target}' ";
    $sql .= "ORDER BY timestamp DESC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($bodytext) = mysql_fetch_array($result);
    $bodytext = str_replace("<hr>", "", $bodytext);
    $rtn .= $bodytext;
    return $rtn;
}


/**
 * Retreive the service level tag of the incident
 * @author Paul Heaney
 * @param $incidentid int The incident ID to retreive
 * @return String The service level tag
 * @todo Remove as of 4.0 in favour of incidents class
 */
function incident_service_level($incidentid)
{
    global $dbIncidents;
    $servicelevel = '';

    $sql = "SELECT servicelevel FROM `{$dbIncidents}` WHERE id = {$incidentid}";
    $result = mysql_query($sql);

    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($servicelevel) = mysql_fetch_array($result);

    return $servicelevel;
}


/**
 * Load the incident entitlement for the portal
 * Moved from portal/ad.php
 * @param $contactid  - The contact to load the entitlement for
 * @param $siteid - The site the contact belongs to 
 */
function load_entitlements($contactid, $siteid)
{
    global $dbSupportContacts, $dbMaintenance, $dbProducts;
    
    //get entitlement
    $sql = "SELECT m.*, p.name, ";
    $sql .= "(m.incident_quantity - m.incidents_used) AS availableincidents ";
    $sql .= "FROM `{$dbSupportContacts}` AS sc, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p ";
    $sql .= "WHERE m.product=p.id ";
    $sql .= "AND sc.contactid='{$contactid}' AND sc.maintenanceid=m.id ";
    $sql .= "AND (expirydate > (UNIX_TIMESTAMP(NOW()) - 15778463) OR expirydate = -1) ";
    $sql .= "AND m.site = {$siteid} ";
    $sql .= "UNION SELECT m.*, p.name, ";
    $sql .= "(m.incident_quantity - m.incidents_used) AS availableincidents ";
    $sql .= "FROM `{$dbSupportContacts}` AS sc, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p ";
    $sql .= "WHERE m.product=p.id ";
    $sql .= "AND m.allcontactssupported = 'yes' ";
    $sql .= "AND (expirydate > (UNIX_TIMESTAMP(NOW()) - 15778463) OR expirydate = -1) ";
    $sql .= "AND m.site = {$siteid} ";
    $sql .= "ORDER BY expirydate DESC ";

    $contractresult = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    while ($contract = mysql_fetch_object($contractresult))
    {
        $_SESSION['entitlement'][] = serialize($contract);
    }
}

?>
