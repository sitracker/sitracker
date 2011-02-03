<?php
// control_panel.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


$permission = 4; // Edit your profile

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strControlPanel;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');
echo "<h2>".icon('settings', 32, $strControlPanel);
echo " {$CONFIG['application_shortname']} {$strControlPanel}</h2>";
echo "<table align='center'>";
echo "<thead>";
echo "<tr><th>{$strUserSettings}</th></tr>";
echo "</thead>";
echo "<tbody>";
echo "<tr><td><a href='user_profile_edit.php'>{$strMyProfile}</a></td></tr>";
echo "<tr><td><a href='edit_user_skills.php'>{$strMySkills}</a></td></tr>";
echo "<tr><td><a href='edit_backup_users.php'>{$strMySubstitutes}</a></td></tr>";
echo "<tr><td><a href='holidays.php'>{$strMyHolidays}</a></td></tr>";
echo "</tbody>\n";
if (user_permission($sit[2],42)) // Review/Delete Incident Updates
{
    echo "<thead><tr><th>{$strTechnicalSupportAdmin}</th></tr></thead>";
    echo "<tbody><tr><td><a href='holding_queue.php'>{$strHoldingQueue}</a></td></tr></tbody>";
}

if (user_permission($sit[2],44)) // FTP Publishing
{
    echo "<thead><tr><th>{$strFiles}</th></tr></thead>";
    echo "<tbody><tr><td><a href='ftp_list_files.php'>{$strManageFTPFiles}</a></td></tr></tbody>";
}
if (user_permission($sit[2],50)) // Approve holidays
{
    echo "<thead><tr><th>{$strManageUsers}</th></tr></thead>";
    echo "<tbody><tr><td><a href='holiday_request.php?user=all&mode=approval'>{$strApproveHolidays}</a></td></tr></tbody>";
}
if (user_permission($sit[2],22)) // Administrate
{
    echo "<thead><tr><th>{$strAdministratorsOnly}</th></tr></thead>";
    echo "<tbody>";
    echo "<tr><td><a href='manage_users.php'>{$strManageUsers}</a></td></tr>";
    echo "<tr><td><a href='templates.php'>{$strManageEmailTemplates}</a></td></tr>";
    echo "<tr><td><a href='journal.php'>{$strBrowse} {$CONFIG['application_shortname']} {$strJournal}</a></td></tr>";
    echo "<tr><td><a href='service_levels.php'>{$strServiceLevels}</a></td></tr>";
    echo "<tr><td><a href='product_info_new.php?action=showform'>{$strNewProductInformation}</a></td></tr>";
    echo "<tr><td><a href='calendar.php?type=10&amp;display=year'>{$strSetPublicHolidays}</a></td></tr>";
    echo "<tr><td><a href='contacts_show_orphans.php'>{$strShowOrphandedContacts}</a></td></tr>";
    echo "</tbody>";
}

plugin_do('cp_menu');
echo "</table>\n";
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>
