<?php
// escalate.php - Escalate an incident to a external support system
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2012 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paul[at]sitracker.org>


require ('core.php');
$permission = PERM_NOT_REQUIRED;
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$id = clean_int($_REQUEST['id']);
$incidentid = $id;

$title = "ESCALATE";
include (APPLICATION_INCPATH . 'incident_html_top.inc.php');

$action = clean_fixed_list($_REQUEST['action'], array('', 'get_details', 'escalate'));

$path = clean_alphanumeric($_REQUEST['path']);

switch ($action)
{
    case 'escalate':

        $esc = new $path;
        
        $esc = $esc->doEscalation($id);
        
        if ($esc->status)
        {
            // update incident with external id
        }
        else
        {
            
        }
        
        
        break;
    
    case 'get_details':
        echo "<h2>Enter escalation details</h2>";
        $esc = new $path;
        
        echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
        
        echo "<input type='hidden' id='id' name='id' value='{$id}' />";
        echo "<input type='hidden' id='action' name='action' value='escalate' />";
        
        echo $esc->getFormElements();
        
        echo "<p align='center'>";
        echo "<input type='submit' value='Escalate' />";
        echo "</p>";
        
        echo "</form>";
        
        break;

	default:

	    echo "<h2>Choose escalation path</h2>";
	    
	    echo "<p align='center'>Escalate to:";
	    
		echo "<ul>";

		foreach ($PLUGINACTIONS['escalate'] AS $pluginaction)
		{
		    $esc = new $pluginaction;
			if ($esc instanceof EscalationPlugin)
			{
				echo "<li><a href='{$_SERVER['PHP_SELF']}?id={$id}&amp;path={$pluginaction}&amp;action=get_details'>{$esc->name}</a></li>";
			}
		}
		echo "</ul>";
		
		echo "</p>";
}

include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');

?>