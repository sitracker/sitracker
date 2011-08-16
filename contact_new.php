<?php
// contact_new.php - Adds a new contact
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional!  31Oct05

require ('core.php');
$permission = PERM_CONTACT_ADD; // Add new contact
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