<?php
// timesheet_approve.php - Show and approve timesheets
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Tom Gerrard <tom.gerrard[at]salfordsoftware.co.uk>

$permission = 50; /* Approve holidays */
require ('..'.DIRECTORY_SEPARATOR.'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');
$title = $strApproveTimesheets;

foreach (array('user', 'date', 'approve' ) as $var)
{
    eval("\$$var=cleanvar(\$_REQUEST['$var']);");
}

if ($user == '')
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('holiday', 32)." ";
    echo $strTimesheets;
    echo "</h2>";
    $usql = "SELECT u.groupid FROM `{$dbUsers}` AS u, `{$dbGroups}` AS g ";
    $usql .= "WHERE u.id = {$sit[2]} AND u.groupid = g.id";
    $uresult = mysql_query($usql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $mygroup = mysql_fetch_object($uresult);
    $sql = "SELECT DISTINCT owner FROM `{$dbTasks}` AS t, `{$dbUsers}` AS u, `{$dbGroups}` AS g ";
    $sql .= "WHERE completion = 1 AND distribution='event' AND ";
    if (mysql_num_rows($uresult) > 0)
    {
    	// User is in a group. only approve there groups
    	$sql .= "u.groupid = {$mygroup->groupid} AND ";
    }
    $sql .= "u.id = t.owner ORDER BY owner";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        echo "<table align='center'>";
        echo "<tr>";
        echo "<th>{$strName}</th>";
        echo "<th>{$strDate}</th>";
        echo "</tr>";
        while ($owner = mysql_fetch_object($result))
        {
            echo "<tr class='shade2'>";
            echo "<td>";
            echo user_realname($owner->owner, TRUE);
            echo "</td>";
            $ssql = "SELECT startdate FROM `{$dbTasks}` WHERE completion = 1 AND distribution = 'event' AND owner = {$owner->owner} ORDER BY startdate LIMIT 1";
            $sresult = mysql_query($ssql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            $startdate = mysql_fetch_object($sresult);
            $sd = strtotime($startdate->startdate);
            if (date('w', $sd) != 1)
            {
                $sd = strtotime('last monday', $sd);
            }
            else
            {
                $sd = strtotime('midnight', $sd);
            }
            echo "<td>".date($CONFIG['dateformat_date'], $sd) ."</td>";
            echo "<td>";
            echo "<a href=\"timesheet_approve.php?user={$owner->owner}&amp;date=$sd\">{$strView}</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    else
    {
        echo "<p class='info'>There are currently no timesheets waiting for your approval</p>";
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else if ($approve == '')
{
    include ('calendar.inc.php');
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>$strTimesheet - " . user_realname($user) . "</h2>";
    echo "<p align='center'>" . date($CONFIG['dateformat_date'], $date) . " - " . date($CONFIG['dateformat_date'], $date + 86400 * 6) . "</p>";
    echo "<table align='center'>";
    echo "<tr>";
    echo "<th>{$strDate}</th>";
    echo "<th>{$strActivity}</th>";
    echo "<th>{$strTotal}</th>";
    echo "</tr>";
    foreach (array($strMonday, $strTuesday, $strWednesday, $strThursday, $strFriday, $strSaturday, $strSunday) as $day)
    {
        $daytime = 0;
        $items = get_users_appointments($user, $date, $date + 86400);
        echo "<tr class='shade2'><th>$day</th>";
        echo "<td style='width: 250px;'>";
        $times = array();
        foreach ($items as $item)
        {
            $timediff = strtotime($item['eventEndDate']) - strtotime($item['eventStartDate']);
            $times[$item['description']] += $timediff;
            $daytime += $timediff;
        }
        ksort($times);
        $html = array();

        foreach ($times as $description => $time)
            $html[] = "<strong>$description</strong>: " . format_seconds($time);
        echo implode('<br />', $html);
        echo "</td>";

        echo "<td>";
        if ($daytime > 0) echo format_seconds($daytime);
        echo "</td>";
        $date += 86400;
    }
    echo "</table>";
    echo "<p align = 'center'><a href='{$_SERVER['PHP_SELF']}?user=$user&amp;date=$date&amp;approve=1'>$strApprove</a> | ";
    echo "<a href='{$_SERVER['PHP_SELF']}?user=$user&amp;date=$date&amp;approve=2'>$strDecline</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    if ($approve == 1)
    {
        $newCompletion = 2;
    }
    else
    {
        $newCompletion = 0;
    }
    $sql = "UPDATE `{$dbTasks}` SET completion = {$newCompletion} WHERE distribution = 'event' AND owner = $user ";
    $sql.= "AND UNIX_TIMESTAMP(startdate) >= ($date - 86400 * 7) AND UNIX_TIMESTAMP(startdate) < $date";
    mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    html_redirect($_SERVER['PHP_SELF']);
}

?>
