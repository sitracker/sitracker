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
    
    /**
     * This function closes an incident and performs type specific operations
     * @param int $incidentid
     */
    abstract function close_incident($incidentid);
    abstract function contract_unit_balance($contractid,$includenonapproved = FALSE, $includereserved = TRUE, $showonlycurrentlyvalid = TRUE);
    abstract function approve_incident_transaction($transactionid);
    abstract function amount_used_incident($incidentid);
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
            // $numberofunits += $bills['refunds'];
        
            $cost = (($totalbillableunits + $totalrefunds)  * $unitrate) * -1;
        
            $desc = trim(sprintf($GLOBALS['strBillableIncidentSummary'], $incidentid, $numberofunits, $CONFIG['currency_symbol'], $unitrate, $s));
        
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
        
        }

        return $rtnvalue;
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
        global $dbLinks, $sit, $CONFIG, $strUnits;
        
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
    
}




class IncidentBillable extends Billable {
   
    function close_incident($incidentid)
    {
        trigger_error("Not yet implemented");
    }
    
    function contract_unit_balance($contractid,$includenonapproved = FALSE, $includereserved = TRUE, $showonlycurrentlyvalid = TRUE)
    {
        trigger_error("Not yet implemented");
    }
    
    function approve_incident_transaction($transactionid)
    {
        trigger_error("Not yet implemented");
    }
    
    function amount_used_incident($incidentid)
    {
        trigger_error("Not yet implemented");
    }
}