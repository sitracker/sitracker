<?php
// planner_schedule_delete.php - deletes an event from the tasks table
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Tom Gerrard <tom.gerrard[at]salfordsoftware.co.uk>

$permission = 27; // View your calendar
require ('..'.DIRECTORY_SEPARATOR.'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'auth.inc.php');

$eventToDelete = cleanvar($_GET['eventToDeleteId']);

if (isset($eventToDelete))
{
    // TODO there should be a permission check here
    if (true)
    {
        mysql_query("DELETE FROM `{$dbTasks}` WHERE id='".$eventToDelete."'");
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        echo "OK"; // Do not translate
    }
}

?>