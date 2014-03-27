<?php
// billing_matrix_new_points_based.php - Page to add a new points based billing matric
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission =  PERM_BILLING_DURATION_EDIT;  // TODO we need a permission to administer billing matrixes;  // TODO we need a permission to administer billing matrixes
require_once (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require_once (APPLICATION_LIBPATH . 'auth.inc.php');

$title = "{$strPointsBased} {$strNewBillingMatrix}";

$action = $_REQUEST['action'];

if (empty($action) OR $action == "showform")
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('billing', 32)." {$title}</h2>";
    plugin_do('billing_matrix_new_points_based');

    echo show_form_errors('billing_matrix_new_points_based');
    clear_form_errors('billing_matrix_new_points_based');

    echo "<form name='billing_matrix_new_points_based' action='{$_SERVER['PHP_SELF']}' method='post'>";

    echo "<p align='center'>{$strTag}: <input type='text' name='tag' value='".show_form_value('billing_matrix_new_points_based', 'tag')."' /></p>";

    echo "<table class='maintable'>";

    echo "<tr><th>{$strName}</th><th>{$strPoints}</th></tr>";
    for ($i = 0; $i < 4; $i++) 
    {
        echo "<tr><td><input type='text' name='name[{$i}]' id='name{$i}' /></td><td><input type='text' name='points[{$i}]' id='points{$i}' /></td></tr>";
    }
    
    plugin_do('billing_matrix_new_points_based_form');
    echo "</table>";

    echo "<input type='hidden' name='action' value='new' />";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' />  ";
    echo "<input type='submit' value='{$strSave}' /></p>";
    echo "<p class='return'><a href=\"billing_matrix.php\">{$strReturnWithoutSaving}</a></p>";

    echo "</form>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == "new")
{
    $tag = clean_dbstring($_REQUEST['tag']);
    $names = clean_dbstring($_REQUEST['name']);
    $points = clean_dbstring($_REQUEST['points']); // We don't use clean_float so we can notify if a non numeric value has been entered

    // Check input
    $errors = 0;
    if (empty($tag))
    {
        $errors++;
        $_SESSION['formerrors']['billing_matrix_new_points_based']['tag'] = sprintf($strFieldMustNotBeBlank, $strTag);
    }
    plugin_do('billing_matrix_new_points_based_submitted');

    $sql = "SELECT tag FROM `{$dbBillingMatrixPoints}` WHERE tag='{$tag}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        $errors++;
        $_SESSION['formerrors']['billing_matrix_new_points_based']['tag1'] = sprintf($strADuplicateAlreadyExists, $strTag);
    }

    $pos = 0;
    foreach ( $points AS $p )
    {
        if (!is_numeric($p)) 
        {
            $errors++;
            $_SESSION['formerrors']['billing_matrix_new_points_based']["points{$pos}"] = sprintf($strInvalidParameter, $strTag);
        }
        $pos++;
    }

    if ($errors >= 1)
    {
        $_SESSION['formdata']['billing_matrix_new_points_based'] = cleanvar($_POST, TRUE, FALSE, FALSE,
                                                     array("@"), array("'" => '"'));

        // show error message if errors
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
    else
    {
        for ($i = 0; $i < sizeof($names); $i++)
        {
            $sql = "INSERT INTO `{$dbBillingMatrixPoints}` VALUES ('{$tag}', '{$names[$i]}', $points[$i])";
            $result = mysql_query($sql);
            if (mysql_error())
            {
                $errors++;
                trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                break; // Dont try and add any more
            }
            
        }

        if ($errors >= 1)
        {
            html_redirect("billing_matrix.php?tab=PointsBillable", FALSE, $strBillingMatrixAddFailed);
        }
        else
        {
            clear_form_data('billing_matrix_new_points_based');
            clear_form_errors('billing_matrix_new_points_based');
            plugin_do('billing_matrix_new_points_basedsaved');
            html_redirect("billing_matrix.php?tab=PointsBillable", TRUE);
        }
    }
}