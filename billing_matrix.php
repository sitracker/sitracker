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

$title = $strBillingMatrix;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('billing', 32)." {$title}</h2>";

echo "<p align='center'><a href='billing_matrix_add.php'>Add new Billing Matrix</a></p>";

$sql = "SELECT DISTINCT tag FROM `{$dbBillingMatrix}";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

if (mysql_num_rows($result) >= 1)
{


    while ($matrix = mysql_fetch_object($result))
    {
        $sql = "SELECT * FROM `{$dbBillingMatrix}` WHERE tag = '{$matrix->tag}'";
        $matrixresult = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

        echo "<table align='center'>";
        echo "<thead><tr><th colspan='9'>{$matrix->tag} <a href='billing_matrix_edit.php?tag={$matrix->tag}'>{$strEdit}</a></th></tr></thead>\n";
        echo "<tr><th>{$strHour}</th><th>{$strMonday}</th><th>{$strTuesday}</th>";
        echo "<th>{$strWednesday}</th><th>{$strThursday}</th><th>{$strFriday}</th>";
        echo "<th>{$strSaturday}</th><th>{$strSunday}</th><th>{$strPublicHoliday}</th></tr>\n";
        
        while ($obj = mysql_fetch_object($matrixresult))
        {
            echo "<tr><td>{$obj->hour}</td><td>{$obj->mon}</td><td>{$obj->tue}</td>";
            echo "<td>{$obj->wed}</td><td>{$obj->thu}</td><td>{$obj->fri}</td>";
            echo "<td>{$obj->sat}</td><td>{$obj->sun}</td><td>{$obj->holiday}</td></tr>\n";
        }
        echo "</table>";
    }
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');