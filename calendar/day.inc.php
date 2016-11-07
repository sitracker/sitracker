<?php
// day.inc.php - Displays a day view of the calendar
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
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

$gidurl = '';
if (!empty($groupid)) $gidurl = "&amp;gid={$groupid}";

// skip over weekends in day view
if (date('D',mktime(0,0,0,$month,$day,$year)) == 'Sat') $day -= 1;
if (date('D',mktime(0,0,0,$month,$day,$year)) == 'Sun') $day += 1;
if (date('D',mktime(0,0,0,$month,$day,$year)) == 'Mon') $pdate = mktime(0,0,0,$month,$day-3,$year);
else $pdate = mktime(0,0,0,$month,$day-1,$year);
if (date('D',mktime(0,0,0,$month,$day,$year)) == 'Fri') $ndate = mktime(0,0,0,$month,$day+3,$year);
else $ndate = mktime(0,0,0,$month,$day+1,$year);
echo "<h2>{$strDayView}</h2>";
echo "<p class='daycalendarview'>";
echo "<a href='{$_SERVER['PHP_SELF']}?display=day&amp;year=".date('Y',$pdate)."&amp;month=".date('m',$pdate)."&amp;day=".date('d',$pdate)."{$gidurl}'>&lt;</a> ";
echo ldate('l dS F Y',mktime(0,0,0,$month,$day,$year));
echo " <a href='{$_SERVER['PHP_SELF']}?display=day&amp;year=".date('Y',$ndate)."&amp;month=".date('m',$ndate)."&amp;day=".date('d',$ndate)."{$gidurl}'>&gt;</a>";
echo "</p>";

$numgroups = group_selector($groupid, "display={$display}&amp;year={$year}&amp;month={$month}&amp;day={$day}");

if ($groupid == 'all') $groupid = '';

echo draw_chart('day', $year, $month, $day, $groupid, $user);


?>
