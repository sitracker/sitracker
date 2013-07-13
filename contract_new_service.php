<?php
// services/add.php - Adds a new service record
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional! 24May2009

require ('core.php');

$permission = PERM_NEW_SERVICE;

require_once (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require_once (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$contractid = clean_int($_REQUEST['contractid']);
$submit = cleanvar($_REQUEST['submit']);

$title = ("$strContract - $strNewService");

// Contract ID must not be blank
if (empty($contractid))
{
    html_redirect('main.php', FALSE);
    exit;
}

// Find the latest end date so we can suggest a start date
$sql = "SELECT enddate FROM `{$dbService}` WHERE contractid = {$contractid} ORDER BY enddate DESC LIMIT 1";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

if (mysql_num_rows($result) > 0)
{
    list($prev_enddate) = mysql_fetch_row($result);
    $suggested_startdate = mysql2date($prev_enddate) + 86400; // the next day
}
else
{
    $suggested_startdate = $now; // Today
}

if (empty($submit) OR !empty($_SESSION['formerrors']['new_service']))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo show_form_errors('new_service');
    clear_form_errors('new_service');
    echo "<h2>{$strNewService}</h2>\n";

    $timed = is_contract_timed($contractid);

    echo "<form id='serviceform' name='serviceform' action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_submit(\"{$strAreYouSureMakeTheseChanges}\");'>";
    echo "<table class='vertical'>";
    if ($timed) echo "<thead>\n";
    echo "<tr><th>{$strStartDate}</th>";
    echo "<td><input type='text' name='startdate' id='startdate' size='10' ";
    if ($_SESSION['formdata']['new_service']['startdate'] != '')
    {
        echo "value='{$_SESSION['formdata']['new_service']['startdate']}'";
    }
    else
    {
        echo "value='".date('Y-m-d', $suggested_startdate)."'";
    }
    echo "/> ";
    echo date_picker('serviceform.startdate');
    echo "</td></tr>";

    echo "<tr><th>{$strEndDate}</th>";
    echo "<td><input type='text' name='enddate' id='enddate' size='10'";
    if ($_SESSION['formdata']['new_service']['enddate'] != '')
    {
        echo "value='{$_SESSION['formdata']['new_service']['enddate']}'";
    }
    echo "/> ";
    echo date_picker('serviceform.enddate');
    echo "</td></tr>";

    echo "<tr><th>{$strTitle}</th><td>";
    echo "<input type='text' id='title' name='title' /></td></tr>";

    echo "<tr><th>{$strNotes}</th><td>";
    echo "<textarea rows='5' cols='20' name='notes'></textarea></td></tr>";
    
    echo "<tr><th>{$strBilling}</th>";
    echo "<td>";
    if ($timed)
    {
        $billtype = get_billable_object_from_contract_id($contractid);
        if ($billtype instanceof Billable)
        {
            echo $billtype->display_name();
        }

        echo "<input type='hidden' id='billtype' name='billtype' value='{$billtype}' />";
    }
    else
    {
        echo "<label>";
        echo "<input type='radio' name='billtype' value='' checked='checked' disabled='disabled' /> ";
        echo "{$strNone}</label>";
        echo help_link('NewServiceNoBilling');
    }
    echo "</td></tr>\n";

    if ($timed)
    {
        echo "</thead>\n";
        echo "<tbody id='billingsection'>\n";

        echo "<tr><th>{$strCustomerReference}</th>";
        echo "<td><input type='text' id='cust_ref' name='cust_ref' ";
        if ($_SESSION['formdata']['new_service']['cust_ref'] != '')
        {
            echo "value='{$_SESSION['formdata']['new_service']['cust_ref']}' ";
        }
        echo "/></td></tr>\n";

        echo "<tr><th>{$strCustomerReferenceDate}</th>";
        echo "<td><input type='text' name='cust_ref_date' id='cust_ref_date' size='10' ";
        if ($_SESSION['formdata']['new_service']['cust_ref_date'] != '')
        {
            echo "value='{$_SESSION['formdata']['new_service']['cust_ref_date']}' />";
        }
        else
        {
            echo "value='".date('Y-m-d', $now)."' />";
        }
        echo date_picker('serviceform.cust_ref_date');
        echo " </td></tr>\n";

        echo "<tr><th>{$strCreditAmount}</th>";
        echo "<td>{$CONFIG['currency_symbol']} ";
        echo "<input class='required' type='text' name='amount' id='amount' size='5' ";
        if ($_SESSION['formdata']['new_service']['amount'] != '')
        {
            echo "value='{$_SESSION['formdata']['new_service']['amount']}'";
        }
        echo "/>";
        echo " <span class='required'>{$strRequired}</span></td></tr>\n";

        echo "<tr id='unitratesection' {$unitratestyle}><th>{$strUnitRate}</th>";
        echo "<td>{$CONFIG['currency_symbol']} ";
        echo "<input class='required' type='text' name='unitrate' id='unitrate' size='5' ";
        if ($_SESSION['formdata']['new_service']['unitrate'] != '')
        {
            echo "value='{$_SESSION['formdata']['new_service']['unitrate']}'";
        }
        echo "/>";
        echo " <span class='required'>{$strRequired}</span></td></tr>\n";

        echo "<tr>";
        echo "<th>{$strFreeOfCharge}</th>";
        echo "<td><input type='checkbox' id='foc' name='foc' value='yes' /> {$strAboveMustBeCompletedToAllowDeductions}</td>";
        echo "</tr>\n";

        echo "</tbody>\n";
    }
    echo "</table>\n\n";
    echo "<input type='hidden' name='contractid' value='{$contractid}' />";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input name='submit' type='submit' value=\"{$strSave}\" /></p>";
    echo "</form>\n";

    echo "<p class='return'><a href='contract_details.php?id={$contractid}'>{$strReturnWithoutSaving}</a></p>";

    clear_form_data('new_service');
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    // External variables
    $contractid = clean_int($_POST['contractid']);
    $startdate = strtotime($_REQUEST['startdate']);
    if ($startdate > 0) $startdate = date('Y-m-d', $startdate);
    else $startdate = date('Y-m-d', $now);
    $enddate = strtotime($_REQUEST['enddate']);
    if ($enddate > 0) $enddate = date('Y-m-d', $enddate);
    else $enddate = date('Y-m-d', strtotime($startdate) + 31556926); // No date set so we default to one year after start

    $billtype = cleanvar($_REQUEST['billtype']);

    $amount =  clean_float($_POST['amount']);
    if ($amount == '') $amount = 0;
    $unitrate = clean_float($_POST['unitrate']);
    $notes = cleanvar($_REQUEST['notes']);
    $title = cleanvar($_REQUEST['title']);

    $_SESSION['formdata']['new_service'] = cleanvar($_POST, TRUE, FALSE, FALSE,
                                                     array("@"), array("'" => '"'));

    if (isset($billtype) AND empty($unitrate))
    {
        $errors++;
        $_SESSION['formerrors']['new_service']['unitrate'] = sprintf($strFieldMustNotBeBlank, $strUnitRate);
    }

    if (isset($billtype) AND $amount == 0)
    {
        $errors++;
        $_SESSION['formerrors']['new_service']['amount'] = sprintf($strFieldMustNotBeBlank, $strCreditAmount);
    }

    if ($errors == 0)
    {
        if (!empty($billtype))
        {
            $foc = cleanvar($_REQUEST['foc']);
            if (empty($foc)) $foc = 'no';

            $cust_ref = cleanvar($_REQUEST['cust_ref']);
            $cust_ref_date = cleanvar($_REQUEST['cust_ref_date']);

            $sql = "INSERT INTO `{$dbService}` (contractid, startdate, enddate, creditamount, rate, cust_ref, cust_ref_date, title, notes, foc) ";
            $sql .= "VALUES ('{$contractid}', '{$startdate}', '{$enddate}', '{$amount}', '{$unitrate}', '{$cust_ref}', '{$cust_ref_date}', '{$title}', '{$notes}', '{$foc}')";
        }
        else
        {
            $sql = "INSERT INTO `{$dbService}` (contractid, startdate, enddate, title, notes) ";
            $sql .= "VALUES ('{$contractid}', '{$startdate}', '{$enddate}', '{$title}', '{$notes}')";
        }

        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
        if (mysql_affected_rows() < 1)
        {
            trigger_error("Insert failed", E_USER_ERROR);
            $errors++;
        }

        $serviceid = mysql_insert_id();

        if ($amount != 0)
        {
            update_contract_balance($contractid, "New service", $amount, $serviceid);
        }

        $sql = "SELECT expirydate FROM `{$dbMaintenance}` WHERE id = {$contractid}";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($result) > 0)
        {
            $obj = mysql_fetch_object($result);
            if ($obj->expirydate < strtotime($enddate))
            {
                $update = "UPDATE `{$dbMaintenance}` ";
                $update .= "SET expirydate = '".strtotime($enddate)."' ";
                $update .= "WHERE id = {$contractid}";
                mysql_query($update);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
                if (mysql_affected_rows() < 1) trigger_error("Expiry of contract update failed", E_USER_ERROR);
            }
        }

        clear_form_data('new_service');
    }

    if ($errors == 0)
    {
        html_redirect("contract_details.php?id={$contractid}", TRUE);
    }
    else
    {
        html_redirect("contract_new_service.php?contractid={$contractid}", FALSE);
    }

}
?>
