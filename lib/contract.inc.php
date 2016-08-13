<?php
// contract.inc.php - functions relating to contracts
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

require_once (APPLICATION_LIBPATH . 'base.inc.php');

/**
 * Picks a 'best' contract for a contact
 *
 * The function is limited in its usefulness, it will only work if you either
 * have just one contract, or just one preferred contract.
 * @author Kieran Hogg
 * @param int $contactid the ID of the contact to find the contract for
 * @return int|bool returns either the ID of the contract or FALSE if none
 */
function guess_contract_id($contactid)
{
    global $dbSupportContacts, $db;

    $contactid = intval($contactid);
    $sql = "SELECT * FROM `{$dbSupportContacts}` ";
    $sql .= "WHERE contactid = {$contactid}";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);

    $num_contracts = mysqli_num_rows($result);

    if ($num_contracts == 0)
    {
        $contractid = FALSE;
    }
    elseif ($num_contracts == 1)
    {
        $row = mysqli_fetch_object($result);
        $contractid = $row->maintenanceid;
    }
    else
    {
        //to complete as a programming exercise
    }

    return $contractid;
}


function maintenance_siteid($id)
{
    return db_read_column('site', $GLOBALS['dbMaintenance'], $id);

}


/**
 * Finds the software (skills) associated with a contract
 * @author Ivan Lucas
 * @note Wrap the php function for different versions of php
 */
function contract_software()
{
    global $db;
    $contract = intval($contract);
    $sql = "SELECT s.id
            FROM `{$GLOBALS['dbMaintenance']}` AS m,
                `{$GLOBALS['dbProducts']}` AS p,
                `{$GLOBALS['dbSoftwareProducts']}` AS sp,
                `{$GLOBALS['dbSoftware']}` AS s
            WHERE m.product=p.id
            AND p.id=sp.productid
            AND sp.softwareid=s.id ";
    $sql .= "AND (1=0 ";
    if (is_array($_SESSION['contracts']))
    {
        foreach ($_SESSION['contracts'] AS $contract)
        {
            $sql .= "OR m.id={$contract} ";
        }
    }
    $sql .= ")";

    if ($result = mysqli_query($db, $sql))
    {
        while ($row = mysqli_fetch_object($result))
        {
            $softwarearray[] = $row->id;
        }
    }

    return $softwarearray;
}


/**
 * Returns the SLA Tag of a contract
 *
 * @param int $maintid ID of the contract
 * @return String TAG of the SLA
 * @author Kieran Hogg
 */
function contract_slatag($maintid)
{
    $maintid = intval($maintid);
    $slatag = db_read_column('servicelevel', $GLOBALS['dbMaintenance'], $maintid);
    return $slatag;
}


/**
 * Outputs the product name of a contract
 *
 * @param int $maintid ID of the contract
 * @return string the name of the product
 * @author Kieran Hogg
 */
function contract_product($maintid)
{
    global $db;
    $maintid = intval($maintid);
    $productid = db_read_column('product', $GLOBALS['dbMaintenance'], $maintid);
    $sql = "SELECT name FROM `{$GLOBALS['dbProducts']}` WHERE id='{$productid}'";
    $result = mysqli_query($db, $sql);
    $productobj = mysqli_fetch_object($result);
    if (!empty($productobj->name))
    {
        return $productobj->name;
    }
    else
    {
        return $GLOBALS['strUnknown'];
    }
}


/**
 * Outputs the contract's site name
 *
 * @param int $maintid ID of the contract
 * @return string name of the site
 * @author Kieran Hogg
 */
function contract_site($maintid)
{
    global $db;
    $maintid = intval($maintid);
    $sql = "SELECT site FROM `{$GLOBALS['dbMaintenance']}` WHERE id='{$maintid}'";
    $result = mysqli_query($db, $sql);
    $maintobj = mysqli_fetch_object($result);

    $sitename = site_name($maintobj->site);
    if (!empty($sitename))
    {
        return $sitename;
    }
    else
    {
        return $GLOBALS['strUnknown'];
    }
}


/**
 * Return an array of contacts allowed to use this contract
 * @author Kieran Hogg
 * @param int $maintid - ID of the contract
 * @return array of supported contacts, NULL if none
 */
function supported_contacts($maintid)
{
    global $dbSupportContacts, $dbContacts, $db;
    $sql  = "SELECT c.forenames, c.surname, sc.contactid AS contactid ";
    $sql .= "FROM `{$dbSupportContacts}` AS sc, `{$dbContacts}` AS c ";
    $sql .= "WHERE sc.contactid=c.id AND sc.maintenanceid='{$maintid}' ";
    $sql .= "ORDER BY c.surname, c.forenames ";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    if (!empty($result))
    {
        while ($row = mysqli_fetch_object($result))
        {
            $returnarray[] = $row->contactid;
        }
        return $returnarray;
    }
    else return NULL;
}


/**
 * Return an array of contracts which non-contract contacts can see incidents
 * @author Kieran Hogg
 * @param int $siteid - ID of the site
 * @return array of supported contracts, NULL if none
 */
function all_contact_contracts($siteid)
{
    global $db;
    $sql = "SELECT DISTINCT m.id AS id
            FROM `{$GLOBALS['dbMaintenance']}` AS m
            WHERE m.site={$siteid}
            AND m.var_incident_visible_all = 'yes'";

    if ($result = mysqli_query($db, $sql))
    {
        while ($row = mysqli_fetch_object($result))
        {
            $contractsarray[] = $row->id;
        }
    }
    return $contractsarray;
}


/**
 * Returns the SLA ID of an incident
 *
 * @param int $incidentid ID of the incident
 * @return int ID of the SLA
 * @author Kieran Hogg
 */
function incident_slaid($incidentid)
{
    global $dbIncidents, $dbServiceLevels, $db;
    $incidentid = intval($incidentid);
    $slatag = db_read_column('servicelevel', $dbIncidents, $incidentid);
    $sql = "SELECT id FROM `{$dbServiceLevels}` WHERE tag = '{$slatag}' LIMIT 1";
    $result = mysqli_query($db, $sql);
    list($id) = mysqli_fetch_assoc($result);
    return $id;
}

?>
