<?php
// view_task.php - Display existing task
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Kieran Hogg <kieran[at]sitracker.org>

require ('core.php');
$permission = PERM_NOT_REQUIRED; // Allow all auth users
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strViewTask;

// External variables
$id = clean_int($_REQUEST['incident']);
$taskid = clean_int($_REQUEST['id']);
$mode = clean_fixed_list($_REQUEST['mode'], array('', 'incident'));

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