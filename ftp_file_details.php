<?php
// ftp_file_details.php -
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!   4Nov05


$permission = 44; // Publish Files to FTP site

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// display file details
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

// External Vars
$id = clean_int($_REQUEST['id']);
$title = $strFTPFileDetails;

$sql = "SELECT * FROM `{$dbFiles}` WHERE id='{$id}' AND category='ftp' ";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
$frow = mysql_fetch_array($result);

// calculate filesize
$j = 0;
$ext = array($strBytes, $strKBytes, $strMBytes, $strGBytes, $strTBytes);
$pretty_file_size = $frow['size'];
while ($pretty_file_size >= pow(1024,$j)) ++$j;
$pretty_file_size = round($pretty_file_size / pow(1024,$j-1) * 100) / 100 . ' ' . $ext[$j-1];

echo "<h2>{$title}</h2>";
echo "<table summary='file-details' align='center' width='60%' class='vertical'>";
echo "<tr><th>File:</th><td><img src='".getattachmenticon($frow['filename'])."' alt=\"{$frow['filename']} ({$pretty_file_size})\" border='0' />";
echo "<strong>{$frow['filename']}</strong> ({$pretty_file_size})</td></tr>";
if ($frow['path'] == '')
{
    $ftp_path = $CONFIG['ftp_path'];
}
else
{
    $ftp_path = $CONFIG['ftp_path'].substr($frow['path'],1).'/';
}

echo "<tr><th>Location:</th><td><a href='ftp://{$CONFIG['ftp_hostname']}{$ftp_path}{$frow['filename']}'><code>'ftp://{$CONFIG['ftp_hostname']}{$ftp_path}{$frow['filename']}</code></a></td></tr>";
echo "<tr><th>Title:</th><td>{$frow['shortdescription']}</td></tr>";
echo "<tr><th>Web Category:</th><td>{$frow['webcategory']}</td></tr>";
echo "<tr><th>Description:</th><td>{$frow['longdescription']}</td></tr>";
echo "<tr><th>File Version:</th><td>{$frow['fileversion']}</td></tr>";
echo "<tr><th>File Date:</th><td>".ldate($CONFIG['dateformat_filedatetime'], $frow['filedate'])." <strong>by</strong> ".user_realname($frow['userid'],TRUE)."</td></tr>";

if ($frow['expiry'] > 0)
{
    echo "<tr><th>Expiry:</th><td>".ldate($CONFIG['dateformat_filedatetime'], $frow['expiry'])."</td></tr>";
}
echo "</table>\n";
echo "<p align='center'>";
echo "<a href='ftp_delete.php?id={$id}'>Delete this file</a> | ";
echo "<a href='ftp_edit_file.php?id={$id}'>Describe and Publish this file</a></p>";
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>
