<?php
// billing.class.php - Representation of billing types
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2013 The Support Incident Tracker Project

//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.


// This lib is currently included at the end of billing.class.php

// TODO prevent incident opening when your out of overdraft

class PointsBillable extends Billable {

    public $new_billing_matrix_page = 'billing_matrix_new_point_based.php';
    public $edit_billing_matrix_page = 'billing_matrix_edit_points_based.php';
    
    function close_incident($incidentid)
    {
        global $CONFIG, $now;
        
        if (!(get_billable_object_from_incident_id($incidentid) instanceof PointsBillable))
        {
            trigger_error("Trying to close a non PointsBillable incident with the PointsBillable function");
        }
        
        // Get incident cost for the incident/contract
        $contractid = incident_maintid($incidentid);
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
        
        $totalunits = $refunds + $additions;
        $totalbillableunits = $additions;
        $totalcost = $totalunits * -1;
        
        $transactionid = get_incident_transactionid($incidentid);
        return transition_reserved_monites($transactionid, $totalcost);
    }


    function open_incident($incidentid)
    {
        $serviceid = get_serviceid(incident_maintid($incidentid));
        if ($serviceid < 1) trigger_error("Invalid service ID", E_USER_ERROR);
        else 
        {
            $points = points_incident_base_points($incidentid);
            $desc = $this->format_amount($points);
            reserve_monies($serviceid, 6, $incidentid, $points, $desc);
        }
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
                $unitbalance += $service->balance;
            }
        
            if ($includenonapproved)
            {
                $awaiting = contract_transaction_total($contractid, BILLING_AWAITINGAPPROVAL);
                if ($awaiting != 0) $unitbalance += $awaiting;
            }
        
            if ($includereserved)
            {
                $reserved = contract_transaction_total($contractid, BILLING_RESERVED);
                if ($reserved != 0) $unitbalance += $reserved;
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
        
            $totalpoints = $refunds + $additions * -1;
            $totalbillableunits = $additions;
             
            $desc = $this->format_amount($totalpoints);
        
            $rtn = update_contract_balance(incident_maintid($incidentid), $desc, $totalpoints, $serviceid, $transactionid, $totalbillableunits, $totalpoints, $refunds);
        
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
        $toReturn = FALSE;
        
        $sql = "SELECT SUM(duration) AS points FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid = {$incidentid}";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) >= 1)
        {
            $obj = mysql_fetch_object($result);
            $toReturn = $obj->points;
        }
        
        return  $toReturn;
    }
    

    function produce_site_approvals_table($siteid, $formname, $startdate, $enddate)
    {
        global $CONFIG;
        
        $used = FALSE;
        
        $sitetotalawaitingapproval = 0;
        
        $sitetotalsbillablewaitingapproval = 0;
        $refundedunapproved = 0;
        
        $str .= "<h3>".$this->display_name()."</h3>";
        $str .= "<table align='center' width='80%'>";
        $str .= "<tr>";
        $str .= "<th><input type='checkbox' name='selectAll' value='CheckAll' onclick=\"checkAll({$sitenamenospaces}, this.checked);\" /></th>";
        $str .= "<th>{$GLOBALS['strID']}</th><th>{$GLOBALS['strIncidentTitle']}</th><th>{$GLOBALS['strContact']}</th>";
        $str .= "<th>{$GLOBALS['strEngineer']}</th><th>{$GLOBALS['strOpened']}</th><th>{$GLOBALS['strClosed']}</th>";
        $str .= "<th>{$GLOBALS['strTotalPoints']}</th><th>{$GLOBALS['strCredits']}</th>";
        $str .= "<th>{$GLOBALS['strBill']}</th><th>{$GLOBALS['strActions']}</th></tr>\n";
        $str .= "<tr>";
        
        $sql = "SELECT i.id, i.owner, i.contact, i.title, i.closed, i.opened, t.transactionid FROM `{$GLOBALS['dbTransactions']}` AS t, `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbIncidents']}` AS i ";
        $sql .= ", `{$GLOBALS['dbContacts']}` AS c, `{$GLOBALS['dbMaintenance']}` AS m WHERE ";
        $sql .= "t.transactionid = l.origcolref AND t.transactionstatus = ".BILLING_AWAITINGAPPROVAL." AND linktype= 6 AND l.linkcolref = i.id AND i.contact = c.id AND i.maintenanceid = m.id AND c.siteid = {$siteid} ";
        $sql .= "AND m.billingtype = 'PointsBillable' ";
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
        
                $totalunits = $refunds + $additions;
                $totalbillableunits = $additions;
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
                $line .= "<td>{$totalbillableunits}</td><td>".($refunds * -1 )."</td><td>{$totalcost}</td>";
        
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
        $str.= "<td>{$sitetotalawaitingapproval}</td><td></td>";
        
        $str .= "</tr>\n";
        
        $str .= "<tr><td align='right' colspan='6'></td>";
        
        $str .= "</table>\n";
        
        if (!$used) $str = FALSE;
        
        return $str;
    }


    function update_incident_transaction_record($incidentid)
    {
        $toReturn = FALSE;
        
        $points = $this->amount_used_incident($incidentid);
        
        if ($points)
        {
            $desc = $this->format_amount($points);
            
            $transactionid = get_incident_transactionid($incidentid);
            if ($transactionid != FALSE)
            {
                $toReturn = update_transaction($transactionid, $cost, $desc, BILLING_AWAITINGAPPROVAL);
            }
        }       
        
        return $toReturn;
    }


    function billing_matrix_selector($id, $selected='')
    {
        $sql = "SELECT DISTINCT tag FROM `{$GLOBALS['dbBillingMatrixPoints']}`";
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
    
    
    function show_billing_matrix_details()
    {
        $html = '';
        $sql = "SELECT DISTINCT tag FROM `{$GLOBALS['dbBillingMatrixPoints']}";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    
        if (mysql_num_rows($result) >= 1)
        {
            while ($matrix = mysql_fetch_object($result))
            {
                $sql = "SELECT * FROM `{$GLOBALS['dbBillingMatrixPoints']}` WHERE tag = '{$matrix->tag}' ORDER BY points ASC";
                $matrixresult = mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    
                $html .= "<table class='maintable'>";
                $html .= "<thead><tr><th colspan='2'>{$matrix->tag} <a href='{$this->edit_billing_matrix_page}?type=PointsBillable&amp;tag={$matrix->tag}'>{$GLOBALS['strEdit']}</a></th></tr></thead>\n";
                
                $html .= "<tr><th>{$GLOBALS['strName']}</th><th>{$GLOBALS['strPoints']}</th></tr>\n";
                $shade = 'shade1';
                while ($obj = mysql_fetch_object($matrixresult))
                {
                    $html .= "<tr class='{$shade}'><td>{$obj->name}</td><td>{$obj->points}</td></tr>\n";
                    if ($shade == 'shade1') $shade = 'shade2';
                    else $shade = 'shade1';
                }
                $html .= "</table>";
            }
        }
    
        return $html;
    }


    function display_name()
    {
        return $GLOBALS['strPointsBased'];
    }


    function incident_update_amount_interface($id)
    {
        return "<input type='text' name='{$id}' id='{$id}' size='10' /> {$GLOBALS['strPoints']}<br />{$GLOBALS['strForRefundsThisShouldBeNegative']}";
    }


    function incident_update_amount_text($amount, $description)
    {
        return "[b]{$GLOBALS['strPoints']}[/b]: {$amount} {$GLOBALS['strPoints']}\n\n{$description}";
    }


    function incident_log_update_summary($amount)
    {
        return $this->format_amount($amount); 
    }
    
    
    /**
     * Gets the points drop down available for an incident
     * @author Paul Heaney
     * @param String $id The name and ID for the option element 
     * @param int $selected The number of points that should be preselected
     * @param int $incidentid
     * @param bool $showpoints - Show the points in brackets after the name
     * @return String The HTML points dropdown
     */
    function get_points_drop_down($id, $selected, $incidentid, $showpoints=TRUE)
    {
        $toReturn = $GLOBALS['strNoBillingMatrixDefined'];

        $billingmatrix = get_contract_billing_matrix(incident_maintid($incidentid), get_class($this));
        $toReturn = points_drop_down($id, $selected, $billingmatrix, $showpoints, TRUE);

        return $toReturn;
    }


    /**
     * Does this billing method use the unit rate?
     * @author Paul Heaney
     * @return boolean TRUE if this billing method uses the unit rate (like Unit or Incident) or FALSE otherwise
     */
    function uses_unit_rate()
    {
        return FALSE;
    }
    
    
    /**
     * Formats an amount on the billing, this overides the parent and returns X Point(s)
     * @author Paul Heaney
     * @param float $amount The amount to format
     * @return string the representation of the amount of the billing type
     */
    function format_amount($amount)
    {
        if ($amount === 1) $desc = sprintf($GLOBALS['strXPoint'], $amount * -1);
        else $desc = sprintf($GLOBALS['strXPoints'], $amount * 1);
        
        return $desc; 
    }
}


function points_billing_incident_edit()
{
    global $id, $billingObj;
        
    if ($billingObj instanceof PointsBillable)
    {
        $current_base_points = points_incident_base_points($id);
        
        echo "<tr>";
        echo "<th>{$GLOBALS['strIncidentPoints']}</th>";
        echo "<td>";
        echo $billingObj->get_points_drop_down('points_base', $current_base_points, $id);
        echo "</td>";
        echo "</tr>";
    }
}


plugin_register('incident_edit_form', 'points_billing_incident_edit');


/**
 * Function which registers with the incident_edited plugin hook
 * and saves the number of points used when editing an incident
 * @author Paul Heaney
 */
function points_billing_incident_edited()
{
    global $id;
    
    $billingObj = get_billable_object_from_incident_id($id);
    
    if ($billingObj instanceof PointsBillable)
    {
        $new_points = clean_float($_REQUEST['points_base']);
    
        $sql = "UPDATE `{$GLOBALS['dbUpdates']}` SET duration = {$new_points} WHERE incidentid = {$id} AND type = 'opening' ORDER by timestamp ASC LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    }
}

plugin_register('incident_edited', 'points_billing_incident_edited');


/**
 * Function which registers with the incident_edit_submitted plugin hook
 * and checks performs the required validation (that the number of points is not decreasing)
 * also updates $header with the points change so teh updat elog is adjusted
 * @author Paul Heaney
 */
function points_billing_incident_edit_submitted()
{
    global $id, $errors, $header, $SYSLANG;
    
    $billingObj = get_billable_object_from_incident_id($id);
    
    if ($billingObj instanceof PointsBillable)
    {
        $new_points = clean_float($_REQUEST['points_base']);
        $current_base_points = points_incident_base_points($id);
        
        if ($new_points < $current_base_points)
        {
            if ($title == '')
            {
                $errors += 1;
                $_SESSION['formerrors']['edit_incident']['points_base'] = sprintf($GLOBALS['strFieldMustNotBeBlank'], $GLOBALS['strIncidentPoints']);
            }
        }
        else
        {
            $billingmatrix = get_contract_billing_matrix(incident_maintid($id), 'PointsBillable');
            $points_current_name = points_name($billingmatrix, $current_base_points);
            $points_new_name = points_name($billingmatrix, $new_points);
            $header .= "{$SYSLANG['strIncidentPoints']}: <b>{$points_current_name} ({$current_base_points})</b> -&gt; <b>{$points_new_name} ({$new_points})</b>";
        }
    }
}


plugin_register('incident_edit_submitted', 'points_billing_incident_edit_submitted');


/**
 * Function which registers with the incident_new_form plugin hook to 
 * add a drop down on incidents logged against points based contracts 
 * to set the base number of points for an incident.
 * 
 * This function echos HTML
 * 
 * @author Paul Heaney
 */
function points_incident_new_form()
{
    global $maintid;

    $billingObj = get_billable_object_from_contract_id($maintid);
    
    if ($billingObj instanceof PointsBillable)
    {
        $billingmatrix = get_contract_billing_matrix($maintid, 'PointsBillable');
        
        echo "<tr><td></td>";
        echo "<td colspan='2'><strong>{$GLOBALS['strIncidentPoints']}:</strong><br />".points_drop_down("points_base", '', $billingmatrix)."</td>";
        echo "</tr>";        
    }
}


plugin_register('incident_new_form', 'points_incident_new_form');


/**
 * Function which registers with the incident_new_saved plugin hook 
 * and saves the number of points used when opening an incident
 * @author Paul Heaney
 */
function points_incident_new_saved()
{
    global $incidentid;
    
    $billingObj = get_billable_object_from_incident_id($incidentid);
    
    if ($billingObj instanceof PointsBillable)
    {
        $points = clean_float($_REQUEST['points_base']);
    
        $sql = "UPDATE `{$GLOBALS['dbUpdates']}` SET duration = {$points} WHERE incidentid = {$incidentid} AND type = 'opening' ORDER by timestamp ASC LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    }
}


plugin_register('incident_new_saved', 'points_incident_new_saved');


/**
 * 
 * Gets the points drop down for a given billing matrix
 * @author Paul Heaney
 * @param String $id The name and ID for the option element 
 * @param int $selected The number of points that should be preselected
 * @param String $billingmatrix Name of the billing matrix to get the available points for
 * @param bool $showpoints - Show the points in brackets after the name
 * @return string
 */
function points_drop_down($id, $selected, $billingmatrix, $showpoints = TRUE)
{
    $sql = "SELECT name, points FROM `{$GLOBALS['dbBillingMatrixPoints']}` WHERE tag = '{$billingmatrix}' ORDER BY points ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) >= 1)
    {
        $html = "<select name='{$id}' id='{$id}'>\n";
        while ($obj = mysql_fetch_object($result))
        {
            $html .= "<option value='{$obj->points}'";
            if ($obj->points == $selected) $html .= " selected='selected'";
            if ($obj->points < $selected) $html .= " disabled='disabled' ";
            $html .= ">{$obj->name} ({$obj->points})</option>\n";
        }
        $html .= "</select>\n";
    }
    else
    {
        $html = "{$GLOBALS['strNoBillingMatrixDefined']}";
    }
    
    return $html;
}



function points_incident_base_points($incidentid)
{
    $toReturn = 0;    

    $sql = "SELECT duration FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid = {$incidentid} AND type = 'opening' ORDER by timestamp ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) >= 1)
    {
        $obj = mysql_fetch_object($result);
        $toReturn = $obj->duration;
    }
    
    return $toReturn;
}


/**
 * Gets the name associated with a number of points on a billing matrix
 * @author Paul Heaney
 * @param String $billingmatrix tag of the billing matrix
 * @param float $points The number of points
 * @return string The name associated with these points
 */
function points_name($billingmatrix, $points)
{
    $toReturn = '';

    $sql = "SELECT name FROM `{$GLOBALS['dbBillingMatrixPoints']}` WHERE tag = '{$billingmatrix}' AND points = {$points}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) >= 1)
    {
        $obj = mysql_fetch_object($result);
        $toReturn = $obj->name;
    }
    
    return $toReturn;
}