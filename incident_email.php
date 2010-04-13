<?php
// incident_email.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


$permission = 33; // Send Emails
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// include ('mime.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$step = cleanvar($_REQUEST['step']);
$id = cleanvar($_REQUEST['id']);
$menu = cleanvar($_REQUEST['menu']);
$incidentid = $id;
$draftid = cleanvar($_REQUEST['draftid']);
if (empty($draftid)) $draftid = -1;

$title = $strEmail;


if (empty($step))
{
    $action = $_REQUEST['action'];

    if ($action == "deletedraft")
    {
        if ($draftid != -1)
        {
            $sql = "DELETE FROM `{$dbDrafts}` WHERE id = {$draftid}";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        }
        html_redirect("incident_email.php?id={$id}");
        exit;
    }

    $sql = "SELECT * FROM `{$dbDrafts}` WHERE type = 'email' AND userid = '{$sit[2]}' AND incidentid = '{$id}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        include (APPLICATION_INCPATH . 'incident_html_top.inc.php');

        echo "<h2>{$title}</h2>";

        echo display_drafts('email', $result);

        echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=1&amp;id={$id}'>{$strNewEmail}</a></p>";

        include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');

        exit;
    }
    else
    {
        $step = 1;
    }
}

switch ($step)
{
    case 1:
        // show form 1
        include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
        echo "<h2>".icon('email', 32)." {$strSendEmail}</h2>";
        echo "<form action='{$_SERVER['PHP_SELF']}?id={$id}' name='updateform' method='post'>";
        echo "<table align='center' class='vertical'>";
        echo "<tr><th>{$strTemplate}</th><td>".emailtemplate_drop_down("emailtype", 1, 'incident')."</td></tr>";
        echo "<tr><th>{$strDoesThisUpdateMeetSLA}:</th><td>";
        $target = incident_get_next_target($id);
        echo "<select name='target' class='dropdown'>\n";
        echo "<option value='none'>{$strNo}</option>\n";
        switch ($target->type)
        {
            //FIXME can this be put into the style sheets?
            case 'initialresponse':
                echo "<option value='initialresponse' style='text-indent: 15px;";
                echo " height: 17px; background-image: ";
                echo "url({$CONFIG['application_webpath']}/images/icons/";
                echo "{$iconset}/16x16/initialresponse.png); background-repeat:";
                echo " no-repeat;' >";
                echo "{$strInitialResponse}</option>\n";
                echo "<option value='probdef' style='text-indent: 15px; height:";
                echo " 17px; background-image: ";
                echo "url({$CONFIG['application_webpath']}/images/icons/";
                echo "{$iconset}/16x16/probdef.png); background-repeat: ";
                echo "no-repeat;'>";
                echo "{$strProblemDefinition}</option>\n";
                echo "<option value='actionplan' style='text-indent: 15px; ";
                echo "height: 17px; background-image: ";
                echo "url({$CONFIG['application_webpath']}/images/icons/";
                echo "{$iconset}/16x16/actionplan.png); background-repeat: ";
                echo "no-repeat;'>";
                echo "{$strActionPlan}</option>\n";
                echo "<option value='solution' style='text-indent: 15px; ";
                echo "height: 17px; background-image: ";
                echo "url({$CONFIG['application_webpath']}/images/icons/";
                echo "{$iconset}/16x16/solution.png); background-repeat: ";
                echo "no-repeat;'>";
                echo "{$strResolutionReprioritisation}</option>\n";
            break;

            case 'probdef':
                echo "<option value='probdef' style='text-indent: 15px; height:";
                echo " 17px; background-image: ";
                echo "url({$CONFIG['application_webpath']}/images/icons/";
                echo "{$iconset}/16x16/probdef.png); background-repeat: ";
                echo "no-repeat;'>";
                echo "{$strProblemDefinition}</option>\n";
                echo "<option value='actionplan' style='text-indent: 15px; ";
                echo "height: 17px; background-image: ";
                echo "url({$CONFIG['application_webpath']}/images/icons/";
                echo "{$iconset}/16x16/actionplan.png); background-repeat: ";
                echo "no-repeat;'>";
                echo "{$strActionPlan}</option>\n";
                echo "<option value='solution' style='text-indent: 15px; ";
                echo "height: 17px; background-image: ";
                echo "url({$CONFIG['application_webpath']}/images/icons/";
                echo "{$iconset}/16x16/solution.png); background-repeat: ";
                echo "no-repeat;'>";
                echo "{$strResolutionReprioritisation}</option>\n";
            break;

            case 'actionplan':
                echo "<option value='actionplan' style='text-indent: 15px; ";
                echo "height: 17px; background-image: ";
                echo "url({$CONFIG['application_webpath']}/images/icons/";
                echo "{$iconset}/16x16/actionplan.png); background-repeat: ";
                echo "no-repeat;'>";
                echo "{$strActionPlan}</option>\n";
                echo "<option value='solution' style='text-indent: 15px; ";
                echo "height: 17px; background-image: ";
                echo "url({$CONFIG['application_webpath']}/images/icons/";
                echo "{$iconset}/16x16/solution.png); background-repeat: ";
                echo "no-repeat;'>";
                echo "{$strResolutionReprioritisation}</option>\n";
            break;

            case 'solution':
                echo "<option value='solution' style='text-indent: 15px; ";
                echo "height: 17px; background-image: ";
                echo "url({$CONFIG['application_webpath']}/images/icons/";
                echo "{$iconset}/16x16/solution.png); background-repeat: ";
                echo "no-repeat;'>";
                echo "{$strResolutionReprioritisation}</option>\n";
            break;
        }
        echo "</select>\n</td></tr>";

        if ($CONFIG['auto_chase'] == TRUE)
        {
            $sql = "SELECT * FROM `{$dbUpdates}` WHERE incidentid = {$id} ";
            $sql .= "ORDER BY timestamp DESC LIMIT 1";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

            $obj = mysql_fetch_object($result);

            if ($obj->type == 'auto_chase_phone')
            {
                echo "<tr><th>{$strCustomerChaseUpdate}</th><td>";
                echo "<label><input type='radio' name='chase_customer' ";
                echo "value='no' checked='yes' />{$strNo}</label> ";
                echo "<label><input type='radio' name='chase_customer' ";
                echo "value='yes' />{$strYes}</label>";
                echo "</td></tr>";
            }

            if ($obj->type == 'auto_chase_manager')
            {
                echo "<tr><th>{$strManagerChaseUpdate}</th>";
                echo "<label><input type='radio' name='chase_manager' ";
                echo "value='no' checked='yes' />{$strNo}</label> ";
                echo "<label><input type='radio' name='chase_manager' ";
                echo "value='yes' />{$strYes}</label>";
                echo "</td></tr>";
            }
        }

        echo "<tr><th>{$strNewIncidentStatus}:</th><td>";
        echo incidentstatus_drop_down("newincidentstatus", incident_status($id));
        echo "</td></tr>\n";
        echo "<tr><th>{$strTimeToNextAction}:</th>";
        echo "<td>";
        echo show_next_action('updateform');
        echo "</td>";
        echo "<br />";
        echo "</td></tr>";
        plugin_do('incident_email_form1');
        echo "</table>";
        echo "<p align='center'>";
        echo "<input type='hidden' name='step' value='2' />";
        echo "<input type='hidden' name='menu' value='$menu' />";
        echo "<input name='submit1' type='submit' value='{$strContinue}' /></p>";
        echo "</form>\n";
        include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
    break;

    case 2:
        // show form 2
        if ($draftid != -1)
        {
            $draftsql = "SELECT * FROM `{$dbDrafts}` WHERE id = {$draftid}";
            $draftresult = mysql_query($draftsql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            $draftobj = mysql_fetch_object($draftresult);

            $metadata = explode("|",$draftobj->meta);
        }

        include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
        ?>
        <script type='text/javascript'>
        //<![CDATA[

        function confirm_send_mail()
        {
            return window.confirm('<?php echo $strAreYouSureSendEmail ?>');
        }

        <?php
            echo "var draftid = {$draftid};";
        ?>

        // Auto save
        function save_content(){
            var xmlhttp=false;

            if (!xmlhttp && typeof XMLHttpRequest!='undefined')
            {
                try
                {
                    xmlhttp = new XMLHttpRequest();
                }
                catch (e)
                {
                    xmlhttp=false;
                }
            }
            if (!xmlhttp && window.createRequest)
            {
                try
                {
                    xmlhttp = window.createRequest();
                }
                catch (e)
                {
                    xmlhttp=false;
                }
            }

            var toPass = $('bodytext').value;
            //alert(toPass.value);

/*
Format of meta data
$emailtype|$newincidentstatus|$timetonextaction_none|$timetonextaction_days|$timetonextaction_hours|$timetonextaction_minutes|$day|$month|$year|$target|$chase_customer|$chase_manager|$from|$replyTo|$ccemail|$bccemail|$toemail|$subject|$body
*/

            var meta = $('emailtype').value+"|"+$('newincidentstatus').value+"|"+$('timetonextaction_none').value+"|";
            meta = meta+$('timetonextaction_days').value+"|"+$('timetonextaction_hours').value+"|";
            meta = meta+$('timetonextaction_minutes').value+"||||";
            meta = meta+$('target').value+"|"+$('chase_customer').value+"|";
            meta = meta+$('chase_manager').value+"|"+$('fromfield').value+"|"+$('replytofield').value+"|";
            meta = meta+$('ccfield').value+"|"+$('bccfield').value+"|"+$('tofield').value+"|";
            meta = meta+$('subjectfield').value+"|"+$('bodytext').value+"|"
            meta = meta+$('date').value+"|"+$('timeoffset').value;

            if (toPass != '')
            {
                /*
                xmlhttp.open("GET", "ajaxdata.php?action=auto_save&userid="+<?php echo $_SESSION['userid']; ?>+
                             "&type=email&incidentid="+<?php echo $id; ?>+
                             "&draftid="+draftid+"&meta="+meta+"&content="+
                             escape(toPass), true);
             */

                var url =  "ajaxdata.php";
                var params = "action=auto_save&userid="+<?php echo $_SESSION['userid']; ?>+"&type=email&incidentid="+<?php echo $id; ?>+"&draftid="+draftid+"&meta="+encodeURIComponent(meta)+"&content="+encodeURIComponent(toPass);
                xmlhttp.open("POST", url, true)
                xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=utf-8");
                xmlhttp.setRequestHeader("Content-length", params.length);
                xmlhttp.setRequestHeader("Connection", "close");


                xmlhttp.onreadystatechange=function()
                {
                    if (xmlhttp.readyState==4)
                    {
                        if (xmlhttp.responseText != '')
                        {
                            if (draftid == -1)
                            {
                                draftid = xmlhttp.responseText;
                            }
                            var currentTime = new Date();
                            var hours = currentTime.getHours();
                            var minutes = currentTime.getMinutes();
                            if (minutes < 10)
                            {
                                minutes = "0" + minutes;
                            }
                            var seconds = currentTime.getSeconds();
                            if (seconds < 10)
                            {
                                seconds = "0" + seconds;
                            }
                            $('updatestr').innerHTML = '<?php echo "<a href=\"javascript:save_content();\">".icon('save', 16, $GLOBALS['strSaveDraft'])."</a> ".icon('info', 16, $GLOBALS['strDraftLastSaved'])." "; ?>' + hours + ':' + minutes + ':' + seconds;
                            $('draftid').value = draftid;
                        }
                    }
                }
                xmlhttp.send(params);
            }
        }

        setInterval("save_content()", 10000); //every 10 seconds

        //]]>
        </script>
        <?php
        // External vars
        if ($draftid == -1)
        {
            $emailtype = cleanvar($_REQUEST['emailtype']);
            $newincidentstatus = cleanvar($_REQUEST['newincidentstatus']);
            $timetonextaction_none = cleanvar($_REQUEST['timetonextaction_none']);
            $timetonextaction_days = cleanvar($_REQUEST['timetonextaction_days']);
            $timetonextaction_hours = cleanvar($_REQUEST['timetonextaction_hours']);
            $timetonextaction_minutes = cleanvar($_REQUEST['timetonextaction_minutes']);
            $day = cleanvar($_REQUEST['day']);
            $month = cleanvar($_REQUEST['month']);
            $year = cleanvar($_REQUEST['year']);
            $target = cleanvar($_REQUEST['target']);
            $chase_customer = cleanvar($_REQUEST['chase_customer']);
            $chase_manager = cleanvar($_REQUEST['chase_manager']);
            $date = cleanvar($_REQUEST['date']);
            $timeoffset = cleanvar($_REQUEST['timeoffset']);
        }
        else
        {
            $emailtype = $metadata[0];
            $newincidentstatus = $metadata[1];
            $timetonextaction_none = $metadata[2];
            $timetonextaction_days = $metadata[3];
            $timetonextaction_hours = $metadata[4];
            $timetonextaction_minutes = $metadata[5];
            $day = $metadata[6];
            $month = $metadata[7];
            $year = $metadata[8];
            $target = $metadata[9];
            $chase_customer = $metadata[10];
            $chase_manager = $metadata[11];
            $date = $metadata[12];
            $timeoffset = $metadata[13];
        }


        if ($draftid == -1)
        {
            // Grab the template
            $tsql = "SELECT * FROM `{$dbEmailTemplates}` WHERE id=$emailtype LIMIT 1";
            $tresult = mysql_query($tsql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            if (mysql_num_rows($tresult) > 0) $template = mysql_fetch_object($tresult);
            $paramarray = array('incidentid' => $id, 'triggeruserid' => $sit[2]);
            $from = replace_specials($template->fromfield, $paramarray);
            $replyto = replace_specials($template->replytofield, $paramarray);
            $ccemail = replace_specials($template->ccfield, $paramarray);
            $bccemail = replace_specials($template->bccfield, $paramarray);
            $toemail = replace_specials($template->tofield, $paramarray);
            $subject = replace_specials($template->subjectfield, $paramarray);
            $body = replace_specials($template->body, $paramarray);
        }
        else
        {
            $from = $metadata[12];
            $replyto = $metadata[13];
            $ccemail = $metadata[14];
            $bccemail = $metadata[15];
            $toemail = $metadata[16];
            $subject = $metadata[17];
            $body = $metadata[18];
        }

        echo "<form action='{$_SERVER['PHP_SELF']}?id={$id}' method='post' ";
        echo "enctype='multipart/form-data' onsubmit='return confirm_send_mail();' >";
        echo "<table align='center' class='vertical' width='95%'>";
        echo "<tr><th width='30%'>{$strFrom}</th><td><input maxlength='100' ";
        echo "name='fromfield' id='fromfield' size='40' value=\"{$from}\" /></td></tr>\n";
        echo "<tr><th>{$strReplyTo}</th><td><input maxlength='100' name='replytofield' ";
        echo "id='replytofield' size='40' value=\"{$replyto}\" /></td></tr>\n";
        if (trim($ccemail) == ",") $ccemail = '';
        if (substr($ccemail, 0, 1) == ",") $ccfield = substr($ccemail, 1, strlen($ccemail));
        echo "<tr><th>{$strCC}</th><td><input maxlength='100' name='ccfield' ";
        echo "id='ccfield' size='40' value=\"{$ccemail}\" /></td></tr>\n";
        echo "<tr><th>{$strBCC}</th><td><input maxlength='100' name='bccfield' ";
        echo "id='bccfield' size='40' value=\"{$bccemail}\" /></td></tr>\n";
        echo "<tr><th>{$strTo}</th><td><input maxlength='100' name='tofield' ";
        echo "id='tofield' size='40' value=\"{$toemail}\" /></td></tr>\n";
        echo "<tr><th>{$strSubject}</th><td><input maxlength='255' ";
        echo "name='subjectfield' id='subjectfield' size='40' value=\"{$subject}\" /></td></tr>\n";
        echo "<tr><th>{$strAttachment}";
        $file_size = readable_file_size($CONFIG['upload_max_filesize']);
        echo "(&lt; $file_size)";
        echo "</th><td>";
        echo "<input type='hidden' name='MAX_FILE_SIZE' value='{$CONFIG['upload_max_filesize']}' />";
        echo "<input type='file' name='attachment' size='40' />";
        echo "</td></tr>";
        echo "<tr><th>{$strMessage}</th><td>";
        echo "<textarea name='bodytext' id='bodytext' rows='20' cols='65'>";
        echo $body;
        echo "</textarea>";
        echo "<div id='updatestr'><a href='javascript:save_content();'>".icon('save', 16, $strSaveDraft)."</a></div>";
        echo "</td></tr>";
        plugin_do('incident_email_form2');
        echo "</table>";
        echo "<p align='center'>";
        echo "<input name='newincidentstatus' id='newincidentstatus' type='hidden' value='{$newincidentstatus}' />";
        echo "<input name='timetonextaction_none' id='timetonextaction_none' type='hidden' value='{$timetonextaction_none}' />";
        echo "<input name='timetonextaction_days' id='timetonextaction_days' type='hidden' value='{$timetonextaction_days}' />";
        echo "<input name='timetonextaction_hours' id='timetonextaction_hours' type='hidden' value='{$timetonextaction_hours}' />";
        echo "<input name='timetonextaction_minutes' id='timetonextaction_minutes' type='hidden' value='{$timetonextaction_minutes}' />";
        echo "<input name='chase_customer' id='chase_customer' type='hidden' value='{$chase_customer}' />";
        echo "<input name='chase_manager' id='chase_manager' type='hidden' value='{$chase_manager}' />";
        echo "<input name='date' id='date' type='hidden' value='{$date}' />";
        echo "<input name='timeoffset' id='timeoffset' type='hidden' value='{$timeoffset}' />";
        echo "<input name='target' id='target' type='hidden' value='{$target}' />";
        echo "<input type='hidden' id='step' name='step' value='3' />";
        echo "<input type='hidden' id='emailtype' name='emailtype' value='{$emailtype}' />";
        echo "<input type='hidden' id='draftid' name='draftid' value='{$draftid}' />";
        echo "<input name='submit2' type='submit' value='{$strSendEmail}' />";
        echo "</p>\n</form>\n";

        include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
    break;

    case 3:
        // show form 3 or send email and update incident

        // External variables
        $bodytext = $_REQUEST['bodytext'];
        $tofield = cleanvar($_REQUEST['tofield']);
        $fromfield = cleanvar($_REQUEST['fromfield']);
        $replytofield = cleanvar($_REQUEST['replytofield']);
        $ccfield = cleanvar($_REQUEST['ccfield']);
        $bccfield = cleanvar($_REQUEST['bccfield']);
        $subjectfield = cleanvar($_REQUEST['subjectfield'], FALSE, TRUE, FALSE);
        $emailtype = cleanvar($_REQUEST['emailtype']);
        $newincidentstatus = cleanvar($_REQUEST['newincidentstatus']);
        $timetonextaction_none = cleanvar($_REQUEST['timetonextaction_none']);
        $timetonextaction_days = cleanvar($_REQUEST['timetonextaction_days']);
        $timetonextaction_hours = cleanvar($_REQUEST['timetonextaction_hours']);
        $timetonextaction_minutes = cleanvar($_REQUEST['timetonextaction_minutes']);
        $date = cleanvar($_REQUEST['date']);
        $timeoffset = cleanvar($_REQUEST['timeoffset']);
        $year = cleanvar($_REQUEST['year']);
        $target = cleanvar($_REQUEST['target']);
        $chase_customer = cleanvar($_REQUEST['chase_customer']);
        $chase_manager = cleanvar($_REQUEST['chase_manager']);

        // move attachment to a safe place for processing later
        if ($_FILES['attachment']['name'] != '')       // Should be using this format throughout TPG 13/08/2002
        {
            $umask = umask(0000);
            $mk = TRUE;
            if (!file_exists($CONFIG['attachment_fspath'].$id))
            {
                $mk = mkdir($CONFIG['attachment_fspath'].$id, 0770, TRUE);
                if (!$mk)
                {
                    trigger_error('Failed creating incident attachment directory: '.$CONFIG['attachment_fspath'].$id, E_USER_WARNING);
                }
            }

            $name = $_FILES['attachment']['name'];
            $size = filesize($_FILES['attachment']['tmp_name']);
            $sql = "INSERT INTO `{$dbFiles}`(filename, size, userid, usertype) ";
            $sql .= "VALUES('{$name}', '{$size}', '{$sit[2]}', '1')";
            mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            $fileid = mysql_insert_id();

            $filename = $CONFIG['attachment_fspath'].$id.$fsdelim.$fileid."-".$name;

            $mv = rename($_FILES['attachment']['tmp_name'], $filename);
            if (!mv) trigger_error("Problem moving attachment from temp directory: {$filename}", E_USER_WARNING);
            $attachmenttype = $_FILES['attachment']['type'];
        }
        $errors = 0;
        // check to field
        if ($tofield == '')
        {
            $errors = 1;
            $error_string .= "<p class='error'>".sprintf($strFieldMustNotBeBlank, $strTo)."</p>\n";
        }
        // check from field
        if ($fromfield == '')
        {
            $errors = 1;
            $error_string .= "<p class='error'>".sprintf($strFieldMustNotBeBlank, $strFrom)."</p>\n";
        }
        // check reply to field
        if ($replytofield == '')
        {
            $errors = 1;
            $error_string .= "<p class='error'>".sprintf($strFieldMustNotBeBlank, $strReplyTo)."</p>\n";
        }
        $errorcode = $_FILES['attachment']['error'];
        // check the for errors related to file size in php.ini(upload_max_filesize) TODO: Should i18n this..
        if ($errorcode == 1 || $errorcode == 2)
        {
            $errors = 1;
            $error_string .= "<p>".get_file_upload_error_message($_FILES['attachment']['error'], $_FILES['attachment']['name'])."</p>\n";
        }
        // Store email body in session if theres been an error
        if ($errors > 0) $_SESSION['temp-emailbody'] = $bodytext;
        else unset($_SESSION['temp-emailbody']);

        // send email if no errors
        if ($errors == 0)
        {
            $extra_headers = "Reply-To: $replytofield\nErrors-To: ".user_email($sit[2])."\n";
            $extra_headers .= "X-Mailer: {$CONFIG['application_shortname']} {$application_version_string}/PHP " . phpversion() . "\n";
            $extra_headers .= "X-Originating-IP: {$_SERVER['REMOTE_ADDR']}\n";
            if ($ccfield != '')  $extra_headers .= "CC: $ccfield\n";
            if ($bccfield != '') $extra_headers .= "BCC: $bccfield\n";
            $extra_headers .= "\n"; // add an extra crlf to create a null line to separate headers from body
                                // this appears to be required by some email clients - INL

            $mime = new MIME_mail($fromfield, $tofield, html_entity_decode($subjectfield), '', $extra_headers, $mailerror);
            // INL 5 Aug 09, quoted-printable seems to split lines in unexpected places, base64 seems to work ok
            $mime -> attach($bodytext, '', "text/plain; charset={$GLOBALS['i18ncharset']}", 'quoted-printable', 'inline');

            // check for attachment
            //        if ($_FILES['attachment']['name']!='' || strlen($filename) > 3)
            if ($filename != '' && strlen($filename) > 3)
            {
                //          if (!isset($filename)) $filename = $attachment_fspath.$_FILES['attachment']['name'];   ??? TPG 13/08/2002
                if (!file_exists($filename)) trigger_error("File did not exist upon processing attachment: {$filename}", E_USER_WARNING);
                if ($filename == '') trigger_error("Filename was blank upon processing attachment: {$filename}", E_USER_WARNING);

                // Check file size before sending
                if (filesize($filename) > $CONFIG['upload_max_filesize'] || filesize($filename)==FALSE)
                {
                    trigger_error("User Error: Attachment too large or file upload error, filename: $filename,  perms: ".fileperms($filename).", size:",filesize($filename), E_USER_WARNING);
                    // throwing an error isn't the nicest thing to do for the user but there seems to be no way of
                    // checking file sizes at the client end before the attachment is uploaded. - INL
                }

                if (preg_match("!/x\-.+!i", $attachmenttype)) $type = OCTET;
                else $type = str_replace("\n","",$attachmenttype);
                $disp = "attachment; filename=\"$name\"; name=\"$name\";";
                $mime -> fattach($filename, "Attachment for incident $id", $type, 'base64', $disp);
            }

            // Lookup the email template (we need this to find out if the update should be visible or not)
            $sql = "SELECT * FROM `{$dbEmailTemplates}` WHERE id='$emailtype' ";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($result) < 1) trigger_error("Email template '{$meailtype}' not found",E_USER_WARNING);
            $emailtype = mysql_fetch_object($result);
            $storeinlog = $emailtype->storeinlog;
            $templatename = $emailtype->name;
            $templatedescription = $emailtype->description;

            // actually send the email
            $mailok = $mime -> send_mail();

            if ($mailok == FALSE)
            {
                trigger_error("Internal error sending email: send_mail() failed", E_USER_WARNING);
            }

            if ($mailok == TRUE)
            {
                // update incident status if necessary
                switch ($timetonextaction_none)
                {
                    case 'none':
                        $timeofnextaction = 0;
                    break;

                    case 'time':
                        $timeofnextaction = calculate_time_of_next_action($timetonextaction_days, $timetonextaction_hours, $timetonextaction_minutes);
                    break;

                    case 'date':
                        // kh: parse date from calendar picker, format: 200-12-31
                        $date=explode("-", $date);
                        $timeofnextaction=mktime(8 + $timeoffset,0,0,$date[1],$date[2],$date[0]);
                        $now = time();
                        if ($timeofnextaction < 0) $timeofnextaction = 0;
                    break;

                    default:
                        $timeofnextaction = 0;
                    break;
                }

                $oldtimeofnextaction = incident_timeofnextaction($id);

                if ($newincidentstatus != incident_status($id))
                {
                    $sql = "UPDATE `{$dbIncidents}` SET status='$newincidentstatus', lastupdated='$now', timeofnextaction='$timeofnextaction' WHERE id='$id'";
                    mysql_query($sql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                    $updateheader = "New Status: <b>" . incidentstatus_name($newincidentstatus) . "</b>\n\n";
                }
                else
                {
                    mysql_query("UPDATE `{$dbIncidents}` SET lastupdated='$now', timeofnextaction='$timeofnextaction' WHERE id='$id'");
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                }

                $timetext = '';

                if ($timeofnextaction != 0)
                {
                    $timetext = "Next Action Time: ";
                    if (($oldtimeofnextaction-$now) < 1)
                    {
                        $timetext .= "None";
                    }
                    else
                    {
                        $timetext .= date("D jS M Y @ g:i A", $oldtimeofnextaction);
                    }
                    $timetext .= " -&gt; <b>";
                    if ($timeofnextaction < 1)
                    {
                        $timetext .= "None";
                    }
                    else
                    {
                        $timetext .= date("D jS M Y @ g:i A", $timeofnextaction);
                    }
                    $timetext .= "</b>\n\n";
                    //$bodytext = $timetext.$bodytext;
                }

                if ($storeinlog == 'Yes')
                {
																// add update
                $bodytext = htmlentities($bodytext, ENT_COMPAT, 'UTF-8');
                $updateheader .= "{$SYSLANG['strTo']}: [b]{$tofield}[/b]\n";
                $updateheader .= "{$SYSLANG['strFrom']}: [b]{$fromfield}[/b]\n";
                $updateheader .= "{$SYSLANG['strReplyTo']}: [b]{$replytofield}[/b]\n";
                if ($ccfield != '' AND $ccfield != ",") $updateheader .=   "CC: [b]{$ccfield}[/b]\n";
                if ($bccfield != '') $updateheader .= "BCC: [b]{$bccfield}[/b]\n";
                if ($filename != '') $updateheader .= "{$SYSLANG['strAttachment']}: [b][[att={$fileid}]]".$name."[[/att]][/b]\n";
                $updateheader .= "{$SYSLANG['strSubject']}: [b]{$subjectfield}[/b]\n";

                if (!empty($updateheader)) $updateheader .= "<hr>";
                $updatebody = $timetext . $updateheader . $bodytext;
                $updatebody = mysql_real_escape_string($updatebody);

                $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, bodytext, type, timestamp, currentstatus, customervisibility) ";
                $sql .= "VALUES ({$id}, {$sit[2]}, '{$updatebody}', 'email', '{$now}', '{$newincidentstatus}', '{$emailtype->customervisibility}')";
                mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                $updateid = mysql_insert_id();

																$sql = "INSERT INTO `{$dbLinks}`(linktype, origcolref, linkcolref, direction, userid) ";
                $sql .= "VALUES (5, '{$updateid}', '{$fileid}', 'left', '{$sit[2]}')";
                mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
																}
                if ($storeinlog == 'No')
                {
                //Create a small note in the log to say the mail was sent but not logged (short )
																$updatebody  = "{$SYSLANG['strUpdateNotLogged']} \n";
                $updatebody .= "[b] {$SYSLANG['strTemplate']}: [/b]".$templatename."\n";
                $updatebody .= "[b] {$SYSLANG['strDescription']}: [/b]".$templatedescription."\n";
                $updatebody .= "{$SYSLANG['strTo']}: [b]{$tofield}[/b]\n";
                $updatebody = mysql_real_escape_string($updatebody);

																$sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, bodytext, type, timestamp, currentstatus, customervisibility) ";
                $sql .= "VALUES ({$id}, {$sit[2]}, '{$updatebody}', 'email', '{$now}', '{$newincidentstatus}', '{$emailtype->customervisibility}')";
                mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                $updateid = mysql_insert_id();

																$sql = "INSERT INTO `{$dbLinks}`(linktype, origcolref, linkcolref, direction, userid) ";
                $sql .= "VALUES (5, '{$updateid}', '{$fileid}', 'left', '{$sit[2]}')";
                mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
																}

                $owner = incident_owner($id);

                // Handle meeting of service level targets
                switch ($target)
                {
                    case 'none':
                        // do nothing
                        $sql = '';
                    break;

                    case 'initialresponse':
                        $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, sla, bodytext) ";
                        $sql .= "VALUES ('{$id}', '{$sit[2]}', 'slamet', '{$now}', '{$owner}', '{$newincidentstatus}', 'show', 'initialresponse','{$SYSLANG['strInitialResponseHasBeenMade']}')";
                    break;

                    case 'probdef':
                        $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, sla, bodytext) ";
                        $sql .= "VALUES ('{$id}', '{$sit[2]}', 'slamet', '{$now}', '{$owner}', '{$newincidentstatus}', 'show', 'probdef','{$SYSLANG['strProblemHasBeenDefined']}')";
                    break;

                    case 'actionplan':
                        $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, sla, bodytext) ";
                        $sql .= "VALUES ('{$id}', '{$sit[2]}', 'slamet', '{$now}', '{$owner}', '{$newincidentstatus}', 'show', 'actionplan','{$SYSLANG['strActionPlanHasBeenMade']}')";
                    break;

                    case 'solution':
                        $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, sla, bodytext) ";
                        $sql .= "VALUES ('{$id}', '{$sit[2]}', 'slamet', '{$now}', '{$owner}', '{$newincidentstatus}', 'show', 'solution','{$SYSLANG['strIncidentResolved']}')";
                    break;
                }
                if (!empty($sql))
                {
                    mysql_query($sql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                }
                if ($target != 'none')
                {
                    // Reset the slaemail sent column, so that email reminders can be sent if the new sla target goes out
                    $sql = "UPDATE `{$dbIncidents}` SET slaemail='0', slanotice='0' WHERE id='$id' LIMIT 1";
                    mysql_query($sql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                }

                if (!empty($chase_customer))
                {
                    $sql_insert = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp, customervisibility) ";
                    $sql_insert .= "VALUES ('{$id}','{$sit['2']}','auto_chased_phone', '{$owner}', '{$newincidentstatus}', '{$SYSLANG['strCustomerHasBeenCalledToChase']}','{$now}','hide')";
                    mysql_query($sql_insert);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

                    $sql_update = "UPDATE `{$dbIncidents}` SET lastupdated = '{$now}' WHERE id = {$id}";
                    mysql_query($sql_update);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                }

                if (!empty($chase_manager))
                {
                    $sql_insert = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp, customervisibility) ";
                    $sql_insert .= "VALUES ('{$id}','{$sit['2']}','auto_chased_manager', '{$owner}', '{$newincidentstatus}', 'Manager has been called to chase','{$now}','hide')";
                    mysql_query($sql_insert);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

                    $sql_update = "UPDATE `{$dbIncidents}` SET lastupdated = '{$now}' WHERE id = {$id}";
                    mysql_query($sql_update);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                }

                if ($draftid != -1)
                {
                    $sql = "DELETE FROM `{$dbDrafts}` WHERE id = {$draftid}";
                    mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                }

                journal(CFG_LOGGING_FULL, $SYSLANG['strEmailSent'], "{$SYSLANG['strSubject']}: $subjectfield, {$SYSLANG['strIncident']}: $id", CFG_JOURNAL_INCIDENTS, $id);
                // FIXME i18n, maybe have a function that prints a dialog and then closes the window?
                echo "<html>";
                echo "<head>";
                ?>
                <script type="text/javascript">
                function confirm_close_window()
                {
                    if (window.confirm('The email was sent successfully, click OK to close this window'))
                    {
                        window.opener.location='incident_details.php?id=<?php echo $id; ?>';
                        window.close();
                    }
                }
                </script>
                <?php
                echo "</head>";
                echo "<body onload=\"confirm_close_window();\">";
                echo "</body>";
                echo "</html>";
            }
            else
            {
                include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
                echo "<p class='error'>{$SYSLANG['strErrorSendingEmail']}: $mailerror</p>\n";
                include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
            }
        }
        else
        {
            // there were errors
            html_redirect("incident_email.php?id={$id}&step=2&draftid={$draftid}", FALSE, $error_string);
        }
    break;

    default:
        trigger_error("{$SYSLANG['strInvalidParameter']}: $step", E_USER_ERROR);
    break;
} // end switch step

?>