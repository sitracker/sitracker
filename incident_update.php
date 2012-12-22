<?php
// incident_update.php - For for logging updates to an incident
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

require ('core.php');
$permission = PERM_UPDATE_ADD; // Update Incident
require (APPLICATION_LIBPATH . 'functions.inc.php');

$disable_priority = TRUE;

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External Variables
// $bodytext = cleanvar($_REQUEST['bodytext'],FALSE,FALSE);
$bodytext = cleanvar($_REQUEST['bodytext'], FALSE, TRUE);
$id = clean_int($_REQUEST['id']);
$incidentid = $id;
$action = clean_fixed_list($_REQUEST['action'], array('','editdraft','deletedraft','newupdate','update'));
$draftid = cleanvar($_REQUEST['draftid']);
if (empty($draftid)) $draftid = -1;

$title = $strUpdate;

/**
 * Update page
 */
function display_update_page($draftid=-1)
{
    global $id;
    global $incidentid;
    global $action;
    global $CONFIG;
    global $iconset;
    global $now;
    global $dbDrafts;
    global $sit;

    if ($draftid != -1)
    {
        $draftsql = "SELECT * FROM `{$dbDrafts}` WHERE id = {$draftid}";
        $draftresult = mysql_query($draftsql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        $draftobj = mysql_fetch_object($draftresult);

        $metadata = explode("|",$draftobj->meta);
    }

    // No update body text detected show update form

    ?>
    <script type="text/javascript">

    new PeriodicalExecuter(function(pe) {
            save_draft('<?php echo $id; ?>', 'update')
        },
        10);

    //-->
    </script>
    <?php

    echo show_form_errors('update');
    clear_form_errors('update');

    //echo "<form action='".$_SERVER['PHP_SELF']."?id={$id}&amp;draftid={$draftid}' method='post' name='updateform' id='updateform' enctype='multipart/form-data'>";
    echo "<form action='{$_SERVER['PHP_SELF']}?id={$id}' method='post' name='updateform' id='updateform' enctype='multipart/form-data'>";
    echo "<table class='vertical'>";
    echo "<tr>";
    echo "<th align='right' width='20%;'>{$GLOBALS['strSLATarget']}";
    echo icon('sla', 16)."</th>";
    echo "<td class='shade2'>";
    $target = incident_get_next_target($id);

    $targetNone = '';
    $targetInitialresponse = '';
    $targetProbdef = '';
    $targetActionplan = '';
    $targetSolution = '';

    $typeResearch = '';
    $typeEmailin = '';
    $typeEmailout = '';
    $typePhonecallin = '';
    $typePhonecallout = '';
    $typeExternalinfo = '';
    $typeReviewmet = '';


    if (!empty($metadata))
    {
        switch ($metadata[0])
        {
            case 'none':
                $targetNone = " selected='selected' ";
                break;
            case 'initialresponse':
                $targetInitialresponse = " selected='selected' ";
                break;
            case 'probdef':
                $targetProbdef = " selected='selected' ";
                break;
            case 'actionplan':
                $targetActionplan = " selected='selected' ";
                break;
            case 'solution':
                $targetSolution = " selected='selected' ";
                break;
        }

        switch ($metadata[1])
        {
            case 'research':
                $typeResearch = " selected='selected' ";
                break;
            case 'emailin':
                $typeEmailin = " selected='selected' ";
                break;
            case 'emailout':
                $typeEmailout = " selected='selected' ";
                break;
            case 'phonecallin':
                $typePhonecallin = " selected='selected' ";
                break;
            case 'phonecallout':
                $typePhonecallout = " selected='selected' ";
                break;
            case 'externalinfo':
                $typeExternalinfo = " selected='selected' ";
                break;
            case 'reviewmet':
                $typeReviewmet = " selected='selected' ";
                break;
        }
    }
    else
    {
        $targetNone = " selected='selected' ";
    	$typeResearch = " selected='selected' ";
    }

    $sla_targets = get_incident_sla_targets($incidentid);

    echo "<select name='target' id='target' class='dropdown' onchange=\"incident_update_sla_change($('target').value)\">\n";
    echo "<option value='none' {$targetNone}>{$GLOBALS['strNone']}</option>\n";
    switch ($target->type)
    {
        case 'initialresponse':
            if ($sla_targets->initial_response_mins > 0) echo "<option value='initialresponse' {$targetInitialresponse} class='initialresponse'>{$GLOBALS['strInitialResponse']}</option>\n";
            if ($sla_targets->prob_determ_mins > 0) echo "<option value='probdef' {$targetProbdef} class='problemdef'>{$GLOBALS['strProblemDefinition']}</option>\n";
            if ($sla_targets->action_plan_mins > 0) echo "<option value='actionplan' {$targetActionplan} class='actionplan'>{$GLOBALS['strActionPlan']}</option>\n";
            if ($sla_targets->resolution_days > 0) echo "<option value='solution' {$targetSolution} class='solution'>{$GLOBALS['strResolutionReprioritisation']}</option>\n";
            break;
        case 'probdef':
            if ($sla_targets->prob_determ_mins > 0) echo "<option value='probdef' {$targetProbdef} class='problemdef'>{$GLOBALS['strProblemDefinition']}</option>\n";
            if ($sla_targets->action_plan_mins > 0) echo "<option value='actionplan' {$targetActionplan} class='actionplan'>{$GLOBALS['strActionPlan']}</option>\n";
            if ($sla_targets->resolution_days > 0) echo "<option value='solution' {$targetSolution} class='solution'>{$GLOBALS['strResolutionReprioritisation']}</option>\n";
            break;
        case 'actionplan':
            if ($sla_targets->action_plan_mins > 0) echo "<option value='actionplan' {$targetActionplan} class='actionplan'>{$GLOBALS['strActionPlan']}</option>\n";
            if ($sla_targets->resolution_days > 0) echo "<option value='solution' {$targetSolution} class='solution'>{$GLOBALS['strResolutionReprioritisation']}</option>\n";
            break;
        case 'solution':
            if ($sla_targets->resolution_days > 0) echo "<option value='solution' {$targetSolution} class='solution'>{$GLOBALS['strResolutionReprioritisation']}</option>\n";
            break;
    }
    echo "</select>\n";
    echo "</td></tr>\n";
    echo "<tr><th align='right'>{$GLOBALS['strUpdateType']}</th>";
    echo "<td class='shade1'>";

    if (!empty($metadata[0]))
    {
        echo "<select name='updatetype' id='updatetype' class='dropdown' disabled='disabled''>\n";
        if ($metadata[0] == 'probdef')
        {
            echo "<option selected='selected' value='probdef'>Problem Definition</option>\n";
        }
        else if ($metadata[0] == 'actionplan')
        {
            echo "<option selected='selected' value='actionplan'>Action Plan</option>\n";
        }
    }
    else
    {
    	    echo "<select name='updatetype' id='updatetype' class='dropdown'>\n";
    }

    echo "<option value='research' {$typeResearch} style='text-indent: 15px; height: 17px; background-image: url({$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/research.png); background-repeat: no-repeat;'>{$GLOBALS['strResearchNotes']}</option>\n";
    echo "<option value='emailin' {$typeEmailin} style='text-indent: 15px; height: 17px; background-image: url({$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/emailin.png); background-repeat: no-repeat;'>{$GLOBALS['strEmailFromCustomer']}</option>\n";
    echo "<option value='emailout' {$typeEmailout} style='text-indent: 15px; height: 17px; background-image: url({$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/emailout.png); background-repeat: no-repeat;'>{$GLOBALS['strEmailToCustomer']}</option>\n";
    echo "<option value='phonecallin' {$typePhonecallin} style='text-indent: 15px; height: 17px; background-image: url({$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/callin.png); background-repeat: no-repeat;'>{$GLOBALS['strCallFromCustomer']}</option>\n";
    echo "<option value='phonecallout' {$typePhonecallout} style='text-indent: 15px; height: 17px; background-image: url({$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/callout.png); background-repeat: no-repeat;'>{$GLOBALS['strCallToCustomer']}</option>\n";
    echo "<option value='externalinfo' {$typeExternalinfo} style='text-indent: 15px; height: 17px; background-image: url({$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/externalinfo.png); background-repeat: no-repeat;'>{$GLOBALS['strExternalInfo']}</option>\n";
    echo "<option value='reviewmet' {$typeReviewmet} style='text-indent: 15px; height: 17px; background-image: url({$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/review.png); background-repeat: no-repeat;'>{$GLOBALS['strReview']}</option>\n";

    echo "</select>";
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<th align='right'>{$GLOBALS['strUpdate']}<br />";
    echo "<span class='required'>{$GLOBALS['strRequired']}</span></th>";
    echo "<td class='shade1'>";
    $checkbox = '';
    if (!empty($metadata))
    {
        if ($metadata[2] == "true") $checkbox = "checked='checked'";
    }
    else
    {
        $checkbox = "checked='checked'";
    }
    echo "<label><input type='checkbox' name='cust_vis' id='cust_vis' ";
    echo "{$checkbox} value='yes' /> {$GLOBALS['strMakeVisibleInPortal']}</label><br />";
    echo bbcode_toolbar('updatelog');
    echo "<textarea name='bodytext' id='updatelog' rows='13' cols='50' class='required'>";
    if ($draftid != -1) echo $draftobj->content;
    echo "</textarea>";
    echo "<div id='updatestr'><a href=\"javascript:save_draft('{$id}', 'update');\">".icon('save', 16, $GLOBALS['strSaveDraft'])."</a></div>";
    echo "</td></tr>";

    if ($target->type == 'initialresponse')
    {
        $disable_priority = TRUE;
    }
    else
    {
        $disable_priority = FALSE;
    }
    echo "<tr><th align='right'>{$GLOBALS['strNewPriority']}</th>";
    echo "<td class='shade1'>";

    $maxpriority = servicelevel_maxpriority(incident_service_level($id));

    $setPriorityTo = incident_priority($id);

    if (!empty($metadata))
    {
        $setPriorityTo = $metadata[3];
    }

    echo priority_drop_down("newpriority", $setPriorityTo, $maxpriority, $disable_priority); //id='priority
    echo "</td></tr>\n";

    echo "<tr>";
    echo "<th align='right'>{$GLOBALS['strNewStatus']}</th>";

    $setStatusTo = incident_status($id);

    $disabled = FALSE;

    //we do this so if you update another user's incident, it defaults to active
    if ($sit[2] != incident_owner($incidentid))
    {
        $setStatusTo = '0';
    }
    elseif (!empty($metadata))
    {
        $setStatusTo = $metadata[4];
    }

    echo "<td class='shade1'>".incidentstatus_drop_down("newstatus", $setStatusTo)."</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<th align='right'>{$GLOBALS['strNextAction']}</th>";

    $nextAction = '';

    if (!empty($metadata))
    {
        $nextAction = $metadata[5];
    }

    echo "<td class='shade2'><input type='text' name='nextaction' ";
    echo "id='nextaction' maxlength='50' size='30' value='{$nextAction}' /></td></tr>";
    echo "<tr>";
    echo "<th align='right'>";
    echo "<strong>{$GLOBALS['strTimeToNextAction']}</strong></th>";
    echo "<td class='shade2'>";
    echo show_next_action('updateform', $id);
    echo "</td></tr>";
    echo "<tr>";
    // calculate upload filesize
    $att_file_size = readable_bytes_size($CONFIG['upload_max_filesize']);
    echo "<th align='right'>{$GLOBALS['strAttachFile']}";
    echo " (&lt;{$att_file_size})</th>";

    echo "<td class='shade1'><input type='hidden' name='MAX_FILE_SIZE' value='{$CONFIG['upload_max_filesize']}' />";
    // maxfilesize='{$CONFIG['upload_max_filesize']}'
    echo "<input type='file' name='attachment' size='40' /></td>";
    echo "</tr>";
    echo "</table>";
    echo "<p class='center'>";
    echo "<input type='hidden' name='action' id='action' value='update' />";

    echo "<input type='hidden' name='draftid' id='draftid' value='{$draftid}' />";
    echo "<input type='hidden' name='storepriority' id='storepriority' value='".incident_priority($id)."' />";
    echo "<input type='submit' name='submit' value='{$GLOBALS['strUpdateIncident']}' /></p>";
    echo "</form>";
}


if (empty($action))
{
    $sql = "SELECT * FROM `{$dbDrafts}` WHERE type = 'update' AND userid = '{$sit[2]}' AND incidentid = '{$id}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    include (APPLICATION_INCPATH . 'incident_html_top.inc.php');

    if (mysql_num_rows($result) > 0)
    {
        echo "<h2>{$title}</h2>";

        echo display_drafts('update', $result);

        echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?action=newupdate&amp;id={$id}'>{$strUpdateNewUpdate}</a></p>";
    }
    else
    {
        //No previous updates - just display the page
        display_update_page();
    }
}
else if ($action == "editdraft")
{
    include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
    display_update_page($draftid);
}
else if ($action == "deletedraft")
{
    $draftid = cleanvar($_REQUEST['draftid']);
    if ($draftid != -1)
    {
        $sql = "DELETE FROM `{$dbDrafts}` WHERE id = {$draftid}";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
    }
    html_redirect("{$_SERVER['PHP_SELF']}?id={$id}");
}
else if ($action == "newupdate")
{
    include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
    display_update_page();
}
else
{
    // Update the incident

    // External variables
    $target = clean_fixed_list($_POST['target'], array('', 'none', 'initialresponse', 'actionplan', 'probdef', 'solution'));
    $updatetype = cleanvar($_POST['updatetype']);
    $newstatus = clean_int($_POST['newstatus']);
    $nextaction = cleanvar($_POST['nextaction']);
    $newpriority = clean_int($_POST['newpriority']);
    $cust_vis = clean_fixed_list($_POST['cust_vis'], array('no', 'yes'));
    $timetonextaction = cleanvar($_POST['timetonextaction']);
    $date = cleanvar($_POST['date']);
    $time_picker_hour = clean_int($_REQUEST['time_picker_hour']);
    $time_picker_minute = clean_int($_REQUEST['time_picker_minute']);
    $timetonextaction_days = clean_int($_POST['timetonextaction_days']);
    $timetonextaction_hours = clean_int($_POST['timetonextaction_hours']);
    $timetonextaction_minutes = clean_int($_POST['timetonextaction_minutes']);
    $draftid = cleanvar($_POST['draftid']);

    // \p{L} A Unicode character
    // \p{N} A Unicode number
    // /u does a unicode search
    if (empty($bodytext))
    {
        $_SESSION['formerrors']['update'][] = sprintf($strFieldMustNotBeBlank, $strUpdate);
        html_redirect($_SERVER['PHP_SELF']."?id={$id}", FALSE);
        exit;
    }
    elseif ((mb_strlen($bodytext) < 4) OR !preg_match('/[\p{L}\p{N}]+/u', $bodytext))
    {
        $_SESSION['formerrors']['update'][] = sprintf($strMustContainFourCharacters, $strUpdate);
        html_redirect($_SERVER['PHP_SELF']."?id={$id}", FALSE);
        exit;
    }

    if (empty($newpriority)) $newpriority  = incident_priority($id);
    $timeofnextaction = 0;
    // update incident
    switch ($timetonextaction)
    {
        case 'none':
            $timeofnextaction = 0;
            break;
        case 'time':
            if ($timetonextaction_days < 1 && $timetonextaction_hours < 1 && $timetonextaction_minutes < 1)
            {
                $timeofnextaction = 0;
            }
            else
            {
                $timeofnextaction = calculate_time_of_next_action($timetonextaction_days, $timetonextaction_hours, $timetonextaction_minutes);
            }
            break;
        case 'date':
            // kh: parse date from calendar picker, format: 200-12-31
            $date = explode("-", $date);
            $timeofnextaction = mktime($time_picker_hour, $time_picker_minute, 0, $date[1], $date[2], $date[0]);
            if ($timeofnextaction < 0) $timeofnextaction = 0;
            break;
        default:
            $timeofnextaction = 0;
            break;
    }

    // Put text into body of update for field changes (reverse order)
    // delim first
    $bodytext = "<hr>" . $bodytext;
    $oldstatus = incident_status($id);
    $oldtimeofnextaction = incident_timeofnextaction($id);
    if ($newstatus != $oldstatus)
    {
        $bodytext = "Status: ".mysql_real_escape_string(incidentstatus_name($oldstatus))." -&gt; <b>" . mysql_real_escape_string(incidentstatus_name($newstatus)) . "</b>\n\n" . $bodytext;
    }

    if ($newpriority != incident_priority($id))
    {
        $bodytext = "New Priority: <b>" . mysql_real_escape_string(priority_name($newpriority)) . "</b>\n\n" . $bodytext;
    }

    if ($timeofnextaction > ($oldtimeofnextaction + 60))
    {
        $timetext = "Next Action Time: ";
        if (($oldtimeofnextaction - $now) < 1) $timetext .= "None";
        else $timetext .= date("D jS M Y @ g:i A", $oldtimeofnextaction);
        $timetext .= " -&gt; <b>";
        if ($timeofnextaction < 1) $timetext .= "None";
        else $timetext .= date("D jS M Y @ g:i A", $timeofnextaction);
        $timetext .= "</b>\n\n";
        $bodytext = $timetext.$bodytext;
    }

    // attach file - have to do it here to get fileid
    // TODO user file_upload
    $att_max_filesize = return_bytes($CONFIG['upload_max_filesize']);
    if ($_FILES['attachment']['name'] != '')
    {
        $filename = cleanvar(clean_fspath($_FILES['attachment']['name']));
        if ($cust_vis == 'yes')
        {
            $category = 'public';
        }
        else
        {
            $category = 'private';
        }

        $sql = "INSERT INTO `{$dbFiles}`(category, filename, size, userid, usertype, shortdescription, longdescription, filedate) ";
        $sql .= "VALUES ('{$category}', '" . clean_dbstring($filename) . "', '{$_FILES['attachment']['size']}', '{$sit[2]}', 'user', '', '', NOW())";
        mysql_query($sql);
        if (mysql_error())
        {
            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        }
        else
        {
            $fileid = mysql_insert_id();
        }
    }

    if ($_FILES['attachment']['name'] != '' && isset($_FILES['attachment']['name']) == TRUE)
    {
        $bodytext = "{$SYSLANG['strAttachment']}: [[att={$fileid}]]" . cleanvar($_FILES['attachment']['name']) ."[[/att]]\n\n{$bodytext}";
    }

    // Check the updatetype field, if it's blank look at the target

    if (empty($updatetype))
    {
        switch ($target)
        {
            case 'actionplan':
                $updatetype = 'actionplan';
                break;
            case 'probdef':
                $updatetype = 'probdef';
                break;
            case 'solution':
                $updatetype = 'solution';
                break;
            default:
                $updatetype = 'research';
                break;
        }
    }

    // Force reviewmet to be visible
    if ($updatetype == 'reviewmet') $cust_vis = 'yes';

    $owner = incident_owner($id);

    if ($target == 'none') $sla = "Null";
    else $sla = "'{$target}'";

    // visible update
    if ($cust_vis == "yes")
    {
        $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, bodytext, timestamp, currentowner, currentstatus, customervisibility, nextaction, sla) ";
        $sql .= "VALUES ('{$id}', '{$sit[2]}', '{$updatetype}', '{$bodytext}', '{$now}', '{$owner}', '{$newstatus}', 'show' , '{$nextaction}', {$sla})";
    }
    else
    {
        // invisible update
        $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, bodytext, timestamp, currentowner, currentstatus, nextaction, sla) ";
        $sql .= "VALUES ({$id}, {$sit[2]}, '{$updatetype}', '{$bodytext}', '{$now}', '{$owner}', '{$newstatus}', '{$nextaction}', {$sla})";
    }

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    $updateid = mysql_insert_id();
    $t = new TriggerEvent('TRIGGER_INCIDENT_UPDATED_INTERNAL', array('incidentid' => $id, 'userid' => $sit[2]));

    //upload file, here because we need updateid
    if ($_FILES['attachment']['name'] != '')
    {
        // make incident attachment dir if it doesn't exist
        $umask = umask(0000);
        if (!file_exists("{$CONFIG['attachment_fspath']}{$id}"))
        {
            $mk = @mkdir("{$CONFIG['attachment_fspath']}{$id}", 0770, TRUE);
            if (!$mk)
            {
                $sql = "DELETE FROM `{$dbUpdates}` WHERE id='{$updateid}'";
                mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                trigger_error("Failed creating incident attachment directory." . DIRECTORY_SEPARATOR, E_USER_WARNING);
            }
        }
        umask($umask);
        $newfilename = "{$CONFIG['attachment_fspath']}{$id}" . DIRECTORY_SEPARATOR . "{$fileid}";

        // Move the uploaded file from the temp directory into the incidents attachment dir
        $mv = @move_uploaded_file($_FILES['attachment']['tmp_name'], $newfilename);
        if (!$mv)
        {
            $sql = "DELETE FROM `{$dbUpdates}` WHERE id='{$updateid}'";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            trigger_error('!Error: Problem moving attachment from temp directory.', E_USER_WARNING);
        }

        // Check file size before attaching
        if ($_FILES['attachment']['size'] > $att_max_filesize)
        {
            trigger_error('User Error: Attachment too large or file upload error.', E_USER_WARNING);
            // throwing an error isn't the nicest thing to do for the user but there seems to be no guaranteed
            // way of checking file sizes at the client end before the attachment is uploaded. - INL
        }
        $filename = cleanvar($_FILES['attachment']['name']);
        if ($cust_vis == 'yes')
        {
            $category = 'public';
        }
        else
        {
            $category = 'private';
        }
    }

    //create link
    $sql = "INSERT INTO `{$dbLinks}`(linktype, origcolref, linkcolref, direction, userid) ";
    $sql .= "VALUES(5, '{$updateid}', '{$fileid}', 'left', '{$sit[2]}')";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    }

    $sql = "UPDATE `{$dbIncidents}` SET status='{$newstatus}', priority='{$newpriority}', lastupdated='{$now}', timeofnextaction='{$timeofnextaction}' WHERE id='{$id}'";
    mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    if ($target != 'none')
    {
        // Reset the slaemail sent column, so that email reminders can be sent if the new sla target goes out
        $sql = "UPDATE `{$dbIncidents}` SET slaemail='0', slanotice='0' WHERE id='{$id}' LIMIT 1";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    }


    if (!$result)
    {
        include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
        echo "<p class='error'>{$strUpdateIncidentFailed}</p>\n";
        include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
    }
    else
    {
        if ($draftid != -1 AND !empty($draftid))
        {
            $sql = "DELETE FROM `{$dbDrafts}` WHERE id = {$draftid}";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
        }
        journal(CFG_LOGGING_MAX, 'Incident Updated', "Incident {$id} Updated", CFG_JOURNAL_SUPPORT, $id);
        html_redirect("incident_details.php?id={$id}");
    }
}

include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');

?>