<?php
// journal.inc.php - functions relating to the journal
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


/**
 * Inserts an entry into the Journal table and marks the user online
 * @author Ivan Lucas, Kieran Hogg
 * @param int $loglevel The log level required for this event to be logged
 * @param string $event Text title for the event
 * @param string $bodytext Text describing the event in detail
 * @param int $journaltype One of the defined journal types
 * @param int $refid An ID to relate to data, the table this ID is for
 depends on the journal type used
 * @return TRUE success, entry logged, FALSE failure. entry not logged
 * @note Produces an audit log
 */
function journal($loglevel, $event, $bodytext, $journaltype, $refid)
{
    global $CONFIG, $sit, $dbJournal;
    // Journal Types
    // 1 = Logon/Logoff
    // 2 = Support Incidents
    // 3 = -Unused-
    // 4 = Sites
    // 5 = Contacts
    // 6 = Admin
    // 7 = User Management

    // Logging Level
    // 0 = No logging
    // 1 = Minimal Logging
    // 2 = Normal Logging
    // 3 = Full Logging
    // 4 = Max Debug Logging

    $bodytext = mysql_real_escape_string($bodytext);
    if ($loglevel <= $CONFIG['journal_loglevel'])
    {
        $sql  = "INSERT INTO `{$dbJournal}` ";
        $sql .= "(userid, event, bodytext, journaltype, refid) ";
        $sql .= "VALUES ('{$_SESSION['userid']}', '{$event}', '{$bodytext}', '{$journaltype}', '{$refid}') ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        return TRUE;
    }
    else
    {
        // Below minimum log level - do nothing
        return FALSE;
    }
}
