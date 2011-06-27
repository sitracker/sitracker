<?php
// calendar.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//         Tom Gerrard <tom.gerrard[at]salfordsoftware.co.uk>


$permission = PERM_CALENDAR_VIEW; // View your calendar
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');
include ('calendar/calendar.inc.php');

$groupid = clean_int($_REQUEST['gid']);
if (empty($groupid)) $groupid = clean_int($_SESSION['groupid']);

// External variables
$user = clean_int($_REQUEST['user']);
$nmonth = clean_int($_REQUEST['nmonth']);
$nyear = clean_int($_REQUEST['nyear']);
$type = clean_int($_REQUEST['type']);
$selectedday = clean_int($_REQUEST['selectedday']);
$selectedmonth = clean_int($_REQUEST['selectedmonth']);
$selectedyear = clean_int($_REQUEST['selectedyear']);
$selectedtype = clean_int($_REQUEST['selectedtype']);
$approved = clean_int($_REQUEST['approved']);
$length = clean_fixed_list($_REQUEST['length'], array('day','am','pm'));
$display = clean_fixed_list($_REQUEST['display'], array('month','list','year','week','day'));
$weeknumber = clean_int($weeknumber);

$title = $strCalendar;
$pagecss = array('calendar/planner.css.php');
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

if (empty($user) || $user == 'current') $user = $sit[2];
elseif ($user == 'all') $user = '';
if (empty($type)) $type = HOL_HOLIDAY;
if (user_permission($sit[2], PERM_HOLIDAY_APPROVE)) $approver = TRUE;
else $approver = FALSE;

// Force user to 0 (SiT) when setting public holidays
if ($type == HOL_PUBLIC) $user = 0;

$gidurl = '';
if (!empty($groupid)) $gidurl = "&amp;gid={$groupid}";

// Defaults
if (empty($_REQUEST['year'])) $year = date('Y');
else $year = intval($_REQUEST['year']);

if (empty($_REQUEST['month'])) $month = date('m');
else $month = intval($_REQUEST['month']);

if (empty($_REQUEST['day'])) $day = date('d');
else $day = intval($_REQUEST['day']);

$calendarTypes = array('list','year','month','week','day');

// Prevent people from including any old file - this also handles any cases
// where $display == 'chart'
if (!in_array($display, $calendarTypes)) $display = 'month';

// Navigation (Don't show for public holidays)
if ($type != HOL_PUBLIC)
{
    echo "<p>{$strDisplay}: ";
    foreach ($calendarTypes as $navType)
    {
        $navHtml[$navType]  = "<a href='{$_SERVER['PHP_SELF']}?display={$navType}";
        $navHtml[$navType] .= "&amp;year={$year}&amp;month={$month}&amp;day={$day}";
        $navHtml[$navType] .= "&amp;type={$type}{$gidurl}'>";
        $navi18n = eval('return $str' . ucfirst($navType) . ';');
        if ($display == $navType) $navHtml[$navType] .= '<em>' . $navi18n . '</em>';
        else $navHtml[$navType] .= $navi18n;
        $navHtml[$navType] .= "</a>";
    }
    echo implode(' | ', $navHtml);
    echo "</p>";
}
include ("calendar/{$display}.inc.php");

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>