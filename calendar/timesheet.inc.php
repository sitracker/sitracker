<?php
// timesheet.inc.php - Displays a timesheet sview of the calendar
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

if ($CONFIG['timesheets_enabled'] != TRUE)
{
    echo "<p class='error'>{$strTimesheets}: {$strDisabled}</p>";
}
else
{
    $rowHeight = 30;

    global $user;

    // DHTML week planner library
    echo "<script type='text/javascript' src='{$CONFIG['application_webpath']}calendar/ajax.js'></script>\n";
    echo "<script type='text/javascript'>\n";
    echo "/* <![CDATA[ */\n";
    echo "  var itemRowHeight={$rowHeight};\n";
    echo "  var initDateToShow = '" . date('Y-m-d') . "';\n";
    echo "/* ]]> */\n";
    echo "</script>";
    echo "<script src='{$CONFIG['application_webpath']}calendar/week_planner.js.php?user=$user' type='text/javascript'></script>\n";

    // DOJO to drop the jobs, etc. onto the dates
    // FIXME dojo is DEPRECATED
    echo "<script type='text/javascript' src='{$CONFIG['application_webpath']}scripts/dojo/dojo.js' djConfig='parseOnLoad: true'></script>\n";

    echo "<script type='text/javascript'>\n
        /* <![CDATA[ */\n
        var activitycount = 0;
        dojo.require ('dojo.dnd.*');
        dojo.require ('dojo.event.*');
        dojo.require ('dojo.lang');
        dojo.declare('salford.dnd.DestDropTarget', dojo.dnd.HtmlDropTarget, {
            onDrop: function(e)
            {
                this.domNode.style.backgroundColor = '#ddf';
                var saveString = '?saveAnItem=true'
                + '&description=' + escape(e.dragObject.domNode.innerHTML)
                + '&name=' + escape(e.dragObject.domNode.id)
                + '&droptarget=' + this.domNode.id
                + '&week=' + dateStartOfWeek.getTime()
                + '&user=' + $user
                + '&newItem=2';


                var newid;
                newid = new sack();
                newid.requestFile = externalSourceFile_save  + saveString;
                newid.onCompletion = function(){ refreshAppointments(); };
                newid.runAJAX();
            },

            onDragOver: function(e)
            {
                this.domNode.style.backgroundColor = '#ff0';
                return dojo.dnd.HtmlDropTarget.prototype.onDragOver.apply(this, arguments);
            },

            onDragOut: function(e)
            {
                this.domNode.style.backgroundColor = '#ddf';
                return dojo.dnd.HtmlDropTarget.prototype.onDragOut.apply(this, arguments);
            }
        });


        dojo.event.connect(dojo, 'loaded', 'initialise');

        var activityTypes = new Array();

        retrievePreviousActivities();\n
        /* ]]> */\n
        </script>
    ";

    // Build up activity types
    $activity_files['$strSupport'] = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR . 'calendar/activity_support.inc.php';
    $activity_files['$strTravelling'] = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR . 'calendar/activity_travelling.inc.php';
    $activity_files['$strResearch'] = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR . 'calendar/activity_research.inc.php';
    $activity_files['$strDevelopment'] = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR . 'calendar/activity_development.inc.php';
    $activity_files['$PreSales'] = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR . 'calendar/activity_presales.inc.php';
    $activity_files['$strTraining'] = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR . 'calendar/activity_training.inc.php';
    $activity_files['$strManagement'] = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR . 'calendar/activity_managerial.inc.php';
    plugin_do('activity_types');

    foreach ($activity_files as $activity_name => $activity_file)
    {
        include ($activity_file);
    }

    ksort($activity_types);

    global $approver;

    if ($approver)
    {
        echo "<form method='post' action='" . $_SERVER['PHP_SELF'] . "?display=timesheet'>";
        echo user_drop_down('user', $user, FALSE);
        echo "<input type='submit' value='{$strSwitchUser}' />";
        echo "</form>";
        echo "<br/>";
    }

    // Controls Table
    echo "<div id='rightdiv' style='float: right; border: 1px dashed gray; width: 25%;'>";
    echo "<table class='timesheet'>";
    echo "<tr>";
    echo "<th>{$strKey}</th>";
    echo "<th>{$strOperation}</th>";
    echo "</tr>";
    echo "<tr>";
    echo "<td style='width:200px;'><center>";
    foreach (array('Unconfirmed' => 'ddf', 'Confirmed' => 'fff', 'Waiting Approval' => 'ffb', 'Approved' => 'fdf', 'Unavailable' => 'dfd') as $at => $col)
    {
        echo "<div style='background-color: #$col; width: 110px; margin: 5px; border: 1px solid grey;'>$at</div>";
    }
    echo "</center></td>";
    echo "<td>";
    echo "<input type='button' class='weekButton' value='&lt;&lt; Week' onclick='displayPreviousWeek(); return false' /><br />";
    echo "<input type='button' class='weekButton' value='Week &gt;&gt;' onclick='displayNextWeek(); return false' style='margin-bottom: 10px;'/><br />";
    echo "<input id='addtogbutton' type='button' class='weekButton' value='$strAdd &uArr;' onclick=\"toggleMode();\" style='margin-bottom: 20px;' /><br />";
    echo "<input type='button' class='weekButton' value='Submit' onclick='submitTimesheet(); return false'/><br />";
    echo "</td>";
    echo "</tr>";
    echo "</table>\n";
    echo "</div>";







    echo "<div id='addremove' style='position: absolute; display:none;  ";
    echo "width: 0px; background-color: #CCCCFF; z-index:30000; ";
    echo "border: 3px solid gray;'>";
//     echo "<fieldset style='width: 586px;'>";
//     echo "<legend>{$strActivity}</legend>: ";
    echo "<div style='float:right; '>";
    echo "</div>";
    echo "<div style='padding: 5px;'>";
    for ($i = 0; $i < 4; $i ++)
    {
        echo "<input type='hidden' id='newactivity{$i}' name='' value='' />";
    }

    echo "<input type='hidden' id='newactivityalias' name='' value='' />";
    echo "<table class='timesheet'>\n";
    echo "<tr><th id = 'addactivitydescription0' style='width:300px; text-align: right;'>Please choose the category of activity to add:</th>";
    echo "<td><select id='addactivityselect0' style='width: 260px;' onchange='activityChange(\"0\", \"select\");'>\n";
    echo "<option selected='selected'></option>";
    foreach ($activity_types as $type => $xxx)
    {
        echo "<option>{$type}</option>";
    }
    echo "</select>";
    echo "</td>";
    echo "<td></td>";
    echo "</tr>";

    for ($i = 1; $i < 4; $i ++)
    {
        echo "<tr style='display:none;'><th style='width:300px;'><span id = 'addactivitydescription$i'>&nbsp;</span></th>";
        echo "<td><select id='addactivityselect$i' style='width: 260px;' onchange='activityChange(\"$i\", \"select\");'>\n";
        echo "<option selected='selected'></option>";
        echo "</select></td>";
        // TODO: this needs to be there, but hidden and switched in and out over the top of the above one...
        //echo "<td><input id='addactivityedit$i' style='width: 200px;' onchange='activity_change($i, \'edit\');'>\n";
        echo "</tr>";
    }
    echo "<tr><td></td><td style='text-align:right; padding-top: 10px;'>";
    // INL 03Apr08 removed "enabled='false'" from the following two buttons, invalid markup, not sure what it was supposed to do
    echo "<input type='button' class='weekButton' value='Add' onclick='addActivity(\"0\", \"0\", \"0\", \"\"); return false;' />";
    echo "<input type='button' class='weekButton' value='Close' onclick='toggleMode(); return false;' />";
    echo "</td></tr>";
    echo "</table>";


    echo "</div>";
    echo "<br /><br />";
//     echo "</fieldset>";
    echo "</div>";

    echo "<div style='text-align: left;'>"; // width: 986px;
//     echo "<div id='rightdiv' style='float: right; padding: 23px 5px 0 0;'>";
//     echo "<table class='timesheet'>";
//     echo "<tr>";
//     echo "<th>{$strKey}</th>";
//     echo "<th>{$strControls}</th>";
//     echo "</tr>";
//     echo "<tr>";
//     echo "<td style='width:200px;'><center>";
//     foreach (array('Unconfirmed' => 'ddf', 'Confirmed' => 'fff', 'Waiting Approval' => 'ffb', 'Approved' => 'fdf', 'Unavailable' => 'dfd') as $at => $col)
//     {
//         echo "<div style='background-color: #$col; width: 110px; margin: 5px; border: 1px solid grey;'>$at</div>";
//     }
//     echo "</center></td>";
//     echo "<td>";
//     echo "<input type='button' class='weekButton' value='&lt;&lt; Week' onclick='displayPreviousWeek(); return false' /><br />";
//     echo "<input type='button' class='weekButton' value='Week &gt;&gt;' onclick='displayNextWeek(); return false' style='margin-bottom: 10px;'/><br />";
//     echo "<input id='addtogbutton' type='button' class='weekButton' value='$strAdd &uArr;' onclick=\"toggleMode();\" style='margin-bottom: 20px;' /><br />";
//     echo "<input type='button' class='weekButton' value='Submit' onclick='submitTimesheet(); return false'/><br />";
//     echo "</td>";
//     echo "</tr>";
//     echo "</table>\n";
//     echo "</div>";



    echo "<div id='leftdiv' style='width: 70%;'>";
//     echo "<fieldset><legend>{$strTimesheet}</legend>\n";
    echo "<div class='push'></div>";
    echo "<ul id='dropsources'><li>\n";
    echo "<table class='timesheet' style='width: 100%;' id='activitytable'><tr>";
    echo "<th>{$strActivity}</th><th>{$strStatus}</th><th>{$strNotes}</th>";
    echo "</tr></table>";
    echo "</li></ul>";
    echo "<div class='floor'></div>";
//     echo "</fieldset>";
    echo "</div>";

    echo "<br />";
    echo "<div id='weekScheduler_container'>\n";
    echo "<div id='weekScheduler_top'>\n";
    echo "<div class='spacer'><a href='javascript:displayPreviousWeek();' title='Previous Week'>&lt;</a>";
    echo " &nbsp; ";
    echo "<a href='javascript:displayNextWeek();' title='Next Week'>&gt;</a><span></span></div>\n";
    echo "<div class='days' id='weekScheduler_dayRow'>\n";
    echo "<div id='drop1' class='shade2'>{$strMonday} <span></span></div>\n";
    echo "<div id='drop2' class='shade2'>{$strTuesday} <span></span></div>\n";
    echo "<div id='drop3' class='shade2'>{$strWednesday} <span></span></div>\n";
    echo "<div id='drop4' class='shade2'>{$strThursday} <span></span></div>\n";
    echo "<div id='drop5' class='shade2'>{$strFriday} <span></span></div>\n";
    echo "<div id='drop6' class='shade2'>{$strSaturday} <span></span></div>\n";
    echo "<div id='drop7' class='shade2'>{$strSunday} <span></span></div>\n";
    echo "</div></div>\n";
    echo "<div id='weekScheduler_content'>\n";
    echo "<div id='weekScheduler_hours'>\n";


    $startHourOfWeekPlanner = 0;    // Start hour of week planner
    $endHourOfWeekPlanner = 23; // End hour of weekplanner.

    $date = mktime($startHourOfWeekPlanner, 0, 0, 5, 5, 2006);
    for ($no = $startHourOfWeekPlanner; $no <= $endHourOfWeekPlanner; $no ++)
    {
        $hour = $no;
        $suffix = date('a', $date);
        $hour = date('g', $date);
        $time = $hour . "<span class='content_hour'>$suffix</span>";
        $date = $date + 3600;
        echo "<div class='calendarContentTime shade2'>$time</div>\n";
    }
    echo "</div>\n";
    echo "<div id='weekScheduler_appointments'>\n";
    for($no = 0; $no < 7; $no ++)
    {
        echo "<div class='weekScheduler_appointments_day'>\n";
        for($no2 = $startHourOfWeekPlanner; $no2 <= $endHourOfWeekPlanner; $no2 ++)
        {
            echo "<div id='weekScheduler_appointment_hour". $no . '_' . $no2 . "' class='weekScheduler_appointmentHour' style='background-color: #eee;'></div>\n";
        }
        echo "</div>\n";
    }
    echo "</div></div></div>\n";
    echo "</div>";
}
?>
