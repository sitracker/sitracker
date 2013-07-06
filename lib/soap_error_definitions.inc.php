<?php
// soap_types.inc.php - The types used by SIT! soap implementation
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul[at]sitracker.org>

$soap_errors = array(
            'no_error' => array ('value' => 0, 'name' => 'No error', 'description' => 'No Error'),
            'login_failed' => array ('value' => 1, 'name' => 'Login details incorrect', 'description' => 'Username and password supplied are invalid'),
            'session_not_valid' => array ('value' => 2, 'name' => 'Not logged in/session not valid', 'description' => 'Session supplied is not authenticated'),
            'no_access' => array('value' => 3, 'name' => 'No Permission', 'description' => 'User does not have permission to view this item')
);

?>
