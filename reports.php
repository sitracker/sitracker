<?php
// reports.php - Reports summary page
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_REPORT_RUN;
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>{$strReports}</h2>";

$tables = array();

//reports_draw_menu(60);

foreach (reports_draw_menu(60) AS $r){
    echo "<p>";
    echo $r;
    echo "</p>";
}


function reports_draw_menu($index, $heading = '')
{
    global $hmenu, $tables;

    $html = "<table class='maintable'>";
    if (!empty($heading)) $html .= "<tr><th colspan='2'>{$heading}</th></tr>";
    $html .= "<tr><th>{$GLOBALS['strName']}</th><th>{$GLOBALS['strDescription']}</th></tr>";


    foreach ($hmenu[$index] as $top => $topvalue)
    {
        if (in_array($topvalue['perm'], $_SESSION['permissions']))
        {
            if (array_key_exists('submenu', $topvalue) AND $topvalue['submenu'] > 0)
            {
                reports_draw_menu($topvalue['submenu'], $topvalue['name']);
            }
            else
            {
                $html .= "<tr><td><a href='{$topvalue['url']}'>{$indent} {$topvalue['name']}</a></td><td>{$topvalue['desc']}</td></tr>";
            }
        }
    }

    $html .= "</table>";

    $tables[] = $html;

    return $tables;
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');