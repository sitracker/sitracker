<?php
// portal/update.php - Update incidents in the portal
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Kieran Hogg <kieran[at]sitracker.org>

require ('..' . DIRECTORY_SEPARATOR . 'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

$accesslevel = 'any';

include (APPLICATION_LIBPATH . 'portalauth.inc.php');

// External vars
$id = clean_int($_REQUEST['id']);

// First check the portal user is allowed to access this incident
$sql = "SELECT contact FROM `{$dbIncidents}` WHERE id = $id LIMIT 1";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
list($incidentcontact) = mysql_fetch_row($result);
if ($incidentcontact == $_SESSION['contactid'])
{
    if (empty($_POST['update']) AND empty($_FILES))
    {
        include (APPLICATION_INCPATH . 'portalheader.inc.php');
        echo "<h2>".icon('note', 32, $strUpdateIncident);
        echo " {$strUpdateIncident} {$id}</h2>";
        echo "<div id='update' align='center'><form action='{$_SERVER[PHP_SELF]}?id={$id}' method='post' id='updateform' name='updateform' enctype='multipart/form-data'>";
        
        echo "<table class='vertical maintable' width='50%'>";
        
        echo "<tr><th>{$strUpdate}:</th><td><textarea cols='60' rows='10' name='update'></textarea></td></tr>";
        
        echo "<tr><th>";
        echo "{$strPutIncidentOnHoldUntil}</th><td><input name='timetonextaction_date' id='timetonextaction_date' size='10' /> ".date_picker("updateform.timetonextaction_date");
        echo "</td></tr>";

        echo "<tr><th>".icon('attach', 16, $strAttachment);
        // calculate upload filesize
        $j = 0;
        $ext = array($strBytes, $strKBytes, $strMBytes, $strGBytes, $strTBytes);
        $att_file_size = $CONFIG['upload_max_filesize'];
        while ($att_file_size >= pow(1024, $j))
        {
            ++$j;
        }

        $att_file_size = round($att_file_size / pow(1024, $j-1) * 100) / 100 . ' ' . $ext[$j-1];

        echo " {$strAttachment} ";

        echo "(&lt;{$att_file_size}):</th><td>";
        echo "<input type='hidden' name='MAX_FILE_SIZE' value='{$CONFIG['upload_max_filesize']}' />";
        echo "<input type='file' name='attachment' size='20' /></td></tr>";
        
        echo "</table>";
        echo "<p><input type='submit' value=\"{$strUpdate}\"/></p></form></div>";

        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
    else
    {
        $usersql = "SELECT forenames, surname FROM `{$dbContacts}` WHERE id='{$_SESSION['contactid']}'";
        $result = mysql_query($usersql);
        $user = mysql_fetch_object($result);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        $forenames = cleanvar($user->forenames);
        $surname = cleanvar($user->surname); // If name has ' in it
        $update = cleanvar($_REQUEST['update']);
        $timetonextaction_date = cleanvar($_POST['timetonextaction_date']);

        if (isset($_SESSION['syslang'])) $SYSLANG = $_SESSION['syslang'];

        if (!empty($forenames) AND !empty($surname))
        {
            //TODO change order for a name such as Chinese?
            $updatebody = "<hr>".sprintf($SYSLANG['strUpdatedViaThePortalBy'], "[b]{$forenames}", "{$surname}[/b]")."\n\n";
        }
        else
        {
            $updatebody = "<hr>".sprintf($SYSLANG['strUpdatedViaThePortalBy'], "[b]{$strCustomer}[/b]", '')."\n\n";
        }

        if (!empty($_FILES['attachment']['name']))
        {
            $filename = cleanvar(clean_fspath($_FILES['attachment']['name']));
            $sql = "INSERT INTO `{$dbFiles}`(category, filename, size, userid, usertype, shortdescription, longdescription, filedate) ";
            $sql .= "VALUES ('public', '{$filename}', '{$_FILES['attachment']['size']}', '{$_SESSION['contactid']}', 'contact', '', '', NOW())";
            mysql_query($sql);
            if (mysql_error())
            {
                $errors++;
                trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            }
            else
            {
                $fileid = mysql_insert_id();
                $updatebody = "{$SYSLANG['strAttachment']}: [[att={$fileid}]]{$filename}[[/att]]".$updatebody;
            }
        }
        //add the update
        $updatebody .= $update;
        
        $timeofnextaction = 0;
        
        if (!empty($timetonextaction_date)) 
        {
            $date = explode("-", $timetonextaction_date);
            $timeofnextaction = mktime($time_picker_hour, $time_picker_minute, 0, $date[1], $date[2], $date[0]);
            if ($timeofnextaction < 0) $timeofnextaction = 0;
            else 
            {
                $timetext = "Next Action Time: ".date("D jS M Y @ g:i A", $timeofnextaction)."</b>\n\n";
                $updatebody = $timetext.$updatebody;
            }
            
        }

        $owner = incident_owner($id);

        $sql = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp, customervisibility, nextaction) ";
        $sql .= "VALUES('{$id}', '0', 'webupdate', '{$owner}', '1', '{$updatebody}', '{$now}', 'show', {$timeofnextaction})";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        else
        {
            $updateid = mysql_insert_id();
        }


        //upload file, here because we need updateid
        if ($_FILES['attachment']['name'] != '')
        {
            // make incident attachment dir if it doesn't exist
            $umask = umask(0000);

            $directory = "{$CONFIG['attachment_fspath']}{$id}" . DIRECTORY_SEPARATOR;

            if (!file_exists($directory))
            {
                $mk = @mkdir($directory, 0770, TRUE);
                if (!$mk)
                {
                    $errors++;
                    $sql = "DELETE FROM `{$dbUpdates}` WHERE id='{$updateid}'";
                    mysql_query($sql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                    trigger_error("Failed creating incident attachment directory.", E_USER_WARNING);
                }
            }
            umask($umask);
            $newfilename = "{$directory}{$fileid}-" . clean_fspath($_FILES['attachment']['name']);

            // Move the uploaded file from the temp directory into the incidents attachment dir
            $mv = @move_uploaded_file($_FILES['attachment']['tmp_name'], $newfilename);
            if (!$mv)
            {
                $errors++;
                $sql = "DELETE FROM `{$dbUpdates}` WHERE id='{$updateid}'";
                mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                trigger_error('!Error: Problem moving attachment from temp directory.', E_USER_WARNING);
            }

            // Check file size before attaching
            $att_max_filesize = return_bytes($CONFIG['upload_max_filesize']);
            if ($_FILES['attachment']['size'] > $att_max_filesize)
            {
                $errors++;
                $sql = "DELETE FROM `{$dbUpdates}` WHERE id='{$updateid}'";
                mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                trigger_error('User Error: Attachment too large or file upload error.', E_USER_WARNING);
                // throwing an error isn't the nicest thing to do for the user but there seems to be no guaranteed
                // way of checking file sizes at the client end before the attachment is uploaded. - INL
            }
            $filename = cleanvar($_FILES['attachment']['name']);
        }

        //create link
        $sql = "INSERT INTO `{$dbLinks}`(linktype, origcolref, linkcolref, direction, userid) ";
        $sql .= "VALUES(5, '{$updateid}', '{$fileid}', 'left', '0')";
        mysql_query($sql);
        if (mysql_error())
        {
            $errors++;
            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        }

        // Set incident status, active if not put on hold until a future date
        $id = clean_int($_REQUEST['id']);
        $status = STATUS_ACTIVE;
        if (!empty($timetonextaction_date) AND $timeofnextaction > $now)
        {
            $status = STATUS_CUSTOMER;
        }

        $sql = "UPDATE `{$dbIncidents}` SET status={$status}, lastupdated='{$now}' WHERE id='{$id}'";
        mysql_query($sql);
        if (mysql_error())
        {
            $errors++;
            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        }

        if ($errors > 0)
        {
            html_redirect($_SERVER['PHP_SELF']."?id={$id}", FALSE);
        }
        else
        {
            html_redirect("incident.php?id={$id}");
        }
    }
}
else
{
    include (APPLICATION_INCPATH . 'portalheader.inc.php');
    echo "<p class='warning'>{$strNoPermission}.</p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    exit;
}

?>