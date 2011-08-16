<?php
// browse_feedback_form.php - Browse feedback forms
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paulheaney[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_FEEDBACK_FORM_EDIT; // Edit Feedback Forms
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strFeedbackForms;
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('reports', 32)." {$title}</h2>";

$sql = "SELECT * FROM `{$dbFeedbackForms}`";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

if (mysql_num_rows($result) > 0)
{
    echo "<div id='feedbackformtable'>";
    while ($obj = mysql_fetch_object($result))
    {
        echo "<dl>\n";
        echo "<dt>";
        echo "<a href='feedback_form_edit.php?formid={$obj->id}'>{$obj->name}</a> ";
        echo "</dt>\n";
        echo "<dd>{$obj->introduction}</dd>\n";
        echo "</dl>\n";
    }
    echo "</div>";
}
else
{
    echo "<p align='center'>{$strNoFeedbackFormsDefined}</p>";
    echo "<p align='center'><a href='feedback_form_edit.php?action=new'>{$strCreateNewForm}</a></p>";
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>