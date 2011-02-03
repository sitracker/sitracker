<?php
// ftp.inc.php - functions relating to FTP
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
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
 * Function to return a logged in ftp connection
 * @author Ivan Lucas
 */
function create_ftp_connection()
{
    global $CONFIG;

    $conn_id = ftp_connect($CONFIG['ftp_hostname']);

    // login with username and password
    $login_result = ftp_login($conn_id, $CONFIG['ftp_username'], $CONFIG['ftp_password']);

    // check connection
    if ((!$conn_id) || (!$login_result))
    {
        trigger_error("FTP Connection failed, connecting to {$CONFIG['ftp_hostname']} for user {$CONFIG['ftp_hostname']}}", E_USER_WARNING);
    }
    else
    {
        echo "Connected to {$CONFIG['ftp_hostname']}, for user {$CONFIG['ftp_username']}<br />";
    }

    return $conn_id;
}


?>