<?php
// statistics.php - Over view and stats of calls logged - intended for last 24hours
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paulheaney[at]users.sourceforge.net>

$permission = 68; // Manage holidays

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$submit = cleanvar($_REQUEST['submit']);

if (empty($submit))
{
	include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    
    echo "<h2>{$strHolidayUsage}</h2>";
    
    echo "<form action='{$_SERVER['PHP_SELF']}' name='holiday_usage' id='holiday_usage' method='post'>";
    echo "<table class='vertical'>";
    echo "<tr><th>{$strStartDate}:</th>";
    echo "<td><input type='text' name='startdate' id='startdate' size='10' /> ";
    echo date_picker('holiday_usage.startdate');
    echo "</td></tr>\n";
    echo "<tr><th>{$strEndDate}:</th>";
    echo "<td><input type='text' name='enddate' id='enddate' size='10' /> ";
    echo date_picker('holiday_usage.enddate');
    echo "</td></tr>\n";
    
    echo group_user_selector($strGroup, "management", $_SESSION['groupid'], 'checkbox');
    
    echo "<tr><th>{$strOutput}</th>\n";
    echo "<td><select name='output' id='output'><option value='screen'>{$strScreen}</option>\n";
    echo "<option value='csv'>{$strCSVfile}</option></select></td></tr>\n";   
    echo "</table>";
    echo "<p align='center'><input type='submit' name='submit' value='{$strRunReport}' /></p>";
    echo "</form>";
    
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
	$startdate = cleanvar($_REQUEST['startdate']);
    $enddate = cleanvar($_REQUEST['enddate']);
    $output = cleanvar($_REQUEST['output']);
    $users = cleanvar($_POST['users']);

    if (empty($enddate)) $enddate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("y")));
    if (empty($startdate)) $startdate = date("y-m-d", strtotime("{$enddate} - 1 year"));
    
    $sql = "SELECT SUM(if(length = 'day', 1, if(length like '%m', 0.5, 0))) AS count, u.realname, h.type, u.holiday_entitlement  ";
    $sql .= "FROM `{$dbHolidays}` AS h, `{$dbUsers}` AS u ";
    $sql .= "WHERE u.id = h.userid AND h.date >= '{$startdate}' AND h.date <= '{$enddate}' ";
    $usercount = count($users);
    if ($usercount >= 1 AND $_POST['users'] != NULL)
    {
        for ($i = 0; $i < $usercount; $i++)
        {
            // $html .= "{$_POST['inc'][$i]} <br />";
            $gsql .= "u.id = {$users[$i]} ";
            if ($i < ($usercount-1)) $gsql .= " OR ";
        }
        
        $sql .= "AND ({$gsql}) ";
    }
    $sql .= "GROUP BY h.userid, h.type";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
    	while ($obj = mysql_fetch_object($result))
        {
        	$holidays[$obj->realname]['name'] = $obj->realname;
            $holidays[$obj->realname]['entitlement'] = $obj->holiday_entitlement;
            $holidays[$obj->realname][$obj->type] = $obj->count;
        }
        
        $array = "{$strName},{$strHolidayEntitlement},{$strHoliday},{$strCompassionateLeave},{$strAbsentSick},{$strWorkingAway},{$strTraining}\n";
        
        foreach ($holidays AS $h)
        {
        	$array .= "{$h['name']},{$h['entitlement']},{$h[HOL_HOLIDAY]},{$h[HOL_FREE]},{$h[HOL_SICKNESS]},{$h[HOL_WORKING_AWAY]},{$h[HOL_TRAINING]}\n";
        }
        
        if ($output == "screen")
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            echo "<h2>{$strHolidayUsage}</h2>";
            echo create_report($array, 'table');
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        elseif ($output == "csv")
        {
        	echo create_report($array, 'csv');
        }
    }
    else
    {
    	echo "<h2>{$strHolidayUsage}<h2>";
        echo "<p class='warning'>{$strNoRecords}</p>";
    }

    
}

?>