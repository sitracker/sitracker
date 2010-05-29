<?php
// deprecated.inc.php - deprecated functions that will be removed in a subsequent release
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
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
 * @author Kieran Hogg
 * @param string $name. name of the html entity
 * @param string $time. the time to set it to, format 12:34
 * @return string. HTML
 * @TODO perhaps merge with the new time display function?
 * @deprecated Replaced by time_picker, scheduled for removal in 4.0
 */
function time_dropdown($name, $time='')
{
    if ($time)
    {
        $time = explode(':', $time);
    }

    $html = "<select name='$name'>\n";
    $html .= "<option></option>";
    for ($hours = 0; $hours < 24; $hours++)
    {
        for ($mins = 0; $mins < 60; $mins+=15)
        {
            $hours = str_pad($hours, 2, "0", STR_PAD_LEFT);
            $mins = str_pad($mins, 2, "0", STR_PAD_RIGHT);

            if ($time AND $time[0] == $hours AND $time[1] == $mins)
            {
                $html .= "<option selected='selected' value='$hours:$mins'>$hours:$mins</option>";
            }
            else
            {
                if ($time AND $time[0] == $hours AND $time[1] < $mins AND $time[1] > ($mins - 15))
                {
                    $html .= "<option selected='selected' value='$time[0]:$time[1]'>$time[0]:$time[1]</option>\n";
                }
                else
                {
                    $html .= "<option value='$hours:$mins'>$hours:$mins</option>\n";
                }
            }
        }
    }
    $html .= "</select>";
    return $html;
}


