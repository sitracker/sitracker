<?php
// task.class.php - The representation of a task within sit
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney <paul[at]sitracker.org>


class Task extends SitEntity {

    function Task($id=0)
    {
        if ($id > 0)
        {
        $this->id = $id;
            $this->retrieveDetails();
        }
    }


    function retrieveDetails()
    {
        trigger_error("Task.retrieveDetails() not yet implemented");
    }


    function add()
    {
        trigger_error("Task.add() not yet implemented");
    }


    function edit()
    {
        trigger_error("Task.edit() not yet implemented");
    }

    function getSOAPArray()
    {
        trigger_error("Task.getSOAPArray() not yet implemented");
    }
}
?>
