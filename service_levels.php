<?php
// service_levels.php - Displays current service level settings
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_VIEW_SLA; // View service levels
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$title = $strServiceLevels;

echo "<h2>".icon('sla', 32, $strServiceLevels)." {$title}</h2>";

$operations = array();
$operations[$strNewServiceLevel] = array('url' => 'service_level_new.php');
echo "<p align='center'>" . html_action_links($operations) . "</p>";


$tsql = "SELECT DISTINCT * FROM `{$dbServiceLevels}` GROUP BY tag ORDER BY active, tag";
$tresult = mysqli_query($db, $tsql);
if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
if (mysqli_num_rows($tresult) >= 1)
{
    $minsinday = ($CONFIG['end_working_day'] - $CONFIG['start_working_day']) / 60;

    echo "<table class='maintable'>";
    while ($tag = mysqli_fetch_object($tresult))
    {
        $strActive = '';
        $class = '';
        if ($tag->active == 'false')
        {
            $strActive = "({$strDisabled})";
            $class = "class='expired' ";
        }
        echo "<thead><tr><th colspan='9' {$class}>{$tag->tag} {$strActive}</th></tr></thead>";
        $sql = "SELECT * FROM `{$dbServiceLevels}` WHERE tag='{$tag->tag}' ORDER BY priority";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

        echo "<tr><th colspan='2' {$class}>{$strPriority}</th><th {$class}>{$strInitialResponse}</th>";
        echo "<th {$class}>{$strProblemDefinition}</th><th {$class}>{$strActionPlan}</th><th {$class}>{$strResolutionReprioritisation}</th>";
        echo "<th {$class}>{$strReview}</th><th {$class}>{$strTimed}</th><th {$class}>{$strActions}</th></tr>";
        while ($sla = mysqli_fetch_object($result))
        {
            echo "<tr>";
            echo "<td align='right' {$class}>".priority_icon($sla->priority)."</td>";
            echo "<td {$class}>".priority_name($sla->priority)."</td>";
            echo "<td {$class}>".format_workday_minutes($sla->initial_response_mins, $strNotApplicableAbbrev)."</td>";
            echo "<td {$class}>".format_workday_minutes($sla->prob_determ_mins, $strNotApplicableAbbrev)."</td>";
            echo "<td {$class}>".format_workday_minutes($sla->action_plan_mins, $strNotApplicableAbbrev)."</td>";
            // 480 mins in a working day
            echo "<td {$class}>".format_workday_minutes($sla->resolution_days * $minsinday, $strNotApplicableAbbrev)."</td>";
            echo "<td {$class}>".sprintf($strXDays, $sla->review_days)."</td>";
            if ($sla->timed == 'yes')
            {
                echo "<td {$class}>{$strYes}</td>";
            }
            else echo "<td {$class}>{$strNo}</td>";
            $operations = array();
            $operations[$strEdit] = array('url' => "service_level_edit.php?tag={$sla->tag}&amp;priority={$sla->priority}");
            echo "<td {$class}>" . html_action_links($operations) . "</td>";
            echo "</tr>\n";
        }
    }
    echo "</table>";
}
else
{
    echo user_alert($strNoRecords, E_USER_NOTICE);
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>