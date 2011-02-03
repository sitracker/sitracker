<?php
// week.inc.php - Displays a week view of the calendar
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

echo "<h2>".icon('holiday', 32)." {$strWeekView}</h2>";
// Force the week view to the start first day of the week (ie. the monday)
switch (date('D',mktime(0,0,0,$month,$day,$year)))
{
    case 'Tue': $day -= 1; break;
    case 'Wed': $day -= 2; break;
    case 'Thu': $day -= 3; break;
    case 'Fri': $day -= 4; break;
    case 'Sat': $day -= 5; break;
    case 'Sun': $day -= 6; break;
    case 'Mon':
    default: $day=$day; break;
}

$gidurl = '';
if (!empty($groupid)) $gidurl = "&amp;gid={$groupid}";

echo "<p align='center'>";
$pdate = mktime(0,0,0,$month,$day-7,$year);
$ndate = mktime(0,0,0,$month,$day+7,$year);
echo "<a href='{$_SERVER['PHP_SELF']}?display=week&amp;year=".date('Y',$pdate)."&amp;month=".date('m',$pdate)."&amp;day=".date('d',$pdate)."{$gidurl}'>&lt;</a> ";
echo date('dS F Y',mktime(0,0,0,$month,$day,$year))." &ndash; ".date('dS F Y',mktime(0,0,0,$month,$day+7,$year));
echo " <a href='{$_SERVER['PHP_SELF']}?display=week&amp;year=".date('Y',$ndate)."&amp;month=".date('m',$ndate)."&amp;day=".date('d',$ndate)."{$gidurl}'>&gt;</a>";
echo "</p>";

$numgroups = group_selector($groupid, "display={$display}&amp;year={$year}&amp;month={$month}&amp;day={$day}");

if ($groupid == 'all') $groupid = '';

echo draw_chart('week', $year, $month, $day, $groupid, $user);


?>
