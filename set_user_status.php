<?php
// set_user_status.php - Change the users status
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


$permission = 35;  // Set your status

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$mode = cleanvar($_REQUEST['mode']);
$userstatus = cleanvar($_REQUEST['userstatus']);
$accepting = cleanvar($_REQUEST['accepting']);
$incidentid = cleanvar($_REQUEST['incidentid']);
$originalowner = cleanvar($_REQUEST['originalowner']);

switch ($mode)
{
    case 'setstatus':
        $sql  = "UPDATE `{$dbUsers}` SET status='$userstatus'";

        // NOTE: do not translate thise 'Yes' 'No' strings below

switch ($userstatus)
        {
            case 1: // in office
                $accepting='Yes';
            break;

            case 2: // Not in office
                $accepting='No';
            break;

            case 3: // In Meeting
                // don't change
                $accepting='';
            break;

            case 4: // At Lunch
                $accepting='';
            break;

            case 5: // On Holiday
                $accepting='No';
            break;

            case 6: // Working from home
                $accepting='Yes';
            break;

            case 7: // On training course
                $accepting='No';
            break;

            case 8: // Absent Sick
                $accepting='No';
            break;

            case 9: // Working Away
                // don't change
                $accepting='';
            break;
        }
        if (!empty($accepting)) $sql.=", accepting='$accepting'";
        $sql .= " WHERE id='$sit[2]' LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

        incident_backup_switchover($sit[2], $accepting);

        trigger("TRIGGER_USER_CHANGED_STATUS", array('userid' => $sit[2]));

        header('Location: index.php');
    break;

    case 'setaccepting':
        $sql  = "UPDATE `{$dbUsers}` SET accepting='$accepting' ";
        $sql .= "WHERE id='$sit[2]' LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        header('Location: index.php');
    break;


    case 'return': // dummy entry, just returns user back
        header('Location: index.php');
    break;

    case 'editprofile':
    header('Location: user_profile_edit.php');
    break;

    case 'deleteassign':
        // this may not be the very best place for this functionality but it's all i could find - inl 19jan05
        // hide a record from tempassign as requested by clicking 'ignore' in the holding queue
        $sql = "UPDATE `{$dbTempAssigns}` SET assigned='yes' WHERE incidentid='{$incidentid}' AND originalowner='{$originalowner}' LIMIT 1";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        header("Location: holding_queue.php");
        exit;
    break;
}
?>