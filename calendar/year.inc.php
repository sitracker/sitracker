<?php
// year.inc.php - Displays a year view of the calendar
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
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

// Display year calendar
if ($type < HOL_PUBLIC)
{
    echo "<h2>".icon('holiday', 32)." {$strCalendar}: ";
    if ($user == 'all' AND $approver == TRUE) echo $strAll;
    else echo user_realname($user,TRUE);
    if ($type == HOL_HOLIDAY)
    {
        echo "<p align='center'>".sprintf($strUsedNofNDaysEntitlement, user_count_holidays($user, $type), user_holiday_entitlement($user))."<br />";
    }

    echo appointment_type_dropdown($type, 'year');

    $sql = "SELECT * FROM {$dbHolidays} WHERE userid='{$user}' AND approved=0 AND type='{$type}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result))
    {
        echo "<table class='maintable'>";
        echo "<tr class='shade2'><td><strong>{$strAwaitingApproval}</strong>:</td></tr>";
        echo "<tr class='shade1'><td>";
        while ($dates = mysql_fetch_object($result))
        {
            echo date('l ', strtotime($dates->date));
            if ($dates->length == 'am') echo "{$strMorning} ";
            if ($dates->length == 'pm') echo "{$strAfternoon} ";
            echo date('jS F Y', strtotime($dates->date));
            echo "<br/>\n";
        }
        echo "</td></tr>\n";
        echo "<tr class='shade1'><td><a href='holiday_request.php?type={$type}'>{$strSendRequest}</a></td></tr>";
        echo "</table>";
    }
    mysql_free_result($result);
}
else
{
    // Public Holidays are a special type = 10
    echo "<h2>{$strSetPublicHolidays}</h2>";
}

echo "<h2>{$strYearView}</h2>";
$pdate = mktime(0, 0, 0, $month, $day, $year-1);
$ndate = mktime(0, 0, 0 ,$month, $day, $year+1);
echo "<p class='yearcalendarview'>";
echo "<a href='{$_SERVER['PHP_SELF']}?display=year&amp;year=".date('Y',$pdate)."&amp;month=".date('m',$pdate)."&amp;day=".date('d',$pdate)."&amp;type={$type}'>&lt;</a> ";
echo date('Y', mktime(0, 0, 0, $month, $day, $year));
echo " <a href='{$_SERVER['PHP_SELF']}?display=year&amp;year=".date('Y',$ndate)."&amp;month=".date('m',$ndate)."&amp;day=".date('d',$ndate)."&amp;type={$type}'>&gt;</a>";
echo "</p>";


echo "<table class='maintable yearcalendar'>";
$displaymonth = 1;
$displayyear = $year;
for ($r = 1; $r <= 3; $r++)
{
    echo "<tr>";
    for ($c = 1; $c <= 4;$c++)
    {
        echo "<td class='shade1 yearcalendar'>";
        draw_calendar($displaymonth, $displayyear);
        echo "</td>";
        if ($displaymonth == 12)
        {
            $displayyear++;
            $displaymonth = 0;
        }
        $displaymonth++;
    }
    echo "</tr>";
}
echo "</table>";


?>
