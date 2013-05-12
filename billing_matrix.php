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
$permission = PERM_BILLING_DURATION_EDIT;  // TODO we need a permission to administer billing matrixes
require_once (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require_once (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strBillingMatrix;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('billing', 32)." {$title}</h2>";
plugin_do('billing_matrix');

echo "<p align='center'><a href='billing_matrix_new.php'>$strAddNewBillingMatrix</a></p>";

$sql = "SELECT DISTINCT tag FROM `{$dbBillingMatrixUnit}";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

if (mysql_num_rows($result) >= 1)
{
    while ($matrix = mysql_fetch_object($result))
    {
        $sql = "SELECT * FROM `{$dbBillingMatrixUnit}` WHERE tag = '{$matrix->tag}'";
        $matrixresult = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

        echo "<table class='maintable'>";
        echo "<thead><tr><th colspan='9'>{$matrix->tag} <a href='billing_matrix_edit.php?tag={$matrix->tag}'>{$strEdit}</a></th></tr></thead>\n";
        echo "<tr><th>{$strHour}</th><th>{$strMonday}</th><th>{$strTuesday}</th>";
        echo "<th>{$strWednesday}</th><th>{$strThursday}</th><th>{$strFriday}</th>";
        echo "<th>{$strSaturday}</th><th>{$strSunday}</th><th>{$strPublicHoliday}</th></tr>\n";
        $shade = 'shade1';
        while ($obj = mysql_fetch_object($matrixresult))
        {
            echo "<tr class='{$shade}'><td>{$obj->hour}</td><td>&#215;{$obj->mon}</td><td>&#215;{$obj->tue}</td>";
            echo "<td>&#215;{$obj->wed}</td><td>&#215;{$obj->thu}</td><td>&#215;{$obj->fri}</td>";
            echo "<td>&#215;{$obj->sat}</td><td>&#215;{$obj->sun}</td><td>&#215;{$obj->holiday}</td></tr>\n";
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        echo "</table>";
    }
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');