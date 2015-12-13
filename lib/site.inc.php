<?php
// site.inc.php - functions relating to sites
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
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
    global $dbInventory, $db;
    $count = 0;

    $sql = "SELECT COUNT(id) FROM `{$dbInventory}` WHERE siteid='{$id}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    else list($count) = mysqli_fetch_row($result);
    mysqli_free_result($result);

    return $count;
}


/**
 * Returns yes/no if site wants to receive feedback
 * @author Carsten Jensen
 * @param int $id the id of the site
 * @return yes/no or FALSE if no results
 * @retval string yes if site wants to receive feedback
 * @retval string no if site doesn't want to receive feedback
 */
function site_feedback($id)
{
    global $dbSiteConfig, $db;
    $sql = "SELECT value FROM `{$dbSiteConfig}` WHERE siteid = {$id} AND config = 'feedback_enable' LIMIT 1";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    if (mysqli_num_rows($result) == 0)
    {
        // Site not opted out assumed yes
        $answer = "yes";
    }
    else
    {
        list($answer) = mysqli_fetch_row($result);
        $answer = strtolower($answer);
    }
    return $answer;
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



/**
 * Identified whether a site as a contract for a certain SLA or set of SLAs
 * 
 * @param int $siteid  ID of the site
 * @param array $slas Array of SLA tags
 * @author Paul Heaney
 */
function does_site_have_certain_sla_contract($siteid, $slas)
{
    $toReturn = false;
    global $CONFIG, $dbMaintenance, $dbServiceLevels, $db;

    if (!empty($slas))
    {
        $ssql = "SELECT id FROM `{$dbMaintenance}` WHERE site = '{$siteid}' AND ";

        foreach ($slas AS $s)
        {
            if (!empty($qsql)) $qsql .= " OR ";
            $qsql .= " servicelevel = {$s} ";
        }

        $ssql .= "({$qsql})";

        $sresult = mysqli_query($db, $ssql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        if (mysqli_num_rows($sresult) > 0)
        {
            $toReturn = true;
        }
    }

    return $toReturn;
}
