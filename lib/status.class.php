<?php
// status.class.php - The status class for SiT
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
 * 
 * Enter description here ...
 * @author Paul Heaney
 *
 */
class Status {
    var $statusentries = array();
    
    function Status()
    {
        $s = new StatusItem();
        $s->checkname = 'PHP Version';
        $s->minimum = MIN_PHP_VERSION;
        $s->found = PHP_VERSION;
        if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '>=')) $s->status = INSTALL_OK;
        else $s->status = INSTALL_FATAL;
        
        $this->statusentries[] = $s;
    }
    
    function add_extension_check($extension, $name, $min_status)
    {
        $s = new StatusItem();
        $s->checkname = $name;
        if ($min_status == INSTALL_FATAL)
        {
            $s->minimum = $GLOBALS['strInstalled'];
        }
        else
        {
            $s->minimum = $GLOBALS['strOptionional'];
        }

        if (extension_loaded($extension))
        {
            $s->found = $GLOBALS['strInstalled'];
            $s->status = INSTALL_OK;
        }
        else 
        {
            $s->found = $GLOBALS['strNotInstalled'];
            $s->status = $min_status;
        }
        
        $this->statusentries[] = $s;
    }
    
    function mysql_check()
    {
        $s = new StatusItem();
        $s->checkname = 'MySQL Version';
        $s->minimum = MIN_MYSQL_VERSION;
        $s->found = mysql_get_server_info();
        if (version_compare($s->found, $s->minimim, '>=')) $s->status = INSTALL_OK;
        else $s->status = INSTALL_FATAL;
        
        $this->statusentries[] = $s;
    }
    
    function get_status()
    {
        $rtn = INSTALL_OK;
        
        foreach ($this->statusentries AS $s)
        {
            if ($s->status > $rtn) $rtn = $s->status;
        }
        
        return $rtn;
    }
}


/**
 * 
 * Enter description here ...
 * @author Paul Heaney
 *
 */
class StatusItem {
    var $checkname = '';
    var $minimum = '';
    var $found = '';
    var $status = INSTALL_FATAL;
}
