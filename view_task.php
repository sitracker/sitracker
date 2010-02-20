<?php
// view_task.php - Display existing task
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Kieran Hogg <kieran[at]sitracker.org>

$permission = 0; // Allow all auth users

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strViewTask;

// External variables
$action = $_REQUEST['action'];
$id = cleanvar($_REQUEST['incident']);
$taskid = cleanvar($_REQUEST['id']);
$mode = cleanvar($_REQUEST['mode']);

if ($mode == 'incident')
{
    include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
}
else
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
}

require (APPLICATION_INCPATH . 'task_view.inc.php');
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>