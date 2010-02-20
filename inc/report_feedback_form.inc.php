<?php
// form.php - Feedback selection form
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
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
echo "<input type='radio' name='dates' value='closedin' checked='checked' />{$strClosed} ";
echo "<input type='radio' name='dates' value='feedbackin' />{$strFeedback} ";
echo "</td></tr>";

echo "<tr><th>Type:</th><td>";
echo "<input type='radio' name='type' value='byengineer' checked='checked' />{$strUser} ";
echo "<input type='radio' name='type' value='bycustomer' />{$strContact} ";
echo "<input type='radio' name='type' value='bysite' />{$strSite} ";
echo "<input type='radio' name='type' value='byproduct' />{$strProduct} ";
echo "</td></tr>";

echo "</table>";

echo "<p><input type='submit' name='runreport' value=\"{$strRunReport}\" /></p>";
echo "</form>";

?>
