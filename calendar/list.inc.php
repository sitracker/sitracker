<?php
// list.inc.php - Displays a list view of the calendar events
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
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

echo "<h2>".icon('holiday', 32)." {$strHolidayList}</h2>";
if (empty($type)) $type = 1;
echo appointment_type_dropdown($type, 'list');
echo "<h3>{$strDescendingDateOrder}</h3>";

$sql = "SELECT *, h.id AS holidayid FROM `{$dbHolidays}` AS h, `{$dbUsers}` AS u ";
$sql .= "WHERE h.userid = u.id AND h.type=$type ";
if (!empty($user) AND $user != 'all')
{
    $sql .= "AND u.id='{$user}' ";
}
$sql .= "ORDER BY date DESC";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
if (mysql_num_rows($result))
{
    echo "<table align='center'>";
    echo "<tr><th>{$GLOBALS['strType']}</th><th>{$GLOBALS['strUser']}</th><th>{$GLOBALS['strDate']}</th><th>{$GLOBALS['strStatus']}</th><th>{$GLOBALS['strOperation']}</th></tr>\n";
    $shade='shade1';
    while ($dates = mysql_fetch_array($result))
    {
        echo "<tr class='$shade'><td>".holiday_type($dates['type'])."</td>";
        echo "<td>{$dates['realname']}</td>";
        echo "<td>".date('l jS F Y', mysql2date($dates['date']));
        if ($dates['length'] == 'am') echo " {$strMorning}";
        if ($dates['length'] == 'pm') echo " {$strAfternoon}";
        echo "</td>";
        echo "<td>";
        if (empty($dates['approvedby'])) echo " <em>{$strNotRequested}</em>";
        else echo "<strong>".holiday_approval_status($dates['approved'])."</strong>";
        if ($dates['approvedby'] > 0 AND $dates['approved'] >= 1) echo " by ".user_realname($dates['approvedby']);
        elseif ($dates['approvedby'] > 0 AND empty($dates['approved'])) echo " of ".user_realname($dates['approvedby']);
        echo "</td>";
        echo "<td>";
        if ($approver == TRUE)
        {
            echo "<a href='holiday_add.php?hid={$dates['holidayid']}&amp;year=";
            echo substr($dates['date'], 0, 4) ."&amp;month=".substr($dates['date'], 5, 2);
            echo "&amp;day=".substr($dates['date'], 8, 2)."&amp;user={$dates['userid']}";
            echo "&amp;type={$dates['type']}&amp;length=0&amp;return=list' ";
            echo "onclick=\"return window.confirm('{$dates['realname']}: ".ldate('l jS F Y', mysql2date($dates['date']));
            echo ": {$strAreYouSureDelete}', true);\">{$strDelete}</a>";
        }
        echo "</td></tr>\n";
        if ($shade=='shade1') $shade='shade2';
        else $shade='shade1';
    }
    echo "</table>";
    if ($approver) echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?display=list&amp;type={$type}&amp;user=all'>{$GLOBALS['strShowAll']}</a></p>";
}
else echo "<p>{$GLOBALS['strNoResults']}</p>";
mysql_free_result($result);

?>
