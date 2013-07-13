<?php
// move_update.php - Moves an incident from the pending/holding queue
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

require ('core.php');
$permission = PERM_UPDATE_DELETE;
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$incidentid = clean_int($_REQUEST['incidentid']);
$updateid = clean_int($_REQUEST['updateid']);
$contactid = clean_int($_REQUEST['contactid']);
$id = clean_int($_REQUEST['id']);
$send_email = cleanvar($_REQUEST['send_email']);

if ($incidentid == '')
{
    $title = $strMoveUpdate;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    $incidentid = clean_int($_REQUEST['incidentid']); // Need to do this here again as incident_html_top changes this to $id which we need above so the menu works
    echo "<h2>{$title}</h2>";
    echo "<h3>{$strMoveToIncident}</h3>";

    echo show_form_errors('moveupdate');
    clear_form_errors('moveupdate');

    echo "<div align='center'>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' id='moveupdate' name='moveupdate'>";
    echo "<label>{$strToIncidentID}: ";
    if ($contactid > 0) echo incident_drop_down('incidentid', 0, $contactid);
    else echo "<input type='text' name='incidentid' value='{$incidentid}' size='10' maxlength='12' />";
    echo "</label>";
    echo "<p class='formbuttons'><input type='submit' value='{$strMoveUpdate}' /></p><br />";
    echo "<input type='hidden' name='updateid' value='{$updateid}' />";
    echo "<input type='hidden' name='id' value='{$id}' />";
    echo "<input type='hidden' name='win' value='incomingview' />";
    echo "</form>";
    if ($contactid > 0)
    {
        echo "<p><a href='move_update.php?id={$id}&amp;updateid=";
        echo "{$updateid}&amp;win=incomingview'>{$strOtherIncidents}</a>";
    }
    echo "</div>";

    $sql  = "SELECT * FROM `{$dbUpdates}` WHERE id='{$updateid}' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    while ($updates = mysql_fetch_object($result))
    {
        $update_timestamp_string = ldate($CONFIG['dateformat_datetime'], $updates->timestamp);
        echo "<br />";
        echo "<table align='center' width='95%'>";
        echo "<tr><th>";

        $text = $updatetypes[$updates->type]['text'];
        $text = str_replace('currentowner', user_realname($updates->currentowner, TRUE), $text);
        $text = str_replace('updateuser', user_realname($updates->userid, TRUE), $text);
        echo $text;

        if ($updates->nextaction != '') echo " Next Action: <strong>{$updates->nextaction}</strong>";

        echo " - {$update_timestamp_string}</th></tr>";
        echo "<tr><td class='shade2' width='100%'>";
        $updatecounter++;
        echo parse_updatebody($updates->bodytext);

        echo "</td></tr>";
        echo "</table>";

        echo "<p><a href=\"inbox.php?id={$id}\">$strReturnToPreviousPage</a></p>";

        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
}
else
{
    // check that the incident is still open.  i.e. status not = closed
    if (incident_status($incidentid) != STATUS_CLOSED)
    {
        $moved_attachments = TRUE;
        // update the incident record, change the incident status to active
        $sql = "UPDATE `{$dbIncidents}` SET status='1', lastupdated='{$now}', timeofnextaction='0' WHERE id='{$incidentid}'";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        $old_path = $CONFIG['attachment_fspath']. 'updates' . DIRECTORY_SEPARATOR;
        $new_path = $CONFIG['attachment_fspath'] . $incidentid . DIRECTORY_SEPARATOR;

        //move attachments from updates to incident
        $sql = "SELECT linkcolref, filename FROM `{$dbLinks}` AS l, ";
        $sql .= "`{$dbFiles}` as f ";
        $sql .= "WHERE l.origcolref = '{$updateid}' ";
        $sql .= "AND l.linktype = 5 ";
        $sql .= "AND l.linkcolref = f.id";
        $result = mysql_query($sql);
        if ($result)
        {
            if (!file_exists($new_path))
            {
                $umask = umask(0000);
                mkdir($CONFIG['attachment_fspath'] . "{$incidentid}", 0770);
                umask($umask);
            }

            while ($row = mysql_fetch_object($result))
            {
                $filename = $row->linkcolref ;
                //. "-" . $row->filename;
                $old_file = $old_path . $filename;
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

        if ($moved_attachments)
        {
            // retrieve the update body so that we can insert time headers
            $sql = "SELECT incidentid, bodytext, timestamp FROM `{$dbUpdates}` WHERE id='{$updateid}'";
            $uresult = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            list($oldincidentid, $bodytext, $timestamp) = mysql_fetch_row($uresult);

            if ($oldincidentid == 0) $oldincidentid = 'Inbox';

            $prettydate = ldate('r', $timestamp);
            // prepend 'moved' header to bodytext
            $body = sprintf($SYSLANG['strMovedFromXtoXbyX'], "<b>$oldincidentid</b>",
                            "<b>$incidentid</b>",
                            "<b>".user_realname($sit[2])."</b>")."\n";
            $body .= sprintf($SYSLANG['strOriginalMessageReceivedAt'],
                             "<b>$prettydate</b>")."\n";
            $body .= $SYSLANG['strStatus'] . " -&gt; <b>{$SYSLANG['strActive']}</b>\n";
            $bodytext = $body . $bodytext;
            $bodytext = mysql_real_escape_string($bodytext);
            // move the update.
            $sql = "UPDATE `{$dbUpdates}` SET incidentid='{$incidentid}', userid='{$sit[2]}', bodytext='{$bodytext}', timestamp='{$now}' WHERE id='{$updateid}'";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            //remove from tempincoming to prevent build up
            $sql = "DELETE FROM `{$dbTempIncoming}` WHERE updateid='{$updateid}'";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            journal(CFG_LOGGING_NORMAL, 'Incident Update Moved', "Incident update {$update} moved to incident {$incidentid}", CFG_JOURNAL_INCIDENTS, $incidentid);

            html_redirect("inbox.php");
        }
        else
        {
            $_SESSION['formerrors']['moveupdate']['id'] = $strErrorAssigningUpdate;
            header("Location: {$_SERVER['PHP_SELF']}?id={$id}&updateid={$updateid}&win=incomingview");
            exit;
        }
    }
    else
    {
        // no open incident with this number.  Return to form.
        $_SESSION['formerrors']['moveupdate']['id'] = $strErrorAssigningUpdate;
        header("Location: {$_SERVER['PHP_SELF']}?id={$id}&updateid={$updateid}&win=incomingview");
        exit;
    }
}
?>
