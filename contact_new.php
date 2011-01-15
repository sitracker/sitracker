<?php
// contact_new.php - Adds a new contact
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional!  31Oct05


$permission = 1; // Add new contact

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$siteid = mysql_real_escape_string($_REQUEST['siteid']);
$submit = $_REQUEST['submit'];
$title = $strNewContact;

if (empty($submit) OR !empty($_SESSION['formerrors']['new_contact']))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo show_new_contact($siteid, 'internal');
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    echo process_new_contact();
}
?>