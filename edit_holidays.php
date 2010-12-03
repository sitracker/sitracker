<?php
// edit_holidays.php - Reset holiday entitlements
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

$permission = 22; // Administrate
require ('core.php');
require (APPLICATION_LIBPATH.'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$title = $strEditHolidayEntitlement;

switch ($_REQUEST['action'])
{
    case 'save':
        $max_carryover = clean_int($_REQUEST['max_carryover']);
        $archivedate = strtotime($_REQUEST['archivedate']);
        if ($archivedate < 1000) $archivedate = $now;
        $default_entitlement = cleanvar($_REQUEST['default_entitlement']);
        $sql = "SELECT * FROM `{$dbUsers}` WHERE status >= 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        while ($users = mysql_fetch_object($result))
        {
            $fieldname="user{$users->id}";
            if ($_REQUEST[$fieldname] == 'yes')
            {
                $orig_entitlement = user_holiday_entitlement($users->id);
                $used_holidays = user_count_holidays($users->id, 1, $archivedate);
                $remaining_holidays = $orig_entitlement - $used_holidays;
                if ($remaining_holidays < $max_carryover) $carryover = $remaining_holidays;
                else $carryover = $max_carryover;
                $new_entitlement = $default_entitlement + $carryover;

                // Archive previous holiday
                $hsql = "UPDATE `{$dbHolidays}` SET approved = approved+10 ";
                $hsql .= "WHERE approved < 10 AND userid={$users->id} ";
                $hsql .= "AND date < FROM_UNIXTIME({$archivedate})";
                mysql_query($hsql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

                // Update Holiday Entitlement
                $usql = "UPDATE `{$dbUsers}` SET holiday_entitlement = ";
                $usql .= "{$new_entitlement} WHERE id={$users->id} LIMIT 1";
                mysql_query($usql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
            }
        }
        header("Location: edit_holidays.php");
        exit;
        break;

    case 'form':
    default:
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>".icon('holiday', 32)." {$title}</h2>";

        $sql = "SELECT * FROM `{$dbUsers}` WHERE status >= 1 ORDER BY realname ASC";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

        echo "<form name='editholidays' action='{$_SERVER['PHP_SELF']}?action=save' method='post'>";
        echo "<p>{$strResetHolidayEntitlementCarryOverNDaysOfUnusedHoliday}</p>";
        echo "<div align='center'><label>{$strDefaultNewEntitlement}: ";
        echo "<input type='text' name='default_entitlement' value='{$CONFIG['default_entitlement']}' size='4' /></label>, ";
        echo sprintf($strMaxCarryOverXDays, "<input type='text' name='max_carryover' value='5' size='4' />");
        $str = "<input type='text' id='archivedate' name='archivedate' size='10' value='".date('Y-m-d')."' />";
        echo "<br />".sprintf($strArchiveDaysBookedPriorToX, $str)."\n ";
        echo date_picker('editholidays.archivedate');
        echo "</div>";

        echo "<table align='center'>";
        echo "<tr><th></th>";
        echo colheader('realname', $strName, FALSE);
        echo colheader('entitlement', $strEntitlement, FALSE);
        echo colheader('holidaysused', $strUsed, FALSE);
        echo colheader('holidaysremaining', sprintf($strRemaining, ''), FALSE);
        //echo colheader('resetdate', $strResetDate, FALSE);
        //echo colheader('newentitlement', $strNewEntitlement, FALSE);
        echo "</tr>";
        $shade = 'shade1';
        while ($users = mysql_fetch_object($result))
        {
            echo "<tr class='{$shade}'>";
            echo "<td><input type='checkbox' name='user{$users->id}' value='yes' /></td>";
            echo "<td><a href='holidays.php?user={$users->id}'>{$users->realname}</a> ({$users->username})</td>";

            $entitlement = $users->holiday_entitlement;
            $used_holidays = user_count_holidays($users->id, HOL_HOLIDAY, mysql2date($users->holiday_resetdate . ' 17:00:00'), array(HOL_APPROVAL_GRANTED));
            $remaining_holidays = $entitlement - $used_holidays;

            echo "<td style='text-align: right;'>{$entitlement}</td>";
            echo "<td style='text-align: right;'>{$used_holidays}</td>";
            echo "<td style='text-align: right;'>{$remaining_holidays}</td>";
            //echo "<td style='text-align: right;'>{$users->holiday_resetdate}</td>";
            //echo "<td style='text-align: right;'><input type='text' size='4' maxlength='5' value='{$newentitlement}' /></td>";
            echo "</tr>";
            
            if ($shade == 'shade1') $shade = "shade2";
            else $shade = "shade1";
        }
        echo "</table>";
        echo "<p>";
        echo "<input type='hidden' name='action' value='save' />";
        echo "<input type='submit' name='submit' value='{$strSave}' /></p>";
        echo "</form>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        break;
}
?>