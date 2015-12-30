<?php
// contract_delete_contact.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


// Removes an Association between a contact and a maintenance contract

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!   31Oct05

require ('core.php');
$permission = PERM_SUPPORTED_PRODUCT_EDIT;  // Edit Supported Products
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$action = clean_fixed_list($_REQUEST['action'], array('','showform','delete'));
$context = clean_fixed_list($_REQUEST['context'], array('', 'maintenance'));
$maintid = clean_int($_REQUEST['maintid']);
$contactid = clean_int($_REQUEST['contactid']);
$title = ("$strContract - $strRemoveASupportedContact");

if (empty($action) OR $action == "showform")
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>{$strRemoveLinkContractAndSupportContact}</h2>";
    echo show_form_errors('contract_delete_contact');
    clear_form_errors('contract_delete_contact');

    plugin_do('contract_delete_contact');
    echo "<p align='center'>{$strRemoveLinkContractAndSupportContactText}</p>";
    echo "<form action='{$_SERVER['PHP_SELF']}?action=delete' method='post' onsubmit='return confirm_action(\"{$strAreYouSureDeleteMaintenceContract}\", true)' name='contract_delete_contact' >";
    echo "<input type='hidden' name='context' value='{$context}' />";
    echo "<table class='maintable vertical'>";

    if (empty($maintid))
    {
        echo "<tr><th>{$strContract} ".icon('contract', 16)."</th>";
        echo "<td>";
        echo maintenance_drop_down("maintid", 0, NULL, true);
        echo "</td></tr>";
    }
    else
    {
        echo "<tr><th>{$strContract} ".icon('contract', 16)."</th>";
        echo "<td>".sprintf($strXProductForYSite, contract_product($maintid), contract_site($maintid));
        echo "<input name='maintid' type='hidden' value='{$maintid}' /></td></tr>";
    }

    if (empty($contactid))
    {
        echo "<tr><th>{$strSupportedContacts} ".icon('contact', 16)."</th><td width='400'>";
        echo contact_drop_down("contactid", 0)."</td></tr>";
    }
    else
    {
        echo "<tr><th>{$strContact} ".icon('contact', 16)."</th><td>{$contactid} - ".contact_realname($contactid);
        echo "<input name='contactid' type='hidden' value='{$contactid}' /></td></tr>";
    }
    plugin_do('contract_delete_contact_form');
    echo "</table>";
    echo "<p class='formbuttons'><input name='submit' type='submit' value='{$strContinue}' /></p>";
    echo "</form>";
    echo "<p class='return'><a href='contract_details.php?id={$maintid}'>{$strReturnWithoutSaving}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == "delete")
{
    // Delete the chosen support contact
    $errors = 0;
    // check for blank contact
    if ($contactid == 0)
    {
        $_SESSION['formerrors']['contract_delete_contact']['contactid'] = $strYouMustSelectAsupportContact;
        $errors++;
    }
    // check for blank maintenance id
    if ($maintid == 0)
    {
        $_SESSION['formerrors']['contract_delete_contact']['contractid'] = $strYouMustSelectAmaintenanceContract;
        $errors++;
    }
    plugin_do('contract_delete_contact_submitted');
    
    // delete maintenance support contact if no errors
    if ($errors != 0)
    {
        html_redirect("{$_SERVER['PHP_SELF']}?action=showform&context={$context}&maintid={$maintid}&contactid={$contactid}", FALSE);
    }
    else
    {
        $sql  = "DELETE FROM `{$dbSupportContacts}` WHERE maintenanceid='{$maintid}' AND contactid='{$contactid}'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        // show error message if deletion failed
        if (!$result)
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            trigger_error($strDeletionOfSupportContractFailed, E_USER_WARNING);
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        else
        {
            journal(CFG_LOGGING_NORMAL, 'Supported Contact Removed', "Contact {$contactid} removed from maintenance contract $maintid", CFG_JOURNAL_MAINTENANCED, $maintid);

            plugin_do('contract_delete_contact_saved');
            if ($context == 'maintenance') html_redirect("contract_details.php?id={$maintid}");
            else html_redirect("contact_details.php?id={$contactid}");
        }
    }
}
?>