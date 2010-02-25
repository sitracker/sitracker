<?php
// functions.inc.php - Function library and defines for SiT -Support Incident Tracker
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Authors: Ivan Lucas, <ivanlucas[at]users.sourceforge.net>
//          Tom Gerrard, <tomgerrard[at]users.sourceforge.net> - 2001 onwards
//          Martin Kilcoyne - 2000
//          Paul Heaney, <paulheaney[at]users.sourceforge.net>
//          Kieran Hogg, <kieran[at]sitracker.org>

// Many functions here simply extract various snippets of information from
// Most are legacy and can replaced by improving the pages that call them to
// use SQL joins.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

include (APPLICATION_LIBPATH . 'classes.inc.php');

include (APPLICATION_LIBPATH . 'group.class.php');
include (APPLICATION_LIBPATH . 'user.class.php');
include (APPLICATION_LIBPATH . 'contact.class.php');
include (APPLICATION_LIBPATH . 'incident.class.php');

include (APPLICATION_LIBPATH . 'ldap.inc.php');
include (APPLICATION_LIBPATH . 'base.inc.php');
include_once (APPLICATION_LIBPATH . 'billing.inc.php');
include_once (APPLICATION_LIBPATH . 'user.inc.php');
include_once (APPLICATION_LIBPATH . 'sla.inc.php');
include_once (APPLICATION_LIBPATH . 'ftp.inc.php');
include_once (APPLICATION_LIBPATH . 'tags.inc.php');
include_once (APPLICATION_LIBPATH . 'string.inc.php');
include_once (APPLICATION_LIBPATH . 'html.inc.php');
include_once (APPLICATION_LIBPATH . 'tasks.inc.php');
include_once (APPLICATION_LIBPATH . 'export.inc.php');

// function stripslashes_array($data)
// {
//     if (is_array($data))
//     {
//         foreach ($data as $key => $value)
//         {
//             $data[$key] = stripslashes_array($value);
//         }
//         return $data;
//     }
//     else
//     {
//         return stripslashes($data);
//     }
// }

if (version_compare(PHP_VERSION, "5.1.0", ">="))
{
    date_default_timezone_set($CONFIG['timezone']);
}

//Prevent Magic Quotes from affecting scripts, regardless of server settings
//Make sure when reading file data,
//PHP doesn't "magically" mangle backslashes!
set_magic_quotes_runtime(FALSE);

if (get_magic_quotes_gpc())
{

//     All these global variables are slash-encoded by default,
//     because    magic_quotes_gpc is set by default!
//     (And magic_quotes_gpc affects more than just $_GET, $_POST, and $_COOKIE)
//     We don't strip slashes from $_FILES as of 3.32 as this should be safe without
//     doing and it will break windows file paths if we do
    $_SERVER = stripslashes_array($_SERVER);
    $_GET = stripslashes_array($_GET);
    $_POST = stripslashes_array($_POST);
    $_COOKIE = stripslashes_array($_COOKIE);
    $_ENV = stripslashes_array($_ENV);
    $_REQUEST = stripslashes_array($_REQUEST);
    $HTTP_SERVER_VARS = stripslashes_array($HTTP_SERVER_VARS);
    $HTTP_GET_VARS = stripslashes_array($HTTP_GET_VARS);
    $HTTP_POST_VARS = stripslashes_array($HTTP_POST_VARS);
    $HTTP_COOKIE_VARS = stripslashes_array($HTTP_COOKIE_VARS);
    $HTTP_POST_FILES = stripslashes_array($HTTP_POST_FILES);
    $HTTP_ENV_VARS = stripslashes_array($HTTP_ENV_VARS);
    if (isset($_SESSION))
    {
        #These are unconfirmed (?)
        $_SESSION = stripslashes_array($_SESSION, '');
        $HTTP_SESSION_VARS = stripslashes_array($HTTP_SESSION_VARS, '');
    }
//     The $GLOBALS array is also slash-encoded, but when all the above are
//     changed, $GLOBALS is updated to reflect those changes.  (Therefore
//     $GLOBALS should never be modified directly).  $GLOBALS also contains
//     infinite recursion, so it's dangerous...
}


/**
    * Authenticate a user with a username/password pair
    * @author Ivan Lucas
    * @param string $username. A username
    * @param string $password. A password (non-md5)
    * @return an integer to indicate whether the user authenticated against the database
    * @retval int 0 the credentials were wrong or the user was not found.
    * @retval int 1 to indicate user is authenticated and allowed to continue.
*/
function authenticateSQL($username, $password)
{
    global $dbUsers;

    $password = md5($password);
    if ($_SESSION['auth'] == TRUE)
    {
        // Already logged in
        return 1;
    }

    // extract user
    $sql  = "SELECT id FROM `{$dbUsers}` ";
    $sql .= "WHERE username = '{$username}' AND password = '{$password}' AND status != 0 ";
    // a status of 0 means the user account is disabled
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    // return appropriate value
    if (mysql_num_rows($result) == 0)
    {
        mysql_free_result($result);
        return 0;
    }
    else
    {
        journal(CFG_LOGGING_MAX,'User Authenticated',"{$username} authenticated from " . getenv('REMOTE_ADDR'),CFG_JOURNAL_LOGIN,0);
        return 1;
    }
}


/**
    * Authenticate a user
    * @author Lea Anthony
    * @param string $username. Username
    * @param string $password. Password
    * @return an integer to indicate whether the user authenticated against any authentication backends
    * @retval bool false the credentials were wrong or the user was not found.
    * @retval bool true to indicate user is authenticated and allowed to continue.
*/
function authenticate($username, $password)
{
    global $CONFIG;
    $toReturn = false;

    if (!empty($username) AND !empty($password))
    {
	    $sql = "SELECT id, password, status, user_source FROM `{$GLOBALS['dbUsers']}` WHERE username = '{$username}'";
	    $result = mysql_query($sql);
	    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
	    if (mysql_num_rows($result) == 1)
	    {
	        // Exist in SiT DB
	        $obj = mysql_fetch_object($result);
	        if ($obj->user_source == 'sit')
	        {
	            if (md5($password) == $obj->password AND $obj->status != 0) $toReturn = true;
	            else $toReturn = false;
	        }
	        elseif ($obj->user_source == 'ldap')
	        {
	            // Auth against LDAP and sync
	            $toReturn =  authenticateLDAP($username, $password, $obj->id);
	            if ($toReturn === -1)
	            {
	                // Communication with LDAP server failed
	                if ($CONFIG['ldap_allow_cached_password'])
	                {
	                    // Use cached password
	                    if (md5($password) == $obj->password AND $obj->status != 0) $toReturn = true;
	                    else $toReturn = false;
	                }
	                else
	                {
	                    $toReturn = false;
	                }
	            }
	            elseif ($toReturn)
	            {
	                $toReturn = true;
	            }
	            else
	            {
	                $toReturn = false;
	            }
	        }
	    }
	    elseif (mysql_num_rows($result) > 1)
	    {
	    	// Multiple this should NEVER happen
	        trigger_error("Username not unique", E_USER_ERROR);
	        $toReturn = false;
	    }
	    else
	    {
	    	// Don't exist, check LDAP etc
	        if ($CONFIG['use_ldap'])
	        {
	            $toReturn =  authenticateLDAP($username, $password);
	            if ($toReturn === -1) $toReturn = false;
	        }
	    }

	    if ($toReturn)
	    {
	    	journal(CFG_LOGGING_MAX,'User Authenticated',"{$username} authenticated from " . getenv('REMOTE_ADDR'),CFG_JOURNAL_LOGIN,0);
			debug_log ("Authenticate: User authenticated",TRUE);
		}
		else
		{
			debug_log ("authenticate: User NOT authenticated",TRUE);
	    }
    }
    else
    {
    	debug_log ("Blank username or password for user thus denying access");
    	$toReturn = false;
    }

    return $toReturn;
}


function authenticateContact($username, $password)
{
    debug_log ("authenticateContact called");
    global $CONFIG;
    $toReturn = false;

    if (!empty($username) AND !empty($password))
    {
	    $sql = "SELECT id, password, contact_source, active FROM `{$GLOBALS['dbContacts']}` WHERE username = '{$username}'";
	    $result = mysql_query($sql);
	    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
	    if (mysql_num_rows($result) == 1)
	    {
	        debug_log ("Authenticate: Just one contact in db");
	        // Exists in SiT DB
	        $obj = mysql_fetch_object($result);
	        if ($obj->contact_source == 'sit')
	        {
	            if ((md5($password) == $obj->password OR $password == $obj->password) AND $obj->active == 'true') $toReturn = true;
	            else $toReturn = false;
	        }
	        elseif ($obj->contact_source == 'ldap')
	        {
	            // Auth against LDAP and sync
	            $toReturn =  authenticateLDAP($username, $password, $obj->id, false);
	            if ($toReturn === -1)
	            {
	                // Communication with LDAP server failed
	                if ($CONFIG['ldap_allow_cached_password'])
	                {
	                    debug_log ("LDAP connection failed, using cached password");
	                    // Use cached password
	                    if ((md5($password) == $obj->password OR $password == $obj->password) AND $obj->active == 'true') $toReturn = true;
	                    else $toReturn = false;
	                    debug_log ("Cached contact {$toReturn} {$password}");

	                }
	                else
	                {
	                    debug_log ("Cached passwords are not enabled");
	                	$toReturn = false;
	                }
	            }
	            elseif ($toReturn)
	            {
	            	$toReturn = true;
	            }
	            else
	            {
	            	$toReturn = false;
	            }
	        }
	        else
	        {
	        	debug_log ("Source SOMETHING ELSE this shouldn't happen'");
	            $toReturn = false;
	        }
	    }
	    elseif (mysql_num_rows($result) > 1)
	    {
	        debug_log ("Multiple");
	        // Multiple this should NEVER happen
	        trigger_error($GLOBALS['strUsernameNotUnique'], E_USER_ERROR);
	        $toReturn = false;
	    }
	    else
	    {
	        debug_log ("Authenticate: No matching contact '$username' found in db");
	        // Don't exist, check LDAP etc
	        if ($CONFIG['use_ldap'] AND !empty($CONFIG['ldap_customer_group']))
	        {
	            $toReturn =  authenticateLDAP($username, $password, 0, false);
	            if ($toReturn === -1) $toReturn = false;
	        }
	    }
    }
    else
    {
    	debug_log ("Blank username or password for user thus denying access");
        $toReturn = false;
    }

    debug_log ("authenticateContact returning {$toReturn}");
    return $toReturn;
}

/**
    * See if a customer exists in the database
    * @author Lea Anthony
    * @param string $username. Username of customer
    * @retval bool TRUE exists in db
    * @retval bool FALSE does not exist in db
*/
function customerExistsInDB($username)
{
    global $dbContacts;
    $exists = 0;
    $sql  = "SELECT id FROM `{$dbContacts}` WHERE username='$username'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    while( $res = mysql_fetch_array($result) )
    {
        $exists = 1;
    }

    return $exists;
}


/**
    * Returns a specified column from a specified table in the database given an ID primary key
    * @author Ivan Lucas
    * @param string $column a database column
    * @param string $table a database table
    * @param int $id the primary key / id column
    * @return A column from the database
    * @note it's not always efficient to read a single column at a time, but when you only need
    *  one column, this is handy
*/
function db_read_column($column, $table, $id)
{
    $sql = "SELECT `$column` FROM `{$table}` WHERE id ='$id' LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) == 0)
    {
        $column = FALSE;
    }
    else
    {
        list($column) = mysql_fetch_row($result);
    }
    return $column;
}


/**
    * @author Ivan Lucas
*/
function permission_name($permissionid)
{
    global $dbPermissions;
    $name = db_read_column('name', $dbPermissions, $permissionid);
    if (empty($name)) $name = $GLOBALS['strUnknown'];
    return $name;
}


/**
    * Get the name associated with software ID / skill ID
    * @author Ivan Lucas
    * @param int $softwareid
    * @returns string. Skill/Software Name
    * @note Software was renamed skills for v3.30
*/
function software_name($softwareid)
{
    global $now, $dbSoftware, $strEOL, $strEndOfLife;

    $sql = "SELECT * FROM `{$dbSoftware}` WHERE id = '{$softwareid}'";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) >= 1)
    {
        $software = mysql_fetch_object($result);
        $lifetime_end = mysql2date($software->lifetime_end);
        if ($lifetime_end > 0 AND $lifetime_end < $now)
        {
            $name = "<span class='deleted'>{$software->name}</span> (<abbr title='{$strEndOfLife}'>{$strEOL}</abbr>)";
        }
        else
        {
            $name = $software->name;
        }
    }
    else
    {
        $name = $GLOBALS['strUnknown'];
    }

    return $name;
}


/**
    * Find a contacts real name
    * @author Ivan Lucas
    * @param int $id. Contact ID
    * @returns string. Full name or 'Unknown'
*/
function contact_realname($id)
{
    global $dbContacts;
    $sql = "SELECT forenames, surname FROM `{$dbContacts}` WHERE id='$id'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) == 0)
    {
        mysql_free_result($result);
        return ($GLOBALS['strUnknown']);
    }
    else
    {
        $contact = mysql_fetch_object($result);
        $realname = "{$contact->forenames} {$contact->surname}";
        mysql_free_result($result);
        return $realname;
    }
}


/**
    * Return a contacts site name
    * @author Ivan Lucas
    * @param int $id. Contact ID
    * @returns string. Full site name or 'Unknown'
    * @note this returns the site _NAME_ not the siteid for the site id use contact_siteid()
*/
function contact_site($id)
{
    global $dbContacts, $dbSites;
    //
    $sql = "SELECT s.name FROM `{$dbContacts}` AS c, `{$dbSites}` AS s WHERE c.siteid = s.id AND c.id = '$id'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) == 0)
    {
        mysql_free_result($result);
        return $GLOBALS['strUnknown'];
    }
    else
    {
        list($contactsite) = mysql_fetch_row($result);
        mysql_free_result($result);
        $contactsite = $contactsite;
        return $contactsite;
    }
}


/**
    * Return a contacts site ID
    * @author Ivan Lucas
    * @param int $id. Contact ID
    * @returns int. Site ID
*/
function contact_siteid($id)
{
    return db_read_column('siteid', $GLOBALS['dbContacts'], $id);
}


/**
    * Return a contacts email address
    * @author Ivan Lucas
    * @param int $id. Contact ID
    * @returns string. Email address
*/
function contact_email($id)
{
    return db_read_column('email', $GLOBALS['dbContacts'], $id);
}


/**
    * Return a contacts phone number
    * @author Ivan Lucas
    * @param integer $id. Contact ID
    * @returns string. Phone number
*/
function contact_phone($id)
{
    return db_read_column('phone', $GLOBALS['dbContacts'], $id);
}


/**
    * Return a contacts fax number
    * @author Ivan Lucas
    * @param int $id. Contact ID
    * @returns string. Fax number
*/
function contact_fax($id)
{
    return db_read_column('fax', $GLOBALS['dbContacts'], $id);
}


/**
    * Return the number of incidents ever logged against a contact
    * @author Ivan Lucas
    * @param int $id. Contact ID
    * @returns int.
*/
function contact_count_incidents($id)
{
    global $dbIncidents;
    $count = 0;

    $sql = "SELECT COUNT(id) FROM `{$dbIncidents}` WHERE contact='$id'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    else list($count) = mysql_fetch_row($result);
    mysql_free_result($result);

    return $count;
}


/**
    * Return the number of incidents ever logged against a site
    * @author Kieran
    * @param int $id. Site ID
    * @returns int.
*/
function site_count_incidents($id)
{
    global $dbIncidents, $dbContacts;
    $id = intval($id);
    $count = 0;

    $sql = "SELECT COUNT(i.id) FROM `{$dbIncidents}` AS i, `{$dbContacts}` as c ";
    $sql .= "WHERE i.contact = c.id ";
    $sql .= "AND c.siteid='$id'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    else list($count) = mysql_fetch_row($result);
    mysql_free_result($result);

    return $count;
}


/**
    * Return the number of inventory items for a site
    * @author Kieran
    * @param int $id. Site ID
    * @returns int.
*/
function site_count_inventory_items($id)
{
    global $dbInventory;
    $count = 0;

    $sql = "SELECT COUNT(id) FROM `{$dbInventory}` WHERE siteid='$id'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    else list($count) = mysql_fetch_row($result);
    mysql_free_result($result);

    return $count;
}


/**
    * Return the number of inventory items for a contact
    * @author Kieran
    * @param int $id. Contact ID
    * @returns int.
*/
function contact_count_inventory_items($id)
{
    global $dbInventory;
    $count = 0;

    $sql = "SELECT COUNT(id) FROM `{$dbInventory}` WHERE contactid='$id'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    else list($count) = mysql_fetch_row($result);
    mysql_free_result($result);

    return $count;
}



/**
    * The number representing the total number of currently OPEN incidents submitted by a given contact.
    * @author Ivan Lucas
    * @param int $id. The Contact ID to check
    * @returns integer. The number of currently OPEN incidents for the given contact
*/
function contact_count_open_incidents($id)
{
    global $dbIncidents;
    $sql = "SELECT COUNT(id) FROM `{$dbIncidents}` WHERE contact=$id AND status<>2";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    list($count) = mysql_fetch_row($result);
    mysql_free_result($result);

    return $count;
}


/**
    * Creates a vcard electronic business card for the given contact
    * @author Ivan Lucas
    * @param int $id Contact ID
    * @returns string vcard
*/
function contact_vcard($id)
{
    global $dbContacts, $dbSites;
    $sql = "SELECT *, s.name AS sitename, s.address1 AS siteaddress1, s.address2 AS siteaddress2, ";
    $sql .= "s.city AS sitecity, s.county AS sitecounty, s.country AS sitecountry, s.postcode AS sitepostcode ";
    $sql .= "FROM `{$dbContacts}` AS c, `{$dbSites}` AS s ";
    $sql .= "WHERE c.siteid = s.id AND c.id = '$id' LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $contact = mysql_fetch_object($result);
    $vcard = "BEGIN:VCARD\r\n";
    $vcard .= "N:{$contact->surname};{$contact->forenames};{$contact->courtesytitle}\r\n";
    $vcard .= "FN:{$contact->forenames} {$contact->surname}\r\n";
    if (!empty($contact->jobtitle)) $vcard .= "TITLE:{$contact->jobtitle}\r\n";
    if (!empty($contact->sitename)) $vcard .= "ORG:{$contact->sitename}\r\n";
    if ($contact->dataprotection_phone != 'Yes') $vcard .= "TEL;TYPE=WORK:{$contact->phone}\r\n";
    if ($contact->dataprotection_phone != 'Yes' && !empty($contact->fax))
    {
        $vcard .= "TEL;TYPE=WORK;TYPE=FAX:{$contact->fax}\r\n";
    }

    if ($contact->dataprotection_phone != 'Yes' && !empty($contact->mobile))
    {
        $vcard .= "TEL;TYPE=WORK;TYPE=CELL:{$contact->mobile}\r\n";
    }

    if ($contact->dataprotection_email != 'Yes' && !empty($contact->email))
    {
        $vcard .= "EMAIL;TYPE=INTERNET:{$contact->email}\r\n";
    }

    if ($contact->dataprotection_address != 'Yes')
    {
        if ($contact->address1 != '')
        {
            $vcard .= "ADR;WORK:{$contact->address1};{$contact->address2};{$contact->city};{$contact->county};{$contact->postcode};{$contact->country}\r\n";
        }
        else
        {
            $vcard .= "ADR;WORK:{$contact->siteaddress1};{$contact->siteaddress2};{$contact->sitecity};{$contact->sitecounty};{$contact->sitepostcode};{$contact->sitecountry}\r\n";
        }
    }
    if (!empty($contact->notes))
    {
        $vcard .= "NOTE:{$contact->notes}\r\n";
    }

    $vcard .= "REV:".iso_8601_date($contact->timestamp_modified)."\r\n";
    $vcard .= "END:VCARD\r\n";
    return $vcard;
}


/**
    * @author Ivan Lucas
    * @param int $id Incident ID
    * @returns integer. UserID of the user that currently owns the incident
*/
function incident_owner($id)
{
    return db_read_column('owner', $GLOBALS['dbIncidents'], $id);
}


/**
    * @author Ivan Lucas
    * @param int $id Incident ID
    * @returns integer. UserID of the user that currently temporarily owns the incident
*/
function incident_towner($id)
{
    return db_read_column('towner', $GLOBALS['dbIncidents'], $id);
}


/**
    * @author Ivan Lucas
    * @param int $id Incident ID
    * @returns integer. ContactID of the contact this incident is logged against
*/
function incident_contact($id)
{
    return db_read_column('contact', $GLOBALS['dbIncidents'], $id);
}


/**
    * @author Ivan Lucas
    * @param int $id Incident ID
    * @returns integer. Contract ID of the maintenance contract this incident is logged against
*/
function incident_maintid($id)
{
    $maintid = db_read_column('maintenanceid', $GLOBALS['dbIncidents'], $id);
    if ($maintid == '')
    {
        trigger_error("!Error: No matching record while reading in incident_maintid() Incident ID: {$id}", E_USER_WARNING);
    }
    else
    {
        return ($maintid);
    }
}


/**
    * @author Ivan Lucas
    * @param int $id Incident ID
    * @returns string. Title of the incident
*/
function incident_title($id)
{
    return db_read_column('title', $GLOBALS['dbIncidents'], $id);
}


/**
    * @author Ivan Lucas
    * @param int $id Incident ID
    * @returns id. Current incident status ID
*/
function incident_status($id)
{
    return db_read_column('status', $GLOBALS['dbIncidents'], $id);
}


/**
    * @author Ivan Lucas
    * @param int $id Incident ID
    * @returns id. Current incident Priority ID
*/
function incident_priority($id)
{
    return db_read_column('priority', $GLOBALS['dbIncidents'], $id);
}


/**
    * @author Ivan Lucas
    * @param int $id Incident ID
    * @returns id. Current incident external ID
*/
function incident_externalid($id)
{
    return db_read_column('externalid', $GLOBALS['dbIncidents'], $id);
}


/**
    * @author Ivan Lucas
    * @param int $id Incident ID
    * @returns string. Current incident external engineer
*/
function incident_externalengineer($id)
{
    return db_read_column('externalengineer', $GLOBALS['dbIncidents'], $id);
}


/**
    * @author Ivan Lucas
    * @param int $id Incident ID
    * @returns string. Current incident external email address
*/
function incident_externalemail($id)
{
    return db_read_column('externalemail', $GLOBALS['dbIncidents'], $id);
}


/**
    * @author Ivan Lucas
    * @param int $id Incident ID
    * @returns string. Current incident CC email address
*/
function incident_ccemail($id)
{
    return db_read_column('ccemail', $GLOBALS['dbIncidents'], $id);
}


/**
    * @author Ivan Lucas
    * @param int $id Incident ID
    * @returns int. UNIX Timestamp of the time of the next action for this incident
*/
function incident_timeofnextaction($id)
{
    return db_read_column('timeofnextaction', $GLOBALS['dbIncidents'], $id);
}


/**
    * Returns a string of HTML nicely formatted for the incident details page containing any additional
    * product info for the given incident.
    * @author Ivan Lucas
    * @param int $incidentid The incident ID
    * @returns string HTML
*/
function incident_productinfo_html($incidentid)
{
    global $dbProductInfo, $dbIncidentProductInfo, $strNoProductInfo;

    // TODO extract appropriate product info rather than *
    $sql  = "SELECT *, TRIM(incidentproductinfo.information) AS info FROM `{$dbProductInfo}` AS p, {$dbIncidentProductInfo}` ipi ";
    $sql .= "WHERE incidentid = $incidentid AND productinfoid = p.id AND TRIM(p.information) !='' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) == 0)
    {
        return ('<tr><td>{$strNoProductInfo}</td><td>{$strNoProductInfo}</td></tr>');
    }
    else
    {
        // generate HTML
        while ($productinfo = mysql_fetch_object($result))
        {
            if (!empty($productinfo->info))
            {
                $html = "<tr><th>{$productinfo->moreinformation}:</th><td>";
                $html .= urlencode($productinfo->info);
                $html .= "</td></tr>\n";
            }
        }
        echo $html;
    }
}


/**
 * prints the HTML for a drop down list of contacts, with the given name
 * and with the given id  selected.
 * @author Ivan Lucas
 */
function contact_drop_down($name, $id, $showsite = FALSE, $required = FALSE)
{
    global $dbContacts, $dbSites;
    if ($showsite)
    {
        $sql  = "SELECT c.id AS contactid, s.id AS siteid, surname, forenames, ";
        $sql .= "s.name AS sitename, s.department AS department ";
        $sql .= "FROM `{$dbContacts}` AS c, `{$dbSites}` AS s WHERE c.siteid = s.id AND c.active = 'true' ";
        $sql .= "AND s.active = 'true' ";
        $sql .= "ORDER BY s.name, s.department, surname ASC, forenames ASC";
    }
    else
    {
        $sql  = "SELECT c.id AS contactid, surname, forenames FROM `{$dbContacts}` AS c, `{$dbSites}` AS s ";
        $sql .= "WHERE c.siteid = s.id AND s.active = 'true' AND c.active = 'true' ";
        $sql .= "ORDER BY forenames ASC, surname ASC";
    }

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $html = "<select name='$name' id='$name'";
    if ($required)
    {
        $html .= " class='required' ";
    }
    $html .= ">\n";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    $prevsite=0;
    while ($contacts = mysql_fetch_object($result))
    {
        if ($showsite AND $prevsite != $contacts->siteid AND $prevsite != 0)
        {
            $html .= "</optgroup>\n";
        }

        if ($showsite AND $prevsite != $contacts->siteid)
        {
            $html .= "<optgroup label='".htmlentities($contacts->sitename, ENT_COMPAT, 'UTF-8').", ".htmlentities($contacts->department, ENT_COMPAT, $GLOBALS['i18ncharset'])."'>";
        }

        $realname = "{$contacts->forenames} {$contacts->surname}";
        $html .= "<option ";
        if ($contacts->contactid == $id)
        {
            $html .= "selected='selected' ";
        }
        $html .= "value='{$contacts->contactid}'>{$realname}";
        $html .= "</option>\n";

        $prevsite = $contacts->siteid;
    }
    if ($showsite)
    {
        $html.= "</optgroup>";
    }

    $html .= "</select>\n";
    return $html;
}


/**
 * prints the HTML for a drop down list of contacts along with their site, with the given name and
 * and with the given id selected.
 * @author Ivan Lucas
 * @param string $name. The name of the field
 * @param int $id. Select this contactID by default
 * @param int $siteid. (optional) Filter list to show contacts from this siteID only
 * @param mixed $exclude int|array (optional) Do not show this contactID in the list, accepts an integer or array of integers
 * @param bool $showsite (optional) Suffix the name with the site name
 * @param bool $allownone (optional) Allow 'none' to be selected (blank value)
 * @returns string.  HTML select
 */
function contact_site_drop_down($name, $id, $siteid='', $exclude='', $showsite=TRUE, $allownone=FALSE)
{
    global $dbContacts, $dbSites;
    $sql  = "SELECT c.id AS contactid, forenames, surname, siteid, s.name AS sitename ";
    $sql .= "FROM `{$dbContacts}` AS c, `{$dbSites}` AS s ";
    $sql .= "WHERE c.siteid = s.id AND c.active = 'true' AND s.active = 'true' ";
    if (!empty($siteid)) $sql .= "AND s.id='$siteid' ";
    if (!empty($exclude))
    {
        if (is_array($exclude))
        {
            foreach ($exclude AS $contactid)
            {
                $sql .= "AND c.id != $contactid ";
            }
        }
        else
        {
            $sql .= "AND c.id != $exclude ";
        }
    }
    $sql .= "ORDER BY surname ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $html = "<select name='$name'>";
    if (mysql_num_rows($result) > 0)
    {
        if ($allownone) $html .= "<option value='' selected='selected'>{$GLOBALS['strNone']}</option>";
        while ($contacts = mysql_fetch_object($result))
        {
            $html .= "<option ";
            if ($contacts->contactid == $id)
            {
                $html .= "selected='selected' ";
            }

            $html .= "value='{$contacts->contactid}'>";
            if ($showsite)
            {
                $html .= htmlspecialchars("{$contacts->surname}, {$contacts->forenames} - {$contacts->sitename}");
            }
            else
            {
                $html .= htmlspecialchars("{$contacts->surname}, {$contacts->forenames}");
            }
            $html .= "</option>\n";
        }
    }
    else $html .= "<option value=''>{$GLOBALS['strNone']}</option>";

    $html .= "</select>\n";
    return $html;
}


/**
 * HTML for a drop down list of products
 * @author Ivan Lucas
 * @param string $name. name/id to use for the select element
 * @param int $id. Product ID
 * @param bool $required.
 * @returns string. HTML select
 * @note With the given name and with the given id selected.
 */
function product_drop_down($name, $id, $required = FALSE)
{
    global $dbProducts;
    // extract products
    $sql  = "SELECT id, name FROM `{$dbProducts}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $html = "<select name='{$name}' id='{$name}'";
    if ($required)
    {
        $html .= " class='required' ";
    }
    $html .= ">";


    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($products = mysql_fetch_object($result))
    {
        $html .= "<option value='{$products->id}'";
        if ($products->id == $id)
        {
            $html .= " selected='selected'";
        }
        $html .= ">{$products->name}</option>\n";
    }
    $html .= "</select>\n";
    return $html;

}


/**
 * HTML for a drop down list of skills (was called software)
 * @author Ivan Lucas
 * @param string $name. name/id to use for the select element
 * @param int $id. Software ID
 * @returns HTML select
 */
function skill_drop_down($name, $id)
{
    global $now, $dbSoftware, $strEOL;

    // extract software
    $sql  = "SELECT id, name, lifetime_end FROM `{$dbSoftware}` ";
    $sql .= "ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $html = "<select name='{$name}' id='{$name}' >";

    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'>{$GLOBALS['strNone']}</option>\n";
    }

    while ($software = mysql_fetch_object($result))
    {
        $html .= "<option value='{$software->id}'";
        if ($software->id == $id)
        {
            $html .= " selected='selected'";
        }

        $html .= ">{$software->name}";
        $lifetime_start = mysql2date($software->lifetime_start);
        $lifetime_end = mysql2date($software->lifetime_end);
        if ($lifetime_end > 0 AND $lifetime_end < $now)
        {
            $html .= " ({$strEOL})";
        }
        $html .= "</option>\n";
    }
    $html .= "</select>\n";

    return $html;
}



/**
 * Generates a HTML dropdown of software products
 * @author Kieran Hogg
 * @param string $name. name/id to use for the select element
 * @returns HTML select
 */
function softwareproduct_drop_down($name, $id, $productid, $visibility='internal')
{
    global $dbSoftware, $dbSoftwareProducts;
    // extract software
    $sql  = "SELECT id, name FROM `{$dbSoftware}` AS s, ";
    $sql .= "`{$dbSoftwareProducts}` AS sp WHERE s.id = sp.softwareid ";
    $sql .= "AND productid = '$productid' ";
    $sql .= "ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) >=1)
    {
        $html = "<select name='$name' id='$name'>";

        if ($visibility == 'internal' AND $id == 0)
        {
            $html .= "<option selected='selected' value='0'></option>\n";
        }
        elseif ($visiblity = 'external' AND $id == 0)
        {
            $html .= "<option selected='selected' value=''>{$GLOBALS['strUnknown']}</option>\n";
        }

        while ($software = mysql_fetch_object($result))
        {
            $html .= "<option";
            if ($software->id == $id)
            {
                $html .= " selected='selected'";
            }
            $html .= " value='{$software->id}'>{$software->name}</option>\n";
        }
        $html .= "</select>\n";
    }
    else
    {
        $html = "-";
    }

    return $html;
}


/**
 * A HTML Select listbox for vendors
 * @author Ivan Lucas
 * @param string $name. name/id to use for the select element
 * @param int $id. Vendor ID to preselect
 * @returns HTML select
 */
function vendor_drop_down($name, $id)
{
    global $dbVendors;
    $sql = "SELECT id, name FROM `{$dbVendors}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $html = "<select name='$name'>";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($row = mysql_fetch_object($result))
    {
        $html .= "<option";
        if ($row->id == $id)
        {
            $html .= " selected='selected'";
        }
        $html .= " value='{$row->id}'>{$row->name}</option>\n";
    }
    $html .= "</select>";

    return $html;
}


/**
 * A HTML Select listbox for Site Types
 * @author Ivan Lucas
 * @param string $name. name/id to use for the select element
 * @param int $id. Site Type ID to preselect
 * @todo TODO i18n needed site types
 * @returns HTML select
 */
function sitetype_drop_down($name, $id)
{
    global $dbSiteTypes;
    $sql = "SELECT typeid, typename FROM `{$dbSiteTypes}` ORDER BY typename ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $html .= "<select name='$name'>\n";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($obj = mysql_fetch_object($result))
    {
        $html .= "<option ";
        if ($obj->typeid == $id)
        {
            $html .="selected='selected' ";
        }

        $html .= "value='{$obj->typeid}'>{$obj->typename}</option>\n";
    }
    $html .= "</select>";
    return $html;
}


/**
 * Returns the HTML for a drop down list of upported products for the given contact and with the
 * given name and with the given product selected
 * @author Ivan Lucas
 * @todo FIXME this should use the contract and not the contact
 */
function supported_product_drop_down($name, $contactid, $productid)
{
    global $CONFIG, $dbSupportContacts, $dbMaintenance, $dbProducts, $strXIncidentsLeft;

    $sql = "SELECT *, p.id AS productid, p.name AS productname FROM `{$dbSupportContacts}` AS sc, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p ";
    $sql .= "WHERE sc.maintenanceid = m.id AND m.product = p.id ";
    $sql .= "AND sc.contactid='$contactid'";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if ($CONFIG['debug']) $html .= "<!-- Original product {$productid}-->";
    $html .= "<select name=\"$name\">\n";
    if ($productid == 0)
    {
        $html .= "<option selected='selected' value='0'>No Contract - Not Product Related</option>\n";
    }

    if ($productid == -1)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($products = mysql_fetch_objecy($result))
    {
        $remainingstring = sprintf($strXIncidentsLeft, incidents_remaining($products->incidentpoolid));
        $html .= "<option ";
        if ($productid == $products->productid)
        {
            $html .= "selected='selected' ";
        }
        $html .= "value='{$products->productid}'>";
        $html .= servicelevel_name($products->servicelevelid)." ".$products->productname.", Exp:".date($CONFIG['dateformat_shortdate'], $products->expirydate).", $remainingstring";
        $html .= "</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * A HTML Select listbox for user roles
 * @author Ivan Lucas
 * @param string $name. name to use for the select element
 * @param int $id. Role ID to preselect
 * @returns HTML select
 */
function role_drop_down($name, $id)
{

    global $dbRoles;
    $sql  = "SELECT id, rolename FROM `{$dbRoles}` ORDER BY rolename ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $html = "<select name='{$name}'>";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($role = mysql_fetch_object($result))
    {
        $html .= "<option value='{$role->id}'";
        if ($role->id == $id)
        {
            $html .= " selected='selected'";
        }

        $html .= ">{$role->rolename}</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * A HTML Select listbox for user groups
 * @author Ivan Lucas
 * @param string $name. name attribute to use for the select element
 * @param int $selected.  Group ID to preselect
 * @returns HTML select
 */
function group_drop_down($name, $selected)
{
    global $grouparr, $numgroups;
    $html = "<select name='$name'>";
    $html .= "<option value='0'>{$GLOBALS['strNone']}</option>\n";
    if ($numgroups >= 1)
    {
        foreach ($grouparr AS $groupid => $groupname)
        {
            $html .= "<option value='$groupid'";
            if ($groupid == $selected)
            {
                $html .= " selected='selected'";
            }
            $html .= ">$groupname</option>\n";
        }
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * A HTML Form and Select listbox for user groups, with javascript to reload page
 * @param int $selected. Group ID to preselect
 * @param string $urlargs. (Optional) text to pass after the '?' in the url (parameters)
 * @returns int Number of groups found
 * @note outputs a HTML form directly
 */
function group_selector($selected, $urlargs='')
{
    $gsql = "SELECT * FROM `{$GLOBALS['dbGroups']}` ORDER BY name";
    $gresult = mysql_query($gsql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    while ($group = mysql_fetch_object($gresult))
    {
        $grouparr[$group->id] = $group->name;
    }
    $numgroups = mysql_num_rows($gresult);

    if (!empty($urlargs)) $urlargs = "&amp;{$urlargs}";
    if ($numgroups >= 1)
    {
        echo "<form action='{$_SERVER['PHP_SELF']}?{$urlargs}' class='filterform' method='get'>";
        echo "{$GLOBALS['strGroup']}: <select name='choosegroup' onchange='window.location.href=this.options[this.selectedIndex].value'>";
        echo "<option value='{$_SERVER['PHP_SELF']}?gid=all{$urlargs}'";
        if ($selected == 'all') echo " selected='selected'";
        echo ">{$GLOBALS['strAll']}</option>\n";
        echo "<option value='{$_SERVER['PHP_SELF']}?gid=allonline{$urlargs}'";
        if ($selected == 'allonline') echo " selected='selected'";
        echo ">{$GLOBALS['strAllOnline']}</option>\n";
        foreach ($grouparr AS $groupid => $groupname)
        {
            echo "<option value='{$_SERVER['PHP_SELF']}?gid={$groupid}{$urlargs}'";
            if ($groupid == $selected) echo " selected='selected'";
            echo ">{$groupname}</option>\n";
        }
        echo "<option value='{$_SERVER['PHP_SELF']}?gid=0{$urlargs}'";
        if ($selected === '0') echo " selected='selected'";
        echo ">{$GLOBALS['strUsersNoGroup']}</option>\n";
        echo "</select>\n";
        echo "</form>\n";
    }

    return $numgroups;
}


/**
 * Return HTML for a box to select interface style/theme
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. Interface style ID
 * @returns string.  HTML
 */
function interfacestyle_drop_down($name, $id)
{
    global $dbInterfaceStyles;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbInterfaceStyles}` ORDER BY name ASC";
    $result = mysql_query($sql);
    $html = "<select name=\"{$name}\">";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($styles = mysql_fetch_object($result))
    {
        $html .= "<option ";
        if ($styles->id == $id)
        {
            $html .= "selected='selected'";
        }

        $html .= " value=\"{$styles->id}\">{$styles->name}</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * Retrieve cssurl and headerhtml for given interface style
 * @author Ivan Lucas
 * @param int $id. Interface style ID
 * @returns asoc array.
 */
function interface_style($id)
{
    global $CONFIG, $dbInterfaceStyles;

    $sql  = "SELECT cssurl, headerhtml FROM `{$dbInterfaceStyles}` WHERE id='$id'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) == 0)
    {
        mysql_free_result($result);
        $style = (array($CONFIG['default_css_url'],''));  // default style
    }
    else
    {
        $style = mysql_fetch_assoc($result);
        mysql_free_result($result);
    }

    if (empty($style))
    {
        $style = (array($CONFIG['default_css_url'],''));  // default style
    }

    return ($style);
}


/**
 * prints the HTML for a drop down list of incident status names (EXCLUDING 'CLOSED'),
 * with the given name and with the given id selected.
 * @author Ivan Lucas
 * @param string $name. Text to use for the HTML select name and id attributes
 * @param int $id. Status ID to preselect
 * @param bool $disabled. Disable the select box when TRUE
 * @returns string. HTML.
 */
function incidentstatus_drop_down($name, $id, $disabled = FALSE)
{
    global $dbIncidentStatus;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbIncidentStatus}` WHERE id<>2 AND id<>7 AND id<>10 ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) < 1)
    {
        trigger_error("Zero rows returned",E_USER_WARNING);
    }

    $html = "<select id='{$name}' name='{$name}'";
    if ($disabled)
    {
        $html .= " disabled='disabled' ";
    }
    $html .= ">";
    // if ($id == 0) $html .= "<option selected='selected' value='0'></option>\n";
    while ($statuses = mysql_fetch_object($result))
    {
        $html .= "<option ";
        if ($statuses->id == $id)
        {
            $html .= "selected='selected' ";
        }

        $html .= "value='{$statuses->id}'";
        $html .= ">{$GLOBALS[$statuses->name]}</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * Return HTML for a select box of closing statuses
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of Closing Status to pre-select. None selected if 0 or blank.
 * @todo Requires database i18n
 * @returns string. HTML
 */
function closingstatus_drop_down($name, $id, $required = FALSE)
{
    global $dbClosingStatus;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbClosingStatus}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $html = "<select name='{$name}'";
    if ($required)
    {
        $html .= " class='required' ";
    }
    $html .= ">";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($statuses = mysql_fetch_object($result))
    {
        $html .= "<option ";
        if ($statuses->id == $id)
        {
            $html .= "selected='selected' ";
        }
        $html .= "value='{$statuses->id}'>";
        if (isset($GLOBALS[$statuses->name]))
        {
            $html .= $GLOBALS[$statuses->name];
        }
        else
        {
            $html .= $statuses->name;
        }
        $html .= "</option>\n";
    }
    $html .= "</select>\n";

    return $html;
}


/**
 * Return HTML for a select box of user statuses
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of User Status to pre-select. None selected if 0 or blank.
 * @param bool $userdisable. (optional). When TRUE an additional option is given to allow disabling of accounts
 * @returns string. HTML
 */
function userstatus_drop_down($name, $id, $userdisable = FALSE)
{
    global $dbUserStatus;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbUserStatus}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $html = "<select name='$name'>\n";
    if ($userdisable)
    {
        $html .= "<option class='disable' selected='selected' value='0'>ACCOUNT DISABLED</option>\n";
    }

    while ($statuses = mysql_fetch_object($result))
    {
        if ($statuses->id > 0)
        {
            $html .= "<option ";
            if ($statuses->id == $id)
            {
                $html .= "selected='selected' ";
            }
            $html .= "value='{$statuses->id}'>";
            $html .= "{$GLOBALS[$statuses->name]}</option>\n";
        }
    }
    $html .= "</select>\n";

    return $html;
}


/**
 * Return HTML for a select box of user statuses with javascript to effect changes immediately
 * Includes two extra options for setting Accepting yes/no
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of User Status to pre-select. None selected if 0 or blank.
 * @returns string. HTML
 */
function userstatus_bardrop_down($name, $id)
{
    global $dbUserStatus;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbUserStatus}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $html = "<select name='$name' title='{$GLOBALS['strSetYourStatus']}' onchange=\"if ";
    $html .= "(this.options[this.selectedIndex].value != 'null') { ";
    $html .= "window.open(this.options[this.selectedIndex].value,'_top') }\">";
    $html .= "\n";
    while ($statuses = mysql_fetch_object($result))
    {
        if ($statuses->id > 0)
        {
            $html .= "<option ";
            if ($statuses->id == $id)
            {
                $html .= "selected='selected' ";
            }

            $html .= "value='set_user_status.php?mode=setstatus&amp;";
            $html .= "userstatus={$statuses->id}'>";
            $html .= "{$GLOBALS[$statuses->name]}</option>\n";
        }
    }
    $html .= "<option value='set_user_status.php?mode=setaccepting";
    $html .= "&amp;accepting=Yes' class='enable seperator'>";
    $html .= "{$GLOBALS['strAccepting']}</option>\n";
    $html .= "<option value='set_user_status.php?mode=setaccepting&amp;";
    $html .= "accepting=No' class='disable'>{$GLOBALS['strNotAccepting']}";
    $html .= "</option></select>\n";

    return $html;
}


/**
 * Return HTML for a select box of user email templates
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of Template to pre-select. None selected if 0 or blank.
 * @param string $type. Type to display.
 * @returns string. HTML
 */
function emailtemplate_drop_down($name, $id, $type)
{
    global $dbEmailTemplates;
    // INL 22Apr05 Added a filter to only show user templates

    $sql  = "SELECT id, name, description FROM `{$dbEmailTemplates}` WHERE type='{$type}' ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $html = "<select name=\"{$name}\">";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($template = mysql_fetch_object($result))
    {
        $html .= "<option ";
        if (!empty($template->description))
        {
            $html .= "title='{$template->description}' ";
        }

        if ($template->id == $id)
        {
            $html .= "selected='selected' ";
        }
        $html .= "value='{$template->id}'>{$template->name}</option>";
        $html .= "\n";
    }
    $html .= "</select>\n";

    return $html;
}


/**
 * Return HTML for a select box of priority names (with icons)
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of priority to pre-select. None selected if 0 or blank.
 * @param int $max. The maximum priority ID to list.
 * @param bool $disable. Disable the control when TRUE.
 * @returns string. HTML
 */
function priority_drop_down($name, $id, $max=4, $disable = FALSE)
{
    global $CONFIG, $iconset;
    // INL 8Oct02 - Removed DB Query
    $html = "<select id='priority' name='$name' ";
    if ($disable)
    {
        $html .= "disabled='disabled'";
    }

    $html .= ">";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    $html .= "<option style='text-indent: 14px; background-image: url({$CONFIG['application_webpath']}images/low_priority.gif); background-repeat:no-repeat;' value='1'";
    if ($id == 1)
    {
        $html .= " selected='selected'";
    }

    $html .= ">{$GLOBALS['strLow']}</option>\n";
    $html .= "<option style='text-indent: 14px; background-image: url({$CONFIG['application_webpath']}images/med_priority.gif); background-repeat:no-repeat;' value='2'";
    if ($id == 2)
    {
        $html .= " selected='selected'";
    }

    $html .= ">{$GLOBALS['strMedium']}</option>\n";
    $html .= "<option style='text-indent: 14px; background-image: url({$CONFIG['application_webpath']}images/high_priority.gif); background-repeat:no-repeat;' value='3'";
    if ($id==3)
    {
        $html .= " selected='selected'";
    }

    $html .= ">{$GLOBALS['strHigh']}</option>\n";
    if ($max >= 4)
    {
        $html .= "<option style='text-indent: 14px; background-image: url({$CONFIG['application_webpath']}images/crit_priority.gif); background-repeat:no-repeat;' value='4'";
        if ($id==4)
        {
            $html .= " selected='selected'";
        }
        $html .= ">{$GLOBALS['strCritical']}</option>\n";
    }
    $html .= "</select>\n";

    return $html;
}


/**
 * Return HTML for a select box for accepting yes/no. The given user's accepting status is displayed.
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $userid. The user ID to check
 * @returns string. HTML
 */
function accepting_drop_down($name, $userid)
{
    if (user_accepting($userid) == "Yes")
    {
        $html = "<select name=\"$name\">\n";
        $html .= "<option selected='selected' value=\"Yes\">{$GLOBALS['strYes']}</option>\n";
        $html .= "<option value=\"No\">{$GLOBALS['strNo']}</option>\n";
        $html .= "</select>\n";
    }
    else
    {
        $html = "<select name=\"$name\">\n";
        $html .= "<option value=\"Yes\">{$GLOBALS['strYes']}</option>\n";
        $html .= "<option selected='selected' value=\"No\">{$GLOBALS['strNo']}</option>\n";
        $html .= "</select>\n";
}
return $html;
}


/**
 * Return HTML for a select box for escalation path
 * @param string $name. Name attribute
 * @param int $userid. The escalation path ID to pre-select
 * @returns string. HTML
 */
function escalation_path_drop_down($name, $id)
{
    global $dbEscalationPaths;
    $sql  = "SELECT id, name FROM `{$dbEscalationPaths}` ";
    $sql .= "ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $html = "<select name='{$name}' id='{$name}' >";
    $html .= "<option selected='selected' value='0'>{$GLOBALS['strNone']}</option>\n";
    while ($path = mysql_fetch_object($result))
    {
        $html .= "<option value='{$path->id}'";
        if ($path->id ==$id)
        {
            $html .= " selected='selected'";
        }
        $html .= ">{$path->name}</option>\n";
    }
    $html .= "</select>\n";

    return $html;
}


/**
  * Returns a string representing the name of
  * the given priority. Returns an empty string if the
  * priority does not exist.
  * @author Ivan Lucas
  * @param int $id. Priority ID, higher the number higher the priority
  * @param bool $syslang. (optional) Uses system language when set to TRUE otherwise
  *                       uses user language (default)
  * @returns string.
*/
function priority_name($id, $syslang = FALSE)
{
    switch ($id)
    {
        case 1:
            if (!$syslang) $value = $GLOBALS['strLow'];
            else $value = $_SESSION['syslang']['strLow'];
        break;
        case 2:
            if (!$syslang) $value = $GLOBALS['strMedium'];
            else $value = $_SESSION['syslang']['strMedium'];
        break;
        case 3:
            if (!$syslang) $value = $GLOBALS['strHigh'];
            else $value = $_SESSION['syslang']['strHigh'];
        break;
        case 4:
            if (!$syslang) $value = $GLOBALS['strCritical'];
            else $value = $_SESSION['syslang']['strCritical'];
        break;
        case '':
            if (!$sylang) $value = $GLOBALS['strNotSet'];
            else $value = $_SESSION['syslang']['strNotSet'];
        break;
        default:
            if (!$syslang) $value = $GLOBALS['strUnknown'];
            else $value = $_SESSION['syslang']['strUnknown'];
        break;
    }
return $value;
}


// Returns HTML for an icon to indicate priority
function priority_icon($id)
{
    global $CONFIG;
    switch ($id)
    {
        case 1: $html = "<img src='{$CONFIG['application_webpath']}images/low_priority.gif' width='10' height='16' alt='{$GLOBALS['strLowPriority']}' title='{$GLOBALS['strLowPriority']}' />"; break;
        case 2: $html = "<img src='{$CONFIG['application_webpath']}images/med_priority.gif' width='10' height='16' alt='{$GLOBALS['strMediumPriority']}' title='{$GLOBALS['strMediumPriority']}' />"; break;
        case 3: $html = "<img src='{$CONFIG['application_webpath']}images/high_priority.gif' width='10' height='16' alt='{$GLOBALS['strHighPriority']}' title='{$GLOBALS['strHighPriority']}' />"; break;
        case 4: $html = "<img src='{$CONFIG['application_webpath']}images/crit_priority.gif' width='16' height='16' alt='{$GLOBALS['strCriticalPriority']}' title='{$GLOBALS['strCriticalPriority']}' />"; break;
        default: $html = '?'; break;
    }
    return $html;
}


/**
    * Returns an array of fields from the most recent update record for a given incident id
    * @author Ivan Lucas
    * @param int $id An incident ID
    * @returns array
*/
function incident_lastupdate($id)
{
    // Find the most recent update
    $sql = "SELECT userid, type, sla, currentowner, currentstatus, LEFT(bodytext,500) AS body, timestamp, nextaction, id ";
    $sql .= "FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid='{$id}' AND bodytext != '' ORDER BY timestamp DESC, id DESC LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) == 0)
    {
        trigger_error("Zero records while retrieving incident last update for incident {$id}",E_USER_WARNING);
    }
    else
    {
        $update = mysql_fetch_array($result);

        mysql_free_result($result);
        // Remove Tags from update Body
        $update['body'] = trim($update['body']);
        $update['body'] = $update['body'];
        return array($update['userid'], $update['type'] ,$update['currentowner'], $update['currentstatus'], $update['body'], $update['timestamp'], $update['nextaction'], $update['id']);
    }
}


/**
 * Returns a string containing the body of the first update (that is visible to customer)
 * in a format suitable for including in an email
 * @author Ivan Lucas
 * @param int $id An incident ID
 */
function incident_firstupdate($id)
{
    $sql = "SELECT bodytext FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid='$id' AND customervisibility='show' ORDER BY timestamp ASC, id ASC LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) >= 1)
    {
        list($bodytext) = mysql_fetch_row($result);
        $bodytext = strip_tags($bodytext);
    }
    else
    {
        $bodytext = '';
    }

    return $bodytext;
}


/**
 * Converts an incident status ID to an internationalised status string
 * @author Ivan Lucas
 * @param int $id. incident status ID
 * @param string $type. 'internal' or 'external', where external means customer/client facing
 * @returns string Internationalised incident status.
 *                 Or empty string if the ID is not recognised.
 * @note The incident status database table must contain i18n keys.
 */
function incidentstatus_name($id, $type='internal')
{
    global $dbIncidentStatus;

    if ($type == 'external')
    {
        $type = 'ext_name';
    }
    else
    {
        $type = 'name';
    }

    $sql = "SELECT {$type} FROM `{$dbIncidentStatus}` WHERE id='{$id}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) == 0)
    {
        $name = '';
    }
    else
    {
        $incidentstatus = mysql_fetch_assoc($result);
        $name =  $GLOBALS[$incidentstatus[$type]];
    }
    return $name;
}


function closingstatus_name($id)
{
    global $dbClosingStatus;
    if ($id != '')
    {
        $closingstatus = db_read_column('name', $GLOBALS['dbClosingStatus'], $id);
    }
    else
    {
        $closingstatus = 'strUnknown';
    }

    return ($GLOBALS[$closingstatus]);
}


/**
 * A drop down to select from a list of open incidents
 * optionally filtered by contactid
 * @author Ivan Lucas
 * @param string $name The name attribute for the HTML select
 * @param int $id The value to select by default (not implemented yet)
 * @param int $contactid Filter the list to show incidents from a single
 contact
 * @returns string HTML
 */
function incident_drop_down($name, $id, $contactid = 0)
{
    global $dbIncidents;

    $html = '';

    $sql = "SELECT * FROM `{$dbIncidents}` WHERE status != ".STATUS_CLOSED . " ";
    if ($contactid > 0) $sql .= "AND contact = {$contactid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $html = "<select id='{$name}' name='{$name}' {$select}>\n";
        while ($incident = mysql_fetch_object($result))
        {
            // FIXME unfinished
            $html .= "<option value='{$incident->id}'>[{$incident->id}] - ";
            $html .= "{$incident->title}</option>";
        }

        $html .= "</select>";
    }
    else
    {
        $html = "<input type='text' name='{$name}' value='' size='10' maxlength='12' />";
    }
    return $html;
}


/* Returns a string representing the name of   */
/* the given user status. Returns an empty string if the      */
/* status does not exist.                                     */
function userstatus_name($id)
{
    $status = db_read_column('name', $GLOBALS['dbUserStatus'], $id);
    return $GLOBALS[$status];
}



/* Returns a string representing the name of   */
/* the given product. Returns an empty string if the product  */
/* does not exist.                                            */
function product_name($id)
{
    return db_read_column('name', $GLOBALS['dbProducts'], $id);
}


/**
 * Formats a given number of seconds into a readable string showing days, hours and minutes.
 * @author Ivan Lucas
 * @param int $seconds number of seconds
 * @param bool $showseconds bool If TRUE and $seconds is less than 60 the function returns 1 minute.
 * @returns string Readable date/time
 */
function format_seconds($seconds, $showseconds = FALSE)
{
    global $str1Year, $str1Hour, $str1Minute, $str1Day, $str1Month, $strXSeconds, $str1Second;
    global $strXHours, $strXMinutes, $strXDays, $strXMonths, $strXYears;

    if ($seconds <= 0)
    {
        return sprintf($strXMinutes, 0);
    }
    elseif ($seconds <= 60 AND $seconds >= 1 AND $showseconds == FALSE)
    {
        return $str1Minute;
    }
    elseif ($seconds < 60 AND $seconds >= 1 AND $showseconds == TRUE)
    {
        if ($seconds == 1)
        {
            return $str1Second;
        }
        else
        {
            return sprintf($strXSeconds, $seconds);
        }
    }
    else
    {
        $years = floor($seconds / ( 2629800 * 12));
        $remainder = ($seconds % ( 2629800 * 12));
        $months = floor($remainder / 2629800);
        $remainder = ($seconds % 2629800);
        $days = floor($remainder / 86400);
        $remainder = ($remainder % 86400);
        $hours = floor($remainder / 3600);
        $remainder = ($remainder % 3600);
        $minutes = floor($remainder / 60);

        $return_string = '';

        if ($years > 0)
        {
            if ($years == 1)
            {
                $return_string .= $str1Year.' ';
            }
            else
            {
                $return_string .= sprintf($strXYears, $years).' ';
            }
        }

        if ($months > 0 AND $years < 2)
        {
            if ($months == 1)
            {
                $return_string .= $str1Month." ";
            }
            else
            {
                $return_string .= sprintf($strXMonths, $months).' ';
            }
        }

        if ($days > 0 AND $months < 6)
        {
            if ($days == 1)
            {
                $return_string .= $str1Day." ";
            }
            else
            {
                $return_string .= sprintf($strXDays, $days)." ";
            }
        }

        if ($months < 1 AND $days < 7 AND $hours > 0)
        {
            if ($hours == 1)
            {
                $return_string .= $str1Hour." ";
            }
            else
            {
                $return_string .= sprintf($strXHours, $hours)." ";
            }
        }
        elseif ($months < 1 AND $days < 1 AND $hours > 0)
        {
            if ($minutes == 1)
            {
                $return_string .= $str1Minute." ";
            }
            elseif ($minutes > 1)
            {
                $return_string .= sprintf($strXMinutes, $minutes)." ";
            }
        }

        if ($months < 1 AND $days < 1 AND $hours < 1)
        {
            if ($minutes <= 1)
            {
                $return_string .= $str1Minute." ";
            }
            else
            {
                $return_string .= sprintf($strXMinutes, $minutes)." ";
            }
        }

        $return_string = trim($return_string);
        if (empty($return_string)) $return_string = "({$seconds})";
        return $return_string;
    }
}


/**
 * Return a string containing the time remaining as working days/hours/minutes (eg. 9am - 5pm)
 * @author Ivan Lucas
 * @returns string. Length of working time, in readable days, hours and minutes
 * @note The working day is calculated using the $CONFIG['end_working_day'] and
 * $CONFIG['start_working_day'] config variables
 */
function format_workday_minutes($minutes)
{
    global $CONFIG, $strXMinutes, $str1Minute, $strXHours, $strXHour;
    global $strXWorkingDay, $strXWorkingDays;
    $working_day_mins = ($CONFIG['end_working_day'] - $CONFIG['start_working_day']) / 60;
    $days = floor($minutes / $working_day_mins);
    $remainder = ($minutes % $working_day_mins);
    $hours = floor($remainder / 60);
    $minutes = floor($remainder % 60);

    if ($days == 1)
    {
        $time = sprintf($strXWorkingDay, $days);
    }
    elseif ($days > 1)
    {
        $time = sprintf($strXWorkingDays, $days);
    }

    if ($days <= 3 AND $hours == 1)
    {
        $time .= " ".sprintf($strXHour, $hours);
    }
    elseif ($days <= 3 AND $hours > 1)
    {
        $time .= " ".sprintf($strXHours, $hours);
    }
    elseif ($days > 3 AND $hours >= 1)
    {
        $time = "&gt; ".$time;
    }

    if ($days < 1 AND $hours < 8 AND $minutes == 1)
    {
        $time .= " ".$str1Minute;
    }
    elseif ($days < 1 AND $hours < 8 AND $minutes > 1)
    {
        $time .= " ".sprintf($strXMinutes, $minutes);
    }

    if ($days == 1 AND $hours < 8 AND $minutes == 1)
    {
        $time .= " ".$str1Minute;
    }
    elseif ($days == 1 AND $hours < 8 AND $minutes > 1)
    {
        $time .= " ".sprintf($strXMinutes, $minutes);
    }

    $time = trim($time);

    return $time;
}


/**
 * Make a readable and friendly date, i.e. say Today, or Yesterday if it is
 * @author Ivan Lucas
 * @param int $date a UNIX timestamp
 * @returns string. Date in a readable friendly format
 * @note See also readable_date() dupe?
 */
function format_date_friendly($date)
{
    global $CONFIG, $now;
    if (ldate('dmy', $date) == ldate('dmy', time()))
    {
        $datestring = "{$GLOBALS['strToday']} @ ".ldate($CONFIG['dateformat_time'], $date);
    }
    elseif (ldate('dmy', $date) == ldate('dmy', (time() - 86400)))
    {
        $datestring = "{$GLOBALS['strYesterday']} @ ".ldate($CONFIG['dateformat_time'], $date);
    }
    elseif ($date < $now-86400 AND
            $date > $now-(86400*6))
    {
        $datestring = ldate('l', $date)." @ ".ldate($CONFIG['dateformat_time'], $date);
    }
    else
    {
        $datestring = ldate($CONFIG['dateformat_datetime'], $date);
    }

    return ($datestring);
}




/*  calculates the value of the unix timestamp  */
/* which is the number of given days, hours and minutes from  */
/* the current time.                                          */
function calculate_time_of_next_action($days, $hours, $minutes)
{
    $now = time();
    $return_value = $now + ($days * 86400) + ($hours * 3600) + ($minutes * 60);
    return ($return_value);
}


/**
 * Retrieves the service level ID of a given maintenance contract
 * @author Ivan Lucas
 * @param int $maintid. Contract ID
 * @returns. int Service Level ID
 * @deprecated
 * @note Service level ID's are DEPRECATED service level tags should be used in favour of service level ID's
 */
function maintenance_servicelevel($maintid)
{
    global $CONFIG, $dbMaintenance;
    $sql = "SELECT servicelevelid FROM `{$dbMaintenance}` WHERE id='{$maintid}' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) < 1)
    {
        // in case there is no maintenance contract associated with the incident, use default service level
        // if there is a maintenance contract then we should throw an error because there should be
        // service level
        if ($maintid == 0)
        {
            // Convert the default service level tag to an ide and use that
            $servicelevelid = servicelevel_tag2id($CONFIG['default_service_level']);
        }
    }
    else
    {
        list($servicelevelid) = mysql_fetch_row($result);
    }
    return $servicelevelid;

}


function maintenance_siteid($id)
{
    return db_read_column('site', $GLOBALS['dbMaintenance'], $id);

}


// Returns the number of remaining incidents given an incident pool id
// Returns 'Unlimited' if theres no match on ID
function incidents_remaining($id)
{
    $remaining = db_read_column('incidentsremaining', $GLOBALS['dbIncidentPools'], $id);
    if (empty($remaining))
    {
        $remaining = '&infin;';
    }

    return $remaining;
}


function decrement_free_incidents($siteid)
{
    global $dbSites;
    $sql = "UPDATE `{$dbSites}` SET freesupport = (freesupport - 1) WHERE id='$siteid'";
    mysql_query($sql);
    if (mysql_affected_rows() < 1)
    {
        trigger_error("No rows affected while updating freesupport",E_USER_ERROR);
    }

    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    else return TRUE;
}


function increment_incidents_used($maintid)
{
    global $dbMaintenance;
    $sql = "UPDATE `{$dbMaintenance}` SET incidents_used = (incidents_used + 1) WHERE id='$maintid'";
    mysql_query($sql);
    if (mysql_affected_rows() < 1) trigger_error("No rows affected while updating freesupport",E_USER_ERROR);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    else return TRUE;
}


/**
 * Handle a PHP triggered error
 * @author Ivan Lucas
 * @note Not called directly but triggered by PHP's own error handling
 *       and the trigger_error function.
 * @note Parameters as per http://www.php.net/set_error_handler
 * @note This function is not internationalised in order that bugs can
 *       be reported to developers and still be sure that they will be
 *       understood
 **/
function sit_error_handler($errno, $errstr, $errfile, $errline, $errcontext)
{
    global $CONFIG, $sit, $siterrors;

    // if error has been supressed with an @
    if (error_reporting() == 0)
    {
        return;
    }

    $errortype = array(
    E_ERROR           => 'Fatal Error',
    E_WARNING         => 'Warning',
    E_PARSE           => 'Parse Error',
    E_NOTICE          => 'Notice',
    E_CORE_ERROR      => 'Core Error',
    E_CORE_WARNING    => 'Core Warning',
    E_COMPILE_ERROR   => 'Compile Error',
    E_COMPILE_WARNING => 'Compile Warning',
    E_USER_ERROR      => 'Application Error',
    E_USER_WARNING    => 'Application Warning',
    E_USER_NOTICE     => 'Application Notice');

    if (defined('E_STRICT')) $errortype[E_STRICT] = 'Strict Runtime notice';

    $trace_errors = array(E_ERROR, E_USER_ERROR);

    $user_errors = E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE;
    $system_errors = E_ERROR | E_WARNING | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING;
    $warnings = E_WARNING | E_USER_WARNING | E_CORE_WARNING | E_COMPILE_WARNING;
    $notices = E_NOTICE | E_USER_NOTICE;

    if (($errno & $user_errors) OR ($errno & $system_errors))
    {
        if (empty($CONFIG['error_logfile']) === FALSE AND is_writable($CONFIG['error_logfile']) === TRUE)
        {
            $displayerrors = FALSE;
        }
        else
        {
            $displayerrors = TRUE;
        }

        if ($errno & $notices) $class = 'info';
        elseif ($errno & $warnings) $class = 'warning';
        else $class = 'error';

        $backtrace = debug_backtrace();
        if (php_sapi_name() != 'cli')
        {
            $tracelog = '';
            if ($displayerrors)
            {
                echo "<p class='{$class}'><strong>{$errortype[$errno]} [{$errno}]</strong><br />";
                echo "{$errstr} in {$errfile} @ line {$errline}";
                if ($CONFIG['debug']) echo "<br /><strong>Backtrace</strong>:";
            }

            foreach ($backtrace AS $trace)
            {
                if (!empty($trace['file']))
                {
                    if ($CONFIG['debug'] AND $displayerrors)
                    {
                        echo "<br />{$trace['file']} @ line {$trace['line']}";
                    }

                    $tracelog .= "{$trace['file']} @ line {$trace['line']}";
                    if (!empty($trace['function']))
                    {
                        $tracelog .= " {$trace['function']}()";
                        if ($displayerrors) echo " {$trace['function']}() ";
//                         foreach ($trace['args'] AS $arg)
//                         {
//                             echo "$arg &bull; ";
//                         }
                    }
                    $tracelog .= "\n";
                }
            }
            if ($errno != E_NOTICE)
            {
                $logentry = " {$errortype[$errno]} [{$errno}] {$errstr} (in line {$errline} of file {$errfile})\n";
            }

            if ($errno == E_ERROR
                || $errno == E_USER_ERROR
                || $errno == E_CORE_ERROR
                || $errno == E_CORE_WARNING
                || $errno == E_COMPILE_ERROR
                || $errno == E_COMPILE_WARNING)
            {
                $logentry .= "Context: [CONTEXT-BEGIN]\n".print_r($errcontext, TRUE)."\n[CONTEXT-END]\n----------\n\n";
                $siterrors++;
            }

            debug_log($logentry);
            if ($displayerrors)
            {
                echo "</p>";
                // Tips, to help diagnose errors
                if (strpos($errstr, 'Unknown column') !== FALSE OR
                    preg_match("/Table '(.*)' doesn't exist/", $errstr))
                {
                    echo "<p class='tip'>The SiT schema may need updating to fix this problem.";
                    if (user_permission($sit[2], 22)) echo "Visit <a href='setup.php'>Setup</a>"; // Only show this to admin
                    echo "</p>";
                }

                if (strpos($errstr, 'headers already sent') !== FALSE)
                {
                    echo "<p class='tip'>This warning may be caused by a problem that occurred before the ";
                    echo "page was displayed, or sometimes by a syntax error or ";
                    echo "extra whitespace in your config file.</p>";
                }

                if (strpos($errstr, 'You have an error in your SQL syntax') !== FALSE OR
                    strpos($errstr, 'Query Error Incorrect table name') !== FALSE)
                {
                    echo "<p class='tip'>You may have found a bug in SiT, please <a href=\"{$CONFIG['bugtracker_url']}\">report it</a>.</p>";
                }
            }
        }
        else
        {
            debug_log("ERROR: {$errortype[$errno]} {$errstr} in {$errfile} at line {$errline}\n");
            if (!empty($tracelog)) debug_log("ERROR: Backtrace:\n{$tracelog}\n");
        }
    }
}


/**
 * Write an entry to the configured error logfile
 * @author Ivan Lucas
 * @param string $logentry. A line, or lines to write to the log file
 * (with newlines \n)
 * @param bool $debugmodeonly. Only write an entry if debug mode is TRUE
 * @retval bool TRUE log entry written
 * @retval bool FALSE log entry not written
 */
function debug_log($logentry, $debugmodeonly = FALSE)
{
    global $CONFIG;

    if ($debugmodeonly == FALSE
        OR ($debugmodeonly == TRUE AND $CONFIG['debug_mode'] == TRUE))
    {
        $logentry = $_SERVER["SCRIPT_NAME"] . ' ' .$logentry;

        if (substr($logentry, -1) != "\n") $logentry .= "\n";
        if (!empty($CONFIG['error_logfile']))
        {
            if (is_writable($CONFIG['error_logfile']))
            {
                $fp = fopen($CONFIG['error_logfile'], 'a+');
                if ($fp)
                {
                    fwrite($fp, date('c').' '.strip_tags($logentry));
                    fclose($fp);
                }
                else
                {
                    echo "<p class='error'>Could not log message to error_logfile</p>";
                    return FALSE;
                }
                return TRUE;
            }
        }
        else
        {
            return FALSE;
        }
    }
    else return TRUE;
}



/**
 * Generates a HTML drop down of sites within SiT!
 * @param string $name The name of the field
 * @param int $id The ID of the selected item
 * @param bool $required Whether this is a mandetory field, defaults to false
 * @param bool $showinactive Whether to show the sites marked inactive, defaults to false
 * @return string The HTML for the drop down
 */
function site_drop_down($name, $id, $required = FALSE, $showinactive = FALSE)
{
    global $dbSites;
    $sql  = "SELECT id, name, department FROM `{$dbSites}` ";
    if (!$showinactive)  $sql .= "WHERE active = 'true' ";
    $sql .= "ORDER BY name ASC";
    $result = mysql_query($sql);

    $html = "<select name='{$name}'";
    if ($required)
    {
        $html .= " class='required' ";
    }
    $html .= ">\n";
    if ($id == 0)
    {
        $html .="<option selected='selected' value='0'></option>\n";
    }

    while ($sites = mysql_fetch_object($result))
    {
        $text = $sites->name;
        if (!empty($sites->department))
        {
            $text.= ", ".$sites->department;
        }

        if (strlen($text) >= 55)
        {
            $text = substr(trim($text), 0, 55)."&hellip;";
        }
        else
        {
            $text = $text;
        }

        $html .= "<option ";
        if ($sites->id == $id)
        {
            $html .= "selected='selected' ";
        }

        $html .= "value='{$sites->id}'>{$text}</option>\n";
    }
    $html .= "</select>\n";

    return $html;
}


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
 * prints the HTML for a drop down list of maintenance contracts
 * @param string $name. name of the drop down box
 * @param int $id. the contract id to preselect
 * @param int $siteid. Show records from this SiteID only, blank for all sites
 * @param array $excludes. Hide contracts with ID's in this array
 * @param bool $return. Whether to return to HTML or echo
 * @param bool $showonlyactive. True show only active (with a future expiry date), false shows all
 */
function maintenance_drop_down($name, $id, $siteid = '', $excludes = '', $return = FALSE, $showonlyactive = FALSE, $adminid = '')
{
    global $GLOBALS, $now;
    // TODO make maintenance_drop_down a hierarchical selection box sites/contracts
    // extract all maintenance contracts
    $sql  = "SELECT s.name AS sitename, p.name AS productname, m.id AS id ";
    $sql .= "FROM `{$GLOBALS['dbMaintenance']}` AS m, `{$GLOBALS['dbSites']}` AS s, `{$GLOBALS['dbProducts']}` AS p ";
    $sql .= "WHERE site = s.id AND product = p.id ";
    if (!empty($siteid)) $sql .= "AND s.id = {$siteid} ";

    if ($showonlyactive)
    {
        $sql .= "AND (m.expirydate > {$now} OR m.expirydate = -1) ";
    }

    if ($adminid != '')
    {
      $sql .= "AND admincontact = '{$adminid}' ";
    }

    $sql .= "ORDER BY s.name ASC";
    $result = mysql_query($sql);
    $results = 0;
    // print HTML
    $html .= "<select name='{$name}'>";
    if ($id == 0 AND $results > 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($maintenance = mysql_fetch_object($result))
    {
        if (!is_array($excludes) OR (is_array($excludes) AND !in_array($maintenance->id, $excludes)))
        {
            $html .= "<option ";
            if ($maintenance->id == $id)
            {
                $html .= "selected='selected' ";
            }
            $html .= "value='{$maintenance->id}'>{$maintenance->sitename} | {$maintenance->productname}</option>";
            $html .= "\n";
            $results++;
        }
    }

    if ($results == 0)
    {
        $html .= "<option>{$GLOBALS['strNoRecords']}</option>";
    }
    $html .= "</select>";

    if ($return)
    {
        return $html;
    }
    else
    {
        echo $html;
    }
}


//  prints the HTML for a drop down list of resellers, with the given name and with the given id
// selected.                                                  */
function reseller_drop_down($name, $id)
{
    global $dbResellers;
    $sql  = "SELECT id, name FROM `{$dbResellers}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    // print HTML
    echo "<select name='{$name}'>";

    if ($id == 0 OR empty($id))
    {
        echo "<option selected='selected' value='0'></option>\n";
    }
    else
    {
        echo "<option value='0'></option>\n";
    }

    while ($resellers = mysql_fetch_object($result))
    {
        echo "<option ";
        if ($resellers->id == $id)
        {
            echo "selected='selected' ";
        }

        echo "value='{$resellers->id}'>{$resellers->name}</option>";
        echo "\n";
    }

    echo "</select>";
}


//  prints the HTML for a drop down list of
// licence types, with the given name and with the given id
// selected.
function licence_type_drop_down($name, $id)
{
    global $dbLicenceTypes;
    $sql  = "SELECT id, name FROM `{$dbLicenceTypes}` ORDER BY name ASC";
    $result = mysql_query($sql);

    // print HTML
    echo "<select name='{$name}'>";

    if ($id == 0)
    {
        echo "<option selected='selected' value='0'></option>\n";
    }

    while ($licencetypes = mysql_fetch_object($result))
    {
        echo "<option ";
        if ($licencetypes->id == $id)
        {
            echo "selected='selected' ";
        }

        echo "value='{$licencetypes->id}'>{$licencetypes->name}</option>";
        echo "\n";
    }

    echo "</select>";
}


/**
    * @author Ivan Lucas
*/
function countdayincidents($day, $month, $year)
{
    // Counts the number of incidents opened on a specified day
    global $dbIncidents;
    $unixstartdate = mktime(0,0,0,$month,$day,$year);
    $unixenddate = mktime(23,59,59,$month,$day,$year);
    $sql = "SELECT count(id) FROM `{$dbIncidents}` ";
    $sql .= "WHERE opened BETWEEN '$unixstartdate' AND '$unixenddate' ";
    $result = mysql_query($sql);
    list($count) = mysql_fetch_row($result);
    mysql_free_result($result);
    return $count;
}


/**
 * @author Ivan Lucas
 */
function countdayclosedincidents($day, $month, $year)
{
    // Counts the number of incidents closed on a specified day
    global $dbIncidents;
    $unixstartdate = mktime(0,0,0,$month,$day,$year);
    $unixenddate = mktime(23,59,59,$month,$day,$year);
    $sql = "SELECT COUNT(id) FROM `{$dbIncidents}` ";
    $sql .= "WHERE closed BETWEEN '$unixstartdate' AND '$unixenddate' ";
    $result = mysql_query($sql);
    list($count) = mysql_fetch_row($result);
    mysql_free_result($result);
    return $count;
}


/**
 * @author Ivan Lucas
 */
function countdaycurrentincidents($day, $month, $year)
{
    global $dbIncidents;
    // Counts the number of incidents currently open on a specified day
    $unixstartdate = mktime(0,0,0,$month,$day,$year);
    $unixenddate = mktime(23,59,59,$month,$day,$year);
    $sql = "SELECT COUNT(id) FROM `{$dbIncidents}` ";
    $sql .= "WHERE opened <= '$unixenddate' AND closed >= '$unixstartdate' ";
    $result = mysql_query($sql);
    list($count) = mysql_fetch_row($result);
    mysql_free_result($result);
    return $count;
}


/**
 * Inserts an entry into the Journal table and marks the user online
 * @author Ivan Lucas, Kieran Hogg
 * @param int $loglevel The log level required for this event to be logged
 * @param string $event Text title for the event
 * @param string $bodytext Text describing the event in detail
 * @param int $journaltype One of the defined journal types
 * @param int $refid An ID to relate to data, the table this ID is for
 depends on the journal type used
 * @retval TRUE success, entry logged
 * @retval FALSE failure. entry not logged
 * @note Produces an audit log
 */
function journal($loglevel, $event, $bodytext, $journaltype, $refid)
{
    global $CONFIG, $sit, $dbJournal;
    // Journal Types
    // 1 = Logon/Logoff
    // 2 = Support Incidents
    // 3 = -Unused-
    // 4 = Sites
    // 5 = Contacts
    // 6 = Admin
    // 7 = User Management

    // Logging Level
    // 0 = No logging
    // 1 = Minimal Logging
    // 2 = Normal Logging
    // 3 = Full Logging
    // 4 = Max Debug Logging

    $bodytext = mysql_real_escape_string($bodytext);
    if ($loglevel <= $CONFIG['journal_loglevel'])
    {
        $sql  = "INSERT INTO `{$dbJournal}` ";
        $sql .= "(userid, event, bodytext, journaltype, refid) ";
        $sql .= "VALUES ('".$_SESSION['userid']."', '$event', '$bodytext', '$journaltype', '$refid') ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        return TRUE;
    }
    else
    {
        // Below minimum log level - do nothing
        return FALSE;
    }
}


/**
 Send an email from SiT
 * @param string $to. Destination email address
 * @param string $from. Source email address
 * @param string $subject. Email subject line
 * @param string $body. Email body text
 * @param string $replyto. (optional) Address to send reply to
 * @param string $cc. (optional) Carbon copy address
 * @param string $bcc. (optional) Blind carbon copy address
 * @returns The return value from PHP mail() function or TRUE when in Demo mode
 * @note Returns TRUE but does not actually send mail when SiT is in Demo mode
 */
function send_email($to, $from, $subject, $body, $replyto='', $cc='', $bcc='')
{
    global $CONFIG, $application_version_string;

    $crlf = "\r\n";

    if (empty($to)) trigger_error('Empty TO address in email', E_USER_WARNING);

    $extra_headers  = "From: {$from}" . $crlf;
    if (!empty($replyto)) $extra_headers .= "Reply-To: {$replyto}" . $crlf;
    if (!empty($email_cc))
    {
        $extra_headers .= "CC: {$cc}" . $crlf;
    }
    if (!empty($email_bcc))
    {
        $extra_headers .= "BCC: {$bcc}" . $crlf;
    }
    if (!empty($CONFIG['support_email']))
    {
        $extra_headers .= "Errors-To: {$CONFIG['support_email']}" . $crlf;
    }
    $extra_headers .= "X-Mailer: {$CONFIG['application_shortname']} {$application_version_string}/PHP " . phpversion() . $crlf;
    $extra_headers .= "X-Originating-IP: {$_SERVER['REMOTE_ADDR']}" . $crlf;
    $extra_headers .= "MIME-Version: 1.0" . $crlf;
    $extra_headers .= "Content-type: text/plain; charset={$GLOBALS['i18ncharset']}" . $crlf;
//     $extra_headers .= "\r\n";

    if ($CONFIG['demo'])
    {
        $rtnvalue = TRUE;
    }
    else
    {
        $rtnvalue = mail($to, $subject, $body, $extra_headers);
    }
    return $rtnvalue;
}


/**
 * Generates and returns a random alphanumeric password
 * @author Ivan Lucas
 * @note Some characters (0 and 1) are not used to avoid user confusion
 */
function generate_password($length=8)
{
    $possible = '0123456789'.'abcdefghijkmnpqrstuvwxyz'.'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.'-';
    // $possible = '23456789'.'abcdefghjkmnpqrstuvwxyz'.'ABCDEFGHJKLMNPQRSTUVWXYZ'.'-';
                // not using 1's 0's etc. to save confusion
                // '-=!&';
    $str = '';
    while (strlen($str) < $length)
    {
        $str .= substr($possible, (rand() % strlen($possible)),1);
    }
    return $str;
}


if (!function_exists('list_dir'))
{
    // returns an array contains all files in a directory and optionally recurses subdirectories
    function list_dir($dirname, $recursive = 1)
    {
        // try to figure out what delimeter is being used (for windows or unix)...
        $delim = (strstr($dirname,"/")) ? "/" : "\\";

        if ($dirname[strlen($dirname)-1] != $delim)
        $dirname .= $delim;

        $handle = opendir($dirname);
        if ($handle == FALSE)
        {
            trigger_error("Error in list_dir() Problem attempting to open directory: {$dirname}",E_USER_WARNING);
        }

        $result_array = array();

        while ($file = readdir($handle))
        {
            if ($file == '.' || $file == '..')
            {
                continue;
            }

            if (is_dir($dirname.$file) && $recursive)
            {
                $x = list_dir($dirname.$file.$delim);
                $result_array = array_merge($result_array, $x);
            }
            else
            {
                $result_array[] = $dirname.$file;
            }
        }
        closedir($handle);

        if (sizeof($result_array))
        {
            natsort($result_array);

            if ($_SESSION['update_order'] == "desc")
            {
                $result_array = array_reverse($result_array);
            }
        }
        return $result_array;
    }
}


if (!function_exists('is_number'))
{
    function is_number($string)
    {
        $number = TRUE;
        for ($i=0; $i < strlen($string); $i++)
        {
            if (!(ord(substr($string, $i, 1)) <= 57 && ord(substr($string, $i, 1)) >= 48))
            {
                $number = FALSE;
            }
        }
        return $number;
    }
}


// recursive copy from one directory to another
function rec_copy ($from_path, $to_path)
{
    if ($from_path == '') trigger_error('Cannot move file', 'from_path not set', E_USER_WARNING);
    if ($to_path == '') trigger_error('Cannot move file', 'to_path not set', E_USER_WARNING);

    $mk = mkdir($to_path, 0700);
    if (!$mk) trigger_error('Failed creating directory: {$to_path}',E_USER_WARNING);
    $this_path = getcwd();
    if (is_dir($from_path))
    {
        chdir($from_path);
        $handle = opendir('.');
        while (($file = readdir($handle)) !== false)
        {
            if (($file != ".") && ($file != ".."))
            {
                if (is_dir($file))
                {
                    rec_copy ($from_path.$file."/",
                    $to_path.$file."/");
                    chdir($from_path);
                }

                if (is_file($file))
                {
                    if (!(substr(rtrim($file),strlen(rtrim($file))-8,4) == 'mail'
                        || substr(rtrim($file),strlen(rtrim($file))-10,5) == 'part1'
                        || substr(rtrim($file),strlen(rtrim($file))-8,4) == '.vcf'))
                    {
                        copy($from_path.$file, $to_path.$file);
                    }
                }
            }
        }
        closedir($handle);
    }
}


/**
 * @author Ivan Lucas
 */
function getattachmenticon($filename)
{
    global $CONFIG, $iconset;
    // Maybe sometime make this use mime typesad of file extensions
    $ext = strtolower(substr($filename, (strlen($filename)-3) , 3));
    $imageurl = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/unknown.png";

    $type_image = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/file_image.png";

    $filetype[]="gif";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[]="jpg";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[]="bmp";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[]="png";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[]="pcx";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[]="xls";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/spreadsheet.png";
    $filetype[]="csv";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/spreadsheet.png";
    $filetype[]="zip";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[]="arj";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/zip.png";
    $filetype[]="rar";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/rar.png";
    $filetype[]="cab";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[]="lzh";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[]="txt";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/txt.png";
    $filetype[]="f90";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_f.png";
    $filetype[]="f77";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_f.png";
    $filetype[]="inf";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png";
    $filetype[]="ins";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png";
    $filetype[]="adm";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png";
    $filetype[]="f95";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_f.png";
    $filetype[]="cpp";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_cpp.png";
    $filetype[]="for";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_f.png";
    $filetype[]=".pl";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_pl.png";
    $filetype[]=".py";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_py.png";
    $filetype[]="rtm";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/misc_doc.png";
    $filetype[]="doc";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/wordprocessing.png";
    $filetype[]="rtf";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/wordprocessing.png";
    $filetype[]="wri";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/wordprocessing.png";
    $filetype[]="wri";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/wordprocessing.png";
    $filetype[]="pdf";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/pdf.png";
    $filetype[]="htm";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[]="tml";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[]="wav";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/sound.png";
    $filetype[]="mp3";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/sound.png";
    $filetype[]="voc";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/sound.png";
    $filetype[]="exe";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]="com";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]="nlm";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]="evt";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/log.png";
    $filetype[]="log";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/log.png";
    $filetype[]="386";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]="dll";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]="asc";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/txt.png";
    $filetype[]="asp";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[]="avi";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/video.png";
    $filetype[]="bkf";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tar.png";
    $filetype[]="chm";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/man.png";
    $filetype[]="hlp";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/man.png";
    $filetype[]="dif";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/txt.png";
    $filetype[]="hta";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[]="reg";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/resource.png";
    $filetype[]="dmp";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/core.png";
    $filetype[]="ini";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png";
    $filetype[]="jpe";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[]="mht";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[]="msi";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]="aot";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]="pgp";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]="dbg";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]="axt";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png"; // zen text
    $filetype[]="rdp";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]="sig";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/document.png";
    $filetype[]="tif";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[]="ttf";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/font_ttf.png";
    $filetype[]="for";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/font_bitmap.png";
    $filetype[]="vbs";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[]="vbe";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[]="bat";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[]="wsf";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[]="cmd";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[]="scr";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]="xml";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/xml.png";
    $filetype[]="zap";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]=".ps";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/postscript.png";
    $filetype[]=".rm";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/real_doc.png";
    $filetype[]="ram";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/real_doc.png";
    $filetype[]="vcf";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/vcard.png";
    $filetype[]="wmf";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/vectorgfx.png";
    $filetype[]="cer";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/document.png";
    $filetype[]="tmp";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/unknown.png";
    $filetype[]="cap";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]="tr1";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[]=".gz";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[]="tar";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tar.png";
    $filetype[]="nfo";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/info.png";
    $filetype[]="pal";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/colorscm.png";
    $filetype[]="iso";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/cdimage.png";
    $filetype[]="jar";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/java_src.png";
    $filetype[]="eml";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/message.png";
    $filetype[]=".sh";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[]="bz2";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[]="out";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/log.png";
    $filetype[]="cfg";    $imgurl[]="{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/log.png";

    $cnt = count($filetype);
    if ( $cnt > 0 )
    {
        $a = 0;
        $stop = FALSE;
        while ($a < $cnt && $stop == FALSE)
        {
            if ($ext == $filetype[$a])
            {
                $imageurl = $imgurl[$a];
                $stop = TRUE;
            }
            $a++;
        }
    }
    unset ($filetype);
    unset ($imgurl);
    return $imageurl;
}


function count_incoming_updates()
{
    $sql = "SELECT id FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid=0";
    $result = mysql_query($sql);
    $count = mysql_num_rows($result);
    mysql_free_result($result);
    return $count;
}


function global_signature()
{
    $sql = "SELECT signature FROM `{$GLOBALS['dbEmailSig']}` ORDER BY RAND() LIMIT 1";
    $result = mysql_query($sql);
    list($signature) = mysql_fetch_row($result);
    mysql_free_result($result);
    return $signature;
}


function holiday_type ($id)
{
    switch ($id)
    {
        case HOL_HOLIDAY:
            $holidaytype = $GLOBALS['strHoliday'];
            break;
        case HOL_SICKNESS:
            $holidaytype = $GLOBALS['strAbsentSick'];
            break;
        case HOL_WORKING_AWAY:
            $holidaytype = $GLOBALS['strWorkingAway'];
            break;
        case HOL_TRAINING:
            $holidaytype = $GLOBALS['strTraining'];
            break;
        case HOL_FREE:
            $holidaytype = $GLOBALS['strCompassionateLeave'];
            break;
        case HOL_PUBLIC:
            $holidaytype = $GLOBALS['strPublicHoliday'];
            break;
        default:
            $holidaytype = $GLOBALS['strUnknown'];
            break;
    }
    return ($holidaytype);
}


function holiday_approval_status($approvedid, $approvedby=-1)
{
    global $strApproved, $strApprovedFree, $strRequested, $strNotRequested, $strDenied;
    global $strArchivedDenied, $strArchivedNotRequested, $strArchivedRequested;
    global $strArchivedApproved, $strArchivedApprovedFree, $strApprovalStatusUnknown;

    // We add 10 to normal status when we archive holiday
    switch ($approvedid)
    {
        case -2:
            $status = $strNotRequested;
            break;
        case -1:
            $status = $strDenied;
            break;
        case 0:
            if ($approvedby == 0)
            {
                $status = $strNotRequested;
            }
            else
            {
                $status = $strRequested;
            }
            break;
        case 1:
            $status = $strApproved;
            break;
        case 2:
            $status = $strApprovedFree;
            break;
        case 8:
            $status = $strArchivedNotRequested;
            break;
        case 9:
            $status = $strArchivedDenied;
            break;
        case 10:
            $status = $strArchivedRequested;
            break;
        case 11:
            $status = $strArchivedApproved;
            break;
        case 12:
            $status = $strArchivedApprovedFree;
            break;
        default:
            $status = $strApprovalStatusUnknown;
            break;
    }
    return $status;
}


function holidaytype_drop_down($name, $id)
{
    $holidaytype[HOL_HOLIDAY] = $GLOBALS['strHoliday'];
    $holidaytype[HOL_SICKNESS] = $GLOBALS['strAbsentSick'];
    $holidaytype[HOL_WORKING_AWAY] = $GLOBALS['strWorkingAway'];
    $holidaytype[HOL_TRAINING] = $GLOBALS['strTraining'];
    $holidaytype[HOL_FREE] = $GLOBALS['strCompassionateLeave'];

    $html = "<select name='$name'>";
    if ($id == 0)
    {
        $html .= "<option selected value='0'></option>\n";
    }

    foreach ($holidaytype AS $htypeid => $htype)
    {
        $html .= "<option";
        if ($htypeid == $id)
        {
            $html .= " selected='selected'";
        }
        $html .= " value='{$htypeid}'>{$htype}</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * check to see if any fellow group members have holiday on the date specified
 * @author Ivan Lucas
 * @param int $userid - user ID
 * @param int $date - UNIX Timestamp
 * @param string $length - 'day', 'pm' or 'am'
 * @return HTML space seperated list of users that are away on the date specified
 */
function check_group_holiday($userid, $date, $length='day')
{
    global $dbUsers, $dbHolidays;

    $namelist = '';
    $groupid = user_group_id($userid);
    if (!empty($groupid))
    {
        // list group members
        $msql = "SELECT id AS userid FROM `{$dbUsers}` ";
        $msql .= "WHERE groupid='{$groupid}' AND id != '$userid' ";
        $mresult = mysql_query($msql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        while ($member = mysql_fetch_object($mresult))
        {
            // check to see if this group member has holiday
            $hsql = "SELECT id FROM `{$dbHolidays}` WHERE userid='{$member->userid}' AND date = FROM_UNIXTIME({$date}) ";
            if ($length == 'am' OR $length == 'pm')
            {
                $hsql .= "AND (length = '{$length}' OR length = 'day') ";
            }

            $hresult = mysql_query($hsql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            if (mysql_num_rows($hresult) >= 1)
            {
                $namelist .= user_realname($member->userid)." ($length)";
                $namelist .= "&nbsp;&nbsp;";
            }
        }
    }
    return $namelist;
}


/**
 * Print a listbox of countries
 * @author Ivan Lucas
 * @param string $name - HTML select 'name' attribute
 * @param string $country - Country to pre-select (default to config file setting)
 * @param string $extraattributes - Extra attributes to put on the select tag
 * @return HTML
 * @note if the $country given is not in the list, an editable input box is given instead of a select box
 * @todo TODO i18n country list (How do we do this?)
 */
function country_drop_down($name, $country, $extraattributes='')
{
    global $CONFIG;
    if ($country == '') $country = $CONFIG['home_country'];

    if ($country == 'UK') $country = 'UNITED KINGDOM';
    $countrylist[] = 'ALBANIA';
    $countrylist[] = 'ALGERIA';
    $countrylist[] = 'AMERICAN SAMOA';
    $countrylist[] = 'ANDORRA';
    $countrylist[] = 'ANGOLA';
    $countrylist[] = 'ANGUILLA';
    $countrylist[] = 'ANTIGUA';
    $countrylist[] = 'ARGENTINA';
    $countrylist[] = 'ARMENIA';
    $countrylist[] = 'ARUBA';
    $countrylist[] = 'AUSTRALIA';
    $countrylist[] = 'AUSTRIA';
    $countrylist[] = 'AZERBAIJAN';
    $countrylist[] = 'BAHAMAS';
    $countrylist[] = 'BAHRAIN';
    $countrylist[] = 'BANGLADESH';
    $countrylist[] = 'BARBADOS';
    $countrylist[] = 'BELARUS';
    $countrylist[] = 'BELGIUM';
    $countrylist[] = 'BELIZE';
    $countrylist[] = 'BENIN';
    $countrylist[] = 'BERMUDA';
    $countrylist[] = 'BHUTAN';
    $countrylist[] = 'BOLIVIA';
    $countrylist[] = 'BONAIRE';
    $countrylist[] = 'BOSNIA HERZEGOVINA';
    $countrylist[] = 'BOTSWANA';
    $countrylist[] = 'BRAZIL';
    $countrylist[] = 'BRUNEI';
    $countrylist[] = 'BULGARIA';
    $countrylist[] = 'BURKINA FASO';
    $countrylist[] = 'BURUNDI';
    $countrylist[] = 'CAMBODIA';
    $countrylist[] = 'CAMEROON';
    $countrylist[] = 'CANADA';
    $countrylist[] = 'CANARY ISLANDS';
    $countrylist[] = 'CAPE VERDE ISLANDS';
    $countrylist[] = 'CAYMAN ISLANDS';
    $countrylist[] = 'CENTRAL AFRICAN REPUBLIC';
    $countrylist[] = 'CHAD';
    $countrylist[] = 'CHANNEL ISLANDS';
    $countrylist[] = 'CHILE';
    $countrylist[] = 'CHINA';
    $countrylist[] = 'COLOMBIA';
    $countrylist[] = 'COMOROS ISLANDS';
    $countrylist[] = 'CONGO';
    $countrylist[] = 'COOK ISLANDS';
    $countrylist[] = 'COSTA RICA';
    $countrylist[] = 'CROATIA';
    $countrylist[] = 'CUBA';
    $countrylist[] = 'CURACAO';
    $countrylist[] = 'CYPRUS';
    $countrylist[] = 'CZECH REPUBLIC';
    $countrylist[] = 'DENMARK';
    $countrylist[] = 'DJIBOUTI';
    $countrylist[] = 'DOMINICA';
    $countrylist[] = 'DOMINICAN REPUBLIC';
    $countrylist[] = 'ECUADOR';
    $countrylist[] = 'EGYPT';
    $countrylist[] = 'EL SALVADOR';
    $countrylist[] = 'EQUATORIAL GUINEA';
    $countrylist[] = 'ERITREA';
    $countrylist[] = 'ESTONIA';
    $countrylist[] = 'ETHIOPIA';
    $countrylist[] = 'FAROE ISLANDS';
    $countrylist[] = 'FIJI ISLANDS';
    $countrylist[] = 'FINLAND';
    $countrylist[] = 'FRANCE';
    $countrylist[] = 'FRENCH GUINEA';
    $countrylist[] = 'GABON';
    $countrylist[] = 'GAMBIA';
    $countrylist[] = 'GEORGIA';
    $countrylist[] = 'GERMANY';
    $countrylist[] = 'GHANA';
    $countrylist[] = 'GIBRALTAR';
    $countrylist[] = 'GREECE';
    $countrylist[] = 'GREENLAND';
    $countrylist[] = 'GRENADA';
    $countrylist[] = 'GUADELOUPE';
    $countrylist[] = 'GUAM';
    $countrylist[] = 'GUATEMALA';
    $countrylist[] = 'GUINEA REPUBLIC';
    $countrylist[] = 'GUINEA-BISSAU';
    $countrylist[] = 'GUYANA';
    $countrylist[] = 'HAITI';
    $countrylist[] = 'HONDURAS REPUBLIC';
    $countrylist[] = 'HONG KONG';
    $countrylist[] = 'HUNGARY';
    $countrylist[] = 'ICELAND';
    $countrylist[] = 'INDIA';
    $countrylist[] = 'INDONESIA';
    $countrylist[] = 'IRAN';
    $countrylist[] = 'IRELAND, REPUBLIC';
    $countrylist[] = 'ISRAEL';
    $countrylist[] = 'ITALY';
    $countrylist[] = 'IVORY COAST';
    $countrylist[] = 'JAMAICA';
    $countrylist[] = 'JAPAN';
    $countrylist[] = 'JORDAN';
    $countrylist[] = 'KAZAKHSTAN';
    $countrylist[] = 'KENYA';
    $countrylist[] = 'KIRIBATI, REP OF';
    $countrylist[] = 'KOREA, SOUTH';
    $countrylist[] = 'KUWAIT';
    $countrylist[] = 'KYRGYZSTAN';
    $countrylist[] = 'LAOS';
    $countrylist[] = 'LATVIA';
    $countrylist[] = 'LEBANON';
    $countrylist[] = 'LESOTHO';
    $countrylist[] = 'LIBERIA';
    $countrylist[] = 'LIBYA';
    $countrylist[] = 'LIECHTENSTEIN';
    $countrylist[] = 'LITHUANIA';
    $countrylist[] = 'LUXEMBOURG';
    $countrylist[] = 'MACAU';
    $countrylist[] = 'MACEDONIA';
    $countrylist[] = 'MADAGASCAR';
    $countrylist[] = 'MALAWI';
    $countrylist[] = 'MALAYSIA';
    $countrylist[] = 'MALDIVES';
    $countrylist[] = 'MALI';
    $countrylist[] = 'MALTA';
    $countrylist[] = 'MARSHALL ISLANDS';
    $countrylist[] = 'MARTINIQUE';
    $countrylist[] = 'MAURITANIA';
    $countrylist[] = 'MAURITIUS';
    $countrylist[] = 'MEXICO';
    $countrylist[] = 'MOLDOVA, REP OF';
    $countrylist[] = 'MONACO';
    $countrylist[] = 'MONGOLIA';
    $countrylist[] = 'MONTSERRAT';
    $countrylist[] = 'MOROCCO';
    $countrylist[] = 'MOZAMBIQUE';
    $countrylist[] = 'MYANMAR';
    $countrylist[] = 'NAMIBIA';
    $countrylist[] = 'NAURU, REP OF';
    $countrylist[] = 'NEPAL';
    $countrylist[] = 'NETHERLANDS';
    $countrylist[] = 'NEVIS';
    $countrylist[] = 'NEW CALEDONIA';
    $countrylist[] = 'NEW ZEALAND';
    $countrylist[] = 'NICARAGUA';
    $countrylist[] = 'NIGER';
    $countrylist[] = 'NIGERIA';
    $countrylist[] = 'NIUE';
    $countrylist[] = 'NORWAY';
    $countrylist[] = 'OMAN';
    $countrylist[] = 'PAKISTAN';
    $countrylist[] = 'PANAMA';
    $countrylist[] = 'PAPUA NEW GUINEA';
    $countrylist[] = 'PARAGUAY';
    $countrylist[] = 'PERU';
    $countrylist[] = 'PHILLIPINES';
    $countrylist[] = 'POLAND';
    $countrylist[] = 'PORTUGAL';
    $countrylist[] = 'PUERTO RICO';
    $countrylist[] = 'QATAR';
    $countrylist[] = 'REUNION ISLAND';
    $countrylist[] = 'ROMANIA';
    $countrylist[] = 'RUSSIAN FEDERATION';
    $countrylist[] = 'RWANDA';
    $countrylist[] = 'SAIPAN';
    $countrylist[] = 'SAO TOME & PRINCIPE';
    $countrylist[] = 'SAUDI ARABIA';
    $countrylist[] = 'SENEGAL';
    $countrylist[] = 'SEYCHELLES';
    $countrylist[] = 'SIERRA LEONE';
    $countrylist[] = 'SINGAPORE';
    $countrylist[] = 'SLOVAKIA';
    $countrylist[] = 'SLOVENIA';
    $countrylist[] = 'SOLOMON ISLANDS';
    $countrylist[] = 'SOUTH AFRICA';
    $countrylist[] = 'SPAIN';
    $countrylist[] = 'SRI LANKA';
    $countrylist[] = 'ST BARTHELEMY';
    $countrylist[] = 'ST EUSTATIUS';
    $countrylist[] = 'ST KITTS';
    $countrylist[] = 'ST LUCIA';
    $countrylist[] = 'ST MAARTEN';
    $countrylist[] = 'ST VINCENT';
    $countrylist[] = 'SUDAN';
    $countrylist[] = 'SURINAME';
    $countrylist[] = 'SWAZILAND';
    $countrylist[] = 'SWEDEN';
    $countrylist[] = 'SWITZERLAND';
    $countrylist[] = 'SYRIA';
    $countrylist[] = 'TAHITI';
    $countrylist[] = 'TAIWAN';
    $countrylist[] = 'TAJIKISTAN';
    $countrylist[] = 'TANZANIA';
    $countrylist[] = 'THAILAND';
    $countrylist[] = 'TOGO';
    $countrylist[] = 'TONGA';
    $countrylist[] = 'TRINIDAD & TOBAGO';
    $countrylist[] = 'TURKEY';
    $countrylist[] = 'TURKMENISTAN';
    $countrylist[] = 'TURKS & CAICOS ISLANDS';
    $countrylist[] = 'TUVALU';
    $countrylist[] = 'UGANDA';
    // $countrylist[] = 'UK';
    $countrylist[] = 'UKRAINE';
    $countrylist[] = 'UNITED KINGDOM';
    $countrylist[] = 'UNITED STATES';
    $countrylist[] = 'URUGUAY';
    $countrylist[] = 'UTD ARAB EMIRATES';
    $countrylist[] = 'UZBEKISTAN';
    $countrylist[] = 'VANUATU';
    $countrylist[] = 'VENEZUELA';
    $countrylist[] = 'VIETNAM';
    $countrylist[] = 'VIRGIN ISLANDS';
    $countrylist[] = 'VIRGIN ISLANDS (UK)';
    $countrylist[] = 'WESTERN SAMOA';
    $countrylist[] = 'YEMAN, REP OF';
    $countrylist[] = 'YUGOSLAVIA';
    $countrylist[] = 'ZAIRE';
    $countrylist[] = 'ZAMBIA';
    $countrylist[] = 'ZIMBABWE';

    if (in_array(strtoupper($country), $countrylist))
    {
        // make drop down
        $html = "<select id=\"{$name}\" name=\"{$name}\" {$extraattributes}>";
        foreach ($countrylist as $key => $value)
        {
            $value = htmlspecialchars($value);
            $html .= "<option value='$value'";
            if ($value == strtoupper($country))
            {
                $html .= " selected='selected'";
            }
            $html .= ">$value</option>\n";
        }
        $html .= "</select>";
    }
    else
    {
        // make editable input box
        $html = "<input maxlength='100' name='{$name}' size='40' value='{$country}' {$extraattributes} />";
    }
    return $html;
}


function check_email($email, $check_dns = FALSE)
{
    if ((preg_match('/(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)/', $email)) ||
    (preg_match('/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?)$/',$email)))
    {
        if ($check_dns)
        {
            $host = explode('@', $email);
            // Check for MX record
            if ( checkdnsrr($host[1], 'MX') ) return TRUE;
            // Check for A record
            if ( checkdnsrr($host[1], 'A') ) return TRUE;
            // Check for CNAME record
            if ( checkdnsrr($host[1], 'CNAME') ) return TRUE;
        }
        else
        {
            return TRUE;
        }
    }
    return FALSE;
}


function incident_get_next_target($incidentid)
{
    global $now;
    // Find the most recent SLA target that was met
    $sql = "SELECT sla,timestamp FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid='{$incidentid}' AND type='slamet' ORDER BY id DESC LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $target = '';
    if (mysql_num_rows($result) > 0)
    {
        $upd = mysql_fetch_object($result);
        switch ($upd->sla)
        {
            case 'opened':
                $target->type = 'initialresponse';
                break;
            case 'initialresponse':
                $target->type = 'probdef';
                break;
            case 'probdef':
                $target->type = 'actionplan';
                break;
            case 'actionplan':
                $target->type = 'solution';
                break;
            case 'solution':
                $target->type = '';
                break;
            case 'closed':
                $target->type = 'opened';
                break;
        }

        $target->since = calculate_incident_working_time($incidentid, $upd->timestamp, $now);
    }
    else
    {
        $target->type = 'regularcontact';
        $target->since = 0;
    }
    return $target;
}


function target_type_name($targettype)
{
    switch ($targettype)
    {
        case 'opened':
            $name = $GLOBALS['strOpened'];
            break;
        case 'initialresponse':
            $name = $GLOBALS['strInitialResponse'];
            break;
        case 'probdef':
            $name = $GLOBALS['strProblemDefinition'];
            break;
        case 'actionplan':
            $name = $GLOBALS['strActionPlan'];
            break;
        case 'solution':
            $name = $GLOBALS['strResolutionReprioritisation'];
            break;
        case 'closed':
            $name = '';
            break;
        case 'regularcontact':
            $name = '';
            break; // Contact Customer
        default:
            $name = '';
            break;
    }
    return $name;
}


function incident_get_next_review($incidentid)
{
    global $now;
    $sql = "SELECT timestamp FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid='{$incidentid}' AND type='reviewmet' ORDER BY id DESC LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $upd = mysql_fetch_object($result);
        $timesincereview = floor(($now - ($upd->timestamp)) / 60);
    }
    return $timesincereview;
}


/**
 * Converts a MySQL date to a UNIX Timestamp
 * @author Ivan Lucas
 * @param string $mysqldate - A date column from mysql
 * @param bool $utc - TRUE = Timestamp given is UTC
 *                    FALSE = Timestamp as system time
 * @returns integer. a UNIX Timestamp
 */
function mysql2date($mysqldate, $utc = FALSE)
{
    // for the zero/blank case, return 0
    if (empty($mysqldate))
    {
        return 0;
    }

    if ($mysqldate == '0000-00-00 00:00:00' OR $mysqldate == '0000-00-00')
    {
        return 0;
    }

    // Takes a MYSQL date and converts it to a proper PHP date
    $day = substr($mysqldate, 8, 2);
    $month = substr($mysqldate, 5, 2);
    $year = substr($mysqldate, 0, 4);

    if (strlen($mysqldate) > 10)
    {
        $hour = substr($mysqldate, 11, 2);
        $minute = substr($mysqldate, 14, 2);
        $second = substr($mysqldate, 17, 2);
        if ($utc) $phpdate = gmmktime($hour, $minute, $second, $month, $day, $year);
        else $phpdate = mktime($hour, $minute, $second, $month, $day, $year);
    }
    else
    {
        if ($utc) $phpdate = gmmktime(0, 0, 0, $month, $day, $year);
        else $phpdate = mktime(0, 0, 0, $month, $day, $year);
    }

    return $phpdate;
}


/**
    * Converts a MySQL timestamp to a UNIX Timestamp
    * @author Ivan Lucas
    * @param string $mysqldate  A timestamp column from mysql
    * @returns integer. a UNIX Timestamp
*/
function mysqlts2date($mysqldate)
{
    // for the zero/blank case, return 0
    if (empty($mysqldate)) return 0;

    // Takes a MYSQL date and converts it to a proper PHP date
    if (strlen($mysqldate) == 14)
    {
        $day = substr($mysqldate, 6, 2);
        $month = substr($mysqldate, 4, 2);
        $year = substr($mysqldate, 0, 4);
        $hour = substr($mysqldate, 8, 2);
        $minute = substr($mysqldate, 10, 2);
        $second = substr($mysqldate, 12, 2);
    }
    elseif (strlen($mysqldate) > 14)
    {
        $day = substr($mysqldate, 8, 2);
        $month = substr($mysqldate, 5, 2);
        $year = substr($mysqldate, 0, 4);
        $hour = substr($mysqldate, 11, 2);
        $minute = substr($mysqldate, 14, 2);
        $second = substr($mysqldate, 17, 2);
    }
    $phpdate = mktime($hour, $minute, $second, $month, $day, $year);
    return $phpdate;
}


function iso_8601_date($timestamp)
{
    $date_mod = date('Y-m-d\TH:i:s', $timestamp);
    $pre_timezone = date('O', $timestamp);
    $time_zone = substr($pre_timezone, 0, 3).":".substr($pre_timezone, 3, 2);
    $date_mod .= $time_zone;
    return $date_mod;
}

/**
    * Decide whether the time is during a public holiday
    * @author Paul Heaney
    * @param int $time  Timestamp to identify
    * @param array $publicholidays array of Holiday. Public holiday to compare against
    * @returns integer. If > 0 number of seconds left in the public holiday
*/
function is_public_holiday($time, $publicholidays)
{
    if (!empty($publicholidays))
    {
        foreach ($publicholidays AS $holiday)
        {
            if ($time >= $holiday->starttime AND $time <= $holiday->endtime)
            {
                return $holiday->endtime-$time;
            }
        }
    }

    return 0;
}

/**
 * Calculate the working time between two timestamps
 * @author Tom Gerrard, Ivan Lucas, Paul Heaney
 * @param int $t1. The start timestamp (earliest date/time)
 * @param int $t2. The ending timetamp (latest date/time)
 * @returns integer. the number of working minutes (minutes in the working day)
 */
function calculate_working_time($t1, $t2, $publicholidays)
{
    // PH 16/12/07 Old function commented out, rewritten to support public holidays. Old code to be removed once we're happy this is stable
    // KH 13/07/08 Use old function again for 3.35 beta
    // Note that this won't work if we have something
    // more complicated than a weekend

    global $CONFIG;
    $swd = $CONFIG['start_working_day'] / 3600;
    $ewd = $CONFIG['end_working_day'] / 3600;

    // Just in case the time params are the wrong way around ...
    if ( $t1 > $t2 )
    {
        $t3 = $t2;
        $t2 = $t1;
        $t1 = $t3;
    }

    // We don't need all the elements here.  hours, days and year are used
    // later on to calculate the difference.  wday is just used in this
    // section
    $at1 = getdate($t1);
    $at2 = getdate($t2);

    // Make sure that the start time is on a valid day and within normal hours
    // if it isn't then move it forward to the next work minute
    if ($at1['hours'] > $ewd)
    {
        do
        {
            $at1['yday'] ++;
            $at1['wday'] ++;
            $at1['wday'] %= 7;
            if ($at1['yday'] > 365)
            {
                $at1['year'] ++;
                $at1['yday'] = 0;
            }
        } while (!in_array($at1['wday'], $CONFIG['working_days']));

        $at1['hours']=$swd;
        $at1['minutes']=0;

    }
    else
    {
        if (($at1['hours'] < $swd) || (!in_array($at1['wday'], $CONFIG['working_days'])))
        {
            while (!in_array($at1['wday'], $CONFIG['working_days']))
            {
                $at1['yday'] ++;
                $at1['wday'] ++;
                $at1['wday'] %= 7;
                if ($at1['days']>365)
                {
                    $at1['year'] ++;
                    $at1['yday'] = 0;
                }
            }
            $at1['hours'] = $swd;
            $at1['minutes'] = 0;
        }
    }

    // Same again but for the end time
    // if it isn't then move it backward to the previous work minute
    if ( $at2['hours'] < $swd)
    {
        do
        {
            $at2['yday'] --;
            $at2['wday'] --;
            if ($at2['wday'] < 0) $at2['wday'] = 6;
            if ($at2['yday'] < 0)
            {
                $at2['yday'] = 365;
                $at2['year'] --;
            }
        } while (!in_array($at2['wday'], $CONFIG['working_days']));

        $at2['hours'] = $ewd;
        $at2['minutes'] = 0;
    }
    else
    {
        if (($at2['hours'] > $ewd) || (!in_array($at2['wday'], $CONFIG['working_days'])))
        {
            while (!in_array($at2['wday'],$CONFIG['working_days']))
            {
                $at2['yday'] --;
                $at2['wday'] --;
                if ($at2['wday'] < 0) $at2['wday'] = 6;
                if ($at2['yday'] < 0)
                {
                    $at2['yday'] = 365;
                    $at2['year'] --;
                }
            }
            $at2['hours'] = $ewd;
            $at2['minutes'] = 0;
        }
    }

    $t1 = mktime($at1['hours'], $at1['minutes'], 0, 1, $at1['yday'] + 1, $at1['year']);
    $t2 = mktime($at2['hours'], $at2['minutes'], 0, 1, $at2['yday'] + 1, $at2['year']);

    $weeks = floor(($t2 - $t1) / (60 * 60 * 24 * 7));
    $t1 += $weeks * 60 * 60 * 24 * 7;

    while ( date('z', $t2) != date('z', $t1) )
    {
        if (in_array(date('w', $t1), $CONFIG['working_days'])) $days++;
        $t1 += (60 * 60 * 24);
    }

    // this could be negative and that's not ok
    $coefficient = 1;
    if ($t2 < $t1)
    {
        $t3 = $t2;
        $t2 = $t1;
        $t1 = $t3;
        $coefficient =- 1;
    }

    $min = floor( ($t2 - $t1) / 60 ) * $coefficient;

    $minutes = $min + ($weeks * count($CONFIG['working_days']) + $days ) * ($ewd - $swd) * 60;

    return $minutes;

//new version below
/*
    global $CONFIG;
    $swd = $CONFIG['start_working_day']/3600;
    $ewd = $CONFIG['end_working_day']/3600;

// Just in case they are the wrong way around ...

    if ( $t1 > $t2 )
    {
        $t3 = $t2;
        $t2 = $t1;
        $t1 = $t3;
    }

    $currenttime = $t1;

    $timeworked = 0;

    $t2date = getdate($t2);

    $midnight = 1440; // 24 * 60  minutes

    while ($currenttime < $t2) // was <=
    {
        $time = getdate($currenttime);

        $ph = 0;

        if (in_array($time['wday'], $CONFIG['working_days']) AND $time['hours'] >= $swd
            AND $time['hours'] <= $ewd AND (($ph = is_public_holiday($currenttime, $publicholidays)) == 0))
        {
            if ($t2date['yday'] == $time['yday'] AND $t2date['year'] == $time['year'])
            {
                // if end same day as time
                $c = $t2 - $currenttime;
                $timeworked += $c/60;
                $currenttime += $c;
            }
            else
            {
                // End on a different day
                $secondsintoday = (($t2date['hours']*60)*60)+($t2date['minutes']*60)+$t2date['seconds'];

                $timeworked += ($CONFIG['end_working_day']-$secondsintoday)/60;

                $currenttime += ($midnight*$secondsintoday)+$swd;
            }
        }
        else
        {
            // Jump closer to the next work minute
            if (!in_array($time['wday'], $CONFIG['working_days']))
            {
                // Move to next day
                $c = ($time['hours'] * 60) + $time['minutes'];
                $diff = $midnight - $c;
                $currenttime += ($diff * 60); // to seconds

                // Jump to start of working day
                $currenttime += ($swd * 60);
            }
            else if ($time['hours'] < $swd)
            {
                // jump to beginning of working day
                $c = ($time['hours'] * 60) + $time['minutes'];
                $diff = ($swd * 60) - $c;
                $currenttime += ($diff * 60); // to seconds
            }
            else if ($time['hours'] > $ewd)
            {
                // Jump to the start of the next working day
                $c = ($midnight - (($time['hours'] * 60) + $time['minutes'])) + ($swd * 60);
                $currenttime += ($c * 60);
            }
            else if ($ph != 0)
            {
                // jump to the minute after the public holiday
                $currenttime += $ph + 60;

                // Jump to start of working day
                $currenttime += ($swd * 60);
            }
            else
            {
                $currenttime += 60;  // move to the next minute
            }
        }
    }

    return $timeworked;
*/
}


/**
 * @author Ivan Lucas
 */
function is_active_status($status, $states)
{
    if (in_array($status, $states)) return false;
    else return true;
}


/**
 * Function to get an array of public holidays
 * @author Paul Heaney
 * @param int $startdate - UNIX Timestamp of start of the period to find public holidays in
 * @param int $enddate - UNIX Timestamp of end of the period to find public holidays in
 * @return array of Holiday
 */
function get_public_holidays($startdate, $enddate)
{
    $sql = "SELECT * FROM `{$GLOBALS['dbHolidays']}` ";
    $sql .= "WHERE type = ".HOL_PUBLIC." AND (`date` >= FROM_UNIXTIME({$startdate}) AND `date` <= FROM_UNIXTIME({$enddate}))";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $publicholidays = array();

    if (mysql_num_rows($result) > 0)
    {
        // Assume public holidays are ALL day
        while ($obj = mysql_fetch_object($result))
        {
            $holiday = new Holiday();
            $holiday->starttime = $obj->date;
            $holiday->endtime = ($obj->date + (60 * 60 * 24));

            $publicholidays[] = $holiday;
        }
    }
    return $publicholidays;
}


/**
 * Calculate the engineer working time between two timestamps for a given incident
 i.e. ignore times when customer has action
 * @author Ivan Lucas
 @param int $incidentid - The incident ID to perform a calculation on
 @param int $t1 - UNIX Timestamp. Start of range
 @param int $t2 - UNIX Timestamp. End of range
 @param array $states (optional) Does not count time when the incident is set to
 any of the states in this array. (Default is closed, awaiting closure and awaiting customer action)
 */
function calculate_incident_working_time($incidentid, $t1, $t2, $states=array(2,7,8))
{
    if ( $t1 > $t2 )
    {
        $t3 = $t2;
        $t2 = $t1;
        $t1 = $t3;
    }

    $startofday = mktime(0, 0, 0, date("m", $t1), date("d", $t1), date("Y", $t1));
    $endofday = mktime(23, 59, 59, date("m", $t2), date("d", $t2), date("Y", $t2));

    $publicholidays = get_public_holidays($startofday, $endofday);

    $sql = "SELECT id, currentstatus, timestamp FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid='{$incidentid}' ORDER BY id ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $time = 0;
    $timeptr = 0;
    $laststatus = 2; // closed
    while ($update = mysql_fetch_array($result))
    {
        //  if ($t1<=$update['timestamp'])
        if ($t1 <= $update['timestamp'])
        {
            if ($timeptr == 0)
            {
                // This is the first update
                // If it's active, set the ptr = t1
                // otherwise set to current timestamp ???
                if (is_active_status($laststatus, $states))
                {
                    $timeptr = $t1;
                }
                else
                {
                    $timeptr = $update['timestamp'];
                }
            }

            if ($t2 < $update['timestamp'])
            {
                // If we have reached the very end of the range, increment time to end of range, break
                if (is_active_status($laststatus, $states))
                {
                    $time += calculate_working_time($timeptr, $t2, $publicholidays);
                }
                break;
            }

            // if status has changed or this is the first (active update)
            if (is_active_status($laststatus, $states) != is_active_status($update['currentstatus'], $states))
            {
                // If it's active and we've not reached the end of the range, increment time
                if (is_active_status($laststatus, $states) && ($t2 >= $update['timestamp']))
                {
                    $time += calculate_working_time($timeptr, $update['timestamp'], $publicholidays);
                }
                else
                {
                    $timeptr = $update['timestamp'];
                }
                // if it's not active set the ptr
            }
        }
        $laststatus = $update['currentstatus'];
    }
    mysql_free_result($result);

    // Calculate remainder
    if (is_active_status($laststatus, $states) && ($t2 >= $update['timestamp']))
    {
        $time += calculate_working_time($timeptr, $t2, $publicholidays);
    }

    return $time;
}


/**
 * Takes a UNIX Timestamp and returns a string with a pretty readable date
 * @param int $date
 * @param string $lang. takes either 'user' or 'system' as to which language to use
 * @returns string
 */
function readable_date($date, $lang = 'user')
{
    global $SYSLANG;
    //
    // e.g. Yesterday @ 5:28pm
    if (ldate('dmy', $date) == ldate('dmy', time()))
    {
        if ($lang == 'user')
        {
            $datestring = "{$GLOBALS['strToday']} @ ".ldate('g:ia', $date);
        }
        else
        {
            $datestring = "{$SYSLANG['strToday']} @ ".ldate('g:ia', $date);
        }
    }
    elseif (ldate('dmy', $date) == ldate('dmy', (time()-86400)))
    {
        if ($lang == 'user')
        {
            $datestring = "{$GLOBALS['strYesterday']} @ ".ldate('g:ia', $date);
        }
        else
        {
            $datestring = "{$SYSLANG['strYesterday']} @ ".ldate('g:ia', $date);
        }
    }
    else
    {
        $datestring = ldate("l jS M y @ g:ia", $date);
    }
    return $datestring;
}


/**
 * Return the email address of the notify contact of the given contact
 * @author Ivan Lucas
 * @returns string. email address.
 */
function contact_notify_email($contactid)
{
    global $dbContacts;
    $sql = "SELECT notify_contactid FROM `{$dbContacts}` WHERE id='{$contactid}' LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($notify_contactid) = mysql_fetch_row($result);

    $sql = "SELECT email FROM `{$dbContacts}` WHERE id='{$notify_contactid}' LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($email) = mysql_fetch_row($result);

    return $email;
}


/**
 * Returns the contact ID of the notify contact for the given contact ID
 * @author Ivan Lucas
 * @param int $contactid. Contact ID
 * @param int $level. Number of levels to recurse upwards
 * @note If Level is specified and is >= 1 then the notify contact is
 * found recursively, ie. the notify contact of the notify contact etc.
 */
function contact_notify($contactid, $level=0)
{
    global $dbContacts;
    $notify_contactid = 0;
    if ($level == 0)
    {
        return $contactid;
    }
    else
    {
        $sql = "SELECT notify_contactid FROM `{$dbContacts}` WHERE id='{$contactid}' LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        list($notify_contactid) = mysql_fetch_row($result);

        if ($level > 0)
        {
            $newlevel = $level -1;
            $notify_contactid = contact_notify($notify_contactid, $newlevel);

        }
        return $notify_contactid;
    }
}


/**
 * HTML select box listing substitute engineers
 * @author Ivan Lucas
 */
function software_backup_dropdown($name, $userid, $softwareid, $backupid)
{
    global $dbUsers, $dbUserSoftware, $dbSoftware;
    $sql = "SELECT *, u.id AS userid FROM `{$dbUserSoftware}` AS us, `{$dbSoftware}` AS s, `{$dbUsers}` AS u ";
    $sql .= "WHERE us.softwareid = s.id ";
    $sql .= "AND s.id = '{$softwareid}' ";
    $sql .= "AND userid != '{$userid}' AND u.status > 0 ";
    $sql .= "AND us.userid = u.id ";
    $sql .= " ORDER BY realname";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $countsw = mysql_num_rows($result);
    if ($countsw >= 1)
    {
        $html = "<select name='{$name}'>\n";
        $html .= "<option value='0'";
        if ($user->userid==0) $html .= " selected='selected'";
        $html .= ">{$GLOBALS['strNone']}</option>\n";
        while ($user = mysql_fetch_object($result))
        {
            $html .= "<option value='{$user->userid}'";
            if ($user->userid == $backupid) $html .= " selected='selected'";
            $html .= ">{$user->realname}</option>\n";
        }
        $html .= "</select>\n";
    }
    else
    {
        $html .= "<input type='hidden' name='$name' value='0' />{$GLOBALS['strNoneAvailable']}";
    }
    return ($html);
}


/**
 *
 * @author Ivan Lucas
 */
function software_backup_userid($userid, $softwareid)
{
    global $dbUserSoftware;
    $backupid = 0; // default
    // Find out who is the substitute for this user/skill
    $sql = "SELECT backupid FROM `{$dbUserSoftware}` WHERE userid = '{$userid}' AND softwareid = '{$softwareid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($backupid) = mysql_fetch_row($result);
    $backup1 = $backupid;

    // If that substitute is not accepting then try and find another
    if (empty($backupid) OR user_accepting($backupid) != 'Yes')
    {
        $sql = "SELECT backupid FROM `{$dbUserSoftware}` WHERE userid='{$backupid}' AND userid!='{$userid}' ";
        $sql .= "AND softwareid='{$softwareid}' AND backupid!='{$backup1}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        list($backupid) = mysql_fetch_row($result);
        $backup2 = $backupid;
    }

    // One more iteration, is the backup of the backup accepting?  If not try another
    if (empty($backupid) OR user_accepting($backupid)!='Yes')
    {
        $sql = "SELECT backupid FROM `{$dbUserSoftware}` WHERE userid='{$backupid}' AND userid!='{$userid}' ";
        $sql .= "AND softwareid='{$softwareid}' AND backupid!='{$backup1}' AND backupid!='{$backup2}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        list($backupid) = mysql_fetch_row($result);
    }
    return ($backupid);
}


/**
 * Switches incidents temporary owners to the backup/substitute engineer depending on the setting of 'accepting'
 * @author Ivan Lucas
 * @param int $userid. The userid of the user who's status has changed.
 * @param string $accepting. 'yes' or 'no' to indicate whether the user is accepting
 * @note if the $accepting parameter is 'no' then the function will attempt to temporarily assign
 * all the open incidents that the user owns to the users defined substitute engineers
 * If Substitute engineers cannot be found or they themselves are not accepting, the given users incidents
 * are placed in the holding queue
 */
function incident_backup_switchover($userid, $accepting)
{
    global $now, $dbIncidents, $dbUpdates, $dbTempAssigns, $dbUsers, $dbUserStatus;

    $usersql = "SELECT u.*, us.name AS statusname ";
    $usersql .= "FROM `{$dbUsers}` AS u, `{$dbUserStatus}` AS us ";
    $usersql .= "WHERE u.id = '{$userid}' AND u.status = us.id";
    $userresult = mysql_query($usersql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $user = mysql_fetch_row($userresult);

    if (strtolower($accepting) == 'no')
    {
        // Look through the incidents that this user OWNS (and are not closed)
        $sql = "SELECT * FROM `{$dbIncidents}` WHERE (owner='{$userid}' OR towner='{$userid}') AND status!=2";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        while ($incident = mysql_fetch_object($result))
        {
            // Try and find a backup/substitute engineer
            $backupid = software_backup_userid($userid, $incident->softwareid);

            if (empty($backupid) OR user_accepting($backupid) == 'No')
            {
                // no backup engineer found so add to the holding queue
                // Look to see if this assignment is in the queue already
                $fsql = "SELECT * FROM `{$dbTempAssigns}` WHERE incidentid='{$incident->id}' AND originalowner='{$userid}'";
                $fresult = mysql_query($fsql);
                if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                if (mysql_num_rows($fresult) < 1)
                {
                    // it's not in the queue, and the user isn't accepting so add it
                    //$userstatus=user_status($userid);
                    $userstatus = $user['status'];
                    $usql = "INSERT INTO `{$dbTempAssigns}` (incidentid,originalowner,userstatus) VALUES ('{$incident->id}', '{$userid}', '$userstatus')";
                    mysql_query($usql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                }
            }
            else
            {
                // do an automatic temporary reassign
                // update incident
                $rusql = "UPDATE `{$dbIncidents}` SET ";
                $rusql .= "towner='{$backupid}', lastupdated='$now' WHERE id='{$incident->id}' LIMIT 1";
                mysql_query($rusql);
                if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

                // add update
                $username=user_realname($userid);
                //$userstatus = userstatus_name(user_status($userid));
                $userstatus = $user['statusname'];
                //$usermessage=user_message($userid);
                $usermessage = $user['message'];
                $bodytext = "Previous Incident Owner ({$username}) {$userstatus}  {$usermessage}";
                $assigntype = 'tempassigning';
                $risql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, bodytext, type, timestamp, currentowner, currentstatus) ";
                $risql .= "VALUES ('{$incident->id}', '0', '{$bodytext}', '{$assigntype}', '{$now}', ";
                $risql .= "'{$backupid}', ";
                $risql .= "'{$incident->status}')";
                mysql_query($risql);
                if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

                // Look to see if this assignment is in the queue already
                $fsql = "SELECT * FROM `{$dbTempAssigns}` WHERE incidentid='{$incident->id}' AND originalowner='{$userid}'";
                $fresult = mysql_query($fsql);
                if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                if (mysql_num_rows($fresult) < 1)
                {
                    //$userstatus=user_status($userid);
                    $userstatus = $user['status'];
                    $usql = "INSERT INTO `{$dbTempAssigns}` (incidentid,originalowner,userstatus,assigned) VALUES ('{$incident->id}', '{$userid}', '$userstatus','yes')";
                    mysql_query($usql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                }
                else
                {
                    // mark the temp assigns table so it's not showing in the holding queue
                    $tasql = "UPDATE `{$dbTempAssigns}` SET assigned='yes' WHERE originalowner='$userid' AND incidentid='{$incident->id}' LIMIT 1";
                    mysql_query($tasql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                }
            }
        }
    }
    elseif ($accepting=='')
    {
        // Do nothing when accepting status doesn't exist
    }
    else
    {
        // The user is now ACCEPTING, so first have a look to see if there are any reassignments in the queue
        $sql = "SELECT * FROM `{$dbTempAssigns}` WHERE originalowner='{$userid}' ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        while ($assign = mysql_fetch_object($result))
        {
            if ($assign->assigned == 'yes')
            {
                // Incident has actually been reassigned, so have a look if we can grab it back.
                $lsql = "SELECT id,status FROM `{$dbIncidents}` ";
                $lsql .= "WHERE id='{$assign->incidentid}' AND owner='{$assign->originalowner}' AND towner!=''";
                $lresult = mysql_query($lsql);
                if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                while ($incident = mysql_fetch_object($lresult))
                {
                    // Find our tempassign
                    $usql = "SELECT id,currentowner FROM `{$dbUpdates}` ";
                    $usql .= "WHERE incidentid='{$incident->id}' AND userid='0' AND type='tempassigning' ";
                    $usql .= "ORDER BY id DESC LIMIT 1";
                    $uresult = mysql_query($usql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    list($prevassignid,$tempowner) = mysql_fetch_row($uresult);

                    // Look to see if the temporary owner has updated the incident since we temp assigned it
                    // If he has, we leave it in his queue
                    $usql = "SELECT id FROM `{$dbUpdates}` ";
                    $usql .= "WHERE incidentid='{$incident->id}' AND id > '{$prevassignid}' AND userid='{$tempowner}' LIMIT 1 ";
                    $uresult = mysql_query($usql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    if (mysql_num_rows($uresult) < 1)
                    {
                        // Incident appears not to have been updated by the temporary owner so automatically reassign back to orignal owner
                        // update incident
                        $rusql = "UPDATE `{$dbIncidents}` SET ";
                        $rusql .= "towner='', lastupdated='{$now}' WHERE id='{$incident->id}' LIMIT 1";
                        mysql_query($rusql);
                        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

                        // add update
                        $username = user_realname($userid);
                        //$userstatus = userstatus_name(user_status($userid));
                        $userstatus = $user['statusname'];
                        //$usermessage=user_message($userid);
                        $usermessage = $user['message'];
                        $bodytext = "Reassigning to original owner {$username} ({$userstatus})";
                        $assigntype = 'reassigning';
                        $risql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, bodytext, type, timestamp, currentowner, currentstatus) ";
                        $risql .= "VALUES ('{$incident->id}', '0', '{$bodytext}', '{$assigntype}', '{$now}', ";
                        $risql .= "'{$backupid}', ";
                        $risql .= "'{$incident->status}')";
                        mysql_query($risql);
                        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

                        // remove from assign queue now, all done
                        $rsql = "DELETE FROM `{$dbTempAssigns}` WHERE incidentid='{$assign->incidentid}' AND originalowner='{$assign->originalowner}'";
                        mysql_query($rsql);
                        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                    }
                }
            }
            else
            {
                // now have a look to see if the reassign was completed
                $ssql = "SELECT id FROM `{$dbIncidents}` WHERE id='{$assign->incidentid}' LIMIT 1";
                $sresult = mysql_query($ssql);
                if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                if (mysql_num_rows($sresult) >= 1)
                {
                    // reassign wasn't completed, or it was already assigned back, simply remove from assign queue
                    $rsql = "DELETE FROM `{$dbTempAssigns}` WHERE incidentid='{$assign->incidentid}' AND originalowner='{$assign->originalowner}'";
                    mysql_query($rsql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                }
            }
        }
    }
    return;
}


/**
 * Format an external ID (From an escalation partner) as HTML
 * @author Ivan Lucas
 * @param int $externalid. An external ID to format
 * @param int $escalationpath. Escalation path ID
 * @returns HTML
 */
function format_external_id($externalid, $escalationpath='')
{
    global $CONFIG, $dbEscalationPaths;

    if (!empty($escalationpath))
    {
        // Extract escalation path
        $epsql = "SELECT id, name, track_url, home_url, url_title FROM `{$dbEscalationPaths}` ";
        if (!empty($escalationpath)) $epsql .= "WHERE id='$escalationpath' ";
        $epresult = mysql_query($epsql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($epresult) >= 1)
        {
            while ($escalationpath = mysql_fetch_object($epresult))
            {
                $epath['name'] = $escalationpath->name;
                $epath['track_url'] = $escalationpath->track_url;
                $epath['home_url'] = $escalationpath->home_url;
                $epath['url_title'] = $escalationpath->url_title;
            }
            if (!empty($externalid))
            {
                $epathurl = str_replace('%externalid%', $externalid, $epath['track_url']);
                $html = "<a href='{$epathurl}' title='{$epath['url_title']}'>{$externalid}</a>";
            }
            else
            {
                $epathurl = $epath['home_url'];
                $html = "<a href='{$epathurl}' title='{$epath['url_title']}'>{$epath['name']}</a>";
            }
        }
    }
    else
    {
        $html = $externalid;
    }
    return $html;
}


// Converts a PHP.INI integer into a byte value
function return_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val{strlen($val)-1});
    switch ($last)
    {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}


// FIXME use this instead of hardcoding tabs
function draw_tabs($tabsarray, $selected='')
{
    if ($selected=='') $selected=key($tabsarray);
    $html .= "<div class='tabcontainer'>";
    $html .= "<ul class='tabnav'>";
    foreach ($tabsarray AS $tab => $url)
    {
        $html .= "<li><a href='$url'";
        if (strtolower($tab) == strtolower($selected))
        {
            $html .= " class='active'";
        }
        $tab = str_replace('_', ' ', $tab);
        $html .= ">$tab</a></li>\n";
    }
    $html .= "</ul>";
    $html .= "</div>";

return ($html);
}


/**
 * Identifies whether feedback should be send for this contract,
 * This checks against $CONFIG['no_feedback_contracts'] to see if the contract is set to receive no feedback
 * @param $contractid int The contract ID to check
 * @return bool TRUE if feedback should be sent, false otherwise
 * @author Paul Heaney
 */
function send_feedback($contractid)
{
    global $CONFIG;
    foreach ($CONFIG['no_feedback_contracts'] AS $contract)
    {
        if ($contract == $contractid)
        {
            return FALSE;
        }
    }

    return TRUE;
}

/**
 * Creates a blank feedback form response
 * @param $formid int The feedback form to use
 * @param $incidentid int The incident to generate the form for
 * @return int The form ID
 */
function create_incident_feedback($formid, $incidentid)
{
    global $dbFeedbackRespondents;
    $contactid = incident_contact($incidentid);
    $email = contact_email($contactid);

    $sql = "INSERT INTO `{$dbFeedbackRespondents}` (formid, contactid, email, incidentid) VALUES (";
    $sql .= "'".mysql_real_escape_string($formid)."', ";
    $sql .= "'".mysql_real_escape_string($contactid)."', ";
    $sql .= "'".mysql_real_escape_string($email)."', ";
    $sql .= "'".mysql_real_escape_string($incidentid)."') ";
    mysql_query($sql);
    if (mysql_error()) trigger_error ("MySQL Error: ".mysql_error(), E_USER_ERROR);
    $blankformid = mysql_insert_id();
    return $blankformid;
}


function file_permissions_info($perms)
{
    if (($perms & 0xC000) == 0xC000) $info = 's';
    elseif (($perms & 0xA000) == 0xA000) $info = 'l';
    elseif (($perms & 0x8000) == 0x8000) $info = '-';
    elseif (($perms & 0x6000) == 0x6000) $info = 'b';
    elseif (($perms & 0x4000) == 0x4000) $info = 'd';
    elseif (($perms & 0x2000) == 0x2000) $info = 'c';
    elseif (($perms & 0x1000) == 0x1000) $info = 'p';
    else $info = 'u';

    // Owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
            (($perms & 0x0800) ? 's' : 'x' ) :
            (($perms & 0x0800) ? 'S' : '-'));

    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
            (($perms & 0x0400) ? 's' : 'x' ) :
            (($perms & 0x0400) ? 'S' : '-'));

    // World
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
            (($perms & 0x0200) ? 't' : 'x' ) :
            (($perms & 0x0200) ? 'T' : '-'));

    return $info;
}




function external_escalation($escalated, $incid)
{
    foreach ($escalated as $i => $id)
    {
        if ($id == $incid)
        {
            return "yes";
        }
    }

    return "no";
}



/**
 * Converts BBcode to HTML
 * @author Paul Heaney
 * @param string $text. Text with BBCode
 * @returns string HTML
 */
function bbcode($text)
{
    global $CONFIG;
    $bbcode_regex = array(0 => "/\[b\](.*?)\[\/b\]/s",
                        1 => "/\[i\](.*?)\[\/i\]/s",
                        2 => "/\[u\](.*?)\[\/u\]/s",
                        3 => "/\[quote\](.*?)\[\/quote\]/s",
                        4 => "/\[size=(.+?)\](.+?)\[\/size\]/is",
                        //5 => "/\[url\](.*?)\[\/url\]/s",
                        6 => "/\[size=(.+?)\](.+?)\[\/size\]/is",
                        7 => "/\[img\](.*?)\[\/img\]/s",
                        8 => "/\[size=(.+?)\](.+?)\[\/size\]/is",
                        9 => "/\[color\](.*?)\[\/color\]/s",
                        10 => "/\[size=(.+?)\](.+?)\[\/size\]/is",
                        11 => "/\[size\](.*?)\[\/size\]/s",
                        12 => "/\[code\](.*?)\[\/code\]/s",
                        13 => "/\[hr\]/s",
                        14 => "/\[s\](.*?)\[\/s\]/s",
                        15 => "/\[\[att\=(.*?)]](.*?)\[\[\/att]]/s",
                        16 => "/\[url=(.+?)\](.+?)\[\/url\]/is");

    $bbcode_replace = array(0 => "<strong>$1</strong>",
                            1 => "<em>$1</em>",
                            2 => "<u>$1</u>",
                            3 => "<blockquote><p>$1</p></blockquote>",
                            4 => "<blockquote cite=\"$1\"><p>$1 said:<br />$2</p></blockquote>",
                            //5 => '<a href="$1" title="$1">$1</a>',
                            6 => "<a href=\"$1\" title=\"$1\">$2</a>",
                            7 => "<img src=\"$1\" alt=\"User submitted image\" />",
                            8 => "<span style=\"color:$1\">$2</span>",
                            9 => "<span style=\"color:red;\">$1</span>",
                            10 => "<span style=\"font-size:$1\">$2</span>",
                            11 => "<span style=\"font-size:large\">$1</span>",
                            12 => "<code>$1</code>",
                            13 => "<hr />",
                            14 => "<span style=\"text-decoration:line-through\">$1</span>",
                            15 => "<a href=\"{$CONFIG['application_webpath']}download.php?id=$1\">$2</a>",
                            16 => "<a href=\"$1\">$2</a>");

    $html = preg_replace($bbcode_regex, $bbcode_replace, $text);
    return $html;
}


function strip_bbcode_tooltip($text)
{
    $bbcode_regex = array(0 => '/\[url\](.*?)\[\/url\]/s',

                        1 => '/\[url\=(.*?)\](.*?)\[\/url\]/s',
                        2 => '/\[color\=(.*?)\](.*?)\[\/color\]/s',
                        3 => '/\[size\=(.*?)\](.*?)\[\/size\]/s',
                        4 => '/\[blockquote\=(.*?)\](.*?)\[\/blockquote\]/s',
                        5 => '/\[blockquote\](.*?)\[\/blockquote\]/s',
                        6 => "/\[s\](.*?)\[\/s\]/s");
    $bbcode_replace = array(0 => '$1',
                            1 => '$2',
                            2 => '$2',
                            3 => '$2',
                            4 => '$2',
                            5 => '$1',
                            6 => '$1'
                            );

    return preg_replace($bbcode_regex, $bbcode_replace, $text);
}


/**
 * Produces a HTML toolbar for use with a textarea or input box for entering bbcode
 * @author Ivan Lucas
 * @param string $elementid. HTML element ID of the textarea or input
 * @returns string HTML
 */
function bbcode_toolbar($elementid)
{
    $html = "\n<div class='bbcode_toolbar'>BBCode: ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[b]', '[/b]')\">B</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[i]', '[/i]')\">I</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[u]', '[/u]')\">U</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[s]', '[/s]')\">S</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[quote]', '[/quote]')\">Quote</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[url]', '[/url]')\">Link</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[img]', '[/img]')\">Img</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[color]', '[/color]')\">Color</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[size]', '[/size]')\">Size</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[code]', '[/code]')\">Code</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '', '[hr]')\">HR</a> ";
    $html .= "</div>\n";
    return $html;
}


function parse_updatebody($updatebody, $striptags = TRUE)
{
    if (!empty($updatebody))
    {
        $updatebody = str_replace("&lt;hr&gt;", "[hr]\n", $updatebody);
        if ($striptags)
        {
            $updatebody = strip_tags($updatebody);
        }
        else
        {
            $updatebody = str_replace("<hr>", "", $updatebody);
        }
        $updatebody = nl2br($updatebody);
        $updatebody = str_replace("&amp;quot;", "&quot;", $updatebody);
        $updatebody = str_replace("&amp;gt;", "&gt;", $updatebody);
        $updatebody = str_replace("&amp;lt;", "&lt;", $updatebody);
        // Insert path to attachments
        //new style
        $updatebody = preg_replace("/\[\[att\=(.*?)\]\](.*?)\[\[\/att\]\]/","$2", $updatebody);
        //old style
        $updatebody = preg_replace("/\[\[att\]\](.*?)\[\[\/att\]\]/","$1", $updatebody);
        //remove tags that are incompatable with tool tip
        $updatebody = strip_bbcode_tooltip($updatebody);
        //then show compatable BBCode
        $updatebody = bbcode($updatebody);
        if (strlen($updatebody) > 490) $updatebody .= '...';
    }

    return $updatebody;
}


/**
 * Produces a HTML form for adding a note to an item
 * @param $linkid int The link type to be used
 * @param $refid int The ID of the item this note if for
 * @return string The HTML to display
 */
function add_note_form($linkid, $refid)
{
    global $now, $sit, $iconset;
    $html = "<form name='addnote' action='note_add.php' method='post'>";
    $html .= "<div class='detailhead note'> <div class='detaildate'>".readable_date($now)."</div>\n";
    $html .= icon('note', 16, $GLOBALS['strNote ']);
    $html .= " ".sprintf($GLOBALS['strNewNoteByX'], user_realname($sit[2]))."</div>\n";
    $html .= "<div class='detailentry note'>";
    $html .= "<textarea rows='3' cols='40' name='bodytext' style='width: 94%; margin-top: 5px; margin-bottom: 5px; margin-left: 3%; margin-right: 3%; background-color: transparent; border: 1px dashed #A2A86A;'></textarea>";
    if (!empty($linkid))
    {
        $html .= "<input type='hidden' name='link' value='{$linkid}' />";
    }
    else
    {
        $html .= "&nbsp;{$GLOBALS['strLInk']} <input type='text' name='link' size='3' />";
    }

    if (!empty($refid))
    {
        $html .= "<input type='hidden' name='refid' value='{$refid}' />";
    }
    else
    {
        $html .= "&nbsp;{$GLOBALS['strRefID']} <input type='text' name='refid' size='4' />";
    }

    $html .= "<input type='hidden' name='action' value='addnote' />";
    $html .= "<input type='hidden' name='rpath' value='{$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']}' />";
    $html .= "<div style='text-align: right'><input type='submit' value='{$GLOBALS['strAddNote']}' /></div>\n";
    $html .= "</div>\n";
    $html .= "</form>";
    return $html;
}


/**
 * Produces HTML of all notes assigned to an item
 * @param $linkid int The link type
 * @param $refid int The ID of the item the notes are linked to
 * @param $delete bool Whether its possible to delet notes (default TRUE)
 * @return string HTML of the notes
 */
function show_notes($linkid, $refid, $delete = TRUE)
{
    global $sit, $iconset, $dbNotes;
    $sql = "SELECT * FROM `{$dbNotes}` WHERE link='{$linkid}' AND refid='{$refid}' ORDER BY timestamp DESC, id DESC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $countnotes = mysql_num_rows($result);
    if ($countnotes >= 1)
    {
        while ($note = mysql_fetch_object($result))
        {
            $html .= "<div class='detailhead note'> <div class='detaildate'>".readable_date(mysqlts2date($note->timestamp));
            if ($delete)
            {
                $html .= "<a href='note_delete.php?id={$note->id}&amp;rpath=";
                $html .= "{$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']}' ";
                $html .= "onclick=\"return confirm_action('{$strAreYouSureDelete}');\">";
                $html .= icon('delete', 16)."</a>";
            }
            $html .= "</div>\n"; // /detaildate
            $html .= icon('note', 16)." ";
            $html .= sprintf($GLOBALS['strNoteAddedBy'], user_realname($note->userid,TRUE));
            $html .= "</div>\n"; // detailhead
            $html .= "<div class='detailentry note'>";
            $html .= nl2br(bbcode($note->bodytext));
            $html .= "</div>\n";
        }
    }
    return $html;
}


/**
 * Produces a HTML dashlet 'window' for display on the dashboard
 * @author Ivan Lucas
 * @param string $dashboard. Dashboard component name.
 * @param string $dashletid. The table row ID of that we are 'drawing' this dashlet into and
 *                           the ID of the dashboard component instance as recorded in the users settings
 *                           as a single string, this is received by the dashlet from dashboard_do()
 * @param string $icon. HTML for an icon to be displayed on the dashlet window
 * @param string $title. A title for the dashlet, also displayed on the dashlet window
 * @param string $link. URL of a page to link to from the dashlet window (link on the title)
 * @param string $content. HTML content to display inside the dashlet window
 * @note This function looks for the existence of two dashboard component functions
 *       dashboard_*_display() and dashboard_*_edit(), (where * is the name of the dashlet)
 *       if these are found the dashlet will use ajax and call these functions for it's
 *       main display (and refreshing) and to edit settings.
 * @returns string HTML
 */
function dashlet($dashboard, $dashletid, $icon, $title='', $link='', $content='')
{
    global $strLoading;
    if (empty($icon)) $icon = icon('dashboard', 16);
    if (empty($title)) $title = $GLOBALS['strUntitled'];
    $displayfn = "dashboard_{$dashboard}_display";
    $editfn = "dashboard_{$dashboard}_edit";

    $html .= "<div class='windowbox' id='{$dashletid}'>";
    $html .= "<div class='windowtitle'>";
    $html .= "<div class='innerwindow'>";
    if (function_exists($displayfn))
    {
        $html .= "<a href=\"javascript:get_and_display('ajaxdata.php?action=dashboard_display&amp;dashboard={$dashboard}&amp;did={$dashletid}','win{$dashletid}',true);\">";
        $html .= icon('reload', 16, '', '', "refresh{$dashletid}")."</a>";
    }

    if (function_exists($editfn))
    {
        $html .= "<a href=\"javascript:get_and_display('ajaxdata.php?action=dashboard_edit&amp;dashboard={$dashboard}&amp;did={$dashletid}','win{$dashletid}',false);\">";
        $html .= icon('edit', 16)."</a>";
    }
    $html .= "</div>";
    if (!empty($link)) $html .= "<a href=\"{$link}\">{$icon}</a> <a href=\"{$link}\">{$title}</a>";
    else $html .= "{$icon} {$title}";
    $html .= "</div>\n";
    $html .= "<div class='window' id='win{$dashletid}'>";
    $html .= $content;
    $displayfn = "dashboard_{$dashboard}_display";
    if (function_exists($displayfn))
    {
        $html .= "<script type='text/javascript'>\n//<![CDATA[\nget_and_display('ajaxdata.php?action=dashboard_display&dashboard={$dashboard}','win{$dashletid}',true);\n//]]>\n</script>\n";
    }
    $html .= "</div></div>";

    return $html;
}


/**
 * Creates a link that opens within a dashlet window
 * @author Ivan Lucas
 * @param string $dashboard. Dashboard component name.
 * @param string $dashletid. The table row ID of that we are 'drawing' this dashlet into and
 *                           the ID of the dashboard component instance as recorded in the users settings
 *                           as a single string, this is received by the dashlet from dashboard_do()
 * @param string $text. The text of the hyperlink for the user to click
 * @param string $action. edit|save|display
 edit = This is a link to a dashlet config form page
 save = Submit a dashlet config form (see $formid param)
 display = Display a regular dashlet page
 * @param array $params. Associative array of parameters to pass on the URL of the link
 * @param bool $refresh. The link will be automatically refreshed when TRUE
 * @param string $formid. The form element ID to be submitted when using 'save' action
 * @returns string HTML
*/
function dashlet_link($dashboard, $dashletid, $text='', $action='', $params='', $refresh = FALSE, $formid='')
{
    if ($action == 'edit') $action = 'dashboard_edit';
    elseif ($action == 'save') $action = 'dashboard_save';
    else $action = 'dashboard_display';
    if (empty($text)) $text = $GLOBALS['strUntitled'];

    // Ensure the dashlet ID is always correct, 'win' gets prepended with each subpage
    // We only need it once
    $dashletid = 'win'.str_replace('win', '', $dashletid);

    // Convert refresh boolean to javascript text for boolean
    if ($refresh) $refresh = 'true';
    else $refresh = 'false';

    if ($action == 'dashboard_save' AND $formid == '') $formid = "{$dashboard}form";

    if ($action == 'dashboard_save') $html .= "<a href=\"javascript:ajax_save(";
    else  $html .= "<a href=\"javascript:get_and_display(";
    $html .= "'ajaxdata.php?action={$action}&dashboard={$dashboard}&did={$dashletid}";
    if (is_array($params))
    {
        foreach ($params AS $pname => $pvalue)
        {
            $html .= "&{$pname}={$pvalue}";
        }
    }
    //$html .= "&editaction=do_add&type={$type}";

    if ($action != 'dashboard_save')
    {
        $html .= "', '{$dashletid}'";
        $html .= ", $refresh";
    }
    else
    {
        $html .= "', '{$formid}'";
    }
    $html .= ");\">{$text}</a>";

    return $html;
}


/**
 * Wrapper function to call dashboard_*_do() within a dashlet plugin
 * See dashlet() for more information
 * @author Ivan Lucas
 * @param string $context
 * @param string $row
 * @param string $dashboardid
*/
function dashboard_do($context, $row=0, $dashboardid=0)
{
    global $DASHBOARDCOMP;
    $dashletid = "{$row}-{$dashboardid}";
    $action = $DASHBOARDCOMP[$context];
    if ($action != NULL || $action != '')
    {
        if (function_exists($action)) $action($dashletid);
    }
}


function show_dashboard_component($row, $dashboardid)
{
    global $dbDashboard;
    $sql = "SELECT name FROM `{$dbDashboard}` WHERE enabled = 'true' AND id = '$dashboardid'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) == 1)
    {
        $obj = mysql_fetch_object($result);
        dashboard_do("dashboard_".$obj->name, 'db_'.$row, $dashboardid);
    }
}


/**
    * Recursive function to list links as a tree
    * @author Ivan Lucas
*/
function show_links($origtab, $colref, $level=0, $parentlinktype='', $direction='lr')
{
    global $dbLinkTypes, $dbLinks;
    // Maximum recursion
    $maxrecursions = 15;

    if ($level <= $maxrecursions)
    {
        $sql = "SELECT * FROM `{$dbLinkTypes}` WHERE origtab='$origtab' ";
        if (!empty($parentlinktype)) $sql .= "AND id='{$parentlinktype}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        while ($linktype = mysql_fetch_object($result))
        {
            // Look up links of this type
            $lsql = "SELECT * FROM `{$dbLinks}` WHERE linktype='{$linktype->id}' ";
            if ($direction=='lr') $lsql .= "AND origcolref='{$colref}'";
            elseif ($direction=='rl') $lsql .= "AND linkcolref='{$colref}'";
            $lresult = mysql_query($lsql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            if (mysql_num_rows($lresult) >= 1)
            {
                if (mysql_num_rows($lresult) >= 1)
                {
                    $html .= "<ul>";
                    $html .= "<li>";
                    while ($link = mysql_fetch_object($lresult))
                    {
                        $recsql = "SELECT {$linktype->selectionsql} AS recordname FROM {$linktype->linktab} WHERE ";
                        if ($direction=='lr') $recsql .= "{$linktype->linkcol}='{$link->linkcolref}' ";
                        elseif ($direction=='rl') $recsql .= "{$linktype->origcol}='{$link->origcolref}' ";
                        $recresult = mysql_query($recsql);
                        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                        while ($record = mysql_fetch_object($recresult))
                        {
                            if ($link->direction == 'bi')
                            {
                                $html .= "<strong>{$linktype->name}</strong> ";
                            }
                            elseif ($direction == 'lr')
                            {
                                $html .= "<strong>{$linktype->lrname}</strong> ";
                            }
                            elseif ($direction == 'rl')
                            {
                                $html .= "<strong>{$linktype->rlname}</strong> ";
                            }
                            else
                            {
                                $html = $GLOBALS['strError'];
                            }

                            if ($direction == 'lr')
                            {
                                $currentlinkref = $link->linkcolref;
                            }
                            elseif ($direction == 'rl')
                            {
                                $currentlinkref = $link->origcolref;
                            }

                            $viewurl = str_replace('%id%',$currentlinkref,$linktype->viewurl);

                            $html .= "{$currentlinkref}: ";
                            if (!empty($viewurl)) $html .= "<a href='$viewurl'>";
                            $html .= "{$record->recordname}";
                            if (!empty($viewurl)) $html .= "</a>";
                            $html .= " - ".user_realname($link->userid,TRUE);
                            $html .= show_links($linktype->linktab, $currentlinkref, $level+1, $linktype->id, $direction); // Recurse
                            $html .= "</li>\n";
                        }
                    }
                    $html .= "</ul>\n";
                }
                else $html .= "<p>{$GLOBALS['strNone']}</p>";
            }
        }
    }
    else $html .= "<p class='error'>{$GLOBALS['strError']}: Maximum number of {$maxrecursions} recursions reached</p>";
    return $html;
}


/**
  * Interface for creating record 'links' (relationships)
  * @author Ivan Lucas
*/
function show_create_links($table, $ref)
{
    global $dbLinkTypes;
    $html .= "<p align='center'>{$GLOBALS['strAddLink']}: ";
    $sql = "SELECT * FROM `{$dbLinkTypes}` WHERE origtab='$table' OR linktab='$table' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $numlinktypes = mysql_num_rows($result);
    $rowcount = 1;
    while ($linktype = mysql_fetch_object($result))
    {
        if ($linktype->origtab == $table AND $linktype->linktab != $table)
        {
            $html .= "<a href='link_add.php?origtab=tasks&amp;origref={$ref}&amp;linktype={$linktype->id}'>{$linktype->lrname}</a>";
        }
        elseif ($linktype->origtab != $table AND $linktype->linktab == $table)
        {
            $html .= "<a href='link_add.php?origtab=tasks&amp;origref={$ref}&amp;linktype={$linktype->id}'>{$linktype->rlname}</a>";
        }
        else
        {
            $html .= "<a href='link_add.php?origtab=tasks&amp;origref={$ref}&amp;linktype={$linktype->id}'>{$linktype->lrname}</a> | ";
            $html .= "<a href='link_add.php?origtab=tasks&amp;origref={$ref}&amp;linktype={$linktype->id}&amp;dir=rl'>{$linktype->rlname}</a>";
        }

        if ($rowcount < $numlinktypes) $html .= " | ";
        $rowcount++;
    }
    $html .= "</p>";
    return $html;
}


/**
    * Create a PNG chart
    * @author Ivan Lucas
    * @param string $type. The type of chart to draw. (e.g. 'pie').
    * @return resource a PNG image resource
    * @note Currently only has proper support for pie charts (type='pie')
    * @todo TODO Support for bar and line graphs
*/
function draw_chart_image($type, $width, $height, $data, $legends, $title='', $unit='')
{
    global $CONFIG;
    // Graph settings
    if (empty($width)) $width = 500;
    if (empty($height)) $height = 150;
    $fontfile = dirname( __FILE__ ).DIRECTORY_SEPARATOR."FreeSans.ttf"; // FIXME font file!

    if (!empty($fontfile) AND file_exists($fontfile)) $use_ttf = TRUE;
    else $use_ttf = FALSE;

    $countdata = count($data);
    $sumdata = array_sum($data);

    if ($countdata > 8) $height += (($countdata - 8) * 14);

    $img = imagecreatetruecolor($width, $height);

    $white = imagecolorallocate($img, 255, 255, 255);
    $blue = imagecolorallocate($img, 240, 240, 255);
    $midblue = imagecolorallocate($img, 204, 204, 255);
    $darkblue = imagecolorallocate($img, 32, 56, 148);
    $black = imagecolorallocate($img, 0, 0, 0);
    $grey = imagecolorallocate($img, 224, 224, 224);
    $red = imagecolorallocate($img, 255, 0, 0);

    imagefill($img, 0, 0, $white);

    $rgb[] = "190,190,255";
    $rgb[] = "205,255,255";
    $rgb[] = "255,255,156";
    $rgb[] = "156,255,156";
    $rgb[] = "255,205,195";
    $rgb[] = "255,140,255";
    $rgb[] = "100,100,155";
    $rgb[] = "98,153,90";
    $rgb[] = "205,210,230";
    $rgb[] = "192,100,100";
    $rgb[] = "204,204,0";
    $rgb[] = "255,102,102";
    $rgb[] = "0,204,204";
    $rgb[] = "0,255,0";
    $rgb[] = "255,168,88";
    $rgb[] = "128,0,128";
    $rgb[] = "0,153,153";
    $rgb[] = "255,230,204";
    $rgb[] = "128,170,213";
    $rgb[] = "75,75,75";
    // repeats...
    $rgb[] = "190,190,255";
    $rgb[] = "156,255,156";
    $rgb[] = "255,255,156";
    $rgb[] = "205,255,255";
    $rgb[] = "255,205,195";
    $rgb[] = "255,140,255";
    $rgb[] = "100,100,155";
    $rgb[] = "98,153,90";
    $rgb[] = "205,210,230";
    $rgb[] = "192,100,100";
    $rgb[] = "204,204,0";
    $rgb[] = "255,102,102";
    $rgb[] = "0,204,204";
    $rgb[] = "0,255,0";
    $rgb[] = "255,168,88";
    $rgb[] = "128,0,128";
    $rgb[] = "0,153,153";
    $rgb[] = "255,230,204";
    $rgb[] = "128,170,213";
    $rgb[] = "75,75,75";

    switch ($type)
    {
        case 'pie':
            // Set Pie Postition. CenterX,CenterY
            $cx = '120';
            $cy ='60';

            // Set Size-dimensions. SizeX,SizeY,SizeZ
            $sx = '200';
            $sy='100';
            $sz ='15';

            // Title
            if (!empty($title))
            {
                $cy += 10;
                if ($use_ttf)
                {
                    imagettftext($img, 10, 0, 2, 10, $black, $fontfile, $title);
                }
                else
                {
                    imagestring($img, 2, 2, ($legendY-1), "{$title}", $black);
                }
            }

            $angle_sum[-1] = 0;

            //convert to angles.
            for ($i = 0; $i < $countdata; $i++)
            {
                if ($sumdata > 0)
                {
                    $angle[$i] = (($data[$i] / $sumdata) * 360);
                }
                else
                {
                    $angle[$i] = 0;
                }
                $angle_sum[$i] = array_sum($angle);
            }

            $background = imagecolorallocate($img, 255, 255, 255);
            //Random colors.

            for ($i = 0; $i <= $countdata; $i++)
            {
                $rgbcolors = explode(',',$rgb[$i]);
                $colors[$i] = imagecolorallocate($img, $rgbcolors[0], $rgbcolors[1], $rgbcolors[2]);
                $colord[$i] = imagecolorallocate($img, ($rgbcolors[0]/1.5), ($rgbcolors[1]/1.5), ($rgbcolors[2]/1.5));
            }

            //3D effect.
            $legendY = 80 - ($countdata * 10);

            if ($legendY < 10) $legendY = 10;

            for ($z = 1; $z <= $sz; $z++)
            {
                for ($i = 0; $i < $countdata; $i++)
                {
                        imagefilledarc($img, $cx, ($cy + $sz) - $z, $sx, $sy, $angle_sum[$i-1], $angle_sum[$i], $colord[$i], IMG_ARC_PIE);
                }

            }

            imagerectangle($img, 250, $legendY - 5, 470, $legendY + ($countdata * 15), $black);

            //Top of the pie.
            for ($i = 0; $i < $countdata; $i++)
            {
                // If its the same angle don't try and draw anything otherwise you end up with the whole pie being this colour
                if ($angle_sum[$i - 1] != $angle_sum[$i])
                {
                    imagefilledarc($img, $cx, $cy, $sx, $sy, $angle_sum[$i-1], $angle_sum[$i], $colors[$i], IMG_ARC_PIE);
                }

                imagefilledrectangle($img, 255, ($legendY + 1), 264, ($legendY + 9), $colors[$i]);
                // Legend
                if ($unit == 'seconds')
                {
                    $data[$i] = format_seconds($data[$i]);
                }

                if ($use_ttf)
                {
                    imagettftext($img, 8, 0, 270, ($legendY + 9), $black, $fontfile, substr(urldecode($legends[$i]),0,27)." ({$data[$i]})");
                }
                else
                {
                    imagestring($img,2, 270, ($legendY - 1), substr(urldecode($legends[$i]), 0,27)." ({$data[$i]})", $black);
                }
                // imagearc($img,$cx,$cy,$sx,$sy,$angle_sum[$i1] ,$angle_sum[$i], $blue);
                $legendY += 15;
            }
        break;

        case 'line':
            $maxdata = 0;
            $colwidth=round($width/$countdata);
            $rowheight=round($height/10);
            foreach ($data AS $dataval)
            {
                if ($dataval > $maxdata) $maxdata = $dataval;
            }

            imagerectangle($img, $width-1, $height-1, 0, 0, $black);
            for ($i=1; $i<$countdata; $i++)
            {
                imageline($img, $i*$colwidth, 0, $i*$colwidth, $width, $grey);
                imageline($img, 2, $i*$rowheight, $width-2, $i*$rowheight, $grey);
            }

            for ($i=0; $i<$countdata; $i++)
            {
                $dataheight=($height-($data[$i] / $maxdata) * $height);
                $legendheight = $dataheight > ($height - 15) ? $height - 15 : $dataheight;
                $nextdataheight=($height-($data[$i+1] / $maxdata) * $height);
                imageline($img, $i*$colwidth, $dataheight, ($i+1)*$colwidth, $nextdataheight, $red);
                imagestring($img, 3, $i*$colwidth, $legendheight, substr($legends[$i],0,6), $darkblue);
            }
            imagestring($img,3, 10, 10, $title, $red);
        break;

        case 'bar':
            $maxdata = 0;
            $colwidth=round($width/$countdata);
            $rowheight=round($height/10);
            foreach ($data AS $dataval)
            {
                if ($dataval > $maxdata) $maxdata = $dataval;
            }

            imagerectangle($img, $width-1, $height-1, 0, 0, $black);
            for ($i=1; $i<$countdata; $i++)
            {
                imageline($img, $i*$colwidth, 0, $i*$colwidth, $width, $grey);
                imageline($img, 2, $i*$rowheight, $width-2, $i*$rowheight, $grey);
            }

            for ($i=0; $i<$countdata; $i++)
            {
                $dataheight=($height-($data[$i] / $maxdata) * $height);
                $legendheight = $dataheight > ($height - 15) ? $height - 15 : $dataheight;
                imagefilledrectangle($img, $i*$colwidth, $dataheight, ($i+1)*$colwidth, $height, $darkblue);
                imagefilledrectangle($img, ($i*$colwidth)+1, $dataheight+1, (($i+1)*$colwidth)-3, ($height-2), $midblue);
                imagestring($img, 3, ($i*$colwidth)+4, $legendheight, substr($legends[$i],0,5), $darkblue);
            }
            imagestring($img,3, 10, 10, $title, $red);
        break;


        default:
            imagerectangle($img, $width-1, $height-1, 1, 1, $red);
            imagestring($img,3, 10, 10, "Invalid chart type", $red);
    }

    // Return a PNG image
    return $img;
}


/**
    * @author Paul Heaney
*/
function display_drafts($type, $result)
{
    global $iconset;
    global $id;
    global $CONFIG;

    if ($type == 'update')
    {
        $page = "incident_update.php";
        $editurlspecific = '';
    }
    else if ($type == 'email')
    {
        $page = "incident_email.php";
        $editurlspecific = "&amp;step=2";
    }

    echo "<p align='center'>{$GLOBALS['strDraftChoose']}</p>";

    $html = '';

    while ($obj = mysql_fetch_object($result))
    {
        $html .= "<div class='detailhead'>";
        $html .= "<div class='detaildate'>".date($CONFIG['dateformat_datetime'], $obj->lastupdate);
        $html .= "</div>";
        $html .= "<a href='{$page}?action=editdraft&amp;draftid={$obj->id}&amp;id={$id}{$editurlspecific}' class='info'>";
        $html .= icon('edit', 16, $GLOBALS['strDraftEdit'])."</a>";
        $html .= "<a href='{$page}?action=deletedraft&amp;draftid={$obj->id}&amp;id={$id}' class='info'>";
        $html .= icon('delete', 16, $GLOBALS['strDraftDelete'])."</a>";
        $html .= "</div>";
        $html .= "<div class='detailentry'>";
        $html .= nl2br($obj->content)."</div>";
    }

    return $html;
}


function ansort($x,$var,$cmp='strcasecmp')
{
    // Numeric descending sort of multi array
    if ( is_string($var) ) $var = "'$var'";

    if ($cmp=='numeric')
    {
        uasort($x, create_function('$a,$b', 'return '.'( $a['.$var.'] < $b['.$var.']);'));
    }
    else
    {
        uasort($x, create_function('$a,$b', 'return '.$cmp.'( $a['.$var.'],$b['.$var.']);'));
    }
    return $x;
}


function array_remove_duplicate($array, $field)
{
    foreach ($array as $sub)
    {
        $cmp[] = $sub[$field];
    }

    $unique = array_unique($cmp);
    foreach ($unique as $k => $rien)
    {
        $new[] = $array[$k];
    }
    return $new;
}


function array_multi_search($needle, $haystack, $searchkey)
{
    foreach ($haystack AS $thekey => $thevalue)
    {
        if ($thevalue[$searchkey] == $needle) return $thekey;
    }
    return FALSE;
}


// Implode assocative array
function implode_assoc($glue1, $glue2, $array)
{
    foreach ($array as $key => $val)
    {
        $array2[] = $key.$glue1.$val;
    }

    return implode($glue2, $array2);
}


/**
    * @author Kieran Hogg
    * @param string $name. name of the html entity
    * @param string $time. the time to set it to, format 12:34
    * @returns string. HTML
*/
function time_dropdown($name, $time='')
{
    if ($time)
    {
        $time = explode(':', $time);
    }

    $html = "<select name='$name'>\n";
    $html .= "<option></option>";
    for ($hours = 0; $hours < 24; $hours++)
    {
        for ($mins = 0; $mins < 60; $mins+=15)
        {
            $hours = str_pad($hours, 2, "0", STR_PAD_LEFT);
            $mins = str_pad($mins, 2, "0", STR_PAD_RIGHT);

            if ($time AND $time[0] == $hours AND $time[1] == $mins)
            {
                $html .= "<option selected='selected' value='$hours:$mins'>$hours:$mins</option>";
            }
            else
            {
                if ($time AND $time[0] == $hours AND $time[1] < $mins AND $time[1] > ($mins - 15))
                {
                    $html .= "<option selected='selected' value='$time[0]:$time[1]'>$time[0]:$time[1]</option>\n";
                }
                else
                {
                    $html .= "<option value='$hours:$mins'>$hours:$mins</option>\n";
                }
            }
        }
    }
    $html .= "</select>";
    return $html;
}


/**
    * @author Kieran Hogg
    * @param int $seconds. Number of seconds
    * @returns string. Readable time in seconds
*/
function exact_seconds($seconds)
{
    $days = floor($seconds / (24 * 60 * 60));
    $seconds -= $days * (24 * 60 * 60);
    $hours = floor($seconds / (60 * 60));
    $seconds -=  $hours * (60 * 60);
    $minutes = floor($seconds / 60);
    $seconds -= $minutes * 60;

    $string = "";
    if ($days != 0) $string .= "{$days} {$GLOBALS['strDays']}, ";
    if ($hours != 0) $string .= "{$hours} {$GLOBALS['strHours']}, ";
    if ($minutes != 0) $string .= "{$minutes} {$GLOBALS['strMinutes']}, ";
    $string .= "{$seconds} {$GLOBALS['strSeconds']}";

    return $string;
}


/**
    * Shows errors from a form, if any
    * @author Kieran Hogg
    * @returns string. HTML of the form errors stored in the users session
*/
function show_form_errors($formname)
{
    if ($_SESSION['formerrors'][$formname])
    {
        foreach ($_SESSION['formerrors'][$formname] as $error)
        {
            $html .= "<p class='error'>$error</p>";
        }
    }
    return $html;
}


/**
    * Cleans form errors
    * @author Kieran Hogg
    * @returns nothing
*/
function clear_form_errors($formname)
{
    unset($_SESSION['formerrors'][$formname]);
}


/**
    * Cleans form data
    * @author Kieran Hogg
    * @returns nothing
*/
function clear_form_data($formname)
{
    unset($_SESSION['formdata'][$formname]);
}


/**
 * Adjust a timezoned date/time to UTC
 * @author Ivan Lucas
 * @param int UNIX timestamp.  Uses 'now' if ommitted
 * @returns int UNIX timestamp (in UTC)
*/
function utc_time($time = '')
{
    if ($time == '') $time = $GLOBALS['now'];
    $tz = strftime('%z', $time);
    $tzmins = (substr($tz, -4, 2) * 60) + substr($tz, -2, 2);
    $tzsecs = $tzmins * 60; // convert to seconds
    if (substr($tz, 0, 1) == '+') $time -= $tzsecs;
    else $time += $tzsecs;
    return $time;
}


/**
    * Returns a localised and translated date
    * @author Ivan Lucas
    * @param string $format. date() format
    * @param int $date.  UNIX timestamp.  Uses 'now' if ommitted
    * @param bool $utc bool. Is the timestamp being passed as UTC or system time
                        TRUE = passed as UTC
                        FALSE = passed as system time
    * @returns string. An internationised date/time string
    * @todo  th/st and am/pm maybe?
*/
function ldate($format, $date = '', $utc = FALSE)
{
    if ($date == '') $date = $GLOBALS['now'];
    if ($_SESSION['utcoffset'] != '')
    {
        if ($utc === FALSE)
        {
            // Adjust the date back to UTC
            $date = utc_time($date);
        }
        // Adjust the display time to the users local timezone
        $useroffsetsec = $_SESSION['utcoffset'] * 60;
        $date += $useroffsetsec;
    }
    $datestring = date($format, $date);

    // Internationalise date endings (e.g. st)
    if (strpos($format, 'S') !== FALSE)
    {
        $endings = array('st', 'nd', 'rd', 'th');
        $i18nendings = array($GLOBALS['strst'], $GLOBALS['strnd'],
                            $GLOBALS['strrd'], $GLOBALS['strth']);
        $datestring = str_replace($endings, $i18nendings, $datestring);
    }


    // Internationalise full day names
    if (strpos($format, 'l') !== FALSE)
    {
        $days = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
        $i18ndays = array($GLOBALS['strMonday'], $GLOBALS['strTuesday'], $GLOBALS['strWednesday'],
                        $GLOBALS['strThursday'], $GLOBALS['strFriday'], $GLOBALS['strSaturday'], $GLOBALS['strSunday']);
        $datestring = str_replace($days, $i18ndays, $datestring);
    }

    // Internationalise abbreviated day names
    if (strpos($format, 'D') !== FALSE)
    {
        $days = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
        $i18ndays = array($GLOBALS['strMon'], $GLOBALS['strTue'], $GLOBALS['strWed'],
                        $GLOBALS['strThu'], $GLOBALS['strFri'], $GLOBALS['strSat'], $GLOBALS['strSun']);
        $datestring = str_replace($days, $i18ndays, $datestring);
    }

    // Internationalise full month names
    if (strpos($format, 'F') !== FALSE)
    {
        $months = array('January','February','March','April','May','June','July','August','September','October','November','December');
        $i18nmonths = array($GLOBALS['strJanuary'], $GLOBALS['strFebruary'], $GLOBALS['strMarch'],
                        $GLOBALS['strApril'], $GLOBALS['strMay'], $GLOBALS['strJune'], $GLOBALS['strJuly'],
                        $GLOBALS['strAugust'], $GLOBALS['strSeptember'], $GLOBALS['strOctober'],
                        $GLOBALS['strNovember'], $GLOBALS['strDecember']);
        $datestring = str_replace($months, $i18nmonths, $datestring);
    }

    // Internationalise short month names
    if (strpos($format, 'M') !== FALSE)
    {
        $months = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
        $i18nmonths = array($GLOBALS['strJanAbbr'], $GLOBALS['strFebAbbr'], $GLOBALS['strMarAbbr'],
                        $GLOBALS['strAprAbbr'], $GLOBALS['strMayAbbr'], $GLOBALS['strJunAbbr'], $GLOBALS['strJulAbbr'],
                        $GLOBALS['strAugAbbr'], $GLOBALS['strSepAbbr'], $GLOBALS['strOctAbbr'],
                        $GLOBALS['strNovAbbr'], $GLOBALS['strDecAbbr']);
        $datestring = str_replace($months, $i18nmonths, $datestring);
    }

    // Internationalise am/pm
    if (strpos($format, 'a') !== FALSE)
    {
        $months = array('am','pm');
        $i18nmonths = array($GLOBALS['strAM'], $GLOBALS['strPM']);
        $datestring = str_replace($months, $i18nmonths, $datestring);
    }

    return $datestring;
}


/**
    * Returns an array of open activities/timed tasks for an incident
    * @author Paul Heaney
    * @param int $incidentid. Incident ID you want
    * @returns array - with the task id
*/
function open_activities_for_incident($incientid)
{
    global $dbLinks, $dbLinkTypes, $dbTasks;
    // Running Activities

    $sql = "SELECT DISTINCT origcolref, linkcolref ";
    $sql .= "FROM `{$dbLinks}` AS l, `{$dbLinkTypes}` AS lt ";
    $sql .= "WHERE l.linktype=4 ";
    $sql .= "AND linkcolref={$incientid} ";
    $sql .= "AND direction='left'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        //get list of tasks
        $sql = "SELECT * FROM `{$dbTasks}` WHERE enddate IS NULL ";
        while ($tasks = mysql_fetch_object($result))
        {
            if (empty($orSQL)) $orSQL = "(";
            else $orSQL .= " OR ";
            $orSQL .= "id={$tasks->origcolref} ";
        }

        if (!empty($orSQL))
        {
            $sql .= "AND {$orSQL})";
        }
        $result = mysql_query($sql);

        // $num = mysql_num_rows($result);
        while ($obj = mysql_fetch_object($result))
        {
        	$num[] = $obj->id;
        }
    }
    else
    {
        $num = null;
    }

    return $num;
}


/**
    * Returns the number of open activities/timed tasks for a site
    * @author Paul Heaney
    * @param int $siteid. Site ID you want
    * @returns int. Number of open activities for the site (0 if non)
*/
function open_activities_for_site($siteid)
{
    global $dbIncidents, $dbContacts;

    $openactivites = 0;

    if (!empty($siteid) AND $siteid != 0)
    {
        $sql = "SELECT i.id FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c ";
        $sql .= "WHERE i.contact = c.id AND ";
        $sql .= "c.siteid = {$siteid} AND ";
        $sql .= "(i.status != 2 AND i.status != 7)";

        $result = mysql_query($sql);

        while ($obj = mysql_fetch_object($result))
        {
            $openactivites += count(open_activities_for_incident($obj->id));
        }
    }

    return $openactivites;
}


/**
 * Finds out which scheduled tasks should be run right now
 * @author Ivan Lucas, Paul Heaney
 * @returns array
 */
function schedule_actions_due()
{
    global $now;
    global $dbScheduler;

    $actions = FALSE;
    $sql = "SELECT * FROM `{$dbScheduler}` WHERE `status` = 'enabled' AND type = 'interval' ";
    $sql .= "AND UNIX_TIMESTAMP(start) <= $now AND (UNIX_TIMESTAMP(end) >= $now OR UNIX_TIMESTAMP(end) = 0) ";
    $sql .= "AND IF(UNIX_TIMESTAMP(lastran) > 0, UNIX_TIMESTAMP(lastran) + `interval` <= $now, UNIX_TIMESTAMP(NOW())) ";
    $sql .= "AND IF(laststarted > 0, laststarted <= lastran, 1=1)";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        while ($action = mysql_fetch_object($result))
        {
            $actions[$action->action] = $actions->params;
        }
    }

    // Month
    $sql = "SELECT * FROM `{$dbScheduler}` WHERE `status` = 'enabled' AND type = 'date' ";
    $sql .= "AND UNIX_TIMESTAMP(start) <= $now AND (UNIX_TIMESTAMP(end) >= $now OR UNIX_TIMESTAMP(end) = 0) ";
    $sql .= "AND ((date_type = 'month' AND (DAYOFMONTH(CURDATE()) > date_offset OR (DAYOFMONTH(CURDATE()) = date_offset AND CURTIME() >= date_time)) ";
    $sql .= "AND DATE_FORMAT(CURDATE(), '%Y-%m') != DATE_FORMAT(lastran, '%Y-%m') ) ) ";  // not run this month
    $sql .= "AND IF(laststarted > 0, laststarted <= lastran, 1=1)";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        while ($action = mysql_fetch_object($result))
        {
            $actions[$action->action] = $actions->params;
        }
    }

    // Year TODO CHECK
    $sql = "SELECT * FROM `{$dbScheduler}` WHERE `status` = 'enabled' ";
    $sql .= "AND type = 'date' AND UNIX_TIMESTAMP(start) <= $now ";
    $sql .= "AND (UNIX_TIMESTAMP(end) >= $now OR UNIX_TIMESTAMP(end) = 0) ";
    $sql .= "AND ((date_type = 'year' AND (DAYOFYEAR(CURDATE()) > date_offset ";
    $sql .= "OR (DAYOFYEAR(CURDATE()) = date_offset AND CURTIME() >= date_time)) ";
    $sql .= "AND DATE_FORMAT(CURDATE(), '%Y') != DATE_FORMAT(lastran, '%Y') ) ) ";  // not run this year
    $sql .= "AND IF(laststarted > 0, laststarted <= lastran, 1=1)";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        while ($action = mysql_fetch_object($result))
        {
            $actions[$action->action] = $actions->params;
        }
    }

    debug_log('Scheduler actions due: '.implode(', ',array_keys($actions)));

    return $actions;
}


/**
* Marks a schedule action as started
* @author Paul Heaney
* @param string $action. Name of scheduled action
* @return boolean Success of update
*/
function schedule_action_started($action)
{
    global $now;

    $nowdate = date('Y-m-d H:i:s', $now);

    $sql = "UPDATE `{$GLOBALS['dbScheduler']}` SET laststarted = '$nowdate' ";
    $sql .= "WHERE action = '{$action}'";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_ERROR);
        return FALSE;
    }
    if (mysql_affected_rows() > 0) return TRUE;
    else return FALSE;
}


/**
    * Mark a schedule action as done
    * @author Ivan Lucas
    * @param string $doneaction. Name of scheduled action
    * @param bool $success. Was the run successful, TRUE = Yes, FALSE = No
 */
function schedule_action_done($doneaction, $success = TRUE)
{
    global $now;
    global $dbScheduler;

    if ($success != TRUE)
    {
        trigger('TRIGGER_SCHEDULER_TASK_FAILED', array('schedulertask' => $doneaction));
    }

    $nowdate = date('Y-m-d H:i:s', $now);
    $sql = "UPDATE `{$dbScheduler}` SET lastran = '$nowdate' ";
    if ($success == FALSE) $sql .= ", success=0, status='disabled' ";
    else $sql .= ", success=1 ";
    $sql .= "WHERE action = '{$doneaction}'";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_ERROR);
        return FALSE;
    }
    if (mysql_affected_rows() > 0) return TRUE;
    else return FALSE;
}


/**
* Return an array of contacts allowed to use this contract
* @author Kieran Hogg
* @param int $maintid - ID of the contract
* @returns array of supported contacts, NULL if none
**/
function supported_contacts($maintid)
{
    global $dbSupportContacts, $dbContacts;
    $sql  = "SELECT c.forenames, c.surname, sc.contactid AS contactid ";
    $sql .= "FROM `{$dbSupportContacts}` AS sc, `{$dbContacts}` AS c ";
    $sql .= "WHERE sc.contactid=c.id AND sc.maintenanceid='{$maintid}' ";
    $sql .= "ORDER BY c.surname, c.forenames ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (!empty($result))
    {
        while ($row = mysql_fetch_object($result))
        {
            $returnarray[] = $row->contactid;
        }
        return $returnarray;
    }
    else return NULL;
}


/**
* Return an array of contracts which the contact is an admin contact for
* @author Kieran Hogg
* @param int $maintid - ID of the contract
* @param int $siteid - The ID of the site
* @returns array of contract ID's for which the given contactid is an admin contact, NULL if none
**/
function admin_contact_contracts($contactid, $siteid)
{
    $sql = "SELECT DISTINCT m.id ";
    $sql .= "FROM `{$GLOBALS['dbMaintenance']}` AS m ";
    $sql .= "WHERE m.admincontact={$contactid} ";
    $sql .= "AND m.site={$siteid} ";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if ($result)
    {
        while ($row = mysql_fetch_object($result))
        {
            $contractsarray[] = $row->id;
        }
    }

    return $contractsarray;
}


/**
* Return an array of contracts which the contact is an named contact for
* @author Kieran Hogg
* @param int $maintid - ID of the contract
* @returns array of supported contracts, NULL if none
**/
function contact_contracts($contactid, $siteid, $checkvisible = TRUE)
{
    $sql = "SELECT DISTINCT m.id AS id
            FROM `{$GLOBALS['dbMaintenance']}` AS m,
            `{$GLOBALS['dbContacts']}` AS c,
            `{$GLOBALS['dbSupportContacts']}` AS sc
            WHERE m.site={$siteid}
            AND sc.maintenanceid=m.id
            AND sc.contactid=c.id ";
    if ($checkvisible)
    {
        $sql .= "AND m.var_incident_visible_contacts = 'yes'";
    }

    if ($result = mysql_query($sql))
    {
        while ($row = mysql_fetch_object($result))
        {
            $contractsarray[] = $row->id;
        }
    }
    return $contractsarray;
}


/**
* Return an array of contracts which non-contract contacts can see incidents
* @author Kieran Hogg
* @param int $maintid - ID of the contract
* @returns array of supported contracts, NULL if none
**/
function all_contact_contracts($contactid, $siteid)
{
    $sql = "SELECT DISTINCT m.id AS id
            FROM `{$GLOBALS['dbMaintenance']}` AS m
            WHERE m.site={$siteid}
            AND m.var_incident_visible_all = 'yes'";

    if ($result = mysql_query($sql))
    {
        while ($row = mysql_fetch_object($result))
        {
            $contractsarray[] = $row->id;
        }
    }
    return $contractsarray;
}


/**
* Checks is a given username is unique
* @author Kieran Hogg
* @param string $username - username
* @returns bool TRUE if valid, FALSE if not
**/
function valid_username($username)
{
    $username = cleanvar($username);
    $valid = TRUE;

    $tables = array('dbUsers', 'dbContacts');

    foreach ($tables AS $table)
    {
        $sql = "SELECT username FROM `{$GLOBALS[$table]}` WHERE username='{$username}'";
        if ($result = mysql_query($sql) AND mysql_num_rows($result) != 0)
        {
            $valid = FALSE;
        }
    }

    return $valid;
}


/**
* Update the current session id with a newly generated one
* @author Ivan Lucas
* @note Wrap the php function for different versions of php
 */
function session_regenerate()
{
    if (function_exists('session_regenerate_id'))
    {
        if (!version_compare(phpversion(),"5.1.0",">=")) session_regenerate_id(FALSE);
        else session_regenerate_id();
    }
}


/**
* Finds the software associated with a contract
* @author Ivan Lucas
* @note Wrap the php function for different versions of php
 */
function contract_software()
{
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

    if ($result = mysql_query($sql))
    {
        while ($row = mysql_fetch_object($result))
        {
            $softwarearray[] = $row->id;
        }
    }

    return $softwarearray;
}


/**
* HTML for an ajax help link
* @author Ivan Lucas
* @param string $context. The base filename of the popup help file in
                          help/en-GB/ (without the .txt extension)
* @returns string HTML
**/
function help_link($context)
{
    global $strHelpChar;
    $html = "<span class='helplink'>[<a href='#' tabindex='-1' onmouseover=\"";
    $html .= "contexthelp(this, '$context'";
    if ($_SESSION['portalauth'] == TRUE) $html .= ",'portal'";
    else $html .= ",'standard'";
    $html .= ");return false;\">{$strHelpChar}<span>";
    $html .= "</span></a>]</span>";

    return $html;
}


/**
* Function to return an user error message when a file fails to upload
* @author Paul Heaney
* @param errorcode The error code from $_FILES['file']['error']
* @param name The file name which was uploaded from $_FILES['file']['name']
* @return String containing the error message (in HTML)
*/
function get_file_upload_error_message($errorcode, $name)
{
    $str = "<div class='detailinfo'>\n";

    $str .=  "An error occurred while uploading <strong>{$_FILES['attachment']['name']}</strong>";

    $str .=  "<p class='error'>";
    switch ($errorcode)
    {
        case UPLOAD_ERR_INI_SIZE:  $str .= "The file exceded the maximum size set in PHP"; break;
        case UPLOAD_ERR_FORM_SIZE:  $str .=  "The uploaded file was too large"; break;
        case UPLOAD_ERR_PARTIAL: $str .=  "The file was only partially uploaded"; break;
        case UPLOAD_ERR_NO_FILE: $str .=  "No file was uploaded"; break;
        case UPLOAD_ERR_NO_TMP_DIR: $str .=  "Temporary folder is missing"; break;
        default: $str .=  "An unknown file upload error occurred"; break;
    }
    $str .=  "</p>";
    $str .=  "</div>";

    return $str;
}


/**
* Function to produce a user readable file size i.e 2048 bytes 1KB etc
* @author Paul Heaney
* @param filesize - filesize in bytes
* @return String filesize in readable format
*
*/
function readable_file_size($filesize)
{
    global $strBytes, $strKBytes, $strMBytes, $strGBytes, $strTBytes;
    $j = 0;

    $ext = array($strBytes, $strKBytes, $strMBytes, $strGBytes, $strTBytes);
    while ($filesize >= pow(1024,$j))
    {
        ++$j;
    }
    $filemax = round($filesize / pow(1024,$j-1) * 100) / 100 . ' ' . $ext[$j-1];

    return $filemax;
}


/**
* Return the html of contract detatils
* @author Kieran Hogg
* @param int $maintid - ID of the contract
* @param string $mode. 'internal' or 'external'
 * @return array of supported contracts, NULL if none
* @todo FIXME not quite generic enough for a function ?
 */
function contract_details($id, $mode='internal')
{
    global $CONFIG, $iconset, $dbMaintenance, $dbSites, $dbResellers, $dbLicenceTypes, $now;

    $sql  = "SELECT m.*, m.notes AS maintnotes, s.name AS sitename, ";
    $sql .= "r.name AS resellername, lt.name AS licensetypename ";
    $sql .= "FROM `{$dbMaintenance}` AS m, `{$dbSites}` AS s, ";
    $sql .= "`{$dbResellers}` AS r, `{$dbLicenceTypes}` AS lt ";
    $sql .= "WHERE s.id = m.site ";
    $sql .= "AND m.id='{$id}' ";
    $sql .= "AND m.reseller = r.id ";
    $sql .= "AND (m.licence_type IS NULL OR m.licence_type = lt.id) ";
    if ($mode == 'external') $sql .= "AND m.site = '{$_SESSION['siteid']}'";

    $maintresult = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    $maint = mysql_fetch_object($maintresult);

    $html = "<table align='center' class='vertical'>";
    $html .= "<tr><th>{$GLOBALS['strContract']} {$GLOBALS['strID']}:</th>";
    $html .= "<td><h3>".icon('contract', 32)." ";
    $html .= "{$maint->id}</h3></td></tr>";
    $html .= "<tr><th>{$GLOBALS['strStatus']}:</th><td>";
    if ($maint->term == 'yes')
    {
        $html .= "<strong>{$GLOBALS['strTerminated']}</strong>";
    }
    else
    {
        $html .= $GLOBALS['strActive'];
    }

    if ($maint->expirydate < $now AND $maint->expirydate != '-1')
    {
        $html .= "<span class='expired'>, {$GLOBALS['strExpired']}</span>";
    }
    $html .= "</td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strSite']}:</th>";

    if ($mode == 'internal')
    {
        $html .= "<td><a href=\"site_details.php?id=".$maint->site."\">".$maint->sitename."</a></td></tr>";
    }
    else
    {
        $html .= "<td><a href=\"sitedetails.php\">".$maint->sitename."</a></td></tr>";
    }
    $html .= "<tr><th>{$GLOBALS['strAdminContact']}:</th>";

    if ($mode == 'internal')
    {
        $html .= "<td><a href=\"contact_details.php?id=";
        $html .= "{$maint->admincontact}\">";
        $html .= contact_realname($maint->admincontact)."</a></td></tr>";
    }
    else
    {
        $html .= "<td><a href='contactdetails.php?id={$maint->admincontact}'>";
        $html .= contact_realname($maint->admincontact)."</a></td></tr>";
    }

    $html .= "<tr><th>{$GLOBALS['strReseller']}:</th><td>";

    if (empty($maint->resellername))
    {
        $html .= $GLOBALS['strNoReseller'];
    }
    else
    {
        $html .= $maint->resellername;
    }
    $html .= "</td></tr>";
    $html .= "<tr><th>{$GLOBALS['strProduct']}:</th><td>".product_name($maint->product)."</td></tr>";
    $html .= "<tr><th>{$GLOBALS['strIncidents']}:</th>";
    $html .= "<td>";
    $incidents_remaining = $maint->incident_quantity - $maint->incidents_used;

    if ($maint->incident_quantity == 0)
    {
        $quantity = $GLOBALS['strUnlimited'];
    }
    else
    {
        $quantity = $maint->incident_quantity;
    }

    $html .= sprintf($GLOBALS['strUsedNofN'], $maint->incidents_used, $quantity);
    if ($maint->incidents_used >= $maint->incident_quantity AND
        $maint->incident_quantity != 0)
    {
        $html .= " ({$GLOBALS['strZeroRemaining']})";
    }

    $html .= "</td></tr>";
    if ($maint->licence_quantity != '0')
    {
        $html .= "<tr><th>{$GLOBALS['strLicense']}:</th>";
        $html .= "<td>{$maint->licence_quantity} {$maint->licensetypename}</td></tr>\n";
    }

    $html .= "<tr><th>{$GLOBALS['strServiceLevel']}:</th><td>".servicelevel_name($maint->servicelevelid)."</td></tr>";
    $html .= "<tr><th>{$GLOBALS['strExpiryDate']}:</th><td>";
    if ($maint->expirydate == '-1')
    {
        $html .= "{$GLOBALS['strUnlimited']}";
    }
    else
    {
        $html .= ldate($CONFIG['dateformat_date'], $maint->expirydate);
    }

    $html .= "</td></tr>";

    if ($mode == 'internal')
    {
        $timed = db_read_column('timed', $GLOBALS['dbServiceLevels'], $maint->servicelevelid);
        if ($timed == 'yes') $timed = TRUE;
        else $timed = FALSE;
        $html .= "<tr><th>{$GLOBALS['strService']}</th><td>";
        $html .= contract_service_table($id, $timed);
        $html .= "</td></tr>\n";

        if ($timed)
        {
            $html .= "<tr><th>{$GLOBALS['strBalance']}</th><td>{$CONFIG['currency_symbol']}".number_format(get_contract_balance($id, TRUE, TRUE), 2);
            $multiplier = get_billable_multiplier(strtolower(date('D', $now)), date('G', $now));
            $html .= " (&cong;".contract_unit_balance($id, TRUE, TRUE)." units)";
            $html .= "</td></tr>";
        }
    }

    if ($maint->maintnotes != '' AND $mode == 'internal')
    {
        $html .= "<tr><th>{$GLOBALS['strNotes']}:</th><td>{$maint->maintnotes}</td></tr>";
    }
    $html .= "</table>";

    if ($mode == 'internal')
    {
        $html .= "<p align='center'>";
        $html .= "<a href=\"contract_edit.php?action=edit&amp;maintid=$id\">{$GLOBALS['strEditContract']}</a> | ";
        $html .= "<a href='contract_add_service.php?contractid={$id}'>{$GLOBALS['strAddService']}</a></p>";
    }
    $html .= "<h3>{$GLOBALS['strContacts']}</h3>";

    if (mysql_num_rows($maintresult) > 0)
    {
        if ($maint->allcontactssupported == 'yes')
        {
            $html .= "<p class='info'>{$GLOBALS['strAllSiteContactsSupported']}</p>";
        }
        else
        {
            $allowedcontacts = $maint->supportedcontacts;

            $supportedcontacts = supported_contacts($id);
            $numberofcontacts = 0;

                $numberofcontacts = sizeof($supportedcontacts);
                if ($allowedcontacts == 0)
                {
                    $allowedcontacts = $GLOBALS['strUnlimited'];
                }
                $html .= "<table align='center'>";
                $supportcount = 1;

                if ($numberofcontacts > 0)
                {
                    foreach ($supportedcontacts AS $contact)
                    {
                        $html .= "<tr><th>{$GLOBALS['strContact']} #{$supportcount}:</th>";
                        $html .= "<td>".icon('contact', 16)." ";
                        if ($mode == 'internal')
                        {
                            $html .= "<a href=\"contact_details.php?";
                        }
                        else
                        {
                            $html .= "<a href=\"contactdetails.php?";
                        }
                        $html .= "id={$contact}\">".contact_realname($contact)."</a>, ";
                        $html .= contact_site($contact). "</td>";

                        if ($mode == 'internal')
                        {
                            $html .= "<td><a href=\"contract_delete_contact.php?contactid=".$contact."&amp;maintid=$id&amp;context=maintenance\">{$GLOBALS['strRemove']}</a></td></tr>\n";
                        }
                        else
                        {
                            $html .= "<td><a href=\"{$_SERVER['PHP_SELF']}?id={$id}&amp;contactid=".$contact."&amp;action=remove\">{$GLOBALS['strRemove']}</a></td></tr>\n";
                        }
                        $supportcount++;
                    }
                    $html .= "</table>";
                }
                else
                {
                    $html .= "<p class='info'>{$GLOBALS['strNoRecords']}<p>";
                }
        }
        if ($maint->allcontactssupported != 'yes')
        {
            $html .= "<p align='center'>";
            $html .= sprintf($GLOBALS['strUsedNofN'],
                            "<strong>".$numberofcontacts."</strong>",
                            "<strong>".$allowedcontacts."</strong>");
            $html .= "</p>";

            if ($numberofcontacts < $allowedcontacts OR $allowedcontacts == 0 AND $mode == 'internal')
            {
                $html .= "<p align='center'><a href='contract_add_contact.php?maintid={$id}&amp;siteid={$maint->site}&amp;context=maintenance'>";
                $html .= "{$GLOBALS['strAddContact']}</a></p>";
            }
            else
            {
                $html .= "<h3>{$GLOBALS['strAddContact']}</h3>";
                $html .= "<form action='{$_SERVER['PHP_SELF']}?id={$id}&amp;action=";
                $html .= "add' method='post' >";
                $html .= "<p align='center'>{$GLOBLAS['strAddNewSupportedContact']} ";
                $html .= contact_site_drop_down('contactid',
                                                'contactid',
                                                maintenance_siteid($id),
                                                supported_contacts($id));
                $html .= help_link('NewSupportedContact');
                $html .= " <input type='submit' value='{$GLOBALS['strAdd']}' /></p></form>";
            }
            if ($mode == 'external')
            {
                $html .= "<p align='center'><a href='addcontact.php'>";
                $html .= "{$GLOBALS['strAddNewSiteContact']}</a></p>";
            }
        }

        $html .= "<br />";
        $html .= "<h3>{$GLOBALS['strSkillsSupportedUnderContract']}:</h3>";
        // supported software
        $sql = "SELECT * FROM `{$GLOBALS[dbSoftwareProducts]}` AS sp, `{$GLOBALS[dbSoftware]}` AS s ";
        $sql .= "WHERE sp.softwareid = s.id AND productid='{$maint->product}' ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($result)>0)
        {
            $html .="<table align='center'>";
            while ($software = mysql_fetch_object($result))
            {
                $software->lifetime_end = mysql2date($software->lifetime_end);
                $html .= "<tr><td> ".icon('skill', 16)." ";
                if ($software->lifetime_end > 0 AND $software->lifetime_end < $now)
                {
                    $html .= "<span class='deleted'>";
                }
                $html .= $software->name;
                if ($software->lifetime_end > 0 AND $software->lifetime_end < $now)
                {
                    $html .= "</span>";
                }
                $html .= "</td></tr>\n";
            }
            $html .= "</table>\n";
        }
        else
        {
            $html .= "<p align='center'>{$GLOBALS['strNone']} / {$GLOBALS['strUnknown']}<p>";
        }
    }
    else $html = "<p align='center'>{$GLOBALS['strNothingToDisplay']}</p>";

    return $html;
}


/**
* Uploads a file
* @author Kieran Hogg
* @param mixed $file file to upload
* @param int $incidentd
* @returns string path of file
* @todo FIXME this function doesn't seem to make use of $updateid and is never called, is it still used?'
 */
function upload_file($file, $incidentid, $updateid, $type='public')
{
    global $CONFIG, $now;
    $att_max_filesize = return_bytes($CONFIG['upload_max_filesize']);
    $incident_attachment_fspath = $CONFIG['attachment_fspath'] . $id; //FIXME $id never declared
    if ($file['name'] != '')
    {
        // try to figure out what delimeter is being used (for windows or unix)...
        //.... // $delim = (strstr($filesarray[$c],"/")) ? "/" : "\\";
        $delim = (strstr($file['tmp_name'],"/")) ? "/" : "\\";

        // make incident attachment dir if it doesn't exist
        $umask = umask(0000);
        if (!file_exists($CONFIG['attachment_fspath'] . "$id"))
        {
            $mk = @mkdir($CONFIG['attachment_fspath'] ."$id", 0770);
            if (!$mk) trigger_error("Failed creating incident attachment directory: {$incident_attachment_fspath }{$id}", E_USER_WARNING);
        }
        $mk = @mkdir($CONFIG['attachment_fspath'] .$id . "{$delim}{$now}", 0770);
        if (!$mk) trigger_error("Failed creating incident attachment (timestamp) directory: {$incident_attachment_fspath} {$id} {$delim}{$now}", E_USER_WARNING);
        umask($umask);
        $returnpath = $id.$delim.$now.$delim.$file['name'];
        $filepath = $incident_attachment_fspath.$delim.$now.$delim;
        $newfilename = $filepath.$file['name'];

        // Move the uploaded file from the temp directory into the incidents attachment dir
        $mv = move_uploaded_file($file['tmp_name'], $newfilename);
        if (!$mv) trigger_error('!Error: Problem moving attachment from temp directory to: '.$newfilename, E_USER_WARNING);

        // Check file size before attaching
        if ($file['size'] > $att_max_filesize)
        {
            trigger_error("User Error: Attachment too large or file upload error - size: {$file['size']}", E_USER_WARNING);
            // throwing an error isn't the nicest thing to do for the user but there seems to be no guaranteed
            // way of checking file sizes at the client end before the attachment is uploaded. - INL
            return FALSE;
        }
        else
        {
            if (!empty($sit[2]))
            {
                $usertype = 'user';
                $userid = $sit[2];
            }
            else
            {
                $usertype = 'contact';
                $userid = $_SESSION['contactid'];
            }
            $sql = "INSERT INFO `{$GLOBALS['dbFiles']}`
                    (category, filename, size, userid, usertype, path, filedate, refid)
                    VALUES
                    ('{$type}', '{$file['name']}', '{$file['size']}', '{$userid}', '{$usertype}', '{$filepath}', '{$now}', '{$id}')";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            return $returnpath;
        }
    }
}


/**
* Function to return a HTML table row with two columns.
* Giving radio boxes for groups and if the level is 'management' then you are able to view the users (de)selcting
* @param string $title - text to go in the first column
* @param string $level either management or engineer, management is able to (de)select users
* @param int $groupid  Defalt group to select
* @param string $type - Type of buttons to use either radio or checkbox
* @return table row of format <tr><th /><td /></tr>
* @author Paul Heaney
*/
function group_user_selector($title, $level="engineer", $groupid, $type='radio')
{
    global $dbUsers, $dbGroups;
    $str .= "<tr><th>{$title}</th>";
    $str .= "<td align='center'>";

    $sql = "SELECT DISTINCT(g.name), g.id FROM `{$dbUsers}` AS u, `{$dbGroups}` AS g ";
    $sql .= "WHERE u.status > 0 AND u.groupid = g.id ORDER BY g.name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    while ($row = mysql_fetch_object($result))
    {
        if ($type == 'radio')
        {
            $str .= "<input type='radio' name='group' id='{$row->name}' onclick='groupMemberSelect(\"{$row->name}\", \"TRUE\")' ";
        }
        elseif ($type == 'checkbox')
        {
        	$str .= "<input type='checkbox' name='{$row->name}' id='{$row->name}' onclick='groupMemberSelect(\"{$row->name}\", \"FALSE\")' ";
        }

        if ($groupid == $row->id)
        {
            $str .= " checked='checked' ";
            $groupname = $row->name;
        }

        $str .= "/>{$row->name} \n";
    }

    $str .="<br />";


    $sql = "SELECT u.id, u.realname, g.name FROM `{$dbUsers}` AS u, `{$dbGroups}` AS g ";
    $sql .= "WHERE u.status > 0 AND u.groupid = g.id ORDER BY username";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    if ($level == "management")
    {
        $str .= "<select name='users[]' id='include' multiple='multiple' size='20'>";
    }
    elseif ($level == "engineer")
    {
        $str .= "<select name='users[]' id='include' multiple='multiple' size='20' style='display:none'>";
    }

    while ($row = mysql_fetch_object($result))
    {
        $str .= "<option value='{$row->id}'>{$row->realname} ({$row->name})</option>\n";
    }
    $str .= "</select>";
    $str .= "<br />";
    if ($level == "engineer")
    {
        $visibility = " style='display:none'";
    }

    $str .= "<input type='button' id='selectall' onclick='doSelect(true, \"include\")' value='Select All' {$visibility} />";
    $str .= "<input type='button' id='clearselection' onclick='doSelect(false, \"include\")' value='Clear Selection' {$visibility} />";

    $str .= "</td>";
    $str .= "</tr>\n";

    // FIXME make this XHTML valid
    $str .= "<script type='text/javascript'>\n//<![CDATA[\ngroupMemberSelect(\"{$groupname}\", \"TRUE\");\n//]]>\n</script>";

    return $str;
}


/**
* Output html for the 'time to next action' box
* Used in add incident and update incident
 * @param string $formid. HTML ID of the form containing the controls
* @return $html string html to output
* @author Kieran Hogg
* @TODO populate $id
*/
function show_next_action($formid)
{
    global $now, $strAM, $strPM;
    $html = "{$GLOBALS['strPlaceIncidentInWaitingQueue']}<br />";

    $oldtimeofnextaction = incident_timeofnextaction($id); //FIXME $id never populated
    if ($oldtimeofnextaction < 1)
    {
        $oldtimeofnextaction = $now;
    }
    $wait_time = ($oldtimeofnextaction - $now);

    $na_days = floor($wait_time / 86400);
    $na_remainder = $wait_time % 86400;
    $na_hours = floor($na_remainder / 3600);
    $na_remainder = $wait_time % 3600;
    $na_minutes = floor($na_remainder / 60);
    if ($na_days < 0) $na_days = 0;
    if ($na_hours < 0) $na_hours = 0;
    if ($na_minutes < 0) $na_minutes = 0;

    $html .= "<label>";
    $html .= "<input checked='checked' type='radio' name='timetonextaction' ";
    $html .= "id='ttna_none' onchange=\"update_ttna();\" ";
//     $html .= "onclick=\"$('timetonextaction_days').value = ''; window.document.updateform.";
//     $html .= "timetonextaction_hours.value = ''; window.document.updateform."; timetonextaction_minutes.value = '';\"
    $html .= " value='None' />{$GLOBALS['strNo']}";
    $html .= "</label><br />";

    $html .= "<label><input type='radio' name='timetonextaction' ";
    $html .= "id='ttna_time' value='time' onchange=\"update_ttna();\" />";
    $html .= "{$GLOBALS['strForXDaysHoursMinutes']}</label><br />\n";
    $html .= "<span id='ttnacountdown'";
    if (empty($na_days) AND
        empty($na_hours) AND
        empty($na_minutes))
    {
        $html .= " style='display: none;'";
    }
    $html .= ">";
    $html .= "&nbsp;&nbsp;&nbsp;<input name='timetonextaction_days' ";
    $html .= " id='timetonextaction_days' value='{$na_days}' maxlength='3' ";
    $html .= "onclick=\"$('ttna_time').checked = true;\" ";
    $html .= "size='3' /> {$GLOBALS['strDays']}&nbsp;";
    $html .= "<input maxlength='2' name='timetonextaction_hours' ";
    $html .= "id='timetonextaction_hours' value='{$na_hours}' ";
    $html .= "onclick=\"$('ttna_time').checked = true;\" ";
    $html .= "size='3' /> {$GLOBALS['strHours']}&nbsp;";
    $html .= "<input maxlength='2' name='timetonextaction_minutes' id='";
    $html .= "timetonextaction_minutes' value='{$na_minutes}' ";
    $html .= "onclick=\"$('ttna_time').checked = true;\" ";
    $html .= "size='3' /> {$GLOBALS['strMinutes']}";
    $html .= "<br />\n</span>";

    $html .= "<label><input type='radio' name='timetonextaction' id='ttna_date' ";
    $html .= "value='date' onchange=\"update_ttna();\" />";
    $html .= "{$GLOBALS['strUntilSpecificDateAndTime']}</label><br />\n";
    $html .= "<div id='ttnadate' style='display: none;'>";
    $html .= "<input name='date' id='timetonextaction_date' size='10' value='{$date}' ";
    $html .= "onclick=\"$('ttna_date').checked = true;\" /> ";
    $html .= date_picker("{$formid}.timetonextaction_date");
    $html .= " <select name='timeoffset' id='timeoffset' ";
    $html .= "onclick=\"$('ttna_date').checked = true;\" >";
    $html .= "<option value='0'></option>";
    $html .= "<option value='0'>8:00 $strAM</option>";
    $html .= "<option value='1'>9:00 $strAM</option>";
    $html .= "<option value='2'>10:00 $strAM</option>";
    $html .= "<option value='3'>11:00 $strAM</option>";
    $html .= "<option value='4'>12:00 $strPM</option>";
    $html .= "<option value='5'>1:00 $strPM</option>";
    $html .= "<option value='6'>2:00 $strPM</option>";
    $html .= "<option value='7'>3:00 $strPM</option>";
    $html .= "<option value='8'>4:00 $strPM</option>";
    $html .= "<option value='9'>5:00 $strPM</option>";
    $html .= "</select>";
    $html .= "<br />\n</div>";

    return $html;
}


/**
* Output the html for a KB article
*
* @param int $id ID of the KB article
* @param string $mode whether this is internal or external facing, defaults to internal
* @returns string $html kb article html
* @author Kieran Hogg
*/
function kb_article($id, $mode='internal')
{
    global $CONFIG, $iconset;
    $id = intval($id);
    if (!is_number($id) OR $id == 0)
    {
        trigger_error("Incorrect KB ID", E_USER_ERROR);
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        exit;
    }

    $sql = "SELECT * FROM `{$GLOBALS['dbKBArticles']}` WHERE docid='{$id}' LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $kbarticle = mysql_fetch_object($result);

    if (empty($kbarticle->title))
    {
        $kbarticle->title = $GLOBALS['strUntitled'];
    }
    $html .= "<div id='kbarticle'";
    if ($kbarticle->distribution == 'private') $html .= " class='expired'";
    if ($kbarticle->distribution == 'restricted') $html .= " class='urgent'";
    $html .= ">";
    $html .= "<h2 class='kbtitle'>{$kbarticle->title}</h2>";

    if (!empty($kbarticle->distribution) AND $kbarticle->distribution != 'public')
    {
        $html .= "<h2 class='kbdistribution'>{$GLOBALS['strDistribution']}: ".ucfirst($kbarticle->distribution)."</h2>";
    }

    // Lookup what software this applies to
    $ssql = "SELECT * FROM `{$GLOBALS['dbKBSoftware']}` AS kbs, `{$GLOBALS['dbSoftware']}` AS s ";
    $ssql .= "WHERE kbs.softwareid = s.id AND kbs.docid = '{$id}' ";
    $ssql .= "ORDER BY s.name";
    $sresult = mysql_query($ssql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($sresult) >= 1)
    {
        $html .= "<h3>{$GLOBALS['strEnvironment']}</h3>";
        $html .= "<p>{$GLOBALS['strTheInfoInThisArticle']}:</p>\n";
        $html .= "<ul>\n";
        while ($kbsoftware = mysql_fetch_object($sresult))
        {
            $html .= "<li>{$kbsoftware->name}</li>\n";
        }
        $html .= "</ul>\n";
    }

    $csql = "SELECT * FROM `{$GLOBALS['dbKBContent']}` WHERE docid='{$id}' ";
    $cresult = mysql_query($csql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $restrictedcontent = 0;
    while ($kbcontent = mysql_fetch_object($cresult))
    {
        switch ($kbcontent->distribution)
        {
            case 'private':
                if ($mode != 'internal')
                {
                    echo "<p class='error'>{$GLOBALS['strPermissionDenied']}</p>";
                    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
                    exit;
                }
                $html .= "<div class='kbprivate'><h3>{$kbcontent->header} (private)</h3>";
                $restrictedcontent++;
            break;

            case 'restricted':
                if ($mode != 'internal')
                {
                    echo "<p class='error'>{$GLOBALS['strPermissionDenied']}</p>";
                    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
                    exit;
                }
                $html .= "<div class='kbrestricted'><h3>{$kbcontent->header}</h3>";
                $restrictedcontent++;
            break;

            default:
                $html .= "<div><h3>{$kbcontent->header}</h3>";
        }
        //$html .= "<{$kbcontent->headerstyle}>{$kbcontent->header}</{$kbcontent->headerstyle}>\n";
        $html .= '';
        $kbcontent->content=nl2br($kbcontent->content);
        $search = array("/(?<!quot;|[=\"]|:\/{2})\b((\w+:\/{2}|www\.).+?)"."(?=\W*([<>\s]|$))/i", "/(([\w\.]+))(@)([\w\.]+)\b/i");
        $replace = array("<a href=\"$1\">$1</a>", "<a href=\"mailto:$0\">$0</a>");
        $kbcontent->content = preg_replace("/href=\"www/i", "href=\"http://www", preg_replace ($search, $replace, $kbcontent->content));
        $html .= bbcode($kbcontent->content);
        $author[]=$kbcontent->ownerid;
        $html .= "</div>\n";

    }

    if ($restrictedcontent > 0)
    {
        $html .= "<h3>{$GLOBALS['strKey']}</h3>";
        $html .= "<p><span class='keykbprivate'>{$GLOBALS['strPrivate']}</span>".help_link('KBPrivate')." &nbsp; ";
        $html .= "<span class='keykbrestricted'>{$GLOBALS['strRestricted']}</span>".help_link('KBRestricted')."</p>";
    }


    $html .= "<h3>{$GLOBALS['strArticle']}</h3>";
    //$html .= "<strong>{$GLOBALS['strDocumentID']}</strong>: ";
    $html .= "<p><strong>{$CONFIG['kb_id_prefix']}".leading_zero(4,$kbarticle->docid)."</strong> ";
    $pubdate = mysql2date($kbarticle->published);
    if ($pubdate > 0)
    {
        $html .= "{$GLOBALS['strPublished']} ";
        $html .= ldate($CONFIG['dateformat_date'],$pubdate)."<br />";
    }

    if ($mode == 'internal')
    {
        if (is_array($author))
        {
            $author=array_unique($author);
            $countauthors=count($author);
            $count=1;
            if ($countauthors > 1)
            {
                $html .= "<strong>{$GLOBALS['strAuthors']}</strong>:<br />";
            }
            else
            {
                $html .= "<strong>{$GLOBALS['strAuthor']}:</strong> ";
            }
            foreach ($author AS $authorid)
            {
                $html .= user_realname($authorid,TRUE);
                if ($count < $countauthors) $html .= ", " ;
                $count++;
            }
        }
    }

    $html .= "<br />";
    if (!empty($kbarticle->keywords))
    {
        $html .= "<strong>{$GLOBALS['strKeywords']}</strong>: ";
        if ($mode == 'internal')
        {
            $html .= preg_replace("/\[([0-9]+)\]/", "<a href=\"incident_details.php?id=$1\" target=\"_blank\">$0</a>", $kbarticle->keywords);
        }
        else
        {
            $html .= $kbarticle->keywords;
        }
        $html .= "<br />";
    }

    //$html .= "<h3>{$GLOBALS['strDisclaimer']}</h3>";
    $html .= "</p><hr />";
    $html .= $CONFIG['kb_disclaimer_html'];
    $html .= "</div>";

    if ($mode == 'internal')
    {
        $html .= "<p align='center'>";
        $html .= "<a href='kb.php'>{$GLOBALS['strBackToList']}</a> | ";
        $html .= "<a href='kb_article.php?id={$kbarticle->docid}'>{$GLOBALS['strEdit']}</a></p>";
    }
    return $html;
}

/**
* Output the html for the edit site form
*
* @param int $site ID of the site
* @param string $mode whether this is internal or external facing, defaults to internal
* @return string $html edit site form html
* @author Kieran Hogg
*/
function show_edit_site($site, $mode='internal')
{
    global $CONFIG;
    $sql = "SELECT * FROM `{$GLOBALS['dbSites']}` WHERE id='$site' ";
    $siteresult = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    while ($siterow = mysql_fetch_array($siteresult))
    {
        if ($mode == 'internal')
        {
            $html .= "<h2>".icon('site', 32)." {$GLOBALS['strEditSite']}: {$site} - ";
            $html .= site_name($site)."</h2>";
        }
        else
        {
            $html .= "<h2>".icon('site', 32)." ".site_name($site)."</h2>";
        }

        $html .= "<form name='edit_site' action='{$_SERVER['PHP_SELF']}";
        $html .= "?action=update' method='post' onsubmit='return ";
        $html .= "confirm_action(\"{$GLOBALS['strAreYouSureMakeTheseChanges']}\")'>";
        $html .= "<table align='center' class='vertical'>";
        $html .= "<tr><th>{$GLOBALS['strName']}:</th>";
        $html .= "<td><input class='required' maxlength='50' name='name' size='40' value='{$siterow['name']}' />";
        $html .= "<span class='required'>{$GLOBALS['strRequired']}</span></td></tr>\n";
        if ($mode == 'internal')
        {
            $html .= "<tr><th>{$GLOBALS['strTags']}:</th><td><textarea rows='2' cols='60' name='tags'>";
            $html .= list_tags($site, TAG_SITE, false)."</textarea>\n";
        }
        $html .= "<tr><th>{$GLOBALS['strDepartment']}:</th>";
        $html .= "<td><input maxlength='50' name='department' size='40' value='{$siterow['department']}' />";
        $html .= "</td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strAddress1']}:</th>";
        $html .= "<td><input maxlength='50' name='address1'";
        $html .= "size='40' value='{$siterow['address1']}' />";
        $html .= "</td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strAddress2']}: </th><td><input maxlength='50' name='address2' size='40' value='{$siterow['address2']}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strCity']}:</th><td><input maxlength='255' name='city' size='40' value='{$siterow['city']}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strCounty']}:</th><td><input maxlength='255' name='county' size='40' value='{$siterow['county']}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strPostcode']}:</th><td><input maxlength='255' name='postcode' size='40' value='{$siterow['postcode']}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strCountry']}:</th><td>".country_drop_down('country', $siterow['country'])."</td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strTelephone']}:</th><td>";
        $html .= "<input class='required' maxlength='255' name='telephone' size='40' value='{$siterow['telephone']}' />";
        $html .= "<span class='required'>{$GLOBALS['strRequired']}</span></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strFax']}:</th><td>";
        $html .= "<input maxlength='255' name='fax' size='40' value='{$siterow['fax']}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strEmail']}:</th><td>";
        $html .= "<input class='required' maxlength='255' name='email' size='40' value='{$siterow['email']}' />";
        $html .= "<span class='required'>{$GLOBALS['strRequired']}</span></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strWebsite']}:</th><td>";
        $html .= "<input maxlength='255' name='websiteurl' size='40' value='{$siterow['websiteurl']}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strSiteType']}:</th><td>\n";
        $html .= sitetype_drop_down('typeid', $siterow['typeid']);
        $html .= "</td></tr>\n";
        if ($mode == 'internal')
        {
            $html .= "<tr><th>{$GLOBALS['strSalesperson']}:</th><td>";
            $html .= user_drop_down('owner', $siterow['owner'], $accepting = FALSE, '', '', TRUE);
            $html .= "</td></tr>\n";
        }
        if ($mode == 'internal')
        {
            $html .= "<tr><th>{$GLOBALS['strIncidentPool']}:</th>";
            $incident_pools = explode(',', "{$GLOBALS['strNone']},{$CONFIG['incident_pools']}");
            if (array_key_exists($siterow['freesupport'], $incident_pools) == FALSE)
            {
                array_unshift($incident_pools,$siterow['freesupport']);
            }
            $html .= "<td>".array_drop_down($incident_pools,'incident_poolid',$siterow['freesupport'])."</td></tr>";
            $html .= "<tr><th>{$GLOBALS['strActive']}:</th><td><input type='checkbox' name='active' ";
            if ($siterow['active'] == 'true')
            {
                $html .= "checked='".$siterow['active']."'";
            }
            $html .= " value='true' /></td></tr>\n";
            $html .= "<tr><th>{$GLOBALS['strNotes']}:</th><td>";
            $html .= "<textarea rows='5' cols='30' name='notes'>{$siterow['notes']}</textarea>";
            $html .= "</td></tr>\n";
        }
        plugin_do('edit_site_form');
        $html .= "</table>\n";
        $html .= "<input name='site' type='hidden' value='$site' />";
        $html .= "<p><input name='submit' type='submit' value='{$GLOBALS['strSave']}' /></p>";
        $html .= "</form>";
    }
    return $html;
}


/**
* Output the html for an add contact form
*
* @param int $siteid - the site you want to add the contact to
* @param string $mode - whether this is internal or external facing, defaults to internal
* @return string $html add contact form html
* @author Kieran Hogg
*/
function show_add_contact($siteid = 0, $mode = 'internal')
{
    global $CONFIG;
    $returnpage = cleanvar($_REQUEST['return']);
    if (!empty($_REQUEST['name']))
    {
        $name = explode(' ',cleanvar(urldecode($_REQUEST['name'])), 2);
        $_SESSION['formdata']['add_contact']['forenames'] = ucfirst($name[0]);
        $_SESSION['formdata']['add_contact']['surname'] = ucfirst($name[1]);
    }

    $html = show_form_errors('add_contact');
    clear_form_errors('add_contact');
    $html .= "<h2>".icon('contact', 32)." ";
    $html .= "{$GLOBALS['strNewContact']}</h2>";

    if ($mode == 'internal')
    {
        $html .= "<h5 class='warning'>{$GLOBALS['strAvoidDupes']}</h5>";
    }
    $html .= "<form name='contactform' action='{$_SERVER['PHP_SELF']}' ";
    $html .= "method='post' onsubmit=\"return confirm_action('{$GLOBALS['strAreYouSureAdd']}')\">";
    $html .= "<table align='center' class='vertical'>";
    $html .= "<tr><th>{$GLOBALS['strName']}</th>\n";

    $html .= "<td>";
    $html .= "\n<table><tr><td align='center'>{$GLOBALS['strTitle']}<br />";
    $html .= "<input maxlength='50' name='courtesytitle' title=\"";
    $html .= "{$GLOBALS['strCourtesyTitle']}\" size='7'";
    if ($_SESSION['formdata']['add_contact']['courtesytitle'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['courtesytitle']}'";
    }
    $html .= "/></td>\n";

    $html .= "<td align='center'>{$GLOBALS['strForenames']}<br />";
    $html .= "<input class='required' maxlength='100' name='forenames' ";
    $html .= "size='15' title=\"{$GLOBALS['strForenames']}\"";
    if ($_SESSION['formdata']['add_contact']['forenames'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['forenames']}'";
    }
    $html .= "/></td>\n";

    $html .= "<td align='center'>{$GLOBALS['strSurname']}<br />";
    $html .= "<input class='required' maxlength='100' name='surname' ";
    $html .= "size='20' title=\"{$GLOBALS['strSurname']}\"";
    if ($_SESSION['formdata']['add_contact']['surname'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['surname']}'";
    }
    $html .= " /> <span class='required'>{$GLOBALS['strRequired']}</span></td></tr>\n";
    $html .= "</table>\n</td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strJobTitle']}</th><td><input maxlength='255'";
    $html .= " name='jobtitle' size='35' title=\"{$GLOBALS['strJobTitle']}\"";
    if ($_SESSION['formdata']['add_contact']['jobtitle'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['jobtitle']}'";
    }
    $html .= " /></td></tr>\n";
    if ($mode == 'internal')
    {
        $html .= "<tr><th>{$GLOBALS['strSite']}</th><td>";
        $html .= site_drop_down('siteid',$siteid, TRUE)."<span class='required'>{$GLOBALS['strRequired']}</span></td></tr>\n";
    }
    else
    {
        // For external always force the site to be the session site
        $html .= "<input type='hidden' name='siteid' value='{$_SESSION['siteid']}' />";
    }

    $html .= "<tr><th>{$GLOBALS['strDepartment']}</th><td><input maxlength='255' name='department' size='35'";
    if ($_SESSION['formdata']['add_contact']['department'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['department']}'";
    }
    $html .= "/></td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strEmail']}</th><td>";
    $html .= "<input class='required' maxlength='100' name='email' size='35'";
    if ($_SESSION['formdata']['add_contact']['email'])
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['email']}'";
    }
    $html .= "/><span class='required'>{$GLOBALS['strRequired']}</span> ";

    $html .= "<label>";
    $html .= html_checkbox('dataprotection_email', 'No');
    $html .= "{$GLOBALS['strEmail']} {$GLOBALS['strDataProtection']}</label>".help_link("EmailDataProtection");
    $html .= "</td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strTelephone']}</th><td><input maxlength='50' name='phone' size='35'";
    if ($_SESSION['formdata']['add_contact']['phone'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['phone']}'";
    }
    $html .= "/> ";

    $html .= "<label>";
    $html .= html_checkbox('dataprotection_phone', 'No');
    $html .= "{$GLOBALS['strTelephone']} {$GLOBALS['strDataProtection']}</label>".help_link("TelephoneDataProtection");
    $html .= "</td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strMobile']}</th><td><input maxlength='100' name='mobile' size='35'";
    if ($_SESSION['formdata']['add_contact']['mobile'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['mobile']}'";
    }
    $html .= "/></td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strFax']}</th><td><input maxlength='50' name='fax' size='35'";
    if ($_SESSION['formdata']['add_contact']['fax'])
    {
        $html .= "value='{$_SESSION['formdata']['add_contact']['fax']}'";
    }
    $html .= "/></td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strAddress']}</th><td><label>";
    $html .= html_checkbox('dataprotection_address', 'No');
    $html .= " {$GLOBALS['strAddress']} {$GLOBALS['strDataProtection']}</label>";
    $html .= help_link("AddressDataProtection")."</td></tr>\n";
    $html .= "<tr><th></th><td><label><input type='checkbox' name='usesiteaddress' value='yes' onclick=\"$('hidden').toggle();\" /> {$GLOBALS['strSpecifyAddress']}</label></td></tr>\n";
    $html .= "<tbody id='hidden' style='display:none'>";
    $html .= "<tr><th>{$GLOBALS['strAddress1']}</th>";
    $html .= "<td><input maxlength='255' name='address1' size='35' /></td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strAddress2']}</th>";
    $html .= "<td><input maxlength='255' name='address2' size='35' /></td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strCity']}</th><td><input maxlength='255' name='city' size='35' /></td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strCounty']}</th><td><input maxlength='255' name='county' size='35' /></td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strCountry']}</th><td>";
    $html .= country_drop_down('country', $CONFIG['home_country'])."</td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strPostcode']}</th><td><input maxlength='255' name='postcode' size='35' /></td></tr>\n";
    $html .= "</tbody>";
    if ($mode == 'internal')
    {
        $html .= "<tr><th>{$GLOBALS['strNotes']}</th><td><textarea cols='60' rows='5' name='notes'>";
        if ($_SESSION['formdata']['add_contact']['notes'] != '')
        {
            $html .= $_SESSION['formdata']['add_contact']['notes'];
        }
        $html .= "</textarea></td></tr>\n";
    }
    $html .= "<tr><th>{$GLOBALS['strEmailDetails']}</th>";
    // Check the box to send portal details, only if portal is enabled
    $html .= "<td><input type='checkbox' name='emaildetails'";
    if ($CONFIG['portal'] == TRUE) $html .= " checked='checked'";
    else $html .= " disabled='disabled'";
    $html .= ">";
    $html .= "<label for='emaildetails'>{$GLOBALS['strEmailContactLoginDetails']}</td></tr>";
    $html .= "</table>\n\n";
    if (!empty($returnpage)) $html .= "<input type='hidden' name='return' value='{$returnpage}' />";
    $html .= "<p><input name='submit' type='submit' value=\"{$GLOBALS['strAddContact']}\" /></p>";
    $html .= "</form>\n";

    //cleanup form vars
    clear_form_data('add_contact');

    return $html;
}


/**
* Procceses a new contact
*
* @author Kieran Hogg
*/
function process_add_contact($mode = 'internal')
{
    global $now, $CONFIG, $dbContacts, $sit;
    // Add new contact
    // External variables
    $siteid = mysql_real_escape_string($_REQUEST['siteid']);
    $email = strtolower(cleanvar($_REQUEST['email']));
    $dataprotection_email = mysql_real_escape_string($_REQUEST['dataprotection_email']);
    $dataprotection_phone = mysql_real_escape_string($_REQUEST['dataprotection_phone']);
    $dataprotection_address = mysql_real_escape_string($_REQUEST['dataprotection_address']);
    $username = cleanvar($_REQUEST['username']);
    $courtesytitle = cleanvar($_REQUEST['courtesytitle']);
    $forenames = cleanvar($_REQUEST['forenames']);
    $surname = cleanvar($_REQUEST['surname']);
    $jobtitle = cleanvar($_REQUEST['jobtitle']);
    $address1 = cleanvar($_REQUEST['address1']);
    $address2 = cleanvar($_REQUEST['address2']);
    $city = cleanvar($_REQUEST['city']);
    $county = cleanvar($_REQUEST['county']);
    if (!empty($address1)) $country = cleanvar($_REQUEST['country']);
    else $country='';
    $postcode = cleanvar($_REQUEST['postcode']);
    $phone = cleanvar($_REQUEST['phone']);
    $mobile = cleanvar($_REQUEST['mobile']);
    $fax = cleanvar($_REQUEST['fax']);
    $department = cleanvar($_REQUEST['department']);
    $notes = cleanvar($_REQUEST['notes']);
    $returnpage = cleanvar($_REQUEST['return']);
    $_SESSION['formdata']['add_contact'] = $_REQUEST;

    $errors = 0;
    // check for blank name
    if ($surname == '')
    {
        $errors++;
        $_SESSION['formerrors']['add_contact']['surname'] = $GLOBALS['strMustEnterSurname'];
    }
    // check for blank site
    if ($siteid == '')
    {
        $errors++;
        $_SESSION['formerrors']['add_contact']['siteid'] = $GLOBALS['strMustSelectCustomerSite'];
    }
    // check for blank email
    if ($email == '' OR $email=='none' OR $email=='n/a')
    {
        $errors++;
        $_SESSION['formerrors']['add_contact']['email'] = $GLOBALS['strMustEnterEmail'];
    }
    if ($siteid==0 OR $siteid=='')
    {
        $errors++;
        $_SESSION['formerrors']['add_contact']['siteid'] = $GLOBALS['strMustSelectSite'];
    }
    // Check this is not a duplicate
    $sql = "SELECT id FROM `{$dbContacts}` WHERE email='$email' AND LCASE(surname)=LCASE('$surname') LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) >= 1)
    {
        $errors++;
        $_SESSION['formerrors']['add_contact']['duplicate'] = $GLOBALS['strContactRecordExists'];
    }


    // add contact if no errors
    if ($errors == 0)
    {
        if (!empty($dataprotection_email))
        {
            $dataprotection_email = 'Yes';
        }
        else
        {
            $dataprotection_email = 'No';
        }

        if (!empty($dataprotection_phone))
        {
            $dataprotection_phone = 'Yes';
        }
        else
        {
            $dataprotection_phone = 'No';
        }

        if (!empty($dataprotection_address))
        {
            $dataprotection_address = 'Yes';
        }
        else
        {
            $dataprotection_address = 'No';
        }

        // generate username and password

        $username = strtolower(substr($surname, 0, strcspn($surname, " ")));
        $prepassword = generate_password();

        $password = md5($prepassword);

        $sql  = "INSERT INTO `{$dbContacts}` (username, password, courtesytitle, forenames, surname, jobtitle, ";
        $sql .= "siteid, address1, address2, city, county, country, postcode, email, phone, mobile, fax, ";
        $sql .= "department, notes, dataprotection_email, dataprotection_phone, dataprotection_address, ";
        $sql .= "timestamp_added, timestamp_modified) ";
        $sql .= "VALUES ('$username', '$password', '$courtesytitle', '$forenames', '$surname', '$jobtitle', ";
        $sql .= "'$siteid', '$address1', '$address2', '$city', '$county', '$country', '$postcode', '$email', ";
        $sql .= "'$phone', '$mobile', '$fax', '$department', '$notes', '$dataprotection_email', ";
        $sql .= "'$dataprotection_phone', '$dataprotection_address', '$now', '$now')";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        // concatenate username with insert id to make unique
        $newid = mysql_insert_id();
        $username = $username . $newid;
        $sql = "UPDATE `{$dbContacts}` SET username='{$username}' WHERE id='{$newid}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (!$result)
        {
            if ($mode == 'internal')
            {
                html_redirect("contact_add.php", FALSE);
            }
            else
            {
                html_redirect("addcontact.php", FALSE);
            }
        }
        else
        {
            clear_form_data('add_contact');
            clear_form_errors('add_contact');
            $sql = "SELECT username, password FROM `{$dbContacts}` WHERE id=$newid";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            else
            {
                if ($CONFIG['portal'] AND $_POST['emaildetails'] == 'on')
                {
                    trigger('TRIGGER_NEW_CONTACT', array('contactid' => $newid, 'prepassword' => $prepassword, 'userid' => $sit[2]));
                }

                if ($returnpage == 'addincident')
                {
                    html_redirect("incident_add.php?action=findcontact&contactid={$newid}");
                    exit;
                }
                elseif ($mode == 'internal')
                {
                    html_redirect("contact_details.php?id={$newid}");
                    exit;
                }
                else
                {
                    html_redirect("contactdetails.php?id={$newid}");
                    exit;
                }
            }
        }

    }
    else
    {
        if ($mode == 'internal')
        {
            html_redirect('contact_add.php', FALSE);
        }
        else
        {
            html_redirect('addcontact.php', FALSE);
        }
    }
}


/**
* Outputs the name of a KB article, used for triggers
*
* @param int $kbid ID of the KB article
* @return string $name kb article name
* @author Kieran Hogg
*/
function kb_name($kbid)
{
    $kbid = intval($kbid);
    $sql = "SELECT title FROM `{$GLOBALS['dbKBArticles']}` WHERE docid='{$kbid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    else
    {
        $row = mysql_fetch_object($result);
        return $row->title;
    }
}


/**
* Outputs the full base url of the install, e.g. http://www.example.com/
*
* @return string base url of the install
* @author Kieran Hogg
*/
function application_url()
{
    global $CONFIG;
    if (empty($CONFIG['application_uriprefix']))
    {
        $url = parse_url($_SERVER['HTTP_REFERER']);
        if ($_SERVER['HTTPS'] == 'off' OR empty($_SERVER['HTTPS']))
        {
            $baseurl = "http://";
        }
        else
        {
            $baseurl = "https://";
        }
        $baseurl .= "{$_SERVER['HTTP_HOST']}";
    }
    else
    {
        $baseurl = "{$CONFIG['application_uriprefix']}";
    }
    $baseurl .= "{$CONFIG['application_webpath']}";

    return $baseurl;
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
    $maintid = intval($maintid);
    $productid = db_read_column('product', $GLOBALS['dbMaintenance'], $maintid);
    $sql = "SELECT name FROM `{$GLOBALS['dbProducts']}` WHERE id='{$productid}'";
    $result = mysql_query($sql);
    $productobj = mysql_fetch_object($result);
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
    $maintid = intval($maintid);
    $sql = "SELECT site FROM `{$GLOBALS['dbMaintenance']}` WHERE id='{$maintid}'";
    $result = mysql_query($sql);
    $maintobj = mysql_fetch_object($result);

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
* Sets up default triggers for new users or upgraded users
*
* @param int $userid ID of the user
* @return bool TRUE on success, FALSE if not
* @author Kieran Hogg
*/
function setup_user_triggers($userid)
{
    $return = TRUE;
    $userid = intval($userid);
    if ($userid != 0)
    {
        $sqls[] = "INSERT INTO `{$GLOBALS['dbTriggers']}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`)
                VALUES('TRIGGER_INCIDENT_ASSIGNED', {$userid}, 'ACTION_NOTICE', 'NOTICE_INCIDENT_ASSIGNED', '', '{userid} == {$userid}');";
        $sqls[] = "INSERT INTO `{$GLOBALS['dbTriggers']}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`)
                VALUES('TRIGGER_SIT_UPGRADED', {$userid}, 'ACTION_NOTICE', 'NOTICE_SIT_UPGRADED', '', '');";
        $sqls[] = "INSERT INTO `{$GLOBALS['dbTriggers']}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`)
                VALUES('TRIGGER_INCIDENT_CLOSED', {$userid}, 'ACTION_NOTICE', 'NOTICE_INCIDENT_CLOSED', '', '{userid} == {$userid}');";
        $sqls[] = "INSERT INTO `{$GLOBALS['dbTriggers']}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`)
                VALUES('TRIGGER_INCIDENT_NEARING_SLA', {$userid}, 'ACTION_NOTICE', 'NOTICE_INCIDENT_NEARING_SLA', '',
                '{ownerid} == {$userid} OR {townerid} == {$userid}');";
        $sqls[] = "INSERT INTO `{$GLOBALS['dbTriggers']}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`)
                VALUES('TRIGGER_LANGUAGE_DIFFERS', {$userid}, 'ACTION_NOTICE', 'NOTICE_LANGUAGE_DIFFERS', '', '');";


        foreach ($sqls AS $sql)
        {
            mysql_query($sql);
            if (mysql_error())
            {
                trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                $return = FALSE;
            }
        }
    }
    else
    {
        trigger_error("setup_user_triggers() Invalid userid '{$userid}' specified", E_USER_NOTICE);
        $return = FALSE;
    }

    return $return;
}


/**
* Returns the SLA ID of a contract
*
* @param int $maintid ID of the contract
* @return int ID of the SLA
* @author Kieran Hogg
*/
function contract_slaid($maintid)
{
    $maintid = intval($maintid);
    $slaid = db_read_column('servicelevelid', $GLOBALS['dbMaintenance'], $maintid);
    return $slaid;
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
* Function to return currently running SiT! version
* @return String - Currently running application version
*/
function application_version_string()
{
    global $application_version_string;
    return $application_version_string;
}


/**
* Returns the currently running schema version
* @author Paul Heaney
* @return String - currently running schema version
*/
function database_schema_version()
{
    $return = '';
    $sql = "SELECT `schemaversion` FROM `{$GLOBALS['dbSystem']}` WHERE id = 0";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $return = FALSE;
    }

    if (mysql_num_rows($result) > 0)
    {
        list($return) = mysql_fetch_row($result);
    }

    return $return;
}




/**
* Returns the contacts's portal username
*
* @param int $userid ID of the contact
* @return string username
* @author Kieran Hogg
*/
function contact_username($userid)
{
    $userid = intval($userid);
    return db_read_column('username', $GLOBALS['dbContacts'], $userid);
}


/**
* Populates $_SESSION['syslang], system language strings
*
* @author Kieran Hogg
*/
function populate_syslang()
{
    global $CONFIG;
    // Populate $SYSLANG with system lang
    $file = APPLICATION_I18NPATH . "{$CONFIG['default_i18n']}.inc.php";
    if (file_exists($file))
    {
        $fh = fopen($file, "r");

        $theData = fread($fh, filesize($file));
        fclose($fh);
        $lines = explode("\n", $theData);
        foreach ($lines as $values)
        {
            $badchars = array("$", "\"", "\\", "<?php", "?>");
            $values = trim(str_replace($badchars, '', $values));
            if (substr($values, 0, 3) == "str")
            {
                $vars = explode("=", $values);
                $vars[0] = trim($vars[0]);
                $vars[1] = trim(substr_replace($vars[1], "",-2));
                $vars[1] = substr_replace($vars[1], "",0, 1);
                $SYSLANG[$vars[0]] = $vars[1];
            }
        }
        $_SESSION['syslang'] = $SYSLANG;
    }
    else
    {
        trigger_error("File specified in \$CONFIG['default_i18n'] can't be found", E_USER_ERROR);
    }
}


/**
* Outputs a contact's contract associate, if the viewing user is allowed
* @author Kieran Hogg
* @param int $userid ID of the contact
* @retval string output html
* @todo TODO should this be renamed, it has nothing to do with users
*/
function user_contracts_table($userid, $mode = 'internal')
{
    global $now, $CONFIG, $sit;
    if ((!empty($sit[2]) AND user_permission($sit[2], 30)
        OR ($_SESSION['usertype'] == 'admin'))) // view supported products
    {
        $html .= "<h4>".icon('contract', 16)." {$GLOBALS['strContracts']}:</h4>";
        // Contracts we're explicit supported contact for
        $sql  = "SELECT sc.maintenanceid AS maintenanceid, m.product, p.name AS productname, ";
        $sql .= "m.expirydate, m.term ";
        $sql .= "FROM `{$GLOBALS['dbContacts']}` AS c, ";
        $sql .= "`{$GLOBALS['dbSupportContacts']}` AS sc, ";
        $sql .= "`{$GLOBALS['dbMaintenance']}` AS m, ";
        $sql .= "`{$GLOBALS['dbProducts']}` AS p ";
        $sql .= "WHERE c.id = '{$userid}' ";
        $sql .= "AND (sc.maintenanceid=m.id AND sc.contactid='$userid') ";
        $sql .= "AND m.product=p.id  ";
        // Contracts we're an 'all supported' on
        $sql .= "UNION ";
        $sql .= "SELECT m.id AS maintenanceid, m.product, p.name AS productname, ";
        $sql .= "m.expirydate, m.term ";
        $sql .= "FROM `{$GLOBALS['dbContacts']}` AS c, ";
        $sql .= "`{$GLOBALS['dbMaintenance']}` AS m, ";
        $sql .= "`{$GLOBALS['dbProducts']}` AS p ";
        $sql .= "WHERE c.id = '{$userid}' AND c.siteid = m.site ";
        $sql .= "AND m.allcontactssupported = 'yes' ";
        $sql .= "AND m.product=p.id  ";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result)>0)
        {
            $html .= "<table align='center' class='vertical'>";
            $html .= "<tr>";
            $html .= "<th>{$GLOBALS['strID']}</th><th>{$GLOBALS['strProduct']}</th><th>{$GLOBALS['strExpiryDate']}</th>";
            $html .= "</tr>\n";

            $supportcount=1;
            $shade='shade2';
            while ($supportedrow = mysql_fetch_array($result))
            {
                if ($supportedrow['term'] == 'yes')
                {
                    $shade='expired';
                }

                if ($supportedrow['expirydate'] < $now AND $supportedrow['expirydate'] != -1)
                {
                    $shade='expired';
                }

                $html .= "<tr><td class='$shade'>";
                $html .= ''.icon('contract', 16)." ";
                if ($mode == 'internal')
                {
                    $html .= "<a href='contract_details.php?id=";
                }
                else
                {
                    $html .= "<a href='contracts.php?id=";
                }
                $html .= "{$supportedrow['maintenanceid']}'>";
                $html .= "{$GLOBALS['strContract']}: ";
                $html .= "{$supportedrow['maintenanceid']}</a></td>";
                $html .= "<td class='$shade'>{$supportedrow['productname']}</td>";
                $html .= "<td class='$shade'>";
                if ($supportedrow['expirydate'] == -1)
                {
                    $html .= $GLOBALS['strUnlimited'];
                }
                else
                {
                    $html .= ldate($CONFIG['dateformat_date'], $supportedrow['expirydate']);
                }
                if ($supportedrow['term'] == 'yes')
                {
                    $html .= " {$GLOBALS['strTerminated']}";
                }

                $html .= "</td>";
                $html .= "</tr>\n";
                $supportcount++;
                $shade = 'shade2';
            }
            $html .= "</table>\n";
        }
        else
        {
            $html .= "<p align='center'>{$GLOBALS['strNone']}</p>\n";
        }

        if ($mode == 'internal')
        {
            $html .= "<p align='center'>";
            $html .= "<a href='contract_add_contact.php?contactid={$userid}&amp;context=contact'>";
            $html .= "{$GLOBALS['strAssociateContactWithContract']}</a></p>\n";
        }

    }

    return $html;
}

// -------------------------- // -------------------------- // --------------------------
// leave this section at the bottom of functions.inc.php ================================

// Evaluate and Load plugins
if (is_array($CONFIG['plugins']))
{
    foreach ($CONFIG['plugins'] AS $plugin)
    {
        // Remove any dots
        $plugin = str_replace('.','',$plugin);
        // Remove any slashes

        $plugin = str_replace('/','',$plugin);
        $plugini18npath = APPLICATION_PLUGINPATH . "{$plugin}". DIRECTORY_SEPARATOR . "i18n". DIRECTORY_SEPARATOR;
        if ($plugin != '')
        {
            if (file_exists(APPLICATION_PLUGINPATH . "{$plugin}.php"))
            {
                include (APPLICATION_PLUGINPATH . "{$plugin}.php");
                // Load i18n if it exists
                if (file_exists($plugini18npath))
                {
                    @include ("{$plugini18npath}{$CONFIG['default_i18n']}.inc.php");
                    if (!empty($_SESSION['lang'])
                        AND $_SESSION['lang'] != $CONFIG['default_i18n'])
                    {
                        @include ("{$plugini18npath}{$_SESSION['lang']}.inc.php");
                    }
                }
            }
            else
            {
                // Only trigger a warning if headers are sent
                // No need to break whole pages
                if (headers_sent())
                {
                    trigger_error("Plugin '{$plugin}' could not be found.", E_USER_WARNING);
                }
            }
        }
    }
}


/**
  * Register a plugin context handler function
  * @author Ivan Lucas
  * @param string $context - A valid plugin context
  * @param string $action - Your plugin context handler function name
  * @note see http://sitracker.org/wiki/CreatingPlugins for help and a list
  *  of contexts
*/
function plugin_register($context, $action)
{
    global $PLUGINACTIONS;
    $PLUGINACTIONS[$context][] = $action;
}


/**
    * Call a plugin function that handles a given context
    * @author Ivan Lucas
    * @param string $context - Plugin context,
    * @param string $optparms - Optional parameters
    * @retval mixed - Whatever the plugin function returns
    * @note This function calls a plugin function or multiple plugin
    *  functions, if they exist.
    *  see http://sitracker.org/wiki/CreatingPlugins for help and a list
    *  of contexts
*/
function plugin_do($context, $optparams = FALSE)
{
    global $PLUGINACTIONS;
    foreach ($GLOBALS as $key => $val) { global $$key; }
    $rtnvalue = '';
    if (is_array($PLUGINACTIONS[$context]))
    {
        foreach ($PLUGINACTIONS[$context] AS $pluginaction)
        {
            // Call Variable function (function with variable name)
            if ($optparams)
            {
                $rtn = $action($optparams);
            }
            else
            {
                $rtn = $pluginaction();
            }

            // Append return value
            if (is_array($rtn) AND is_array($rtnvalue))
            {
                array_push($rtnvalue, $rtn);
            }
            elseif (is_array($rtn) AND !is_array($rtnvalue))
            {
                $rtnvalue=array(); array_push($rtnvalue, $rtn);
            }
            else
            {
                $rtnvalue .= $rtn;
            }
        }
    }
    return $rtnvalue;
}


/**
* Function passed a day, month and year to identify if this day is defined as a public holiday
* @author Paul Heaney
* FIXME this is horribily inefficient, we should load a table ONCE with all the public holidays
        and then just check that with this function
*/
function is_day_bank_holiday($day, $month, $year)
{
    global $dbHolidays;

    $date = "{$year}-{$month}-{$day}";
    $sql = "SELECT * FROM `{$dbHolidays}` WHERE type = 10 AND date = '{$date}'";

    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_ERROR);
        return FALSE;
    }

    if (mysql_num_rows($result) > 0) return TRUE;
    else return FALSE;
}


/**
* Outputs a table or csv file based on csv-based array
* @author Kieran Hogg
* @param array $data Array of data, see @note for format
* @param string $ouput Whether to show a table or create a csv file
* @return string $html The html to produce the output
* @note format: $array[] = 'Colheader1,Colheader2'; $array[] = 'data1,data2';
*/
function create_report($data, $output = 'table', $filename = 'report.csv')
{
    if ($output == 'table')
    {
        $html = "\n<table align='center'><tr>\n";
        $data = explode("\n", $data);
        $headers = explode(',', $data[0]);
        $rows = sizeof($headers);
        foreach ($headers as $header)
        {
            $html .= colheader($header, $header);
        }
        $html .= "</tr>";

        if (sizeof($data) == 1)
        {
            $html .= "<tr><td rowspan='{$rows}'>{$GLOBALS['strNoRecords']}</td></tr>";
        }
        else
        {
            // use 1 -> sizeof as we've already done one row
            for ($i = 1; $i < sizeof($data); $i++)
            {
                $html .= "<tr>";
                $values = explode(',', $data[$i]);
                foreach ($values as $value)
                {
                    $html .= "<td>$value</td>";
                }
                $html .= "</tr>";
            }
        }
        $html .= "</table>";
    }
    else
    {
        $html = header("Content-type: text/csv\r\n");
        $html .= header("Content-disposition-type: attachment\r\n");
        $html .= header("Content-disposition: filename={$filename}");

        foreach($data as $line)
        {
            if (!beginsWith($line, "\""))
            {
                    $line = "\"".str_replace(",", "\",\"",$line)."\"\r\n";
            }

            $html .= $line;
        }
    }

    return $html;
}


/**
* HTML for an alphabetical index of links
* @author Ivan Lucas
* @param string $baseurl start of a URL, the letter will be appended to this
* @returns HTML
*/
function alpha_index($baseurl = '#')
{
    global $i18nAlphabet;

    $html = '';
    if (!empty($i18nAlphabet))
    {
        $len = utf8_strlen($i18nAlphabet);
        for ($i = 0; $i < $len; $i++)
        {
            $html .= "<a href=\"{$baseurl}";
            $html .= urlencode(utf8_substr($i18nAlphabet, $i, 1))."\">";
            $html .= utf8_substr($i18nAlphabet, $i, 1)."</a> | \n";

        }
    }
    return $html;
}


/**
    * Converts emoticon text to HTML
    * @author Kieran Hogg
    * @param string $text. Text with smileys in it
    * @returns string HTML
*/
function emoticons($text)
{
    global $CONFIG;
    $smiley_url = "{$CONFIG['application_uriprefix']}{$CONFIG['application_webpath']}images/emoticons/";
    $smiley_regex = array(0 => "/\:[-]?\)/s",
                        1 => "/\:[-]?\(/s",
                        2 => "/\;[-]?\)/s",
                        3 => "/\:[-]?[pP]/s",
                        4 => "/\:[-]?@/s",
                        5 => "/\:[-]?[Oo]/s",
                        6 => "/\:[-]?\\$/s",
                        7 => "/\\([Yy]\)/s",
                        8 => "/\\([Nn]\)/s",
                        9 => "/\\([Bb]\)/s",
                        10 => "/\:[-]?[dD]/s"
                        );

    $smiley_replace = array(0 => "<img src='{$smiley_url}smile.png' alt='$1' title='$1' />",
                            1 => "<img src='{$smiley_url}sad.png' alt='$1' title='$1' />",
                            2 => "<img src='{$smiley_url}wink.png' alt='$1' title='$1' />",
                            3 => "<img src='{$smiley_url}tongue.png' alt='$1' title='$1' />",
                            4 => "<img src='{$smiley_url}angry.png' alt='$1' title='$1' />",
                            5 => "<img src='{$smiley_url}omg.png' alt='$1' title='$1' />",
                            6 => "<img src='{$smiley_url}embarassed.png' alt='$1' title='$1' />",
                            7 => "<img src='{$smiley_url}thumbs_up.png' alt='$1' title='$1' />",
                            8 => "<img src='{$smiley_url}thumbs_down.png' alt='$1' title='$1' />",
                            9 => "<img src='{$smiley_url}beer.png' alt='$1' title='$1' />",
                            10 => "<img src='{$smiley_url}teeth.png' alt='$1' title='$1' />"
                            );

    $html = preg_replace($smiley_regex, $smiley_replace, $text);
    return $html;
}


/**
 * Inserts a new incident update
 * @param int $incidentid ID of the incident to add the update to
 * @param string $text The text of the update
 * @param enum $type (Optional) Update type (Default: 'default'), types:
 * 'default', 'editing', 'opening', 'email', 'reassigning', 'closing',
 * 'reopening', 'auto', 'phonecallout', 'phonecallin', 'research', 'webupdate',
 * 'emailout', 'emailin', 'externalinfo', 'probdef', 'solution', 'actionplan',
 * 'slamet', 'reviewmet', 'tempassigning', 'auto_chase_email',
 * 'auto_chase_phone', 'auto_chase_manager', 'auto_chased_phone',
 * 'auto_chased_manager', 'auto_chase_managers_manager',
 * 'customerclosurerequest', 'fromtask'
 * @param string $sla The SLA the update meets
 * @param int $userid (Optional) ID of the user doing the updating (Default: 0)
 * @param int $currentowner (Optional) ID of the current incident owner
 * @param int $currentstatus (Optional) Current incident status (Default: 1 = active)
 * @param enum $visibility (Optional) Whether to 'show' or 'hide' in the portal (Default: 'show')
 * @author Kieran Hogg
 */
function new_update($incidentid, $text, $type = 'default', $sla = '', $userid = 0, $currentowner = '',
                    $currentstatus = 1, $visibility = 'show')
{
    global $now;
    $text = cleanvar($text);
    $sql  = "INSERT INTO `{$GLOBALS['dbUpdates']}` (incidentid, userid, ";
    $sql .= "type, bodytext, timestamp, currentowner, currentstatus, ";
    $sql .= "customervisibility, sla) VALUES ('{$incidentid}', '{$userid}', ";
    $sql .= "'{$type}', '{$text}', '{$now}', '{$currentowner}', ";
    $sql .= "'{$currentstatus}', '{$visibility}', '{$sla}')";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        return FALSE;
    }
    else
    {
        return mysql_insert_id();
    }
}


/**
 * Create a new holding queue item
 * @param int $updateid ID of the associated update entry
 * @param string $from Name of the from field
 * @param string $subject Subject of the item
 * @param string $emailfrom Email address the item is from
 * @param int $contactid (Optional) Contact ID of the sender
 * @param int $incidentid (Optional) Associated incident ID
 * @param int $locked (Optional) 1 if the item is locked, 0 if not
 * @param time $lockeduntil (Optional) MySQL timestamp of lock expiration
 * @param string $reason (Optional) Reason the item is in the holding queue
 * @param id $reason_user (Optional) The user ID who set the reason
 * @param time $reason_time (Optional) MySQL timestamp of when the reason was set
 * @author Kieran Hogg
 */
function create_temp_incoming($updateid, $from, $subject, $emailfrom,
                              $contactid = '', $incidentid = 0, $locked = '',
                              $lockeduntil = '', $reason = '',
                              $reason_user = '', $reason_time = '')
{
    global $dbTempIncoming;
    $sql = "INSERT INTO `{$dbTempIncoming}`(updateid, `from`, subject, ";
    $sql .= "emailfrom, contactid, incidentid, locked, lockeduntil, ";
    $sql .= "reason, reason_user, reason_time) VALUES('{$updateid}', ";
    $sql .= "'{$from}', '{$subject}', '{$emailfrom}', '{$contactid}', ";
    $sql .= "'{$incidentid}', '{$locked}', '{$lockeduntil}', '{$reason}', ";
    $sql .= "'{$reason_user}', '{$reason_time}')";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        return FALSE;
    }
    else
    {
        return mysql_insert_id();
    }
}



/**
 * Detect whether an array is associative
 * @param array $array
 * @note From http://uk.php.net/manual/en/function.is-array.php#77744
 */
function is_assoc($array)
{
    return is_array($array) && count($array) !== array_reduce(array_keys($array), 'is_assoc_callback', 0);
}


/**
 * Detect whether an array is associative
 * @param various $a
 * @param various $b
 * @note Callback function, Called by is_assoc()
         From http://uk.php.net/manual/en/function.is-array.php#77744
**/
function is_assoc_callback($a, $b)
{
    return $a === $b ? $a + 1 : 0;
}



/**
 * HTML for a config variable input box
 * @author Ivan Lucas
 * @param string $setupvar The setup variable key name
 * @param bool $showvarnames Whether to display the config variable name
 * @returns string HTML
**/
function cfgVarInput($setupvar, $showvarnames = FALSE)
{
    global $CONFIG, $CFGVAR;

    if ($CFGVAR[$setupvar]['type'] == 'languageselect'
        OR $CFGVAR[$setupvar]['type'] == 'languagemultiselect')
    {
        $available_languages = available_languages();
    }

    $html .= "<div class='configvar'>";
    if ($CFGVAR[$setupvar]['title']!='') $title = $CFGVAR[$setupvar]['title'];
    else $title = $setupvar;
    $html .= "<h4>{$title}</h4>";
    if ($CFGVAR[$setupvar]['help']!='') $html .= "<p class='helptip'>{$CFGVAR[$setupvar]['help']}</p>\n";

    $value = '';
    if (!$cfg_file_exists OR ($cfg_file_exists AND $cfg_file_writable))
    {
        $value = $CONFIG[$setupvar];
        if (is_bool($value))
        {
            if ($value==TRUE) $value='TRUE';
            else $value='FALSE';
        }
        elseif (is_array($value))
        {
            if (is_assoc($value))
            {
                $value = "array(".implode_assoc('=>',',',$value).")";
            }
            else
            {
                $value="array(".implode(',',$value).")";
            }
        }
        if ($setupvar=='db_password' AND $_REQUEST['action']!='reconfigure') $value='';
    }
    $value = stripslashes($value);
    switch ($CFGVAR[$setupvar]['type'])
    {
        case 'select':
            $html .= "<select name='{$setupvar}' id='{$setupvar}'>";
            if (empty($CFGVAR[$setupvar]['options'])) $CFGVAR[$setupvar]['options'] = "TRUE|FALSE";
            $options = explode('|', $CFGVAR[$setupvar]['options']);
            foreach ($options AS $option)
            {
                $html .= "<option value=\"{$option}\"";
                if ($option == $value) $html .= " selected='selected'";
                $html .= ">{$option}</option>\n";
            }
            $html .= "</select>";
        break;

        case 'percent':
            $html .= "<select name='{$setupvar}' id='{$setupvar}'>";
            for($i = 0; $i <= 100; $i++)
            {
                $html .= "<option value=\"{$i}\"";
                if ($i == $value) $html .= " selected='selected'";
                $html .= ">{$i}</option>\n";
            }
            $html .= "</select>%";
        break;

        case 'interfacestyleselect':
            $html .= interfacestyle_drop_down($setupvar, $value);
        break;

        case 'languageselect':
            if (empty($value)) $value = $_SESSION['lang'];
            $html .= array_drop_down($available_languages, $setupvar, $value, '', TRUE);
        break;

        case 'languagemultiselect':
            if (empty($value))
            {
                foreach ($available_languages AS $code => $lang)
                {
                    $value[] = $code;
                }
                $checked = TRUE;
            }
            else
            {
                $checked = FALSE;
                $replace = array('array(', ')', "'");
                $value = str_replace($replace, '',  $value);
                $value = explode(',', $value);
            }
            $html .= array_drop_down($available_languages, $setupvar, $value, '', TRUE, TRUE);
            $attributes = "onchange=\"toggle_multiselect('{$setupvar}[]')\"";
            $html .= "<label>".html_checkbox($setupvar.'checkbox', $checked, "");
            $html .= $GLOBALS['strAll']."</label>";
        break;

        case 'slaselect':
            $html .= serviceleveltag_drop_down($setupvar, $value, TRUE);
        break;

        case 'userselect':
            $html .= user_drop_down($setupvar, $value, FALSE, FALSE, '', TRUE);
        break;

        case 'siteselect':
            $html .= site_drop_down($setupvar, $value, FALSE);
        break;

        case 'userstatusselect':
            $html .= userstatus_drop_down($setupvar, $value);
        break;

        case 'roleselect':
            $html .= role_drop_down($setupvar, $value);
        break;

        case 'number':
            $html .= "<input type='text' name='{$setupvar}' id='{$setupvar}' size='7' value=\"{$value}\" />";
        break;

        case '1darray':
            $replace = array('array(', ')', "'");
            $value = str_replace($replace, '',  $value);
            $html .= "<input type='text' name='{$setupvar}' id='{$setupvar}' size='60' value=\"{$value}\" />";
        break;

        case '2darray':
            $replace = array('array(', ')', "'", '\r','\n');
            $value = str_replace($replace, '',  $value);
            $value = str_replace(',', "\n", $value);
            $html .= "<textarea name='{$setupvar}' id='{$setupvar}' cols='60' rows='10'>{$value}</textarea>";
        break;

        case 'password':
          $html .= "<input type='password' id='cfg{$setupvar}' name='{$setupvar}' size='16' value=\"{$value}\" /> ".password_reveal_link("cfg{$setupvar}");
        break;

        case 'ldappassword':
          $html .= "<input type='password' id='cfg{$setupvar}' name='{$setupvar}' size='16' value=\"{$value}\" /> ".password_reveal_link("cfg{$setupvar}");
          $html.= " &nbsp; <a href='javascript:void(0);' onclick=\"checkLDAPDetails('status{$setupvar}');\">{$GLOBALS['strCheckLDAPDetails']}</a>";
        break;


        case 'text':
        default:
            if (strlen($CONFIG[$setupvar]) < 65)
            {
                $html .= "<input type='text' name='{$setupvar}' id='{$setupvar}'  size='60' value=\"{$value}\" />";
            }
            else
            {
                $html .= "<textarea name='{$setupvar}' id='{$setupvar}' cols='60' rows='10'>{$value}</textarea>";
            }
    }
    if (!empty($CFGVAR[$setupvar]['unit'])) $html .= " {$CFGVAR[$setupvar]['unit']}";
    if (!empty($CFGVAR[$setupvar]['helplink'])) $html .= ' '.help_link($CFGVAR[$setupvar]['helplink']);
    if ($setupvar == 'db_password' AND $_REQUEST['action'] != 'reconfigure' AND $value != '')
    {
        $html .= "<p class='info'>The current password setting is not shown</p>";
    }

    if ($showvarnames) $html .= "<br />(<var>\$CONFIG['$setupvar']</var>)";

    if ($CFGVAR[$setupvar]['statusfield'] == 'TRUE')
    {
        $html .= "<div id='status{$setupvar}'></div>";
    }

    $html .= "</div>";
    $html .= "<br />\n";
    if ($c == 1) $c == 2;
    else $c = 1;

    return $html;
}


/**
 * Save configuration
 * @param array $setupvars. An array of setup variables $setupvars['setting'] = 'foo';
 * @todo  TODO, need to make setup.php use this  INL 5Dec08
 * @author Ivan Lucas
**/
function cfgSave($setupvars)
{
    global $dbConfig;
    foreach ($setupvars AS $key => $value)
    {
        $sql = "REPLACE INTO `{$dbConfig}` (`config`, `value`) VALUES ('{$key}', '{$value}')";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(). "  $sql",E_USER_WARNING);
    }
    return TRUE;
}


/**
 * HTML for a hyperlink to hide/reveal a password field
 * @author Ivan Lucas
**/
function password_reveal_link($id)
{
    $html = "<a href=\"javascript:password_reveal('$id')\" id=\"link{$id}\">{$GLOBALS['strReveal']}</a>";
    return $html;
}


function holding_email_update_id($holding_email)
{
    $holding_email = intval($holding_email);
    return db_read_column('updateid', $GLOBALS['dbTempIncoming'], $holding_email);
}


function delete_holding_queue_update($updateid)
{
    $sql = "DELETE FROM {$GLOBALS['dbTempIncoming']} WHERE updateid = '{$updateid}'";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(). "  $sql",E_USER_WARNING);
        return FALSE;
    }
    else
    {
        return TRUE;
    }
}


function num_unread_emails()
{
    global $dbTempIncoming;
    $sql = "SELECT COUNT(*) AS count FROM `{$dbTempIncoming}`";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(). "  $sql",E_USER_WARNING);
    list($count) = mysql_fetch_row($result);
    return $count;
}


/**
 * Checks whether an KB article exists and/or the user is allowed to view is
 * @author Kieran Hogg
 * @param $id int ID of the KB article
 * @param $mode string 'public' for portal users, 'private' for internal users
 * @return bool Whether we are allowed to see it or not
*/
function is_kb_article($id, $mode)
{
    $rtn = FALSE;
    global $dbKBArticles;
    $id = cleanvar($id);
    if ($id > 0)
    {
        $sql = "SELECT distribution FROM `{$dbKBArticles}` ";
        $sql .= "WHERE docid = '{$id}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(). "  $sql",E_USER_WARNING);
        list($visibility) = mysql_fetch_row($result);
        if ($visibility == 'public' && $mode == 'public')
        {
            $rtn = TRUE;
        }
        else if (($visibility == 'private' OR $visibility == 'restricted') AND
                 $mode == 'private')
        {
            $rtn = TRUE;
        }
    }
    return $rtn;
}


/**
 * Generates a feedback form hash
 * @author Kieran Hogg
 * @param $formid int ID of the form to use
 * @param $contactid int ID of the contact to send it to
 * @param $incidentid int ID of the incident the feedback is about
 * @return string the hash
*/
function feedback_hash($formid, $contactid, $incidentid)
{
    $hashtext = urlencode($formid)."&&".urlencode($contactid)."&&".urlencode($incidentid);
    $hashcode4 = str_rot13($hashtext);
    $hashcode3 = gzcompress($hashcode4);
    $hashcode2 = base64_encode($hashcode3);
    $hashcode1 = trim($hashcode2);
    $hashcode = urlencode($hashcode1);
    return $hashcode;
}


// ** Place no more function defs below this **


// These are the modules that we are dependent on, without these something
// or everything will fail, so let's throw an error here.
// Check that the correct modules are loaded
if (!extension_loaded('mysql')) trigger_error('SiT requires the php/mysql module', E_USER_ERROR);
if (!extension_loaded('imap') AND $CONFIG['enable_inbound_mail'] == 'POP/IMAP')
{
    trigger_error('SiT requires the php IMAP module to recieve incoming mail.'
                .' If you really don\'t need this, you can set $CONFIG[\'enable_inbound_mail\'] to false');
}
if (version_compare(PHP_VERSION, "5.0.0", "<")) trigger_error('INFO: You are running an older PHP version, some features may not work properly.', E_USER_NOTICE);
if (@ini_get('register_globals') == 1 OR strtolower(@ini_get('register_globals')) == 'on')
{
    trigger_error('Error: php.ini MUST have register_globals set to off, there are potential security risks involved with leaving it as it is!', E_USER_ERROR);
    die('Stopping SiT now, fix your php and try again.');
}

?>
