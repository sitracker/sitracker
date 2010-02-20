<?php
// contract.inc.php - functions relating to contracts
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

require_once (APPLICATION_LIBPATH . 'base.inc.php');

/**
 * Picks a 'best' contract for a contact
 *
 * The function is limited in its usefulness, it will only work if you either
 * have just one contract, or just one preferred contract.
 * @author Kieran Hogg
 * @param int $contactid the ID of the contact to find the contract for
 * @return int|bool returns either the ID of the contract or FALSE if none
 */
function guess_contract_id($contactid)
{
    global $dbSupportContacts;

    $contactid = intval($contactid);
    $sql = "SELECT * FROM `{$dbSupportContacts}` ";
    $sql .= "WHERE contactid = '{$contactid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

    $num_contracts = mysql_num_rows($result);

    if ($num_contracts == 0)
    {
        $contractid = FALSE;
    }
    elseif ($num_contracts == 1)
    {
        $row = mysql_fetch_object($result);
        $contractid = $row->id;
    }
    else
    {
        //to complete as a programming exercise
    }

    return $contractid;
}

?>
