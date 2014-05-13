<?php
// incident.inc.php - functions relating to incidents
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
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
    $sql = "SELECT * FROM `{$dbIncidents}` WHERE id = '{$incident}'";
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
 * @param string $updatetext The update to open the incident with
 * @param int $priority (Optional) Priority of the incident (Default: 1 = Low)
 * @param int $owner (Optional) Owner of the incident (Default: 0 = SiT)
 * @param int $status (Optional) Incident status (Default: 1 = Active)
 * @param string $productversion (Optional) Product version field
 * @param string $productservicepacks (Optional) Product service packs field
 * @param int $opened (Optional) Timestamp when incident was opened (Default: now)
 * @param int $lastupdated (Optional) Timestamp when incident was updated (Default: now)
 * @param int $customerid (Optional) The customer reference for the incident
 * @return int|bool Returns FALSE on failure, an incident ID on success
 * @author Kieran Hogg
 */
function create_incident($title, $contact, $servicelevel, $contract, $product,
                         $software, $updatetext, $priority = PRIORITY_LOW, $owner = 0, $status = STATUS_ACTIVE,
                         $productversion = '', $productservicepacks = '',
                         $opened = '', $lastupdated = '', $customerid = '')
{
    global $now, $dbIncidents, $dbUpdates, $sit;

    if (empty($opened))
    {
        $opened = $now;
    }

    if (empty($lastupdated))
    {
        $lastupdated = $now;
    }
    
    if (!empty($customerid)) 
    {
        $customerid = "'{$customerid}'";
    }
    else
    {
        $customerid = "NULL";
    }

    $sql  = "INSERT INTO `{$dbIncidents}` (title, owner, contact, priority, ";
    $sql .= "servicelevel, status, maintenanceid, product, softwareid, ";
    $sql .= "productversion, productservicepacks, opened, lastupdated, customerid) ";
    $sql .= "VALUES ('{$title}', '{$owner}', '{$contact}', '{$priority}', ";
    $sql .= "'{$servicelevel}', '{$status}', '{$contract}', ";
    $sql .= "'{$product}', '{$software}', '{$productversion}', ";
    $sql .= "'{$productservicepacks}', '{$opened}', '{$lastupdated}', $customerid)";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        return FALSE;
    }
    else
    {
        $incidentid = mysql_insert_id();
        increment_incidents_used($contract);

    }

    //add the updates and SLA etc
    $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, bodytext, timestamp, currentowner, ";
    $sql .= "currentstatus, customervisibility, nextaction, sla) ";
    $sql .= "VALUES ('{$incidentid}', '{$sit[2]}', 'opening', '{$updatetext}', '{$now}', '{$sit[2]}', ";
    $sql .= "'1', '{$customervisibility}', '{$nextaction}', 'opened')";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    // Insert the first Review update, this indicates the review period of an incident has started
    // This insert could possibly be merged with another of the 'updates' records, but for now we keep it seperate for clarity
    $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, sla, bodytext) ";
    $sql .= "VALUES ('{$incidentid}', '{$sit[2]}', 'reviewmet', '{$now}', '{$sit[2]}', '1', 'hide', 'opened','')";
    mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    return $incidentid;
}


/**
 * Creates an incident based on an 'tempincoming' table entry
 * @author Kieran Hogg
 * @param int $incomingid the ID of the tempincoming entry
 * @return int|bool returns either the ID of the contract or FALSE if none
 */
function create_incident_from_incoming($incomingid)
{
    global $dbTempIncoming, $dbMaintenance, $dbServiceLevels, $dbSoftwareProducts, $CONFIG;
    $rtn = TRUE;

    $incomingid = intval($incomingid);
    $sql = "SELECT * FROM `{$dbTempIncoming}` ";
    $sql .= "WHERE id = '{$incomingid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

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

    $sql = "SELECT tag, product, softwareid ";
    $sql .= "FROM `{$dbMaintenance}` AS m, `{$dbServiceLevels}` AS s, ";
    $sql .= "`{$dbSoftwareProducts}` AS sp ";
    $sql .= "WHERE m.id = '{$contract}' ";
    $sql .= "AND m.servicelevel = s.tag ";
    $sql .= "AND m.product = sp.productid LIMIT 1";

    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_ERROR);
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
    else
    {
        $sql = "DELETE FROM `$dbTempIncoming` WHERE id = '{$incomingid}'";
        $result = mysql_query($sql);
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
    global $dbUpdates, $CONFIG, $fsdelim;
    $update = intval($update);
    $incident = intval($incident);

    $sql = "UPDATE `{$dbUpdates}` SET incidentid = '{$incident}' ";
    $sql .= "WHERE id = '{$update}'";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_ERROR);
        return FALSE;
    }
    else
    {
        $old_path = $CONFIG['attachment_fspath']. 'updates' . $fsdelim;
        $new_path = $CONFIG['attachment_fspath'] . $incident . $fsdelim;
        
        //move attachments from updates to incident
        $sql = "SELECT linkcolref, filename FROM `{$GLOBALS['dbLinks']}` AS l, ";
        $sql .= "`{$GLOBALS['dbFiles']}` as f ";
        $sql .= "WHERE l.origcolref = '{$update}' ";
        $sql .= "AND l.linktype = 5 ";
        $sql .= "AND l.linkcolref = f.id";
        $result = mysql_query($sql);
        if ($result)
        {
            if (!file_exists($new_path))
            {
                $umask = umask(0000);
                @mkdir($new_path, 0770);
                umask($umask);
            }
        
            while ($row = mysql_fetch_object($result))
            {
                $filename = $row->linkcolref . "-" . $row->filename;
                $old_file = $old_path . $row->linkcolref;
                if (file_exists($old_file))
                {
                    $rename = rename($old_file, $new_path . $filename);
                    if (!$rename)
                    {
                        trigger_error("Couldn't move file: {$file}", E_USER_WARNING);
                        $moved_attachments = FALSE;
                    }
                }
            }
        }
        
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
 * @return A user ID of the suggested new owner
 * @retval bool FALSE failure.
 * @retval int The user ID of the suggested new owner
 * @note Users are chosen randomly in a weighted lottery depending on their
 * avilability and queue status
 */
function suggest_reassign_userid($incidentid, $exceptuserid = 0)
{
    global $now, $dbUsers, $dbIncidents, $dbUserSoftware, $startofsession;
    $ticket = array();

    $sql = "SELECT product, softwareid, priority, contact, owner FROM `{$dbIncidents}` WHERE id={$incidentid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    if (!$result)
    {
        $userid = FALSE;
    }
    else
    {
        $incident = mysql_fetch_object($result);
        // If this is a critical incident the user we're assigning to must be online
        if ($incident->priority >= PRIORITY_CRITICAL)
        {
            $req_online = TRUE;
        }
        else
        {
            $req_online = FALSE;
        }

        // Find the users with this skill (or all users)
        if (!empty($incident->softwareid))
        {
            $sql = "SELECT us.userid, u.status, u.lastseen FROM `{$dbUserSoftware}` AS us, `{$dbUsers}` AS u ";
            $sql .= "WHERE u.id = us.userid AND u.status > 0 AND u.accepting='Yes' ";
            if ($exceptuserid > 0) $sql .= "AND u.id != '{$exceptuserid}' ";
            $sql .= "AND softwareid = {$incident->softwareid}";
        }
        else
        {
            $sql = "SELECT id AS userid, status, lastseen FROM `{$dbUsers}` AS u WHERE status > 0 AND u.accepting='Yes' ";
            if ($exceptuserid > 0) $sql .= "AND id != '{$exceptuserid}' ";
        }
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

        // Fallback to all users if we have no results from above
        if (mysql_num_rows($result) < 1)
        {
            $sql = "SELECT id AS userid, status, lastseen FROM `{$dbUsers}` AS u WHERE status > 0 AND u.accepting='Yes' ";
            if ($exceptuserid > 0) $sql .= "AND id != '{$exceptuserid}' ";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        }

        while ($user = mysql_fetch_object($result))
        {
            // Get a ticket for being skilled
            // Or in the case we don't know the skill, just get a ticket for accepting
            $ticket[] = $user->userid;

            // Get a ticket for being seen within the current session time
            if (mysql2date($user->lastseen) > $startofsession) $ticket[] = $user->userid;

            // Get two tickets for being marked in-office or working at home
            if ($user->status == USERSTATUS_IN_OFFICE OR $user->status == USERSTATUS_WORKING_FROM_HOME)
            {
                $ticket[] = $user->userid;
                $ticket[] = $user->userid;
            }

            // Get one ticket for being marked at lunch or in meeting
            // BUT ONLY if the incident isn't critical
            if ($incident->priority < PRIORITY_CRITICAL AND ($user->status == USERSTATUS_IN_MEETING OR $user->status == USERSTATUS_AT_LUNCH))
            {
                $ticket[] = $user->userid;
            }

            // Have a look at the users (all open) incident queue (owned)
            $qsql = "SELECT id, priority, lastupdated, status, softwareid FROM `{$dbIncidents}` WHERE owner={$user->userid} AND status != " . STATUS_CLOSED . " AND status != " . STATUS_CLOSING;
            $qresult = mysql_query($qsql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            $queue_size = mysql_num_rows($qresult);
            if ($queue_size > 0)
            {
                $queued_critical = 0;
                $queued_high = 0;
                $queue_lastupdated = 0;
                $queue_samecontact = FALSE;
                while ($queue = mysql_fetch_object($qresult))
                {
                    if ($queue->priority == PRIORITY_HIGH) $queued_high++;
                    if ($queue->priority >= PRIORITY_CRITICAL) $queued_critical++;
                    if ($queue->lastupdated > $queue_lastupdated) $queue_lastupdated = $queue->lastupdated;
                    if ($queue->contact == $incident->contact) $queue_samecontact = TRUE;
                }
                // Get one ticket for your queue being updated in the past 4 hours
                if ($queue_lastupdated > ($now - 14400)) $ticket[] = $user->userid;

                // Get two tickets for dealing with the same contact in your queue
                if ($queue_samecontact == TRUE)
                {
                    $ticket[] = $user->userid;
                    $ticket[] = $user->userid;
                }

                // Get one ticket for having five or less incidents
                if ($queue_size <= 5) $ticket[] = $user->userid;

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
        $numtickets = count($ticket) - 1;
        // Ensure we return a failure if we have a negative amount of tickets
        if ($numtickets < 0)
        {
            return FALSE;
        }
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

    if ($type == 'temp')
    {
        $sql = "UPDATE `{$dbIncidents}` SET towner = '{$tuser}' ";
    }
    else
    {
        $sql = "UPDATE `{$dbIncidents}` SET owner = '{$user}' ";
    }
    $sql .= "WHERE id = '{$incident}'";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_WARNING);
        $rtn = FALSE;
    }

    $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, nextaction) ";
    $sql .= "VALUES ('{$incident}', '{$sit[2]}', 'reassigning', '{$now}', '{$user}', '1', '{$nextaction}')";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_WARNING);
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
    if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

    $owner = incident_owner($incident);
    // add update
    $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, ";
    $sql .= "bodytext, timestamp, currentowner, currentstatus) ";
    $sql .= "VALUES ({$incident}, '{$sit[2]}', 'reopening', '{$bodytext}', '{$time}', ";
    $sql .= "'{$owner}', '{$newstatus}')";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_ERROR);
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
    $sql .= STATUS_ACTIVE.", 'show', 'opened', '{$GLOBALS['strIncidentIsOpen']}')";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_ERROR);
        $rtn = FALSE;
    }

    // Insert the first Review update, this indicates the review period of an incident has restarted
    // This insert could possibly be merged with another of the 'updates' records, but for now we keep it seperate for clarity
    $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, sla, bodytext) ";
    $sql .= "VALUES ('{$incident}', '0', 'reviewmet', '{$now}', '{$owner}', ".STATUS_ACTIVE.", 'hide', 'opened','')";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_ERROR);
        $rtn = FALSE;
    }

    return $rtn;
}


/**
 * Send a template email without using a trigger
 * @author Ivan Lucas
 * @param int $templateid: The ID number of the template to use
 * @param array $paramarray. An associative array of template parameters
             This should at the very least be
             array('incidentid' => $id, 'triggeruserid' => $sit[2])
 * @param string $attach. Path and filename of file to attach
 * @param string $attachtype. Type of file to attach (Default 'OCTET')
 * @param string $attachdesc. Description of the attachment, (Default, same as filename)
 * @return bool TRUE: The email was sent successfully, FALSE: There was an error sending the mail
 * @note This is v2 of this function, it has different paramters than v1
 */
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
    $tsql = "SELECT * FROM `{$dbEmailTemplates}` WHERE id={$templateid} LIMIT 1";
    $tresult = mysql_query($tsql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($tresult) > 0) $template = mysql_fetch_object($tresult);
    $paramarray = array('incidentid' => $paramarray['incidentid'], 'triggeruserid' => $sit[2]);
    if ($CONFIG['outbound_email_newline'] == 'CRLF')
    {
        $crlf = "\r\n";
    }
    else
    {
        $crlf = "\n";
    }
    $from = replace_specials($template->fromfield, $paramarray);
    $replyto = replace_specials($template->replytofield, $paramarray);
    $ccemail = replace_specials($template->ccfield, $paramarray);
    $bccemail = replace_specials($template->bccfield, $paramarray);
    $toemail = replace_specials($template->tofield, $paramarray);
    $subject = replace_specials($template->subjectfield, $paramarray);
    $body = replace_specials($template->body, $paramarray);
    $extra_headers = "Reply-To: {$replyto}{$crlf}Errors-To: ".user_email($sit[2]) . $crlf;
    if ($CONFIG['outbound_email_send_xoriginatingip']) $extra_headers .= "X-Mailer: {$CONFIG['application_shortname']} {$application_version_string}/PHP " . phpversion() . "\n";
    $extra_headers .= "X-Originating-IP: " . substr($_SERVER['REMOTE_ADDR'],0, 15) . "\n";
    if ($ccemail != '')  $extra_headers .= "CC: $ccemail\n";
    if ($bccemail != '') $extra_headers .= "BCC: $bccemail\n";
    $extra_headers .= "\n"; // add an extra crlf to create a null line to separate headers from body
                        // this appears to be required by some email clients - INL

    // Removed $mailerror as MIME_mail expects 5 args and not 6 of which is it not expect errors
    $mime = new MIME_mail($from, $toemail, html_entity_decode($subject), '', $extra_headers);
    $mime -> attach($body, '', "text-plain; charset={$GLOBALS['i18ncharset']}", $CONFIG['outbound_email_encoding']);

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
function drafts_waiting_on_incident($incidentid, $type='all', $userid='')
{
    $rtn = FALSE;
    $sql = "SELECT count(id) AS count FROM `{$GLOBALS['dbDrafts']}` WHERE incidentid = {$incidentid} ";
    if ($type == "update") $sql .= "AND type = 'update' ";
    elseif ($type == "email") $sql .= "AND type = 'email' ";

    if (!empty($userid)) $sql .= "AND userid = {$userid} ";

    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_ERROR);
        $rtn = FALSE;
    }

    list($count) = mysql_fetch_row($result);
    if ($count > 0) $rtn = TRUE;

    return $rtn;
}


/**
 * Gets the incident ID for an email based on its subject
 * @author Kieran Hogg
 * @param string $subject The email subject
 * @param string $from The email address it was sent from
 * @return int ID of the incident, 0 if none
 */
function incident_id_from_subject($subject, $from)
{
	global $CONFIG;
    $incident_id = FALSE;
    $from_parts = explode($from, "@");
    $domain = $from_parts[2];

    $open = escape_regex($CONFIG['incident_id_email_opening_tag']);
    $close = escape_regex($CONFIG['incident_id_email_closing_tag']);
    $prefix = escape_regex($CONFIG['incident_reference_prefix']);
    
    if (preg_match("/{$open}{$prefix}(\d+){$close}/", $subject, $m))
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
            while ($obj = mysql_fetch_object($result))
            {
                if ($obj->email_domain == $domain)
                {
                    $sql_ext = "SELECT id FROM `{$dbIncidents}` ";
                    $sql_ext .= "WHERE externalid = '{$external_id}'";
                    $result_ext = mysql_query($sql_ext);
                    if (mysql_num_rows($result_ext) != 1)
                    {
                        $o = mysql_fetch_object($result_ext);
                        $incident_id = $o->id;
                    }                    
                }
            }
        }
    }
    
    return $incident_id;
}


/**
 * @author Ivan Lucas
 */
function count_incident_stats($incidentid)
{
    global $dbUpdates;
    $sql = "SELECT count(DISTINCT currentowner),count(id) FROM `{$dbUpdates}` WHERE incidentid='{$incidentid}' AND userid!=0 GROUP BY userid";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
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
    $sql .= "WHERE status = '" . STATUS_CLOSED . "' ";
    if ($start > 0) $sql .= "AND opened >= {$start} ";
    if ($end > 0) $sql .= "AND opened <= {$end} ";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

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
    $incidentid = clean_int($incidentid);
    $target = clean_dbstring($target);

    $sql = "SELECT bodytext FROM `{$dbUpdates}` ";
    $sql .= "WHERE incidentid = '{$incidentid}' ";
    $sql .= "AND sla = '{$target}' ";
    $sql .= "ORDER BY timestamp DESC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    list($bodytext) = mysql_fetch_assoc($result);
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
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $servicelevel = mysql_fetch_object($result)->servicelevel;
    }

    return $servicelevel;
}


/**
 * Load the incident entitlement for the portal
 * Moved from portal/ad.php
 * @author Kieran Hogg
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
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    unset($_SESSION['entitlement']);
    while ($contract = mysql_fetch_object($contractresult))
    {
        $_SESSION['entitlement'][] = serialize($contract);
    }
}


/**
 * Get a readable last update body, written for the triggers variable
 * @param $incidentid  int The incident ID to get the update for
 * @param $num int amount of updates to include
 */
function readable_last_updates($incidentid, $num)
{
    global $dbUpdates;

    $num = intval($num);
    ($num == 0) ? $num = 1 : $num;
    $sql  = "SELECT * FROM `{$dbUpdates}` ";
    $sql .= " WHERE incidentid='{$incidentid}' ";
    $sql .= "AND bodytext != '' ";
    $sql .= "ORDER BY timestamp DESC ";
    if ($num != -1 ) $sql .= "LIMIT {$num}";
    $query = mysql_query($sql);
    $text = "";
    while ($result = mysql_fetch_object($query))
    {
        $num--;
        $text .= strip_tags($result->bodytext);
        if ($num > 0 ) $text .= "\n--------------------------\n";
    }
    return $text;
}


/**
 * @author Ivan Lucas
 * @param int $id Incident ID
 * @return integer. UserID of the user that currently owns the incident
 */
function incident_owner($id)
{
    return db_read_column('owner', $GLOBALS['dbIncidents'], $id);
}


/**
 * @author Ivan Lucas
 * @param int $id Incident ID
 * @return integer. UserID of the user that currently temporarily owns the incident
 */
function incident_towner($id)
{
    return db_read_column('towner', $GLOBALS['dbIncidents'], $id);
}


/**
 * @author Ivan Lucas
 * @param int $id Incident ID
 * @return integer. ContactID of the contact this incident is logged against
 */
function incident_contact($id)
{
    return db_read_column('contact', $GLOBALS['dbIncidents'], $id);
}


/**
 * @author Ivan Lucas
 * @param int $id Incident ID
 * @return integer. Contract ID of the maintenance contract this incident is logged against
 */
function incident_maintid($id)
{
    $maintid = db_read_column('maintenanceid', $GLOBALS['dbIncidents'], $id);
    if ($maintid == '')
    {
        trigger_error("!Error: No matching record while reading in incident_maintid() Incident ID: {$id}", E_USER_WARNING);
    }
    else
    {
        return ($maintid);
    }
}


/**
 * @author Ivan Lucas
 * @param int $id Incident ID
 * @return string. Title of the incident
 */
function incident_title($id)
{
    return db_read_column('title', $GLOBALS['dbIncidents'], $id);
}


/**
 * @author Ivan Lucas
 * @param int $id Incident ID
 * @return id. Current incident status ID
 */
function incident_status($id)
{
    return db_read_column('status', $GLOBALS['dbIncidents'], $id);
}


/**
 * @author Ivan Lucas
 * @param int $id Incident ID
 * @return id. Current incident Priority ID
 */
function incident_priority($id)
{
    return db_read_column('priority', $GLOBALS['dbIncidents'], $id);
}


/**
 * @author Ivan Lucas
 * @param int $id Incident ID
 * @return id. Current incident external ID
 */
function incident_externalid($id)
{
    return db_read_column('externalid', $GLOBALS['dbIncidents'], $id);
}


/**
* @author Paul Heaney
* @param int $id Incident ID
* @return id. Current incident customer ID
*/
function incident_customerid($id)
{
    return db_read_column('customerid', $GLOBALS['dbIncidents'], $id);
}


/**
 * @author Ivan Lucas
 * @param int $id Incident ID
 * @return string. Current incident external engineer
 */
function incident_externalengineer($id)
{
    return db_read_column('externalengineer', $GLOBALS['dbIncidents'], $id);
}


/**
 * @author Ivan Lucas
 * @param int $id Incident ID
 * @return string. Current incident external email address
 */
function incident_externalemail($id)
{
    return db_read_column('externalemail', $GLOBALS['dbIncidents'], $id);
}


/**
 * @author Ivan Lucas
 * @param int $id Incident ID
 * @return string. Current incident CC email address
 */
function incident_ccemail($id)
{
    return db_read_column('ccemail', $GLOBALS['dbIncidents'], $id);
}


/**
 * @author Ivan Lucas
 * @param int $id Incident ID
 * @return int. UNIX Timestamp of the time of the next action for this incident
 */
function incident_timeofnextaction($id)
{
    return db_read_column('timeofnextaction', $GLOBALS['dbIncidents'], $id);
}


/**
 * Returns a string representing the name of
 * the given priority. Returns an empty string if the
 * priority does not exist.
 * @author Ivan Lucas
 * @param int $id. Priority ID, higher the number higher the priority
 * @param bool $syslang. (optional) Uses system language when set to TRUE otherwise
 *                       uses user language (default)
 * @return string.
 */
function priority_name($id, $syslang = FALSE)
{
    switch ($id)
    {
        case PRIORITY_LOW:
            if (!$syslang) $value = $GLOBALS['strLow'];
            else $value = $_SESSION['syslang']['strLow'];
            break;
        case PRIORITY_MEDIUM:
            if (!$syslang) $value = $GLOBALS['strMedium'];
            else $value = $_SESSION['syslang']['strMedium'];
            break;
        case PRIORITY_HIGH:
            if (!$syslang) $value = $GLOBALS['strHigh'];
            else $value = $_SESSION['syslang']['strHigh'];
            break;
        case PRIORITY_CRITICAL:
            if (!$syslang) $value = $GLOBALS['strCritical'];
            else $value = $_SESSION['syslang']['strCritical'];
            break;
        case '':
            if (!$sylang) $value = $GLOBALS['strNotSet'];
            else $value = $_SESSION['syslang']['strNotSet'];
            break;
        default:
            if (!$syslang) $value = $GLOBALS['strUnknown'];
            else $value = $_SESSION['syslang']['strUnknown'];
            break;
    }
    return $value;
}


/**
 * Returns an array of fields from the most recent update record for a given incident id
 * @author Ivan Lucas
 * @param int $id An incident ID
 * @return array
 */
function incident_lastupdate($id)
{
    // Find the most recent update
    $sql = "SELECT userid, type, sla, currentowner, currentstatus, bodytext AS body, timestamp, nextaction, id ";
    $sql .= "FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid='{$id}' AND bodytext != '' ORDER BY timestamp DESC, id DESC LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) == 0)
    {
        trigger_error("Zero records while retrieving incident last update for incident {$id}", E_USER_WARNING);
    }
    else
    {
        $update = mysql_fetch_object($result);
        mysql_free_result($result);

        $offset = 500;
        
        if (strlen($update->body) > $offset) 
        {
            // Only truncate if more than $offset in length
            $pos = strpos($update->body, " ", $offset);
            if (!empty($pos)) 
            {
                // Only truncate if longer than 500 characters
                $update->body = substr($update->body, 0, $pos);
            }
        }
        
        // Remove Tags from update Body
        $update->body = trim($update->body);
        $update->body = $update->body;
        return array($update->userid, $update->type ,$update->currentowner, $update->currentstatus, $update->body, $update->timestamp, $update->nextaction, $update->id);
    }
}

/**
 * Returns a string containing the body of the first update (that is visible to customer)
 * in a format suitable for including in an email
 * @author Ivan Lucas
 * @param int $id An incident ID
 */
function incident_firstupdate($id)
{
    $sql = "SELECT bodytext FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid='{$id}' AND customervisibility='show' ";
    $sql .= "ORDER BY timestamp ASC, id ASC LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) >= 1)
    {
        list($bodytext) = mysql_fetch_row($result);
        $bodytext = strip_tags($bodytext);
    }
    else
    {
        $bodytext = '';
    }

    return $bodytext;
}


/**
 * Converts an incident status ID to an internationalised status string
 * @author Ivan Lucas
 * @param int $id. incident status ID
 * @param string $type. 'internal' or 'external', where external means customer/client facing
 * @return string Internationalised incident status.
 *                 Or empty string if the ID is not recognised.
 * @note The incident status database table must contain i18n keys.
 */
function incidentstatus_name($id, $type='internal')
{
    global $dbIncidentStatus;

    if ($type == 'external')
    {
        $type = 'ext_name';
    }
    else
    {
        $type = 'name';
    }

    $sql = "SELECT {$type} FROM `{$dbIncidentStatus}` WHERE id='{$id}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) == 0)
    {
        $name = '';
    }
    else
    {
        $incidentstatus = mysql_fetch_assoc($result);
        $name =  $GLOBALS[$incidentstatus[$type]];
    }
    return $name;
}


/**
 * Convert a closing status ID to a readable name
 * @param int $id. Closing status ID
 * @retval string Closing Status
*/
function closingstatus_name($id)
{
    global $dbClosingStatus;
    if ($id != '')
    {
        $closingstatus = db_read_column('name', $GLOBALS['dbClosingStatus'], $id);
    }
    else
    {
        $closingstatus = 'strUnknown';
    }

    return ($GLOBALS[$closingstatus]);
}

/**
 * Returns the number of remaining incidents given an incident pool id
 *
 * @param int $id. Pool ID
 * @retval int The number of incidents remaining
 * @retval string Returns a string meaning 'Unlimited' if theres no match on ID
*/
function incidents_remaining($id)
{
    $remaining = db_read_column('incidentsremaining', $GLOBALS['dbIncidentPools'], $id);
    if (empty($remaining))
    {
        $remaining = '&infin;';
    }

    return $remaining;
}


/**
 * Decrement a 'free' incident from a site by one
 *
 * @param int $siteid. Site ID
 * @retval TRUE success
*/
function decrement_free_incidents($siteid)
{
    global $dbSites;
    $sql = "UPDATE `{$dbSites}` SET freesupport = (freesupport - 1) WHERE id='{$siteid}'";
    mysql_query($sql);
    if (mysql_affected_rows() < 1)
    {
        trigger_error("No rows affected while updating freesupport", E_USER_ERROR);
    }

    if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
    else return TRUE;
}


/**
 * Increment the contract count of incidents used by one
 * @param int $maintid - Contract ID
*/
function increment_incidents_used($maintid)
{
    global $dbMaintenance;
    $sql = "UPDATE `{$dbMaintenance}` SET incidents_used = (incidents_used + 1) WHERE id='{$maintid}'";
    mysql_query($sql);
    if (mysql_affected_rows() < 1) trigger_error("No rows affected while updating freesupport", E_USER_ERROR);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
    else return TRUE;
}


/**
 * Counts the number of incidents opened on a specific date
 * @param int $day The day of the month
 * @param int $month The month of the year
 * @param int $year The year
 * @author Ivan Lucas
 */
function countdayincidents($day, $month, $year)
{
    global $dbIncidents;
    $unixstartdate = mktime(0, 0, 0, $month, $day, $year);
    $unixenddate = mktime(23, 59, 59, $month, $day, $year);
    $sql = "SELECT count(id) FROM `{$dbIncidents}` ";
    $sql .= "WHERE opened BETWEEN '{$unixstartdate}' AND '{$unixenddate}' ";
    $result = mysql_query($sql);
    list($count) = mysql_fetch_row($result);
    mysql_free_result($result);
    return $count;
}


/**
 * Counts the number of incidents closed on a specific date
 * @param int $day The day of the month
 * @param int $month The month of the year
 * @param int $year The year
 * @author Ivan Lucas
 */
function countdayclosedincidents($day, $month, $year)
{
    global $dbIncidents;
    $unixstartdate = mktime(0, 0, 0, $month, $day, $year);
    $unixenddate = mktime(23, 59, 59, $month, $day, $year);
    $sql = "SELECT COUNT(id) FROM `{$dbIncidents}` ";
    $sql .= "WHERE closed BETWEEN '{$unixstartdate}' AND '{$unixenddate}' ";
    $result = mysql_query($sql);
    list($count) = mysql_fetch_row($result);
    mysql_free_result($result);
    return $count;
}


/**
 * Counts the number of incidents open on a specific date
 * @param int $day The day of the month
 * @param int $month The month of the year
 * @param int $year The year
 * @author Ivan Lucas
 */
function countdaycurrentincidents($day, $month, $year)
{
    global $dbIncidents;
    $unixstartdate = mktime(0, 0, 0, $month, $day, $year);
    $unixenddate = mktime(23, 59, 59, $month, $day, $year);
    $sql = "SELECT COUNT(id) FROM `{$dbIncidents}` ";
    $sql .= "WHERE opened <= '{$unixenddate}' AND closed >= '{$unixstartdate}' ";
    $result = mysql_query($sql);
    list($count) = mysql_fetch_row($result);
    mysql_free_result($result);
    return $count;
}


/**
 * Counts the number of updates not yet linked to an incident
 * @author Ivan Lucas
 * @note Updates are linked to incident ID 0 (no such incident) when they
 * arrive via email or portal and not yet assigned to an incident.
 */
function count_incoming_updates()
{
    $sql = "SELECT id FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid=0";
    $result = mysql_query($sql);
    $count = mysql_num_rows($result);
    mysql_free_result($result);
    return $count;
}


/**
 * Identify the next due SLA target for a given incident
 *
 * @param int $incidentid
 * retval string Target type
*/
function incident_get_next_target($incidentid)
{
    global $now;

    
    // Find the most recent SLA target that was met
    $sql = "SELECT sla, timestamp FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid='{$incidentid}' AND sla IS NOT Null ORDER BY id DESC LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    $sla_targets = get_incident_sla_targets($incidentid);

    $target = new SLATarget();
    if (mysql_num_rows($result) > 0)
    {
        $upd = mysql_fetch_object($result);
        switch ($upd->sla)
        {
            case 'opened':
                if ($sla_targets->initial_response_mins > 0)
                {
                    $target->type = 'initialresponse';
                    break;
                }
            case 'initialresponse':
                if ($sla_targets->prob_determ_mins > 0)
                {
                    $target->type = 'probdef';
                    break;
                }
            case 'probdef':
                if ($sla_targets->action_plan_mins > 0)
                {
                    $target->type = 'actionplan';
                    break;
                }
            case 'actionplan':
                if ($sla_targets->resolution_days > 0)
                {
                    $target->type = 'solution';
                    break;
                }
            case 'solution':
                $target->type = '';
                break;
            case 'closed':
                $target->type = 'opened';
                break;
        }

        $target->since = calculate_incident_working_time($incidentid, $upd->timestamp, $now);
    }
    else
    {
        $target->type = 'regularcontact';
        $target->since = 0;
    }
    return $target;
}


/**
 * Convert an SLA target type to a readable SLA target name
 * @param string $targettype
 * @retval string Target type readable name
*/
function target_type_name($targettype)
{
    switch ($targettype)
    {
        case 'opened':
            $name = $GLOBALS['strOpened'];
            break;
        case 'initialresponse':
            $name = $GLOBALS['strInitialResponse'];
            break;
        case 'probdef':
            $name = $GLOBALS['strProblemDefinition'];
            break;
        case 'actionplan':
            $name = $GLOBALS['strActionPlan'];
            break;
        case 'solution':
            $name = $GLOBALS['strResolutionReprioritisation'];
            break;
        case 'closed':
            $name = '';
            break;
        case 'regularcontact':
            $name = '';
            break; // Contact Customer
        default:
            $name = '';
            break;
    }
    return $name;
}


/**
 * Returns the number of minutes since the last incident review for a specified
 * incident
 * @author Ivan Lucas
 * @param int $incidentid - Incident ID
 * @return int Time since the last review in minutes
 * @note was called incident_get_next_review() (very bad name) until 3.60 14Mar10
 */
function incident_time_since_review($incidentid)
{
    global $now;
    $sql = "SELECT timestamp FROM `{$GLOBALS['dbUpdates']}` ";
    $sql .= "WHERE incidentid='{$incidentid}' AND type='reviewmet' ";
    $sql .= "ORDER BY id DESC LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $upd = mysql_fetch_object($result);
        $timesincereview = floor(($now - $upd->timestamp) / 60);
    }
    return $timesincereview;
}


/**
 * Switches incidents temporary owners to the backup/substitute engineer depending on the setting of 'accepting'
 * @author Ivan Lucas
 * @param int $userid. The userid of the user who's status has changed.
 * @param string $accepting. 'yes' or 'no' to indicate whether the user is accepting
 * @note if the $accepting parameter is 'no' then the function will attempt to temporarily assign
 * all the open incidents that the user owns to the users defined substitute engineers
 * If Substitute engineers cannot be found or they themselves are not accepting, the given users incidents
 * are placed in the holding queue
 */
function incident_backup_switchover($userid, $accepting)
{
    global $now, $dbIncidents, $dbUpdates, $dbTempAssigns, $dbUsers, $dbUserStatus;

    $usersql = "SELECT u.*, us.name AS statusname ";
    $usersql .= "FROM `{$dbUsers}` AS u, `{$dbUserStatus}` AS us ";
    $usersql .= "WHERE u.id = '{$userid}' AND u.status = us.id";
    $userresult = mysql_query($usersql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $user = mysql_fetch_row($userresult);

    if (strtolower($accepting) == 'no')
    {
        // Look through the incidents that this user OWNS (and are not closed)
        $sql = "SELECT * FROM `{$dbIncidents}` WHERE (owner='{$userid}' OR towner='{$userid}') AND status!=2";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        while ($incident = mysql_fetch_object($result))
        {
            // Try and find a backup/substitute engineer
            $backupid = software_backup_userid($userid, $incident->softwareid);

            if (empty($backupid) OR user_accepting($backupid) == 'No')
            {
                // no backup engineer found so add to the holding queue
                // Look to see if this assignment is in the queue already
                $fsql = "SELECT * FROM `{$dbTempAssigns}` WHERE incidentid='{$incident->id}' AND originalowner='{$userid}'";
                $fresult = mysql_query($fsql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                if (mysql_num_rows($fresult) < 1)
                {
                    // it's not in the queue, and the user isn't accepting so add it
                    //$userstatus=user_status($userid);
                    $userstatus = $user['status'];
                    $usql = "INSERT INTO `{$dbTempAssigns}` (incidentid,originalowner,userstatus) VALUES ('{$incident->id}', '{$userid}', '{$userstatus}')";
                    mysql_query($usql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
                }
            }
            else
            {
                // do an automatic temporary reassign
                // update incident
                $rusql = "UPDATE `{$dbIncidents}` SET ";
                $rusql .= "towner='{$backupid}', lastupdated='{$now}' WHERE id='{$incident->id}' LIMIT 1";
                mysql_query($rusql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

                // add update
                $username = user_realname($userid);
                //$userstatus = userstatus_name(user_status($userid));
                $userstatus = $user['statusname'];
                //$usermessage=user_message($userid);
                $usermessage = $user['message'];
                $bodytext = "Previous Incident Owner ({$username}) {$userstatus}  {$usermessage}";
                $assigntype = 'tempassigning';
                $risql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, bodytext, type, timestamp, currentowner, currentstatus) ";
                $risql .= "VALUES ('{$incident->id}', '0', '{$bodytext}', '{$assigntype}', '{$now}', ";
                $risql .= "'{$backupid}', ";
                $risql .= "'{$incident->status}')";
                mysql_query($risql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

                // Look to see if this assignment is in the queue already
                $fsql = "SELECT * FROM `{$dbTempAssigns}` WHERE incidentid='{$incident->id}' AND originalowner='{$userid}'";
                $fresult = mysql_query($fsql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                if (mysql_num_rows($fresult) < 1)
                {
                    //$userstatus=user_status($userid);
                    $userstatus = $user['status'];
                    $usql = "INSERT INTO `{$dbTempAssigns}` (incidentid,originalowner,userstatus,assigned) VALUES ('{$incident->id}', '{$userid}', '$userstatus','yes')";
                    mysql_query($usql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
                }
                else
                {
                    // mark the temp assigns table so it's not showing in the holding queue
                    $tasql = "UPDATE `{$dbTempAssigns}` SET assigned='yes' WHERE originalowner='{$userid}' AND incidentid='{$incident->id}' LIMIT 1";
                    mysql_query($tasql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
                }
            }
        }
    }
    elseif ($accepting == '')
    {
        // Do nothing when accepting status doesn't exist
    }
    else
    {
        // The user is now ACCEPTING, so first have a look to see if there are any reassignments in the queue
        $sql = "SELECT * FROM `{$dbTempAssigns}` WHERE originalowner='{$userid}' ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        while ($assign = mysql_fetch_object($result))
        {
            if ($assign->assigned == 'yes')
            {
                // Incident has actually been reassigned, so have a look if we can grab it back.
                $lsql = "SELECT id,status FROM `{$dbIncidents}` ";
                $lsql .= "WHERE id='{$assign->incidentid}' AND owner='{$assign->originalowner}' AND towner!=''";
                $lresult = mysql_query($lsql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                while ($incident = mysql_fetch_object($lresult))
                {
                    // Find our tempassign
                    $usql = "SELECT id,currentowner FROM `{$dbUpdates}` ";
                    $usql .= "WHERE incidentid='{$incident->id}' AND userid='0' AND type='tempassigning' ";
                    $usql .= "ORDER BY id DESC LIMIT 1";
                    $uresult = mysql_query($usql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                    list($prevassignid,$tempowner) = mysql_fetch_row($uresult);

                    // Look to see if the temporary owner has updated the incident since we temp assigned it
                    // If he has, we leave it in his queue
                    $usql = "SELECT id FROM `{$dbUpdates}` ";
                    $usql .= "WHERE incidentid='{$incident->id}' AND id > '{$prevassignid}' AND userid='{$tempowner}' LIMIT 1 ";
                    $uresult = mysql_query($usql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                    if (mysql_num_rows($uresult) < 1)
                    {
                        // Incident appears not to have been updated by the temporary owner so automatically reassign back to orignal owner
                        // update incident
                        $rusql = "UPDATE `{$dbIncidents}` SET ";
                        $rusql .= "towner='', lastupdated='{$now}' WHERE id='{$incident->id}' LIMIT 1";
                        mysql_query($rusql);
                        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

                        // add update
                        $username = user_realname($userid);
                        //$userstatus = userstatus_name(user_status($userid));
                        $userstatus = $user['statusname'];
                        //$usermessage=user_message($userid);
                        $usermessage = $user['message'];
                        $bodytext = "Reassigning to original owner {$username} ({$userstatus})";
                        $assigntype = 'reassigning';
                        $risql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, bodytext, type, timestamp, currentowner, currentstatus) ";
                        $risql .= "VALUES ('{$incident->id}', '0', '{$bodytext}', '{$assigntype}', '{$now}', ";
                        $risql .= "'{$backupid}', ";
                        $risql .= "'{$incident->status}')";
                        mysql_query($risql);
                        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

                        // remove from assign queue now, all done
                        $rsql = "DELETE FROM `{$dbTempAssigns}` WHERE incidentid='{$assign->incidentid}' AND originalowner='{$assign->originalowner}'";
                        mysql_query($rsql);
                        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
                    }
                }
            }
            else
            {
                // now have a look to see if the reassign was completed
                $ssql = "SELECT id FROM `{$dbIncidents}` WHERE id='{$assign->incidentid}' LIMIT 1";
                $sresult = mysql_query($ssql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                if (mysql_num_rows($sresult) >= 1)
                {
                    // reassign wasn't completed, or it was already assigned back, simply remove from assign queue
                    $rsql = "DELETE FROM `{$dbTempAssigns}` WHERE incidentid='{$assign->incidentid}' AND originalowner='{$assign->originalowner}'";
                    mysql_query($rsql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
                }
            }
        }
    }
    return;
}


/**
 * Inserts a new incident update
 * @param int $incidentid ID of the incident to add the update to
 * @param string $text The text of the update
 * @param enum $type (Optional) Update type (Default: 'default'), types:
 * 'default', 'editing', 'opening', 'email', 'reassigning', 'closing',
 * 'reopening', 'auto', 'phonecallout', 'phonecallin', 'research', 'webupdate',
 * 'emailout', 'emailin', 'externalinfo', 'probdef', 'solution', 'actionplan',
 * 'slamet', 'reviewmet', 'tempassigning', 'auto_chase_email',
 * 'auto_chase_phone', 'auto_chase_manager', 'auto_chased_phone',
 * 'auto_chased_manager', 'auto_chase_managers_manager',
 * 'customerclosurerequest', 'fromtask'
 * @param string $sla The SLA the update meets
 * @param int $userid (Optional) ID of the user doing the updating (Default: 0)
 * @param int $currentowner (Optional) ID of the current incident owner
 * @param int $currentstatus (Optional) Current incident status (Default: 1 = active)
 * @param enum $visibility (Optional) Whether to 'show' or 'hide' in the portal (Default: 'show')
 * @author Kieran Hogg
 */
function new_update($incidentid, $text, $type = 'default', $sla = '', $userid = 0, $currentowner = '',
                    $currentstatus = 1, $visibility = 'show')
{
    global $now;
    $sql  = "INSERT INTO `{$GLOBALS['dbUpdates']}` (incidentid, userid, ";
    $sql .= "type, bodytext, timestamp, currentowner, currentstatus, ";
    $sql .= "customervisibility, sla) VALUES ('{$incidentid}', '{$userid}', ";
    $sql .= "'{$type}', '{$text}', '{$now}', '{$currentowner}', ";
    $sql .= "'{$currentstatus}', '{$visibility}', '{$sla}')";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        return FALSE;
    }
    else
    {
        return mysql_insert_id();
    }
}


/**
 * Create a new holding queue item
 * @param int $updateid ID of the associated update entry
 * @param string $from Name of the from field
 * @param string $subject Subject of the item
 * @param string $emailfrom Email address the item is from
 * @param int $contactid (Optional) Contact ID of the sender
 * @param int $incidentid (Optional) Associated incident ID
 * @param int $locked (Optional) 1 if the item is locked, 0 if not
 * @param time $lockeduntil (Optional) MySQL timestamp of lock expiration
 * @param string $reason (Optional) Reason the item is in the holding queue
 * @param id $reason_user (Optional) The user ID who set the reason
 * @param time $reason_time (Optional) MySQL timestamp of when the reason was set
 * @author Kieran Hogg
 */
function create_temp_incoming($updateid, $from, $subject, $emailfrom,
                              $contactid = '', $incidentid = 0, $locked = '',
                              $lockeduntil = '', $reason = '',
                              $reason_user = '', $reason_time = '')
{
    global $dbTempIncoming;
    $sql = "INSERT INTO `{$dbTempIncoming}`(updateid, `from`, subject, ";
    $sql .= "emailfrom, contactid, incidentid, locked, lockeduntil, ";
    $sql .= "reason, reason_user, reason_time) VALUES('{$updateid}', ";
    $sql .= "'{$from}', '{$subject}', '{$emailfrom}', '{$contactid}', ";
    $sql .= "'{$incidentid}', '{$locked}', '{$lockeduntil}', '{$reason}', ";
    $sql .= "'{$reason_user}', '{$reason_time}')";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        return FALSE;
    }
    else
    {
        return mysql_insert_id();
    }
}


function holding_email_update_id($holding_email)
{
    $holding_email = intval($holding_email);
    return db_read_column('updateid', $GLOBALS['dbTempIncoming'], $holding_email);
}


function delete_holding_queue_update($updateid)
{
    $sql = "DELETE FROM `{$GLOBALS['dbTempIncoming']}` WHERE updateid = '{$updateid}'";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(). "  {$sql}", E_USER_WARNING);
        return FALSE;
    }
    else
    {
        return TRUE;
    }
}


function num_unread_emails()
{
    global $dbTempIncoming;
    $sql = "SELECT COUNT(*) AS count FROM `{$dbTempIncoming}`";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(). "  {$sql}", E_USER_WARNING);
    list($count) = mysql_fetch_row($result);
    return $count;
}


/**
 * Return the number of incidents ever logged against a site
 * @author Kieran
 * @param int $id. Site ID
 * @param boolean $open. Include only open incidents (FALSE includes all)
 * @return int.
 */
function site_count_incidents($id, $open=FALSE)
{
    global $dbIncidents, $dbContacts;
    $id = intval($id);
    $count = 0;

    $sql = "SELECT COUNT(i.id) FROM `{$dbIncidents}` AS i, `{$dbContacts}` as c ";
    $sql .= "WHERE i.contact = c.id ";
    $sql .= "AND c.siteid='{$id}' ";
    if ($open) $sql .= "AND i.status != ".STATUS_CLOSED;
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    else list($count) = mysql_fetch_row($result);
    mysql_free_result($result);

    return $count;
}


/**
 * @author Paul Heaney
 */
function display_drafts($type, $result)
{
    global $iconset;
    global $id;
    global $CONFIG;

    if ($type == 'update')
    {
        $page = "incident_update.php";
        $editurlspecific = '';
    }
    else if ($type == 'email')
    {
        $page = "incident_email.php";
        $editurlspecific = "&amp;step=2";
    }

    echo "<p align='center'>{$GLOBALS['strDraftChoose']}</p>";

    $html = '';

    while ($obj = mysql_fetch_object($result))
    {
        $html .= "<div class='detailhead'>";
        $html .= "<div class='detaildate'>".date($CONFIG['dateformat_datetime'], $obj->lastupdate);
        $html .= "</div>";
        $html .= "<a href='{$page}?action=editdraft&amp;draftid={$obj->id}&amp;id={$id}{$editurlspecific}' class='info'>";
        $html .= icon('edit', 16, $GLOBALS['strDraftEdit'])."</a>";
        $html .= "<a href='{$page}?action=deletedraft&amp;draftid={$obj->id}&amp;id={$id}' class='info'>";
        $html .= icon('delete', 16, $GLOBALS['strDraftDelete'])."</a>";
        $html .= "</div>";
        $html .= "<div class='detailentry'>";
        $html .= nl2br($obj->content)."</div>";
    }

    return $html;
}


function external_escalation($escalated, $incid)
{
    foreach ($escalated as $i => $id)
    {
        if ($id == $incid)
        {
            return "yes";
        }
    }

    return "no";
}

/**
 * Get an SLA of an incident in human-readable format
 * Written for trigger templates but can be re-used
 * @param int $incident_id. Incident ID
 * @param string $type. Type of the SLA, from: initial_response, prob_determ, action_plan, resolution
 * @return string
 */
function incident_sla($incident_id, $type)
{
    global $dbServiceLevels;
    $incident = incident($incident_id);
    $sql = "SELECT * FROM `{$dbServiceLevels}` ";
    $sql .= "WHERE tag = '{$incident->servicelevel}' AND priority = '{$incident->priority}'";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        return "Error getting SLA";
    }
    else
    {
        $sla_obj = mysql_fetch_object($result);
        switch ($type)
        {
            case 'prob_determ':
                $sla = $sla_obj->prob_determ_mins;
                break;

            case 'action_plan':
                $sla = $sla_obj->action_plan_mins;
                break;

            case 'resolution':
                $sla = $sla_obj->resolution_days * 480;
                break;

            case 'initial_response':
            default:
                $sla = $sla_obj->initial_response_mins;
                break;

        }
        return format_workday_minutes($sla);
    }
}


/**
 * Get the user facing incident ID, this translates the internal ID to the user ID shown to the user
 * @author Paul Heaney
 * @param int $id The internal incident ID (from the database)
 * @return String The user facing incident ID
 */
function get_userfacing_incident_id($id)
{
	global $CONFIG;
	
	return "{$CONFIG['incident_reference_prefix']}{$id}";
}


/**
* Get the user facing incident ID used for emails, this translates the internal ID to the user ID shown to the user
* @author Paul Heaney
* @param int $id The internal incident ID (from the database)
* @return String The user facing incident ID for emails
*/
function get_userfacing_incident_id_email($id)
{
	global $CONFIG;

	return "{$CONFIG['incident_id_email_opening_tag']}{$CONFIG['incident_reference_prefix']}{$id}{$CONFIG['incident_id_email_closing_tag']}";
}


/**
 * Gets all the SLA targets for a particular incident 
 * 
 * @author Paul Heaney..
 * @param int $incidentid The ID of the incident to get the applicable SLA targets for.
 * @return object With the following properties: initial_response_mins, prob_determ_mins, action_plan_mins, resolution_days, review_days, timed, allow_reopen
 */
function get_incident_sla_targets($incidentid)
{
    $incidentsla = incident_service_level($incidentid);
    
    $sql_sla = "SELECT initial_response_mins, prob_determ_mins, action_plan_mins, resolution_days, review_days, timed, allow_reopen ";
    $sql_sla .= "FROM `{$GLOBALS['dbServiceLevels']}` WHERE tag = '{$incidentsla}' GROUP BY tag";
    
    $result_sla = mysql_query($sql_sla);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    return mysql_fetch_object($result_sla);
}


/**
 * Not 100% sure of the purpose of this function though triggers requires it, TODO revisit and fully under stand
 * 
 * @author Paul Heaney - to fix Mantis 1372
 * @param int $holdingemailid The holding queue ID
 * @return int the update ID
 */
function incoming_email_update_id($holdingemailid)
{
    $sql = "SELECT updateid FROM `{$GLOBALS['dbTempIncoming']}` WHERE id = {$holdingemailid}";
    $contractresult = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    list($updateid) = mysql_fetch_array($result);
    
    return $updateid;
}

?>
