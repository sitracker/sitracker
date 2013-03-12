<?php
// incident.inc.php - The class which represents an incident within SiT
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney <paul[at]sitracker.org>

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


/**
 * Incident class for SiT, represents a single incident within SiT
 * @author Paul Heaney
 */
class Incident extends SitEntity
{
    var $incidentid = -1;
    var $title = "no title";
    var $owner; // User object type
    var $towner; // User object type
    var $skillid = -1;
    var $skill = "no skill";
    var $maintenanceid = -1;
    var $maintenance = "no maintenance";
    var $priorityid = -1;
    var $priority = "no priority";
    var $currentstatusid = -1;
    var $currentstatusinternal = "no status";
    var $currentstatusexternal = "no status";
    var $servicelevel = "no service level";

    function Incident($id=0)
    {
        if ($id > 0)
        {
            $this->incidentid = $id;
            $this->retrieveDetails();
        }
        else
        {
            $this->owner = new User();
            $this->owner = new User();
        }
    }
    
    function retrieveDetails()
    {
    	trigger_error("Incident.retrieveDetails() not yet implemented");
    }

    function add()
    {
    	trigger_error("incident.add() not yet implemented");
    }
    
    function edit()
    {
    	trigger_error("Incident.edit() not yet implemented");
    }

    /**
     * Returns the array of the incident required by nusoap
     * @return array. Array for NUSOAP
     * @author Paul Heaney 
     */
    function getSOAPArray()
    {
        debug_log($this->owner);
        return array('incidentid' => $this->incidentid,
                            'title' => $this->title,
                            'owner' => $this->owner,
                            'towner' => $this->towner,
                            'skillid' => $this->skillid,
                            'skill' => $this->skill,
                            'maintenanceid' => $this->maintenanceid,
                            'maintenance' => $this->maintenance,
                            'priorityid' => $this->priorityid,
                            'priority' => $this->priority,
                            'currentstatusid' => $this->currentstatusid,
                            'currentstatusinternal' => $this->currentstatusinternal,
                            'currentstatusexternal' => $this->currentstatusexternal,
                            'servicelevel' => $this->servicelevel
                        );
    }

}

?>