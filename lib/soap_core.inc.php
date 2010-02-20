<?php
// soap_core.inc.php - Core SOAP functions
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Paul Heaney <paul[at]sitracker.org>

include (APPLICATION_LIBPATH . 'soap_types.inc.php');

$server->register('login',
        array('username' => 'xsd:string', 'password' => 'xsd:string'), // Input
        array('return'  => 'tns:login_response'), // return
        $soap_namespace);


/**
 * Log the user into SiT!
 * @author Paul Heaney
 * @param string $username - The users username
 * @param string $password - The users Password
 * @param string $applicationname (Optional)  an optional name for the application 
 * @return Array - array of session ID and error
 */
function login($username, $password, $applicationname='noname')
{
    global $CONFIG;
    $auth_result = authenticate($username, $password);
    $status = new SoapStatus();
    $sessionid = '';
    if ($auth_result)
    {
        // Do setup here
        session_name($CONFIG['session_name']);
        session_start();
        $sessionid = session_id();
        
        // FIXME TODO all this was copied from login.php this probably wants making into a function
        
        $_SESSION['auth'] = TRUE;
        
        // Retrieve users profile
        $sql = "SELECT * FROM `{$GLOBALS['dbUsers']}` WHERE username='{$username}' AND password=MD5('{$password}') LIMIT 1";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) < 1)
        {
            $_SESSION['auth'] = FALSE;
            trigger_error("No such user", E_USER_ERROR);
        }

        $user = mysql_fetch_object($result);
        // Profile
        $_SESSION['userid'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['realname'] = $user->realname;
        $_SESSION['email'] = $user->email;
        $_SESSION['style'] = $user->var_style;
        $_SESSION['incident_refresh'] = $user->var_incident_refresh;
        $_SESSION['update_order'] = $user->var_update_order;
        $_SESSION['num_update_view'] = $user->var_num_updates_view;
        $_SESSION['groupid'] = is_null($user->groupid) ? 0 : $user->groupid;
        $_SESSION['utcoffset'] = is_null($user->var_utc_offset) ? 0 : $user->var_utc_offset;
        $_SESSION['portalauth'] = FALSE;
        $_SESSION['soapmode'] = TRUE;
        $_SESSION['applicationame'] = $applicationname;
        if (!is_null($_SESSION['startdate'])) $_SESSION['startdate'] = $user->user_startdate;
    
    
        // Read user config from database
        $sql = "SELECT * FROM `{$GLOBALS['dbUserConfig']}` WHERE userid = {$user->id}";
        $result = @mysql_query($sql);
        if ($result AND mysql_num_rows($result) > 0)
        {
            while ($conf = mysql_fetch_object($result))
            {
                if ($conf->value==='TRUE') $conf->value = TRUE;
                if ($conf->value==='FALSE') $conf->value = FALSE;
                if (substr($conf->value, 0, 6)=='array(')
                {
                        eval("\$val = {$conf->value};");
                        $conf->value = $val;
                }
                $_SESSION['userconfig'][$conf->config] = $conf->value;
            }
        }
        
        
        if ($user->var_i18n != $CONFIG['default_i18n'] AND $_SESSION['lang'] == '')
        {
            $_SESSION['lang'] = is_null($user->var_i18n) ? '' : $user->var_i18n;
        }
    
        // Make an array full of users permissions
        // The zero permission is added to all users, zero means everybody can access
        $userpermissions[] = 0;
        // First lookup the role permissions
        $sql = "SELECT * FROM `{$GLOBALS['dbUsers']}` AS u, `{$GLOBALS['dbRolePermissions']}` AS rp WHERE u.roleid = rp.roleid ";
        $sql .= "AND u.id = '{$_SESSION['userid']}' AND granted='true'";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            $_SESSION['auth'] = FALSE;
            trigger_error(mysql_error(), E_USER_ERROR);
        }
        if (mysql_num_rows($result) >= 1)
        {
            while ($perm = mysql_fetch_object($result))
            {
                $userpermissions[] = $perm->permissionid;
            }
        }
    
        // Next lookup the individual users permissions
        $sql = "SELECT * FROM `{$GLOBALS['dbUserPermissions']}` WHERE userid = '{$_SESSION['userid']}' AND granted='true' ";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            $_SESSION['auth'] = FALSE;
            trigger_error(mysql_error(),E_USER_ERROR);
        }
    
        if (mysql_num_rows($result) >= 1)
        {
            while ($perm = mysql_fetch_object($result))
            {
                $userpermissions[] = $perm->permissionid;
            }
        }
    
    
        $_SESSION['permissions'] = array_unique($userpermissions);
    }
    else
    {
    	$status->set_error('login_failed');
    }

    return array('sessionid' => $sessionid, 'status' => $status->getSOAPArray());;
}


$server->register('logout',
        array('sessionid' => 'xsd:string'), // Input
        array('return'  => 'tns:logout_response'), // return
        $soap_namespace);


/**
 * Logs a user out of SiT
 * 
 * @author Paul Heaney
 * @param string $sessionid - The session ID to log out of
 * @return Array - Status
 */
function logout($sessionid)
{
    $status = new SoapStatus();
	if (validate_session($sessionid))
    {
        session_id($sessionid);
        session_start();
    	// End the session, remove the cookie and destroy all data registered with the session
        $_SESSION['auth'] = FALSE;
        $_SESSION['portalauth'] = FALSE;
        $_SESSION = array();
        
        session_unset();
        session_destroy();
        
        if (isset($_COOKIE[session_name()]))
        {
           setcookie(session_name(), '', time()-42000, '/');
        }
    }
    else
    {
    	$status->set_error('session_not_valid');
    }
    
    return array('status' => $status->getSOAPArray());
}


/**
 * Function to ensure a session is still valid
 * 
 * @author Paul Heaney
 * @param string $sessionid - The session ID to check
 * @return bool TRUE or FALSE depending on whether its valid
 */
function validate_session($sessionid)
{
    $isvalid = FALSE;
	if (!empty ($sessionid))
    {
    	session_id($sessionid);
        session_start();
        
        if (!empty($_SESSION['auth']))
        {
        	$isvalid = TRUE;
        }
    }
    return $isvalid;
}

?>
