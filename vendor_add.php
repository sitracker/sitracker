<?php
// vendor_add.php - Form for adding software vendors
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

$permission = 56; // Add Software

require ('core.php');
require (APPLICATION_LIBPATH.'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$title = $strAddVendor;

// External variables
$submit = $_REQUEST['submit'];

if (empty($submit))
{
    // Show form
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo show_form_errors('add_vendor');
    clear_form_errors('add_vendor');
    echo "<h2>{$strAddVendor}</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_action(\"{$strAreYouSureAdd}\")'>";
    echo "<table align='center' class='vertical'>";
    echo "<tr><th>{$strVendorName}</th><td><input maxlength='50' name='name' size='30' class='required'> <span class='required'>{$strRequired}</span></td></tr>\n";
    echo "</table>";
    echo "<p align='center'><input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "<p class='warning'>{$strAvoidDupes}</p>";
    echo "</form>\n";
    echo "<p align='center'><a href='products.php'>{$strReturnWithoutSaving}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    // External variables
    $name = cleanvar($_REQUEST['name']);
    $_SESSION['formdata'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);
    // Add new
    $errors = 0;

    // check for blank name
    if ($name == '')
    {
        $errors++;
        $_SESSION['formerrors']['name'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strVendorName}'"), E_USER_ERROR);
    }

    // add product if no errors
    if ($errors == 0)
    {
        $sql = "INSERT INTO `{$dbVendors}` (name) VALUES ('$name')";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (!$result)
        {
            echo "<p class='error'>{$strAdditionFail}</p>\n";
        }
        else
        {
            $id=mysql_insert_id();
            journal(CFG_LOGGING_DEBUG, 'Vendor Added', "Vendor {$id} was added", CFG_JOURNAL_DEBUG, $id);
            html_redirect("products.php");
        }
        clear_form_data('add_vendor');
        clear_form_errors('add_vendor');
    }
    else
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}
?>
