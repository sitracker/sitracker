<?php
//
// functions.inc.php - Function library and defines for SiT -Support Incident Tracker
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
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
include (APPLICATION_LIBPATH . 'status.class.php');

include_once (APPLICATION_LIBPATH . 'file.inc.php');
include (APPLICATION_LIBPATH . 'ldap.inc.php');
include (APPLICATION_LIBPATH . 'base.inc.php');
include_once (APPLICATION_LIBPATH . 'array.inc.php');
include_once (APPLICATION_LIBPATH . 'datetime.inc.php');
include_once (APPLICATION_LIBPATH . 'billing.inc.php');
include_once (APPLICATION_LIBPATH . 'user.inc.php');
include_once (APPLICATION_LIBPATH . 'sla.inc.php');
include_once (APPLICATION_LIBPATH . 'ftp.inc.php');
include_once (APPLICATION_LIBPATH . 'tags.inc.php');
include_once (APPLICATION_LIBPATH . 'string.inc.php');
include_once (APPLICATION_LIBPATH . 'html_drop_downs.inc.php');
include_once (APPLICATION_LIBPATH . 'html.inc.php');
include_once (APPLICATION_LIBPATH . 'incident_html.inc.php');
include_once (APPLICATION_LIBPATH . 'tasks.inc.php');
include_once (APPLICATION_LIBPATH . 'export.inc.php');
include_once (APPLICATION_LIBPATH . 'contact.inc.php');
include_once (APPLICATION_LIBPATH . 'contract.inc.php');
include_once (APPLICATION_LIBPATH . 'journal.inc.php');
include_once (APPLICATION_LIBPATH . 'kb.inc.php');
include_once (APPLICATION_LIBPATH . 'feedback.inc.php');
include_once (APPLICATION_LIBPATH . 'site.inc.php');
include_once (APPLICATION_LIBPATH . 'configfuncs.inc.php');
include_once (APPLICATION_LIBPATH . 'incident.inc.php');

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
	            $toReturn =  authenticateLDAP(stripslashes($username), $password, $obj->id);
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
    $sql = "SELECT `{$column}` FROM `{$table}` WHERE id ='$id' LIMIT 1";
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
 * @return string. Skill/Software Name
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


/* Returns a string representing the name of   */
/* the given product. Returns an empty string if the product  */
/* does not exist.                                            */
function product_name($id)
{
    return db_read_column('name', $GLOBALS['dbProducts'], $id);
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
 */
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
                if ($errno != E_USER_NOTICE)
                {
                    echo "{$errstr} in {$errfile} @ line {$errline}";
                }
                else
                {
                    echo "{$errstr}";
                }
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
                $logentry .= "\n[CONTEXT-BEGIN]\n".print_r($errcontext, TRUE)."\n[CONTEXT-END]\n----------\n\n";
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
    if ($debugmodeonly === FALSE
        OR ($debugmodeonly === TRUE AND $CONFIG['debug'] == TRUE))
    {
        $logentry = $_SERVER["SCRIPT_NAME"] . ' ' .$logentry;

        if (substr($logentry, -1) != "\n") $logentry .= "\n";
        if (!empty($CONFIG['error_logfile']))
        {
            if (file_exists($CONFIG['error_logfile']))
            {
                if (is_writable($CONFIG['error_logfile']))
                {
                    $fp = fopen($CONFIG['error_logfile'], 'a+');
                    if ($fp)
                    {
                        fwrite($fp, date('c').' '.$logentry);
                        fclose($fp);
                    }
                    else
                    {
                        echo "<p class='error'>Could not log message to error_logfile</p>";
                        trigger_error("Could not log message to error_logfile", E_USER_NOTICE);
                        return FALSE;
                    }
                    return TRUE;
                }
                else
                {
                    trigger_error("Debug log file (error_logfile) [{$CONFIG['error_logfile']}] not writable", E_USER_WARNING);
                }
            }
            else
            {
                trigger_error("Debug log file (error_logfile) [{$CONFIG['error_logfile']}] not found", E_USER_WARNING);
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
 * Send an email from SiT
 * @param string $to. Destination email address
 * @param string $from. Source email address
 * @param string $subject. Email subject line
 * @param string $body. Email body text
 * @param string $replyto. (optional) Address to send reply to
 * @param string $cc. (optional) Carbon copy address
 * @param string $bcc. (optional) Blind carbon copy address
 * @return The return value from PHP mail() function or TRUE when in Demo mode
 * @note Returns TRUE but does not actually send mail when SiT is in Demo mode
 */
function send_email($to, $from, $subject, $body, $replyto='', $cc='', $bcc='')
{
    global $CONFIG, $application_version_string;

    if ($CONFIG['outbound_email_newline'] == 'CRLF')
    {
        $crlf = "\r\n";
    }
    else
    {
        $crlf = "\n";
    }

    if (empty($to)) trigger_error('Empty TO address in email', E_USER_WARNING);

    $extra_headers  = '';
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
//     $extra_headers .= "\r\n";

    if ($CONFIG['demo'])
    {
        $rtnvalue = TRUE;
    }
    elseif ($CONFIG['enable_outbound_email'] == false)
    {
        $rtnvalue = TRUE;
        debug_log("Outgoing email disabled, no mail is sent");
    }
    else
    {
        // $rtnvalue = mail($to, $subject, $body, $extra_headers);

        $mime = new MIME_mail($from, $to, html_entity_decode($subject), '', $extra_headers, $mailerror);
        $mime -> attach($body, '', "text/plain; charset={$GLOBALS['i18ncharset']}", $CONFIG['outbound_email_encoding'], 'inline');

        // actually send the email
        $rtnvalue = $mime -> send_mail();
        if (!empty($mailerror)) debug_log("Outoing email error: {$mailerror}");
    }

    return $rtnvalue;
}


function global_signature()
{
    $sql = "SELECT signature FROM `{$GLOBALS['dbEmailSig']}` ORDER BY RAND() LIMIT 1";
    $result = mysql_query($sql);
    list($signature) = mysql_fetch_row($result);
    mysql_free_result($result);
    return $signature;
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
 * Checks to see if a dashlet is installed
 * @author Paul Heaney
 * @param String $dashlet The name of the dashlet
 * @return boolean True if installed, false otherwise
 */
function is_dashlet_installed($dashlet)
{
    $sql = "SELECT id FROM `{$GLOBALS['dbDashboard']}` WHERE name = '{$dashlet}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) == 1)
    {
        return TRUE;
    }
    else
    {
        return FALSE;
    }
}


/**
 * Shows errors from a form, if any
 * @author Kieran Hogg
 * @return string. HTML of the form errors stored in the users session
 */
function show_form_errors($formname)
{
    if ($_SESSION['formerrors'][$formname])
    {
        foreach ($_SESSION['formerrors'][$formname] as $error)
        {

            if (mb_substr(trim($error), 0, 1) != "<")
            {
                $html .= user_alert($error, E_USER_ERROR);
            }
            else
            {
                $html .= $error;
            }
        }
    }
    return $html;
}


/**
 * Cleans form errors
 * @author Kieran Hogg
 * @return nothing
 */
function clear_form_errors($formname)
{
    unset($_SESSION['formerrors'][$formname]);
}


/**
 * Cleans form data
 * @author Kieran Hogg
 * @return nothing
 */
function clear_form_data($formname)
{
    unset($_SESSION['formdata'][$formname]);
}


/**
 * Finds out which scheduled tasks should be run right now
 * Ensures that a task cannot start until the previous iteration has completed
 * @author Ivan Lucas, Paul Heaney
 * @return array
 */
function schedule_actions_due()
{
    global $now;
    global $dbScheduler;

    $actions = FALSE;
    $sql = "SELECT * FROM `{$dbScheduler}` WHERE `status` = 'enabled' AND type = 'interval' ";
    $sql .= "AND UNIX_TIMESTAMP(start) <= $now AND (UNIX_TIMESTAMP(end) >= $now OR UNIX_TIMESTAMP(end) = 0) ";
    $sql .= "AND IF(UNIX_TIMESTAMP(lastran) > 0, UNIX_TIMESTAMP(lastran) + `interval`, UNIX_TIMESTAMP(NOW())) <= $now ";
    $sql .= "AND IF(UNIX_TIMESTAMP(laststarted) > 0, UNIX_TIMESTAMP(lastran), -1) <= IF(UNIX_TIMESTAMP(laststarted) > 0, UNIX_TIMESTAMP(laststarted), 0)";
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
    $sql .= "AND IF(UNIX_TIMESTAMP(lastran) > 0, UNIX_TIMESTAMP(lastran) + `interval`, UNIX_TIMESTAMP(NOW())) <= $now ";
    $sql .= "AND IF(UNIX_TIMESTAMP(laststarted) > 0, UNIX_TIMESTAMP(lastran), -1) <= IF(UNIX_TIMESTAMP(laststarted) > 0, UNIX_TIMESTAMP(laststarted), 0)";
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
    $sql .= "AND IF(UNIX_TIMESTAMP(lastran) > 0, UNIX_TIMESTAMP(lastran) + `interval`, UNIX_TIMESTAMP(NOW())) <= $now ";
    $sql .= "AND IF(UNIX_TIMESTAMP(laststarted) > 0, UNIX_TIMESTAMP(lastran), -1) <= IF(UNIX_TIMESTAMP(laststarted) > 0, UNIX_TIMESTAMP(laststarted), 0)";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        while ($action = mysql_fetch_object($result))
        {
            $actions[$action->action] = $actions->params;
        }
    }

    if (is_array($actions)) debug_log('Scheduler actions due: '.implode(', ',array_keys($actions)));

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
        $t = new TriggerEvent('TRIGGER_SCHEDULER_TASK_FAILED', array('schedulertask' => $doneaction));
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
 * Populates $_SESSION['syslang], system language strings
 *
 * @author Kieran Hogg
 * @sa See also populate_syslang2() which is a copy of this function
 */
function populate_syslang()
{
    global $CONFIG;

    // Populate $SYSLANG with first the native lang and then the system lang
    // This is so that we have a complete language file
    $nativefile = APPLICATION_I18NPATH . "en-GB.inc.php";
    $file = APPLICATION_I18NPATH . "{$CONFIG['default_i18n']}.inc.php";

    if (file_exists($nativefile))
    {
        $fh = fopen($nativefile, "r");

        $theData = fread($fh, filesize($nativefile));
        fclose($fh);
        $nativelines = explode("\n", $theData);

        if (file_exists($file))
        {
            $fh = fopen($file, "r");
            $theData = fread($fh, filesize($file));
            fclose($fh);
            $lines = explode("\n", $theData);
        }
        else
        {
            trigger_error("Language file specified in \$CONFIG['default_i18n'] can't be found", E_USER_ERROR);
            $lines = $nativelines;
        }

       foreach ($nativelines as $values)
        {
            $badchars = array("$", "\"", "\\", "<?php", "?>");
            $values = trim(str_replace($badchars, '', $values));
            if (mb_substr($values, 0, 3) == "str")
            {
                $vars = explode("=", $values);
                $vars[0] = trim($vars[0]);
                $vars[1] = trim(substr_replace($vars[1], "",-2));
                $vars[1] = substr_replace($vars[1], "",0, 1);
                $SYSLANG[$vars[0]] = $vars[1];
            }
        }
        foreach ($lines as $values)
        {
            $badchars = array("$", "\"", "\\", "<?php", "?>");
            $values = trim(str_replace($badchars, '', $values));
            if (mb_substr($values, 0, 3) == "str")
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
        trigger_error("Native language file 'en-GB' can't be found", E_USER_ERROR);
    }
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
    $data = explode("\n", $data);
    if ($output == 'table')
    {
        $html = "\n<table align='center'><tr>\n";
        $headers = explode(',', $data[0]);
        $rows = sizeof($headers);
        foreach ($headers as $header)
        {
            $html .= colheader($header, $header);
        }
        $html .= "</tr>";

        if (sizeof($data) == 1)
        {
            $html .= "<tr><td rowspan='{$rows}'>" . user_alert($GLOBALS['strNoRecords'], E_USER_NOTICE) . "</td></tr>";
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

        foreach ($data as $line)
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
 * Function to add a additional charting library to SiT!
 * Please use this function rather than manually adjusting the array, prevents
 * plugins accidentally corrupting the array
 * @param String $library The library to add
 * @author Paul Heaney
 */
function add_charting_library($library)
{
    global $CONFIG;

    $CONFIG['available_charts'][] = $library;
}


/**
 * Checks the environment for SiT requirements
 * @author Paul Heaney
 * @return Status The status of SiT
 */
function check_install_status()
{
    $s = new Status();
    $s->mysql_check();
    $s->add_extension_check('mysql', 'PHP MySQL Driver', INSTALL_FATAL);
    $s->add_extension_check('mbstring', 'PHP Multibyte', INSTALL_FATAL);
    $s->add_extension_check('ldap', 'PHP LDAP', INSTALL_WARN);
    $s->add_extension_check('imap', 'PHP IMAP', INSTALL_WARN);
    $s->add_extension_check('zlib', 'PHP Zlib Compression', INSTALL_FATAL);
    $s->add_extension_check('session', 'PHP Session', INSTALL_FATAL);
    $s->add_extension_check('pcre', 'PHP Regular Expression', INSTALL_FATAL);

    return $s;
}


/**
 * Makes a string suitable for database insertion where NUll is possible.
 * It checks to see if the string is empty if so returns Null else the string enclosed in quotes
 * @author Paul Heaney
 * @param String $string the string to be checked
 * @return String either "Null" or "'$string'"
 */
function convert_string_null_safe($string)
{
    if ($string == '') return 'Null';
    else return "'{$string}'";
}

// -------------------------- // -------------------------- // --------------------------
// leave this section at the bottom of functions.inc.php ================================

// Evaluate and Load plugins
if (is_array($CONFIG['plugins']))
{
    foreach ($CONFIG['plugins'] AS $plugin)
    {
        $plugin = trim($plugin);
        // Remove any dots
        $plugin = str_replace('.','',$plugin);
        // Remove any slashes
        $plugin = str_replace('/','',$plugin);

        $plugini18npath = APPLICATION_PLUGINPATH . "{$plugin}". DIRECTORY_SEPARATOR . "i18n". DIRECTORY_SEPARATOR;
        $pluginfilename = APPLICATION_PLUGINPATH . $plugin . DIRECTORY_SEPARATOR . "{$plugin}.php";
        if ($plugin != '')
        {
            if (file_exists($pluginfilename))
            {
                include ($pluginfilename);
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

    // Make global variables available to plugins, careful not overwrite vars
    // used in plugin_do function scope (Mantis 1433)
    foreach ($GLOBALS as $key => $val)
    {
        if ($key != 'context' AND $key != 'optparams' AND $key != 'PLUGINACTIONS')
        {
            global $$key;
        }
    }
    $rtnvalue = '';
    if (is_array($PLUGINACTIONS[$context]))
    {
        foreach ($PLUGINACTIONS[$context] AS $pluginaction)
        {
            // Call Variable function (function with variable name)
            if ($optparams)
            {
                $rtn = $pluginaction($optparams);
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


// ** Place no more function defs below this **


// These are the modules that we are dependent on, without these something
// or everything will fail, so let's throw an error here.
// Check that the correct modules are loaded
if (!extension_loaded('mysql')) trigger_error('SiT requires the php/mysql module', E_USER_ERROR);
if (!extension_loaded('imap') AND $CONFIG['enable_inbound_mail'] == 'POP/IMAP')
{
    trigger_error('SiT requires the php IMAP extension to recieve incoming mail (even for POP or MTA methods!).'
                .' If you don\'t use incoming email you can set $CONFIG[\'enable_inbound_mail\'] to false', E_USER_NOTICE);
}
if (version_compare(PHP_VERSION, "5.0.0", "<")) trigger_error('INFO: You are running an older PHP version, some features may not work properly.', E_USER_NOTICE);
if (@ini_get('register_globals') == 1 OR strtolower(@ini_get('register_globals')) == 'on')
{
    trigger_error('Error: php.ini MUST have register_globals set to off, there are potential security risks involved with leaving it as it is!', E_USER_ERROR);
    die('Stopping SiT now, fix your php and try again.');
}

?>