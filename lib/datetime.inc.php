<?php
// datetime.inc.php - functions relating to date and time
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

/**
 * Formats a given number of seconds into a readable string showing days, hours and minutes.
 * @author Ivan Lucas
 * @param int $seconds number of seconds
 * @param bool $showseconds bool If TRUE and $seconds is less than 60 the function returns 1 minute.
 * @return string Readable date/time
 */
function format_seconds($seconds, $showseconds = FALSE)
{
    global $str1Year, $str1Hour, $str1Minute, $str1Day, $str1Month, $strXSeconds, $str1Second;
    global $strXHours, $strXMinutes, $strXDays, $strXMonths, $strXYears;

    if ($seconds <= 0)
    {
        return sprintf($strXMinutes, 0);
    }
    elseif ($seconds <= 60 AND $seconds >= 1 AND $showseconds == FALSE)
    {
        return $str1Minute;
    }
    elseif ($seconds < 60 AND $seconds >= 1 AND $showseconds == TRUE)
    {
        if ($seconds == 1)
        {
            return $str1Second;
        }
        else
        {
            return sprintf($strXSeconds, $seconds);
        }
    }
    else
    {
        $years = floor($seconds / ( 2629800 * 12));
        $remainder = ($seconds % ( 2629800 * 12));
        $months = floor($remainder / 2629800);
        $remainder = ($seconds % 2629800);
        $days = floor($remainder / 86400);
        $remainder = ($remainder % 86400);
        $hours = floor($remainder / 3600);
        $remainder = ($remainder % 3600);
        $minutes = floor($remainder / 60);

        $return_string = '';

        if ($years > 0)
        {
            if ($years == 1)
            {
                $return_string .= $str1Year.' ';
            }
            else
            {
                $return_string .= sprintf($strXYears, $years).' ';
            }
        }

        if ($months > 0 AND $years < 2)
        {
            if ($months == 1)
            {
                $return_string .= $str1Month." ";
            }
            else
            {
                $return_string .= sprintf($strXMonths, $months).' ';
            }
        }

        if ($days > 0 AND $months < 6)
        {
            if ($days == 1)
            {
                $return_string .= $str1Day." ";
            }
            else
            {
                $return_string .= sprintf($strXDays, $days)." ";
            }
        }

        if ($months < 1 AND $days < 7 AND $hours > 0)
        {
            if ($hours == 1)
            {
                $return_string .= $str1Hour." ";
            }
            else
            {
                $return_string .= sprintf($strXHours, $hours)." ";
            }
        }
        elseif ($months < 1 AND $days < 1 AND $hours > 0)
        {
            if ($minutes == 1)
            {
                $return_string .= $str1Minute." ";
            }
            elseif ($minutes > 1)
            {
                $return_string .= sprintf($strXMinutes, $minutes)." ";
            }
        }

        if ($months < 1 AND $days < 1 AND $hours < 1)
        {
            if ($minutes <= 1)
            {
                $return_string .= $str1Minute." ";
            }
            else
            {
                $return_string .= sprintf($strXMinutes, $minutes)." ";
            }
        }

        $return_string = trim($return_string);
        if (empty($return_string)) $return_string = "({$seconds})";
        return $return_string;
    }
}


/**
 * Return a string containing the time remaining as working days/hours/minutes (eg. 9am - 5pm)
 * @author Ivan Lucas
 * @return string. Length of working time, in readable days, hours and minutes
 * @note The working day is calculated using the $CONFIG['end_working_day'] and
 * $CONFIG['start_working_day'] config variables
 */
function format_workday_minutes($minutes)
{
    global $CONFIG, $strXMinutes, $str1Minute, $strXHours, $strXHour;
    global $strXWorkingDay, $strXWorkingDays;
    $working_day_mins = ($CONFIG['end_working_day'] - $CONFIG['start_working_day']) / 60;
    $days = floor($minutes / $working_day_mins);
    $remainder = ($minutes % $working_day_mins);
    $hours = floor($remainder / 60);
    $minutes = floor($remainder % 60);

    if ($days == 1)
    {
        $time = sprintf($strXWorkingDay, $days);
    }
    elseif ($days > 1)
    {
        $time = sprintf($strXWorkingDays, $days);
    }

    if ($days <= 3 AND $hours == 1)
    {
        $time .= " ".sprintf($strXHour, $hours);
    }
    elseif ($days <= 3 AND $hours > 1)
    {
        $time .= " ".sprintf($strXHours, $hours);
    }
    elseif ($days > 3 AND $hours >= 1)
    {
        $time = "&gt; ".$time;
    }

    if ($days < 1 AND $hours < 8 AND $minutes == 1)
    {
        $time .= " ".$str1Minute;
    }
    elseif ($days < 1 AND $hours < 8 AND $minutes > 1)
    {
        $time .= " ".sprintf($strXMinutes, $minutes);
    }

    if ($days == 1 AND $hours < 8 AND $minutes == 1)
    {
        $time .= " ".$str1Minute;
    }
    elseif ($days == 1 AND $hours < 8 AND $minutes > 1)
    {
        $time .= " ".sprintf($strXMinutes, $minutes);
    }

    $time = trim($time);

    return $time;
}


/**
 * Make a readable and friendly date, i.e. say Today, or Yesterday if it is
 * @author Ivan Lucas
 * @param int $date a UNIX timestamp
 * @return string. Date in a readable friendly format
 * @note See also readable_date() dupe?
 */
function format_date_friendly($date)
{
    global $CONFIG, $now;
    if (ldate('dmy', $date) == ldate('dmy', time()))
    {
        $datestring = "{$GLOBALS['strToday']} @ ".ldate($CONFIG['dateformat_time'], $date);
    }
    elseif (ldate('dmy', $date) == ldate('dmy', (time() - 86400)))
    {
        $datestring = "{$GLOBALS['strYesterday']} @ ".ldate($CONFIG['dateformat_time'], $date);
    }
    elseif ($date < $now - 86400 AND
            $date > $now - (86400 * 6))
    {
        $datestring = ldate('l', $date)." @ ".ldate($CONFIG['dateformat_time'], $date);
    }
    else
    {
        $datestring = ldate($CONFIG['dateformat_datetime'], $date);
    }

    return ($datestring);
}


/**
 * Converts a MySQL date to a UNIX Timestamp
 * @author Ivan Lucas
 * @param string $mysqldate - A date column from mysql
 * @param bool $utc - TRUE = Timestamp given is UTC
 *                    FALSE = Timestamp as system time
 * @return integer. a UNIX Timestamp
 */
function mysql2date($mysqldate, $utc = FALSE)
{
    // for the zero/blank case, return 0
    if (empty($mysqldate))
    {
        return 0;
    }

    if ($mysqldate == '0000-00-00 00:00:00' OR $mysqldate == '0000-00-00')
    {
        return 0;
    }

    // Takes a MYSQL date and converts it to a proper PHP date
    $day = substr($mysqldate, 8, 2);
    $month = substr($mysqldate, 5, 2);
    $year = substr($mysqldate, 0, 4);

    if (mb_strlen$mysqldate) > 10)
    {
        $hour = substr($mysqldate, 11, 2);
        $minute = substr($mysqldate, 14, 2);
        $second = substr($mysqldate, 17, 2);
        if ($utc) $phpdate = gmmktime($hour, $minute, $second, $month, $day, $year);
        else $phpdate = mktime($hour, $minute, $second, $month, $day, $year);
    }
    else
    {
        if ($utc) $phpdate = gmmktime(0, 0, 0, $month, $day, $year);
        else $phpdate = mktime(0, 0, 0, $month, $day, $year);
    }

    return $phpdate;
}


/**
 * Converts a MySQL timestamp to a UNIX Timestamp
 * @author Ivan Lucas
 * @param string $mysqldate  A timestamp column from mysql
 * @return integer. a UNIX Timestamp
 */
function mysqlts2date($mysqldate)
{
    // for the zero/blank case, return 0
    if (empty($mysqldate)) return 0;

    // Takes a MYSQL date and converts it to a proper PHP date
    if (mb_strlen$mysqldate) == 14)
    {
        $day = substr($mysqldate, 6, 2);
        $month = substr($mysqldate, 4, 2);
        $year = substr($mysqldate, 0, 4);
        $hour = substr($mysqldate, 8, 2);
        $minute = substr($mysqldate, 10, 2);
        $second = substr($mysqldate, 12, 2);
    }
    elseif (mb_strlen$mysqldate) > 14)
    {
        $day = substr($mysqldate, 8, 2);
        $month = substr($mysqldate, 5, 2);
        $year = substr($mysqldate, 0, 4);
        $hour = substr($mysqldate, 11, 2);
        $minute = substr($mysqldate, 14, 2);
        $second = substr($mysqldate, 17, 2);
    }
    $phpdate = mktime($hour, $minute, $second, $month, $day, $year);
    return $phpdate;
}


function iso_8601_date($timestamp)
{
    $date_mod = date('Y-m-d\TH:i:s', $timestamp);
    $pre_timezone = date('O', $timestamp);
    $time_zone = substr($pre_timezone, 0, 3).":".substr($pre_timezone, 3, 2);
    $date_mod .= $time_zone;
    return $date_mod;
}


/**
 * Decide whether the time is during a public holiday
 * @author Paul Heaney
 * @param int $time  Timestamp to identify
 * @param array $publicholidays array of Holiday. Public holiday to compare against
 * @return integer. If > 0 number of seconds left in the public holiday
 */
function is_public_holiday($time, $publicholidays)
{
    if (!empty($publicholidays))
    {
        foreach ($publicholidays AS $holiday)
        {
            if ($time >= $holiday->starttime AND $time <= $holiday->endtime)
            {
                return $holiday->endtime-$time;
            }
        }
    }

    return 0;
}


/**
 * Function to get an array of public holidays
 * @author Paul Heaney
 * @param int $startdate - UNIX Timestamp of start of the period to find public holidays in
 * @param int $enddate - UNIX Timestamp of end of the period to find public holidays in
 * @return array of Holiday
 */
function get_public_holidays($startdate, $enddate)
{
    $sql = "SELECT * FROM `{$GLOBALS['dbHolidays']}` ";
    $sql .= "WHERE type = ".HOL_PUBLIC." AND (`date` >= FROM_UNIXTIME({$startdate}) AND `date` <= FROM_UNIXTIME({$enddate}))";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $publicholidays = array();

    if (mysql_num_rows($result) > 0)
    {
        // Assume public holidays are ALL day
        while ($obj = mysql_fetch_object($result))
        {
            $holiday = new Holiday();
            $holiday->starttime = $obj->date;
            $holiday->endtime = ($obj->date + (60 * 60 * 24));

            $publicholidays[] = $holiday;
        }
    }
    return $publicholidays;
}


/**
 * Takes a UNIX Timestamp and returns a string with a pretty readable date
 * @param int $date
 * @param string $lang. takes either 'user' or 'system' as to which language to use
 * @return string
 */
function readable_date($date, $lang = 'user')
{
    global $SYSLANG, $CONFIG;
    //
    // e.g. Yesterday @ 5:28pm
    if (ldate('dmy', $date) == ldate('dmy', time()))
    {
        if ($lang == 'user')
        {
            $datestring = "{$GLOBALS['strToday']} @ ".ldate($CONFIG['dateformat_time'], $date);
        }
        else
        {
            $datestring = "{$SYSLANG['strToday']} @ ".ldate($CONFIG['dateformat_time'], $date);
        }
    }
    elseif (ldate('dmy', $date) == ldate('dmy', (time()-86400)))
    {
        if ($lang == 'user')
        {
            $datestring = "{$GLOBALS['strYesterday']} @ ".ldate($CONFIG['dateformat_time'], $date);
        }
        else
        {
            $datestring = "{$SYSLANG['strYesterday']} @ ".ldate($CONFIG['dateformat_time'], $date);
        }
    }
    else
    {
        $datestring = ldate($CONFIG['dateformat_longdate'] . ' @ ' . $CONFIG['dateformat_time'], $date);
    }
    return $datestring;
}


/**
 * @author Kieran Hogg
 * @param int $seconds. Number of seconds
 * @return string. Readable time in seconds
 */
function exact_seconds($seconds)
{
    $days = floor($seconds / (24 * 60 * 60));
    $seconds -= $days * (24 * 60 * 60);
    $hours = floor($seconds / (60 * 60));
    $seconds -=  $hours * (60 * 60);
    $minutes = floor($seconds / 60);
    $seconds -= $minutes * 60;

    $string = str_pad($days, 2, '0', STR_PAD_LEFT) . ':' . str_pad($hours, 2, '0', STR_PAD_LEFT) . ':';
    $string .= str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);
    
    return $string;
}


/**
 * Adjust a timezoned date/time to UTC
 * @author Ivan Lucas
 * @param int UNIX timestamp.  Uses 'now' if ommitted
 * @return int UNIX timestamp (in UTC)
 */
function utc_time($time = '')
{
    global $now;
    if ($time == '')
    {
        $time = $now;
    }
    $tz = strftime('%z', $time);
    $tzmins = (substr($tz, -4, 2) * 60) + substr($tz, -2, 2);
    $tzsecs = $tzmins * 60; // convert to seconds
    if (substr($tz, 0, 1) == '+')
    {
        $time -= $tzsecs;
    }
    else
    {
        $time += $tzsecs;
    }
    return $time;
}


/**
 * Returns a localised and translated date.
 * DST Aware
 * @author Ivan Lucas
 * @param string $format. date() format
 * @param int $date.  UNIX timestamp.  Uses 'now' if ommitted
 * @param bool $utc bool. Is the timestamp being passed as UTC or system time
                     TRUE = passed as UTC
                     FALSE = passed as system time
 * @return string. An internationised date/time string
 * @todo  th/st and am/pm maybe?
 */
function ldate($format, $date = '', $utc = FALSE)
{
    global $now, $CONFIG;
    if ($date == '') $date = $GLOBALS['now'];
    if ($_SESSION['userconfig']['utc_offset'] != '')
    {
        if ($utc === FALSE)
        {
            // Adjust the date back to UTC
            $date = utc_time($date);
        }
        // Adjust the display time to the users local timezone
        $useroffsetsec = $_SESSION['userconfig']['utc_offset'] * 60;
        $date += $useroffsetsec;
    }

    // Adjust the display time according to DST
    if ($utc === FALSE AND date('I', $date) > 0)
    {
        $date += $CONFIG['dst_adjust'] * 60; // Add an hour of DST
    }

    $datestring = date($format, $date);

    // Internationalise date endings (e.g. st)
    if (strpos($format, 'S') !== FALSE)
    {
        $endings = array('st', 'nd', 'rd', 'th');
        $i18nendings = array($GLOBALS['strst'], $GLOBALS['strnd'],
                            $GLOBALS['strrd'], $GLOBALS['strth']);
        $datestring = str_replace($endings, $i18nendings, $datestring);
    }


    // Internationalise full day names
    if (strpos($format, 'l') !== FALSE)
    {
        $days = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
        $i18ndays = array($GLOBALS['strMonday'], $GLOBALS['strTuesday'], $GLOBALS['strWednesday'],
                        $GLOBALS['strThursday'], $GLOBALS['strFriday'], $GLOBALS['strSaturday'], $GLOBALS['strSunday']);
        $datestring = str_replace($days, $i18ndays, $datestring);
    }

    // Internationalise abbreviated day names
    if (strpos($format, 'D') !== FALSE)
    {
        $days = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
        $i18ndays = array($GLOBALS['strMon'], $GLOBALS['strTue'], $GLOBALS['strWed'],
                        $GLOBALS['strThu'], $GLOBALS['strFri'], $GLOBALS['strSat'], $GLOBALS['strSun']);
        $datestring = str_replace($days, $i18ndays, $datestring);
    }

    // Internationalise full month names
    if (strpos($format, 'F') !== FALSE)
    {
        $months = array('January','February','March','April','May','June','July','August','September','October','November','December');
        $i18nmonths = array($GLOBALS['strJanuary'], $GLOBALS['strFebruary'], $GLOBALS['strMarch'],
                        $GLOBALS['strApril'], $GLOBALS['strMay'], $GLOBALS['strJune'], $GLOBALS['strJuly'],
                        $GLOBALS['strAugust'], $GLOBALS['strSeptember'], $GLOBALS['strOctober'],
                        $GLOBALS['strNovember'], $GLOBALS['strDecember']);
        $datestring = str_replace($months, $i18nmonths, $datestring);
    }

    // Internationalise short month names
    if (strpos($format, 'M') !== FALSE)
    {
        $months = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
        $i18nmonths = array($GLOBALS['strJanAbbr'], $GLOBALS['strFebAbbr'], $GLOBALS['strMarAbbr'],
                        $GLOBALS['strAprAbbr'], $GLOBALS['strMayAbbr'], $GLOBALS['strJunAbbr'], $GLOBALS['strJulAbbr'],
                        $GLOBALS['strAugAbbr'], $GLOBALS['strSepAbbr'], $GLOBALS['strOctAbbr'],
                        $GLOBALS['strNovAbbr'], $GLOBALS['strDecAbbr']);
        $datestring = str_replace($months, $i18nmonths, $datestring);
    }

    // Internationalise am/pm
    if (strpos($format, 'a') !== FALSE)
    {
        $months = array('am', 'pm');
        $i18nmonths = array($GLOBALS['strAM'], $GLOBALS['strPM']);
        $datestring = str_replace($months, $i18nmonths, $datestring);
    }

    return $datestring;
}


/**
 * Function passed a day, month and year to identify if this day is defined as a public holiday
 * @author Paul Heaney
 * FIXME this is horribily inefficient, we should load a table ONCE with all the public holidays
        and then just check that with this function
 */
function is_day_bank_holiday($day, $month, $year)
{
    global $dbHolidays;

    $date = "{$year}-{$month}-{$day}";
    $sql = "SELECT * FROM `{$dbHolidays}` WHERE type = 10 AND date = '{$date}'";

    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_ERROR);
        return FALSE;
    }

    if (mysql_num_rows($result) > 0) return TRUE;
    else return FALSE;
}


?>
