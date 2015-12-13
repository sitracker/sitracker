<?php
// product_skill_new.php - Associates skill with a product
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!  11Oct06

require ('core.php');
$permission = PERM_PRODUCT_ADD;  // Add Product
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$action = clean_fixed_list($_REQUEST['action'], array('showform','new'));
$productid = clean_int($_REQUEST['productid']);
$softwareid = clean_int($_REQUEST['softwareid']);
$context = cleanvar($_REQUEST['context']);
$return = cleanvar($_REQUEST['return']);

if (empty($action) OR $action == "showform")
{
    $title = $strNewLink;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo show_form_errors('product_skill_new');
    clear_form_errors('product_skill_new');
    echo "<h2>{$title}</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}?action=new' method='post'>\n";
    echo "<input type='hidden' name='context' value='{$context}' />\n";

    if (empty($productid))
    {
        if (!empty($softwareid))
        {
            $name = db_read_column('name', $dbSoftware, $softwareid);
            echo "<h3>".icon('skill',16)." ";
            echo "{$strSkill}: {$name}</h3>";
        }
        echo "<input name='softwareid' type='hidden' value='{$softwareid}' />\n";
        echo "<p align='center'>{$strProduct}: ".icon('product', 16)." ";
        echo product_drop_down("productid", 0, TRUE);
        echo " <span class='required'>{$strRequired}</p>";
    }
    else
    {
        $sql = "SELECT name FROM `{$dbProducts}` WHERE id='{$productid}' ";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

        list($product) = mysqli_fetch_row($result);
        echo "<h3>{$strProduct}: $product</h3>";
        echo "<input name='productid' type='hidden' value='{$productid}' />\n";
    }
    if (empty($softwareid))
    {
        echo "<p align='center'>{$strSkill}: ".icon('skill', 16)." ";
        echo skill_drop_down("softwareid", 0);
        echo "</p>\n";
    }
    echo "<p class='formbuttons'><input name='submit' type='submit' value='{$strSave}' />";
    echo "<input type='checkbox' name='return' value='true' ";
    if ($return == 'true') echo "checked='checked' ";
    echo "/> {$strReturnAfterSaving}</p>\n";
    echo "</form>";

    echo "<p class='return'><a href='products.php?productid={$productid}'>{$strReturnWithoutSaving}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == "new")
{
    $errors = 0;
    // check for blank
    if ($productid == 0)
    {
        $errors++;
        $_SESSION['formerrors']['product_skill_new']['productid'] = sprintf($strSelectionXmustNotBeEmpty, $strProduct);
    }
    // check for blank software id
    if ($softwareid == 0)
    {
        $errors++;
        $_SESSION['formerrors']['product_skill_new']['softwareid'] = sprintf($strSelectionXmustNotBeEmpty, $strSkill);
    }

    // add record if no errors
    if ($errors == 0)
    {
        // First have a look if we already have this link
        $sql = "SELECT productid FROM `{$dbSoftwareProducts}` WHERE productid='{$productid}' AND softwareid='{$softwareid}'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        if (mysqli_num_rows($result) >= 1)
        {
            html_redirect("product_skill_new.php?productid={$productid}&return={$return}", FALSE, $strAvoidDupes);
            // TODO $strAvoidDupes isn't the perfect string to use here, replace with something better when
            // we have a message about duplicates.
            exit;
        }

        $sql  = "INSERT INTO `{$dbSoftwareProducts}` (productid, softwareid) VALUES ({$productid}, {$softwareid})";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        // show error message if addition failed
        if (!$result)
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            trigger_error("Addition of skill/product failed: {$sql}", E_USER_WARNING);
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        // update db and show success message
        else
        {
            journal(CFG_LOGGING_NORMAL, 'Product Added', "Skill {$softwareid} was added to product {$productid}", CFG_JOURNAL_PRODUCTS, $productid);
            if ($return == 'true') html_redirect("product_skill_new.php?productid={$productid}&return=true");
            else html_redirect("products.php?productid={$productid}");
        }
    }
    else
    {
        html_redirect("product_skill_new.php?softwareid={$softwareid}&productid={$productid}", FALSE);
    }
}
?>