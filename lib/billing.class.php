<?php
// billing.class.php - Representation of billing types
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.


// This lib is currently included at the end of auth.inc.php


abstract class Billable {
    
    public $billing_type_name = '';
    public $billing_matrix_type = ''; // TODO static?
    public $uses_billing_matrix = TRUE;
    
    /**
     * This function closes an incident and performs type specific operations
     * @param int $incidentid
     */
    abstract function close_incident($incidentid);
    
    /**
     * This function performs any necessary billing tasks when opening an incident
     * @param int $incidentid
     */
    abstract function open_incident($incidentid);
    
    /**
     * ?????
     * @param unknown $contractid
     * @param string $includenonapproved
     * @param string $includereserved
     * @param string $showonlycurrentlyvalid
     */
    abstract function contract_unit_balance($contractid, $includenonapproved = FALSE, $includereserved = TRUE, $showonlycurrentlyvalid = TRUE);
    
    /**
     * Approve a transaction for a closed incident, this function typically needs to confirm now much has been used and authorise its deduction from the balance
     * @param unknown $transactionid
     */
    abstract function approve_incident_transaction($transactionid);

    /**
     * Identity the amount used on a particular incident e.g. home many hours, units etc
     * @param int $incidentid - The incident ID to check for
     */
    abstract function amount_used_incident($incidentid);
    
    /**
     * Creates the HTML table which is displayed on the approvals page of the billing system
     * @param int $siteid - ID of the site to produce the table for
     * @param string $formname - Name of the form this table is being added to
     * @param string $startdate - The start date to start show incidents awaiting approval from
     * @param string $enddate - The start date to stop showing incidents awaiting approval from
     * @return string - The HTML for the table
     */
    abstract function produce_site_approvals_table($siteid, $formname, $startdate, $enddate);
    
    /**
     * Updates the transaction record prior to approval, this is called after adjusting duration etc on a incident and forces a recalculation.
     * @param int $incidentid The incident to force a recalculation on
     */
    abstract function update_incident_transaction_record($incidentid);
    
    /**
     * Returns the HTML for the relevent billing matrix drop down
     * @param string $id ID and name to give the select element
     * @param string $selected The currently selected element
     * @return string the select HTML element
     */
    abstract function billing_matrix_selector($id, $selected='');

    /**
     * Returns the display name for this billing type
     * @return string the display name for this type
     */
    abstract function display_name();
    
    /**
     * The interface to choose to manually update an incidents billable amount 
     * e.g. to give a refund or increase the amount
     * This should be the contents of a table cell, the table cell is drawn by the interface
     * @param string $id The ID for the input element
     * @return string The HTML for the table cell to display the incident edit field
     */
    abstract function incident_update_amount_interface($id);
    
    
    /**
     * Produces the text added to the update log on manual incident adjustment
     * @param int $amount - The amount to adjust the incident by
     * @param string $description - The text given for the update
     * @return string - The text to insert into the update log
     * @author Paul Heaney
     */
    abstract function incident_update_amount_text($amount, $description);

    
    /**
     * Produces the HTML that is displayed at the bottom of the incident update in the incident log
     * @param int $amount - The amount of the update change
     * @return string The HTML to display at the bottom of the update in the incident log
     */
    abstract function incident_log_update_summary($amount);
    
    /**
     * Does this billing method uses activities or some other mechanism?
     * @author Paul Heaney
     * @return boolean TRUE if this billing method uses activities (like Unit) or FALSE otherwise
     */
    function uses_activities()
    {
        return FALSE;
    }
    
  
    /**
     * Creates a transaction awaiting approval, this should called from close_incident as every incident should create a transaction waiting approval 
     * @param int $incidentid - The incident ID to create the transaction for
     * @param int $contractid - The contract tht the transaction relates to - this could be calculated here though its usually required to get this during the logic to identify the cost so is passed in as an optimisation 
     * @param int $numberofunits - The number of units thsi incident was active for
     * @param int $unitrate - The rate per unit
     * @param int $totalunits - The total number of units - taking into account any multiplies
     * @param int $totalbillableunits - Total number of units to use, this is usually $totalunits - $refunds
     * @param int $totalrefunds - Number of units refunded
     * @param int $cost - The total cost for this transaction
     * @param string $texttoappend
     * @author Paul Heaney
     * @return boolean
     */
    function create_transaction_awaiting_approval($incidentid, $contractid, $numberofunits, $unitrate, $totalunits, $totalbillableunits, $totalrefunds, $cost, $texttoappend)
    {
        global $CONFIG, $now;

        $rtnvalue = TRUE;
        
        if (!empty($texttoappend)) $texttoappend = "({$texttoappend})";

        $desc = trim(sprintf($GLOBALS['strBillableIncidentSummary'], $incidentid, $numberofunits, $CONFIG['currency_symbol'], $unitrate, $texttoappend));
        
        // Add transaction
        $serviceid = get_serviceid($contractid);
        if ($serviceid < 1) trigger_error("Invalid service ID", E_USER_ERROR);
        $date = date('Y-m-d H:i:s', $now);
        
        $sql = "INSERT INTO `{$GLOBALS['dbTransactions']}` (serviceid, totalunits, totalbillableunits, totalrefunds, amount, description, userid, dateupdated, transactionstatus) ";
        $sql .= "VALUES ('{$serviceid}', '{$totalunits}',  '{$totalbillableunits}', '{$totalrefunds}', '{$cost}', '{$desc}', '{$_SESSION['userid']}', '{$date}', '".BILLING_AWAITINGAPPROVAL."')";
        
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error("Error inserting transaction. ".mysql_error(), E_USER_WARNING);
            $rtnvalue = FALSE;
        }
        
        $transactionid = mysql_insert_id();
        
        if ($transactionid != FALSE)
        {
        
            $sql = "INSERT INTO `{$GLOBALS['dbLinks']}` VALUES (6, {$transactionid}, {$incidentid}, 'left', {$_SESSION['userid']})";
            mysql_query($sql);
            if (mysql_error())
            {
                trigger_error(mysql_error(), E_USER_ERROR);
                $rtnvalue = FALSE;
            }
            if (mysql_affected_rows() < 1)
            {
                trigger_error("Link transaction on closure failed", E_USER_ERROR);
                $rtnvalue = FALSE;
            }
        }
        else 
        {
            $rtnvalue = FALSE;
        }
        
        return $rtnvalue;
    }
}


class UnitBillable extends Billable {
    
    public $billing_type_name = 'unit';

    /**
     * (non-PHPdoc)
     * @see Billable::close_incident()
     * @author Paul Heaney
     */
    function close_incident($incidentid)
    {
        global $CONFIG, $now;

        $rtnvalue = TRUE;
        
        $contractid = incident_maintid($incidentid);
        $duration = 0;
        $sql = "SELECT SUM(duration) FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid = {$incidentid}";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error("Error getting duration for billable incident. ".mysql_error(), E_USER_WARNING);
            $rtnvalue = FALSE;
        }
        list($duration) = mysql_fetch_row($result);
        if ($duration > 0)
        {
            // There where activities on this update so add to the transactions table
        
            $bills = $this->get_incident_billable_breakdown_array($incidentid);
        
            $billingmatrix = get_contract_billing_matrix($contractid);
            $multipliers = $this->get_all_available_multipliers($billingmatrix);
        
            $totalunits = 0;
            $totalbillableunits = 0;
            $totalrefunds = 0;
        
            foreach ($bills AS $bill)
            {
                foreach ($multipliers AS $m)
                {
                    $a[$m] += $bill[$m]['count'];
                }
            }
        
            foreach ($multipliers AS $m)
            {
                $s .= sprintf($GLOBALS['strXUnitsAtX'], $a[$m], $m);
                $totalunits += $a[$m];
                $totalbillableunits += ($m * $a[$m]);
            }
        
            $unitrate = get_unit_rate($contractid);
        
            $totalrefunds = $bills['refunds'];
            $numberofunits += $bills['refunds'];
        
            $cost = (($totalbillableunits + $totalrefunds)  * $unitrate) * -1;
        
            $rtnvalue = $this->create_transaction_awaiting_approval($incidentid, $contractid, $numberofunits, $unitrate, $totalunits, $totalbillableunits, $totalrefunds, $cost, $s);
        }

        return $rtnvalue;
    }
    
    /**
     * Unit billable incidents don't need to do anything special when opening, this always returns true
     * @see Billable::open_incident()
     */
    function open_incident($incidentid)
    {
        return true;
    }


    function contract_unit_balance($contractid, $includenonapproved = FALSE, $includereserved = TRUE, $showonlycurrentlyvalid = TRUE)
    {
        global $now;
        
        $unitbalance = 0;
        
        $sql = "SELECT * FROM `{$GLOBALS['dbService']}` WHERE contractid = {$contractid} ";
        
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
                $multiplier = $this->get_billable_multiplier(strtolower(date('D', $now)), date('G', $now));
                $unitamount = $service->rate * $multiplier;
                if ($unitamount > 0 AND $service->balance != 0) $unitbalance += round($service->balance / $unitamount);
            }
        
            if ($includenonapproved)
            {
                $awaiting = contract_transaction_total($contractid, BILLING_AWAITINGAPPROVAL);
                if ($awaiting != 0) $unitbalance += round($awaiting / $unitamount);
            }
        
            if ($includereserved)
            {
                $reserved = contract_transaction_total($contractid, BILLING_RESERVED);
                if ($reserved != 0) $unitbalance += round($reserved / $unitamount);
            }
        }
        
        return $unitbalance;
    }
    
    
    function approve_incident_transaction($transactionid)
    {
        global $CONFIG;
        
        $rtnvalue = TRUE;
        
        // Check transaction exists, and is awaiting approval and is an incident
        $sql = "SELECT l.linkcolref, t.serviceid FROM `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbTransactions']}` AS t ";
        $sql .= "WHERE t.transactionid = l.origcolref AND t.transactionstatus = ".BILLING_AWAITINGAPPROVAL." AND l.linktype = 6 AND t.transactionid = {$transactionid}";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("Error identify incident transaction. ".mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) > 0)
        {
            list($incidentid, $serviceid) = mysql_fetch_row($result);

            $bills = $this->get_incident_billable_breakdown_array($incidentid);

            $billingmatrix = get_contract_billing_matrix(maintid_from_transaction($transactionid));
            $multipliers = $this->get_all_available_multipliers($billingmatrix);
        
            $totalunits = 0;
            $totalbillableunits = 0;
            $totalrefunds = 0;
        
            foreach ($bills AS $bill)
            {
                foreach ($multipliers AS $m)
                {
                    $a[$m] += $bill[$m]['count'];
                }
            }
        
            $s = '';
            
            foreach ($multipliers AS $m)
            {
                if ($a[$m] > 0)
                {
                    $s .= sprintf($GLOBALS['strXUnitsAtX'], $a[$m], $m)."  ";
                }
                $totalbillableunits += ($m * $a[$m]);
                $totalunits += $a[$m];
            }
        
            $unitrate = get_unit_rate(incident_maintid($incidentid));
        
            $totalrefunds += $bills['refunds'];
        
            $cost = (($totalbillableunits += $totalrefunds) * $unitrate) * -1;
        
            if (!empty($s)) $s = "({$s})";
            
            $desc = trim(sprintf($GLOBALS['strBillableIncidentSummary'], $incidentid, $totalbillableunits, $CONFIG['currency_symbol'], $unitrate, $s));
        
            $rtn = update_contract_balance(incident_maintid($incidentid), $desc, $cost, $serviceid, $transactionid, $totalunits, $totalbillableunits, $totalrefunds);
        
            if ($rtn == FALSE)
            {
                $rtnvalue = FALSE;
            }
        }
        else
        {
            $rtnvalue = FALSE;
        }
        
        return $rtnvalue;
    }

    
    function amount_used_incident($incidentid)
    {
        $a = $this->make_incident_billing_array($incidentid);
        return $a[-1]['totalcustomerperiods'];
    }


    function billing_matrix_selector($id, $selected='')
    {
        $sql = "SELECT DISTINCT tag FROM `{$GLOBALS['dbBillingMatrix']}`";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) >= 1)
        {
            $html = "<select name='{$id}' id='{$id}'>\n";
            while ($obj = mysql_fetch_object($result))
            {
                $html .= "<option value='{$obj->tag}'";
                if ($obj->tag == $selected) $html .= " selected='selected'";
                $html .= ">{$obj->tag}</option>\n";
            }
            $html .= "</select>\n";
        }
        else
        {
            $html = "{$GLOBALS['strNoBillingMatrixDefined']}";
        }
    
        return $html;
    }
    
    
    /**
     * (non-PHPdoc)
     * @see Billable::uses_activities()
     */
    function uses_activities()
    {
       return TRUE; 
    }

    
    function produce_site_approvals_table($siteid, $sitenamenospaces, $startdate, $enddate)
    {
        global $CONFIG;
        
        // TODO this code is abit messy and could do we a tidy up PH 2013-04-01

        $sitetotals = 0;
        $sitetotalsbillable = 0;
        $sitetotalrefunds = 0;
        
        $sitetotalawaitingapproval = 0;
        $sitetotalsawaitingapproval = 0;
        $sitetotalsbillableawaitingapproval = 0;
        $billableunitsincidentunapproved = 0;
        $refundedunapproved = 0;

        $str = "<table align='center' width='80%'>";
        
        $str .= "<tr>";
        $str .= "<th><input type='checkbox' name='selectAll' value='CheckAll' onclick=\"checkAll({$sitenamenospaces}, this.checked);\" /></th>";
        $str .= "<th>{$GLOBALS['strID']}</th><th>{$GLOBALS['strIncidentTitle']}</th><th>{$GLOBALS['strContact']}</th>";
        $str .= "<th>{$GLOBALS['strEngineer']}</th><th>{$GLOBALS['strOpened']}</th><th>{$GLOBALS['strClosed']}</th>";
        
        $multipliers = $this->get_all_available_multipliers();
        
        foreach ($multipliers AS $m)
        {
            $str .= "<th>{$m}&#215;</th>";
        }
        
        $str .= "<th>{$GLOBALS['strTotalUnits']}</th><th>{$GLOBALS['strTotalBillableUnits']}</th>";
        $str .= "<th>{$GLOBALS['strCredits']}</th>";
        $str .= "<th>{$GLOBALS['strBill']}</th><th>{$GLOBALS['strActions']}</th></tr>\n";
        
        $used = FALSE;
        
        $sql = "SELECT i.id, i.owner, i.contact, i.title, i.closed, i.opened, t.transactionid FROM `{$GLOBALS['dbTransactions']}` AS t, `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbIncidents']}` AS i ";
        $sql .= ", `{$GLOBALS['dbContacts']}` AS c, `{$GLOBALS['dbMaintenance']}` AS m  WHERE ";
        $sql .= "t.transactionid = l.origcolref AND t.transactionstatus = ".BILLING_AWAITINGAPPROVAL." AND linktype= 6 AND l.linkcolref = i.id AND i.contact = c.id AND i.maintenanceid = m.id AND c.siteid = {$siteid} ";
        $sql .= "AND m.billingtype = 'UnitBillable' ";
        if ($startdate != 0)
        {
            $sql .= "AND i.closed >= {$startdate} ";
        }
        
        if ($enddate != 0)
        {
            $sql .= "AND i.closed <= {$enddate} ";
        }
        $sql .= "ORDER BY i.closed";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(), E_USER_WARNING);
            return FALSE;
        }
        
        $units = 0;
        
        if (mysql_num_rows($result) > 0)
        {
            $shade = 'shade1';
        
            while ($obj = mysql_fetch_object($result))
            {
                $a = $this->make_incident_billing_array($obj->id);
                $unapprovable = FALSE;
                unset($billtotalsincident);
        
                if ($a[-1]['totalcustomerperiods'] > 0)
                {
                    $billableunitsincident = 0;
        
                    $isapproved = FALSE;
        
                    $unitrate = get_unit_rate(incident_maintid($obj->id));
        
                    if ($unitrate == -1) $unapprovable = TRUE;
        
                    $line = "<tr class='{$shade}'><td style='text-align: center'>";
        
                    if (!$isapproved AND !$unapprovable)
                    {
                        $line .= "<input type='checkbox' name='selected[]' value='{$obj->transactionid}' />";
                    }
                    $line .= "</td>";
                    $line .= "<td>".html_incident_popup_link($obj->id, $obj->id)."</td>";
                    $line .= "<td>{$obj->title}</td><td>".contact_realname($obj->contact)."</td>";
                    $line .= "<td>".user_realname($obj->owner)."</td>";
                    $line .= "<td>".ldate($CONFIG['dateformat_datetime'], $obj->opened)."</td><td>".ldate($CONFIG['dateformat_datetime'], $obj->closed)."</td>";
        
                    $bills = $this->get_incident_billable_breakdown_array($obj->id);
        
                    foreach ($bills AS $bill)
                    {
                        foreach ($multipliers AS $m)
                        {
                            if (!empty($bill[$m]))
                            {
                                $billtotalssite[$m] += $bill[$m]['count'];
                                $billtotalsincident[$m] += $bill[$m]['count'];
        
                                if (!$isapproved)
                                {
                                    $billtotalssiteunapproved[$m] += $bill[$m]['count'];
                                }
                            }
                        }
                    }
        
                    foreach ($multipliers AS $m)
                    {
                        $line .= "<td>";
                        if (!empty($billtotalsincident[$m]))
                        {
                            $line .= $billtotalsincident[$m];
        
                            $billableunitsincident += $m * $billtotalsincident[$m];
        
                            if (!$isapproved)
                            {
                                $billableunitsincidentunapproved += $m * $billtotalsincident[$m];
                            }
                        }
                        else
                        {
                            $line .= "0";
                        }
        
                        $line .= "</td>";
                    }
        
                    $actualunits = ($billableunitsincident + $a[-1]['refunds']);
        
                    $sitetotalrefunds += $a[-1]['refunds'];
        
                    $cost = $actualunits * $unitrate;
        
                    $line .= "<td>{$a[-1]['totalcustomerperiods']}</td>";
                    $line .= "<td>{$billableunitsincident}</td>";
                    $line .= "<td>{$a[-1]['refunds']}</td>";
                    $bill = number_format($cost, 2);
                    if ($unapprovable) $bill = "?";
                    $line .= "<td>{$CONFIG['currency_symbol']}{$bill}</td>";
        
                    $line .= "<td>";
        
                    if ($isapproved)
                    {
                        $line .= $GLOBALS['strApproved'];
                    }
                    elseif ($unapprovable)
                    {
                        $line .= $GLOBALS['strUnapprovable'];
                    }
                    else
                    {
                        $operations[$GLOBALS['strApprove']] = array('url' => "{$_SERVER['PHP_SELF']}?mode=approve&amp;transactionid={$obj->transactionid}&amp;startdate={$startdateorig}&amp;enddate={$enddateorig}&amp;showonlyapproved={$showonlyapproved}");
                        $operations[$GLOBALS['strAdjust']] = array('url' => "billing_update_incident_balance.php?incidentid={$obj->id}");
                        $line .= html_action_links($operations);
                        $sitetotalawaitingapproval += $cost;
        
                        $sitetotalsawaitingapproval += $a[-1]['totalcustomerperiods'];
                        $sitetotalsbillablewaitingapproval += $billableunitsincident;
                        $refundedunapproved += $a[-1]['refunds'];
                    }
        
                    $line .= "</td>";
        
                    $line .= "</tr>\n";
        
                    $sitetotals += $a[-1]['totalcustomerperiods'];
                    $sitetotalsbillable += $billableunitsincident;
        
                    if ($shade == "shade1") $shade = "shade2";
                    else $shade = "shade1";
        
                    $used = true;
        
                    if (($showonlyapproved AND !$isapproved) OR !$showonlyapproved)
                    {
                        $str .= $line;
                    }
                }
            }
        }
        
        $str .= "<tr><td><input type='submit' value='{$GLOBALS['strApprove']}' />";
        $str .= "</td><td colspan='5'></td>";
        
        if (!$showonlyapproved)
        {
            $str .= "<td>{$GLOBALS['strTOTALS']}</td>";
        
            foreach ($multipliers AS $m)
            {
                $str .= "<td>";
                if (!empty($billtotalssite[$m])) $str .= $billtotalssite[$m];
                else $str .= "0";
                $str .= "</td>";
            }
        
            $str .= "<td>{$sitetotals}</td>";
            $str .= "<td>{$sitetotalsbillable}</td>";
            $str .= "<td>{$sitetotalrefunds}</td>";
        
            $cost = ($sitetotalsbillable + $sitetotalrefunds) * $unitrate;
        
            $str.= "<td>{$CONFIG['currency_symbol']}".number_format($cost, 2)."</td><td></td>";
        
            $str .= "</tr>\n";
        
            $str .= "<tr><td align='right' colspan='6'></td>";
        }
        
        $str .= "<td>{$strAwaitingApproval}</td>";
        
        foreach ($multipliers AS $m)
        {
            $str .= "<td>";
            if (!empty($billtotalssiteunapproved[$m]))
            {
                $str .= $billtotalssiteunapproved[$m];
            }
            else
            {
                $str .= "0";
            }
            $str .= "</td>";
        }
        
        $str .= "<td>{$sitetotalsawaitingapproval}</td>";
        $str .= "<td>{$billableunitsincidentunapproved}</td>";
        $str .= "<td>{$refundedunapproved}</td>";
        
        
        $str .= "<td>{$CONFIG['currency_symbol']}".number_format($sitetotalawaitingapproval, 2)."</td><td></td></tr>";
        
        $str .= "</table>";
        
        if (!$used) $str = FALSE;
        return $str;
    }
    
    
    function update_incident_transaction_record($incidentid)
    {
        $toReturn = FALSE;

        $bills = $this->get_incident_billable_breakdown_array($incidentid);
        $multipliers = $this->get_all_available_multipliers();
        
        $totalunits = 0;
        $totalbillableunits = 0;
        $totalrefunds = 0;
        
        foreach ($bills AS $bill)
        {
            foreach ($multipliers AS $m)
            {
                $a[$m] += $bill[$m]['count'];
            }
        }
        
        foreach ($multipliers AS $m)
        {
            $s .= sprintf($GLOBALS['strXUnitsAtX'], $a[$m], $m);
            $totalunits += $a[$m];
            $totalbillableunits += ($m * $a[$m]);
        }
        
        $unitrate = get_unit_rate(incident_maintid($incidentid));
        
        $totalrefunds = $bills['refunds'];
        // $numberofunits += $bills['refunds'];
        
        $cost = (($totalbillableunits + $totalrefunds)  * $unitrate) * -1;
        
        if (!empty($s)) $s = "({$s})";
        
        $desc = trim(sprintf($GLOBALS['strBillableIncidentSummary'], $incidentid, $numberofunits, $CONFIG['currency_symbol'], $unitrate, $s));
        
        $transactionid = get_incident_transactionid($incidentid);
        if ($transactionid != FALSE)
        {
            $toReturn = update_transaction($transactionid, $cost, $desc, BILLING_AWAITINGAPPROVAL);
        }
        
        return $toReturn;
    }


    /**
     * Find the billing multiple that should be applied given the day, time and matrix in use
     * @author Paul Heaney
     * @param string $dayofweek 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' or 'holiday'
     * @param int $hour The hour in the day, values in the range 0 - 23
     * @param string $billingmatrix The billing matrix to get the multiple from, defaults to 'Default'
     * @return float - The applicable multiplier for the time of day and billing matrix being used
     */
    function get_billable_multiplier($dayofweek, $hour, $billingmatrix = 'Default')
    {
        $sql = "SELECT `{$dayofweek}` AS rate FROM {$GLOBALS['dbBillingMatrix']} WHERE hour = {$hour} AND tag = '{$billingmatrix}'";
    
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(), E_USER_WARNING);
            return FALSE;
        }
    
        $rate = 1;
    
        if (mysql_num_rows($result) > 0)
        {
            $obj = mysql_fetch_object($result);
            $rate = $obj->rate;
        }
    
        return $rate;
    }
    
    
    /**
     * Takes an array of engineer/times of services and groups them so we have only periods which should be charged for.
     * This takes into account tasks started in the same period by the same engineer e.g. task started at 17:00 for 10 mins
     * another at 17:30 for 10 mins with a period of 60mins only one is reported
     * @author Paul Heaney
     * @param array $count (Passed by reference) The array to return into, either the 'engineer' or 'customer' element see $countType
     * @param string $countType The counttype we are doing so either engineer or customer
     * @param array $activity The current activity
     * @param int $period The billing period to group to (in seconds)
     * @return $count is passed in by reference so nothing is returned
     */
    function group_billing_periods(&$count, $countType, $activity, $period)
    {
        $duration = $activity['duration'] * 60;
        $startTime = $activity['starttime'];
    
        if (!empty($count[$countType]))
        {
            while ($duration > 0)
            {
                $saved = "false";
                foreach ($count[$countType] AS $ind)
                {
                    /*
                     echo "<pre>";
                    print_r($ind);
                    echo "</pre>";
                    */
                    //echo "IN:{$ind}:START:{$act['starttime']}:ENG:{$engineerPeriod}<br />";
    
                    if($ind <= $activity['starttime'] AND $ind <= ($activity['starttime'] + $period))
                    {
                        //echo "IND:{$ind}:START:{$act['starttime']}<br />";
                        // already have something which starts in this period just need to check it fits in the period
                        if($ind + $period > $activity['starttime'] + $duration)
                        {
                            $remainderInPeriod = ($ind + $period) - $activity['starttime'];
                            $duration -= $remainderInPeriod;
    
                            $saved = "true";
                        }
                    }
                }
                //echo "Saved: {$saved}<br />";
                // This section runs when there are no engineer or customer billing period totals yet (first iteration)
                if ($saved == "false" AND $activity['duration'] > 0)
                {
                    //echo "BB:".$activity['starttime'].":SAVED:{$saved}:DUR:{$activity['duration']}<br />";
                    // need to add a new block
                    $count[$countType][$startTime] = $startTime;
    
                    $startTime += $period;
    
                    $duration -= $period;
                }
            }
        }
        else
        {
            $count[$countType][$activity['starttime']] = $activity['starttime'];
            $localDur = $duration - $period;
    
            while ($localDur > 0)
            {
                $startTime += $period;
                $count[$countType][$startTime] = $startTime;
                $localDur -= $period; // was just -
            }
        }
    }

    
    /**
     * @author Paul Heaney
     * @param int $incidentid. Incident ID
     * @param bool $totals. Set to TRUE to include period totals in the array
     * @return mixed.
     * @retval bool FALSE - Failure
     * @retval array billing array
     * @note  based on periods
     * @todo we need to remove references from other places for this method
     */
    function make_incident_billing_array($incidentid, $totals = TRUE)
    {
    
        $billing = $this->get_incident_billing_details($incidentid);
    
        // echo "<pre>";
        // print_r($billing);
        // echo "</pre><hr />";
    
        $sql = "SELECT servicelevel, priority FROM `{$GLOBALS['dbIncidents']}` WHERE id = {$incidentid}";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(), E_USER_WARNING);
            return FALSE;
        }
    
        $incident = mysql_fetch_object($result);
        $servicelevel_tag = $incident->servicelevel;
        $priority = $incident->priority;
    
        if (!empty($billing))
        {
            $billingSQL = "SELECT * FROM `{$GLOBALS['dbBillingPeriods']}` WHERE tag='{$servicelevel_tag}' AND priority='{$priority}'";
    
            /*
             echo "<pre>";
            print_r($billing);
            echo "</pre>";
    
            echo "<pre>";
            print_r(make_billing_array($incidentid));
            echo "</pre>";
            */
    
            //echo $billingSQL;
    
            $billingresult = mysql_query($billingSQL);
            // echo $billingSQL;
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            $billingObj = mysql_fetch_object($billingresult);
    
            unset($billingresult);
    
            $engineerPeriod = $billingObj->engineerperiod * 60;  //to seconds
            $customerPeriod = $billingObj->customerperiod * 60;
    
            if (empty($engineerPeriod) OR $engineerPeriod == 0) $engineerPeriod = 3600;
            if (empty($customerPeriod) OR $customerPeriod == 0) $customerPeriod = 3600;
    
            /*
             echo "<pre>";
            print_r($billing);
            echo "</pre>";
            */
    
            $count = array();
    
    
            // Loop over each activity that happened during the duration of the incident
            // Grouped by Engineer - and then calculate totals
            foreach ($billing AS $engineer)
            {
                /*
                 [eng][starttime]
                */
    
                if (is_array($engineer))
                {
                    $owner = "";
                    $duration = 0;
    
                    unset($count);
                    $count = array();
    
                    $count['engineer'];
                    $count['customer'];
    
                    foreach ($engineer AS $activity)
                    {
                        $owner = user_realname($activity['owner']);
                        $duration += $activity['duration'];
    
                        $this->group_billing_periods($count, 'engineer', $activity, $engineerPeriod);
    
                        // Optimisation no need to compute again if we already have the details
                        if ($engineerPeriod != $customerPeriod)
                        {
                            $this->group_billing_periods($count, 'customer', $activity, $customerPeriod);
                        }
                        else
                        {
                            $count['customer'] = $count['engineer'];
                        }
                    }
    
                    $tduration += $duration;
                    $totalengineerperiods += sizeof($count['engineer']);
                    $totalcustomerperiods += sizeof($count['customer']);
    
                    $billing_a[$activity['owner']]['owner'] = $owner;
                    $billing_a[$activity['owner']]['duration'] = $duration;
                    $billing_a[$activity['owner']]['engineerperiods'] = $count['engineer'];
                    $billing_a[$activity['owner']]['customerperiods'] = $count['customer'];
                }
    
                if ($totals == TRUE)
                {
                    if (empty($totalengineerperiods)) $totalengineerperiods = 0;
                    if (empty($totalcustomerperiods)) $totalcustomerperiods = 0;
                    if (empty($tduration)) $tduration = 0;
    
                    $billing_a[-1]['totalduration'] = $tduration;
                    $billing_a[-1]['totalengineerperiods'] = $totalengineerperiods;
                    $billing_a[-1]['totalcustomerperiods'] = $totalcustomerperiods;
                    $billing_a[-1]['customerperiod'] = $customerPeriod;
                    $billing_a[-1]['engineerperiod'] = $engineerPeriod;
                }
    
                if (!empty($billing['refunds'])) $billing_a[-1]['refunds'] = $billing['refunds'] / $customerPeriod; // return refunds as a number of units
                else $billing_a[-1]['refunds'] = 0;
    
            }
    
        }
    
        //echo "<pre>";
        //print_r($billing_a);
        //echo "</pre>";
    
        return $billing_a;
    }
    
    
    /**
     * Function to get an array of all billing multipliers for a billing matrix
     * @author Paul Heaney
     * @param String $matrixid The TAG of the billing matrix being used, defaults to 'Default'
     * @return array - All available billing multipliers for the specified Matrix
     */
    function get_all_available_multipliers($matrixtag='Default')
    {
        $days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun', 'holiday');
    
        foreach ($days AS $d)
        {
            $sql = "SELECT DISTINCT({$d}) AS day FROM `{$GLOBALS['dbBillingMatrix']}` ";
            if (!empty($matrixtag)) $sql .= " WHERE tag = '{$matrixtag}'";
            $result = mysql_query($sql);
            if (mysql_error())
            {
                trigger_error(mysql_error(), E_USER_WARNING);
                return FALSE;
            }
    
            while ($obj = mysql_fetch_object($result))
            {
                $a[$obj->day] = $obj->day;
            }
        }
    
        ksort($a);
    
        return $a;
    }
    
    
    /**
     * Creates a billing array containing an entry for every activity that has happened
     * for the duration of the incident specfified.
     * @author Paul Heaney
     * @param int $incidentid - Incident number of the incident to create the array from
     * @return array
     * @note The $billing array lists the owner of each activity with start time and
     * @note duration.  Used for calculating billing totals.
     */
    function get_incident_billing_details($incidentid)
    {
        /*
         $array[owner][] = array(owner, starttime, duration)
        */
        $sql = "SELECT * FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid = {$incidentid} AND duration IS NOT NULL";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(), E_USER_WARNING);
            return FALSE;
        }
    
        if (mysql_num_rows($result) > 0)
        {
            while($obj = mysql_fetch_object($result))
            {
                if ($obj->duration > 0)
                {
                    $temparray['owner'] = $obj->userid;
                    $temparray['starttime'] = ($obj->timestamp - ($obj->duration * 60));
                    $temparray['duration'] = $obj->duration;
                    $billing[$obj->userid][] = $temparray;
                }
                else
                {
                    if (empty($billing['refunds'])) $billing['refunds'] = 0;
                    $billing['refunds'] += $obj->duration;
                }
            }
        }
    
        return $billing;
    }
    
    
    /**
     * Function to make an array with the number of units at each billable multiplier, broken down by engineer
     * @author Paul Heaney
     * @param int $incidentid The inicident to create the billing breakdown for
     * @return array. Array of the billing for this incident broken down by enegineer
     *
     */
    function get_incident_billable_breakdown_array($incidentid)
    {
        $billable = $this->make_incident_billing_array($incidentid, FALSE);
    
        $billingmatrix = '';
    
        $maintenanceid = incident_maintid($incidentid);
        $sql = "SELECT billingmatrix FROM `{$GLOBALS['dbMaintenance']}` WHERE id = {$maintenanceid}";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error("Unable to get billing matrix for service {$serviceid} ".mysql_error(), E_USER_WARNING);
        }
        list($billingmatrix) = mysql_fetch_row($result);
    
        //echo "<pre>";
        //print_r($billable);
        //echo "</pre>";
    
        if (!empty($billable))
        {
            foreach ($billable AS $engineer)
            {
                if (is_array($engineer) AND !isset($engineer['refunds']))
                {
                    $engineerName = $engineer['owner'];

                    foreach ($engineer['customerperiods'] AS $period)
                    {
                        // $period is the start time
                        $day = date('D', $period);
                        $hour = date('H', $period);
    
                        $dayNumber = date('d', $period);
                        $month = date('n', $period);
                        $year = date('Y', $period);
                        // echo "DAY {$day} HOUR {$hour}";
    
                        $dayofweek = strtolower($day);
    
                        if (is_day_bank_holiday($dayNumber, $month, $year))
                        {
                            $dayofweek = "holiday";
                        }
    
                        $multiplier = $this->get_billable_multiplier($dayofweek, $hour, $billingmatrix);
    
                        $billing[$engineerName]['owner'] = $engineerName;
                        $billing[$engineerName][$multiplier]['multiplier'] = $multiplier;
                        if (empty($billing[$engineerName][$multiplier]['count']))
                        {
                            $billing[$engineerName][$multiplier]['count'] = 0;
                        }
    
                        $billing[$engineerName][$multiplier]['count']++;
                    }
                }
            }
    
            if (!empty($billable[-1]['refunds'])) $billing['refunds'] = $billable[-1]['refunds'];
    
        }
    
        return $billing;
    }
    
    
    function display_name()
    {
        return $GLOBALS['strPerUnit'];
    }
    
    
    /**
     * (non-PHPdoc)
     * @see Billable::incident_update_amount_interface()
     * @author Paul Heaney
     */
    function incident_update_amount_interface($id)
    {
        return "<input type='text' name='{$id}' id='{$id}' size='10' /> {$GLOBALS['strMinutes']}<br />{$GLOBALS['strForRefundsThisShouldBeNegative']}";
    }

    
    /**
     * (non-PHPdoc)
     * @see Billable::incident_update_amount_text()
     * @author Paul Heaney
     */
    function incident_update_amount_text($amount, $description)
    {
        return "[b]{$GLOBALS['strAmount']}[/b]: {$amount} {$GLOBALS['strMinutes']}\n\n{$description}";
    }
    
    
    /**
     * (non-PHPdoc)
     * @see Billable::incident_log_update_summary()
     * @author Paul Heaney
     */
    function incident_log_update_summary($amount)
    {
        $inminutes = ceil($amount); // Always round up
        return "{$GLOBALS['strDuration']}: {$inminutes} {$GLOBALS['strMinutes']}";
    }
}




class IncidentBillable extends Billable {
    
    public $billing_type_name = 'incident';
    public $uses_billing_matrix = FALSE;
   
    function close_incident($incidentid)
    {
        global $CONFIG, $now;
        
        if (!(get_billable_object_from_incident_id($incidentid) instanceof IncidentBillable))
        {
            trigger_error("Trying to close a non IncidentBillable incident with the IncidentBillable function");
        } 
        
        // Get incident cost for the incident/contract
        $contractid = incident_maintid($incidentid);
        $unitrate = get_unit_rate($contractid);
        
        $sql = "SELECT (SELECT sum(duration) FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid = {$incidentid} and duration < 0) AS refunds, ";
        $sql .= "(SELECT sum(duration) FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid= {$incidentid} and duration > 0) AS addititions";
        
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        
        $refunds = 0;
        $additions = 0;
        
        if (mysql_num_rows($result) > 0)
        {
            list($refunds, $additions) = mysql_fetch_row($result);
        }
        
        $totalunits = 1 + $refunds + $additions;
        $totalbillableunits = 1 + $additions;
        $totalcost = ($totalunits * $unitrate) * -1;  
        
        return $this->create_transaction_awaiting_approval($incidentid, $contractid, $totalunits, $unitrate, $totalbillableunits, $totalunits, $refunds, $totalcost, '');
    }
    
    /**
     * Incident billable incidents should reserve a incident, 
     * @todo IMPLEMENT
     * @see Billable::open_incident()
     */
    function open_incident($incidentid)
    {
        return true; // TODO this should reserve an incident,  - close_incident needs updating to reflect that we now have a reservation.
    }
    
    
    function contract_unit_balance($contractid, $includenonapproved = FALSE, $includereserved = TRUE, $showonlycurrentlyvalid = TRUE)
    {
        global $now;
        
        $unitbalance = 0;
        
        $sql = "SELECT * FROM `{$GLOBALS['dbService']}` WHERE contractid = {$contractid} ";
        
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
                $rate = $service->rate;
                $unitbalance += ( $service->balance / $rate );
            }
            
            if ($includenonapproved)
            {
                $awaiting = contract_transaction_total($contractid, BILLING_AWAITINGAPPROVAL);
                if ($awaiting != 0) $unitbalance += round($awaiting / $rate);
            }
            
            if ($includereserved)
            {
                $reserved = contract_transaction_total($contractid, BILLING_RESERVED);
                if ($reserved != 0) $unitbalance += round($reserved / $rate);
            }
        }
        
        
        return floor($unitbalance);        
    }

    
    function approve_incident_transaction($transactionid)
    {
        global $CONFIG;
        
        $rtnvalue = TRUE;
        
        // Check transaction exists, and is awaiting approval and is an incident
        $sql = "SELECT l.linkcolref, t.serviceid FROM `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbTransactions']}` AS t ";
        $sql .= "WHERE t.transactionid = l.origcolref AND t.transactionstatus = ".BILLING_AWAITINGAPPROVAL." AND l.linktype = 6 AND t.transactionid = {$transactionid}";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("Error identify incident transaction. ".mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) > 0)
        {
            list($incidentid, $serviceid) = mysql_fetch_row($result);
        
            $unitrate = get_service_unitrate($serviceid);
            
            $sqlUpdates = "SELECT (SELECT sum(duration) FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid = {$incidentid} and duration < 0) AS refunds, ";
            $sqlUpdates .= "(SELECT sum(duration) FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid= {$incidentid} and duration > 0) AS addititions";
            
            $resultUpdates = mysql_query($sqlUpdates);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            
            $refunds = 0;
            $additions = 0;
            
            if (mysql_num_rows($resultUpdates) > 0)
            {
                list($refunds, $additions) = mysql_fetch_row($resultUpdates);
            }
            
            $totalunits = 1 + $refunds + $additions;
            $totalbillableunits = 1 + $additions;
            $totalcost = ($totalunits * $unitrate) * -1;
           
            $desc = trim(sprintf($GLOBALS['strBillableIncidentSummary'], $incidentid, 1, $CONFIG['currency_symbol'], $unitrate, ''));
        
            $rtn = update_contract_balance(incident_maintid($incidentid), $desc, $totalcost, $serviceid, $transactionid, $totalbillableunits, $totalunits, $refunds);
        
            if ($rtn == FALSE)
            {
                $rtnvalue = FALSE;
            }
        }
        else
        {
            $rtnvalue = FALSE;
        }
        
        return $rtnvalue;
    }

    
    function amount_used_incident($incidentid)
    {
        $contractid = incident_maintid($incidentid);
        return get_unit_rate($contractid);
    }


    function produce_site_approvals_table($siteid, $formname, $startdate, $enddate)
    {
        global $CONFIG;
        
        $used = FALSE;
        
        $sitetotalawaitingapproval = 0;
        
        $sitetotalsbillablewaitingapproval = 0;
        $refundedunapproved = 0;

        $str = "<table align='center' width='80%'>";
        $str .= "<tr>";
        $str .= "<th><input type='checkbox' name='selectAll' value='CheckAll' onclick=\"checkAll({$sitenamenospaces}, this.checked);\" /></th>";
        $str .= "<th>{$GLOBALS['strID']}</th><th>{$GLOBALS['strIncidentTitle']}</th><th>{$GLOBALS['strContact']}</th>";
        $str .= "<th>{$GLOBALS['strEngineer']}</th><th>{$GLOBALS['strOpened']}</th><th>{$GLOBALS['strClosed']}</th>";
        $str .= "<th>{$GLOBALS['strTotalUnits']}</th><th>{$GLOBALS['strCredits']}</th>";
        $str .= "<th>{$GLOBALS['strBill']}</th><th>{$GLOBALS['strActions']}</th></tr>\n";
        $str .= "<tr>";
        
        $sql = "SELECT i.id, i.owner, i.contact, i.title, i.closed, i.opened, t.transactionid FROM `{$GLOBALS['dbTransactions']}` AS t, `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbIncidents']}` AS i ";
        $sql .= ", `{$GLOBALS['dbContacts']}` AS c, `{$GLOBALS['dbMaintenance']}` AS m WHERE ";
        $sql .= "t.transactionid = l.origcolref AND t.transactionstatus = ".BILLING_AWAITINGAPPROVAL." AND linktype= 6 AND l.linkcolref = i.id AND i.contact = c.id AND i.maintenanceid = m.id AND c.siteid = {$siteid} ";
        $sql .= "AND m.billingtype = 'IncidentBillable' ";
        if ($startdate != 0)
        {
            $sql .= "AND i.closed >= {$startdate} ";
        }
        
        if ($enddate != 0)
        {
            $sql .= "AND i.closed <= {$enddate} ";
        }
        $sql .= "ORDER BY i.closed";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(), E_USER_WARNING);
            return FALSE;
        }
        
        $units = 0;
        
        if (mysql_num_rows($result) > 0)
        {
            $shade = 'shade1';
        
            while ($obj = mysql_fetch_object($result))
            {
                $used = TRUE;
                $unitrate = get_unit_rate(incident_maintid($obj->id));
                
                $unapprovable = FALSE;
                
                if ($unitrate == -1) $unapprovable = TRUE;
                
                
                $sqlIncident = "SELECT (SELECT sum(duration) FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid = {$obj->id} and duration < 0) AS refunds, ";
                $sqlIncident .= "(SELECT sum(duration) FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid= {$obj->id} and duration > 0) AS addititions";
                $resultIncident = mysql_query($sqlIncident);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                
                $refunds = 0;
                $additions = 0;

                if (mysql_num_rows($resultIncident) > 0)
                {
                    list($refunds, $additions) = mysql_fetch_row($resultIncident);
                }

                if (empty($refunds)) $refunds = 0;
                if (empty($additions)) $additions = 0;

                $totalunits = 1 + $refunds + $additions;
                $totalbillableunits = 1 + $additions;
                $totalcost = $totalunits * $unitrate;
                
                $line = "<tr class='{$shade}'><td style='text-align: center'>";
                
                if (!$unapprovable)
                {
                    $line .= "<input type='checkbox' name='selected[]' value='{$obj->transactionid}' />";
                }

                $line .= "</td>";
                $line .= "<td>".html_incident_popup_link($obj->id, $obj->id)."</td>";
                $line .= "<td>{$obj->title}</td><td>".contact_realname($obj->contact)."</td>";
                $line .= "<td>".user_realname($obj->owner)."</td>";
                $line .= "<td>".ldate($CONFIG['dateformat_datetime'], $obj->opened)."</td><td>".ldate($CONFIG['dateformat_datetime'], $obj->closed)."</td>";
                $line .= "<td>{$totalbillableunits}</td><td>".($refunds * -1 )."</td><td>{$CONFIG['currency_symbol']} {$totalcost}</td>";

                $line .= "<td>";
                
                if ($unapprovable)
                {
                    $line .= $GLOBALS['strUnapprovable'];
                }
                else
                {
                    $operations[$GLOBALS['strApprove']] = array('url' => "{$_SERVER['PHP_SELF']}?mode=approve&amp;transactionid={$obj->transactionid}&amp;startdate={$startdateorig}&amp;enddate={$enddateorig}&amp;showonlyapproved={$showonlyapproved}"); // $showonlyapproved not passed in 
                    $operations[$GLOBALS['strAdjust']] = array('url' => "billing_update_incident_balance.php?incidentid={$obj->id}");
                    $line .= html_action_links($operations);
                    $sitetotalawaitingapproval += $unitrate;
                
                    $sitetotalsbillablewaitingapproval += $totalbillableunits;
                    $refundedunapproved += $refunds;
                }
                
                $line .= "</td>";
                
                $line .= "</tr>\n";
                
                $str .= $line;
                
                if ($shade == "shade1") $shade = "shade2";
                else $shade = "shade1";
            }
        }        
        
        $str .= "<tr><td><input type='submit' value='{$GLOBALS['strApprove']}' />";
        $str .= "</td><td colspan='5'></td>";
        $str .= "<td>{$GLOBALS['strTOTALS']}</td>";
        $str .= "<td>{$sitetotalsbillablewaitingapproval}</td>";
        $str .= "<td>".( $refundedunapproved * -1 )."</td>";
        $str.= "<td>{$CONFIG['currency_symbol']} {$sitetotalawaitingapproval}</td><td></td>";
        
        $str .= "</tr>\n";
        
        $str .= "<tr><td align='right' colspan='6'></td>";
        
        $str .= "</table>\n";
        
        if (!$used) $str = FALSE;
        
        return $str;
        
    }


    function update_incident_transaction_record($incidentid)
    {
        $toReturn = FALSE;
        
        $contractid = incident_maintid($incidentid);
        $unitrate = get_unit_rate($contractid);
        
        $desc = trim(sprintf($GLOBALS['strBillableIncidentSummary'], $incidentid, 1, $CONFIG['currency_symbol'], $unitrate, ''));
        
        $transactionid = get_incident_transactionid($incidentid);
        if ($transactionid != FALSE)
        {
            $toReturn = update_transaction($transactionid, $cost, $desc, BILLING_AWAITINGAPPROVAL);
        }
        
        return $toReturn;
    }


    function billing_matrix_selector($id, $selected='') {
        // We don't use a billing matrix for Incidents
        return "";
    }
    

    function display_name()
    {
        return $GLOBALS['strPerIncident'];
    }


    function incident_update_amount_interface($id)
    {
        return "<input type='checkbox' name='{$id}' id='{$id}' value='-1' /> {$GLOBALS['strRefundIncident']}";
    }
    
    /**
     * (non-PHPdoc)
     * @see Billable::incident_update_amount_text()
     * @author Paul Heaney
     */
    function incident_update_amount_text($amount, $description)
    {
        $toReturn = $GLOBALS['strInvalidParameter'];
        
        if ($amount == -1)
        {
            $toReturn = "[b]{$GLOBALS['strIncidentRefunded']}[/b]\n\n{$description}";
        }
        else
        {
            trigger_error("IncidentBillable.incident_update_amount_text passed an amount of ".$amount." we only support -1");
        }

        return $toReturn;
    }


    /**
     * (non-PHPdoc)
     * @see Billable::incident_log_update_summary()
     * @author Paul Heaney
     */
    function incident_log_update_summary($amount)
    {
        $toReturn = $GLOBALS['strInvalidParameter'];
        
        if ($amount == -1)
        {
            $toReturn = $GLOBALS['strIncidentRefunded'];
        }
        else
        {
            trigger_error("IncidentBillable.incident_log_update_summary passed an amount of ".$amount." we only support -1");
        }
        
        return $toReturn;
    }
}