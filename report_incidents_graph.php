<?php
// incident_graph.php - Shows incidents opened and closed each day over twelve months
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


$permission = 37; // Run Reports
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$startyear = clean_int($_REQUEST['startyear']);

$title = $strIncidentsLoggedOpenClosed;

$openedcolour = '#FF962A';
$closedcolour = '#72B8B8';
$currentcolour = '#1CA772';

$currentyear = date('Y');
include (APPLICATION_INCPATH . 'htmlheader.inc.php');
$currentyear = date('Y');
$currentmonth = date('n');
$daysinyear = date('z', mktime(0, 0, 0, 12, 31, $year));
flush();

echo "<table summary='{$strIncidentsLoggedOpenClosed}' align='center'>";
if (empty($startyear))
{
    $startyear = $currentyear;
    $lastyear = $currentyear+1;
}

if (empty($startmonth))
{
    $startmonth = 1;
    $lastyear = $startyear+1;
}
else
{
    $lastyear=$startyear+2;
}

if ($startyear == $currentyear)
{
    $lastmonth = $currentmonth;
}
else
{
    $lastmonth = 12;
}

echo "<h2>".icon('reports', 32)." {$strIncidentsLoggedOpenClosed}</h2>";
echo "<p align='center'>{$strIncidentsOpenedEachDay}<br />";
echo "<a href='{$_SERVER['PHP_SELF']}?startyear=".($currentyear-2)."'>".($currentyear-2)."</a> | ";
echo "<a href='{$_SERVER['PHP_SELF']}?startyear=".($currentyear-1)."'>".($currentyear-1)."</a> | ";
echo "<a href='{$_SERVER['PHP_SELF']}?startyear=".($currentyear)."'>".($currentyear)."</a>";
echo "</p>";

// If we're starting part way through a year, we need to loop years to ensure we do up to the same time next year
for ($year = $startyear; $year < $lastyear; $year++)
{
    // loop through years
    $grandtotal = 0;
    for ($month = $startmonth; $month <= $lastmonth; $month++)
    {
        // loop through months
        $monthname = date('F', mktime(0, 0, 0, $month, 1, $year));
        $daysinmonth = date('t', mktime(0, 0, 0, $month, 1, $year));
        $colspan = ($daysinmonth * 2) + 1;  // have to calculate number of cols since ie doesn't seem to do colspan=0
        echo "<tr><td align='center' colspan='{$colspan}'><h2><a href='{$_SERVER['PHP_SELF']}?startyear={$year}&startmonth={$month}'>{$monthname} {$year}</a></h2></td></tr>\n";
        echo "<tr align='center'>";
        echo "<td><img src='images/graph_scale.jpg' width='11' height='279' alt=''></td>";
        $monthtotal = 0;
        $monthtotalclosed = 0;
        // loop through days
        for ($day = 1; $day <= $daysinmonth; $day++)
        {
            $countdayincidents = countdayincidents($day, $month, $year);
            // not needed $countdaycurrentincidents=countdaycurrentincidents($day, $month, $year);
            $countdayclosedincidents = countdayclosedincidents($day, $month, $year);
            echo "<td valign='bottom' >";
            if ($countdayincidents > 0)
            {
                $height = $countdayincidents * 4;
                echo "<div style='cursor: help; height: {$height}px; width: 5px; background-color: {$openedcolour};' title='{$countdayincidents}'>&nbsp;</div>";
                $monthtotal += $countdayincidents;
            }
            echo "</td>";

            /*
            current not really needed, slow and looks pretty static
            $currentheight=$countdaycurrentincidents/4;
            $monthtotalcurrent+=$countdaycurrentincidents;
            echo "<td valign=\"bottom\" >";
            if ($countdaycurrentincidents>0)  echo "<div style='cursor: help; height: {$currentheight}px;  width: 5px; background-color: $currentcolour;' title='$countdaycurrentincidents Incidents current on $day $monthname $year'>&nbsp;</div>";
            echo "</td>";
            */

            $closedheight = $countdayclosedincidents * 4;
            $monthtotalclosed += $countdayclosedincidents;
            echo "<td valign='bottom' >";
            if ($countdayclosedincidents > 0)
            {
                echo "<div style='cursor: help; height: {$closedheight}px;  width: 5px; background-color: {$closedcolour};' title='{$countdayclosedincidents}'>&nbsp;</div>";
            }
            echo "</td>";
        }
        echo "</tr>\n";
        echo "<tr><td>&nbsp;</td>";
        for ($day = 1; $day <= $daysinmonth; $day++)
        {
            echo "<td colspan='2' align='center'>{$day}</td>";
        }
        echo "</tr>\n";
        $grandtotal += $monthtotal;
        $grandtotalclosed += $monthtotalclosed;

        $diff = ($monthtotal - $monthtotalclosed);

        if ($diff < 0)
        {
            $diff = "<span style='color: {$closedcolour};'>{$diff}</span>";
        }
        else
        {
            $diff = "<span style='color:{$openedcolour};'>{$diff}</span>";
        }

        echo "<tr><td align='center' colspan='{$colspan}' style='border-bottom: 2px solid #000;'>";
        echo "<p>{$strTotal}: <strong style='color: {$openedcolour};'>{$monthtotal}</strong> {$strOpened}. ";
        echo "<strong style='color: {$closedcolour};'>{$monthtotalclosed}</strong> {$strClosed}. {$strDifference}: <strong>{$diff}</strong><br />";

        $diff = ($grandtotal - $grandtotalclosed);

        if ($diff < 0)
        {
            $diff = "<span style='color: {$closedcolour};'>{$diff}</span>";
        }
        else
        {
            $diff = "<span style='color: {$openedcolour};'>{$diff}</span>";
        }

        echo "{$strGrandTotal}: <strong style='color: {$openedcolour};'>{$grandtotal}</strong> {$strOpened}. ";
        echo "<strong style='color: {$closedcolour};'>{$grandtotalclosed}</strong> {$strClosed}. {$strDifference} <strong>{$diff}</strong></p></td></tr>\n";
    }

    if ($startmonth > 1)
    {
        $lastmonth = $startmonth - 1;
        $startmonth = 1;
    }
}
echo "</table>\n\n";
$diff = ($grandtotal - $grandtotalclosed);

if ($diff < 0)
{
    $diff = "<span style='color: {$closedcolour};'>{$diff}</span>";
}
else
{
    $diff="<span style='color: {$openedcolour};'>{$diff}</span>";
}

echo "<h3>".($year - 1)." {$strTOTALS}: <u style='color: {$openedcolour};'>{$grandtotal}</u> {$strOpened}. ";
echo "<u style='color: {$closedcolour};'>{$grandtotalclosed}</u> {$strClosed}. {$strDifference}: <u>$diff</u></h3>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>