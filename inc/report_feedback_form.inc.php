<?php
// form.php - Feedback selection form
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney Paul Heaney <paulheaney[at]users.sourceforge.net>

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


echo "<form action='{$_SERVER['PHP_SELF']}' method='post' id='feedback'>";
echo "<table class='vertical'>";
echo "<tr><th>{$strStartDate}:</th>";
echo "<td><input type='text' name='startdate' id='startdate' size='10' /> ";
echo date_picker('feedback.startdate');
echo "</td></tr>\n";
echo "<tr><th>{$strEndDate}:</th>";
echo "<td><input type='text' name='enddate' id='enddate' size='10' /> ";
echo date_picker('feedback.enddate');
echo "</td></tr>\n";

echo "<tr><th>{$strDatesAre}:</th><td>";
echo "<label><input type='radio' name='dates' value='closedin' checked='checked' />{$strClosed}</label> ";
echo "<label><input type='radio' name='dates' value='feedbackin' />{$strFeedback}</label> ";
echo "</td></tr>";

echo "<tr><th>{$strType}:</th><td>";
echo "<label><input type='radio' name='type' value='byengineer' checked='checked' />{$strUser}</label> ";
echo "<label><input type='radio' name='type' value='bycustomer' />{$strContact}</label> ";
echo "<label><input type='radio' name='type' value='bysite' />{$strSite}</label> ";
echo "<label><input type='radio' name='type' value='byproduct' />{$strProduct}</label> ";
echo "</td></tr>";

echo "</table>";

echo "<p class='formbuttons'>";
echo "<input type='reset' value=\"{$strReset}\" /> ";
echo "<input type='submit' name='runreport' value=\"{$strRunReport}\" /></p>";
echo "</form>";

?>