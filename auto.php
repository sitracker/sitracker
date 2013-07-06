<?php
// auto.php - Regular SiT! maintenance tasks (for scheduling)
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This file should be called from a cron job (or similar) to run tasks periodically


require ('core.php');
include_once (APPLICATION_LIBPATH . 'strings.inc.php');
require_once (APPLICATION_LIBPATH . 'functions.inc.php');
include_once (APPLICATION_LIBPATH . 'billing.inc.php');
require_once (APPLICATION_LIBPATH . 'trigger.class.php');
require_once (APPLICATION_LIBPATH . 'sactions.inc.php');
populate_syslang();

$crlg = "\n";


// =======================================================================================
$actions = schedule_actions_due();
if ($actions !== FALSE)
{
    foreach ($actions AS $action => $params)
    {
        $fn = "saction_{$action}";
        // Possibly initiate a trigger here named TRIGGER_SCHED_{$action} ?
        if (function_exists($fn))
        {
            schedule_action_started($action);
            $success = $fn($params);
            schedule_action_done($action, $success);
        }
        else
        {
            schedule_action_done($action, FALSE);
        }
    }
}
plugin_do('auto');

?>