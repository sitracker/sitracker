<?php
// unlock_update.php - Unlocks incident updates
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// This page is called from incident_html_top.inc.php

require ('core.php');
$permission = PERM_UPDATE_DELETE;
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$incomingid = clean_int($_REQUEST['id']);

if (empty($incomingid)) trigger_error("Update ID was not set:{$updateid}", E_USER_WARNING);

$sql = "UPDATE `{$dbTempIncoming}` SET locked = NULL, lockeduntil = NULL ";
$sql .= "WHERE id='{$incomingid}' AND locked = '{$sit[2]}'";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
else
{
    // TODO remove this page as and when the new inbox is live
    header('Location: holding_queue.php');
}

?>