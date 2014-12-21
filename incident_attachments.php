<?php
// incident_attachments.php -
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_INCIDENT_VIEW_ATTACHMENT; // View incident attachments
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$id = clean_int($_REQUEST['id']);
$incidentid = $id;

$title = $strFiles;
include (APPLICATION_INCPATH . 'incident_html_top.inc.php');

// append incident number to attachment path to show this users attachments
$incident_attachment_fspath = $CONFIG['attachment_fspath'] . $id;

if (empty($incidentid)) $incidentid = mysql_real_escape_string($_REQUEST['id']);

/**
 * Convert a binary string into something viewable in a web browser
 */
function encode_binary($string)
{
    $chars = array();
    $ent = null;
    $chars = preg_split("//", $string, -1, PREG_SPLIT_NO_EMPTY);
    for ($i = 0; $i < count($chars); $i++ )
    {
        if ( preg_match("/^(\w| )$/",$chars[$i]))
        {
            $ent[$i] =  $chars[$i];
        }
        elseif ( ord($chars[$i]) < 32)
        {
            $ent[$i]=".";
        }
        else
        {
            $ent[$i] = "&#" . ord($chars[$i]) . ";";
        }
    }

    if ( sizeof($ent) < 1)
    {
      return "";
    }

    return implode("",$ent);
}


/**
 * @author Ivan Lucas
 */
function draw_file_row($file, $incidentid, $path)
{
    global $CONFIG;
    $filepathparts = explode(DIRECTORY_SEPARATOR, $file);
    $parts = count($filepathparts);
    $filename = $filepathparts[$parts - 1];
    $filedir = $filepathparts[$parts - 2];
    $preview = ''; // reset the preview
    $filenameparts = explode("-", $filename);
    $newfilename = cleanvar($filenameparts[1]);

    if ($filedir != $incidentid)
    {
        // files are in a subdirectory
        $url = "{$CONFIG['attachment_webpath']}{$incidentid}/{$filedir}/".str_replace('+','%20',urlencode($filename));
    }
    else
    {
        // files are in the root of the incident attachment directory
        $url = "{$CONFIG['attachment_webpath']}{$incidentid}/".str_replace('+','%20',urlencode($filename));
    }
    $filesize = filesize($file);
    $file_size = readable_bytes_size($filesize);

    $mime_type = mime_type($file);

    $updateid = str_replace("u", "", $filedir);
    $sql = "SELECT f.id FROM `{$GLOBALS['dbLinks']}`, `{$GLOBALS['dbFiles']}` AS f  ";
    $sql .= "WHERE linktype = '5' AND origcolref='{$updateid}' ";
    $sql .= "AND f.id = linkcolref ";
    $result = mysql_query($sql);
    $fileobj = mysql_fetch_object($result);
    $fileid = $fileobj->id;

    //new-style, can assume the filename is fileid-filename.ext
    if (is_numeric($filenameparts[0]))
    {
        $sql = "SELECT *, f.id AS fileid FROM `{$GLOBALS['dbLinks']}` AS l, ";
        $sql .= "`{$GLOBALS['dbFiles']}` as f, ";
        $sql .= "`{$GLOBALS['dbUpdates']}` as u ";
        $sql .= "WHERE f.id = '{$filenameparts[0]}' ";
        $sql .= "AND l.origcolref = u.id ";
        $sql .= "AND l.linkcolref = f.id";
        $result = mysql_query($sql);
        $row = mysql_fetch_object($result);
        $url = "download.php?id={$row->fileid}";
        $filename = $row->filename;
    }

    $html = "<tr>";
    $html .= "<td align='right' width='5%'>";
    $html .= "<a href=\"{$url}\"><img src='".getattachmenticon($filename)."' alt='Icon' title='{$filename} ({$file_size})' /></a>";
    $html .= "&nbsp;</td>";
    $html .= "<td width='30%'><a href='{$url}'";
    if (mb_substr($mime_type, 0, 4) == 'text' AND $filesize < 512000)
    {
        // The file is text, extract some of the contents of the file into a string for a preview
        $handle = fopen(clean_fspath($file), "r");
        $preview = fread($handle, 512); // only read this much, we can't preview the whole thing, not enough space
        fclose($handle);
        // Make the preview safe to display
        $preview = nl2br(encode_binary(strip_tags($preview)));
        $html .= " class='info'><span>{$preview}</span>$filename</a>";
    }
    else $html .= ">$filename</a>";
    $html .= "</td>";
    $html .= "<td width='20%'>{$file_size}</td>";
    $html .= "<td width='20%'>{$mime_type}</td>";
    $html .= "<td width='20%'>".ldate($CONFIG['dateformat_filedatetime'],filemtime($file))."</td>";
    //$html .= "<td width='5%'><input type='checkbox' name='fileselection[]' value='{$filename}' onclick=\"togglerow(this, 'tt');\"/></td>";
    $html .= "</tr>\n";
    return $html;
}

// append incident number to attachment path to show this users attachments
$incident_attachment_fspath = $CONFIG['attachment_fspath'] . $incidentid;
$att_max_filesize = return_bytes($CONFIG['upload_max_filesize']);

// Have a look to see if we've uploaded a file and process it if we have
if ($_FILES['attachment']['name'] != '')
{
    // Check if we had an error whilst uploading
    if ($_FILES['attachment']['error'] != '' AND $_FILES['attachment']['error'] != UPLOAD_ERR_OK)
    {
        echo get_file_upload_error_message($_FILES['attachment']['error'], $_FILES['attachment']['name']);
    }
    else
    {
        // OK to proceed
        // Create an entry in the files table
        $sql = "INSERT INTO `{$dbFiles}` (category, filename, size, userid, usertype, filedate) ";
        $sql .= "VALUES ('public', '" . clean_dbstring(clean_fspath($_FILES['attachment']['name'])) . "', '{$_FILES['attachment']['size']}', '{$sit[2]}', 'user', NOW())";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
        $fileid =  mysql_insert_id();

        //create update
        $updatetext = $SYSLANG['strFileUploaded'].": [[att={$fileid}]]" . cleanvar($_FILES['attachment']['name']) . "[[/att]]";
        $currentowner = incident_owner($incidentid);
        $currentstatus = incident_status($incidentid);
        $sql = "INSERT INTO `{$dbUpdates}` (incidentid, userid, `type`, `currentowner`, `currentstatus`, ";
        $sql .= "bodytext, `timestamp`) ";
        $sql .= "VALUES ('{$incidentid}', '{$sit[2]}', 'research', '{$currentowner}', '{$currentstatus}', ";
        $sql .= "'{$updatetext}', '$now')";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        $updateid = mysql_insert_id();

        $incident_attachment_fspath = $CONFIG['attachment_fspath'] . $incidentid . DIRECTORY_SEPARATOR;

        // make incident attachment dir if it doesn't exist
        $newfilename = $incident_attachment_fspath . $fileid . "-" . clean_fspath($_FILES['attachment']['name']);
        $umask = umask(0000);
        $mk = TRUE;
        if (!file_exists($incident_attachment_fspath))
        {
            $mk = mkdir($incident_attachment_fspath, 0770, TRUE);
            if (!$mk)
            {
                trigger_error('Failed creating incident attachment directory.', E_USER_WARNING);
            }
        }
        // Move the uploaded file from the temp directory into the incidents attachment dir
        $mv = @move_uploaded_file($_FILES['attachment']['tmp_name'], $newfilename);
        if (!$mv) trigger_error('!Error: Problem moving attachment from temp directory to.', E_USER_WARNING);

        //create link
        $sql = "INSERT INTO `{$dbLinks}`(linktype, origcolref, linkcolref, direction, userid) ";
        $sql .= "VALUES (5, '{$updateid}', '{$fileid}', 'left', '{$sit[2]}')";
        mysql_query($sql);
        if (mysql_error())
        {
            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        }

        echo "<div class='detailinfo'>\n";
        if ($mk AND $mv)
        {
            echo sprintf($strFileXUploadedOK,
                         "<strong>" . cleanvar($_FILES['attachment']['name']) . "</strong>",
                         "{$_FILES['attachment']['type']}",
                         "{$_FILES['attachment']['size']}");
        }
        else
        {
            echo "{$strErrorUploading} <strong>" . cleanvar($_FILES['attachment']['name']) . "</strong>";
        }

        // Debug
        //echo " tmp filename: {$_FILES['attachment']['tmp_name']}<br />";
        //echo "error: {$_FILES['attachment']['eroor']}<br />";
        //echo "new filename: {$newfilename}<br />";
        echo "</div>";
    }
}

// Have a look to see if we've posted a list of files, process them if we have
if (isset($_REQUEST['fileselection']))
{
    echo "<div class='detailhead'>\n";
    echo "Tested these files";
    echo "</div>";
    echo "<div class='detailentry'>\n";
    foreach ($fileselection AS $filesel)
    {
        $filesel = cleanvar($filesel);
        echo "$filesel {$strEllipsis} ";
        echo "listed";
        echo "<br />";
    }
    echo "</div>";
}


echo "<div class='detailhead'>\n";
echo "{$strFileManagement}";
echo "</div>";
echo "<div class='detailentry'>\n";
echo "<form action='{$_SERVER['PHP_SELF']}?id={$incidentid}' method='post' name='updateform' id='updateform' enctype='multipart/form-data'>\n";
echo "<input type='hidden' name='tab' value='{$selectedtab}' />";
echo "<input type='hidden' name='action' value='{$selectedaction}' />";
echo "<input type='hidden' name='MAX_FILE_SIZE' value='{$att_max_filesize}' />";
// maxfilesize='{$att_file_size}'
echo "<input class='textbox' type='file' name='attachment' size='30' /> ";
echo "<input type='submit' value=\"{$strAttachFile}\" /> (&lt;".readable_bytes_size($att_max_filesize).")";
echo "</form>";
echo "</div>";


if (file_exists($incident_attachment_fspath))
{
    $dirarray = array();
    echo "<form name='filelistform' action='{$_SERVER['PHP_SELF']}' method='post' onsubmit=\"return confirm_action('{$strAreYouSure}'\">";
    //echo "<input type='submit' name='test' value='List' />";
    echo "<input type='hidden' name='id' value='{$incidentid}' />";
    echo "<input type='hidden' name='tab' value='{$selectedtab}' />";
    echo "<input type='hidden' name='action' value='{$selectedaction}' />";

    // List the directories first
    $temparray = list_dir($incident_attachment_fspath, 0);
    if (count($temparray) == 0)
    {
        echo "<p class='info'>{$strNoFiles}<p>";
    }
    else
    {
        foreach ($temparray as $value)
        {
            if (is_dir($value)) $dirarray[] = $value;
            elseif (is_file($value) AND mb_substr($value, -1) != '.' AND mb_substr($value, -8) != 'mail.eml')
            {
                $rfilearray[] = $value;
            }
        }

        if (count($rfilearray) >= 1)
        {
            $headhtml = "<div class='detailhead'>\n";
            $headhtml .= icon('folder', 16, $strRootDirectory)." {$strFiles}";
            $headhtml .= "</div>\n";
            echo $headhtml;
            echo "<div class='detailentry'>\n";

            echo "<table>\n";
            foreach ($rfilearray AS $rfile)
            {
                echo draw_file_row($rfile, $incidentid, $incident_attachment_fspath);
            }
            echo "</table>\n";
            echo "</div>";
        }

        foreach ($dirarray AS $dir)
        {
            $directory = mb_substr($dir, 0, strrpos($dir, DIRECTORY_SEPARATOR));
            $dirname = mb_substr($dir, strrpos($dir, DIRECTORY_SEPARATOR) + 1, mb_strlen($dir));
            if (is_numeric($dirname) AND $dirname != $id AND mb_strlen($dirname) == 10)
            {
                $dirprettyname = ldate('l jS M Y @ g:ia',$dirname);
            }
            elseif ($dirname[0] == 'u')
            {
                $updateid = mb_substr($dirname, 1);
                $sql = "SELECT userid, timestamp, type, bodytext, type FROM `{$GLOBALS['dbUpdates']}` WHERE id = {$updateid}";
                $result = mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                $update = mysql_fetch_object($result);
                $dirprettyname = ldate('l jS M Y @ g:ia',$update->timestamp) . " $strby ".user_realname($update->userid);
                $updatetext = cleanvar($update->bodytext);
                $updatetype = $update->type;
            }
            else
            {
                $dirprettyname = $dirname;
            }
            $headhtml = "<div class='detailhead'>\n";
            $headhtml .= icon('folder', 16, $id, $dir)." {$dirprettyname}";
            $headhtml .= "</div>\n";
            $tempfarray = list_dir($dir, 1);
            if (count($tempfarray) == 1 AND (mb_substr($tempfarray[0],-8) == 'mail.eml'))
            {
                // do nothing if theres only an email in the dir, don't even list the directory
            }
            else
            {
                echo $headhtml;  // print the directory header bar that we drew above
                echo "<div class='detailentry'>\n";
                if (in_array("{$dir}" . DIRECTORY_SEPARATOR . "mail.eml", $tempfarray))
                {
                    $updatelink = readlink($dir);
                    $updateid = mb_substr($updatelink, strrpos($updatelink, DIRECTORY_SEPARATOR) + 1, mb_strlen($updatelink));
                    echo "<p>{$strTheseFilesArrivedBy} <a href='{$CONFIG['attachment_webpath']}{$incidentid}/{$dirname}/mail.eml'>{$strEmail}</a>, <a href='incident_details.php?id={$incidentid}#{$updateid}'>{$strJumpToEntryLog}</a></p>";
                }

                foreach ($tempfarray as $fvalue)
                {
                    if (is_file($fvalue) AND mb_substr($fvalue,-8) != 'mail.eml')
                    {
                        $filearray[] = $fvalue;
                    }
                }
                echo "<table>\n";
                foreach ($filearray AS $file)
                {
                    echo draw_file_row($file, DIRECTORY_SEPARATOR, $incidentid, $dirname);
                }

                if (!empty($updatetext) AND $updatetype == 'email' OR $updatetype == 'webupdate')
                {
                    $updatetext = mb_substr($updatetext, 0, 80) . $strEllipsis;
                    echo "<span style='font-size:400%';>“</span>";
                    echo bbcode($updatetext);
                    echo "<span style='font-size:400%';>„</span>";
                }
                echo "</table>\n";
                echo "</div>";
            }
            unset($filearray);
        }
    }
}
echo "</form>";

include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');

?>