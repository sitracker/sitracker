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

$permission =  81;  // TODO we need a permission to administer billing matrixes

require ('core.php');
require_once (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require_once (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNewBillingMatrix;

$action = $_REQUEST['action'];

if (empty($action) OR $action == "showform")
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('billing', 32)." {$title}</h2>";

    echo show_form_errors('billing_matrix_new');
    clear_form_errors('billing_matrix_new');

    echo "<form name='billing_matrix_new' action='{$_SERVER['PHP_SELF']}' method='post'>";

    echo "<p align='center'>{$strTag}: <input type='text' name='tag' value='{$_SESSION['formdata']['billing_matrix_new']['tag']}' /></p>";

    echo "<table align='center'>";

    echo "<tr><th>{$strHour}</th><th>{$strMonday}</th><th>{$strTuesday}</th>";
    echo "<th>{$strWednesday}</th><th>{$strThursday}</th><th>{$strFriday}</th>";
    echo "<th>{$strSaturday}</th><th>{$strSunday}</th><th>{$strPublicHoliday}</th></tr>\n";

    $hour = 0;

    $days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun', 'holiday');

    while ($hour < 24)
    {
        echo "<tr><th>{$hour}</th>";

        foreach ($days AS $day)
        {
            $id = "{$day}_{$hour}";

            if (!empty($_SESSION['formdata']['billing_matrix_new'][$id])) $i = $_SESSION['formdata']['billing_matrix_new'][$id];
            else $i = '';
            echo "<td>".billing_multiplier_dropdown($id, $i)."</td>";
        }

        echo "</tr>";
        $hour++;
    }
    echo "</table>";

    echo "<input type='hidden' name='action' value='new' />";
    echo "<p align='center'><input type='submit' value='{$strSave}' /></p>";

    echo "</form>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == "new")
{
    $tag = clean_dbstring($_REQUEST['tag']);

    // Check input
    $errors = 0;
    if (empty($tag))
    {
        $errors++;
        $_SESSION['formerrors']['billing_matrix_new']['tag'] = sprintf($strFieldMustNotBeBlank, $strTag);
    }

    $sql = "SELECT tag FROM `{$dbBillingMatrix}` WHERE tag='{$tag}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        $errors++;
        $_SESSION['formerrors']['billing_matrix_new']['tag1'] = sprintf(strADuplicateAlreadyExists, $strTag);
    }


    if ($errors >= 1)
    {
        $_SESSION['formdata']['billing_matrix_new'] = cleanvar($_POST, TRUE, FALSE, FALSE,
                                                     array("@"), array("'" => '"'));

        // show error message if errors
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
    else
    {
        $values = array();

        $days = array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun', 'holiday');

        $hour = 0;

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

            $sql = "INSERT INTO `{$dbBillingMatrix}` (tag, hour, mon, tue, wed, thu, fri, sat, sun, holiday) ";
            $sql .= "VALUES ('{$tag}', {$hour}, {$mon}, {$tue}, {$wed}, {$thu}, {$fri}, {$sat}, {$sun}, {$holiday})";
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
            html_redirect("billing_matrix.php", FALSE, $strBillingMatrixAddFailed);
        }
        else
        {
            html_redirect("billing_matrix.php", TRUE);
        }
    }
}