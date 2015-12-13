<?php
// billing_matrix_edit_points_based.php - Page to edit points based billing matrix
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission =  PERM_BILLING_DURATION_EDIT;  // TODO we need a permission to administer billing matrixes
require_once (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require_once (APPLICATION_LIBPATH . 'auth.inc.php');

$tag = clean_dbstring($_REQUEST['tag']);
$action = cleanvar($_REQUEST['action']);

$title = $strEditBillingMatrix;

if (!empty($tag) AND empty($action))
{
    $sql = "SELECT * FROM `{$dbBillingMatrixPoints}` WHERE tag='{$tag}' ORDER BY points ASC";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    if (mysqli_num_rows($result) > 0)
    {
        $html = "<h2>".icon('billing', 32)." {$title}</h2>";
        plugin_do('billing_matrix_edit');

        $html .= "<p align='center'>{$tag}</p>";

        $html .= "<form name='billing_matrix_edit_points_based' action='{$_SERVER['PHP_SELF']}' method='post'>";

        $html .= "<table class='maintable'>";
        $html .= "<tr><th>{$GLOBALS['strName']}</th><th>{$GLOBALS['strPoints']}</th></tr>\n";

        while ($obj = mysqli_fetch_object($result))
        {
            $html .= "<tr><td><input type='text' name='name[{$obj->id}]' id='name-{$obj->id}' value='{$obj->name}' /></td><td><input type='text' name='points[{$obj->id}]' id='points-{$obj->id}' value='{$obj->points}' /></td></tr>\n";
        }

        plugin_do('billing_matrix_edit_points_based_form');
        $html .="</table>";
        $html .="<input type='hidden' name='tag' value='{$tag}' />";
        $html .="<input type='hidden' name='action' value='edit' />";
        $html .="<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
        $html .="<input type='submit' value='{$strSave}' /></p>";
        $html .="<p class='return'><a href=\"billing_matrix.php\">{$strReturnWithoutSaving}</a></p>";

        $html .="</form>";

        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo $html;
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
    else
    {
        html_redirect("billing_matrix.php", FALSE, $strNoBillingMatrixFound);
    }
}
else if(!empty($tag) AND $action == "edit")
{
    $tag = clean_dbstring($_REQUEST['tag']);
    $names = clean_dbstring($_REQUEST['name']);
    $points = clean_dbstring($_REQUEST['points']); // We don't use clean_float so we can notify if a non numeric value has been entered

    $values = array();

    $days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun', 'holiday');

    $hour = 0;

    plugin_do('billing_matrix_edit_points_based_submitted');
   
    foreach ($names as $key => $value)
    {
        $sql = "UPDATE `{$dbBillingMatrixPoints}` SET name = '{$value}', points = {$points[$key]} WHERE id={$key} ";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db))
        {
            $errors++;
            trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
            break; // Dont try and add any more
        }
    }

    if ($errors >= 1)
    {
        html_redirect("billing_matrix.php?tab=PointsBillable", FALSE, $strBillingMatrixEditFailed);
    }
    else
    {
        plugin_do('billing_matrix_edit_points_based_saved');
        html_redirect("billing_matrix.php?tab=PointsBillable", TRUE);
    }
}
else
{
    html_redirect("billing_matrix.php?tab=PointsBillable", FALSE, $strRequiredDataMissing);
}