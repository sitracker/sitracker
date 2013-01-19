<?php
// inboundemail.php - Process incoming emails
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Note: if performance is poor with attachments, we should download attachments
//       with the function in mail.class.php
// Note2: to be called from auto.php

require_once ('core.php');
require_once (APPLICATION_LIBPATH . 'functions.inc.php');
require_once (APPLICATION_LIBPATH . 'triggers.inc.php');
require_once (APPLICATION_LIBPATH . 'trigger.class.php');
require (APPLICATION_LIBPATH . 'mime_parser.inc.php');
require (APPLICATION_LIBPATH . 'rfc822_addresses.inc.php');
require (APPLICATION_LIBPATH . 'mailbox.class.php');

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    include (APPLICATION_LIBPATH . 'strings.inc.php');
    require_once (APPLICATION_LIBPATH . 'base.inc.php');
}
else
{
    global $CONFIG, $dbFiles, $dbUpdates, $dbTempIncoming, $dbIncidents, $now;
    global $subject, $decoded, $incidentid, $contactid;
}

//hack as we have no session
/**
 * Populates $_SESSION['syslang], system language strings
 *
 * @author Kieran Hogg
 * @sa See also populate_syslang() which is the original version of this function
*/
function populate_syslang2()
{
    global $CONFIG;

    // Populate $SYSLANG with first the native lang and then the system lang
    // This is so that we have a complete language file
    $nativefile = APPLICATION_I18NPATH . "en-GB.inc.php";
    $file = clean_fspath(APPLICATION_I18NPATH . "{$CONFIG['default_i18n']}.inc.php");

    if (file_exists($nativefile))
    {
        $fh = fopen($nativefile, "r");

        $theData = fread($fh, filesize($nativefile));
        fclose($fh);
        $nativelines = explode("\n", $theData);

        if (file_exists($file))
        {
            $fh = fopen($file, "r");
            $theData = fread($fh, filesize($file));
            fclose($fh);
            $lines = explode("\n", $theData);
        }
        else
        {
            trigger_error("Language file specified in \$CONFIG['default_i18n'] can't be found", E_USER_ERROR);
            $lines = $nativelines;
        }

       foreach ($nativelines as $values)
        {
            $badchars = array("$", "\"", "\\", "<?php", "?>");
            $values = trim(str_replace($badchars, '', $values));
            if (mb_substr($values, 0, 3) == "str")
            {
                $vars = explode("=", $values);
                $vars[0] = trim($vars[0]);
                $vars[1] = trim(substr_replace($vars[1], "",-2));
                $vars[1] = substr_replace($vars[1], "",0, 1);
                $SYSLANG[$vars[0]] = $vars[1];
            }
        }
        foreach ($lines as $values)
        {
            $badchars = array("$", "\"", "\\", "<?php", "?>");
            $values = trim(str_replace($badchars, '', $values));
            if (mb_substr($values, 0, 3) == "str")
            {
                $vars = explode("=", $values);
                $vars[0] = trim($vars[0]);
                $vars[1] = trim(substr_replace($vars[1], "",-2));
                $vars[1] = substr_replace($vars[1], "",0, 1);
                $SYSLANG[$vars[0]] = $vars[1];
            }
        }

        $_SESSION['syslang'] = $SYSLANG;
    }
    else
    {
        trigger_error("Native language file 'en-GB' can't be found", E_USER_ERROR);
    }
}

$SYSLANG = populate_syslang2();

if ($CONFIG['enable_inbound_mail'] == 'MTA')
{
    // read the email from stdin (it should be piped to us by the MTA)
    $fp = fopen("php://stdin", "r");
    $rawemail = '';
    while (!feof($fp))
    {
        $rawemail .= fgets($fp); // , 1024
    }
    fclose($fp);
    $emails = 1;
}
elseif ($CONFIG['enable_inbound_mail'] == 'POP/IMAP')
{
    $mailbox = new Mailbox($CONFIG['email_username'], $CONFIG['email_password'],
                           $CONFIG['email_address'], $CONFIG['email_server'],
                           $CONFIG['email_servertype'], $CONFIG['email_port'],
                           $CONFIG['email_options']);


    if (!$mailbox->connect())
    {
        if ($CONFIG['debug'])
        {
            echo "Connection error, see debug log for details.\n";
        }
       return FALSE;
    }

    $emails = $mailbox->getNumUnreadEmails();
//     $size = $mailbox->getTotalSize($emails);
}
else
{
    return FALSE;
}

if ($emails > 0)
{
    if ($CONFIG['debug'])
    {
        debug_log("Found {$emails} email(s) to fetch, Archive folder set to: '{$CONFIG['email_archive_folder']}'\n");
    }
    for ($i = 0; $i < $emails; $i++)
    {
        if ($CONFIG['enable_inbound_mail'] == 'POP/IMAP')
        {
            $rawemail = $mailbox->getMessageHeader($i + 1);
            $rawemail .= "\n".$mailbox->messageBody($i + 1);

            if ($mailbox->servertype == 'imap')
            {
                if (!empty($CONFIG['email_archive_folder']))
                {
                    if ($CONFIG['debug'])
                    {
                        debug_log("Archiving email");
                    }
                    $mailbox->archiveEmail($i + 1) OR debug_log("Archiving email ".($i + 1)." failed: ".imap_last_error());
                }
                else
                {
                    $mailbox->deleteEmail($i + 1) OR debug_log("Deleting email ".($i + 1)." failed: ".imap_last_error());
                }
            }
        }

        $mime = new mime_parser_class();
        $mime->mbox = 0;
        $mime->decode_headers = 1;
        $mime->decode_bodies = 1;
        $mime->ignore_syntax_errors = 1;

        $parameters = array('Data'=>$rawemail);

        $mime->Decode($parameters, $decoded);
        $mime->Analyze($decoded[0], $results);
        $to = $cc = $from = $from_name = $from_email = "";

        if ($CONFIG['debug'])
        {
            debug_log("Message $i Email Type: '{$results['Type']}', Encoding: '{$results['Encoding']}'");
            debug_log('DECODED: '.print_r($decoded, true));
            //debug_log('RESULTS: '.print_r($results, true));
        }

        // Attempt to recognise contact from the email address
        $from_email = strtolower($decoded[0]['ExtractedAddresses']['from:'][0]['address']);
        // Work-around for a problem where email addresses with extra characters (such as apostophe) stop the email address being extracted
        if (empty($from_email) AND !empty($decoded[0]['Headers']['from:']))
        {
            $parsed_from = imap_rfc822_parse_adrlist($decoded[0]['Headers']['from:'], 'example.com');
            $from_email = strtolower($parsed_from[0]->mailbox . '@' . $parsed_from[0]->host);
        }
        $sql = "SELECT id FROM `{$GLOBALS['dbContacts']}` ";
        $sql .= "WHERE email = '".mysql_real_escape_string($from_email)."'";
        if ($result = mysql_query($sql))
        {
            if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
            $row = mysql_fetch_object($result);
            $contactid = $row->id;
        }
        debug_log($sql);

        $from_name = $decoded[0]['ExtractedAddresses']['from:'][0]['name'];
        // Convert the from encoding to UTF-8 if it isn't already
        if (!empty($decoded[0]['ExtractedAddresses']['from:'][0]['encoding'])
            AND strcasecmp('UTF-8', $decoded[0]['ExtractedAddresses']['from:'][0]['encoding']) !== 0)
        {
            $from_name = mb_convert_encoding($from_name, "UTF-8", strtoupper($decoded[0]['ExtractedAddresses']['from:'][0]['encoding']));
            if ($CONFIG['debug']) debug_log("Converted 'from header' encoding from {$decoded[0]['ExtractedAddresses']['from:'][0]['encoding']} to UTF-8");
        }

        if (!empty($from_name))
        {
            $from =  $from_name . " <". $from_email . ">";
        }
        else
        {
            $from = $from_email;
        }

        $subject = $results['Subject'];
        if (!empty($results['SubjectEncoding']) AND strcasecmp('UTF-8', $results['SubjectEncoding']) !== 0)
        {
            $subject = mb_convert_encoding($subject, "UTF-8", strtoupper($results['SubjectEncoding']));
            if ($CONFIG['debug']) debug_log("Converted subject encoding from {$results['SubjectEncoding']} to UTF-8");
        }

        $date = $results['Date'];

        if (is_array($decoded[0]['ExtractedAddresses']['to:']))
        {
            $num = sizeof($decoded[0]['ExtractedAddresses']['to:']);
            $cur = 1;
            foreach ($decoded[0]['ExtractedAddresses']['to:'] as $var)
            {
                if (!empty($var['name']))
                {
                    if (!empty($var['encoding']) AND strcasecmp('UTF-8', $var['encoding']) !== 0)
                    {
                        $var['name'] = mb_convert_encoding($var['name'], "UTF-8", strtoupper($var['encoding']));
                    }
                    $to .= $var['name']. " <".$var['address'].">";
                }
                else
                {
                    $to .= $var['address'];
                }
                if ($cur != $num) $to .= ", ";
                $cur++;
            }
        }

        if (is_array($decoded[0]['ExtractedAddresses']['cc:']))
        {
            $num = sizeof($decoded[0]['ExtractedAddresses']['cc:']);
            $cur = 1;
            foreach ($decoded[0]['ExtractedAddresses']['cc:'] as $var)
            {
                if (!empty($var['name']))
                {
                    if (!empty($var['encoding']) AND strcasecmp('UTF-8', $var['encoding']) !== 0)
                    {
                        $var['name'] = mb_convert_encoding($var['name'], "UTF-8", strtoupper($var['encoding']));
                    }
                    $cc .= $var['name']. " <".$var['address'].">";
                }
                else
                {
                    $cc .= $var['address'];
                }
                if ($cur != $num) $cc .= ", ";
                $cur++;
            }
        }


        switch ($results['Type'])
        {
            case 'html':
                if (!empty($results['Alternative'][0]['Data']))
                {
                    $message = $results['Alternative'][0]['Data'];
                }
                else
                {
                    $message = strip_tags(html_entity_decode($results['Data'], ENT_QUOTES, 'UTF-8'));
                }
                break;

            case 'text':
                $message = $results['Data'];
                break;

            default:
                break;
        }

        // Extract Incident ID
        if ($CONFIG['support_email_tags'] === TRUE AND preg_match('/?:[a-z][a-z]+.*?(\\d+)@?:[a-z][a-z\\.\\d\\-]+)\\.(?:[a-z][a-z\\-]+))(?![\\w\\./', $to, $m))
        {
            if (FALSE !== incident_status($m[1]))
            {
                $incidentid = $m[1];
                debug_log("Incident ID found in email TO address tag: '{$incidentid}'");
            }
        }
        elseif ($incidentid = incident_id_from_subject($subject, $from))
        {
            if (FALSE !== incident_status($incidentid))
            {
                debug_log("Incident ID found in email subject: '{$incidentid}'");
            }
        }
        else
        {
            debug_log("Incident ID not found in email subject: '{$subject}'");
        }

        plugin_do('email_arrived');

        $incident_open = (incident_status($incidentid) != STATUS_CLOSED AND incident_status($incidentid) != STATUS_CLOSING);

        $customer_visible = 'No';
        $part = 1;
        //process attachments
        if (!empty($incidentid) AND $incident_open)
        {
            $fa_dir = $CONFIG['attachment_fspath'] . $incidentid . DIRECTORY_SEPARATOR;
        }
        else
        {
            $fa_dir = $CONFIG['attachment_fspath'] . "updates" . DIRECTORY_SEPARATOR;
        }

        if (!file_exists($fa_dir))
        {
            if (!mkdir($fa_dir, 0775, TRUE)) trigger_error("Failed to create incident update attachment directory $fa_dir", E_USER_WARNING);
        }
        $attachments = array();
        if (is_array($results['Attachments']) OR is_array($results['Related']))
        {
            if (!is_array($results['Attachments']) AND is_array($results['Related']))
            {
                // Treat related content as attachment
                $results['Attachments'] = $results['Related'];
            }
            elseif(is_array($results['Attachments']) AND is_array($results['Related']))
            {
                // Treat related content as attachment
                $results['Attachments'] = array_merge($results['Attachments'], $results['Related']);
            }
            foreach ($results['Attachments'] as $attachment)
            {
                $data = $attachment['Data'];
                echo "{$attachment['FileName']}\n";
                $filename = $attachment['FileName'];
                if (mb_detect_encoding($filename) != 'UTF-8')
                {
                    $filename = utf8_encode($filename);
                }
                $filename = str_replace(' ', '_', $filename);
                $filename = clean_fspath($filename);

                if (empty($filename))
                {
                    // If it was a forwarded email
                    if ($attachment['Type'] == 'message')
                    {
                        $mime_att = new mime_parser_class();
                        $mime_att->mbox = 0;
                        $mime_att->decode_headers = 1;
                        $mime_att->decode_bodies = 1;
                        $mime_att->ignore_syntax_errors = 1;
                        
                        $parameters_att = array('Data'=>$attachment['Data']);

                        // We can't call Analyse as it would overwrite the existing email in memory
                        $mime_att->Decode($parameters_att, $decoded_att);
                        
                        if (strlen($decoded_att[0]['Headers']['subject:']) > 0)
                        {
                            $filename = utf8_encode(mb_decode_mimeheader($decoded_att[0]['Headers']['subject:'])) . '.eml';
                            $filename = str_replace(' ', '_', $filename);
                            $filename = clean_fspath($filename);
                        }
                    }
                    
                    // If its still empty - we may have set it above
                    if (empty($filename))
                    {
                        $filename = 'part'.$part;
                        if ($attachment['SubType'] == 'jpeg') $filename .= '.jpeg';
                        if ($attachment['Type'] == 'message') $filename .= '.eml';
                        $part++;
                    }

                }
                $filesize = mb_strlen($data);
                $sql = "INSERT into `{$GLOBALS['dbFiles']}` ";
                $sql .= "( `id` ,`category` ,`filename` ,`size` ,`userid` ,`usertype` ,`shortdescription` ,`longdescription` ,`webcategory` ,`path` ,`downloads` ,`filedate` ,`expiry` ,`fileversion` ,`published` ,`createdby` ,`modified` ,`modifiedby` ) ";
                $sql .= "VALUES('', 'private', '{$filename}', $filesize, '0', '', '', '', '', '', '', NOW(), NULL, '', 'no', '0', '', NULL)";
                echo $sql;
                mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
                $fileid = mysql_insert_id();
                $attachments[] = array('filename' => $filename, 'fileid' => $fileid);
                $filename = $fileid."-".$filename;

                if (is_writable($fa_dir))
                {
                    if ($CONFIG['debug']) debug_log("Writing attachment to disk: {$fa_dir}{$fileid}");
                    $fwp = fopen(clean_fspath($fa_dir . $fileid), 'a');
                    fwrite($fwp, $data);
                    fclose($fwp);
                }
                else
                {
                    debug_log("Attachment dir '{$fa_dir}' not writable");
                }
                $sql = "INSERT INTO `{$GLOBALS['dbLinks']}` (`linktype`, `origcolref`, `linkcolref`, `direction`, `userid`) ";
                $sql .= "VALUES('5', '{$updateid}', '{$fileid}', 'left', '0') ";
                mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            }
        }

        // Convert the message encoding to UTF-8 if it isn't already
        if (!empty($results['Encoding']) AND strcasecmp('UTF-8', $results['Encoding']) !== 0)
        {
            $message = mb_convert_encoding($message, "UTF-8", strtoupper($results['Encoding']));
            if ($CONFIG['debug']) debug_log("Converted message encoding from {$results['Encoding']} to UTF-8");
        }

        //** BEGIN UPDATE INCIDENT **//
        $headertext = '';
        // Build up header text to append to the incident log
        if (!empty($from))
        {
            $headertext = "From: [b]".htmlspecialchars(mysql_real_escape_string($from), ENT_NOQUOTES)."[/b]\n";
        }

        if (!empty($to))
        {
            $headertext .= "To: [b]".htmlspecialchars(mysql_real_escape_string($to), ENT_NOQUOTES)."[/b]\n";
        }

        if (!empty($cc))
        {
            $headertext .= "CC: [b]".htmlspecialchars(mysql_real_escape_string($cc), ENT_NOQUOTES)."[/b]\n";
        }

        if (!empty($subject))
        {
            $headertext .= "Subject: [b]".htmlspecialchars(mysql_real_escape_string($subject))."[/b]\n";
        }

        $count_attachments = count($attachments);
        if ($count_attachments >= 1)
        {
            $headertext .= $SYSLANG['strAttachments'].": [b]{$count_attachments}[/b] - ";
            $c = 1;
            foreach ($attachments AS $att)
            {
                $headertext .= "[[att={$att['fileid']}]]".htmlspecialchars(mysql_real_escape_string($att['filename']))."[[/att]]";
                if ($c < $count_attachments) $headertext .= ", ";
                $c++;
            }
            $headertext .= "\n";
        }
        //** END UPDATE INCIDENT **//

        //** BEGIN UPDATE **//
        $bodytext = $headertext . "<hr>" . htmlspecialchars(mysql_real_escape_string($message), ENT_NOQUOTES);

        // Strip excessive line breaks
        $message = str_replace("\n\n\n\n","\n", $message);
        $message = str_replace(">\n>\n>\n>\n",">\n", $message);

        if (empty($incidentid))
        {
            // Add entry to the incident update log
            $owner = incident_owner($incidentid);
            $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, bodytext, timestamp, customervisibility, currentowner, currentstatus) ";
            $sql .= "VALUES ('{$incidentid}', 0, 'emailin', '{$bodytext}', '{$now}', '{$customer_visible}', '{$owner}', 1 )";
            mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            $updateid = mysql_insert_id();

            $incidentid = plugin_do('email_arrived_no_owner', array('updateid' => $updateid, 'subject' => $subject));
            
            if (empty($incidentid))
            {
                //new call TODO: We need to find a better solution here for letting plugins change the reason
                if (!$GLOBALS['plugin_reason']) $reason = $SYSLANG['strPossibleNewIncident'];
                else $reason = $GLOBALS['plugin_reason'];
                $sql = "INSERT INTO `{$dbTempIncoming}` (`arrived`, `updateid`, `incidentid`, `from`, `emailfrom`, `subject`, `reason`, `contactid`) ";
                $sql.= "VALUES (FROM_UNIXTIME({$now}), '{$updateid}', '0', '".mysql_real_escape_string($from_email)."', ";
                $sql .= "'".mysql_real_escape_string($from_name)."', ";
                $sql .= "'".mysql_real_escape_string($subject)."', ";
                $sql .= "'{$reason}', '{$contactid}' )";
                mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                $holdingemailid = mysql_insert_id();
    
                $t = new TriggerEvent('TRIGGER_NEW_HELD_EMAIL', array('holdingemailid' => $holdingemailid));
            }

        }
        else
        {
            if (!$incident_open) // Do not translate/i18n fixed string
            {
                //Dont want to associate with a closed call
                $oldincidentid = $incidentid;
                $incidentid = 0;
            }

            //this prevents duplicate emails
            $error = 0;
            $fifteenminsago = $now - 900;
            $sql = "SELECT bodytext FROM `{$dbUpdates}` ";
            $sql .= "WHERE incidentid = '{$incidentid}' AND timestamp > '{$fifteenminsago}' ";
            $sql .= "ORDER BY id DESC LIMIT 1";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

            if (mysql_num_rows($result) > 0)
            {
                list($lastupdate) = mysql_fetch_row($result);

                $newtext = "{$headertext}<hr>{$message}";
                if (strcmp(trim($lastupdate),trim($newtext)) == 0)
                {
                    $error = 1;
                }
            }

            $owner = incident_owner($incidentid);
            if ($error != 1)
            {
                // Existing incident, new update:
                // Add entry to the incident update log
                $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, bodytext, timestamp, customervisibility, currentowner, currentstatus) ";
                $sql .= "VALUES ('{$incidentid}', 0, 'emailin', '{$bodytext}', '{$now}', '{$customer_visible}', '{$owner}', 1 )";
                mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                $updateid = mysql_insert_id();
                plugin_do('inboundemail_customer_visibility_update', array('updateid' => $updateid, 'incidentid' => $incidentid, 'visible' => $customer_visible, 'contactid' => $contactid));

                if ($incident_open) // Do not translate/i18n fixed string
                {
                    // Mark the incident as active
                    $sql = "UPDATE `{$GLOBALS['dbIncidents']}` SET status='1', lastupdated='".time()."', timeofnextaction='0' ";
                    $sql .= "WHERE id='{$incidentid}'";
                    mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                }
                else
                {
                    //create record in tempincoming
                    if (!$incident_open) // Do not translate/i18n fixed string
                    {
                        //incident closed
                        $reason = sprintf($SYSLANG['strIncidentXIsClosed'], $oldincidentid);
                        $sql = "INSERT INTO `{$dbTempIncoming}` (updateid, incidentid, `from`, emailfrom, subject, reason, reason_id, incident_id, contactid) ";
                        $sql .= "VALUES ('{$updateid}', '0', '".mysql_real_escape_string($from_email);
                        $sql .= "', '".mysql_real_escape_string($from_name);
                        $sql .= "', '".mysql_real_escape_string($subject)."', '{$reason}', ".REASON_INCIDENT_CLOSED.", '{$oldincidentid}', '$contactid' )";
                        mysql_query($sql);
                        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                    }
                    else
                    {
                        //new call TODO: We need to find a better solution here for letting plugins change the reason
                        if (!$GLOBALS['plugin_reason']) $reason = $SYSLANG['strPossibleNewIncident'];
                        else $reason = $GLOBALS['plugin_reason'];
                        $sql = "INSERT INTO `{$dbTempIncoming}` (updateid, incidentid, `from`, emailfrom, subject, reason, contactid) ";
                        $sql .= "VALUES ('{$updateid}', '0', '".mysql_real_escape_string($from_email)."',";
                        $sql .= "'".mysql_real_escape_string($from_name)."', '".mysql_real_escape_string($subject);
                        $sql .= "', '{$reason}', '{$contactid}' )";
                        mysql_query($sql);
                        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                    }
                    $holdingemailid = mysql_insert_id();
                }
                //Fix for http://bugs.sitracker.org/view.php?id=572, we shouldn't really have
                //incident ID of 0 here, but apparently we do :/
                if (FALSE !== incident_status($incidentid))
                {
                    $t = new TriggerEvent('TRIGGER_INCIDENT_UPDATED_EXTERNAL', array('incidentid' => $incidentid));
                }
            }
            else
            {
                if ($incidentid != 0)
                {
                    $bodytext = "[i]Received duplicate email within 15 minutes. Message not stored. Possible mail loop.[/i]";
                    $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, bodytext, timestamp, customervisibility, currentowner, currentstatus) ";
                    $sql .= "VALUES ('{$incidentid}', 0, 'emailin', '{$bodytext}', '{$now}', '{$customer_visible}', '{$owner}', 1)";
                    mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                }
            }
        }

        //** END UPDATE **//

        // Create a link between the files and the update
        // We need to update the links table here as otherwise we have a blank
        // updateid.
        // We check first that we have an updateid so that we don't fail here
        // if something else failed above.
        if ($updateid > 0)
        {
            foreach ($attachments AS $att)
            {
                $sql = "UPDATE `{$GLOBALS['dbLinks']}` SET origcolref = '{$updateid}' ";
                $sql .= "WHERE linkcolref = '{$att['fileid']}' ";
                $sql .= "AND linktype = 5 ";
                mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error() , E_USER_WARNING);
                debug_log("Creating a link between $updateid and file {$att['fileid']}");
            }
        }
        unset($headertext, $newupdate, $attachments, $attachment, $updateobj,
            $bodytext, $message, $incidentid);
    }

    if ($CONFIG['enable_inbound_mail'] == 'POP/IMAP')
    {
        // Delete the message from the mailbox
        if ($mailbox->servertype == 'imap')
        {
            imap_expunge($mailbox->mailbox) OR debug_log("Expunging failed: ".imap_last_error());
        }
        elseif ($mailbox->servertype == 'pop')
        {
            imap_delete($mailbox->mailbox, '1:*');
            imap_expunge($mailbox->mailbox);
        }

        imap_close($mailbox->mailbox);
    }
}

?>