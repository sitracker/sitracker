<?php

// activity_support.inc.php - Support activity information
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Tom Gerrard <tom.gerrard[at]salfordsoftware.co.uk>
//
// Included by timesheet.inc.php

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

foreach(array('level', 'data', 'ws' ) as $var)
{
    eval("\$$var=cleanvar(\$_REQUEST['$var']);");
}

if ($level == '')
{
    $activity_types['Development'] = '';
    echo "<script type='text/javascript'>
/* <![CDATA[ */
        function activityDevelopment(level)
        {
            if (level == 0)
            {
                $('newactivityalias').value = 'Development';
                var newid = weekSchedule_ajaxObjects.length;
                weekSchedule_ajaxObjects[newid] = new sack();
                weekSchedule_ajaxObjects[newid].requestFile = 'calendar/activity_development.inc.php?level=' + level + '&amp;data=' + getSelectedActivity() + '&amp;ws=' + dateStartOfWeek.getTime();
                weekSchedule_ajaxObjects[newid].onCompletion = function(){ activityDevelopmentCallback(level, newid); };
                weekSchedule_ajaxObjects[newid].runAJAX();
            }
        }

        function activityDevelopmentCallback(level, newid)
        {
            var incidents = new Array();
            var items = weekSchedule_ajaxObjects[newid].response.split(/<item>/g);
            weekSchedule_ajaxObjects[newid] = false;
            for (var i = 1; i < items.length; i ++)
            {
                var lines = items[i].split(/\\n/g);
                incidents[i] = new Array();
                for (var j = 0; j < lines.length; j ++)
                {
                    var key = lines[j].replace(/<([^>]+)>.*/g,'$1');
                    if (key) key = trimString(key);
                    var pattern = new RegExp('<\\/?' + key + '>', 'g');
                    var value = lines[j].replace(pattern,'');
                    value = trimString(value);
                    incidents[i][key] = value;
                }
            }
            level ++;

            while ($('addactivityselect' + level).length > 1)
            {
                $('addactivityselect' + level).remove(0);
            }

            for (i = 1; i < incidents.length; i ++)
            {
                $('addactivitydescription' + level).innerHTML = incidents[i]['description'];
                $('addactivitydescription' + level).parentNode.parentNode.style.display = 'table-row';
                var incidentname = '" . $strIncident . " ' + incidents[i]['id'];
                $('newactivityalias').value = incidentname;
             	hint = incidents[i]['title'];
                appendOption($('addactivityselect' + level), incidentname + ' - ' + hint, incidentname);
            }
        }

        activityTypes['Development'] = activityDevelopment;
/* ]]> */
    </script>
";
}
else
{
    header('Content-Type: text/xml');
    echo '<?xml version="1.0" ?>' . "\n";

    $descr[1] = 'You may choose a project:';
    $descr[2] = 'You may choose a category:';
    $descr[3] = 'You may choose a bug:';
}
?>