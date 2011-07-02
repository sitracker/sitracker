<?php
// product_new.php - Form to add products
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


$permission = PERM_PRODUCT_ADD; // Add Product

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNewProduct;

// External variables
$submit = $_REQUEST['submit'];

if (empty($submit))
{
    // Show add product form
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo show_form_errors('new_product');
    clear_form_errors('new_product');
    echo "<h2>".icon('product', 32)." ";
    echo "{$strNewProduct}</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_action(\"{$strAreYouSureAdd}\");'>";
    echo "<table class='maintable'>";
    echo "<tr><th>{$strVendor}</th><td>";
    if ($_SESSION['formdata']['new_product']['vendor'] != '')
    {
        echo vendor_drop_down('vendor', $_SESSION['formdata']['new_product']['vendor'], TRUE)." <span class='required'>{$strRequired}</span></td></tr>\n";
    }
    else
    {
        echo vendor_drop_down('vendor', 0, TRUE)." <span class='required'>{$strRequired}</span></td></tr>\n";
    }
    echo "<tr><th>{$strProduct}</th><td><input maxlength='50' name='name' size='40' class='required' ";
    if ($_SESSION['formdata']['new_product']['name'] != '')
    {
        echo "value=".$_SESSION['formdata']['new_product']['name'];
    }
    echo " /> <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr><th>{$strDescription}</th>";
    echo "<td>";
    echo "<textarea name='description' cols='40' rows='6'>";
    if ($_SESSION['formdata']['new_product']['description'] != '')
    {
        echo $_SESSION['formdata']['new_product']['description'];
    }
    echo "</textarea>";
    echo "</td></tr>";
    echo "</table>\n";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "<p class='warning'>{$strAvoidDupes}</p>";
    echo "</form>\n";
    echo "<p class='return'><a href='products.php'>{$strReturnWithoutSaving}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    clear_form_data('new_product');

}
else
{
    // External variables
    $name = cleanvar($_REQUEST['name']);
    $vendor = clean_int($_REQUEST['vendor']);
    $description = cleanvar($_REQUEST['description']);

    $_SESSION['formdata']['new_product'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);
    // Add New
    $errors = 0;

    if ($vendor == '' OR $vendor == "0")
    {
        $errors++;
        $_SESSION['formerrors']['new_product']['vendor'] = sprintf($strFieldMustNotBeBlank, $strVendor);
    }
    // check for blank name
    if ($name == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_product']['name'] = sprintf($strFieldMustNotBeBlank, $strProduct);
    }
    // add product if no errors
    if ($errors == 0)
    {
        $sql = "INSERT INTO `{$dbProducts}` (name, vendorid, description) VALUES ('{$name}', '{$vendor}', '{$description}')";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (!$result) echo "<p class='error'>".sprintf($strNewXfailed, $strProduct)."\n";
        else
        {
            $id = mysql_insert_id();
            journal(CFG_LOGGING_NORMAL, 'Product Added', "Product {$id} was added", CFG_JOURNAL_PRODUCTS, $id);

            html_redirect("products.php");
        }
        clear_form_errors('new_product');
        clear_form_data('new_product');
    }
    else
    {
        html_redirect("product_new.php", FALSE);
    }
}
?>