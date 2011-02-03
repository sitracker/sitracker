<?php
// delete_update.php - Deletes incident updates (log entries) from the database
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


$permission = 42; // Delete Incident Updates
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
$fsdelim = (strstr($_SERVER['SCRIPT_FILENAME'],"/")) ? "/" : "\\";

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$updateid = clean_int($_REQUEST['updateid']);
$timestamp = clean_int($_REQUEST['timestamp']);
$tempid = clean_int($_REQUEST['tempid']);

if (empty($updateid)) trigger_error("!Error: Update ID was not set, not deleting!: {$updateid}", E_USER_WARNING);

$deleted_files = TRUE;
$path = $CONFIG['attachment_fspath'].'updates'.$fsdelim;

$sql = "SELECT linkcolref, filename FROM `{$dbLinks}` as l, `{$dbFiles}` as f ";
$sql .= "WHERE origcolref = '{$updateid}' ";
$sql .= "AND linktype = 5 ";
$sql .= "AND l.linkcolref = f.id ";

if ($result = @mysql_query($sql))
{
    while ($row = mysql_fetch_object($result))
    {
        $file = $path.$row->linkcolref."-".$row->filename;
        if (file_exists($file))
        {
            $del = unlink($file);
            if (!$del)
            {
                trigger_error("Deleting attachment failed", E_USER_ERROR);
                $deleted = FALSE;
            }
        }
    }
}

if ($deleted_files)
{
    // We delete using ID and timestamp to make sure we dont' delete the wrong update by accident
    $sql = "DELETE FROM `{$dbUpdates}` WHERE id='$updateid' AND timestamp='$timestamp'";  // We might in theory have more than one ...
    mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    $sql = "DELETE FROM `{$dbTempIncoming}` WHERE id='$tempid'";
    mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
}

journal(CFG_LOGGING_NORMAL, 'Incident Log Entry Deleted', "Incident Log Entry $updateid was deleted from Incident $incidentid", CFG_JOURNAL_INCIDENTS, $incidentid);
html_redirect("holding_queue.php");
?>