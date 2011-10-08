<?php
// ftp_upload_file.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// FIXME needs i18n

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_FILE_PUBLISH; // ftp publishing
require (APPLICATION_LIBPATH.'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

// External variables
$file = cleanvar($_REQUEST['file']);
$action = clean_fixed_list($_REQUEST['action'], array('','publish'));

$max_filesize = return_bytes($CONFIG['upload_max_filesize']);


if (empty($action))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>Upload Public File</h2>";

    echo "<p align='center'>IMPORTANT: Files published here are <strong>public</strong> and available to all ftp users.</p>";
    echo "<form name='publishform' action='{$_SERVER['PHP_SELF']}' method='post' enctype='multipart/form-data'>";
    echo "<table class='vertical'>";
    echo "<tr><th>File <small>(&lt;".readable_bytes_size($max_filesize).")</small>:</th>";
    echo "<td class='shade2'><input type='hidden' name='MAX_FILE_SIZE' value='{$max_filesize}' />";
    echo "<input type='file' name='file' size='40'' /></td></tr>";

    echo "<tr><th>{$strTitle}:</th><td><input type='text' name='shortdescription' maxlength='255' size='40' /></td></tr>";
    echo "<tr><th>{$strDescription}:</th><td><textarea name='longdescription' cols='40' rows='3'></textarea></td></tr>";
    echo "<tr><th>{$strFileVersion}:</th><td><input type='text' name='fileversion' maxlength='50' size='10' /></td></tr>";
    echo "<tr><th>{$strExpire}:</th><td>";
    echo "<input type='radio' name='expiry_none' value='time'' /> In <em>x</em> days, hours, minutes<br />&nbsp;&nbsp;&nbsp;";
    echo "<input maxlength='3' name='expiry_days' value='{$na_days}' onclick=\"window.document.publishform.expiry_none[0].checked = true;\" size='3'' /> Days&nbsp;";
    echo "<input maxlength='2' name='expiry_hours' value='{$na_hours}' onclick=\"window.document.publishform.expiry_none[0].checked = true;\" size='3'' /> Hours&nbsp;";
    echo "<input maxlength='2' name='expiry_minutes' value='{$na_minutes}' onclick=\"window.document.publishform.expiry_none[0].checked = true;\" size='3'' /> Minutes<br />";
    echo "<input type='radio' name='expiry_none' value='date'' />On specified Date<br />&nbsp;&nbsp;&nbsp;";

    // Print Listboxes for a date selection
    echo "<select name='day' onclick=\"window.document.publishform.expiry_none[1].checked = true;\">";

    for ($t_day=1;$t_day<=31;$t_day++)
    {
        echo "<option value=\"{$t_day}\" ";
        if ($t_day == date("j"))
        {
            echo "selected='selected'";
        }
        echo ">$t_day</option>\n";
    }

    echo "</select><select name='month' onclick=\"window.document.publishform.expiry_none[1].checked = true;\">";

    for ($t_month = 1; $t_month <= 12; $t_month++)
    {
        echo "<option value=\"{$t_month}\"";
        if ($t_month == date("n"))
        {
            echo " selected='selected'";
        }
        echo ">". date ("F", mktime(0,0,0,$t_month,1,2000)) ."</option>\n";
    }

    echo "</select><select name='year' onclick=\"window.document.publishform.expiry_none[1].checked = true;\">";

    for ($t_year = (date("Y")-1); $t_year <= (date("Y")+5); $t_year++)
    {
        echo "<option value=\"{$t_year}\" ";
        if ($t_year == date("Y"))
        {
            echo "selected='selected'";
        }
        echo ">$t_year</option>\n";
    }
    echo "</select>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";
    echo "<p class='formbuttons'><input type='submit' value='{$strPublish}' />";
    echo "<input type='hidden' name='action' value='publish' /></p>";
    echo "<p class='return'><a href='ftp_list_files.php'>{$strBackToList}</a></p>";
    echo "</form>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
//     echo "<pre>".print_r($_REQUEST,true)."</pre>";
//     echo "<pre>".print_r($_FILES,true)."</pre>";

    // TODO v3.2x ext variables
    $file_name = $_FILES['file']['name'];

    $shortdescription = clean_dbstring($_REQUEST['shortdescription']);
    $longdescription = clean_dbstring($_REQUEST['longdescription']);
    $fileversion = clean_dbstring($_REQUEST['fileversion']);

    $expirytype = clean_fixed_list($_REQUEST['expiry_none'], array('','time','date'));


    if ($expirytype == 'time')
    {
        $days = clean_int($_REQUEST['expiry_days']);
        $hours = clean_int($_REQUEST['expiry_hours']);
        $minutes = clean_int($_REQUEST['expiry_minutes']);

        if ($days < 1 && $hours < 1 && $minutes < 1)
        {
            $expirydate = 0;
        }
        else
        {
            $expirydate = calculate_time_of_next_action($days, $hours, $minutes);
        }
    }
    elseif ($expirytype == 'date')
    {
        $day = clean_int($_REQUEST['day']);
        $month = clean_int($_REQUEST['month']);
        $year = clean_int($_REQUEST['year']);

        $date = explode("-", $date);
        $expirydate = mktime(0, 0, 0, $month, $day, $year);
    }
    else
    {
        $expirydate = 0;
    }

    // receive the uploaded file to a temp directory on the local server
    if ($_FILES['file']['error'] != '' AND $_FILES['file']['error'] != UPLOAD_ERR_OK)
    {
        echo get_file_upload_error_message($_FILES['file']['error'], $_FILES['file']['name']);
    }
    else
    {
        $filepath = $CONFIG['attachment_fspath'] . clean_fspath($file_name);
        $mv = @move_uploaded_file($_FILES['file']['tmp_name'], $filepath);
        if (!mv) trigger_error("Problem moving uploaded file from temp directory.", E_USER_WARNING);

        if (!file_exists($filepath)) trigger_error("Error the temporary upload file was not found.", E_USER_WARNING);

        // Check file size
        $filesize = filesize($filepath);
        if ($filesize > $CONFIG['upload_max_filesize'])
        {
            trigger_error("User Error: Attachment too large or file upload error.", E_USER_WARNING);
            // throwing an error isn't the nicest thing to do for the user but there seems to be no way of
            // checking file sizes at the client end before the attachment is uploaded. - INL
        }
        if ($filesize == FALSE) trigger_error("Error handling uploaded file", E_USER_WARNING);

        // set up basic connection
        $conn_id = create_ftp_connection();

        $destination_filepath = $CONFIG['ftp_path'] . $file_name;

        // check the source file exists
        if (!file_exists($filepath)) trigger_error("Source file cannot be found.", E_USER_WARNING);

        // set passive mode if required
        if (!ftp_pasv($conn_id, $CONFIG['ftp_pasv'])) trigger_error("Problem setting passive ftp mode", E_USER_WARNING);

        // upload the file
        $upload = ftp_put($conn_id, "$destination_filepath", "$filepath", FTP_BINARY);

        // close the FTP stream
        ftp_close($conn_id);

        // check upload status
        if (!$upload)
        {
            trigger_error($strUploadFailed, E_USER_ERROR);
        }
        else
        {
            // store file details in database
            // important: path must be blank for public files (all go in same dir)
            $sql = "INSERT INTO `{$dbFiles}` (category, filename, size, userid, shortdescription, longdescription, path, filedate, expiry, fileversion) ";
            $sql .= "VALUES ('ftp', '" . clean_dbstring($file_name) . "', '$filesize', '".$sit[2]."', '$shortdescription', '$longdescription', '{$CONFIG['ftp_path']}', '$now', FROM_UNIXTIME($expirydate) ,'$fileversion')";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            journal(CFG_LOGGING_NORMAL, 'FTP File Uploaded', sprintf($strFTPFileXUploaded, $filename), CFG_JOURNAL_OTHER, 0);

            html_redirect('ftp_upload_file.php', TRUE, "<code>{$ftp_url}</code>");
        }
    }

}
?>