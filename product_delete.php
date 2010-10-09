<?php
// delete_product.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// Removes a product


$permission = 65;  // Delete products
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$productid = intval(cleanvar($_REQUEST['id']));

if (!empty($productid))
{
    $errors = 0;
    // Check there are no contracts with this product
    $sql = "SELECT id FROM `{$dbMaintenance}` WHERE product={$productid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) >= 1) $errors++;

    // check there are no incidents with this product
    $sql = "SELECT id FROM `{$dbIncidents}` WHERE product={$productid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) >= 1) $errors++;

    // Check there is no software linked to this product
    $sql = "SELECT productid FROM `{$dbSoftwareProducts}` WHERE productid={$productid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) >= 1) $errors++;

    if ($errors == 0)
    {
        $sql = "DELETE FROM `{$dbProducts}` WHERE id = {$productid} LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        journal(CFG_LOGGING_NORMAL, 'Product Removed', "Product {$productid} was removed", CFG_JOURNAL_PRODUCTS, $productid);
        html_redirect("products.php");
    }
    else
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<p class='error'>{$strSorryProductCantBeDeleted}</p>";
        echo "<p align='center'><a href='products.php#{$productid}'>{$strReturnToProductList}</a></p>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
}
else
{
    trigger_error($strInvalidParameter, E_USER_ERROR);
}
?>
