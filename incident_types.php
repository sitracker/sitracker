<?php
// incident_types.php - Incident Types
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2013 The Support Incident Tracker Project
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_PRODUCT_VIEW; // View Products and Software
require (APPLICATION_LIBPATH.'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$title = $strIncidentTypes;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');
echo "<h2>{$title}</h2>";

echo "<p align='center'><a href='incident_types_new.php'>{$strNewIncidentType}</a></p>";

$sql = "SELECT * FROM `{$dbIncidentTypes}`";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
if (mysql_num_rows($result) >= 1)
{
    echo "<table align='center'>";
    echo "<tr><th>{$strType}</th><th>{$strIncidentType}</th><th>{$strActions}</th></tr>";
    while ($type = mysql_fetch_object($result))
    {
        echo "<tr><td>{$type->type}<td>{$type->name}</td><td>";
        if ($type->type == 'user') echo "<a href='incident_types_edit.php?id={$type->id}'>{$strEdit}</a>";
        echo "</td></tr>";
    }
    echo "</table>";
}
else
{
    echo user_alert($strNoRecords, E_USER_NOTICE);
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');