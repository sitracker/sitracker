<?php
// daybreakdown.inc.php - Displays the incident breakdown for a day
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paulheaney[at]users.sourceforge.net>


// Included by ../statistics.php

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


switch ($offset)
{
    case 0: $dayString = $strToday;
        break;
    case 1: $dayString = $strYesterday;
        break;
    default:
        $dayString = date("l", mktime(0, 0, 0, date('m'), date('d') - $offset, date('Y')));
        break;
}

echo "<h2>".sprintf($strStatsFromDayStatistics, $dateString);

echo "<table class='maintable'>";
echo "<tr><th>{$strPeriod}</th><th>{$strOpened}</th><th>{$strUpdated}</th><th>{$strClosed}</th><th>{$strHandled}</th>";
echo "<th>{$strUpdates}</th><th>{$strPerIncident}</th><th>{$strSkills}</th><th>{$strOwners}</th><th>{$strUsers}</th>";
echo "<th>{$strUpdatePerUserAbbrev}</th><th>{$strIncidentPerOwnerAbbrev}</th><th>{$strEmailReceivedAbbrev}</th><th>{$strEmailTransmittedAbbrev}</th><th>{$strHigherPriority}</th><th>{$strActivity}</th></tr>\n";

echo stats_period_row($dayString, mktime(0, 0, 0, date('m'), date('d') - $offset, date('Y')), mktime(23, 59, 59, date('m'), date('d') - $offset, date('Y')));

echo "</table>";

?>
