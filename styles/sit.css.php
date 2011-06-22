<?php
// sit.css.php - CSS file
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paul[at]sitracker.net>

// Note: This file is PHP that outputs CSS code, this is primarily
//       to enable us to pass variables from PHP to CSS.
//

$permission = PERM_NOT_REQUIRED; // not required
require ('..' . DIRECTORY_SEPARATOR . 'core.php');

session_name($CONFIG['session_name']);
session_start();

require (APPLICATION_LIBPATH . 'functions.inc.php');

if ($_SESSION['auth'] == TRUE)
{
    $theme = $_SESSION['userconfig']['theme'];
    $iconset = $_SESSION['userconfig']['iconset'];
}
else
{
    $theme = $CONFIG['default_interface_style'];
    $iconset = $CONFIG['default_iconset'];
}
if (empty($iconset)) $iconset = 'sit';

header('Content-type: text/css');

echo "
select .initialresponse 
{
	background-image: url({$CONFIG['application_webpath']}/images/icons/$iconset/16x16/initialresponse.png);
}

select .problemdef 
{
	background-image: url({$CONFIG['application_webpath']}/images/icons/$iconset/16x16/probdef.png);
}

select .actionplan 
{
	background-image: url({$CONFIG['application_webpath']}/images/icons/$iconset/16x16/actionplan.png);
}

select .solution 
{
	background-image: url({$CONFIG['application_webpath']}/images/icons/$iconset/16x16/solution.png);
}
";
