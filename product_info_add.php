<?php
// product_info_add.php - Form to add product information
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional!  16Dec05

// Product information is the info related to a product that is requested when adding an incident


$permission = 25; // Add Product Info

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$product = cleanvar($_REQUEST['product']);
$information = cleanvar($_POST['information']);
$moreinformation = cleanvar($_POST['moreinformation']);
$title = $strAddProductInformation;

// Show add product information form
if (empty($_REQUEST['submit']))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('info', 32)." {$strAddProductQuestion}</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_action(\"{$strAreYouSureAdd}\")'>";
    echo "<table class='vertical' align='center'>";
    echo "<tr><th>{$strProduct}</th><td>".product_drop_down("product", $product, TRUE)." <span class='required'>{$strRequired}</span></td></tr>";
    echo "<tr><th>{$strQuestion}</th><td><input name='information' size='30' class='required' /> <span class='required'>{$strRequired}</span></td></tr>";
    echo "<tr><th>{$strAdditionalInfo}</th><td><input name='moreinformation' size='30' /></td></tr>";
    echo "</table>";
    echo "<p align='center'><input name='submit' type='submit' value='{$strAdd}' /></p>";
    echo "</form>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    // FIXME these errors need tidying INL 9Jun08

    // Add product information
    $errors = 0;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    // check for blank product
    if ($product == 0)
    {
        $errors = 1;
        echo user_alert(sprintf($strFieldMustNotBeBlank, "'{$strProduct}'"), E_USER_ERROR);
    }
    // check for blank information
    if ($information == '')
    {
        $errors = 1;
        echo user_alert(sprintf($strFieldMustNotBeBlank, "'{$strQuestion}'"), E_USER_ERROR);
    }

    // add product information if no errors
    if ($errors == 0)
    {
        $sql = "INSERT INTO `{$dbProductInfo}` (productid, information, moreinformation) ";
        $sql .= "VALUES ('$product', '$information', '$moreinformation')";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

        if (!$result) echo "<p class='error'>".sprintf($strAddXfailed, $strProductInformation)."\n";
        else
        {
            journal(CFG_LOGGING_NORMAL, 'Product Info Added', "Info was added to Product {$product}", CFG_JOURNAL_PRODUCTS, $product);
            html_redirect("products.php?productid={$product}");
            exit;
        }
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>
