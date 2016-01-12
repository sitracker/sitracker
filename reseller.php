<?php
// reseller.php - Page to view resellers
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2016 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul@sitracker.org>

require ('core.php');
$permission = PERM_RESELLER_ADD; // Add Reseller
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$title = $strResellers;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>{$title}</h2>";

$sql = "SELECT * FROM `{$dbResellers}`";
$result = mysqli_query($db, $sql);
if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

if (mysqli_num_rows($result) > 0)
{
    echo "<table class='maintable'>";
    echo "<tr><th>{$strReseller}</tr></th>";
    $shade = 'shade1';
    while ($obj = mysqli_fetch_object($result))
    {
        echo "<tr><td class='{$shade}'>{$obj->name}</td></tr>";
        if ($shade == 'shade1') $shade = 'shade2';
        else $shade = 'shade1';
    }
    echo "</table>";
}
else
{
    echo user_alert($strNoRecords, E_USER_NOTICE);
}

echo "<p align='center'><a href='reseller_new.php'>{$strNewReseller}</a></p>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');