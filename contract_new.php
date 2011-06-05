<?php
// contract_new.php - Add a new maintenance contract
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional! 24May2009


$permission = 39; // Add Maintenance Contract

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNewContract;

// External variables
$action = $_REQUEST['action'];
$siteid = clean_int($_REQUEST['siteid']);

// Show add maintenance form
if ($action == "showform" OR $action == '')
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo show_form_errors('new_contract');
    clear_form_errors('new_contract');
    echo "<h2>".icon('contract', 32)." ";
    echo "{$strNewContract}</h2>";
    echo "<form id='new_contract' name='new_contract' action='{$_SERVER['PHP_SELF']}?action=new' method='post' onsubmit='return confirm_action(\"{$strAreYouSureAdd}\");'>";
    echo "<table align='center' class='vertical'>";
    echo "<thead>";
    echo "<tr><th>{$strSite}</th><td>";
    if ($_SESSION['formdata']['new_contract']['site'] != '')
    {
        echo site_drop_down("site", $_SESSION['formdata']['new_contract']['site'], TRUE);
    }
    else
    {
        echo site_drop_down("site", $siteid, TRUE);
    }
    echo " <span class='required'>{$strRequired}</span></td></tr>\n";
    echo "<tr><th>{$strContacts}</th><td>";
    echo "<input value='amount' type='radio' name='contacts' checked='checked' />";

    echo "{$strLimitTo} <input size='2' name='numcontacts' ";
    if ($_SESSION['formdata']['new_contract']['numcontacts'] != '')
    {
        echo "value='{$_SESSION['formdata']['new_contract']['numcontacts']}'";
    }
    else
    {
        echo "value='0'";
    }
    echo " /> {$strSupportedContacts} ({$str0MeansUnlimited})<br />";
    echo "<input type='radio' value='all' name='contacts' />";
    echo "{$strAllSiteContactsSupported}";
    echo "</td></tr>\n";
    echo "<tr><th>{$strProduct}</th><td>";
    if ($_SESSION['formdata']['new_contract']['product'] != '')
    {
        echo product_drop_down("product", $_SESSION['formdata']['new_contract']['product'], TRUE)." <span class='required'>{$strRequired}</span> </td></tr>\n";
    }
    else
    {
        echo product_drop_down("product", 0, TRUE)." <span class='required'>{$strRequired}</span></td></tr>\n";
    }

    echo "<tr><th>{$strServiceLevel}</th><td>";
    if ($_SESSION['formdata']['new_contract']['servicelevel'] != '')
    {
        $sltag = $_SESSION['formdata']['new_contract']['servicelevel'];
    }
    else
    {
        $sltag = $CONFIG['default_service_level'];
    }
    echo servicelevel_drop_down('servicelevel', $sltag, TRUE, "onchange=\"addcontract_sltimed(\$F('servicelevel'));\"")."</td></tr>\n";
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
    echo "<input type='checkbox' name='noexpiry' ";
    if ($_SESSION['formdata']['new_contract']['noexpiry'] == "on")
    {
        echo "checked='checked' ";
    }
    echo "onclick=\"this.form.expiry.value=''\" /> {$strUnlimited}";
    echo " <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr><th>{$strAdminContact}</th>";
    echo "<td>".contact_drop_down("admincontact", 0, TRUE, TRUE);
    echo " <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr><th>{$strNotes}</th><td><textarea cols='40' name='notes' rows='5'>{$_SESSION['formdata']['new_contract']['notes']}</textarea></td></tr>\n";
    echo "<tr><th></th><td><a href=\"javascript:void(0);\" onclick=\"$('hidden').toggle();\">{$strMore}</a></td></tr>\n";
    echo "</thead>";

    echo "<tbody id='hiddentimed'";
    if (!$timed) echo " style='display:none'";
    echo ">";

    echo "<tr><th>{$strBilling}</th>";
    echo "<td>";
    echo "<label>";
    echo "<input type='radio' name='billtype' value='billperunit' onchange=\"newservice_showbilling('new_contract');\" checked='checked' /> ";
    echo "{$strPerUnit}</label>";
    echo "<label>";
    echo "<input type='radio' name='billtype' value='billperincident' onchange=\"newservice_showbilling('new_contract');\" /> ";
    echo "{$strPerIncident}</label>";
    echo "</td></tr>\n";

    echo "<tr><th>{$strAmount}</th><td>{$CONFIG['currency_symbol']}";
    echo "<input maxlength='7' name='amount' size='5'  ";
    if ($_SESSION['formdata']['new_contract']['amount'] != '')
    {
        echo "value='{$_SESSION['formdata']['new_contract']['amount']}'  ";
    }
    else
    {
        echo "value='0' ";
    }
    echo "/></td></tr>\n";
    echo "<tr id='unitratesection'><th>{$strUnitRate}</th>";
    echo "<td>{$CONFIG['currency_symbol']} ";
    echo "<input class='required' type='text' name='unitrate' size='5' ";
    if ($_SESSION['formdata']['new_contract']['unitrate'] != '')
    {
        echo "value='{$_SESSION['formdata']['new_contract']['unitrate']}' ";
    }
    echo "/>";
    echo " <span class='required'>{$strRequired}</span></td></tr>\n";
    echo "<tr id='incidentratesection' style='display:none'><th>{$strIncidentRate}</th>";
    echo "<td>{$CONFIG['currency_symbol']} ";
    echo "<input class='required' type='text' name='incidentrate' size='5' ";
    if ($_SESSION['formdata']['new_contract']['incidentrate'] != '')
    {
        echo "value='{$_SESSION['formdata']['new_contract']['incidentrate']}' ";
    }
    echo "/>";
    echo " <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr><th>{$strBillingMatrix}</th>";
    echo "<td>".billing_matrix_selector('billing_matrix', $_SESSION['formdata']['new_contract']['billing_matrix'] )."</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<th>{$strFreeOfCharge}</th>";
    echo "<td><input type='checkbox' id='foc' name='foc' value='yes' ";
    if ($_SESSION['formdata']['new_contract']['foc'] == 'yes')
    {
        echo "checked='checked''  ";
    }
    echo "/> {$strAboveMustBeCompletedToAllowDeductions}</td></tr>\n";
    echo "</tbody>\n";

    echo "<tbody id='hidden' style='display:none'>";

    echo "<tr><th>{$strReseller}</th><td>";
    reseller_drop_down("reseller", 1);
    echo "</td></tr>\n";

    echo "<tr><th>{$strLicenseQuantity}</th><td><input value='0' maxlength='7' name='licence_quantity' size='5' />";
    echo " ({$str0MeansUnlimited})</td></tr>\n";

    echo "<tr><th>{$strLicenseType}</th><td>";
    licence_type_drop_down("licence_type", LICENCE_SITE);
    echo "</td></tr>\n";

    echo "<tr><th>{$strIncidentPool}</th>";
    $incident_pools = explode(',', "{$strUnlimited},{$CONFIG['incident_pools']}");
    echo "<td>".array_drop_down($incident_pools,'incident_poolid',$maint['incident_quantity'])."</td></tr>\n";

    echo "<tr><th>{$strProductOnly}</th><td><input name='productonly' type='checkbox' value='yes' /></td></tr></tbody>\n";

    echo "</table>\n";
    if ($timed) $timed = 'yes';
    else $timed = 'no';
    echo "<input type='hidden' id='timed' name='timed' value='{$timed}' />";
    echo "<p align='center'><input name='submit' type='submit' value=\"{$strSave}\" /></p>";
    echo "</form>";
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
    $servicelevel = clean_dbstring($_REQUEST['servicelevel']);
    $incidentpoolid = clean_int($_REQUEST['incidentpoolid']);
    $productonly = clean_fixed_list($_REQUEST['productonly'], array('no','yes'));
    $term = clean_fixed_list($_REQUEST['term'], array('no','yes'));
    $contacts = cleanvar($_REQUEST['contacts']);
    $timed = cleanvar($_REQUEST['timed']);
    $startdate = strtotime($_REQUEST['startdate']);
    if ($startdate > 0) $startdate = date('Y-m-d',$startdate);
    else $startdate = date('Y-m-d',$now);
    $enddate = strtotime($_REQUEST['expiry']);
    if ($enddate > 0) $enddate = date('Y-m-d',$enddate);
    else $enddate = date('Y-m-d',$now);

    if ($_REQUEST['noexpiry'] == 'on')
    {
        $expirydate = '-1';
    }
    else
    {
        $expirydate = strtotime($_REQUEST['expiry']);
    }
    $amount =  clean_float($_POST['amount']);
    if ($amount == '') $amount = 0;
    $unitrate =  clean_float($_POST['unitrate']);
    if ($unitrate == '') $unitrate = 0;
    $incidentrate =  clean_float($_POST['incidentrate']);
    if ($incidentrate == '') $incidentrate = 0;

    $billtype = cleanvar($_REQUEST['billtype']);
    $foc = cleanvar($_REQUEST['foc']);
    if (empty($foc)) $foc = 'no';

    $billingmatrix = clean_dbstring($_REQUEST['billing_matrix']);

    if ($billtype == 'billperunit') $incidentrate = 0;
    elseif ($billtype == 'billperincident') $unitrate = 0;

    $allcontacts = 'no';
    if ($contacts == 'amount') $numcontacts = clean_int($_REQUEST['numcontacts']);
    elseif ($contacts == 'all') $allcontacts = 'yes';

    $incident_pools = explode(',', "0,{$CONFIG['incident_pools']}");
    $incident_quantity = $incident_pools[$_POST['incident_poolid']];

    $_SESSION['formdata']['new_contract'] = cleanvar($_POST, TRUE, FALSE, FALSE,
                                                     array("@"), array("'" => '"'));

    // Add maintenance to database
    $errors = 0;
    // check for blank site
    if ($site == 0)
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['site'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strSite}'"), E_USER_ERROR);
    }
    // check for blank product
    if ($product == 0)
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['product'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strProduct}'"), E_USER_ERROR);
    }
    // check for blank admin contact
    if ($admincontact == 0)
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['admincontact'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strAdminContact}'"), E_USER_ERROR);
    }
    // check for blank expiry day
    if (!isset($expirydate))
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['expirydate'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strExpiryDate}'"), E_USER_ERROR);
    }
    elseif ($expirydate < $now AND $expirydate != -1)
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['expirydate2'] = "{$strExpiryDateCannotBeInThePast}\n";
    }
    // check timed sla data and store it

    if ($timed == 'yes' AND ($billtype == 'billperunit' AND ($unitrate == 0 OR trim($unitrate) == '')))
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['unitrate'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strUnitRate}'"), E_USER_ERROR);
    }

    if ($timed == 'yes' AND ($billtype == 'billperincident' AND ($incidentrate == 0 OR trim($incidentrate) == '')))
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['incidentrate'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strIncidentRate}'"), E_USER_ERROR);
    }

    if ($timed == 'yes' AND empty($billingmatrix))
    {
        $errors++;
        $_SESSION['formerrors']['new_contract']['incidentrate'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strNoBillingMatrixDefined}'"), E_USER_ERROR);
    }

    // add maintenance if no errors
    if ($errors == 0)
    {
        $addition_errors = 0;

        if (empty($productonly))
        {
            $productonly = 'no';
        }

        if ($productonly == 'yes')
        {
            $term = 'yes';
        }
        else
        {
            $term = 'no';
        }

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

        // NOTE above is so we can insert null so browse_contacts etc can see the contract rather than inserting 0
        $sql  = "INSERT INTO `{$dbMaintenance}` (site, product, reseller, expirydate, licence_quantity, licence_type, notes, ";
        $sql .= "admincontact, servicelevel, incidentpoolid, incident_quantity, productonly, term, supportedcontacts, allcontactssupported) ";
        $sql .= "VALUES ('{$site}', '{$product}', {$reseller}, '{$expirydate}', '{$licence_quantity}', {$licence_type}, '{$notes}', ";
        $sql .= "'{$admincontact}', '{$servicelevel}', '{$incidentpoolid}', '{$incident_quantity}', '{$productonly}', '{$term}', '{$numcontacts}', '{$allcontacts}')";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        $maintid = mysql_insert_id();

        if (!$result)
        {
            $addition_errors = 1;
            $addition_errors_string .= user_alert($strAdditionFail, E_USER_WARNING);
        }

        // Add service
        $sql = "INSERT INTO `{$dbService}` (contractid, startdate, enddate, creditamount, unitrate, incidentrate, billingmatrix, foc) ";
        $sql .= "VALUES ('{$maintid}', '{$startdate}', '{$enddate}', '{$amount}', '{$unitrate}', '{$incidentrate}', '{$billingmatrix}', '{$foc}')";
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
            // show success message
            $t = new TriggerEvent('TRIGGER_NEW_CONTRACT', array('contractid' => $maintid, 'userid' => $sit[2]));
            html_redirect("contract_details.php?id=$maintid");
        }
        clear_form_data('new_contract');
    }
    else
    {
        // show error message if errors
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        html_redirect("contract_new.php", FALSE);
    }
}
?>