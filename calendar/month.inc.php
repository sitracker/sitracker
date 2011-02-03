<?php
// month.inc.php - Displays a month view of the calendar
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Included by ../calendar.php

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

// Display planner chart
echo "<h2>".icon('holiday', 32)." {$strMonthView}</h2>";

$nextyear = $year;
if ($month < 12)
{
    $nextmonth = $month + 1;
}
else
{
    $nextmonth = 1;
    $nextyear = $year + 1;
}

$prevyear = $year;

if ($month > 1)
{
    $prevmonth = $month - 1;
}
else
{
    $prevmonth = 12;
    $prevyear = $year - 1;
}

$plugin_calendar = plugin_do('holiday_chart_cal');

echo month_select($month, $year, $gidurl);
echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?month={$prevmonth}&amp;";
echo "year={$prevyear}{$gidurl}' title='Previous Month'>&lt;</a> ";
echo ldate('F Y',mktime(0,0,0,$month,1,$year));
echo " <a href='{$_SERVER['PHP_SELF']}?month={$nextmonth}&amp;year={$nextyear}{$gidurl}' ";
echo "title='Next Month'>&gt;</a></p>";

// echo draw_chart('month', $year, $month, $day, '', $user);

$numgroups = group_selector($groupid, "display={$display}&amp;year={$year}&amp;month={$month}&amp;day={$day}");

if ($groupid == 'all') $groupid = '';

echo draw_chart('month', $year, $month, $day, $groupid, $user);
?>