<?php
// ftp_delete.php -
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


$permission = 44; // Publish Files to FTP site

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$id = clean_int($_REQUEST['id']);

$sql = "SELECT * FROM `{$dbFiles}` WHERE id='$id'"; // TODO only get necessary fields back
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
$obj = mysql_fetch_object($result);

// set up basic connection
$conn_id = create_ftp_connection();

if ($obj->path != '')
{
    // delete private file
    $filewithpath = $CONFIG['ftp_path'] . $obj->path . $obj->filename;
    $filepath = $CONFIG['ftp_path'] . $obj->path;
    $dele = ftp_delete($conn_id, $filewithpath);
    if (!$dele) trigger_error("Error deleting FTP file: {$filewithpath}", E_USER_WARNING);
    // remove the directory if it's not a public one
    if ($filepath != $CONFIG['ftp_path'])
    {
        $dele = ftp_delete($conn_id, $filepath);
        if (!$dele) trigger_error("Error deleting FRP folder: {$filepath}", E_USER_WARNING);
    }
}
else
{
    // delete public file
    $filewithpath = $CONFIG['ftp_path'] . $obj->filename;
    $filepath = $CONFIG['ftp_path'] . $obj->path;
    $dele = ftp_delete($conn_id, $filewithpath);
    if (!$dele) trigger_error("Error deleting FTP file: {$filewithpath}", E_USER_WARNING);
    // remove the directory if it's not a public one
    if ($filepath != $CONFIG['ftp_path'])
    {
        $dele = ftp_delete($conn_id, $filepath);
        if (!$dele) trigger_error("Error deleting FTP folder: {$filepath}", E_USER_WARNING);
    }
}
// close the FTP stream
ftp_close($conn_id);

// remove file from database
$sql = "DELETE FROM `{$dbFiles}` WHERE id='{$id}'";
mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
journal(CFG_JOURNAL_OTHER, 'FTP File Deleted', "File {$frow['filename']} was deleted from FTP", CFG_JOURNAL_PRODUCTS, 0);

html_redirect("ftp_list_files.php");
?>