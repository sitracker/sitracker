<?php
// product_add.php - Form to add products
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


$permission = 24; // Add Product

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strAddProduct;

// External variables
$submit = $_REQUEST['submit'];

if (empty($submit))
{
    // Show add product form
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo show_form_errors('add_product');
    clear_form_errors('add_product');
    echo "<h2>".icon('product', 32)." ";
    echo "{$strNewProduct}</h2>";
    echo "<h5>".sprintf($strMandatoryMarked, "<sup class='red'>*</sup>")."</h5>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_action(\"{$strAreYouSureAdd}\");'>";
    echo "<table align='center'>";
    echo "<tr><th>{$strVendor}<sup class='red'>*</sup></th><td>";
    if ($_SESSION['formdata']['add_product']['vendor'] != '')
    {
        echo vendor_drop_down('vendor', $_SESSION['formdata']['add_product']['vendor'])."</td></tr>\n";
    }
    else
    {
        echo vendor_drop_down('vendor', 0)."</td></tr>\n";
    }
    echo "<tr><th>{$strProduct}<sup class='red'>*</sup></th><td><input maxlength='50' name='name' size='40' ";
    if ($_SESSION['formdata']['add_product']['name'] != '')
    {
        echo "value=".$_SESSION['formdata']['add_product']['name'];
    }
    echo " /></td></tr>\n";

    echo "<tr><th>{$strDescription}</th>";
    echo "<td>";
    echo "<textarea name='description' cols='40' rows='6'>";
    if ($_SESSION['formdata']['add_product']['description'] != '')
    {
        echo $_SESSION['formdata']['add_product']['description'];
    }
    echo "</textarea>";
    echo "</td></tr>";
    echo "</table>\n";
    echo "<p><input name='submit' type='submit' value='{$strAddProduct}' /></p>";
    echo "<p class='warning'>{$strAvoidDupes}</p>";
    echo "</form>\n";
    echo "<p align='center'><a href='products.php'>{$strReturnWithoutSaving}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    clear_form_data('add_product');

}
else
{
    // External variables
    $name = cleanvar($_REQUEST['name']);
    $vendor = cleanvar($_REQUEST['vendor']);
    $description = cleanvar($_REQUEST['description']);

    $_SESSION['formdata']['add_product'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);
    // Add New
    $errors = 0;

    // check for blank name
    if ($name == '')
    {
        $errors++;
        $_SESSION['formerrors']['add_product']['name'] = sprintf($strFieldMustNotBeBlank, $strProduct);
    }
    if ($vendor == '' OR $vendor == "0")
    {
        $errors++;
        $_SESSION['formerrors']['add_product']['vendor'] = sprintf($strFieldMustNotBeBlank, $strVendor);
    }
    // add product if no errors
    if ($errors == 0)
    {
        $sql = "INSERT INTO `{$dbProducts}` (name, vendorid, description) VALUES ('$name', '$vendor', '$description')";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (!$result) echo "<p class='error'>".sprintf($strAddXfailed, $strProduct)."\n";
        else
        {
            $id = mysql_insert_id();
            journal(CFG_LOGGING_NORMAL, 'Product Added', "Product $id was added", CFG_JOURNAL_PRODUCTS, $id);

            html_redirect("products.php");
        }
        clear_form_errors('add_product');
        clear_form_data('add_product');
    }
    else
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        html_redirect("product_add.php", FALSE);
    }
}
?>
