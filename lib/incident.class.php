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
 * @todo FIXME move out of this file into incidents in SiT3.60, extend SitEntity and make more useful
 */
class Incident extends SitEntity
{
    var $incidentid = -1;
    var $title = "no title";
    var $ownerid = -1;
    var $townerid = -1;
    var $owner = "no owner";
    var $towner = "no temp owner";
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
        return array('incidentid' => $this->incidentid,
                            'title' => $this->title,
                            'ownerid' => $this>ownerid,
                            'townerid' => $this->townerid,
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