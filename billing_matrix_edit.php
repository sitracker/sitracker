<?php
// billing_matrix.php - Page to view a billing matrix
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
    $sql = "SELECT * FROM `{$dbBillingMatrixUnit}` WHERE tag='{$tag}' ORDER BY hour";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>".icon('billing', 32)." {$title}</h2>";
        plugin_do('billing_matrix_edit');

        echo "<p align='center'>{$tag}</p>";

        echo "<form name='billing_matrix_edit' action='{$_SERVER['PHP_SELF']}' method='post'>";

        echo "<table class='maintable'>";

        echo "<tr><th>{$strHour}</th><th>{$strMonday}</th><th>{$strTuesday}</th>";
        echo "<th>{$strWednesday}</th><th>{$strThursday}</th><th>{$strFriday}</th>";
        echo "<th>{$strSaturday}</th><th>{$strSunday}</th><th>{$strPublicHoliday}</th></tr>\n";

        $days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun', 'holiday');

        while ($obj = mysql_fetch_object($result))
        {
            echo "<tr>";
            echo "<th>{$obj->hour}</th>";
            foreach ($days AS $day)
            {
                $id = "{$day}_{$obj->hour}";
                echo "<td>".billing_multiplier_dropdown($id, $obj->$day)."</td>";
            }
            echo "</tr>";
        }
        plugin_do('billing_matrix_edit_form');
        echo "</table>";
        echo "<input type='hidden' name='tag' value='{$tag}' />";
        echo "<input type='hidden' name='action' value='edit' />";
        echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
        echo "<input type='submit' value='{$strSave}' /></p>";
        echo "<p class='return'><a href=\"billing_matrix.php\">{$strReturnWithoutSaving}</a></p>";

        echo "</form>";


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

    $values = array();

    $days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun', 'holiday');

    $hour = 0;

    plugin_do('billing_matrix_edit_submitted');

    while ($hour < 24)
    {
        $values[$hour] = array();

        $mon = clean_float($_REQUEST["mon_{$hour}"]);
        $tue = clean_float($_REQUEST["tue_{$hour}"]);
        $wed = clean_float($_REQUEST["wed_{$hour}"]);
        $thu = clean_float($_REQUEST["thu_{$hour}"]);
        $fri = clean_float($_REQUEST["fri_{$hour}"]);
        $sat = clean_float($_REQUEST["sat_{$hour}"]);
        $sun = clean_float($_REQUEST["sun_{$hour}"]);
        $holiday = clean_float($_REQUEST["holiday_{$hour}"]);

        $sql = "UPDATE `{$dbBillingMatrixUnit}` SET mon = {$mon}, tue = {$tue}, wed = {$wed}, thu = {$thu}, ";
        $sql .= "fri = {$fri}, sat = {$sat}, sun = {$sun}, holiday = {$holiday} WHERE tag = '{$tag}' AND hour = {$hour}";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            $errors++;
            trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            break; // Dont try and add any more
        }

        $hour++;
    }

    if ($errors >= 1)
    {
        html_redirect("billing_matrix.php", FALSE, $strBillingMatrixEditFailed);
    }
    else
    {
        plugin_do('billing_matrix_edit_saved');
        html_redirect("billing_matrix.php", TRUE);
    }
}
else
{
    html_redirect("billing_matrix.php", FALSE, $strRequiredDataMissing);
}