<?php
// sactions.inc.php - functions relating to scheduler actions/auto
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
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
 * Select incidents awaiting closure for more than a week where the next action time is not set or has passed
 * @author Ivan Lucas
 * @param $closure_delay int. The amount of time (in seconds) to wait before closing
 */
function saction_CloseIncidents($closure_delay)
{
    $success = TRUE;
    global $dbIncidents, $dbUpdates, $CONFIG, $now;

    if ($closure_delay < 1) $closure_delay = 554400; // Default  six days and 10 hours

    // Code added back in to fix mark as closure incidents
    // http://bugs.sitracker.org/view.php?id=717
    $sql = "UPDATE `{$dbIncidents}` SET lastupdated='{$now}', ";
    $sql .= "closed='{$now}', status='".STATUS_CLOSED."', closingstatus='4', ";
    $sql .= "timeofnextaction='0' WHERE status='".STATUS_CLOSING."' ";
    $sql .= "AND (({$now} - lastupdated) > '{$closure_delay}') ";
    $sql .= "AND (timeofnextaction='0' OR timeofnextaction <= '{$now}')";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_WARNING);
        $success = FALSE;
    }

    $sql = "SELECT * FROM `{$dbIncidents}` WHERE status='".STATUS_CLOSING."' ";
    $sql .= "AND (({$now} - lastupdated) > '{$closure_delay}') ";
    $sql .= "AND (timeofnextaction='0' OR timeofnextaction<='{$now}') ";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_WARNING);
        $success = FALSE;
    }

    //if ($CONFIG['debug']) debug_log("Found ".mysql_num_rows($result)." Incidents to close");

    while ($obj = mysql_fetch_object($result))
    {
        $bill = close_billable_incident($obj->id); // Do the close tasks if necessary

        if ($bill)
        {
            $sqlb = "UPDATE `{$dbIncidents}` SET lastupdated='{$now}', ";
            $sqlb .= "closed='{$now}', status='".STATUS_CLOSED."', closingstatus='4', ";
            $sqlb .= "timeofnextaction='0' WHERE id='{$obj->id}'";
            $resultb = mysql_query($sqlb);
            if (mysql_error())
            {
                trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                $success = FALSE;
            }

            $sqlc = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp, nextaction, customervisibility) ";
            $sqlc .= "VALUES ('{$obj->id}', '0', 'closing', '{$obj->owner}', '{$obj->status}', 'Incident Closed by {$CONFIG['application_shortname']}', '{$now}', '', 'show' ) ";
            $resultc = mysql_query($sqlc);
            if (mysql_error())
            {
                trigger_error(mysql_error(), E_USER_WARNING);
                $success = FALSE;
            }
        }
        else
        {
            $success = FALSE;
        }
    }
    return $success;
}


/**
 * @author Ivan Lucas
 */
function saction_PurgeJournal()
{
    global $dbJournal, $now, $CONFIG;
    $success = TRUE;
    $purgedate = date('YmdHis',($now - $CONFIG['journal_purge_after']));
    $sql = "DELETE FROM `{$dbJournal}` WHERE timestamp < $purgedate";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_WARNING);
        $success = FALSE;
    }
    if ($CONFIG['debug']); //debug_log("Purged ".mysql_affected_rows()." journal entries");

    return $success;
}


/** Calculate SLA times
 * @author Tom Gerrard
 * @note Moved from htdocs/auto/timecalc.php by INL for 3.40 release
 */
function saction_TimeCalc()
{
    global $now;
    global $dbIncidents, $dbServiceLevels, $dbMaintenance, $dbUpdates;
    global $GLOBALS, $CONFIG;

    $success = TRUE;
    // FIXME this should only run INSIDE the working day
    // FIXME ? this will not update the database fully if two SLAs have been met since last run - does it matter ?

    $sql = "SELECT id, title, maintenanceid, priority, slaemail, slanotice, servicelevel, status, owner ";
    $sql .= "FROM `{$dbIncidents}` WHERE status != ".STATUS_CLOSED." AND status != ".STATUS_CLOSING;
    $incident_result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_WARNING);
        $success = FALSE;
    }

    while ($incident = mysql_fetch_object($incident_result))
    {
        // Get the service level timings for this class of incident, we may have one
        // from the incident itself, otherwise look at contract type
        if ($incident->servicelevel ==  '')
        {
            $sql = "SELECT servicelevel FROM  `{$dbMaintenance}` WHERE id = '{$incident->maintenanceid}'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            $t = mysql_fetch_row($sql);
            $tag = $t[0];
            mysql_free_result($result);
        }
        else $tag = $incident->servicelevel;

        $newReviewTime = -1;
        $newSlaTime = -1;

        $sql = "SELECT id, type, sla, timestamp, currentstatus FROM `{$dbUpdates}` WHERE incidentid='{$incident->id}' ";
        $sql .=" AND sla IS NOT Null ORDER BY id DESC LIMIT 1";
        $update_result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(), E_USER_WARNING);
            $success = FALSE;
        }

        if (mysql_num_rows($update_result) != 1)
        {
            //if ($CONFIG['debug']) //debug_log("Cannot find SLA information for incident ".$incident['id'].", skipping");
        }
        else
        {
            $slaInfo = mysql_fetch_object($update_result);
            $newSlaTime = calculate_incident_working_time($incident->id, $slaInfo->timestamp, $now);
            if ($CONFIG['debug'])
            {
                //debug_log("   Last SLA record is ".$slaInfo['sla']." at ".date("jS F Y H:i",$slaInfo['timestamp'])." which is $newSlaTime working minutes ago");
            }
        }
        mysql_free_result($update_result);

        $sql = "SELECT id, type, sla, timestamp, currentstatus, currentowner FROM `{$dbUpdates}` WHERE incidentid='{$incident->id}' ";
        $sql .= "AND type='reviewmet' ORDER BY id DESC LIMIT 1";
        $update_result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(), E_USER_WARNING);
            $success = FALSE;
        }

        if (mysql_num_rows($update_result) != 1)
        {
            //if ($CONFIG['debug']) //debug_log("Cannot find review information for incident ".$incident['id'].", skipping");
        }
        else
        {
            $reviewInfo = mysql_fetch_object($update_result);
            $newReviewTime = floor($now - $reviewInfo->timestamp) / 60;
            if ($CONFIG['debug'])
            {
                //if ($reviewInfo['currentowner'] != 0) //debug_log("There has been no review on incident {$incident['id']}, which was opened $newReviewTime minutes ago");
            }
            new TriggerEvent("TRIGGER_INCIDENT_REVIEW_DUE",
                                array('incidentid' => $incident->id,
                                      'time' => $newReviewTime));
        }
        mysql_free_result($update_result);


        if ($newSlaTime != -1)
        {
            // Get these time of NEXT SLA requirement in minutes
            $coefficient = 1;
            $NextslaName = $GLOBALS['strSLATarget'];

            switch ($slaInfo->sla)
            {
                case 'opened':
                    $slaRequest='initial_response_mins';
                    $NextslaName = $GLOBALS['strInitialResponse'];
                    break;
                case 'initialresponse':
                    $slaRequest='prob_determ_mins';
                    $NextslaName = $GLOBALS['strProblemDefinition'];
                    break;
                case 'probdef':
                    $slaRequest = 'action_plan_mins';
                    $NextslaName = $GLOBALS['strActionPlan'];
                    break;
                case 'actionplan':
                    $slaRequest = 'resolution_days';
                    $NextslaName = $GLOBALS['strResolutionReprioritisation'];
                    $coefficient = ($CONFIG['end_working_day'] - $CONFIG['start_working_day']) / 60;
                    break;
                case 'solution':
                    $slaRequest = 'initial_response_mins';
                    $NextslaName = $GLOBALS['strInitialResponse'];
                    break;
            }

            // Query the database for the next SLA and review times...

            $sql = "SELECT ($slaRequest * $coefficient) as 'next_sla_time', review_days ";
            $sql .= "FROM `{$dbServiceLevels}` WHERE tag = '{$tag}' AND priority = '{$incident->priority}'";
            $result = mysql_query($sql);
            if (mysql_error())
            {
                trigger_error(mysql_error(), E_USER_WARNING);
                $success = FALSE;
            }
            $times = mysql_fetch_assoc($result);
            mysql_free_result($result);

            if ($CONFIG['debug'])
            {
                //debug_log("The next SLA target should be met in ".$times['next_sla_time']." minutes");
                //debug_log("Reviews need to be made every ".($times['review_days']*24*60)." minutes");
            }

            if ($incident->slanotice == 0)
            {
                //reaching SLA
                if ($times['next_sla_time'] > 0) $reach = $newSlaTime / $times['next_sla_time'];
                else $reach = 0;
                if ($reach >= ($CONFIG['urgent_threshold'] * 0.01))
                {
                    $timetil = $times['next_sla_time']-$newSlaTime;

                    $t = new TriggerEvent('TRIGGER_INCIDENT_NEARING_SLA', array('incidentid' => $incident->id,
                    'nextslatime' => $times->next_sla_time,
                    'nextsla' => $NextslaName));

                    $sql = "UPDATE `{$dbIncidents}` SET slanotice='1' WHERE id='{$incident->id}'";
                    mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                }
            }
        }
    }
    mysql_free_result($incident_result);

    return $success;
}


/**
 * Scheduler Action to automatically set Users status (away) based on data from the
 * holiday chart
 * @author Ivan Lucas
*/
function saction_SetUserStatus()
{
    global $dbHolidays, $dbUsers, $CONFIG;
    // Find users with holidays today who don't have correct status
    $success = TRUE;
    $startdate = mktime(0,0,0,date('m'),date('d'),date('Y'));
    $enddate = mktime(23,59,59,date('m'),date('d'),date('Y'));
    $sql = "SELECT * FROM `{$dbHolidays}` ";
    $sql .= "WHERE `date` >= FROM_UNIXTIME($startdate) AND `date` < ";
    $sql .= "FROM_UNIXTIME($enddate) AND (type >='".HOL_HOLIDAY."' AND type <= ".HOL_FREE.") ";
    $sql .= "AND (approved=" . HOL_APPROVAL_GRANTED . " OR approved=" . HOL_APPROVAL_GRANTED_ARCHIVED . ")";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        $success = FALSE;
        trigger_error(mysql_error(), E_USER_WARNING);
    }
    $numrows = mysql_num_rows($result);
    while ($huser = mysql_fetch_object($result))
    {
        if ($huser->length == 'day'
            OR ($huser->length == 'am' AND date('H') < 12)
            OR ($huser->length == 'pm' AND date('H') > 12))
        {
            $currentstatus = user_status($huser->userid);
            $newstatus = $currentstatus;
            // Only enabled users
            if ($currentstatus > 0)
            {
                if ($huser->type == HOL_HOLIDAY AND $currentstatus != USERSTATUS_ON_HOLIDAY)
                {
                    $newstatus = USERSTATUS_ON_HOLIDAY;
                }
                elseif ($huser->type == HOL_SICKNESS AND $currentstatus != USERSTATUS_ABSENT_SICK)
                {
                    $newstatus = USERSTATUS_ABSENT_SICK;
                }
                elseif ($huser->type == HOL_WORKING_AWAY AND
                       ($currentstatus != USERSTATUS_WORKING_FROM_HOME AND
                        $currentstatus != USERSTATUS_WORKING_AWAY))
                {
                    $newstatus = USERSTATUS_WORKING_AWAY;
                }
                elseif ($huser->type == HOL_TRAINING AND $currentstatus != USERSTATUS_ON_TRAINING_COURSE)
                {
                    $newstatus = USERSTATUS_ON_TRAINING_COURSE;
                }
                elseif ($huser->type == HOL_FREE AND
                        ($currentstatus != USERSTATUS_NOT_IN_OFFICE AND
                         $currentstatus != USERSTATUS_ABSENT_SICK))
                {
                    $newstatus = USERSTATUS_ABSENT_SICK; // Compassionate
                }
            }
            if ($newstatus != $currentstatus)
            {
                debug_log('saction_SetUserStatus changing users\' status based on holiday calendar '.user_realname($huser->userid).': '.userstatus_name($currentstatus).' -> '.userstatus_name($newstatus), TRUE);
                set_user_status($huser->userid, $newstatus);
            }
        }
    }

    return $success;
}


/**
 * Chase / Remind customers
 * @author Paul Heaney
 * @note Moved from htdocs/auto/chase_customer.php by INL for 3.40
 */
function saction_ChaseCustomers()
{
    global $CONFIG, $now, $sit;
    global $dbIncidents, $dbUpdates;
    $success = TRUE;

    /**
        * @author Paul Heaney
    */
    function not_auto_type($type)
    {
        if ($type != 'auto_chase_email' AND $type != 'auto_chase_phone' AND $type != 'auto_chase_manager')
        {
            return TRUE;
        }
        return FALSE;
    }

    if ($CONFIG['auto_chase'] == TRUE)
    {
        // if 'awaiting customer action' for more than $CONFIG['chase_email_minutes'] and NOT in an auto state, send auto email

        //$sql = "SELECT incidents.id, contacts.forenames,contacts.surname,contacts.id AS managerid FROM incidents,contacts WHERE status = ".STATUS_CUSTOMER." AND contacts.notify_contactid = contacts.id";
        $sql = "SELECT * FROM `{$dbIncidents}` AS i WHERE status = ".STATUS_CUSTOMER;

        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            $success = FALSE;
        }

        while ($obj = mysql_fetch_object($result))
        {
            if (!in_array($obj->maintenanceid, $CONFIG['dont_chase_maintids']))
            {
                // only annoy these people
                $sql_update = "SELECT * FROM `{$dbUpdates}` WHERE incidentid = {$obj->id} ORDER BY timestamp DESC LIMIT 1";
                $result_update = mysql_query($sql_update);
                if (mysql_error())
                {
                    trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                    $success = FALSE;
                }

                $obj_update = mysql_fetch_object($result_update);

                if ($CONFIG['chase_email_minutes'] != 0)
                {
                    //if (not_auto_type($obj_update->type) AND $obj_update->timestamp <= ($now-$CONFIG['chase_email_minutes']*60))
                    if (not_auto_type($obj_update->type) AND (($obj->timeofnextaction == 0 AND calculate_working_time($obj_update->timestamp, $now) >= $CONFIG['chase_email_minutes']) OR ($obj->timeofnextaction != 0 AND calculate_working_time($obj->timeofnextupdate, $now) >= $CONFIG['chase_email_minutes'])))
                    {
                        $paramarray = array('incidentid' => $obj->id, 'triggeruserid' => $sit[2]);
                        send_email_template($CONFIG['chase_email_template'], $paramarray);
                        $sql_insert = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp, customervisibility) VALUES ('{$obj_update->incidentid}','{$sit['2']}', 'auto_chase_email', '{$obj->owner}', '{$obj->status}', 'Sent auto chase email to customer','{$now}','show')";
                        mysql_query($sql_insert);
                        if (mysql_error())
                        {
                            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                            $success = FALSE;
                        }

                        $sql_update = "UPDATE `{$dbIncidents}` SET lastupdated = '{$now}', nextactiontime = 0 WHERE id = {$obj->id}";
                        mysql_query($sql_update);
                        if (mysql_error())
                        {
                            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                            $success = FALSE;
                        }
                    }
                }

                if ($CONFIG['chase_phone_minutes'] != 0)
                {
                    //if ($obj_update->type == 'auto_chase_email' AND $obj_update->timestamp <= ($now-$CONFIG['chase_phone_minutes']*60))
                    if ($obj_update->type == 'auto_chase_email' AND  (($obj->timeofnextaction == 0 AND calculate_working_time($obj_update->timestamp, $now) >= $CONFIG['chase_phone_minutes']) OR ($obj->timeofnextaction != 0 AND calculate_working_time($obj->timeofnextupdate, $now) >= $CONFIG['chase_phone_minutes'])))
                    {
                        $sql_insert = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp, customervisibility) VALUES ('{$obj_update->incidentid}','{$sit['2']}','auto_chase_phone', '{$obj->owner}', '{$obj->status}', 'Status: Awaiting Customer Action -&gt; <b>Active</b><hr>Please phone the customer to get an update on this call as {$CONFIG['chase_phone_minutes']} have passed since the auto chase email was sent. Once you have done this please use the update type \"Chased customer - phone\"','{$now}','hide')";
                        mysql_query($sql_insert);
                        if (mysql_error())
                        {
                            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                            $success = FALSE;
                        }

                        $sql_update = "UPDATE `{$dbIncidents}` SET lastupdated = '{$now}', ";
                        $sql_update .= "nextactiontime = 0, status = ".STATUS_ACTIVE." WHERE id = {$obj->id}";
                        mysql_query($sql_update);
                        if (mysql_error())
                        {
                            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                            $success = FALSE;
                        }
                    }
                }

                if ($CONFIG['chase_manager_minutes'] != 0)
                {
                    //if ($obj_update->type == 'auto_chased_phone' AND $obj_update->timestamp <= ($now-$CONFIG['chase_manager_minutes']*60))
                    if ($obj_update->type == 'auto_chased_phone' AND (($obj->timeofnextaction == 0 AND calculate_working_time($obj_update->timestamp, $now) >= $CONFIG['chase_manager_minutes']) OR ($obj->timeofnextaction != 0 AND calculate_working_time($obj->timeofnextupdate, $now) >= $CONFIG['chase_manager_minutes'])))
                    {
                        $update = "Status: Awaiting Customer Action -&gt; <b>Active</b><hr>";
                        $update .= "Please phone the customers MANAGER to get an update on this call as ".$CONFIG['chase_manager_minutes']." have passed since the auto chase email was sent.<br />";
                        $update .= "The manager is <a href='contact_details.php?id={$obj->managerid}'>{$obj->forenames} {$obj->surname}</a><br />";
                        $update .= " Once you have done this please email the actions to the customer and select the \"Was this a customer chase?\"'";

                        $sql_insert = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp, customervisibility) VALUES ('{$obj_update->incidentid}','{$sit['2']}','auto_chase_manager', '{$obj->owner}', '{$obj->status}', $update,'{$now}','hide')";
                        mysql_query($sql_insert);
                        if (mysql_error())
                        {
                            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                            $success = FALSE;
                        }

                        $sql_update = "UPDATE `{$dbIncidents}` SET lastupdated = '{$now}', nextactiontime = 0, status = ".STATUS_ACTIVE." WHERE id = {$obj->id}";
                        mysql_query($sql_update);
                        if (mysql_error())
                        {
                            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                            $success = FALSE;
                        }
                    }
                }

                if ($CONFIG['chase_managers_manager_minutes'] != 0)
                {
                    //if ($obj_update->type == 'auto_chased_manager' AND $obj_update->timestamp <= ($now-$CONFIG['chase_managers_manager_minutes']*60))
                    if ($obj_update->type == 'auto_chased_manager' AND (($obj->timeofnextaction == 0 AND calculate_working_time($obj_update->timestamp, $now) >= $CONFIG['chase_amanager_manager_minutes']) OR ($obj->timeofnextaction != 0 AND calculate_working_time($obj->timeofnextupdate, $now) >= $CONFIG['chase_amanager_manager_minutes'])))
                    {
                        $sql_insert = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp, customervisibility) VALUES ('{$obj_update->incidentid}','{$sit['2']}','auto_chase_managers_manager','{$obj->owner}', '{$obj->status}', 'Status: Awaiting Customer Action -&gt; <b>Active</b><hr>Please phone the customers managers manager to get an update on this call as {$CONFIG['chase_manager_minutes']} have passed since the auto chase email was sent. Once you have done this please email the actions to the customer and manager and select the \"Was this a manager chase?\"','{$now}','hide')";
                        mysql_query($sql_insert);
                        if (mysql_error())
                        {
                            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                            $success = FALSE;
                        }

                        $sql_update = "UPDATE `{$dbIncidents}` SET lastupdated = '{$now}', nextactiontime = 0, status = ".STATUS_ACTIVE." WHERE id = {$obj->id}";
                        mysql_query($sql_update);
                        if (mysql_error())
                        {
                            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                            $success = FALSE;
                        }
                    }
                }
            }
        }
    }
    return $success;
}


/**
 * Check the holding queue for waiting email
 * @author Ivan Lucas
 */
function saction_CheckWaitingEmail()
{
    global $dbTempIncoming, $dbUpdates, $dbScheduler, $now;
    $success = TRUE;

    $sql = "SELECT `timestamp`, UNIX_TIMESTAMP(NOW()) - `timestamp` AS minswaiting FROM `{$dbTempIncoming}` AS ti ";
    $sql .= "LEFT JOIN `{$dbUpdates}` AS u ON ti.updateid = u.id GROUP BY ti.id ";
    $sql .= "ORDER BY timestamp ASC LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("MySQL Query Error".mysql_error(), E_USER_WARNING);
        $success = FALSE;
    }
    elseif (mysql_num_rows($result) > 0)
    {
        list($timestamp, $minswaiting) = mysql_fetch_row($result);
        $sql = "SELECT `interval` FROM `{$dbScheduler}` ";
        $sql .= "WHERE action = 'CheckWaitingEmail'";
        $result = mysql_query($sql);
        list($interval) = mysql_fetch_row($result);
        // so we don't get a duplicate if we receive an email exactly at check time
        $checks = "{$timestamp} + ({notifymins} * 60) + {$interval} >= {$now}";
        new TriggerEvent("TRIGGER_WAITING_HELD_EMAIL",
                        array('holdingmins' => ceil($minswaiting / 60),
                              'checks' => $checks));
    }
    return $success;
}


// TODO PurgeAttachments
// Look for the review due trigger, where did it go


/**
 *
 * @author Paul Heaney
*/
function saction_MailPreviousMonthsTransactions()
{
    global $CONFIG;
    /*
     Get todays date
     Subtract one from the month and find last month
     Find the last day of last month
     fope(transactions.php?mode=csv&start=X&end=Y&breakdonw=yes
     mail to people

     TODO need a mechanism to subscribe to scheduled events? Could this be done with a trigger? Hmmhhhhhh

    */
    if ($CONFIG['outbound_email_newline'] == 'CRLF')
    {
        $crlf = "\r\n";
    }
    else
    {
        $crlf = "\n";
    }

    $currentmonth = date('m');
    $currentyear = date('y');
    if ($currentmonth == 1)
    {
        $currentyear--;
        $lastmonth = 12;
    }
    else
    {
        $lastmonth = $currentmonth - 1;
    }

    $startdate = "{$currentyear}-{$lastmonth}-01";
    // Find last date of previous month, 5 day an arbitary choice
    $lastday = date('t', strtotime('{$currentyear}-{$lastmonth}-05'));
    $enddate =  "{$currentyear}-{$lastmonth}-{$lastday}";

    $csv = transactions_report('', $startdate, $enddate, '', 'csv', TRUE);

    $extra_headers = "Reply-To: {$CONFIG['support_email']}{$crlf}Errors-To: {$CONFIG['support_email']}{$crlf}"; // TODO should probably be different
    $extra_headers .= "X-Mailer: {$CONFIG['application_shortname']} {$application_version_string}/PHP " . phpversion() . $crlf;
    $extra_headers .= "X-Originating-IP: " . substr($_SERVER['REMOTE_ADDR'], 0, 15) . $crlf;
//    if ($ccfield != '')  $extra_headers .= "cc: $ccfield\n";
//    if ($bccfield != '') $extra_headers .= "Bcc: $bccfield\n";

    $extra_headers .= $crlf; // add an extra crlf to create a null line to separate headers from body
                        // this appears to be required by some email clients - INL

    $subject = sprintf($GLOBALS['strBillableIncidentsForPeriodXtoX'], $startdate, $enddate);

    $bodytext = $GLOBALS['strAttachedIsBillableIncidentsForAbovePeriod'];

    $mime = new MIME_mail($CONFIG['support_email'], $CONFIG['billing_reports_email'], html_entity_decode($subject), $bodytext, $extra_headers, '');
    $mime->attach($csv, "Billable report", OCTET, BASE64, "filename=billable_incidents_{$lastmonth}_{$currentyear}.csv");
    return $mime->send_mail();
}


function saction_CheckIncomingMail()
{
    global $CONFIG;
    if ($CONFIG['enable_inbound_mail'] == 'POP/IMAP')
    {
        include ( APPLICATION_FSPATH . 'inboundemail.php');
    }
    return TRUE;
}


function saction_CheckTasksDue()
{
    $rtn = TRUE;

    $sql = "SELECT `interval` FROM {$GLOBALS['dbScheduler']} ";
    $sql .= "WHERE `s.action`='CheckTasksDue'";
    if ($result = mysql_query($sql))
    {
        $intervalobj = mysql_fetch_object($result);

        // check the tasks due between now and in N minutes time,
        // where N is the time this action is run
        $format = "Y-m-d H:i:s";
        $startdue = date($format, $GLOBALS['now']);
        $enddue =  date($format, $GLOBALS['now'] + $intervalobj->interval);
        $sql = "SELECT * FROM {$GLOBALS['dbTasks']} ";
        $sql .= "WHERE duedate > {$startdue} AND duedate < {$enddue} ";
        if ($result = mysql_query($sql))
        {
            while ($row = mysql_fetch_object($result))
            {
                $t = new triggerEvent('TRIGGER_TASK_DUE', array('taskid' => $row->id));
            }
        }
    }
    return $rtn;
}


/**
 * Perform the periodic sync of users and contacts from LDAP
 * Perform the periodic sync of existing user and contact details from LDAP
 * @author Paul Heaney
 * @note This function does not create users or contacts it simply updates existing
 * @note details.
*/
function saction_ldapSync()
{
    global $CONFIG;
    $success = FALSE;

    if ($CONFIG['use_ldap'])
    {
        $ldap_conn = ldapOpen();

        if ($ldap_conn != -1)
        {
            // Search for members of each group and then unique the members and loop through
            // Populate an array ($users) with a list of SIT users in LDAP

            // Only want GROUPS
            $filter = "(objectClass={$CONFIG['ldap_grpobjecttype']})";
            $attributesToGet = array($CONFIG['ldap_grpattributegrp']);

            $users = array();

            $userGrps = array($CONFIG['ldap_admin_group'], $CONFIG['ldap_manager_group'], $CONFIG['ldap_user_group'] );

            foreach ($userGrps AS $grp)
            {
                if (!empty($grp))
                {
                    $sr = ldap_search($ldap_conn, $grp, $filter, $attributesToGet);
                    if (ldap_count_entries($ldap_conn, $sr) != 1)
                    {
                        trigger_error ("Group {$grp} not found in LDAP");
                    }
                    else
                    {
                        $entry = ldap_first_entry($ldap_conn, $sr);
                        $attributes = ldap_get_attributes($ldap_conn, $entry);

                        for ($i = 0; $i < $attributes[$CONFIG['ldap_grpattributegrp']]['count']; $i++)
                        {
                            $member = $attributes[$CONFIG['ldap_grpattributegrp']][$i];
                            if (endsWith(strtolower($member), strtolower($CONFIG['ldap_user_base'])) AND $CONFIG['ldap_grpfulldn'])
                            {
                                $users[$member] = $member;
                            }
                            elseif (!$CONFIG['ldap_grpfulldn'])
                            {
                                $users[$member] = $member;
                            }
                        }
                    }
                }
            }

            // Populate an array with the LDAP users already in the SiT database
            $sit_db_users = array();
            $sql = "SELECT id, username, status FROM `{$GLOBALS['dbUsers']}` WHERE user_source = 'ldap'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error".mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($result) > 0)
            {
                while ($obj = mysql_fetch_object($result))
                {
                    $user_obj = new User();
                    $user_obj->id = $obj->id;
                    $user_obj->username = $obj->username;
                    $user_obj->status = $obj->status;
                    $sit_db_users[$obj->username] = $user_obj;
                }
            }

            foreach ($users AS $u)
            {
                $e = ldap_getDetails($u, FALSE, $ldap_conn);

                if ($e)
                {
                    $user_attributes = ldap_get_attributes($ldap_conn, $e);
                    debug_log("user attributes: ".print_r($user_attributes, true), TRUE);
                    debug_log("db users: ".print_r($sit_db_users, true), TRUE);

                    // If the directory supports disabling of users
                    if (!empty($CONFIG['ldap_logindisabledattribute']))
                    {
                        if ($sit_db_users[$user_attributes[$CONFIG['ldap_userattribute']][0]]->status === USERSTATUS_ACCOUNT_DISABLED)
                        {
                            // User is disabled in the SIT db, check to see if we need to re-enable
                            if (!empty($user_attributes[$CONFIG['ldap_logindisabledattribute']]))
                            {
                                if (ldap_is_account_disabled($user_attributes[$CONFIG['ldap_logindisabledattribute']][0]))
                                {
                                    // The user is enabled in LDAP so we want to enable
                                    debug_log("Re-enabling user '{$u}' in the SiT users database", TRUE);
                                    $sit_db_users[$user_attributes[$CONFIG['ldap_userattribute']][0]]->status = $CONFIG['ldap_default_user_status'];
                                    $sit_db_users[$user_attributes[$CONFIG['ldap_userattribute']][0]]->edit();
                                }
                            }
                        }
                        else
                        {
                            // User is not disabled in the SiT database, check to see if we need to disable
                            if (ldap_is_account_disabled($user_attributes[$CONFIG['ldap_logindisabledattribute']][0]))
                            {
                                // User is disabled in LDAP so we want to disable
                                if (is_object($sit_db_users[$user_attributes[$CONFIG['ldap_userattribute']][0]]))
                                {
                                    $sit_db_users[$user_attributes[$CONFIG['ldap_userattribute']][0]]->disable();
                                }
                            }
                        }
                    }

                    $userid = 0;
                    if (!empty($sit_db_users[$user_attributes[$CONFIG['ldap_userattribute']][0]]))
                    {
                        $userid = $sit_db_users[$user_attributes[$CONFIG['ldap_userattribute']][0]]->id;
                        unset ($sit_db_users[$user_attributes[$CONFIG['ldap_userattribute']][0]]);
                    }

                    if (!ldap_storeDetails('', $userid, TRUE, TRUE, $ldap_conn, $user_attributes))
                    {
                        debug_log("Failed to store details for userid {$userid}");
                        $success = FALSE;
                    }
                    else
                    {
                        $success = TRUE;
                    }
                }
                else
                {
                    debug_log ("Failed to get details for user: {$u}");
                }
            }

            // Disable users we no longer know about
            // TODO reassign incidents?
            foreach ($sit_db_users AS $u)
            {
                debug_log ("Disabling User: {$u->username}");
                $u->disable();
            }

            /* CONTACTS */

            $contacts = array();
            if (!empty($CONFIG["ldap_customer_group"]))
            {
                debug_log ("CONTACTS");
                $sr = ldap_search($ldap_conn, $CONFIG["ldap_customer_group"], $filter, $attributesToGet);
                if (ldap_count_entries($ldap_conn, $sr) != 1)
                {
                    trigger_error ("No contact group found in LDAP");
                }
                else
                {
                    $entry = ldap_first_entry($ldap_conn, $sr);
                    $attributes = ldap_get_attributes($ldap_conn, $entry);
                    for ($i = 0; $i < $attributes[$CONFIG['ldap_grpattributegrp']]['count']; $i++)
                    {
                        $member = $attributes[$CONFIG['ldap_grpattributegrp']][$i];
                        if (endsWith(strtolower($member), strtolower($CONFIG['ldap_user_base'])) AND $CONFIG['ldap_grpfulldn'])
                        {
                            $contacts[$member] = $member;
                        }
                        elseif (!$CONFIG['ldap_grpfulldn'])
                        {
                            $contacts[$member] = $member;
                        }
                    }
                }

                $sit_db_contacts = array();
                $sql = "SELECT id, username, active FROM `{$GLOBALS['dbContacts']}` WHERE contact_source = 'ldap'";
                $result = mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error".mysql_error(), E_USER_WARNING);
                if (mysql_num_rows($result) > 0)
                {
                    while ($obj = mysql_fetch_object($result))
                    {
                        $c = new Contact();
                        $c->id = $obj->id;
                        $c->username = $obj->username;
                        $c->status = $obj->active;
                        $sit_db_contacts[$c->username] = $c;
                    }
                }

                foreach ($contacts AS $c)
                {
                    $e = ldap_getDetails($c, FALSE, $ldap_conn);
                    if ($e)
                    {
                        $contact_attributes = ldap_get_attributes($ldap_conn, $e);

                        if (isset($CONFIG['ldap_logindisabledattribute']))
                        {
                            // Directory supports disabling
                            if ($sit_db_contacts[$contact_attributes[$CONFIG['ldap_userattribute']][0]]->status == 'false')
                            {
                                // User disabled in SIT check if needs renameding
                                if (!empty($contact_attributes[$CONFIG['ldap_logindisabledattribute']]))
                                {
                                    if (!ldap_is_account_disabled($contact_attributes[$CONFIG['ldap_logindisabledattribute']][0]))
                                    {
                                        // We want to enable
                                        $sit_db_contacts[$contact_attributes[$CONFIG['ldap_userattribute']][0]]->active = 'true';
                                        $sit_db_contacts[$contact_attributes[$CONFIG['ldap_userattribute']][0]]->edit();
                                    }
                                }
                            }
                            elseif (!empty($contact_attributes[$CONFIG['ldap_logindisabledattribute']]))
                            {
                                // User not disabled in SiT though attribite is available to us
                                if (ldap_is_account_disabled($contact_attributes[$CONFIG['ldap_logindisabledattribute']][0]))
                                {
                                    // We want to disable
                                    if (is_object($sit_db_contacts[$contact_attributes[$CONFIG['ldap_userattribute']][0]]))
                                    {
                                        $sit_db_contacts[$contact_attributes[$CONFIG['ldap_userattribute']][0]]->disable();
                                    }
                                }
                            }
                        }

                        $contactid = 0;
                        if (!empty($sit_db_contacts[$contact_attributes[$CONFIG['ldap_userattribute']][0]]))
                        {
                            $contactid = $sit_db_contacts[$contact_attributes[$CONFIG['ldap_userattribute']][0]]->id;
                            unset ($sit_db_contacts[$contact_attributes[$CONFIG['ldap_userattribute']][0]]);
                        }

                        if (!ldap_storeDetails('', $contactid, FALSE, TRUE, $ldap_conn, $contact_attributes))
                        {
                            debug_log("Failed to store details for contactid {$contactid}");
                            $success = FALSE;
                        }
                        else
                        {
                            $success = TRUE;
                        }
                    }
                }

                // Disable users we no longer know about
                // TODO reassign incidents?
                foreach ($sit_db_contacts AS $c)
                {
                    if ($c->status != 'false')
                    {
                        // Only disable if not already disabled
                        debug_log ("Disabling Contact: {$c->username}", TRUE);
                        $c->disable();
                    }
                }
            }
        }
        else
        {
            trigger_error("Unable to connect to LDAP", E_USER_ERROR);
        }
    }
    else
    {
        $success = TRUE;
    }
    return $success;
}


?>
