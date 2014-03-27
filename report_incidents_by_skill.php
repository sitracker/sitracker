<?php
// incidents_by_software.php - List the number of incidents for each software
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Paul Heaney <paul[at]sitracker.org>

// Requested by Tech Support team (26 Spet 06)

// Notes:
//  Counts activate calls within the specified period (i.e. those with a lastupdate time > timespecified)

require ('core.php');
$permission = PERM_REPORT_RUN; // Run Reports
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strIncidentsBySkill;

$mode = clean_fixed_list($_REQUEST['mode'], array('', 'report'));

if (empty($mode))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('reports', 32)." $title</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}' id='incidentsbysoftware' method='post'>";
    echo "<table class='vertical'>";
    echo "<tr><th>{$strStartDate}:</th>";
    echo "<td><input type='text' name='startdate' id='startdate' size='10' /> ";
    echo date_picker('incidentsbysoftware.startdate');
    echo "</td></tr>\n";
    echo "<tr><th>{$strEndDate}:</th>";
    echo "<td><input type='text' name='enddate' id='enddate' size='10' /> ";
    echo date_picker('incidentsbysoftware.enddate');
    echo "</td></tr>\n";
    echo "<tr><th>{$strSkill}</th><td>".skill_drop_down('software', 0)."</td></tr>\n";
    echo "<tr><th>{$strOptions}</th><td><label><input type='checkbox' name='monthbreakdown' /> {$strMonthBreakdown}</label></td></tr>\n";
    echo "</table>\n";
    echo "<p class='formbuttons'>";
    echo "<input type='hidden' name='mode' value='report' />";
    echo "<input type='reset' value=\"{$strReset}\" /> ";
    echo "<input type='submit' value=\"{$strRunReport}\" />";
    echo "</p>";
    echo "</form>\n";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    $monthbreakdownstatus = clean_fixed_list($_REQUEST['monthbreakdown'], array('','on'));
    $startdate = strtotime($_REQUEST['startdate']);
    $enddate = strtotime($_REQUEST['enddate']);
    $software = clean_int($_REQUEST['software']);

    $sql = "SELECT count(s.id) AS softwarecount, s.name, s.id ";
    $sql .= "FROM `{$dbSoftware}` AS s, `{$dbIncidents}` AS i ";
    $sql .= "WHERE s.id = i.softwareid AND i.opened > '{$startdate}' ";
    if (!empty($enddate)) $sql .= "AND i.opened < '{$enddate}' ";
    if (!empty($software)) $sql .= "AND s.id ='{$software}' ";
    $sql .= "GROUP BY s.id ORDER BY softwarecount DESC";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    $countArray[0] = 0;
    $softwareNames[0] = 'Name';
    $softwareID[0] = 0;
    $c = 0;
    $count = 0;
    while ($obj = mysql_fetch_object($result))
    {
        $countArray[$c] = $obj->softwarecount;
        $count += $countArray[$c];
        $softwareNames[$c] = $obj->name;
        $softwareID[$c] = $obj->id;
        $c++;
    }

    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('reports', 32)." {$strIncidentsBySkill}</h2>";

    if (mysql_num_rows($result) > 0)
    {
        $sqlSLA = "SELECT DISTINCT(tag) FROM `{$dbServiceLevels}`";
        $resultSLA = mysql_query($sqlSLA);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        if ($startdate > 1)
        {
            echo "<p align='center'>".sprintf($strSinceX, ldate($CONFIG['dateformat_date'], $startdate))."</p>";
        }
        echo "<table class='vertical' align='center'>";
        echo "<tr><th>{$strNumOfCalls}</th><th>%</th><th>{$strSkill}</th>";
        while ($sla = mysql_fetch_object($resultSLA))
        {
            echo "<th>".$sla->tag."</th>";
            $slas[$sla->tag]['name'] = $sla->tag;
            $slas[$sla->tag]['notEscalated'] = 0;
            $slas[$sla->tag]['escalated'] = 0;
        }
        $emptySLA = $slas;
        echo "<tr>";

        $others = 0;
        $shade = 'shade1';
        for ($i = 0; $i < $c; $i++)
        {
            if ($i <= 25)
            {
                $data .= $countArray[$i]."|";
                $percentage = number_format(($countArray[$i]/$count) * 100,1);
                $legend .= $softwareNames[$i]." ({$percentage}%)|";
            }
            else
            {
                $others += $countArray[$i];
            }

            $sqlN = "SELECT id, servicelevel, opened FROM `{$dbIncidents}` WHERE softwareid = '{$softwareID[$i]}'";
            $sqlN .= " AND opened > '{$startdate}' ORDER BY opened";

            $resultN = mysql_query($sqlN);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            $numrows = mysql_num_rows($resultN);

            foreach ($slas AS $slaReset)
            $slas = $emptySLA;

            if ($numrows > 0)
            {
                unset($monthbreakdown);
                while ($obj = mysql_fetch_object($resultN))
                {
                    $datestr = date("M y",$obj->opened);

                    // MANTIS 811 this sql uses the body to find out which incidents have been escalated
                    $sqlL = "SELECT count(id) FROM `{$dbUpdates}` AS u ";
                    $sqlL .= "WHERE u.bodytext LIKE \"External ID%\" AND incidentid = '{$obj->id}'";
                    $resultL = mysql_query($sqlL);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                    list($numrowsL) = mysql_fetch_row($resultL);

                    if ($numrowsL > 0) $slas[$obj->servicelevel]['escalated']++;
                    else $slas[$obj->servicelevel]['notEscalated']++;

                    $monthbreakdown[$datestr][$obj->servicelevel]++;
                    $monthbreakdown[$datestr]['month']=$datestr;
                }
            }
            echo "<tr class='$shade'><td>{$countArray[$i]}</td>";
            echo "<td>{$percentage}%</td>";
            echo "<td>{$softwareNames[$i]}</td>";

            foreach ($slas AS $sla)
            {
                echo "<td>";
                echo ($sla['notEscalated'] + $sla['escalated'])." / {$sla['escalated']}";
                echo "</td>";
            }

            if ($monthbreakdownstatus === "on")
            {
                echo "<tr class='$shade'><td></td><td colspan='".(count($slas)+2)."'>";
                echo "<table style='width: 100%'><tr>";
                foreach ($monthbreakdown AS $month)
                {
                    echo "<th>{$month['month']}</th>";
                }
                echo "</tr>\n<tr>";
                foreach ($monthbreakdown AS $month)
                {//echo "<pre>".print_r($month)."</pre>";
                    echo "<td><table>";
                    $total = 0;
                    foreach ($slas AS $slaNames)
                    {
                        if (empty($month[$slaNames['name']])) $month[$slaNames['name']] = 0;
                        echo "<tr>";
                        echo "<td>".$slaNames['name']."</td><td>".$month[$slaNames['name']]."</td>";
                        echo "</tr>\n";
                        $total += $month[$slaNames['name']];
                    }
                    echo "<tr><td><strong>{$strTotal}</strong></td><td><strong>";
                    echo $total;
                    echo "</strong></td></tr>\n";
                    $monthtotals[$month['month']]['month'] = $month['month'];
                    $monthtotals[$month['month']]['value'] += $total;
                    $skilltotals[$softwareNames[$i]]['name'] = $softwareNames[$i];
                    $skilltotals[$softwareNames[$i]][$month['month']]['month'] = $month['month'];
                    $skilltotals[$softwareNames[$i]][$month['month']]['numberofincidents'] = $total;

                    $months[date_to_str($month['month'])] = $month['month'];
                    echo "</table></td>";
                }
                echo "</tr></table>";
                echo "</td></tr>\n";
            }
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        echo "</table>";

        if ($monthbreakdownstatus === "on")
        {
            echo "<p><table class='maintable'>";
            echo "<tr><th>{$strMonth}</th><th>{$strNumOfCalls}</th></tr>";
            $shade = 'shade1';

            $total = 0;

            foreach ($monthtotals AS $m)
            {
                echo "<tr class='$shade'>";
                echo "<td>".$m['month']."</td><td align='center'>".$m['value']."</td><tr>";
                $total += $m['value'];
                echo "</tr>";
                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }
            echo "<tfoot><tr><th>{$strTotal}</th><td align='center'><strong>{$total}</strong></td></tr></tfoot>";
            echo "</table>";

            ksort($months);

            //echo "<pre>";
            //print_r($skilltotals);
            //print_r($months);
            //echo "</pre>";

            $shade = "shade1";

            echo "<p><table class='maintable'><tr><td></td>";
            foreach ($months AS $m)
            {
                echo "<th>{$m}</th>";
            }
            echo "<th>{$strTotal}</th></tr>";
            $js_coordCounter = 0;
            $min = 0;
            $max = 0;
            foreach ($skilltotals AS $skill)
            {

                echo "<tr class='{$shade}'><td>{$skill['name']}</td>";
                $sum = 0;
                $counter = 0;
                $coords = '';
                foreach ($months AS $m)
                {
                    $val = $skill[$m]['numberofincidents'];
                    if (empty($val)) $val = 0;
                    echo "<td>{$val}</td>";
                    $sum += $val;

                    if ($val < $min) $min = $val;
                    if ($val > $max) $max = $val;

                    $coords .= "{ x: {$counter}, y: {$val} }, ";
                    $counter++;
                }
                echo "<td>{$sum}</td></tr>";

                $percentage = ($sum / $total) * 100;

                if ($shade == "shade1") $shade = "shade2";
                else $shade = "shade1";

                $clgth = mb_strlen($coords)-2;
                $coords = mb_substr($coords, 0, $clgth);
            }

            $grandsum = 0;

            echo "<th>{$strTotal}</th>";
            foreach ($months AS $m)
            {
                echo "<td>";
                echo $monthtotals[$m]['value'];
                echo "</td>";

                $grandsum += $monthtotals[$m]['value'];
            }

            echo "<td>{$grandsum}</td></table></p>";
        }

        $data .= $others."|";
        $percentage = @number_format(($others/$count) * 100,1);
        $legend .= "Others ($percentage)|";


        echo "</p>";

        if (extension_loaded('gd'))
        {
            $data = mb_substr($data, 0, mb_strlen($data) - 1);
            $legend = mb_substr($legend, 0, mb_strlen($legend) - 1);
            $title = urlencode($strIncidentsBySkill);
            echo "\n<br /><p><div style='text-align:center;'>";
            echo "\n<img src='chart.php?type=pie&data={$data}&legends={$legend}&title={$title}' />";
            echo "\n</div></p>";
        }
    }
    else
    {
        echo user_alert($strNoRecords, E_USER_NOTICE);
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

}

/**
    * @author Paul Heaney
*/
function date_to_str($date)
{
    $s = explode(" ",$date);
    switch ($s[0])
    {
        case 'Jan': return $s[1]."01";
            break;
        case 'Feb': return $s[1]."02";
                    break;
        case 'Mar': return $s[1]."03";
                    break;
        case 'Apr': return $s[1]."04";
                    break;
        case 'May': return $s[1]."05";
                    break;
        case 'Jun': return $s[1]."06";
                    break;
        case 'Jul': return $s[1]."07";
                    break;
        case 'Aug': return $s[1]."08";
                    break;
        case 'Sep': return $s[1]."09";
                    break;
        case 'Oct': return $s[1]."10";
                    break;
        case 'Nov': return $s[1]."11";
                    break;
        case 'Dec': return $s[1]."12";
                    break;
    }
}

?>