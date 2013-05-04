<?php
// edit_contract.php - Form for editing maintenance contracts
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

require ('core.php');
$permission = PERM_CONTRACT_EDIT; // Edit Contracts
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strEditContract;

// External variables
$action = clean_fixed_list($_REQUEST['action'], array('','showform','edit','update'));
$maintid = clean_int($_REQUEST['maintid']);
$changeproduct = clean_fixed_list($_REQUEST['changeproduct'], array('','no','yes'));

if (empty($action) OR $action == "showform")
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('contract', 32)." ";
    echo "{$strContract}:</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}?action=edit' method='post'>";
    echo "<table class='maintable vertical'>";
    echo "<tr><th>{$strContract}:</th><td>";
    echo maintenance_drop_down("maintid", 0, NULL, true);
    echo "</td></tr>\n";
    echo "</table>\n";
    echo "<p class='formbuttons'><input name='submit' type='submit' value=\"{$strContinue}\" /></p>\n";
    echo "</form>\n";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}


if ($action == "edit")
{
    // Show edit maintenance form
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    if ($maintid == 0) echo user_alert(sprintf($strFieldMustNotBeBlank, "'{$strContract}'"), E_USER_ERROR);
    else
    {
        $sql = "SELECT * FROM `{$dbMaintenance}` WHERE id='{$maintid}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Error", E_USER_WARNING);
        $maint = mysql_fetch_object($result);

        echo "<h2>".icon('contract', 32)." ";
        echo "{$strEditContract}: {$maintid}</h2>";
        
        echo show_form_errors('maintform');
        clear_form_errors('maintform');
        
        echo "<form id='maintform' name='maintform' action='{$_SERVER['PHP_SELF']}?action=update' method='post' onsubmit='return confirm_action(\"{$strAreYouSureMakeTheseChanges}\")'>\n";
        echo "<table class='maintable vertical'>\n";
        echo "<tbody>\n";
        echo "<tr><th>{$strSite}:</th><td>";
        echo site_name($maint->site). "</td></tr>";  // This is mandatory though we don't mark it as such as its not editable
        echo "<tr><th>{$strContacts}:</th><td>";
        echo "<input value='amount' type='radio' name='contacts' checked='checked' />";
        echo "{$strLimitTo} <input size='2' value='{$maint->supportedcontacts}' name='amount' /> {$strSupportedContacts} ({$str0MeansUnlimited})<br />";
        echo "<input type='radio' value='all' name='contacts'";
        if ($maint->allcontactssupported == 'yes')
        echo "checked='checked'";
        echo " />{$strAllSiteContactsSupported}</td></tr>";
        echo "<tr><th>{$strProduct}: </th><td>";
        $productname = product_name($maint->product);
        if (user_permission($sit[2], PERM_ADMIN))
        {
            if ($changeproduct == 'yes')
            {
                echo product_drop_down("product", $maint->product, TRUE);
            }
            else
            {
                echo "{$productname} (<a href='{$_SERVER['PHP_SELF']}?action=edit&amp;maintid={$maintid}&amp;changeproduct=yes'>{$strChange}</a>)";
            }
        }
        else echo "{$productname}";
        echo " <span class='required'>{$strRequired}</span></td></tr>\n";

        echo "<tr><th>{$strExpiryDate}: </th>";
        echo "<td><input class='required' name='expirydate' size='10' value='";
        if ($maint->expirydate > 0) echo ldate('Y-m-d', $maint->expirydate);
        echo "' /> ".date_picker('maintform.expirydate');
        echo "<label>";
        if ($maint->expirydate == '-1')
        {
            echo "<input type='checkbox' checked='checked' name='noexpiry' /> {$strUnlimited}";
        }
        else
        {
            echo "<input type='checkbox' name='noexpiry' /> {$strUnlimited}";
        }
        echo "</label>";
        echo " <span class='required'>{$strRequired}</span></td></tr>\n";
        echo "<tr><th>{$strServiceLevel}:</th><td>";
        echo servicelevel_drop_down('servicelevel', $maint->servicelevel, TRUE, '', FALSE);
        echo "</td></tr>\n";
        echo "<tr><th>{$strAdminContact}: </th><td>";
        echo contact_drop_down("admincontact", $maint->admincontact, TRUE, TRUE);
        echo " <span class='required'>{$strRequired}</span></td></tr>\n";
        echo "<tr><th>{$strNotes}:</th><td><textarea cols='40' name='notes' rows='5'>";
        echo $maint->notes;
        echo "</textarea></td></tr>\n";
        plugin_do('contract_edit_form');
        echo "<tr><th>{$strTerminated}:</th><td><input name='terminated' id='terminated' type='checkbox' value='yes'";
        if ($maint->term == "yes") echo " checked";
        echo " /></td></tr>\n";


        echo "<tr><th></th><td><a href=\"javascript:void(0);\" onclick=\"$('hidden').toggle();\">{$strMore}</a></td></tr>";
        echo "</tbody>\n";
        echo "<tbody id='hidden' style='display:none'>";

        echo "<tr><th>{$strReseller}:</th><td>";
        echo reseller_drop_down("reseller", $maint->reseller);
        echo "</td></tr>\n";

        echo "<tr><th>{$strLicenseQuantity}:</th>";
        echo "<td><input maxlength='7' name='licence_quantity' size='5' value='{$maint->licence_quantity}' /></td></tr>\n";
        echo "<tr><th>{$strLicenseType}:</th><td>";
        echo licence_type_drop_down("licence_type", $maint->licence_type);
        echo "</td></tr>\n";

        echo "<tr><th>{$strIncidentPool}:</th>";
        $incident_pools = explode(',', "Unlimited,{$CONFIG['incident_pools']}");
        echo "<td>".array_drop_down($incident_pools, 'incident_poolid', $maint->incident_quantity, '', TRUE, FALSE)."</td></tr>";

        echo "</tbody>";
        plugin_do('contract_edit_form_more');
        echo "</table>\n";
        echo "<input name='maintid' type='hidden' value='{$maintid}' />";
        echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
        echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
        echo "</form>\n";

        echo "<p class='return'><a href='contract_details.php?id={$maintid}'>{$strReturnWithoutSaving}</a></p>";
        mysql_free_result($result);
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else if ($action == "update")
{
    // External variables
    $incident_pools = explode(',', "0,{$CONFIG['incident_pools']}");
    $incident_quantity = clean_int($incident_pools[$_POST['incident_poolid']]);
    $reseller = clean_int($_POST['reseller']);
    $licence_quantity = clean_int($_POST['licence_quantity']);
    $licence_type = clean_int($_POST['licence_type']);
    $notes = cleanvar($_POST['notes']);
    $admincontact = clean_int($_POST['admincontact']);
    $terminated = cleanvar($_POST['terminated']);
    $servicelevel = clean_dbstring($_POST['servicelevel']);
    $incidentpoolid = clean_int($_POST['incidentpoolid']);
    $product = clean_int($_POST['product']);
    $contacts = cleanvar($_REQUEST['contacts']);
    if ($_REQUEST['noexpiry'] == 'on') $expirydate = '-1';
    else $expirydate = strtotime($_REQUEST['expirydate']);

    $allcontacts = 'No';
    if ($contacts == 'amount') $amount = clean_float($_REQUEST['amount']);
    elseif ($contacts == 'all') $allcontacts = 'Yes';
    
    $errors = 0;

    if ($reseller == 0)
    {       
        $_SESSION['formerrors']['maintform']['reseller'] = sprintf($strFieldMustNotBeBlank, $strReseller);
        $errors++;
    }

    if ($admincontact == 0)
    {
        $_SESSION['formerrors']['maintform']['admincontact'] = sprintf($strFieldMustNotBeBlank, $strAdminContact);
        $errors++;
    }

    if ($_REQUEST['expirydate'] == 0)
    {
        $_SESSION['formerrors']['maintform']['expirydate'] = sprintf($strFieldMustNotBeBlank, $strExpiryDate);
        $errors++;
    }
    
    plugin_do('contract_edit_submitted');

    if ($errors == 0)
    {
        $reseller = convert_string_null_safe($reseller);
        $licence_type = convert_string_null_safe($licence_type);

        // NOTE above is so we can insert null so browse_contacts etc can see the contract rather than inserting 0
        $sql  = "UPDATE `{$dbMaintenance}` SET reseller={$reseller}, expirydate='{$expirydate}', licence_quantity='{$licence_quantity}', ";
        $sql .= "licence_type={$licence_type}, notes='{$notes}', admincontact={$admincontact}, term='{$terminated}', servicelevel='{$servicelevel}', ";
        $sql .= "incident_quantity='{$incident_quantity}', ";
        $sql .= "incidentpoolid='{$incidentpoolid}', ";
        $sql .= "supportedcontacts='{$amount}', allcontactssupported='{$allcontacts}'";
        if (!empty($product) AND user_permission($sit[2], PERM_ADMIN)) $sql .= ", product='{$product}'";
        $sql .= " WHERE id='{$maintid}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

        // show error message if addition failed
        if (!$result)
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            echo user_alert("Update failed", E_USER_WARNING);
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        else
        {
            plugin_do('contract_edit_saved');
            // show success message
            journal(CFG_LOGGING_NORMAL, 'Contract Edited', "contract {$maintid} modified", CFG_JOURNAL_MAINTENANCE, $maintid);
            html_redirect("contract_details.php?id={$maintid}");
        }
    }
    else
    {
        html_redirect("{$_SERVER['PHP_SELF']}?action=edit&maintid={$maintid}", FALSE);
    }
}
?>