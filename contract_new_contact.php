<?php
// contract_new_contact.php - Associates a contact with a contract
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_SUPPORTED_PRODUCT_EDIT;  // Edit Supported Products
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External Variables
$maintid = clean_int($_REQUEST['maintid']);
$contactid = clean_int($_REQUEST['contactid']);
$context = clean_fixed_list($_REQUEST['context'], array('','contact'));
$action = clean_fixed_list($_REQUEST['action'], array('', 'showform', 'add'));

$title = ("$strContract - $strAddContact");

// Valid user, check permissions
if (empty($action) || $action == "showform")
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo show_form_errors('contract_new_contact');
    clear_form_errors('contract_new_contact');
    echo "<h2>{$strAssociateContactWithContract}</h2>";
    plugin_do('contract_new_contact');

    echo "<form action='{$_SERVER['PHP_SELF']}?action=new' method='post'>";
    echo "<input type='hidden' name='context' value='{$context}' />";
    echo "<table class='maintable vertical'>";

    if (empty($maintid))
    {
        echo "<tr><th>{$strContract} ".icon('contract', 16)."</th>";
        echo "<td width='400'>";
        echo maintenance_drop_down("maintid", 0, '', '', TRUE, TRUE)." <span class='required'>{$strRequired}</span>";
        echo "</td></tr>";
    }
    else
    {
        $sql = "SELECT s.name, p.name FROM `{$dbMaintenance}` m, `{$dbSites}` s, `{$dbProducts}` p WHERE m.site=s.id ";
        $sql .= "AND m.product=p.id AND m.id='{$maintid}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        list($sitename, $product)=mysql_fetch_row($result);

        echo "<tr><th>{$strContract} ".icon('contract', 16)."</th><td>{$maintid} - {$sitename}, {$product}</td></tr>";
        echo "<input name='maintid' type='hidden' value='{$maintid}' />";
    }

    if (empty($contactid))
    {
        echo "<tr><th>{$strContact} ".icon('contact', 16)."</th>";
        echo "<td>".contact_drop_down("contactid", 0, TRUE, TRUE)." <span class='required'>{$strRequired}</span></td></tr>";
    }
    else
    {
        echo "<tr><th>{$strContact} ".icon('contact', 16)."</th><td>$contactid - ".contact_realname($contactid).", ".site_name(contact_siteid($contactid));
        echo "<input name='contactid' type='hidden' value='{$contactid}' />";
        echo "</td></tr>";
    }
    plugin_do('contract_new_contact_form');
    echo "</table>";
    echo "<p class='formbuttons'>";
    echo "<input name='submit' type='reset' value='{$strReset}' /> ";
    echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "</form>";
    echo "<p class='return'><a href='contract_details.php?id={$maintid}'>{$strReturnWithoutSaving}</a></p>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else if ($action == "new")
{
    $errors = 0;

    if ($contactid == 0)
    {
        $errors++;
        $_SESSION['formerrors']['contract_new_contact']['contactid'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strContact}'"), E_USER_ERROR);
    }

    if ($maintid == 0)
    {
        $errors++;
        $_SESSION['formerrors']['contract_new_contact']['maintid'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strContract}'"), E_USER_ERROR);
    }
    plugin_do('contract_new_contact_submitted');

    $sql = "SELECT * FROM `{$dbSupportContacts}` WHERE maintenanceid = '{$maintid}' AND contactid = '{$contactid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $errors++;
        $_SESSION['formerrors']['contract_new_contact']['contactid'] = user_alert($strADuplicateAlreadyExists, E_USER_ERROR);
    }

    // add maintenance support contact if no errors
    if ($errors == 0)
    {
        $sql  = "INSERT INTO `{$dbSupportContacts}` (maintenanceid, contactid) VALUES ({$maintid}, {$contactid})";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (!$result)
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            echo user_alert("Addition of support contact failed", E_USER_WARNING);
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        else
        {
            plugin_do('contract_new_contact_saved');
            if ($context == 'contact') html_redirect("contact_details.php?id={$contactid}");
            else html_redirect("contract_details.php?id={$maintid}");
        }
    }
    else
    {
        html_redirect("contract_new_contact.php?maintid={$maintid}&context={$context}", FALSE);
    }
}
?>