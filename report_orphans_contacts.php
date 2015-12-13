<?php
// control_panel.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_SITE_VIEW;
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$title = $strShowOrphanedContacts;

$sql = "SELECT * FROM `{$dbContacts}` WHERE siteid = 0";
$result = mysqli_query($db, $sql);
if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);

echo "<h2>{$title}</h2>";

if (mysqli_num_rows($result) > 0)
{
    echo "<p align='center'>{$strOrphanedSites}</p>";

    echo "<div><table class='vertical'>";
    echo "<tr><th>{$strSiteName}</th></tr>";

    $shade = "shade1";

    while ($contact = mysqli_fetch_object($result))
    {
        echo "<tr class='{$shade}'><td>{$contact->forenames} {$contact->surname}</td></tr>";

        if ($shade == 'shade1')
        {
            $shade = 'shade2';
        }
        else
        {
            $shade = 'shade1';
        }
    }

    echo "</table></div>";
}
else
{
    echo "<p align='center'>{$strNoOrphanedContacts}</p>";
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>