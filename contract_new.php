<?php
// contract_new.php - Add a new maintenance contract
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
$permission = PERM_CONTRACT_ADD; // Add Maintenance Contract
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNewContract;

// External variables
$action = clean_fixed_list($_REQUEST['action'], array('','showform','new'));
$siteid = clean_int($_REQUEST['siteid']);

// Show add maintenance form
if ($action == "showform" OR $action == '')
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo show_form_errors('new_contract');
    clear_form_errors('new_contract');
    echo "<h2>".icon('contract', 32)." ";
    echo "{$strNewContract}</h2>";
    plugin_do('contract_new');
    echo "<form id='new_contract' name='new_contract' action='{$_SERVER['PHP_SELF']}?action=new' method='post' onsubmit='return confirm_action(\"{$strAreYouSureAdd}\");'>";
    echo "<table class='maintable vertical'>";
    echo "<tr><th>{$strSite}</th><td>";
    echo site_drop_down("site", show_form_value('new_contract', 'site', $siteid), TRUE);
    echo " <span class='required'>{$strRequired}</span></td></tr>\n";
    echo "<tr><th>{$strContacts}</th><td>";

    $amountChecked = $allChecked = '';
    if ($_SESSION['formdata']['new_contract']['contacts'] == 'all') $allChecked = "checked='checked'"; 
    else $amountChecked = "checked='checked'";

    echo "<input value='amount' type='radio' name='contacts' {$amountChecked} />";

    echo "{$strLimitTo} <input size='2' name='numcontacts'  value='".show_form_value('new_contract', 'numcontacts', '0')."' /> {$strSupportedContacts} ({$str0MeansUnlimited})<br />";
    echo "<input type='radio' value='all' name='contacts' {$allChecked} />";
    echo "{$strAllSiteContactsSupported}";
    echo "</td></tr>\n";
    echo "<tr><th>{$strProduct}</th><td>";

    echo product_drop_down("product", show_form_value('new_contract', 'product', 0), TRUE)." <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr><th>{$strServiceLevel}</th><td>";
    echo "<table id='incident_types_table'>";
    echo "<tr><th>{$strIncidentType}</th><th>{$strServiceLevel}</th></tr>";
    echo incident_type_service_level_row();
    echo "</table>";
    echo "<a href=\"javascript:void(0);\" onclick=\"add_row_to_incident_sla_table('incident_types_table')\">{$strAdd}</a>\n";
    echo "</td></tr>\n";
    // check the initially selected service level to decide whether to show the extra hiddentimed section
    $timed = servicelevel_timed($sltag);

    echo "<tr><th colspan='2' style='text-align: left;'><br />{$strServicePeriod}</th></tr>\n";
    echo "<tr><th>{$strStartDate}</th>";
    echo "<td><input type='text' name='startdate' id='startdate' size='10' value='".date('Y-m-d', $now)."' /> ";
    echo date_picker('new_contract.startdate');
    echo "</td></tr>\n";

    echo "<tr><th>{$strExpiryDate}</th>";
    echo "<td><input class='required' name='expiry' size='10' ";
    if ($_SESSION['formdata']['new_contract']['expiry'] != '')
    {
        echo "value='{$_SESSION['formdata']['new_contract']['expiry']}'";
    }
    echo "/> ".date_picker('new_contract.expiry');
    echo "<label><input type='checkbox' name='noexpiry' ";
    if ($_SESSION['formdata']['new_contract']['noexpiry'] == "on")
    {
        echo "checked='checked' ";
    }
    echo "onclick=\"this.form.expiry.value=''\" /> {$strUnlimited}</label>";
    echo " <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr><th>{$strAdminContact}</th>";
    echo "<td>".contact_drop_down("admincontact", show_form_value('new_contract', 'admincontact', 0), TRUE, TRUE);
    echo " <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr><th>{$strNotes}</th><td><textarea cols='40' name='notes' rows='5'>{$_SESSION['formdata']['new_contract']['notes']}</textarea></td></tr>\n";

    plugin_do('contract_new_form');

    echo "<tbody id='hiddentimed'";
    if (!$timed) echo " style='display:none'";
    echo ">";

    echo "<tr><th>{$strBilling}</th>";
    echo "<td>";
    echo "<label>";
    echo "<input type='radio' name='billtype' value='UnitBillable' onchange=\"addcontract_display_billing_matrix('new_contract', '{$_SESSION['formdata']['new_contract']['billing_matrix']}');\" checked='checked' /> ";
    echo "{$strPerUnit}</label>";
    echo "<label>";
    echo "<input type='radio' name='billtype' value='IncidentBillable' onchange=\"addcontract_display_billing_matrix('new_contract', '{$_SESSION['formdata']['new_contract']['billing_matrix']}');\" /> ";
    echo "{$strPerIncident}</label>";
    echo "<label>";
    echo "<input type='radio' name='billtype' value='PointsBillable' onchange=\"addcontract_display_billing_matrix('new_contract', '{$_SESSION['formdata']['new_contract']['billing_matrix']}');\" /> ";
    echo "{$strPointsBased}</label>";
    echo "</td></tr>\n";

    echo "<tr><th>{$strCreditAmount}</th><td>{$CONFIG['currency_symbol']}";
    echo "<input maxlength='7' name='amount' size='5' class='required' value='".show_form_value('new_contract', 'amount', '0')."' /> ";
    echo "<span class='required'>{$strRequired}</span>".help_link("BillingCreditAmount")."</td></tr>\n";
    echo "<tr id='unitratesection'><th>{$strUnitRate}</th>";
    echo "<td>{$CONFIG['currency_symbol']} ";
    echo "<input class='required' type='text' name='unitrate' size='5' value='".show_form_value('new_contract', 'unitrate', '')."' />";
    echo " <span class='required'>{$strRequired}</span>".help_link("BillingUnitRate")."</td></tr>\n";

    echo "<tr><th>{$strBillingMatrix}</th>";
    echo "<td><div id='billingmatrix_cell'></div></td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>{$strFreeOfCharge}</th>";
    echo "<td><input type='checkbox' id='foc' name='foc' value='yes' ";
    if ($_SESSION['formdata']['new_contract']['foc'] == 'yes')
    {
        echo "checked='checked''  ";
    }
    echo "/> ".help_link("BillingFreeOfCharge")."{$strAboveMustBeCompletedToAllowDeductions}</td></tr>\n";
    echo "</tbody>\n";
    echo "<tr><th></th><td><a href=\"javascript:void(0);\" onclick=\"$('hidden').toggle();\">{$strMore}</a></td></tr>\n";
    echo "<tbody id='hidden' style='display:none'>";

    echo "<tr><th>{$strReseller}</th><td>";
    reseller_drop_down("reseller", show_form_value('new_contract', 'reseller', 1));
    echo "</td></tr>\n";

    echo "<tr><th>{$strLicenseQuantity}</th><td><input maxlength='7' name='licence_quantity' size='5' value='".show_form_value('new_contract', 'licence_quantity', 0)."' />";
    echo " ({$str0MeansUnlimited})</td></tr>\n";

    echo "<tr><th>{$strLicenseType}</th><td>";
    licence_type_drop_down("licence_type", show_form_value('new_contract', 'licence_type', LICENCE_SITE));
    echo "</td></tr>\n";

    echo "<tr><th>{$strIncidentPool}</th>";
    $incident_pools = explode(',', "{$strUnlimited},{$CONFIG['incident_pools']}");
    echo "<td>".array_drop_down($incident_pools, 'incident_poolid', show_form_value('new_contract', 'incident_poolid', $maint['incident_quantity']))."</td></tr>\n";

    plugin_do('contract_new_form_more');
    echo "</tbody>\n";

    echo "</table>\n";
    if ($timed) $timed = 'yes';
    else $timed = 'no';
    echo "<input type='hidden' id='timed' name='timed' value='{$timed}' />";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input name='submit' type='submit' value=\"{$strSave}\" /></p>";
    echo "</form>";
    echo "<p class='return'><a href=\"contracts.php\">{$strReturnWithoutSaving}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

    clear_form_data('new_contract');
}
elseif ($action == 'new')
{
    // External Variables
    $site = clean_int($_REQUEST['site']);
    $product = clean_int($_REQUEST['product']);
    $reseller = clean_int($_REQUEST['reseller']);
    $licence_quantity = clean_int($_REQUEST['licence_quantity']);
    $licence_type = clean_int($_REQUEST['licence_type']);
    $admincontact = clean_int($_REQUEST['admincontact']);
    $notes = clean_dbstring($_REQUEST['notes']);
    $incidentpoolid = clean_int($_REQUEST['incidentpoolid']);
    $term = clean_fixed_list($_REQUEST['term'], array('no','yes'));
    $contacts = cleanvar($_REQUEST['contacts']);
    $timed = cleanvar($_REQUEST['timed']);
    $startdate = strtotime($_REQUEST['startdate']);
    if ($startdate > 0) $startdate = date('Y-m-d', $startdate);
    else $startdate = date('Y-m-d',$now);
    $enddate = strtotime($_REQUEST['expiry']);
    if ($enddate > 0) $enddate = date('Y-m-d', $enddate);
    else $enddate = date('Y-m-d', $now);

    if ($_REQUEST['noexpiry'] == 'on')
    {
        $expirydate = '-1';
    }
    else
    {
        $expirydate = strtotime($_REQUEST['expiry']);
    }
    $amount = clean_float($_POST['amount']);
    if ($amount == '') $amount = 0;
    $unitrate = clean_float($_POST['unitrate']);

    $billtype = cleanvar($_REQUEST['billtype']);
    $foc = cleanvar($_REQUEST['foc']);
    if (empty($foc)) $foc = 'no';

    $billingmatrix = clean_dbstring($_REQUEST['billing_matrix']);

    $allcontacts = 'no';
    if ($contacts == 'amount') $numcontacts = clean_int($_REQUEST['numcontacts']);
    elseif ($contacts == 'all') $allcontacts = 'yes';

    $incident_pools = explode(',', "0,{$CONFIG['incident_pools']}");
    $incident_quantity = clean_int($incident_pools[$_POST['incident_poolid']]);

    $_SESSION['formdata']['new_contract'] = cleanvar($_POST, TRUE, FALSE, FALSE,
                                                     array("@"), array("'" => '"'));


    // Add maintenance to database
    $errors = 0;
    // check for blank site
    if ($site == 0)
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['site'] = sprintf($strFieldMustNotBeBlank, $strSite);
    }

    // check for blank product
    if ($product == 0)
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['product'] = sprintf($strFieldMustNotBeBlank, $strProduct);
    }

    // check for blank expiry day
    if (empty($_REQUEST['expiry']) AND $expirydate != -1)
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['expirydate'] = sprintf($strFieldMustNotBeBlank, $strExpiryDate);
    }
    elseif ($expirydate < $now AND $expirydate != -1)
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['expirydate2'] = "{$strExpiryDateCannotBeInThePast}\n";
    }

    // check for blank admin contact
    if ($admincontact == 0)
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['admincontact'] = sprintf($strFieldMustNotBeBlank, $strAdminContact);
    }

    if ($timed == 'yes' AND $amount == 0)
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['amount'] = sprintf($strFieldMustNotBeBlank, $strCreditAmount);
    }
    
    $billingObj = new $billtype();

    if ($timed == 'yes' AND empty($unitrate) AND $billingObj->uses_unit_rate())
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['unitrate'] = sprintf($strFieldMustNotBeBlank, $strUnitRate);
    }

    if ($timed == 'yes' AND $billingObj->uses_billing_matrix AND empty($billingmatrix))
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['billing_matrix'] = sprintf($strFieldMustNotBeBlank, $strNoBillingMatrixDefined);
    }
    plugin_do('contract_new_submitted');

    // add maintenance if no errors
    if ($errors == 0)
    {
        $addition_errors = 0;

        if (empty($reseller) OR $reseller == 0)
        {
            $reseller = "NULL";
        }
        else
        {
            $reseller = "'{$reseller}'";
        }

        if (empty($licence_type) OR $licence_type == 0)
        {
            $licence_type = "NULL";
        }
        else
        {
            $licence_type = "'{$licence_type}'";
        }

        if ($timed != 'yes')
        {
            $billingmatrix = '';
            $billtype = '';
        }
        
        $billingmatrix = convert_string_null_safe($billingmatrix);
        $billtype = convert_string_null_safe($billtype);
        
        // NOTE above is so we can insert null so browse_contacts etc can see the contract rather than inserting 0
        $sql  = "INSERT INTO `{$dbMaintenance}` (site, product, reseller, expirydate, licence_quantity, licence_type, notes, ";
        $sql .= "admincontact, incidentpoolid, incident_quantity, term, supportedcontacts, allcontactssupported, billingmatrix, billingtype) ";
        $sql .= "VALUES ('{$site}', '{$product}', {$reseller}, '{$expirydate}', '{$licence_quantity}', {$licence_type}, '{$notes}', ";
        $sql .= "'{$admincontact}', '{$incidentpoolid}', '{$incident_quantity}', '{$term}', '{$numcontacts}', '{$allcontacts}', {$billingmatrix}, {$billtype})";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        $maintid = mysql_insert_id();

        if (!$result)
        {
            $addition_errors = 1;
            $addition_errors_string .= user_alert($strAdditionFail, E_USER_WARNING);
        }

        $count = count($_REQUEST['incident_type']);

        for ($i = 0; $i < $count; $i++)
        {
            $type = clean_dbstring($_REQUEST['incident_type'][$i]);
            $sla = clean_dbstring($_REQUEST['servicelevel'][$i]);
            $sql = "INSERT INTO `{$dbMaintenanceServiceLevels}` VALUES ({$maintid}, {$type}, '{$sla}')";
            mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
            if (mysql_affected_rows() < 1) trigger_error("Insert failed", E_USER_ERROR);
        }

        // Add service
        $sql = "INSERT INTO `{$dbService}` (contractid, startdate, enddate, creditamount, rate, foc) ";
        $sql .= "VALUES ('{$maintid}', '{$startdate}', '{$enddate}', '{$amount}', '{$unitrate}', '{$foc}')";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
        if (mysql_affected_rows() < 1) trigger_error("Insert failed", E_USER_ERROR);

        $serviceid = mysql_insert_id();
        update_contract_balance($maintid, $strNewContract, $amount, $serviceid);

        if ($addition_errors == 1)
        {
            // show addition error message
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            echo $addition_errors_string;
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        else
        {
            plugin_do('contract_new_saved');
            // show success message
            $t = new TriggerEvent('TRIGGER_NEW_CONTRACT', array('contractid' => $maintid, 'userid' => $sit[2]));
            html_redirect("contract_details.php?id={$maintid}");
        }
        clear_form_data('new_contract');
    }
    else
    {
        // show error message if errors
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}
?>