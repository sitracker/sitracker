<?php
// classes.inc.php - The generic classes used by SiT
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Ivan Lucas <ivanlucas[at]users.sourceforge.net> 
//             Paul Heaney <paul[at]sitracker.org>


// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


class Holiday {
    var $starttime;
    var $endtime;
}


/**
 * Highest level within SiT! all entities within SiT! should extend from this class
 * this provides a common interface exposing values and functiosn which are common across all entities
 * @author Paul Heaney
 */
abstract class SitEntity {
    var $id;

    /**
     * Retreives details of the entity from the database
     */
    abstract protected function retrieveDetails();

    /**
     * Adds the entity to SiT
     */
    abstract public function add();

    /**
     * Edits an existing entity in sit
     */
    abstract public function edit();
    
    /**
     * Generates the Array that is required by SOAP
     */
    abstract public function getSOAPArray();
}


/**
 * Base class for all types of people, this contains the core attributes common for all people
 * @author Paul Heaney
 */
abstract class Person extends SitEntity {
    var $username;
    var $password;
    var $jobtitle;
    var $email;
    var $phone;
    var $mobile;
    var $fax;
    var $source; ///< default: sit, ldap etc
}

?>