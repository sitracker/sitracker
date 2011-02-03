<?php
// edit_vendor.php - Page to edit vendor details
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paulheaney[at]users.sourceforge.net>

$permission = 56; //add software
require ('core.php');
require (APPLICATION_LIBPATH.'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$title = $strEditVendor;

$action = cleanvar($_REQUEST['action']);

switch ($action)
{
    case 'save':
        $vendorname = clean_dbstring($_REQUEST['name']);
        $vendorid = clean_int($_REQUEST['vendorid']);

        // check for blank name
        if ($vendorname == '')
        {
            $errors = 1;
            $errors_string .= "<p class='error'>".sprintf($strFieldMustNotBeBlank, $strVendorName)."</p>\n";
        }

        if ($errors == 0)
        {
            $sql = "UPDATE `{$dbVendors}` SET name = '{$vendorname}' WHERE id = '{$vendorid}'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
            html_redirect("main.php");
        }
        else
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            echo $errors_string;
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        break;
    case 'edit':
        $vendorid = clean_int($_REQUEST['vendorid']);
        $vendorname = clean_dbstring($_REQUEST['vendorname']);
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>".icon('edituser', 32)." {$strEditVendor} {$vendorname}</h2>";
        echo "<form action='{$_SERVER['PHP_SELF']}' name'editvendor'>";
        echo "<table align='center'>";
        echo "<tr><th>{$strVendorName}:</th><td><input maxlength='50' name='name' size='30' value='{$vendorname}'/></td></tr>";
        echo "</table>";
        echo "<input type='hidden' name='action' value='save' />";
        echo "<input type='hidden' name='vendorid' value='{$vendorid}' />";
        echo "<p align='center'><input name='submit' type='submit' value='{$strEditVendor}' /></p>";
        echo "</form>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        break;
    default:
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>".icon('edituser', 32)." {$strEditVendor}</h2>";
        $sql = "SELECT * FROM `{$dbVendors}`";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        if (mysql_num_rows($result) > 0)
        {
            echo "<table class='vertical' align='center'>";
            $shade='shade1';
            while ($row = mysql_fetch_object($result))
            {
                echo "<tr class='{$shade}'><td><a href=\"{$_SERVER['PHP_SELF']}?action=edit&amp;vendorid={$row->id}&amp;vendorname=".urlencode($row->name)."\">{$row->name}</a></td></tr>\n";

                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }
            echo "</table>";
        }
        echo "<p align='center'><a href='vendor_add.php'>{$strAddVendor}</a></p>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        break;
}

?>