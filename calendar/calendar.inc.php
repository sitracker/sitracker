<?php
// calendar.inc.php
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


if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


// Get list of holiday types
$holidaytype[HOL_HOLIDAY] = $GLOBALS['strHoliday'];
$holidaytype[HOL_SICKNESS] = $GLOBALS['strAbsentSick'];
$holidaytype[HOL_WORKING_AWAY] = $GLOBALS['strWorkingAway'];
$holidaytype[HOL_TRAINING] = $GLOBALS['strTraining'];
$holidaytype[HOL_FREE] = $GLOBALS['strCompassionateLeave'];


/**
    * @author Ivan Lucas
*/
function draw_calendar($nmonth, $nyear)
{
    global $type, $user, $selectedday, $selectedmonth, $selectedyear, $CONFIG;

    // Get the current date/time for the users timezone
    $timebase = gmmktime() + ($timezone * 3600);

    if (!$nday) $nday = date('d',$timebase);
    if (!$nmonth) $nmonth = date('m',$timebase);
    if (!$nyear) $nyear = date('Y',$timebase);

    # get the first day of the week!
    $firstday = date('w',mktime(0,0,0,$nmonth,1,$nyear));

    # have to perform a loop to test from 31 backwards using this
    # to see which is the last day of the month
    $lastday = 31;
    do
    {
        # This should probably be recursed, but it works as it is
        $monthOrig = date('m',mktime(0,0,0,$nmonth,1,$nyear));
        $monthTest = date('m',mktime(0,0,0,$nmonth,$lastday,$nyear));
        if ($monthTest != $monthOrig) { $lastday -= 1; }
    }
    while ($monthTest != $monthOrig);
    $monthName = ldate('F',gmmktime(0,0,0,$nmonth,1,$nyear));

    if ($CONFIG['debug'])
    {
        debug_log("first day of the first week of $nmonth $nyear is $firstday (from 0 to 6)");
        debug_log("The last day of $nmonth $nyear is $lastday");
    }
    $days[0] = $GLOBALS['strSun'];
    $days[1] = $GLOBALS['strMon'];
    $days[2] = $GLOBALS['strTue'];
    $days[3] = $GLOBALS['strWed'];
    $days[4] = $GLOBALS['strThu'];
    $days[5] = $GLOBALS['strFri'];
    $days[6] = $GLOBALS['strSat'];

    $dayRow = 0;
    echo "\n<table summary='{$monthName} {$nyear}'>";

    /* Make navigation control for months */
    if ($nmonth >= 1)
    {
        $prevmonth = $nmonth-1;
        $prevyear = $nyear;
        $nextmonth = $nmonth+1;
    }
    if ($nmonth == 1)
    {
        $prevmonth = 12;
        $prevyear = $nyear-1;
    }
    if ($nmonth < 12)
    {
        // $nextmonth=nmonth+1;
        $nextyear=$nyear;
    }
    if ($nmonth == 12)
    {
        $nextmonth = 1;
        $nextyear = $nyear + 1;
    }
    echo "<tr><th colspan='7'>";
    //       echo "<small><a href=\"blank.php?nmonth=".date('m',$timebase)."&nyear=".date('Y',$timebase)."&nday=".date('d',$timebase)."&sid=$sid\" title=\"jump to today\">".date('D jS M Y')."</a></small><br /> ";
    //       echo "<a href=\"blank.php?nmonth=$prevmonth&nyear=$prevyear&sid=$sid\" title=\"Previous Month\"><img src=\"images/arrow_left.gif\" height=\"9\" width=\"6\" border=\"0\"></a>&nbsp;";
    /* Print Current Month */
    echo "<a href='{$_SERVER['PHP_SELF']}?display=month&amp;year={$nyear}&amp;month=$nmonth'>{$monthName} {$nyear}</a>";
    //    echo "&nbsp;<a href=\"blank.php?nmonth=$nextmonth&amp;nyear=$nextyear&amp;sid=$sid\" title=\"Next Month\"><img src=\"images/arrow_right.gif\" height=\"9\" width=\"6\" border=\"0\" /></a>";
    echo "</th></tr>\n";
    echo "<tr>\n";
    for($i=0; $i<=6; $i++)
    {
        echo"<td ";
        if ($i==0 || $i==6) echo "class='expired'"; // Weekend
        else echo "class='shade1'";
        echo ">{$days[$i]}</td>";
    }
    echo "</tr>\n";

    echo "<tr>\n";
    while($dayRow < $firstday)
    {
        echo "<td><!-- This day in last month --></td>";
        $dayRow += 1;
    }
    $day = 0;
    while($day < $lastday)
    {
        if (($dayRow % 7) == 0 AND $dayRow >0) echo "</tr>\n<tr>\n";
        $adjusted_day = $day+1;
        $bold= '';
        $notbold= '';
        // Colour Today in Red
        if ($adjusted_day==date('d') && $nmonth==date('m') && $nyear==date('Y'))
        {
            $bold="<span style='color: red'>";
            $notbold="</span>";
        }
        if (mb_strlen($adjusted_day)==1)  // adjust for days with only one digit
        {
            $calday="0$adjusted_day";
        }
        else
        {
            $calday=$adjusted_day;
        }
        if (mb_strlen($nmonth)==1)  // adjust for months with only one digit
        {
            $nmonth="0$nmonth";
        }
        else
        {
            $nmonth=$nmonth;
        }

        $rowcount=0;
        if ($rowcount>0)
        {
            $calnicedate=date( "l jS F Y", mktime(0,0,0,$nmonth,$calday,$nyear) );
            echo "<td id=\"id$calday\" class=\"calendar\"><a href=\"daymessages.php?month=$nmonth&amp;day=$calday&amp;year=$nyear&amp;sid=$sid\" title=\"$rowcount messages\">{$bold}{$adjusted_day}{$notbold}</a></td>";
        }
        else
        {
            if ($dayRow % 7 == 0 || $dayRow % 7 == 6)
            {
                echo "<td class='expired'>";
                echo "$adjusted_day</td>";
            }
            else
            {
                /////////////////////////////////
                // colors and shading
                $halfday= '';
                $style='';

                // Get the holiday information for a single day
                list($dtype, $dlength, $approved, $approvedby) = user_holiday($user, $type, $nyear, $nmonth, $calday, false);

                if ($dlength=='pm')
                {
                    $halfday = "style=\"background-image: url(images/halfday-pm.gif); background-repeat: no-repeat;\" ";
                    $style="background-image: url(images/halfday-pm.gif); background-repeat: no-repeat; ";
                }
                if ($dlength=='am')
                {
                    $halfday = "style=\"background-image: url(images/halfday-am.gif); background-position: bottom right; background-repeat: no-repeat;\" ";
                    $style="background-image: url(images/halfday-am.gif); background-position: bottom right; background-repeat: no-repeat;";
                }
                if ($calday==$selectedday && $selectedmonth==$nmonth && $selectedyear==$nyear)
                {
                    // consider a border color to indicate the selected cell
                    $style.="border: 1px red dashed; ";
                    // $shade="critical";
                }


                // idle = green
                // critical = red
                // urgent = pink
                // expired = grey
                // mainshade = white
                switch ($dtype)
                {
                    case 1:
                        $shade= "mainshade";
                        if ($approved==1) { $shade='idle';  }
                        if ($approved==2) $shade='urgent';
                    break;

                    case 2:
                        $shade= "mainshade";
                        if ($approved==1) { $shade='idle';  }
                        if ($approved==2) $shade='urgent';
                    break;

                    case 3:
                        $shade= "mainshade";
                        if ($approved==1) { $shade='idle';  }
                        if ($approved==2) $shade='urgent';
                    break;

                    case 4:
                        $shade= "mainshade";
                        if ($approved==1) { $shade='idle';  }
                        if ($approved==2) $shade='urgent';
                    break;

                    case 5:
                        $shade= "mainshade";
                        if ($approved==1) { $shade='idle'; $style="border: 1px dotted magenta; "; }
                        if ($approved==2) $shade='urgent';
                    break;

                    case 10: // public holidays
                        $style="background: #D6D6D6;";
                        $shade='shade1';
                    break;

                    default:
                        $shade="shade2";
                    break;
                }
                if ($dtype==1 || $dtype=='' || $dtype==5 || $dtype==3 || $dtype==2 || $dtype==4)
                {
                    echo "<td class=\"$shade\" style=\"width: 15px; $style\">";
                    echo "<a href=\"holiday_new.php?type=$type&amp;user=$user&amp;year=$nyear&amp;month=$nmonth&amp;day=$calday\"  title=\"$celltitle\">$bold$adjusted_day$notbold</a></td>";
                }
                elseif ($dtype==10)
                {
                    echo "<td class=\"$shade\" style=\"width: 15px; $style\">";
                    echo "<a href=\"holiday_new.php?type=0&amp;user=$user&amp;year=$nyear&amp;month=$nmonth&amp;day=$calday\"  title=\"$celltitle\">$bold$adjusted_day$notbold</a></td>";
                }
                else
                {
                    echo "<td class=\"$shade\" style=\"width:15px; $style\">{$bold}{$adjusted_day}{$notbold}</td>";
                }
            }
        }
        $day += 1;
        $dayRow += 1;
    }
    echo "\n</tr>\n</table>\n";
}


/**
    * @author Ivan Lucas
*/
function appointment_popup($mode, $year, $month, $day, $time, $group, $user)
{
    global $sit, $approver;
    $html = '';
    if ($user==$sit[2] OR $approver==TRUE)
    {
        // Note: this first div is closed inline
        $html .= "<div class='appointment' onclick=\"appointment('app{$user}{$year}{$month}{$day}{$time}');\">";
        $html .= "<div id='app{$user}{$year}{$month}{$day}{$time}' class='appointmentdata'>";
        $html .= "<h2><a href=\"javascript:void(0);\">[X]</a> {$year}-{$month}-{$day} {$time}</h2>";
        if ($mode == 'book')
        {
            $html .= "<a href='holiday_new.php?type=1&amp;user={$user}&amp;year={$year}&amp;month={$month}&amp;day={$day}&amp;length={$time}'>{$GLOBALS['strBookHoliday']}</a><br />";
        }
//         else $html .= "<a href=''>Cancel Holiday</a><br />";
//          TODO: Add the ability to cancel holiday from the holiday planner
        $html .= "</div>";
    }
    return $html;
}


/**
    * Draw a month view Holiday planner chart
    * @author Ivan Lucas
    * @param string $mode. modes: 'month', 'week', 'day'
    * @param int $year. Year e.g. 2009
    * @param int $month. Month number
    * @param int $day.  Day number
    * @param int $groupid.
    * @param int $userid
*/
function draw_chart($mode, $year, $month='', $day='', $groupid='', $userid='')
{
    global $plugin_calendar, $sit, $holidaytype, $startofsession;
    if (empty($day)) $day = date('d');

    if ($mode == 'month')
    {
        $day = 1;
        $daysinmonth = date('t',mktime(0, 0, 0, $month, $day, $year));
        $lastday = $daysinmonth;
        $daywidth = 1;
    }
    elseif ($mode == 'week')
    {
        $daysinmonth = 7;
        $lastday = ($day + $daysinmonth)-1;
        $daywidth = 3;
    }
    elseif ($mode == 'day')
    {
        $daysinmonth = 1;
        $lastday = $day;
        $daywidth = 25;
    }
    else
    {
        $daysinmonth = date('t',mktime(0, 0, 0, $month, $day, $year));
        $lastday = $daysinmonth;
        $daywidth = 1;
    }

    $startdate = mktime(0, 0, 0, $month, $day, $year);
    $enddate  = mktime(23, 59, 59, $month, $lastday, $year);

    // Get list of user groups
    $gsql = "SELECT * FROM `{$GLOBALS['dbGroups']}` ORDER BY name";
    $gresult = mysql_query($gsql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $grouparr[0] = $GLOBALS['strNone'];
    while ($group = mysql_fetch_object($gresult))
    {
        $grouparr[$group->id] = $group->name;
    }
    $numgroups = count($grouparr);

    $html .= "<table align='center' border='1' cellpadding='0' cellspacing='0' style='border-collapse:collapse; border-color: #AAA; width: 99%;'>";
    $usql  = "SELECT * FROM `{$GLOBALS['dbUsers']}` WHERE status != ".USERSTATUS_ACCOUNT_DISABLED." ";
    if ($groupid == 'allonline')
    {
        $usql .= "AND lastseen > $startofsession ";
    }
    if (is_numeric($groupid))
    {
        $usql .= "AND groupid = {$groupid} ";
    }
    elseif ($numgroups > 1)
    {
        $usql .= "AND groupid > 0 ";  // there is always 1 group (ie. 'none')
    }

    if (!empty($user))
    {
        $usql .= "AND id={$user} ";
    }

    $usql .= "ORDER BY groupid, realname";
    $uresult = mysql_query($usql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $numusers = mysql_num_rows($uresult);
    $prevgroupid = '-1';
    if ($numusers > 0)
    {
        $hdays = array();
        while ($user = mysql_fetch_object($uresult))
        {
            unset($hdays);

            $hsql = "SELECT *, UNIX_TIMESTAMP(date) AS startdate FROM `{$GLOBALS['dbHolidays']}` WHERE userid={$user->id} AND date BETWEEN '".date('Y-m-d',$startdate)."' AND '".date('Y-m-d',$enddate)."'";
            $hsql .= "AND type != ".HOL_PUBLIC;

            $hresult = mysql_query($hsql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            while ($holiday = mysql_fetch_object($hresult))
            {
                $cday = date('j',mysql2date($holiday->date));
                $hdays[$cday] = $holiday->length;
                $htypes[$cday] = $holiday->type;
                $happroved[$cday] = $holiday->approved;
            }
            // Public holidays
            $phsql = "SELECT * FROM `{$GLOBALS['dbHolidays']}` WHERE type=".HOL_PUBLIC." AND date BETWEEN '".date('Y-m-d',$startdate)."' AND '".date('Y-m-d',$enddate)."'";
            $phresult = mysql_query($phsql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            while ($pubhol = mysql_fetch_object($phresult))
            {
                $cday = date('j',mysql2date($pubhol->date));
                $pubholdays[$cday] = $pubhol->length;
            }

            if ($prevgroupid != $user->groupid)
            {
                if ($user->groupid == '') $user->groupid = 0;
                $html .= "<tr>";
                $html .= "<td align='left' colspan='2' class='shade2'>{$GLOBALS['strGroup']}: <strong>{$grouparr[$user->groupid]}</strong></td>";
                for($cday = $day; $cday <= $lastday; $cday++)
                {
                    $shade = 'shade1';
                    if (date('D', mktime(0, 0, 0, $month, $cday, $year)) == 'Sat')
                    {
                        $shade = 'expired';
                        $html .= "<td class='$shade' style='text-align: center; font-size: 80%; border-left: 1px solid black;'>";
                        $html .= "<strong title='Week Number' >wk<br />";
                        $html .= mb_substr(date('W',mktime(0, 0, 0, $month, $cday, $year))+1, 0, 1, 'UTF-8');
                        $html .= mb_substr(date('W',mktime(0, 0, 0, $month, $cday, $year))+1,1, 1, 'UTF-8')."</strong></td>";
                    }
                    elseif (date('D', mktime(0, 0, 0, $month, $cday, $year)) == 'Sun')
                    {
                        $html .= '';  // nothing
                    }
                    else
                    {
                        $html .= "<td align='center' class=\"$shade\"";
                        if (mktime(0, 0, 0, $month, $cday, $year) == mktime(0, 0, 0, date('m'), date('d'), date('Y')))
                        {
                            $html .= " style='background: #FFFF00;' title='Today'";
                        }

                        $html .= ">";
                        $html .= mb_substr(ldate('l', gmmktime(0, 0, 0, $month, $cday, $year)), 0, $daywidth, 'UTF-8')."<br />";
                        if ($mode == 'day')
                        {
                            $html .= ldate('dS F Y', gmmktime(0, 0, 0, $month, $cday, $year));
                        }
                        else
                        {
                            $html .= "<a href='{$_SERVER['PHP_SELF']}?display=day&amp;year={$year}&amp;month={$month}&amp;day={$cday}'>".date('d',mktime(0,0,0,$month,$cday,$year))."</a>" ;
                        }
                        $html .= "</td>";
                    }
                }
                $html .= "</tr>\n";
            }
            $prevgroupid = $user->groupid;


            $html .= "<tr><th rowspan='2' style='width: 10%'>{$user->realname}</th>";
            // AM
            $html .= "<td style='width: 2%'>{$GLOBALS['strAM']}</td>";
            for($cday = $day; $cday <= $lastday; $cday++)
            {
                $shade = 'shade1';
                if ((date('D', mktime(0, 0, 0, $month, $cday, $year)) == 'Sat'
                        OR date('D', mktime(0, 0, 0, $month, $cday, $year)) == 'Sun'))
                {
                    // Add  day on for a weekend
                    if ($weekend == FALSE) $displaydays += 1;
                    $weekend = TRUE;
                }
                if (date('D', mktime(0, 0, 0, $month, $cday, $year)) == 'Sat')
                {
                    $html .= "<td class='expired'>&nbsp;</td>";
                }
                elseif (date('D', mktime(0, 0, 0, $month, $cday, $year)) == 'Sun')
                {
                    // Do nothing on sundays
                }
                else
                {
                    $weekend = FALSE;
                    if ($hdays[$cday] == 'am' OR $hdays[$cday] == 'day')
                    {
                        if ($happroved[$cday] == HOL_APPROVAL_NONE
                            OR $happroved[$cday] == HOL_APPROVAL_NONE_ARCHIVED)
                        {
                            $html .= "<td class='review'>";  // Waiting approval
                        }
                        elseif ($htypes[$cday] <= 4
                                AND ($happroved[$cday] == HOL_APPROVAL_GRANTED
                                OR $happroved[$cday] == HOL_APPROVAL_GRANTED_ARCHIVED))
                        {
                            $html .= "<td class='idle'>"; // Approved
                        }
                        elseif ($htypes[$cday] <= 5
                                AND ($happroved[$cday] == HOL_APPROVAL_DENIED
                                OR $happroved[$cday] == HOL_APPROVAL_DENIED_ARCHIVED))
                        {
                            $html .= "<td class='urgent'>"; // Denied
                        }
                        elseif ($htypes[$cday] == HOL_FREE
                                AND ($happroved[$cday] == HOL_APPROVAL_GRANTED
                                OR $happroved[$cday]== HOL_APPROVAL_GRANTED_ARCHIVED))
                        {
                            $html .= "<td class='notice'>"; // Approved Free
                        }
                        else
                        {
                            $html .= "<td class='shade2'>";
                        }

                        if ($user->id == $sit[2])
                        {
                            $html .= appointment_popup('cancel', $year, $month, $cday, 'am', $group, $user->id);
                        }

                        $html .= "<span title='{$holidaytype[$htypes[$cday]]}'>";
                        $html .= mb_substr($holidaytype[$htypes[$cday]], 0, $daywidth, 'UTF-8')."</span>";
                        // This plugin function takes an optional param with an associative array containing the day
                        $pluginparams = array('plugin_calendar' => $plugin_calendar,
                                              'year'=> $year,
                                              'month'=> $month,
                                              'day'=> $cday,
                                              'useremail' => $user->email);
                        $html .= plugin_do('holiday_chart_day_am',$pluginparams);
                        if ($user->id == $sit[2]) $html .= "</div>";
                        $html .= "</td>";
                    }
                    else
                    {
                        if ($pubholdays[$cday] == 'am' OR $pubholdays[$cday] == 'day')
                        {
                            $html .= "<td class='expired'>PH</td>";
                        }
                        else
                        {
                            $html .= "<td class='shade2'>";
                            if ($user->id == $sit[2])
                            {
                                $html .= appointment_popup('book', $year, $month, $cday, 'am', $group, $user->id);
                            }
                            $html .= '&nbsp;';
                            // This plugin function takes an optional param with an associative array containing the day
                            $pluginparams = array('plugin_calendar' => $plugin_calendar,
                                              'year'=> $year,
                                              'month'=> $month,
                                              'day'=> $cday,
                                              'useremail' => $user->email);
                            $html .= plugin_do('holiday_chart_day_am',$pluginparams);
                            if ($user->id == $sit[2]) $html .= "</div>";
                            $html .= "</td>";
                        }
                    }
                }
            }
            $html .= "</tr>\n";
            // PM
            $html .= "<tr><td>{$GLOBALS['strPM']}</td>";
            for ($cday = $day; $cday <= $lastday; $cday++)
            {
                $shade='shade1';
                if ((date('D',mktime(0, 0, 0, $month, $cday, $year)) == 'Sat' OR date('D', mktime(0, 0, 0, $month, $cday, $year)) == 'Sun'))
                {
                    // Add  day on for a weekend
                    if ($weekend == FALSE) $displaydays += 1;
                    $weekend = TRUE;
                }
                if (date('D',mktime(0, 0, 0, $month, $cday, $year)) == 'Sat')
                {
                    $html .= "<td class='expired'>&nbsp;</td>";
                }
                elseif (date('D',mktime(0, 0, 0, $month, $cday, $year)) == 'Sun')
                {
                    // Do nothing on sundays
                }
                else
                {
                    $weekend = FALSE;
                    if ($hdays[$cday] == 'pm' OR $hdays[$cday] == 'day')
                    {
                        if ($happroved[$cday] == HOL_APPROVAL_NONE
                            OR $happroved[$cday] == HOL_APPROVAL_NONE_ARCHIVED)
                        {
                            $html .= "<td class='review'>";  // Waiting approval
                        }
                        elseif ($htypes[$cday] <= 4
                                AND ($happroved[$cday] == HOL_APPROVAL_GRANTED
                                OR $happroved[$cday] == HOL_APPROVAL_GRANTED_ARCHIVED))
                        {
                            $html .= "<td class='idle'>"; // Approved
                        }
                        elseif ($htypes[$cday] <= 5
                                AND ($happroved[$cday] == HOL_APPROVAL_DENIED
                                OR $happroved[$cday] == HOL_APPROVAL_DENIED_ARCHIVED))
                        {
                            $html .= "<td class='urgent'>"; // Denied
                        }
                        elseif ($htypes[$cday] == HOL_FREE
                                AND ($happroved[$cday] == HOL_APPROVAL_GRANTED
                                OR $happroved[$cday] == HOL_APPROVAL_GRANTED_ARCHIVED))
                        {
                            $html .= "<td class='notice'>"; // Approved Free
                        }
                        else
                        {
                            $html .= "<td class='shade2'>";
                        }

                        if ($user->id == $sit[2])
                        {
                            $html .= appointment_popup('cancel', $year, $month, $cday, 'pm', $group, $user->id);
                        }

                        $html .= "<span title='{$holidaytype[$htypes[$cday]]}'>".mb_substr($holidaytype[$htypes[$cday]], 0, $daywidth, 'UTF-8')."</span>";
                        // This plugin function takes an optional param with an associative array containing the day
                        $pluginparams = array('plugin_calendar' => $plugin_calendar,
                                              'year'=> $year,
                                              'month'=> $month,
                                              'day'=> $cday,
                                              'useremail' => $user->email);
                        $html .= plugin_do('holiday_chart_day_pm',$pluginparams);
                        if ($user->id == $sit[2]) $html .= "</div>";
                        $html .= "</td>";
                    }
                    else
                    {
                        if ($pubholdays[$cday] == 'pm' OR $pubholdays[$cday] == 'day')
                        {
                            $html .= "<td class='expired'>PH</td>";
                        }
                        else
                        {
                            $html .= "<td class='shade2'>";
                            if ($user->id == $sit[2])
                            {
                                $html .= appointment_popup('book', $year, $month, $cday, 'pm', $group, $user->id);
                            }
                            $html .= '&nbsp;';
                            // This plugin function takes an optional param with an associative array containing the day
                            $pluginparams = array('plugin_calendar' => $plugin_calendar,
                                              'year'=> $year,
                                              'month'=> $month,
                                              'day'=> $cday,
                                              'useremail' => $user->email);
                            $html .= plugin_do('holiday_chart_day_pm',$pluginparams);
                            if ($user->id == $sit[2])  $html .= "</div>";
                            $html .= "</td>";
                        }
                    }
                }
            }
            $html .= "</tr>\n";
            $html .= "<tr><td colspan='0'></td></tr>\n";
        }
    }
    else
    {
        if ($numgroups < 1) $html .= user_alert($GLOBALS['strNothingToDisplay'], E_USER_NOTICE);
        else $html .= user_alert("{$GLOBALS['strNothingToDisplay']}, {$strCheckUserGroupMembership}", E_USER_NOTICE);
    }
    $html .= "</table>\n\n";

    // Legend
    if ($_SESSION['userconfig']['show_table_legends'] == 'TRUE')
    {
        $html .= "<table class='legend'><tr><td><strong>{$GLOBALS['strKey']}</strong>:</td>";
        foreach ($GLOBALS['holidaytype'] AS $htype)
        {
            $html .= "<td>".mb_substr($htype,0,1)." = {$htype}</td>";
        }
        $html .= "<td>PH = {$GLOBALS['strPublicHoliday']}</td>";
        $html .= "</tr>";
        $html .= "<tr><td></td><td class='urgent'>{$GLOBALS['strDeclined']}</td>";
        $html .= "<td class='review'>{$GLOBALS['strNotApproved']}</td>";
        $html .= "<td class='idle'>{$GLOBALS['strApproved']}</td>";
        $html .= "<td class='notice'>{$GLOBALS['strApprovedFree']}</td></tr>";
        $html .= "</table>\n\n";
    }
    return $html;
}

function month_select($month, $year, $params = '')
{
    $cyear = $year;
    $cmonth = $month - 3;
    if ($cmonth < 1)
    {
        $cmonth += 12; $cyear --;
    }
    $html = "<p align='center'>";
    $pmonth = $cmonth-5;
    $pyear = $cyear-1;
    $nyear = $cyear+1;
    $html .= "<a href='{$SERVER['PHP_SELF']}?display=month&amp;month={$month}";
    $html .= "&amp;year={$pyear}$params' title='Back one year'>&lt;&lt;</a> ";
    for ($c = 1; $c <= 12; $c++)
    {
        if (gmmktime(0,0,0,$cmonth,1,$cyear) == gmmktime(0,0,0,date('m'),1,date('Y')))
        {
            $html .= "<span class='calnavcurrent' style='background: #FF0;'>";
        }

        // Current month
        if (gmmktime(0,0,0,$cmonth,1,$cyear) == gmmktime(0,0,0,$month,1,$year))
        {
            $html .= "<span class='calnavselected' style='font-size: 160%'>";
        }

        $html .= "<a href='{$SERVER['PHP_SELF']}?display=month&amp;month=$cmonth&amp;year=$cyear$params'>";
        $html .= ldate('M y',gmmktime(0, 0, 0, $cmonth, 1, $cyear))."</a>";
        if (gmmktime(0, 0, 0, $cmonth, 1, $cyear) == gmmktime(0, 0, 0, $month, 1, $year))
        {
            $html .= "</span>";
        }

        if (gmmktime(0, 0, 0, $cmonth, 1, $cyear) == gmmktime(0, 0, 0, date('m'), 1, date('Y')))
        {
            $html .= "</span>";
        }

        if ($c < 12)
        {
            $html .= " <span style='color: #666;'>|</span> ";
        }

        $cmonth++;
        if ($cmonth > 12)
        {
            $cmonth -= 12;
            $cyear ++;
        }
    }
    $html .= " <a href='{$SERVER['PHP_SELF']}?month=display=month&amp;{$month}&amp;year={$nyear}$params' title='Forward one year'>&gt;&gt;</a>";
    $html .= "</p>";
    return $html;
}


function appointment_type_dropdown($type, $display)
{
    global $holidaytype;

    $html  = "<form action='{$_SERVER['PHP_SELF']}' style='text-align: center;'>";
    $html .= $GLOBALS['strType'];
    $html .= ": <select class='dropdown' name='type' onchange='window.location.href=this.options[this.selectedIndex].value'>\n";
    foreach ($holidaytype AS $htypeid => $htype)
    {
        $html .= "<option value='{$_SERVER['PHP_SELF']}?display={$display}&amp;type={$htypeid}'";
        if ($type == $htypeid) $html .= " selected='selected'";
        $html .= ">{$htype}</option>\n";
    }
    $html .= "</select></form>";
    return $html;
}


function get_users_appointments($user, $start, $end)
{
    global $holidaytype, $CONFIG;
    $items = array();
    $sql = "SELECT * FROM `{$GLOBALS['dbTasks']}` WHERE startdate >= '";
    $sql.= date("Y-m-d H:i:s", $start);
    $sql.= "' AND enddate < '";
    $sql.= date("Y-m-d H:i:s", $end);
    $sql.= "'AND (distribution = 'event' OR distribution = 'incident') AND owner = '{$user}'";
    $res = mysql_query($sql);
    echo mysql_error();
    while($inf = mysql_fetch_object($res))
    {
        if ($inf->distribution == 'event')
        {
            switch ($inf->completion)
            {
                case '2':
                    $bgcolor = '#FFDDFF';
                break;

                case '1':
                    $bgcolor = '#FFFFBB';
                break;

                default:
                    $bgcolor = '#FFFFFF';
                break;
            }
        }
        else
        {
            $inf->completion = 2;
            $bgcolor = '#FFDDFF';
        }

        $items[] = array ('id' => $inf->id,
                         'description' => $inf->description,
                         'owner' => $inf->owner,
                         'completion' => $inf->completion,
                         'eventStartDate' => gmdate('D, d M Y H:i:s', strtotime($inf->startdate)) . ' GMT',
                         'eventEndDate' => gmdate('D, d M Y H:i:s', strtotime($inf->enddate)) . ' GMT',
                         'bgColorCode' => $bgcolor);
    }

    $sql = "SELECT UNIX_TIMESTAMP(date) type, userid FROM `{$GLOBALS['dbHolidays']}` WHERE UNIX_TIMESTAMP(date) >= '$start' AND UNIX_TIMESTAMP(date) < '{$end}' AND userid = '{$user}'";
    $res = mysql_query($sql);
    echo mysql_error();

    while($inf = mysql_fetch_object($res))
    {
        switch ($inf->length)
        {
            case 'am':
                $startdate = $inf->date + $CONFIG['start_working_day'];
                $enddate = $inf->date + ($CONFIG['start_working_day'] + $CONFIG['end_working_day']) / 2;
                break;

            case 'pm':
                $startdate = $inf->date + ($CONFIG['start_working_day'] + $CONFIG['end_working_day']) / 2;
                $enddate = $inf->date + $CONFIG['end_working_day'];
                break;

            default:
                $startdate = $inf->date + $CONFIG['start_working_day'];
                $enddate = $inf->date + $CONFIG['end_working_day'];
            	break;
        }

        switch ($inf->type)
        {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
                $description = $holidaytype[$inf->type];
                $bgcolor = '#DDFFDD';
                break;

            case 10:
                $description = $GLOBALS['strPublicHoliday'];
                $bgcolor = '#ADADAD';
                break;

            default:
                $description = $GLOBALS['strUnknown'];
                $bgcolor = '#ADADAD';
                break;
        }

        $items[] = array (
            'id' => $inf->id,
            'description' => $description,
            'owner' => $inf->userid,
            'completion' => '2',
            'eventStartDate' => gmdate('D, d M Y H:i:s', $startdate) . ' GMT',
            'eventEndDate' => gmdate('D, d M Y H:i:s', $enddate) . ' GMT',
            'bgColorCode' => $bgcolor
        );
    }
    return $items;
}

function book_appointment($name, $description, $user, $start, $end)
{
    global $dbTasks;
    $sql = "INSERT INTO `{$dbTasks}` (name,description,owner,startdate,enddate,distribution,completion)
            values('" . mysql_real_escape_string($name) . "','" .
            mysql_real_escape_string($description) . "','" .
            $user . "','" .
            date("Y-m-d H:i:s",$start) . "','" .
            date("Y-m-d H:i:s",$end) . "',
            'event',
            '0')";
    mysql_query($sql, $GLOBALS['db']);
    return mysql_insert_id($GLOBALS['db']);
}

function book_days_when_free($name, $description, $user, $startdate, $days, $doit)
{
    global $CONFIG;
    $daysarray = array();
    for ($i = 0; $i < $days; $i ++)
    {
        while (!in_array(date('w', $startdate), $CONFIG['working_days']) || (count(get_users_appointments($user, $startdate, $startdate + 86400)) != 0))
        {
            $startdate += 86400;
        }
        if ($doit)
        {
            book_appointment($name, $description, $user, $startdate + $CONFIG['start_working_day'], $startdate + $CONFIG['end_working_day']);
        }
        $daysarray[] = array('name'=>$name, 'description'=>$description, 'user'=>$user, 'startdate'=>$startdate);
        $startdate += 86400;
    }
    return $daysarray;
}

?>