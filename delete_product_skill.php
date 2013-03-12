<?php
// delete_product_skill.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// Removes link between a product and skill

require ('core.php');
$permission = PERM_UNLINK_SKILLS_PRODUCTS;
require (APPLICATION_LIBPATH . 'functions.inc.php');
$title = "{$strDisassociateSkillWithProduct}";

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$productid = clean_int($_REQUEST['productid']);
$softwareid = clean_int($_REQUEST['softwareid']);

if (!empty($productid) AND !empty($softwareid))
{
    $sql = "DELETE FROM `{$dbSoftwareProducts}` WHERE productid='{$productid}' AND softwareid='{$softwareid}' LIMIT 1";
    mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    journal(CFG_LOGGING_NORMAL, 'Skill Unlinked', "Skill $softwareid was unlinked from Product $productid", CFG_JOURNAL_PRODUCTS, $productid);
    html_redirect("products.php");
}
else
{
    html_redirect("products.php", FALSE, "{$strRequiredDataMissing}");
}
?>