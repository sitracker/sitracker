<?php
// holidays.php -
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional!  13Sep06

$permission = 4; // Edit your profile

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$approver = user_permission($sit[2], 50); // Approve holidays

if (!empty($_REQUEST['user']))
{
    $user = cleanvar($_REQUEST['user']);
}
else
{
    $user = $sit[2];
}

if ($user == $sit[2])
{
    $title = sprintf($strUsersHolidays, $_SESSION['realname']);
}
else
{
    $title = sprintf($strUsersHolidays, user_realname($user));
}

include (APPLICATION_INCPATH . 'htmlheader.inc.php');
echo "<h2>".icon('holiday', 32)." ";
echo "$title</h2>";

echo "<p align='center'>";
echo "<a href='book_holidays.php?user={$user}'>{$strBookHoliday}</a>";
echo " | <a href='calendar.php'>{$strHolidayPlanner}</a>";
if ($approver)
{
    echo " | <a href='holiday_request.php?user=";
    if (user == $sit[2]) echo "all";
    else echo $user;
    echo "&amp;mode=approval'>{$strApproveHolidays}</a>";
}
echo "</p>\n";

// Entitlement
if ($user == $sit[2] OR $approver == TRUE)
{
    // Only shown when viewing your own holidays or when you're an approver
    $holiday_resetdate = user_holiday_resetdate($user);
    echo "<table align='center' width='450'>\n";
    echo "<tr><th class='subhead'>{$strHolidays}</th></tr>\n";
    echo "<tr class='shade1'><td><strong>{$strHolidayEntitlement}</strong> ";
    printf("({$strUntilX})", date($CONFIG['dateformat_shortdate'], $holiday_resetdate));
    echo ":</td></tr>\n";
    echo "<tr class='shade2'><td>";
    if ($holiday_resetdate != '' AND $holiday_resetdate > 0 AND $holiday_resetdate >= $now)
    {
        $entitlement = user_holiday_entitlement($user);
        $totalholidaystaken = user_count_holidays($user, HOL_HOLIDAY, 0, array(HOL_APPROVAL_GRANTED));
        $holidaystaken = user_count_holidays($user, HOL_HOLIDAY, $holiday_resetdate, array(HOL_APPROVAL_GRANTED));
        $awaitingapproval = user_count_holidays($user, HOL_HOLIDAY, 0, array(HOL_APPROVAL_NONE));
        echo "{$entitlement} {$strDays}, ";
        echo "$holidaystaken {$strtaken}, ";
        printf ($strRemaining, $entitlement-$holidaystaken);
        if ($awaitingapproval > 0) echo ", {$awaitingapproval} {$strAwaitingApproval} ";
        if ($totalholidaystaken > $holidaystaken)
        {
            $moreholstaken = $totalholidaystaken - $holidaystaken;
            echo "<br />+ {$moreholstaken} {$strtakennextperiod}";
        }
    }
    echo "</td></tr>\n";
    echo "<tr class='shade1'><td ><strong>{$strOtherLeave}</strong>:</td></tr>\n";
    echo "<tr class='shade2'><td>";
    echo user_count_holidays($user, HOL_SICKNESS)." {$strdayssick}, ";
    echo user_count_holidays($user, HOL_WORKING_AWAY)." {$strdaysworkingaway}, ";
    echo user_count_holidays($user, HOL_TRAINING)." {$strdaystraining}";
    echo "<br />";
    echo user_count_holidays($user, HOL_FREE)." {$strdaysother}";
    echo "</td></tr>\n";
    echo "</table>\n";
}

// Holiday List
echo "<table align='center' width='450'>\n";
echo "<tr><th colspan='4' class='subhead'>{$strHolidayList}</th></tr>\n";
$sql = "SELECT * FROM `{$dbHolidays}` WHERE userid='{$user}' ";
$sql .= "AND approved = ".HOL_APPROVAL_NONE." AND type < 10 ORDER BY date ASC";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
$numwaiting = mysql_num_rows($result);
if ($numwaiting > 0)
{
    if ($user == $sit[2] OR $approver == TRUE)
    {
        // Show dates waiting approval, but only to owner
        echo "<tr class='shade2'><td colspan='4'><strong>{$strDatesNotYetApproved}</strong>:</td></tr>";
        while ($dates = mysql_fetch_object($result))
        {
            $dates->date = mysql2date($dates->date, TRUE);
            echo "<tr class='shade1'><td>{$dates->name}</td>";
            echo "<td>".ldate('l', $dates->date, TRUE)." ";
            if ($dates->length == 'am') echo "<u>{$strMorning}</u> ";
            if ($dates->length == 'pm') echo "<u>{$strAfternoon}</u> ";
            echo ldate('jS F Y', $dates->date, TRUE);
            echo "</td>";
            echo "<td>";
            echo holiday_approval_status($dates->approved, $dates->approvedby);
            echo "</td>";
            echo "<td>";
            if ($dates->length == 'pm' OR $dates->length == 'day')
            {
                echo "<a href='holiday_add.php?type={$dates->type}&amp;user=$user";
                echo "&amp;year=".date('Y',$dates->date)."&amp;month=";
                echo date('m',$dates->date)."&amp;day=";
                echo date('d',$dates->date)."&amp;length=am' ";
                echo "onclick=\"return window.confirm('".ldate('l jS F Y', $dates->date, TRUE);
                echo ": {$strHolidayMorningOnlyConfirm}');\" title='{$strHolidayMorningOnly}'>{$strAM}</a> | ";
            }

            if ($dates->length == 'am' OR $dates->length == 'day')
            {
                echo "<a href='holiday_add.php?type={$dates->type}&amp;user=$user";
                echo "&amp;year=".date('Y',$dates->date)."&amp;month=";
                echo date('m',$dates->date)."&amp;day=";
                echo date('d',$dates->date)."&amp;length=pm' ";
                echo "onclick=\"return window.confirm('".ldate('l jS F Y', $dates->date, TRUE);
                echo ": {$strHolidayAfternoonOnlyConfirm}');\" title='{$strHolidayAfternoonOnly}'>{$strPM}</a> | ";
            }

            if ($dates->length == 'am' OR $dates->length == 'pm')
            {
                echo "<a href='holiday_add.php?type={$dates->type}&amp;user=$user";
                echo "&amp;year=".date('Y',$dates->date)."&amp;month=";
                echo date('m',$dates->date)."&amp;day=";
                echo date('d',$dates->date)."&amp;length=day' ";
                echo "onclick=\"return window.confirm('".ldate('l jS F Y', $dates->date, TRUE);
                echo ": {$strHolidayFullDayConfirm}');\" title='{$strHolidayFullDay}'>{$strAllDay}</a> | ";
            }

            if ($sit[2] == $user)
            {
                echo "<a href='holiday_add.php?year=".date('Y',$dates->date);
                echo "&amp;month=".date('m',$dates->date)."&amp;day=";
                echo date('d',$dates->date)."&amp;user={$sit[2]}&amp;type=";
                echo "{$dates->type}&amp;length=0&amp;return=holidays' ";
                echo "onclick=\"return window.confirm('".date('l jS F Y', $dates->date);
                echo ": {$strHolidayCancelConfirm}');\" title='{$strHolidayCancel}'>{$strCancel}</a>";
            }
            echo "</td></tr>\n";
        }
        echo "<tr class='shade1'><td colspan='4'><a href='holiday_request.php?action=resend'>{$strSendReminderRequest}</a></td></tr>";
    }
}
mysql_free_result($result);

// Get list of holiday types
$holidaytype[HOL_HOLIDAY] = $GLOBALS['strHoliday'];
$holidaytype[HOL_SICKNESS] = $GLOBALS['strAbsentSick'];
$holidaytype[HOL_WORKING_AWAY] = $GLOBALS['strWorkingAway'];
$holidaytype[HOL_TRAINING] = $GLOBALS['strTraining'];
$holidaytype[HOL_FREE] = $GLOBALS['strCompassionateLeave'];

$totaltaken = 0;

foreach ($holidaytype AS $htypeid => $htype)
{
    $sql = "SELECT * FROM `{$dbHolidays}` ";
    $sql .= "WHERE userid='{$user}' AND type={$htypeid} ";
    $sql.= "AND (approved=".HOL_APPROVAL_GRANTED." OR (approved=".HOL_APPROVAL_GRANTED_ARCHIVED." AND date >= FROM_UNIXTIME($now))) ORDER BY date ASC ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $numtaken = mysql_num_rows($result);
    $totaltaken += $numtaken;
    if ($numtaken > 0)
    {
        echo "<tr class='shade2'><td colspan='4'><strong>{$htype}</strong>:</td></tr>";
        while ($dates = mysql_fetch_object($result))
        {
            $dates->date = mysql2date($dates->date, TRUE);
            echo "<tr class='shade1'>";
            echo "<td colspan='2'>".ldate('l', $dates->date, TRUE)." ";
            if ($dates->length == 'am') echo "<u>{$strMorning}</u> ";
            if ($dates->length == 'pm') echo "<u>{$strAfternoon}</u> ";
            echo date('jS F Y', $dates->date);
            echo "</td>";
            echo "<td";
            if ($dates->approved == HOL_APPROVAL_GRANTED_ARCHIVED
               OR ($dates->approved == HOL_APPROVAL_GRANTED AND $dates->date < $today))
            {
               echo " colspan='2'";
            }
            echo ">";
            echo holiday_approval_status($dates->approved, $dates->approvedby);
            echo "</td>";
            if ($dates->approved == HOL_APPROVAL_GRANTED AND $dates->date >= $today)
            {
                echo "<td>";
                echo "<a href='holiday_add.php?year=".date('Y',$dates->date);
                echo "&amp;month=".date('m',$dates->date)."&amp;day=";
                echo date('d',$dates->date)."&amp;user={$sit[2]}&amp;type=";
                echo "{$dates->type}&amp;length=0&amp;return=holidays' ";
                echo "onclick=\"return window.confirm('".date('l jS F Y', $dates->date);
                echo ": {$strHolidayCancelConfirm}');\" title='{$strHolidayCancel}'>{$strCancel}</a>";
                echo "</td>";
            }
            echo "</tr>\n";
        }
    }
    mysql_free_result($result);
}

if ($totaltaken < 1 AND $numwaiting < 1)
{
    echo "<tr class='shade2'><td colspan='4'><em>{$strNone}</em</td></tr>\n";
}
echo "</table>\n";


// AWAY TODAY
if ($user == $sit[2])
{
    // Only show when viewing your own holiday page
    $sql  = "SELECT * FROM `{$dbUsers}` ";
    $sql .= "WHERE status!=".USERSTATUS_ACCOUNT_DISABLED;
    $sql .= " AND status!=".USERSTATUS_IN_OFFICE." ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    echo "<table align='center' width='450'>";
    echo "<tr><th align='right'>{$strWhosAwayToday}</th></tr>\n";
    if (mysql_num_rows($result) >=1)
    {
        while ($users = mysql_fetch_object($result))
        {
            echo "<tr><td class='shade2'>";
            $title = userstatus_name($users->status);
            $title .= " - ";
            if ($users->accepting == 'Yes') $title .= "{$GLOBALS['strAcceptingIncidents']}";
            else $title .= "{$GLOBALS['strNotAcceptingIncidents']}";
            if (!empty($users->message)) $title.= "\n({$users->message})";

            echo "<strong>{$users->realname}</strong>, $title";
            echo "</td></tr>\n";
        }
    }
    else echo "<tr class='shade2'><td><em>{$strNobody}</em></td></tr>\n";
    echo "</table>";
}
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>