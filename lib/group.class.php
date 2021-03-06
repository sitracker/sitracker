<?php
// group.class.php - The representation of a group within sit
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney <paul[at]sitracker.org>


class Group extends SitEntity {

    var $name;
    var $imageurl;

    function Group($id=0)
    {
        if ($id > 0)
        {
            $this->id = $id;
            $this->retrieveDetails();
        }
        else
        {
            $this->name = $GLOBALS['strNotSet'];
        }
    }


    function retrieveDetails()
    {
        global $db;
        if ($this->id > 0)
        {
            $sql = "SELECT * FROM `{$GLOBALS['dbGroups']}` WHERE id = {$this->id}";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);

            if (mysqli_num_rows($result) == 1)
            {
                $obj = mysqli_fetch_object($result);
                $this->name = $obj->name;
                $this->imageurl = $obj->imageurl;
            }
            else
            {
            	$this->id = 0;
                $this->name = $GLOBALS['strNotSet'];
            }
        }
    }


    function add()
    {
        trigger_error("Group.add() not yet implemented");
    }


    function edit()
    {
        trigger_error("Group.edit() not yet implemented");
    }

    function getSOAPArray()
    {
        trigger_error("Group.getSOAPArray() not yet implemented");
    }
}
?>