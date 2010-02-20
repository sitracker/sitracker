<?php
// site.class.php - The representation of a site within sit
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney <paul[at]sitracker.org>


class Site extends SitEntity {

    function Site($id=0)
    {
        if ($id > 0)
        {
        $this->id = $id;
            $this->retrieveDetails();
        }
    }


    function retrieveDetails()
    {
        trigger_error("Site.retrieveDetails() not yet implemented");
    }


    function add()
    {
        trigger_error("Site.add() not yet implemented");
    }


    function edit()
    {
        trigger_error("Site.edit() not yet implemented");
    }

    function getSOAPArray()
    {
        trigger_error("Site.getSOAPArray() not yet implemented");
    }
}
?>
