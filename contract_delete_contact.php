<?php
// delete_maintenance_support_contact.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


// Removes an Association between a contact and a maintenance contract

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// FIXME i18n

// This Page Is Valid XHTML 1.0 Transitional!   31Oct05


$permission=32;  // Edit Supported Products
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$action = $_REQUEST['action'];
$context = cleanvar($_REQUEST['context']);
$maintid = clean_int($_REQUEST['maintid']);
$contactid = clean_int($_REQUEST['contactid']);
$title = ("$strContract - $strRemoveASupportedContact");

if (empty($action) OR $action == "showform")
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>{$strRemoveLinkContractAndSupportContact}</h2>";
    echo "<p align='center'>{$strRemoveLinkContractAndSupportContactText}</p>";
    echo "<form action='{$_SERVER['PHP_SELF']}?action=delete' method='post' onsubmit='return confirm_action(\"{$strAreYouSureDeleteMaintenceContract}\", true)'>";
    echo "<input type='hidden' name='context' value='{$context}' />";
    echo "<table align='center' class='vertical'>";

    if (empty($maintid))
    {
        echo "<tr><th>{$strContract} ".icon('contract', 16)."</th>";
        echo "<td>";
        maintenance_drop_down("maintid", 0);
        echo "</td></tr>";
    }
    else
    {
        echo "<tr><th>{$strContract} ".icon('contract', 16)."</th>";
        echo "<td>$maintid - ".contract_product($maintid)." for ".contract_site($maintid);
        echo "<input name=\"maintid\" type=\"hidden\" value=\"$maintid\" /></td></tr>";
    }

    if (empty($contactid))
    {
        echo "<tr><th>{$strSupport} {$strContact} ".icon('contact', 16)."</th><td width='400'>";
        echo contact_drop_down("contactid", 0)."</td></tr>";
    }
    else
    {
        echo "<tr><th>{$strContact} ".icon('contact', 16)."</th><td>{$contactid} - ".contact_realname($contactid);
        echo "<input name='contactid' type='hidden' value='$contactid' /></td></tr>";
    }

    echo "</table>";
    echo "<p align='center'><input name='submit' type='submit' value='{$strContinue}' /></p>";
    echo "</form>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == "delete")
{
    // Delete the chosen support contact
    $errors = 0;
    // check for blank contact
    if ($contactid == 0)
    {
        $errors = 1;
        $errors_string .= user_alert("{$strYouMustSelectAsupportContact}", E_USER_ERROR);
    }
    // check for blank maintenance id
    if ($maintid == 0)
    {
        $errors = 1;
        $errors_string .= user_alert("{$strYouMustSelectAmaintenanceContract}", E_USER_ERROR);
    }
    // delete maintenance support contact if no errors
    if ($errors == 0)
    {
        $sql  = "DELETE FROM `{$dbSupportContacts}` WHERE maintenanceid='$maintid' AND contactid='$contactid'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        // show error message if deletion failed
        if (!$result)
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            trigger_error("Deletion of maintenance support conact failed: {$sql}", E_USER_WARNING);
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        // update db and show success message
        else
        {
            journal(CFG_LOGGING_NORMAL, 'Supported Contact Removed', "Contact $contactid removed from maintenance contract $maintid", CFG_JOURNAL_MAINTENANCED, $maintid);

            if ($context == 'maintenance') html_redirect("contract_details.php?id={$maintid}");
            else html_redirect("contact_details.php?id={$contactid}");
        }
    }
    else
    {
        // show error message if errors
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo $errors_string;
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
}
?>