<?php
// site.inc.php - functions relating to sites
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

/**
 * Return the number of inventory items for a site
 * @author Kieran
 * @param int $id. Site ID
 * @return int.
 */
function site_count_inventory_items($id)
{
    global $dbInventory;
    $count = 0;

    $sql = "SELECT COUNT(id) FROM `{$dbInventory}` WHERE siteid='{$id}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    else list($count) = mysql_fetch_row($result);
    mysql_free_result($result);

    return $count;
}


/**
 * Fetches the name of the given site
 * @author Ivan Lucas
 * @param int $id. the site ID
 * @return string Site Name, or 'unknown' (in local lang) if not found
 */
function site_name($id)
{
    $sitename = db_read_column('name', $GLOBALS['dbSites'], $id);
    if (empty($sitename))
    {
        $sitename = $GLOBALS['strUnknown'];
    }

    return ($sitename);
}


/**
 * Fetches the telephone number of the given site
 * @author Ivan Lucas
 * @param int $id. the site ID
 * @return string Site telephone number
 */
function site_telephone($id)
{
    $sitename = db_read_column('telephone', $GLOBALS['dbSites'], $id);

    return ($sitename);
}


/**
 * Returns the salesperson ID of a site
 *
 * @param int $siteid ID of the site
 * @return int ID of the salesperson
 * @author Kieran Hogg
 */
function site_salespersonid($siteid)
{
    $siteid = intval($siteid);
    $salespersonid = db_read_column('owner', $GLOBALS['dbSites'], $siteid);
    return $salespersonid;
}


/**
 * Returns the salesperson's name of a site
 *
 * @param int $siteid ID of the site
 * @return string name of the salesperson
 * @author Kieran Hogg
 */
function site_salesperson($siteid)
{
    $siteid = intval($siteid);
    $salespersonid = db_read_column('owner', $GLOBALS['dbSites'], $siteid);
    return user_realname($salespersonid);
}


