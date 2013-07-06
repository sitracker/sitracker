<?php
// template_new.php - Form for adding new templates
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

require ('core.php');
$permission = PERM_TEMPLATE_ADD; // Add Email Template
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNewTemplate;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('new', 32)." {$strNewTemplate}</h2>";

echo "<form action='templates.php?action=new' method='post'>";
echo "<p align='center'><label>{$strTemplate}: ";
echo "<select name='template'>";
echo "<option value='email'>{$strEmail}</option>";
echo "<option value='notice'>{$strNotice}</option>";
echo "</select></label><br /><br />";
echo "<label>{$strName}: <input name='name' /></label>";
echo "<br /><br /><input type='submit' value='{$strNew}' />";
echo "</p>";
echo "</form>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>