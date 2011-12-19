<?php
// ftp_publish.php -
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_FILE_PUBLISH; // Publish Files to FTP site
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// seed with microseconds since last "whole" second
mt_srand((double) microtime() * 1000000);
$maxVal = 1000000;
$minVal = 1;
$randvala = (mt_rand() % ($maxVal - $minVal)) + $minVal;
// seed with current time
mt_srand($now);
$maxVal = 1000000;
$minVal = 1;
$randvalb = (mt_rand() % ($maxVal-$minVal)) + $minVal;
$randomdir = dechex(crc32($randvala.$randvalb));

$filesize = filesize($source_file);

$pretty_file_size = readable_bytes_size($filesize);

// FIXME This temp variable name can't be right can it?  INL
if (!isset($temp_directory))
{
    // show form
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>{$strFTPPublish}</h2>";
    echo "<form name='publishform' action='{$_SERVER['PHP_SELF']}' method='post'>";
    echo "<input type='hidden' name='source_file' value='{$source_file}' />";
    echo "<input type='hidden' name='destination_file' value='{$destination_file}' />";
    echo "<input type='hidden' name='temp_directory' value='{$randomdir}' />";
    echo "<input type='hidden' name='ftp_url' value=\"ftp://{$CONFIG['ftp_hostname']}{$CONFIG['ftp_path']}{$randomdir}/{$destination_file}\" />";
    echo "<table summary='ftp-publish' align='center' width='60%' class='vertical'>";
    echo "<tr><th>{$strPublish}:</th><td><img src='".getattachmenticon($filename)."' alt='{$filename} ({$pretty_file_size})' border='0' />";
    echo "<strong>{$destination_file}</strong> ({$pretty_file_size})</td></tr>";
    echo "<tr><th>{$strTo}:</th><td><code>'ftp://{$CONFIG['ftp_hostname']}{$CONFIG['ftp_path']}{$randomdir}/{$destination_file}</code></td></tr>";
    echo "<tr><th>{$strTitle}:</th><td><input type='text' name='shortdescription' maxlength='255' size='40'' /></td></tr>";
    echo "<tr><th>{$strDescription}:</th><td><textarea name='longdescription' cols='40' rows='3'></textarea></td></tr>";
    echo "<tr><th>{$strFileVersion}:</th><td><input type='text' name='fileversion' maxlength='50' size='10' /></td></tr>";
    echo "<tr><th>{$strValid}:</th><td>";
    echo "<input type='radio' name='expiry_none' value='time'> {$strForXDaysHoursMinutes}<br />&nbsp;&nbsp;&nbsp;";
    echo "<input maxlength='3' name='expiry_days' value='{$na_days}' onclick=\"window.document.publishform.expiry_none[0].checked = true;\" size='3' /> {$strDays}&nbsp;";
    echo "<input maxlength='2' name='expiry_hours' value='{$na_hours}' onclick=\"window.document.publishform.expiry_none[0].checked = true;\" size='3' /> {$strHours}&nbsp;";
    echo "<input maxlength='2' name='expiry_minutes' value='{$na_minutes}' onclick=\"window.document.publishform.expiry_none[0].checked = true;\" size='3' /> {$strMinutes}<br />";
    echo "<input type='radio' name='expiry_none' value='date'>{$strUntilSpecificDateAndTime}<br />&nbsp;&nbsp;&nbsp;";

    // Print Listboxes for a date selection
    echo "<select name='day' onclick=\"window.document.publishform.expiry_none[1].checked = true;\">";

    for ($t_day = 1; $t_day <= 31; $t_day++)
    {
        echo "<option value='{$t_day}' ";
        if ($t_day == date("j"))
        {
            echo "selected='selected'";
        }
        echo ">{$t_day}</option>\n";
    }

    echo "</select><select name='month' onclick=\"window.document.publishform.expiry_none[1].checked = true;\">";

    for ($t_month = 1; $t_month <= 12; $t_month++)
    {
        echo "<option value='{$t_month}'";
        if ($t_month == date("n"))
        {
            echo " selected='selected'";
        }
        echo ">". date ("F", mktime(0, 0, 0, $t_month, 1, 2000)) ."</option>\n";
    }

    echo "</select><select name='year' onclick=\"window.document.publishform.expiry_none[1].checked = true;\">";

    for ($t_year=(date("Y")-1); $t_year <= (date("Y")+5); $t_year++)
    {
        echo "<option value=\"$t_year\" ";
        if ($t_year == date("Y"))
        {
            echo "selected='selected'";
        }
        echo ">$t_year\n";
    }

    echo "</select>";
    echo "</td></tr>";
    echo "</table>";
    echo "<p class='formbuttons'><input type='submit' value='{$strPublish}' /></p>";
    echo "</form>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    // publish file
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>{$strFTPPublish}</h2>";
    // set up basic connection
    $conn_id = create_ftp_connection();

    $destination_filepath = $CONFIG['ftp_path'] . $temp_directory . '/' . $destination_file;

    // make the temporary directory
    $mk = @ftp_mkdir($conn_id, $CONFIG['ftp_path'] . $temp_directory);
    if (!mk) trigger_error(sprintf($strFTPFailedCreatingDirectoryX , $temp_directory), E_USER_WARNING);

    // check the source file exists
    if (!file_exists($source_file)) trigger_error(sprintf($strSourceFailCannotBeFoundX, $source_file), E_USER_WARNING);

    // set passive mode
    if (!ftp_pasv($conn_id, TRUE)) trigger_error($strProblemSettingPassiveFTPMode, E_USER_WARNING);

    // upload the file
    $upload = ftp_put($conn_id, "$destination_filepath", "$source_file", FTP_BINARY);

    // check upload status
    if (!$upload)
    {
        echo "{$strUploadFailed}<br />";
    }
    else
    {
        echo sprintf($strUpdatedXToYAsZ, $source_file, $CONFIG['ftp_hostname'], $destination_filepath)."<br />";
        echo "<code>{$ftp_url}</code>";

        journal(CFG_LOGGING_NORMAL, 'FTP File Published', "File $destination_file_file was published to {$CONFIG['ftp_hostname']}", CFG_JOURNAL_OTHER, 0);

        switch ($expiry_none)
        {
            case 'none': $expirydate = 0; break;
            case 'time':
                if ($expiry_days < 1 && $expiry_hours < 1 && $expiry_minutes < 1) $expirydate = 0;
                else
                {
                    // uses calculate_time_of_next_action() because the function suits our purpose
                    $expirydate = calculate_time_of_next_action($expiry_days, $expiry_hours, $expiry_minutes);
                }
            break;

            case 'date':
                // $now + ($days * 86400) + ($hours * 3600) + ($minutes * 60);
                $unixdate = mktime(9,0,0,$month,$day,$year);
                $expirydate = $unixdate;
                if ($expirydate < 0) $expirydate = 0;
            break;

            default:
                $expirydate = 0;
            break;
        }

        // store file details in database
        $sql = "INSERT INTO `{$dbFiles}` ('category', filename, size, userid, shortdescription, longdescription, path, date, expiry, fileversion) ";
        $sql .= "VALUES ('ftp', '" . clean_dbstring($destination_file) . "', '$filesize', '".$sit[2]."', '$shortdescription', '$longdescription', '".$temp_directory.'/'."', '$now', FROM_UNIXTIME($expirydate) ,'$fileversion')";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    }
    // close the FTP stream
    ftp_close($conn_id);
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>
