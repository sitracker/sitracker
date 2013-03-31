<?php
// billing.inc.php - functions relating to billing
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

require_once (APPLICATION_LIBPATH . 'billing.class.php');

define ("BILLING_APPROVED", 0);
define ("BILLING_AWAITINGAPPROVAL", 5);
define ("BILLING_RESERVED", 10);

define ("BILLING_TYPE_UNIT", 'unit');
define ("BILLING_TYPE_INCIDENT", 'incident');

/**
 * Returns if the contact has a timed contract or if the site does in the case of the contact not.
 * @author Paul Heaney
 * @param int $contactid
 * @return either NO_BILLABLE_CONTRACT, CONTACT_HAS_BILLABLE_CONTRACT or SITE_HAS_BILLABLE_CONTRACT the latter is if the site has a billable contract by the contact isn't a named contact
 */
function does_contact_have_billable_contract($contactid)
{
    global $now;
    $return = NO_BILLABLE_CONTRACT;

    $siteid = contact_siteid($contactid);
    $sql = "SELECT DISTINCT m.id FROM `{$GLOBALS['dbMaintenance']}` AS m, `{$GLOBALS['dbServiceLevels']}` AS sl ";
    $sql .= "WHERE m.servicelevel = sl.tag AND sl.timed = 'yes' AND m.site = {$siteid} ";
    $sql .= "AND m.expirydate > {$now} AND m.term != 'yes'";
    $result = mysql_query($sql);

    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        // We have some billable/timed contracts
        $return = SITE_HAS_BILLABLE_CONTRACT;

        // check if the contact is listed on one of these

        while ($obj = mysql_fetch_object($result))
        {
            $sqlcontact = "SELECT * FROM `{$GLOBALS['dbSupportContacts']}` ";
            $sqlcontact .= "WHERE maintenanceid = {$obj->id} AND contactid = {$contactid}";

            $resultcontact = mysql_query($sqlcontact);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            if (mysql_num_rows($resultcontact) > 0)
            {
                $return = CONTACT_HAS_BILLABLE_CONTRACT;
                break;
            }
        }
    }

    return $return;
}


/**
 * Gets the billable contract ID for a contact, if multiple exist then the first one is choosen
 * @author Paul Heaney
 * @param int $contactid - The contact ID you want to find the contract for
 * @return int the ID of the contract, -1 if not found
 */
function get_billable_contract_id($contactid)
{
    global $now;

    $return = -1;

    $siteid = contact_siteid($contactid);
    $sql = "SELECT DISTINCT m.id FROM `{$GLOBALS['dbMaintenance']}` AS m, `{$GLOBALS['dbServiceLevels']}` AS sl ";
    $sql .= "WHERE m.servicelevel = sl.tag AND sl.timed = 'yes' AND m.site = {$siteid} ";
    $sql .= "AND m.expirydate > {$now} AND m.term != 'yes'";

    $result = mysql_query($sql);

    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $return = mysql_fetch_object($result)->id;
    }

    return $return;
}


/**
 * Gte sthe billable contract ID for a site, if multiple exist then the first one is choosen
 * @author Paul Heaney
 * @param int $siteid - The site ID you want to find the contract for
 * @return int the ID of the contract, -1 if not found
 */
function get_site_billable_contract_id($siteid)
{
    global $now;

    $return = -1;

    $sql = "SELECT DISTINCT m.id FROM `{$GLOBALS['dbMaintenance']}` AS m, `{$GLOBALS['dbServiceLevels']}` AS sl ";
    $sql .= "WHERE m.servicelevel = sl.tag AND sl.timed = 'yes' AND m.site = {$siteid} ";
    $sql .= "AND m.expirydate > {$now} AND m.term != 'yes'";

    $result = mysql_query($sql);

    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $return = mysql_fetch_object($result)->id;
    }

    return $return;
}


/**
 * Returns the percentage remaining for ALL services on a contract
 * @author Kieran Hogg
 * @param int $mainid - contract ID
 * @return mixed - percentage between 0 and 1 if services, FALSE if not
 */
function get_service_percentage($maintid)
{
    global $dbService;

    $sql = "SELECT * FROM `{$dbService}` ";
    $sql .= "WHERE contractid = {$maintid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $total = 0;
        $num = 0;
        while ($service = mysql_fetch_object($result))
        {

            if (((float) $service->balance > 0) OR ((float) $service->creditamount > 0))
            {
                $total += (float) $service->balance / (float) $service->creditamount;
            }
            $num++;
        }
        $return = (float) $total / (float) $num;
    }
    else
    {
    	$return = FALSE;
    }

    return $return;
}


/**
 * Does a contract have a service level which is timed / billed
 * @author Ivan Lucas
 * @param int $contractid
 * @return Whether the contract should be billed
 * @return bool TRUE: Yes timed. should be billed, FALSE: No, not timed. Should not be billed
 */
function is_contract_timed($contractid)
{
    global $dbMaintenance, $dbServiceLevels;
    $timed = FALSE;
    $sql = "SELECT timed FROM `{$dbMaintenance}` AS m, `{$dbServiceLevels}` AS sl ";
    $sql .= "WHERE m.servicelevel = sl.tag AND m.id = {$contractid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    list($timed) = mysql_fetch_row($result);
    if ($timed == 'yes')
    {
        return TRUE;
    }
    else
    {
        return FALSE;
    }
}


/**
 * Set the last billing time on a service
 * @param int $serviceid - service ID
 * @param string $date -  Date (in format YYYY-MM-DD) to set the last billing time to
 * @return boolean - TRUE if sucessfully updated, false otherwise
 */
function update_last_billed_time($serviceid, $date)
{
    global $dbService;

    $rtnvalue = FALSE;

    if (!empty($serviceid) AND !empty($date))
    {
        $rtnvalue = TRUE;
        $sql .= "UPDATE `{$dbService}` SET lastbilled = '{$date}' WHERE serviceid = {$serviceid}";
        mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(), E_USER_ERROR);
            $rtnvalue = FALSE;
        }

        if (mysql_affected_rows() < 1)
        {
            trigger_error("Approval failed", E_USER_ERROR);
            $rtnvalue = FALSE;
        }
    }

    return $rtnvalue;
}


/**
 * Function to find the most applicable unit rate for a particular contract
 * @author Paul Heaney
 * @param int $contractid - The contract id
 * @param string $date UNIX timestamp. The function will look for service that is current as of this timestamp
 * @return int the unit rate, -1 if non found
 */
function get_unit_rate($contractid, $date='')
{
    $serviceid = get_serviceid($contractid, $date);

    if ($serviceid != -1)
    {
        $unitrate = get_service_unitrate($serviceid);
    }
    else
    {
        $unitrate = -1;
    }

    return $unitrate;
}


/**
 * Returns the unit rate for a service
 * @author Paul Heaney
 * @param int $serviceid - The serviceID to get the unit rate for
 * @return mixed FALSE if no service found else the unit rate
 */
function get_service_unitrate($serviceid)
{
    $rtnvalue = FALSE;
	$sql = "SELECT rate FROM `{$GLOBALS['dbService']}` AS p WHERE serviceid = {$serviceid}";

    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_WARNING);
        return FALSE;
    }

    if (mysql_num_rows($result) > 0)
    {
        list($rtnvalue) = mysql_fetch_row($result);
    }

    return $rtnvalue;
}


/**
 * @author Paul Heaney
 * @param int $contractid  The Contract ID
 * @param int $date  UNIX timestamp. The function will look for service that is current as of this timestamp
 * @return mixed.     Service ID, or -1 if not found, or FALSE on error
 */
function get_serviceid($contractid, $date = '')
{
    global $now, $CONFIG;
    if (empty($date)) $date = $now;

    $sql = "SELECT serviceid FROM `{$GLOBALS['dbService']}` AS s ";
    $sql .= "WHERE contractid = {$contractid} AND UNIX_TIMESTAMP(startdate) <= {$date} ";
    $sql .= "AND UNIX_TIMESTAMP(enddate) > {$date} ";
    $sql .= "AND (balance > 0 OR (select count(1) FROM `{$GLOBALS['dbService']}` WHERE contractid = s.contractid AND balance > 0) = 0) ";

    if (!$CONFIG['billing_allow_incident_approval_against_overdrawn_service'])
    {
        $sql .= "AND balance > 0 ";
    }

    $sql .= "ORDER BY priority DESC, enddate ASC, balance DESC LIMIT 1";

    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_WARNING);
        return FALSE;
    }

    $serviceid = -1;

    if (mysql_num_rows($result) > 0)
    {
        list($serviceid) = mysql_fetch_row($result);
    }

    return $serviceid;
}


/**
 * Get the current contract balance
 * @author Ivan Lucas
 * @param int $contractid. Contract ID of the contract to credit
 * @param bool $includenonapproved. Include incidents which have not been approved
 * @param bool $showonlycurrentlyvalue - Show only contracts which have valid NOW() - i.e. startdate less than NOW() and endate greate than NOW()
 * @param bool $includereserved - Deduct the reseved amount from the returned balance
 * @return int The total balance remaining on the contract
 * @note The balance is a sum of all the current service that have remaining balance
 * @todo add a param that makes this optionally show the incident pool balance
    in the case of non-timed type contracts
 */
function get_contract_balance($contractid, $includenonapproved = FALSE, $showonlycurrentlyvalid = TRUE, $includereserved = TRUE)
{
    global $dbService, $now;
    $balance = 0.00;

    $sql = "SELECT SUM(balance) FROM `{$dbService}` ";
    $sql .= "WHERE contractid = {$contractid} ";
    if ($showonlycurrentlyvalid)
    {
        $date = ldate('Y-m-d', $now);
        $sql .= "AND '{$date}' BETWEEN startdate AND enddate ";
    }
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    list($balance) = mysql_fetch_row($result);

    if ($includenonapproved)
    {
        // Need to get sum of non approved incidents for this contract and deduct
        $balance += contract_transaction_total($contractid, BILLING_AWAITINGAPPROVAL);
    }

    if ($includereserved)
    {
        $balance += contract_transaction_total($contractid, BILLING_RESERVED);
    }

    return $balance;
}


/**
 * Updates the amount and optionally the description on a transaction awaiting reservation
 * @author Paul Heaney
 * @param int $transactionid The transaction ID to update
 * @param int $amount The amount to set the transaction to
 * @param string $description (optional) the description to set on the transaction
 * @return bool TRUE on a sucessful update FALSE otherwise
 */
function update_reservation($transactionid, $amount, $description='')
{
    return update_transaction($transactionid, $amount, $description, BILLING_RESERVED);
}


/**
 * Updates a transacction that is either waiting approval or reserved
 * @author Paul Heaney
 * @param int $transactionid The transaction ID to update
 * @param float $amount The amount to set the transaction to
 * @param string $description (optional) the description to set on the transaction
 * @param int $status either BILLING_RESERVED or BILLING_AWAITINGAPPROVAL
 * @return bool TRUE on a sucessful update FALSE otherwise
 */
function update_transaction($transactionid, $amount = 0.00, $description = '', $status = BILLING_AWAITINGAPPROVAL)
{
    if ($status == BILLING_APPROVED)
    {
        trigger_error("You cant change a approved transaction", E_USER_ERROR);
        exit;
    }

    $rtnvalue = FALSE;
    // Note we dont need to check its awaiting reservation as we check this when doing the update
    if (is_numeric($transactionid))
    {
        $sql = "UPDATE `{$GLOBALS['dbTransactions']}` SET amount = '{$amount}' ";
        if (!empty($description))
        {
            $sql .= ", description = '{$description}' ";
        }
        $sql .= "WHERE transactionid = {$transactionid} AND transactionstatus = {$status}";
        mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(),E_USER_ERROR);
            $rtnvalue = FALSE;
        }
        if (mysql_affected_rows() > 0)
        {
            $rtnvalue = TRUE;
        }
    }

    return $rtnvalue;
}


/**
 * Do the necessary tasks to billable incidents on closure, including creating transactions
 * @author Paul Heaney
 * @param int $incidentid The incident ID to do the close on, if its not a billable incident then no actions are performed
 * @return bool TRUE on sucessful closure, false otherwise
 */
function close_billable_incident($incidentid)
{
    $toReturn = FALSE;
    $billableincident = get_billable_object_from_incident_id($incidentid);
    if ($billableincident)
    {
        $toReturn = $billableincident->close_incident($incidentid);
    }
    
    return $toReturn;
}



/**
 * Function to return a billable incident object based on incident ID
 * @author Paul Heaney
 * @param int $incidentid The incident ID to get a billable incident for
 * @return mixed Billable if incident is billable else FALSE
 * @todo This may be removed following the billable code refactor
 */
function get_billable_object_from_incident_id($incidentid)
{
    $toReturn = FALSE;
    
    $sql = "SELECT m.billingtype FROM `{$GLOBALS['dbMaintenance']}` AS m, `{$GLOBALS['dbIncidents']}` AS i WHERE i.maintenanceid = m.id AND i.id = {$incidentid}";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("Error finding type of incident billing ".mysql_error(), E_USER_WARNING);
        $toReturn = FALSE;
    }
    
    if (mysql_num_rows($result) > 0)
    {
        list($billingtype) = mysql_fetch_row($result);
        $toReturn = get_billable_incident_object($billingtype);  
    }
    
    return $toReturn;
}


/**
 * Function to return a billable incident object based on contract  ID
 * @author Paul Heaney
 * @param int $incidentid The contract ID to get a object for
 * @return mixed Billable if incident is billable else FALSE
 * @todo This may be removed following the billable code refactor
 */
function get_billable_object_from_contract_id($contractid)
{
    $toReturn = FALSE;

    $sql = "SELECT m.billingtype FROM `{$GLOBALS['dbMaintenance']}` AS m WHERE m.id = {$contractid}";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("Error finding type of contract billing ".mysql_error(), E_USER_WARNING);
        $toReturn = FALSE;
    }

    if (mysql_num_rows($result) > 0)
    {
        list($billingtype) = mysql_fetch_row($result);
        $toReturn = get_billable_incident_object($billingtype);
    }

    return $toReturn;
}


/**
 * Returns a billable object given a particular billing type
 * @author Paul Heaney
 * @param String $billingtype The billing type to return an object off
 * @return mixed Billable if incident is billable else FALSE
 * @todo This may be removed following the billable code refactor
 */
function get_billable_incident_object($billingtype)
{
    $toReturn = FALSE;

    switch ($billingtype)
    {
        case 'unit':
            $toReturn = new UnitBillable();
            break;
        case 'incident':
            $toReturn = new IncidentBillable();
            break;
        case '':
            // Its not a billable incident
            $toReturn = FALSE;
            break;
        default:
            $toReturn = FALSE;
            trigger_error("Unknown billable type of '{$billingtype}'");
    }
    
    return $toReturn;
}

/**
 * Function to approve an incident, this adds a transaction and confirms the 'bill' is correct.
 * @author Paul Heaney
 * @param int incidentid ID of the incident to approve
 */
function approve_incident_transaction($transactionid)
{
    $rtnvalue = FALSE;;

    $sql = "SELECT l.linkcolref FROM `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbTransactions']}` AS t ";
    $sql .= "WHERE t.transactionid = l.origcolref AND t.transactionstatus = ".BILLING_AWAITINGAPPROVAL." AND l.linktype = 6 AND t.transactionid = {$transactionid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error identify incident transaction. ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        list($incidentid) = mysql_fetch_row($result);
        
        $billable = get_billable_object_from_incident_id($incidentid);
        $rtnvalue = $billable->approve_incident_transaction($transactionid);
    }
    
    return $rtnvalue;
}


/**
 * Update contract balance by an amount and log a transaction to record the change
 * @author Ivan Lucas
 * @param int $contractid. Contract ID of the contract to credit
 * @param string $description. A useful description of the transaction
 * @param float $amount. The amount to credit or debit to the contract balance
                    positive for credit and negative for debit
 * @param int $serviceid.    optional serviceid to use. This is calculated if ommitted.
 * @param int $transaction - the transaction you are approving
 * @param int $totalunits - The number of units that are being approved - before the multiplier
 * @param int $totalbillableunits - The number of units charged to the customer (after the multiplier)
 * @param int $totalrefunds - Total number of units refunded to the customer
 * @return boolean - status of the balance update
 * @note The actual service to credit will be calculated automatically if not specified
 */
function update_contract_balance($contractid, $description, $amount, $serviceid='', $transactionid='', $totalunits=0, $totalbillableunits=0, $totalrefunds=0)
{
    global $now, $dbService, $dbTransactions;
    $rtnvalue = TRUE;

    if (empty($totalunits)) $totalunits = -1;
    if (empty($totalbillableunits)) $totalbillableunits = -1;
    if (empty($totalrefunds)) $totalrefunds = 0;

    if ($serviceid == '')
    {
        // Find the correct service record to update
        $serviceid = get_serviceid($contractid);
        if ($serviceid < 1) trigger_error("Invalid service ID", E_USER_ERROR);
    }

    if (trim($amount) == '') $amount = 0;
    $date = date('Y-m-d H:i:s', $now);

    // Update the balance
    $sql = "UPDATE `{$dbService}` SET balance = (balance + {$amount}) WHERE serviceid = '{$serviceid}' LIMIT 1";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_ERROR);
        $rtnvalue = FALSE;
    }

    if (mysql_affected_rows() < 1 AND $amount != 0)
    {
        trigger_error("Contract balance update failed", E_USER_ERROR);
        $rtnvalue = FALSE;
    }

    if ($rtnvalue != FALSE)
    {
        // Log the transaction
        if (empty($transactionid))
        {
            $sql = "INSERT INTO `{$dbTransactions}` (serviceid, totalunits, totalbillableunits, totalrefunds, amount, description, userid, dateupdated, transactionstatus) ";
            $sql .= "VALUES ('{$serviceid}', '{$totalunits}', '{$totalbillableunits}', '{$totalrefunds}', '{$amount}', '{$description}', '{$_SESSION['userid']}', '{$date}', '".BILLING_APPROVED."')";
            $result = mysql_query($sql);

            $rtnvalue = mysql_insert_id();
        }
        else
        {
            $sql = "UPDATE `{$dbTransactions}` SET serviceid = {$serviceid}, totalunits = {$totalunits}, totalbillableunits = {$totalbillableunits}, totalrefunds = {$totalrefunds} ";
            $sql .= ", amount = {$amount}, userid = {$_SESSION['userid']} , dateupdated = '{$date}', transactionstatus = '".BILLING_APPROVED."' ";
            if (!empty($description))
            {
            	$sql .= ", description = '{$description}' ";
            }
            $sql .= "WHERE transactionid = {$transactionid}";
            $result = mysql_query($sql);
            $rtnvalue = $transactionid;
        }

        if (mysql_error())
        {
            trigger_error(mysql_error(), E_USER_ERROR);
            $rtnvalue = FALSE;
        }
        if (mysql_affected_rows() < 1)
        {
            trigger_error("Transaction insert failed", E_USER_ERROR);
            $rtnvalue = FALSE;
        }
    }

    return $rtnvalue;
}


/**
 * Gets the maintenanceID for a incident transaction
 * @author Paul Heaney
 * @param int $transactionid The transaction ID to get the maintenance id from
 * @return int The maintenanceid or -1
 */
function maintid_from_transaction($transactionid)
{
    $rtnvalue = -1;
    $sql = "SELECT i.maintenanceid FROM `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbIncidents']}` AS i WHERE ";
    $sql .= "l.origcolref = {$transactionid} AND l.linkcolref = i.id AND l.linktype = 6";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error getting maintid for transaction. ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
    	list($rtnvalue) = mysql_fetch_row($result);
    }

    return $rtnvalue;
}


/**
 * Returns the total value of incidents in a particular status
 * @author Paul Heaney
 * @param int $contractid. Contract ID of the contract to find total value of inicdents awaiting approval
 * @param int $status The type you are after e.g. BILLING_AWAITINGAPPROVAL, BILLING_APPROVED, BILLING_RESERVED
 * @return int The total value of all incidents awaiting approval logged against the contract
 */
function contract_transaction_total($contractid, $status)
{
    $rtnvalue = FALSE;

    $sql = "SELECT SUM(t.amount) FROM `{$GLOBALS['dbTransactions']}` AS t, `{$GLOBALS['dbService']}` AS s ";
    $sql .= "WHERE s.serviceid = t.serviceid AND s.contractid = {$contractid} AND t.transactionstatus = '{$status}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error getting total for type {$status}. ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        list($rtnvalue) = mysql_fetch_row($result);
    }

    return $rtnvalue;
}


/**
 * Get the total of all transactions on a particular service of a certain type
 * @author Paul Heaney
 * @param int $serviceid The serviceID to report on
 * @param int $status The status' to get the transaction report for'
 * @return int The sum in currency of the transactons
 */
function service_transaction_total($serviceid, $status)
{
    $rtnvalue = FALSE;
    $sql = "SELECT SUM(amount) FROM `{$GLOBALS['dbTransactions']}` ";
    $sql .= "WHERE serviceid = {$serviceid} AND transactionstatus = '{$status}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error getting total for type {$status}. ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        list($rtnvalue) = mysql_fetch_row($result);
    }
    return $rtnvalue;
}


/**
 * Get the current balance of a service
 * @author Paul Heaney
 * @param int $serviceid. Service ID of the service to get the balance for
 * @param int $includeawaitingapproval. Deduct the total awaiting approval from the balance
 * @param int $includereserved. Deduct the total reserved from the balance
 * @return int The remaining balance on the service
 * @todo Add param to take into account unapproved balances
 */
function get_service_balance($serviceid, $includeawaitingapproval = TRUE, $includereserved = TRUE)
{
    global $dbService;

    $balance = FALSE;

    $sql = "SELECT balance FROM `{$dbService}` WHERE serviceid = {$serviceid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) == 1)
    {
        list($balance) = mysql_fetch_row($result);
        if ($includeawaitingapproval)
        {
        	$balance += service_transaction_total($serviceid, BILLING_AWAITINGAPPROVAL);
        }

        if ($includereserved)
        {
        	$balance += service_transaction_total($serviceid, BILLING_RESERVED);
        }
    }
    return $balance;
}


/**
 * Function to identify if incident has been approved for billing
 * @author Paul Heaney
 * @return TRUE for approved, FALSE otherwise
 */
function is_billable_incident_approved($incidentid)
{
    $sql = "SELECT DISTINCT origcolref, linkcolref ";
    $sql .= "FROM `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbTransactions']}` AS t ";
    $sql .= "WHERE l.linktype = 6 ";
    $sql .= "AND l.origcolref = t.transactionid ";
    $sql .= "AND linkcolref = {$incidentid} ";
    $sql .= "AND direction = 'left' ";
    $sql .= "AND t.transactionstatus = '".BILLING_APPROVED."'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0) return TRUE;
    else return FALSE;
}


/**
 * Gets the transactionID for an incident
 * @author paulh Paul Heaney
 * @param int $incidentid The incidentID
 * @return mixed the transactionID or FALSE if not found;
 */
function get_incident_transactionid($incidentid)
{
    $rtnvalue = FALSE;
    $sql = "SELECT origcolref ";
    $sql .= "FROM `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbTransactions']}` AS t ";
    $sql .= "WHERE l.linktype = 6 ";
    $sql .= "AND l.origcolref = t.transactionid ";
    $sql .= "AND linkcolref = {$incidentid} ";
    $sql .= "AND direction = 'left' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
    	list($rtnvalue) = mysql_fetch_row($result);
    }

    return $rtnvalue;
}


/**
 * HTML table showing a summary of current contract service periods
 * @author Ivan Lucas
 * @param int $contractid. Contract ID of the contract to show service for
 * @param bool $billing. Show billing info when TRUE, hide it when FALSE
 * @return string. HTML table
 */
function contract_service_table($contractid, $billing)
{
    global $CONFIG, $dbService, $dbMaintenance, $now;

    $sql = "SELECT * FROM `{$dbService}` WHERE contractid = {$contractid} ORDER BY enddate DESC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        $shade = 'shade1';
        $html = "\n<table class='maintable' id='contractservicetable'>";
        $html .= "<tr>";
        if ($billing) $html .= "<th></th>";
        $html .= "<th>{$GLOBALS['strStartDate']}</th><th>{$GLOBALS['strEndDate']}</th>";
        if ($billing)
        {
            $html .= "<th>{$GLOBALS['strAvailableBalance']}</th>";
        }
        $html .= "<th>{$GLOBALS['strActions']}</th>";
        $html .= "</tr>\n";
        while ($service = mysql_fetch_object($result))
        {
            $service->startdate = mysql2date($service->startdate . ' 00:00');
            $service->enddate = mysql2date($service->enddate . ' 23:59');
            $service->lastbilled = mysql2date($service->lastbilled);

            $expired = false;
            $future = false;
            if ($service->enddate < $now) $expired = true;
            if ($service->startdate > $now) $future = true;

            if ($future)
            {
                $shade = 'notice';
            }
            elseif ($expired)
            {
                $shade = 'expired';
            }
            $html .= "<tr class='{$shade}'>";

            if ($billing)
            {
                $balance = get_service_balance($service->serviceid);
                $awaitingapproval = service_transaction_total($service->serviceid, BILLING_AWAITINGAPPROVAL) * -1;
                $reserved = service_transaction_total($service->serviceid, BILLING_RESERVED) * -1;

                $span = "<strong>{$GLOBALS['strServiceID']}:</strong> {$service->serviceid}<br />";
                if (!empty($service->title))
                {
                    $span .= "<strong>{$GLOBALS['strTitle']}</strong>: {$service->title}<br />";
                }

                if (!empty($service->notes))
                {
                    $span .= "<strong>{$GLOBALS['strNotes']}</strong>: {$service->notes}<br />";
                }

                if (!empty($service->cust_ref))
                {
                    $span .= "<strong>{$GLOBALS['strCustomerReference']}</strong>: {$service->cust_ref}";
                    if ($service->cust_ref_date != "1970-01-01")
                    {
                        $span .= " - <strong>{$GLOBALS['strCustomerReferenceDate']}</strong>: {$service->cust_ref_date}";
                    }
                    $span .= "<br />";
                }


                $span .= "<strong>{$GLOBALS['strBilling']}</strong>: ";
                if (!empty($service->unitrate) AND $service->unitrate > 0)
                {
                    $span .= $GLOBALS['strPerUnit'];
                }
                else
                {
                    $span .= $GLOBALS['strPerIncident'];
                }
                $span .= "<br />";

                if ($service->creditamount != 0)
                {
                    $span .= "<strong>{$GLOBALS['strCreditAmount']}</strong>: {$CONFIG['currency_symbol']}".number_format($service->creditamount, 2)."<br />";
                }

                if ($service->rate != 0)
                {
                    $span .= "<strong>{$GLOBALS['strUnitRate']}</strong>: {$CONFIG['currency_symbol']}{$service->rate}<br />";
                }

                $sql1 = "SELECT billingmatrix FROM `{$dbMaintenance}` WHERE id = {$contractid}";
                $result1 = mysql_query($sql1);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                $maintenanceobj = mysql_fetch_object($result1);
                
                $span .= "<strong>{$GLOBALS['strBillingMatrix']}</string>: {$maintenanceobj->billingmatrix}<br />";

                if ($balance != $service->balance)
                {
                    $span .= "<strong>{$GLOBALS['strBalance']}</strong>: {$CONFIG['currency_symbol']}".number_format($service->balance, 2)."<br />";
                    if ($awaitingapproval != FALSE)
                    {
                        $span .= "<strong>{$GLOBALS['strAwaitingApproval']}</strong>: {$CONFIG['currency_symbol']}".number_format($awaitingapproval, 2)."<br />";
                    }

                    if ($reserved != FALSE)
                    {
                        $span .= "<strong>{$GLOBALS['strReserved']}</strong>: {$CONFIG['currency_symbol']}".number_format($reserved, 2)."<br />";
                    }

                    $span .= "<strong>{$GLOBALS['strAvailableBalance']}</strong>: ";
                    if (!$expired)
                    {
                        $span .= "{$CONFIG['currency_symbol']}".number_format($balance, 2);
                    }
                    else
                    {
                        $span .= $GLOBALS['strExpired'];
                    }
                    $span .= "<br />";
                }

                if ($service->lastbilled > 0)
                {
                    $span .= "<strong>{$GLOBALS['strLastBilled']}</strong>: ".ldate($CONFIG['dateformat_date'], $service->lastbilled)."<br />";
                }

                if ($service->foc == 'yes')
                {
                    $span .= "<strong>{$GLOBALS['strFreeOfCharge']}</strong>";
                }

                $html .= "<td><a name='billingicon' class='info'>".icon('billing', 16);
                if (!empty($span))
                {
                        $html .= "<span>{$span}</span>";
                }
                $html .= "</a></td>";
                $html .= "<td><a href='transactions.php?serviceid={$service->serviceid}' class='info'>".ldate($CONFIG['dateformat_date'], $service->startdate);
                if (!empty($span))
                {
                        $html .= "<span>{$span}</span>";
                }
                $html .= "</a></td>";
            }
            else
            {
                $html .= "<td>".ldate($CONFIG['dateformat_date'],$service->startdate);
                $html .= "</td>";
            }
            $html .= "<td>";
            $html .= ldate($CONFIG['dateformat_date'], $service->enddate)."</td>";

            if ($billing)
            {
                $html .= "<td>{$CONFIG['currency_symbol']}";
                if (!$expired) $html .= number_format($balance, 2);
                else $html .= "0";
                $html .= "</td>";
            }

            $html .= "<td>";
            $operations[$GLOBALS['strEditService']] = array('url' => "contract_edit_service.php?mode=editservice&amp;serviceid={$service->serviceid}&amp;contractid={$contractid}", 'perm' => PERM_SERVICE_EDIT);
            if ($billing)
            {
                $operations[$GLOBALS['strEditBalance']] = array('url' => "contract_edit_service.php?mode=showform&amp;sourceservice={$service->serviceid}&amp;contractid={$contractid}", 'perm' => PERM_SERVICE_BALANCE_EDIT);
                $operations[$GLOBALS['strViewTransactions']] = array('url' => "transactions.php?serviceid={$service->serviceid}", 'perm' => PERM_BILLING_TRANSACTION_VIEW);
            }
            $html .= html_action_links($operations);
            $html .= "</td></tr>\n";

            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        $html .= "</table>\n";
    }
    return $html;
}


/**
 * Returns the amount of billable units used for a site with the option of filtering by date
 * @author Paul Heaney
 * @param int $siteid The siteid to report on
 * @param int $startdate unixtimestamp on the start date to filter by
 * @param int $enddate unixtimestamp on the end date to filter by
 * @return String describing billable units used
 **/
function amount_used_site($siteid, $startdate=0, $enddate=0)
{
    $sql = "SELECT i.id, m.billingtype FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbMaintenance']}` AS m ";
    $sql .= "WHERE m.id = i.maintenanceid AND m.site = {$siteid} ";
    if ($startdate != 0)
    {
        $sql .= "AND i.closed >= {$startdate} ";
    }

    if ($enddate != 0)
    {
        $sql .= "AND i.closed <= {$enddate} ";
    }

    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_WARNING);
        return FALSE;
    }

    $units = array();

    if (mysql_num_rows($result) > 0)
    {
        while ($obj = mysql_fetch_object($result))
        {
            $billable = get_billable_incident_object($obj->billingtype);
            $units[$obj->billingtype] += $billable->amount_used_incident($obj->id); 
        }
    }

    $str = '';
    
    foreach ($units AS $var => $val)
    {
        $str .= "{$var} = {$val}, ";
    }
    
    return $str;
}


/**
 * @author Ivan Lucas
 * @param int $contractid. Contract ID of the contract to show a balance for
 * @return int. Number of available units according to the service balances and unit rates
 * @todo Check this is correct
 **/
function contract_unit_balance($contractid, $includenonapproved = FALSE, $includereserved = TRUE, $showonlycurrentlyvalid = TRUE)
{
    $toReturn = FALSE;
    $billable = get_billable_object_from_contract_id($contractid);
    if ($billable)
    {
        $toReturn = $billable->contract_unit_balance($contractid, $includenonapproved, $includereserved, $showonlycurrentlyvalid);
    } 
    return $toReturn;
    
}


/**
 * @author Ivan Lucas
 * @param int $contractid. Contract ID of the contract to show a balance for
 * @return int. Number of available units according to the service balances and unit rates
 * @todo Check this is correct
 **/
function contract_balance($contractid, $includenonapproved = FALSE, $includereserved = TRUE, $showonlycurrentlyvalid = TRUE)
{
    global $now, $dbService;

    $unitbalance = 0;

    $sql = "SELECT * FROM `{$dbService}` WHERE contractid = {$contractid} ";

    if ($showonlycurrentlyvalid)
    {
        $date = ldate('Y-m-d', $now);
        $sql .= "AND '{$date}' BETWEEN startdate AND enddate ";
    }
    $sql .= "ORDER BY enddate DESC";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        while ($service = mysql_fetch_object($result))
        {
            $balance += round($service->balance);
        }
    }

    if ($includenonapproved)
    {
        $awaiting = contract_transaction_total($contractid, BILLING_AWAITINGAPPROVAL);
        if ($awaiting != 0) $balance += round($awaiting);
    }

    if ($includereserved)
    {
        $reserved = contract_transaction_total($contractid, BILLING_RESERVED);
        if ($reserved != 0) $balance += round($reserved);
    }

    return $balance;
}


/**
 * Function to display/generate the transactions table
 * @author Paul Heaney
 * @param int $serviceid - The service ID to show transactons for
 * @param Date $startdate - Date in format yyyy-mm-dd when you want to start the report from
 * @param Date $enddate - Date in  format yyyy-mm-dd when you want to end the report, empty means today
 * @param int[] $sites - Array of sites to report on
 * @param String $display either csv or html
 * @param boolean $sitebreakdown - Breakdown per site
 * @param boolean $showfoc - Show free of charge as well (defaults to true);
 * @param boolean $includeawaitingapproval - Include transactions awaiting approval
 * @param boolean $includereserved - Include reserved transactions
 * @return String -either HTML or CSV
 */
function transactions_report($serviceid, $startdate, $enddate, $sites, $display, $sitebreakdown=TRUE, $showfoc=TRUE, $focaszero=FALSE, $includeawaitingapproval = TRUE, $includereserved = TRUE)
{
    global $CONFIG;

    $csv_currency = html_entity_decode($CONFIG['currency_symbol'], ENT_NOQUOTES);

    $sql = "SELECT DISTINCT t.*, m.site, p.foc, p.cust_ref, p.cust_ref_date, p.title, p.notes ";
    $sql .= "FROM `{$GLOBALS['dbTransactions']}` AS t, `{$GLOBALS['dbService']}` AS p, ";
    $sql .= "`{$GLOBALS['dbMaintenance']}` AS m, `{$GLOBALS['dbServiceLevels']}` AS sl, `{$GLOBALS['dbSites']}` AS s ";
    $sql .= "WHERE t.serviceid = p.serviceid AND p.contractid = m.id "; // AND t.date <= '{$enddateorig}' ";
    $sql .= "AND m.servicelevel = sl.tag AND sl.timed = 'yes' AND m.site = s.id ";
    //// $sql .= "AND t.date > p.lastbilled AND m.site = {$objsite->site} ";
    if ($serviceid > 0) $sql .= "AND t.serviceid = {$serviceid} ";
    if (!empty($startdate)) $sql .= "AND t.dateupdated >= '{$startdate}' ";
    if (!empty($enddate)) $sql .= "AND t.dateupdated <= '{$enddate}' ";
    $orsql[] = "t.transactionstatus = ".BILLING_APPROVED;
    if ($includeawaitingapproval) $orsql[] = "t.transactionstatus = ".BILLING_AWAITINGAPPROVAL;
    if ($includereserved) $orsql[] = "t.transactionstatus = ".BILLING_RESERVED;
    $o = implode(" OR ", $orsql);
    $sql .= "AND ($o) ";

    if (!$showfoc) $sql .= "AND p.foc = 'no' ";

    if (!empty($sites))
    {
        $sitestr = '';

        foreach ($sites AS $s)
        {
            $s = clean_int($s);
            if (empty($sitestr)) $sitestr .= "m.site = {$s} ";
            else $sitestr .= "OR m.site = {$s} ";
        }

        $sql .= "AND {$sitestr} ";
    }

    if (!empty($site)) $sql .= "AND m.site = {$site} ";

    $sql .= "ORDER BY t.dateupdated, s.name ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $shade = 'shade1';

        $total = 0;
        $totalcredit = 0;
        $totaldebit = 0;

        $details = '';

        while ($transaction = mysql_fetch_object($result))
        {
            if ($display == 'html')
            {
                if ($serviceid > 0 AND empty($details))
                {
                    if (!empty($transaction->cust_ref))
                    {
                        $details .= "<tr>";
                        $details .= "<th>{$GLOBALS['strCustomerReference']}</th><td>{$transaction->cust_ref}</td>";
                        if ($transaction->cust_ref_date != "1970-01-01")
                        {
                            $details .= "<th>{$GLOBALS['strCustomerReferenceDate']}</th><td>{$transaction->cust_ref_date}</td>";
                        }
                        $details .= "</tr>";
                    }

                    if (!empty($transaction->title))
                    {
                        $details .= "<tr><th>{$GLOBALS['strTitle']}</th><td>{$transaction->title}</td></tr>";
                    }

                    if (!empty($transaction->notes))
                    {
                        $details .= "<tr><th>{$GLOBALS['strNotes']}</th><td>{$transaction->notes}</td></tr>";
                    }
                }

                $str = "<tr class='$shade'>";
                $str .= "<td>" . date($CONFIG['dateformat_datetime'], mysql2date($transaction->dateupdated)) . "</td>";
                $str .= "<td>{$transaction->transactionid}</td>";
                $str .= "<td>{$transaction->serviceid}</td>";
                $str .= "<td>".site_name($transaction->site)."</td>";
                $str .= "<td>{$transaction->description}</td>";
                $str .= "<td>";
                switch ($transaction->transactionstatus)
                {
                    case BILLING_APPROVED: $str .= $GLOBALS['strApproved'];
                        break;
                    case BILLING_AWAITINGAPPROVAL: $str .= $GLOBALS['strAwaitingApproval'];
                        break;
                    case BILLING_RESERVED: $str .= $GLOBALS['strReserved'];
                        break;
                }
                $str .= "</td>";
            }
            elseif ($display == 'csv')
            {
                if ($serviceid > 0 AND empty($details))
                {
                    if (!empty($transaction->cust_ref))
                    {
                        $details .= "\"{$GLOBALS['strCustomerReference']}\",\"{$transaction->cust_ref}\",";
                        if ($transaction->cust_ref_date != "1970-01-01")
                        {
                            $details .= "\"{$GLOBALS['strCustomerReferenceDate']}\",\"{$transaction->cust_ref_date}\",";
                        }
                        $details .= "\n";
                    }

                    if (!empty($transaction->title))
                    {
                        $details .= "\"{$GLOBALS['strTitle']}\",\"{$transaction->title}\"\n";
                    }

                    if (!empty($transaction->notes))
                    {
                        $details .= "\"{$GLOBALS['strNotes']}\",\"{$transaction->notes}\"\n";
                    }
                }

                $str = "\"" . date($CONFIG['dateformat_datetime'], mysql2date($transaction->dateupdated)) . "\",";
                $str .= "\"{$transaction->transactionid}\",";
                $str .= "\"{$transaction->serviceid}\",\"";
                $str .= site_name($transaction->site)."\",";
                $str .= "\"".html_entity_decode($transaction->description)."\",";
                $str .= "\"";
                switch ($transaction->transactionstatus)
                {
                    case BILLING_APPROVED:
                        $str .= $GLOBALS['strApproved'];
                        break;
                    case BILLING_AWAITINGAPPROVAL:
                        $str .= $GLOBALS['strAwaitingApproval'];
                        break;
                    case BILLING_RESERVED:
                        $str .= $GLOBALS['strReserved'];
                        break;
                }
                $str .= "\",";
            }

            if ($focaszero AND $transaction->foc == 'yes')
            {
                $transaction->amount = 0;
            }

            $total += $transaction->amount;
            if ($transaction->amount < 0)
            {
                $totaldebit += $transaction->amount;
                if ($display == 'html')
                {
                    $str .= "<td></td><td>{$CONFIG['currency_symbol']}".number_format($transaction->amount, 2)."</td>";
                }
                elseif ($display == 'csv')
                {
                    $str .= ",\"{$csv_currency}".number_format($transaction->amount, 2)."\",";
                }
            }
            else
            {
                $totalcredit += $transaction->amount;
                if ($display == 'html')
                {
                    $str .= "<td>{$CONFIG['currency_symbol']}".number_format($transaction->amount, 2)."</td><td></td>";
                }
                elseif ($display == 'csv')
                {
                    $str .= "\"{$csv_currency}".number_format($transaction->amount, 2)."\",,";
                }
            }

            if ($display == 'html') $str .= "</tr>";
            elseif ($display == 'csv') $str .= "\n";

            if ($sitebreakdown == TRUE)
            {
                $table[$transaction->site]['site'] = site_name($transaction->site);
                $table[$transaction->site]['str'] .= $str;
                if ($transaction->amount < 0)
                {
                    $table[$transaction->site]['debit'] += $transaction->amount;
                }
                else
                {
                    $table[$transaction->site]['credit'] += $transaction->amount;
                }
            }
            else
            {
                $table .= $str;
            }
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }

        if ($sitebreakdown == TRUE)
        {
            foreach ($table AS $e)
            {
                if ($display == 'html')
                {
                    $text .= "<h3>{$e['site']}</h3>";
                    $text .= "<table align='center'  width='60%'>";
                    //echo "<tr><th colspan='7'>{$e['site']}</th></tr>";
                    $text .= "<tr><th>{$GLOBALS['strDate']}</th><th>{$GLOBALS['strID']}</th><th>{$GLOBALS['strServiceID']}</th>";
                    $text .= "<th>{$GLOBALS['strSite']}</th><th>{$GLOBALS['strDescription']}</th><th>{$GLOBALS['strCredit']}</th><th>{$GLOBALS['strDebit']}</th></tr>";
                    $text .= $e['str'];
                    $text .= "<tr><td colspan='5' align='right'>{$GLOBALS['strTotal']}</td>";
                    $text .= "<td>{$CONFIG['currency_symbol']}".number_format($e['credit'], 2)."</td>";
                    $text .= "<td>{$CONFIG['currency_symbol']}".number_format($e['debit'], 2)."</td></tr>";
                    $text .= "</table>";
                }
                elseif ($display == 'csv')
                {
                    $text .= "\"{$e['site']}\"\n\n";
                    $text .= "\"{$GLOBALS['strDate']}\",\"{$GLOBALS['strID']}\",\"{$GLOBALS['strServiceID']}\",";
                    $text .= "\"{$GLOBALS['strSite']}\",\"{$GLOBALS['strDescription']}\",\"{$GLOBALS['strCredit']}\",\"{$GLOBALS['strDebit']}\"\n";
                    $text .= $e['str'];
                    $text .= ",,,,{$GLOBALS['strTotal']},";
                    $text .= "\"{$csv_currency}".number_format($e['credit'], 2)."\",\"";
                    $text .="{$csv_currency}".number_format($e['debit'], 2)."\"\n";
                }
            }
        }
        else
        {
            if ($display == 'html')
            {
                if (!empty($details))
                {
                    // Dont need to worry about this in the above section as sitebreakdown and serviceid are multually exclusive
                    $text .= "<div><table class='maintable'>{$details}</table></div>";
                }

                $text .= "<table class='maintable'>";
                $text .= "<tr><th>{$GLOBALS['strDate']}</th><th>{$GLOBALS['strID']}</th><th>{$GLOBALS['strServiceID']}</th>";
                $text .= "<th>{$GLOBALS['strSite']}</th>";
                $text .= "<th>{$GLOBALS['strDescription']}</th><th>{$GLOBALS['strStatus']}</th><th>{$GLOBALS['strCredit']}</th><th>{$GLOBALS['strDebit']}</th></tr>";
                $text .= $table;
                $text .= "<tfoot><tr><td colspan='6' align='right'>{$GLOBALS['strTOTALS']}</td>";
                $text .= "<td>{$CONFIG['currency_symbol']}".number_format($totalcredit, 2)."</td>";
                $text .= "<td>{$CONFIG['currency_symbol']}".number_format($totaldebit, 2)."</td></tr></tfoot>";
                $text .= "</table>";
            }
            elseif ($display == 'csv')
            {
                if (!empty($details))
                {
                    $text .= $details;
                }
                $text .= "\"{$GLOBALS['strDate']}\",\"{$GLOBALS['strID']}\",\"{$GLOBALS['strServiceID']}\",";
                $text .= "\"{$GLOBALS['strSite']}\",";
                $text .= "\"{$GLOBALS['strDescription']}\",\"{$GLOBALS['strStatus']}\",\"{$GLOBALS['strCredit']}\",\"{$GLOBALS['strDebit']}\"\n";
                $text .= $table;
                $text .= ",,,,{$GLOBALS['strTOTALS']},";
                $text .= "\"{$csv_currency}".number_format($totalcredit, 2)."\",\"";
                $text .= "{$csv_currency}".number_format($totaldebit, 2)."\"\n";
            }
        }
    }
    else
    {
        if ($display == 'html')
        {
            $text = "<p align='center'>{$GLOBALS['strNoTransactionsMatchYourSearch']}</p>";
        }
        elseif ($display == 'csv')
        {
            $text = $GLOBALS['strNoTransactionsMatchYourSearch']."\n";
        }
    }

    return $text;
}


/**
 * Returns the type of billing used on the contract if any
 * @author Paul Heaney
 * @param int $contractid The ID of the contract to check
 * @return string the billing type being used, blank if not billed
 * @todo Possibly merge with is_contract_timed
 */
function get_contract_billable_type($contractid)
{
    $toReturn = '';
    
    $sql = "SELECT billingtype FROM `{$GLOBALS['dbMaintenance']}` WHERE id = {$contractid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error getting services. ".mysql_error(), E_USER_WARNING);
    
    if (mysql_num_rows($result) > 0)
    {
        $obj = mysql_fetch_object($result);
        $toReturn = $obj->billingtype;
    }
    
    return  $toReturn;
}


/**
 * Find the billing matrix for a particular contract
 * @author Paul Heaney
 * @param int $contractid The contract ID to find the billing matrix for
 * @return string The billing matrix being used
 */
function get_contract_billing_matrix($contractid)
{
    $toReturn = '';
    
    $sql = "SELECT billingmatrix FROM `{$GLOBALS['dbMaintenance']}` WHERE id = {$contractid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("Error getting services. ".mysql_error(), E_USER_WARNING);
    
    if (mysql_num_rows($result) > 0)
    {
        $obj = mysql_fetch_object($result);
        $toReturn = $obj->billingmatrix;
    }
    
    return $toReturn;
}

?>