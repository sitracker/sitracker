<?php
// billing_unused.inc.php - functions relating to billing that are not currenly used
//                        placed here to allow for easier refactoring
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
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
 * Unreserve a reserved transaction, this removes the transaction thus removing the reservation
 * @author Paul Heaney
 * @param int $transactionid - The transaction to unreserv
 * @return bool TRUE on sucess FALSE otherwise
 */
function unreserve_monies($transactionid, $linktype)
{
    $rtnvalue = FALSE;
    $sql = "DELETE FROM `{$GLOBALS['dbTransactions']}` WHERE transactionid = {$transactionid} AND transactionstatus = ".BILLING_RESERVED;
    mysqli_query($db, $sql);

    if (mysql_error()) trigger_error("Error unreserving monies ".mysql_error(), E_USER_ERROR);
    if (mysql_affected_rows() == 1) $rtnvalue = TRUE;

    if ($rtnvalue != FALSE)
    {
        $sql = "DELETE FROM `{$GLOBALS['dbLinks']}` WHERE linktype =  {$linktype} AND origcolref = {$transactionid}";
        mysqli_query($db, $sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(),E_USER_ERROR);
            $rtnvalue = FALSE;
        }
        if (mysql_affected_rows() < 1)
        {
            trigger_error("Link deletion failed",E_USER_ERROR);
            $rtnvalue = FALSE;
        }
    }

    return $rtnvalue;
}


/**
 * Produces a HTML dropdown of all valid services for a contract
 * @author Paul Heaney
 * @param int $contractid The contract ID to report on
 * @param int $name name for the dropdown
 * @param int $selected The service ID to select
 * @return string HTML for the dropdown
 */
function service_dropdown_contract($contractid, $name, $selected=0)
{
    global $now, $CONFIG;
    $date = ldate('Y-m-d', $now);

    $sql = "SELECT * FROM `{$GLOBALS['dbService']}` WHERE contractid = {$contractid} ";
    $sql .= "AND '{$date}' BETWEEN startdate AND enddate ";
    $result = mysqli_query($db, $sql);
    if (mysql_error()) trigger_error("Error getting services. ".mysql_error(), E_USER_WARNING);

    $html = FALSE;

    if (mysql_num_rows($result) > 0)
    {
        $html = "<select name='{$name}' id={$name}>\n";
        $html .= "<option value='0' ";
        if ($selected == 0) $html .= " selected='selected' ";
        $html .= "></option>";
        while ($obj = mysqli_fetch_object($result))
        {
            $html .= "<option value='{$obj->serviceid}' ";
            if ($selected == $obj->serviceid) $html .= " selected='selected' ";
            $html .= ">{$CONFIG['currency_symbol']}".get_service_balance($obj->serviceid, TRUE, TRUE);
            $html .= " ({$obj->startdate} - {$obj->enddate})</option>";
        }
        $html .= "</select>\n";
    }

    return $html;
}


/**
 * Produces a HTML dropdown of all valid services for a site
 * @author Paul Heaney
 * @param int $contractid The contract ID to report on
 * @param int $name name for the dropdown
 * @param int $selected The service ID to select
 * @return string HTML for the dropdown
 */
function service_dropdown_site($siteid, $name, $selected=0)
{
    global $now, $CONFIG;
    $date = ldate('Y-m-d', $now);

    $sql = "SELECT s.* FROM `{$GLOBALS['dbService']}` AS s, `{$GLOBALS['dbMaintenance']}` AS m ";
    $sql .= "WHERE s.contractid = m.id AND  m.site = {$siteid} ";
    $sql .= "AND '{$date}' BETWEEN startdate AND enddate ";
    $result = mysqli_query($db, $sql);
    if (mysql_error()) trigger_error("Error getting services. ".mysql_error(), E_USER_WARNING);

    $html = FALSE;

    if (mysql_num_rows($result) > 0)
    {
        $html = "<select name='{$name}' id={$name}>\n";
        $html .= "<option value='0' ";
        if ($selected == 0) $html .= " selected='selected' ";
        $html .= "></option>";
        while ($obj = mysqli_fetch_object($result))
        {
            $html .= "<option value='{$obj->serviceid}' ";
            if ($selected == $obj->serviceid) $html .= " selected='selected' ";
            $html .= ">{$CONFIG['currency_symbol']}".get_service_balance($obj->serviceid, TRUE, TRUE);
            $html .= " ({$obj->startdate} - {$obj->enddate})</option>";
        }
        $html .= "</select>\n";
    }
    else
    {
        $html = "No services currently valid";
    }

    return $html;
}


/**
 * Identify if a transaction has been approved or not
 * @author Paul Heaney
 * @param int $transactionid The transaction ID to check
 * @return bool TRUE if approved FALSE otherwise
 */
function is_transaction_approved($transactionid)
{
    $sql = "SELECT transactionid FROM `{$GLOBALS['dbTransactions']}` WHERE transactionid = {$transactionid} AND transactionstaus = ".BILLING_APPROVED;
    $result = mysqli_query($db, $sql);
    if (mysql_error()) trigger_error("Error getting services. ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0) return TRUE;
    else return FALSE;
}
