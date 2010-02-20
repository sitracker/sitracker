<?php

// activity_managerial.inc.php - Managerial activity information
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Kieran Hogg <kieran_hogg[at]googlemail.com>
//
// Included by timesheet.inc.php

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

$activity_types['Managerial'] = "";

echo "<script type='text/javascript'>
    
    function activityManagerial(level)
    {
        $('newactivityalias').value = 'Managerial';               
    }
    
    activityTypes['Managerial'] = activityManagerial;

</script>
";

?>
