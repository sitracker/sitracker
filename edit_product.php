<?php
// edit_product.php
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
$permission = PERM_PRODUCT_EDIT; // Edit products
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$id = clean_int($_REQUEST['id']);
$action = clean_fixed_list($_POST['action'], array('form', 'save'));

if ($action == 'save')
{
    // External variables
    $vendor = clean_int($_POST['vendor']);
    $name = clean_dbstring($_POST['name']);
    $description = clean_dbstring($_POST['description']);
    $productid = clean_int($_POST['productid']);
    $tags = clean_dbstring($_POST['tags']);

    if ($vendor == '' OR $vendor == "0")
    {
        $errors++;
        $_SESSION['formerrors']['edit_product']['vendor'] = sprintf($strFieldMustNotBeBlank, $strVendor);
    }
    // check for blank name
    if ($name == '')
    {
        $errors++;
        $_SESSION['formerrors']['edit_product']['name'] = sprintf($strFieldMustNotBeBlank, $strName);
    }
    if ($errors > 0)
    {
        html_redirect("edit_product.php?id={$productid}", FALSE);
    }
    else
    {
        replace_tags(TAG_PRODUCT, $productid, $tags);

        // update database
        $sql = "UPDATE `{$dbProducts}` SET vendorid='{$vendor}', name='{$name}', description='{$description}' WHERE id='{$productid}' LIMIT 1 ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (!$result)
        {
            trigger_error("Update of product failed: {$sql}", E_USER_WARNING);
        }
        else
        {
            journal(CFG_LOGGING_NORMAL, 'Product Edited', "Product {$productid} was edited", CFG_JOURNAL_PRODUCTS, $productid);
            html_redirect("products.php");
        }
    }
}
else
{
    $title = $strEditProduct;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo show_form_errors('edit_product');
    clear_form_errors('edit_product');
    echo "<h2>".icon('product', 32)." ";
    echo "$title</h2>\n";

    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' >";
    echo "<table class='maintable vertical'>";

    $sql = "SELECT * FROM `{$dbProducts}` WHERE id={$id} ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error: ".mysql_error(), E_USER_WARNING);

    $row = mysql_fetch_object($result);

    echo "<tr><th>{$strVendor}:</th>";
    echo "<td>";
    echo vendor_drop_down('vendor', $row->vendorid, TRUE);
    echo " <span class='required'>{$strRequired}</span></td></tr>";
    echo "<tr><th>{$strName}:</th>";
    echo "<td>";
    echo "<input maxlength='50' name='name' size='40' value='{$row->name}'  class='required' />";
    echo " <span class='required'>{$strRequired}</span></td></tr>";
    echo "<tr><th>{$strDescription}:</th>";
    echo "<td>";
    echo "<textarea name='description' cols='40' rows='6'>{$row->description}</textarea>";
    echo "</td></tr>";
    echo "<tr><th>{$strTags}:</th>";
    echo "<td><textarea rows='2' cols='30' name='tags'>".list_tags($id, TAG_PRODUCT, false)."</textarea></td></tr>\n";
    echo "</table>";
    echo "<input type='hidden' name='productid' value='{$id}' />";
    echo "<input type='hidden' name='action' value='save' />";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input type='submit' value='{$strSave}' /></p>";
    echo "</form>";

    echo "<p class='return'><a href='products.php'>{$strReturnWithoutSaving}</a></p>";
    mysql_free_result($result);

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>