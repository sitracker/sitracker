<?php
// status.php - Status page
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivan[at]sitracker.org>
// Author: Paul Heaney <paul[at]sitracker.org>
// This Page is Valid XHTML 1.0 Transitional!

require ('core.php');
$permission = PERM_STATUS_VIEW;
require (APPLICATION_LIBPATH . 'functions.inc.php');

$title = $strStatus;

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>{$CONFIG['application_name']} {$title}</h2>";
plugin_do('status');

// $status = html_install_status();

$s = check_install_status();
echo html_install_status($s);

echo "<p align='center'>";

switch ($s->get_status())
{
    case INSTALL_OK:
        echo $strEnvironmentCheckedOK;
        break;
    case INSTALL_WARN:
        echo $strEnvironmentCheckedWarnings;
        break;
    case INSTALL_FATAL:
        echo $strEnvironmentCheckedFatal;
        break;
}

echo "</p>";

plugin_do('status_content');

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');