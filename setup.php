<?php
// setup.php - Install/Upgrade and set up plugins
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// Define path constants, we don't include core.php so we do this here
define ('APPLICATION_FSPATH', realpath(dirname( __FILE__ ) . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
define ('APPLICATION_LIBPATH', realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR . 'lib') . DIRECTORY_SEPARATOR);
define ('APPLICATION_HELPPATH', realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR . 'help') . DIRECTORY_SEPARATOR);
define ('APPLICATION_INCPATH', realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR . 'inc') . DIRECTORY_SEPARATOR);
define ('APPLICATION_I18NPATH', realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR . 'i18n') . DIRECTORY_SEPARATOR);
define ('APPLICATION_PORTALPATH', realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR . 'portal') . DIRECTORY_SEPARATOR);
define ('APPLICATION_PLUGINPATH', realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR . 'plugins') . DIRECTORY_SEPARATOR);


// Load config defaults
@include (APPLICATION_LIBPATH . 'defaults.inc.php');
// Keep the defaults as a seperate array
$DEFAULTS = $CONFIG;

// Load config file with customisations
// @include ("config.inc-dist.php");
if (file_exists(APPLICATION_FSPATH . "config.inc.php")
    AND !include (APPLICATION_FSPATH . "config.inc.php"))
    {
        die('Could not read config file config.inc.php');
    }
// Server Configuration

if (file_exists('/etc/sit.conf')
    AND !include ('/etc/sit.conf'))
    {
        die('Cound not read config file sit.conf');
    }

// // Some actions require authentication
// if ($_REQUEST['action'] == 'reconfigure')
// {
//     $permission = 22;
//     $_REQUEST['config'] = 'advanced'; // set advanced mode
//     require (APPLICATION_LIBPATH . 'functions.inc.php');
//     require (APPLICATION_LIBPATH . 'auth.inc.php');
// }

// These are the required variables we want to configure during installation
$SETUP = array('db_hostname','db_database','db_username','db_password', 'db_tableprefix','application_webpath');

require(APPLICATION_LIBPATH . 'configvars.inc.php');

$upgradeok = FALSE;
$config_filename = APPLICATION_FSPATH . 'config.inc.php';

$configfiles = get_included_files();

$systemhash = md5(date('Y-m-d') . $_SERVER['REMOTE_ADDR']
                . $_SERVER['SCRIPT_FILENAME'] . $_SERVER['HTTP_USER_AGENT']
                . $CONFIG['attachment_fspath'] . $_SERVER['SERVER_SIGNATURE'] );

/**
    * Array filter callback to check to see if a config file is a recognised file
    * @author Ivan Lucas
    * @param string $var. Filename to check
    * @retval bool TRUE : recognised
    * @retval bool FALSE : unrecognised
*/
function filterconfigfiles($var)
{
    $poss_config_files = array('config.inc.php', 'sit.conf');
    $recognised = FALSE;
    foreach ($poss_config_files AS $poss)
    {
        if (substr($var, strlen($var)-strlen($poss)) == $poss) $recognised = TRUE;
    }
    return $recognised;
}


$configfiles = array_filter($configfiles, 'filterconfigfiles');
$configfiles = array_values($configfiles);
$numconfigfiles = count($configfiles);
if ($numconfigfiles == 1)
{
    $config_filename = $configfiles[0];
}
elseif ($numconfigfiles < 1)
{
    $configfiles[] = './config.inc.php';
}

$cfg_file_exists = FALSE;
$cfg_file_writable = FALSE;
foreach ($configfiles AS $conf_filename)
{
    if (file_exists($conf_filename)) $cfg_file_exists = TRUE;
    if (is_writable($conf_filename)) $cfg_file_writable = TRUE;
}


// Detect whether an array is associative
// From http://uk.php.net/manual/en/function.is-array.php#77744
//function is_assoc($array)
//{
//    return is_array($array) && count($array) !== array_reduce(array_keys($array), 'is_assoc_callback', 0);
//}


//function is_assoc_callback($a, $b)
//{
//    return $a === $b ? $a + 1 : 0;
//}

/**
    * Setup configuration form
    * @author Ivan Lucas
    * @retval string HTML
*/
function setup_configure()
{
    global $SETUP, $CFGVAR, $CONFIG, $configfiles, $config_filename, $cfg_file_exists;
    global $cfg_file_writable, $numconfigfiles;
    $html = '';

    if ($cfg_file_exists AND $_REQUEST['configfile'] != 'new')
    {
        if ($_SESSION['new'])
        {
            if ($numconfigfiles < 2)
            {
                $html .= "<h4>Found an existing config file <var>{$config_filename}</var></h4>";
            }
            else
            {
                $html .= "<p class='error'>Found more than one existing config file</p>";
                if ($cfg_file_writable)
                {
                    $html .= "<ul>";
                    foreach ($configfiles AS $conf_filename)
                    {
                        $html .= "<li><var>{$conf_filename}</var></li>";
                    }
                    $html .= "</ul>";
                }
            }
        }
        //$html .= "<p>Since you already have a config file we assume you are upgrading or reconfiguring, if this is not the case please delete the existing config file.</p>";
        if ($cfg_file_writable)
        {
            $html .= "<p class='error'>Important: The file permissions on the configuration file ";
            $html .= "allow it to be modified, we recommend you make this file read-only once SiT! is configured.";
            $html .= "</p>";
        }
        else
        {
            $html .= "<p><a href='setup.php?action=reconfigure&amp;configfile=new' >Create a new config file</a>.</p>";
        }
    }
    else $html .= "<h2>New Configuration</h2><p>Please complete this form to create a new configuration file for SiT!</p>";

    if ($cfg_file_writable OR $_SESSION['new'] === 1 OR $cfg_file_exists == FALSE OR $_REQUEST['configfile'] == 'new')
    {
        $html .= "\n<form action='setup.php' method='post'>\n";

        if ($_REQUEST['config'] == 'advanced')
        {
            $html .= "<input type='hidden' name='config' value='advanced' />\n";
            foreach ($CFGVAR AS $setupvar => $setupval)
            {
                $SETUP[] = $setupvar;
            }
        }

        $c=1;
        foreach ($SETUP AS $setupvar)
        {
            $html .= "<div class='configvar{$c}'>";
            if ($CFGVAR[$setupvar]['title']!='') $title = $CFGVAR[$setupvar]['title'];
            else $title = $setupvar;
            $html .= "<h4>{$title}</h4>";
            if ($CFGVAR[$setupvar]['help']!='') $html .= "<p class='helptip'>{$CFGVAR[$setupvar]['help']}</p>\n";

            $html .= "<var>\$CONFIG['$setupvar']</var> = ";

            $value = '';
            if (!$cfg_file_exists OR ($cfg_file_exists AND $cfg_file_writable))
            {
                $value = $CONFIG[$setupvar];
                if (is_bool($value))
                {
                    if ($value == TRUE) $value = 'TRUE';
                    else $value = 'FALSE';
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
                if ($setupvar == 'db_password' AND $_REQUEST['action'] != 'reconfigure') $value = '';
            }

            if (!$cfg_file_exists OR $_REQUEST['configfile'] == 'new')
            {
                // Dynamic defaults
                    // application_fspath was removed, leaving this code just-in-case
                    // DEPRECATED - remove for >= 3.50
                if ($setupvar == 'application_fspath')
                {
                    $value = str_replace('htdocs' . DIRECTORY_SEPARATOR, '', dirname( __FILE__ ) . DIRECTORY_SEPARATOR);
                }

                if ($setupvar == 'application_webpath')
                {
                    $value = dirname( strip_tags( $_SERVER['PHP_SELF'] ) );
                    if ($value == '/' OR $value == '\\') $value = '/';
                    else $value = $value . '/';
                }
            }

            switch ($CFGVAR[$setupvar]['type'])
            {
                case 'select':
                    $html .= "<select name='$setupvar'>";
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
                    $html .= "<select name='$setupvar'>";
                    for($i = 0; $i <= 100; $i++)
                    {
                        $html .= "<option value=\"{$i}\"";
                        if ($i == $value) $html .= " selected='selected'";
                        $html .= ">{$i}</option>\n";
                    }
                    $html .= "</select>";
                    break;
                case 'text':
                default:
                    if (strlen($CONFIG[$setupvar]) < 65)
                    {
                        $html .= "<input type='text' name='$setupvar' size='60' value=\"{$value}\" />";
                    }
                    else
                    {
                        $html .= "<textarea name='$setupvar' cols='60' rows='10'>{$value}</textarea>";
                    }
            }
            if ($setupvar == 'db_password' AND $_REQUEST['action'] != 'reconfigure' AND $value != '')
            {
                $html .= "<p class='info'>The current password setting is not shown</p>";
            }
            $html .= "</div>";
            $html .= "<br />\n";
            if ($c == 1) $c = 2;
            else $c = 1;
        }
        $html .= "<input type='hidden' name='action' value='save_config' />";
        $html .= "<br /><input type='submit' name='submit' value='Save Configuration' />";
        $html .= "</form>\n";
    }
    return $html;
}


/**
    * Execute a list of SQL queries
    * @author Ivan Lucas
    * @note Attempts to be clever and print helpful messages in the case
    * of an error
*/
function setup_exec_sql($sqlquerylist)
{
    global $CONFIG, $dbSystem, $installed_schema, $application_version;
    if (!empty($sqlquerylist))
    {
        if (!is_array($sqlquerylist)) $sqlquerylist = array($sqlquerylist);


        // Loop around the queries
        foreach ($sqlquerylist AS $schemaversion => $queryelement)
        {
            if ($schemaversion != '0') $schemaversion = substr($schemaversion, 1);
//             echo "<p>Schema version $schemaversion, installed schema $installed_schema, query $queryelement</p>";
            if ($schemaversion == 0 OR $installed_schema < $schemaversion)
            {
                $sqlqueries = explode( ';', $queryelement);
                // We don't need the last entry it's blank, as we end with a ;
                array_pop($sqlqueries);
                $errors = 0;
                foreach ($sqlqueries AS $sql)
                {
                    if (!empty($sql))
                    {
                        mysql_query($sql);
                        if (mysql_error())
                        {
                            $errno = mysql_errno();
                            $errstr = '';
                            // See http://dev.mysql.com/doc/refman/5.0/en/error-messages-server.html
                            // For list of mysql error numbers
                            switch ($errno)
                            {
                                case 1022:
                                case 1050:
                                case 1060:
                                case 1061:
                                case 1062:
                                    $severity = 'info';
                                    $errstr = "This could be because this part of the database schema is already up to date.";
                                    break;
                                case 1058:
                                    $severity = 'error';
                                    $errstr = "This looks suspiciously like a bug, if you think this is the case please report it.";
                                    break;

//                                 case 1054:
//                                     if (preg_match("/ALTER TABLE/", $sql) >= 1)
//                                     {
//                                         $severity = 'info';
//                                         $errstr = "This could be because this part of the database schema is already up to date.";
//                                     }
//                                 break;
                                case 1051:
                                case 1091:
                                    if (preg_match("/DROP/", $sql) >= 1)
                                    {
                                        $severity = 'info';
                                        $errstr = "We expected to find something in order to remove it but it doesn't exist. This could be because this part of the database schema is already up to date..";
                                    }
                                    break;
                                case 1044:
                                case 1045:
                                case 1142:
                                case 1143:
                                case 1227:
                                    $severity = 'error';
                                    $errstr = "This could be because the MySQL user '{$CONFIG['db_username']}' does not have appropriate permission to modify the database schema.<br />";
                                    $errstr .= "<strong>Check your MySQL permissions allow the schema to be modified</strong>.";
                                default:
                                    $severity = 'error';
                                    $errstr = "You may have found a bug, if you think this is the case please report it.";
                            }
                            $html .= "<p class='$severity'>";
                            if ($severity == 'info')
                            {
                                $html .= "<strong>Information:</strong>";
                            }
                            else
                            {
                                $html .= "<strong>A MySQL error occurred:</strong>";
                                $errors ++;
                            }
                            $html .= " [".mysql_errno()."] ".mysql_error()."<br />";
                            if (!empty($errstr)) $html .= $errstr."<br />";
                            $html .= "Raw SQL: <code class='small'>".htmlspecialchars($sql)."</code>";
                        }
                    }
                }
            }
        }
    }
    echo $html;
    return $errors;
}


/**
    * Create a blank SiT database
    * @author Ivan Lucas
    * @retval bool TRUE database created OK
    * @retval bool FALSE database not created, error.
*/
function setup_createdb()
{
    global $CONFIG;

    $res = FALSE;
    $sql = "CREATE DATABASE `{$CONFIG['db_database']}` DEFAULT CHARSET utf8";
    $db = @mysql_connect($CONFIG['db_hostname'], $CONFIG['db_username'], $CONFIG['db_password']);
    if (!@mysql_error())
    {
        // See Mantis 506 for sql_mode discussion
        @mysql_query("SET SESSION sql_mode = '';");

        // Connected to database
        echo "<h2>Creating empty database...</h2>";
        $result = mysql_query($sql);
        if ($result)
        {
            $res = TRUE;
            echo "<p><strong>OK</strong> Database '{$CONFIG['db_database']}' created.</p>";
            echo setup_button('', 'Next');
        }
        else $res = FALSE;
    }
    else
    {
        $res = FALSE;
    }

    if ($res == FALSE)
    {
        echo "<p class='error'>";
        if (mysql_error())
        {
            echo mysql_error()."<br />";
        }
        echo "The database could not be created automatically, ";
        echo "you can create it manually by executing the SQL statement <br /><code>{$sql};</code></p>";
        echo setup_button('', 'Next');
    }
    return $res;
}


/**
    * Check to see whether an admin user exists
    * @author Ivan Lucas
    * @retval bool TRUE : an admin account exists
    * @retval bool FALSE : an admin account doesn't exist
*/
function setup_check_adminuser()
{
    global $dbUsers;
    $sql = "SELECT id FROM `{$dbUsers}` WHERE id=1 OR username='admin' OR roleid='1'";
    $result = @mysql_query($sql);
    if (mysql_num_rows($result) >= 1) return TRUE;
    else FALSE;
}


/**
    * An HTML action button, i.e. a form with a single button
    * @author Ivan Lucas
    * @param string $action.    Value for the hidden 'action' field
    * @param string $label.     Label for the submit button
    * @param string $extrahtml. Extra HTML to display on the form
    * @returns A form with a button
    * @retval string HTML form
*/
function setup_button($action, $label, $extrahtml='')
{
    $html = "\n<form action='{$_SERVER['PHP_SELF']}' method='post'>";
    if (!empty($action))
    {
        $html .= "<input type='hidden' name='action' value=\"{$action}\" />";
    }
    $html .= "<input type='submit' value=\"{$label}\" />";
    if (!empty($extrahtml)) $html .= $extrahtml;
    $html .= "</form>\n";

    return $html;
}

session_name($CONFIG['session_name']);
session_start();

// Force logout
$_SESSION['auth'] = FALSE;
$_SESSION['portalauth'] = FALSE;


echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n";
echo " \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
echo "<head>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=UTF-8\" />\n";
echo "<style type=\"text/css\">\n";
echo "body { background-color: #FFF; font-family: Tahoma, Helvetica, sans-serif; font-size: 10pt;}\n";
echo "h1,h2,h3,h4,h5 { color: #203894; padding: 0.1em; border: 1px solid #4D485B; }\n";
if (file_exists('./images/sitlogo_270x100.png')) echo "body {background-image: url('images/sitlogo_270x100.png'); background-attachment:fixed; background-position: 98% 98%; background-repeat: no-repeat;}\n";
echo "h4 {background-color: transparent; color: #000; border: 0px; margin: 2px 0px 3px 0px; }\n";
echo "div.configvar1 {background-color: #F7FAFF; border: 1px solid #4D485B; margin-bottom: 10px; padding: 0px 5px 10px 5px; filter:alpha(opacity=75);  opacity: 0.75;  -moz-opacity:0.75; -moz-border-radius: 3px;} ";
echo "div.configvar2 {background-color: green; border: 1px solid #4D485B; margin-bottom: 10px;} ";
echo ".error {background-position: 3px 2px;
  background-repeat: no-repeat;
  padding: 3px 3px 3px 22px;
  min-height: 16px;
  -moz-border-radius: 5px;
  /* display: inline; */
  border: 1px solid #000;
  margin-left: 2em;
  margin-right: 2em;
  width: auto;
  text-align: left;
    background-image: url('images/icons/sit/16x16/warning.png');
  color: #5A3612;
  border: 1px solid #A26120;
  background-color: #FFECD7;
}

.info {
 background-position: 3px 2px;
  background-repeat: no-repeat;
  padding: 3px 3px 3px 22px;
  min-height: 16px;
  -moz-border-radius: 5px;
  /* display: inline; */
  border: 1px solid #000;
  margin-left: 2em;
  margin-right: 2em;
  width: auto;
  text-align: left;
}
p.info {
  background-image: url('images/icons/sit/16x16/info.png');
  color: #17446B;
  border: 1px solid #17446B;
  background-color: #F4F6FF;
}

a.button:link, a.button:visited
{
  float: left;
  margin: 2px 5px 2px 5px;
  padding: 2px;
  width: 100px;
  border-top: 1px solid #ccc;
  border-bottom: 1px solid black;
  border-left: 1px solid #ccc;
  border-right: 1px solid black;
  background: #ccc;
  text-align: center;
  text-decoration: none;
  font: normal 10px Verdana;
  color: black;
}

a.button:hover
{
  background: #eee;
}

a.button:active
{
  border-bottom: 1px solid #eee;
  border-top: 1px solid black;
  border-right: 1px solid #eee;
  border-left: 1px solid black;
}

var { font-family: Andale Mono, monospace; font-style: normal; }
code.small { font-size: 75%; color: #555; }
}

";
echo ".help {background: #F7FAFF; border: 1px solid #3165CD; color: #203894; padding: 2px;}\n";
echo ".helptip { color: #203894; }\n";
echo ".warning {background: #FFFFE6; border: 2px solid #FFFF31; color: red; padding: 2px;}\n";
echo "pre {background:#FFF; border:#999; padding: 1em;}\n";
echo "a.button { border: 1px outset #000; padding: 2px; background-color: #EFEFEF;} ";
echo "a:link,a:visited { color: #000099; }\n";
echo "a:hover { background: #99CCFF; }\n";
echo "hr { background-color: #4D485B; margin-top: 3em; }\n";
echo "</style>\n";
echo "<title>Support Incident Tracker Setup</title>\n";
echo "</head>\n<body>\n";

echo "<h1>Support Incident Tracker - Installation &amp; Setup</h1>";

//
// Pre-flight Checks
//

if (!empty($_REQUEST['msg']))
{
    $msg = strip_tags(base64_decode(urldecode($_REQUEST['msg'])));
    if ($cfg_file_exists === FALSE)
    {
        echo "<p class='info'><strong>It looks like you are setting up SiT! for the first time</strong> because we could not find a configuration file.<br />";
        echo "Please proceed with creating a new configuration file.</p>";
    }
    else
    {
        echo "<p class='error'><strong>Configuration Problem</strong>: {$msg}</p>";
    }
}



// Check that includes worked and that we have some config variables set, these two should always be set
if ($CONFIG['application_name'] == '' AND $CONFIG['application_shortname'] == '')
{
    echo "<p class='error'>SiT! Setup couldn't find configuration defaults (defaults.inc.php). Is your lib/ directory missing?</p>";
}

// Check we have the mysql extension
if (!extension_loaded('mysql'))
{
    echo "<p class='error'>Error: Could not find the mysql extension, SiT! requires MySQL to be able to run, you should install and enable the MySQL PHP Extension then run setup again.</p>";
}

if (version_compare(PHP_VERSION, "5.0.0", "<"))
{
    echo "<p class='error'>You are running an older PHP version (< PHP 5), SiT v3.35 and later require PHP 5.0.0 or newer, some features may not work properly.</p>";
}

if (file_exists('/etc/webtrack.conf'))
{
    echo "<p class='warning'>Warning: You have a legacy config file at /etc/webtrack.conf, as of SiT! 4.0 this file is no longer read, please use /etc/sit.conf instead</p>";
}

echo "\n\n<!-- A:".strip_tags($_REQUEST['action'])." -->\n\n";

switch ($_REQUEST['action'])
{
    case 'save_config':
        $newcfgfile = "<";
        $newcfgfile .= "?php\n";
        $newcfgfile .= "# config.inc.php - SiT! Config file generated automatically by setup.php on ".date('r')."\n\n";

        if ($_REQUEST['config'] == 'advanced')
        {
            foreach ($CFGVAR AS $setupvar => $setupval)
            {
                $SETUP[] = $setupvar;
            }
        }

        // Keep the posted setup
        foreach ($SETUP AS $setupvar)
        {
            if ($_POST[$setupvar] === 'TRUE') $_POST[$setupvar] = TRUE;
            if ($_POST[$setupvar] === 'FALSE') $_POST[$setupvar] = FALSE;
            $CONFIG[$setupvar] = $_POST[$setupvar];
        }

        // Set up a hard to find attachment path
        if ($CONFIG['attachment_fspath'] == '')
        {
            // We generate a path based on some semi-static values so that it's hard to guess,
            // but will still probably be the same if setup is run again the same day
            $CONFIG['attachment_fspath'] = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "attachments-" . $systemhash . DIRECTORY_SEPARATOR;
        }

        // Extract the differences between the defaults and the newly configured items
        $CFGDIFF = array_diff_assoc($CONFIG, $DEFAULTS);

        if (count($CFGDIFF) > 0)
        {
            foreach ($CFGDIFF AS $setupvar => $setupval)
            {
                if ($CFGVAR[$setupvar]['title'] != '')
                {
                    $newcfgfile .= "# {$CFGVAR[$setupvar]['title']}\n";
                }

                if ($CFGVAR[$setupvar]['help']!='')
                {
                    $newcfgfile .= "# {$CFGVAR[$setupvar]['help']}\n";
                }

                $newcfgfile .= "\$CONFIG['$setupvar'] = ";

                if (is_numeric($setupval))
                {
                    $newcfgfile .= "{$setupval}";
                }
                elseif (is_bool($setupval))
                {
                    $newcfgfile .= $setupval == TRUE ? "TRUE" : "FALSE";
                }
                elseif (substr($setupval, 0, 6)=='array(')
                {
                    $newcfgfile .= stripslashes("{$setupval}");
                }
                else
                {
                    $newcfgfile .= "'".addslashes($setupval)."'";
                }
                $newcfgfile .= ";\n\n";
            }
        }
        else
        {
            $newcfgfile .= "# Nothing configured. This will mean the defaults are used.\n\n";
        }

        // INL if we leave off the php closing tag it saves people having trouble
        // with whitespace
        //$newcfgfile .= "?";
        //$newcfgfile .= ">";

        $fp = @fopen($config_filename, 'w');
        if (!$fp)
        {
            echo "<p class='error'>Could not write {$config_filename}</p>";
            echo "<p class='help'>Copy this text and paste it into a <var>config.inc.php</var> file in the SiT root directory (the folder than contains setup.php for example)<br />";
            // or <var>sit.conf</var> in the <var>/etc</var> directory
            echo "Or change the permissions on the folder so that it is writable and <a href=\"javascript:location.reload(true)\">refresh</a> this page to try again (if you do this remember to make it ";
            echo "read-only again afterwards)</p>";
            echo "<script type='text/javascript'>\n
                    function selectText(divid)
                    {
                        if (document.selection)
                        {
                            var div = document.body.createTextRange();
                            div.moveToElementText(document.getElementById(divid));
                            div.select();
                        }
                        else
                        {
                            var div = document.createRange();
                            div.setStartBefore(document.getElementById(divid));
                            div.setEndAfter(document.getElementById(divid)) ;
                            window.getSelection().addRange(div);
                        }

                    }
                </script>";
            echo "<div id='configfile' onclick=\"selectText('configfile');\" style='margin-left: 5%; margin-right: 5%; background-color: #F7FAFF; padding: 1em; border: 1px dashed #ccc;filter:alpha(opacity=75);  opacity: 0.75;  -moz-opacity:0.75; -moz-border-radius: 3px; '>";
            highlight_string($newcfgfile);
            echo "</div>";
            echo "<p>After creating your config file click the 'Next' button below.</p>";
        }
        else
        {
            echo "<p>Writing to {$config_filename}</p>";
            fwrite($fp, $newcfgfile);
            fclose($fp);
            echo "<p>Config file modified</p>";
            if (!@chmod($config_filename, 0640))
            {
                echo "<p class='error'>Important: The file permissions on the file <var>{$config_filename}</var> ";
                echo "allow the file to be modified, we recommend you now make this file read-only.";
                if (DIRECTORY_SEPARATOR == '/')
                {
                    $html .= "<br />You can run the command <code>chmod 444</code> to make it read-only.";
                }
                echo "</p>";
            }
        }
        echo setup_button('checkdbstate', 'Next');
        break;
    case 'reconfigure':
        echo "<h2>Reconfigure</h2>";
        echo "<p>Amend your existing SiT! configuration.  Please take care or you may break your SiT! installation.</p>";
        echo setup_configure();
        break;
    case 'checkdbstate':
        // Connect to Database server
        $db = @mysql_connect($CONFIG['db_hostname'], $CONFIG['db_username'], $CONFIG['db_password']);
        if (@mysql_error())
        {
            echo "<p class='error'>Setup could not connect to the database server '{$CONFIG['db_hostname']}'. MySQL Said: ".mysql_error()."</p>";
            echo setup_configure();
        }
        else
        {
            // Connected to database
            // Select database
            mysql_select_db($CONFIG['db_database'], $db);
            if (mysql_error())
            {
                if (!empty($CONFIG['db_username']))
                {
                    if ($cfg_file_exists)
                    {
                        echo "<p class='error'>".mysql_error()."<br />Could not select database";
                        if ($CONFIG['db_database']!='')
                        {
                            echo " '{$CONFIG['db_database']}', check the database name you have configured matches the database in MySQL";
                        }
                        else
                        {
                            echo ", the database name was not configured, please set the <code>\$CONFIG['db_database'] config variable";
                            $CONFIG['db_database'] = 'sit';
                        }
                        echo "</p>";
                        if ($_SESSION['new'] == 1)
                        {
                            echo "<p class='info'>If this is a new installation of SiT and you would like to use the database name '{$CONFIG['db_database']}', you should proceed and create a database</p>";
                        }
                        echo setup_button('reconfigure', 'Reconfigure SiT!');
                        echo "<br />or<br /><br />";
                    }
                    else
                    {
                        echo "<p class='info'>You can now go ahead and create a database called '{$CONFIG['db_database']}' for SiT! to use.</p>";
                    }
                    echo setup_button('createdb', 'Create a database', "<br /><label><input type='checkbox' name='sampledata' value='yes' checked='checked' /> With sample data</label>");
                    //echo "<p><a href='{$_SERVER['PHP_SELF']}?action=reconfigure'>Reconfigure</a> SiT!</p>";
                }
                else
                {
                    // Username and Password are set, but the db could not be selected

                }

                // FIMXE INL temp removed
//                 else
//                 {
//                     echo "<p class='help'>If this is the first time you have used SiT! you may need to create the database, ";
//                     echo "if you have the necessary MySQL permissions you can create the database automatically.<br />";
//                     echo "Alternatively you can create it manually by executing the SQL statement <br /><code>{$sql};</code></p";
//                     echo "<p><a href='setup.php?action=createdatabase' class='button'>Create Database</a></p>";
//                 }
//                 //echo "<p>After creating the database run <a href='setup.php' class='button'>setup</a> again to create the database schema</p>";
                if (empty($CONFIG['db_database']) OR empty($CONFIG['db_username']))
                {
                    echo "<p>You need to configure SiT to be able access the MySQL database.</p>";
                    echo setup_configure();
                }
            }
            else
            {
                echo "<p class='info'>Sucessfully connected to your database.</p>";
                echo setup_button('checkatttdir', 'Next');
            }
        }
        break;
    case 'createdb':
        if ($_REQUEST['sampledata'] == 'yes' ) $_SESSION['sampledata'] = TRUE;
        else $_SESSION['sampledata'] = FALSE;
        setup_createdb();
        break;
    case 'checkatttdiragain':
        $again = TRUE;
    case 'checkatttdir':
        if (file_exists($CONFIG['attachment_fspath']) == FALSE)
        {
            echo "<h2>Attachments Directory</h2>";
            echo "<p>SiT! requires a directory to store attachments.</p>";

            echo setup_button('createattdir', "Create attachments directory");
            echo "<br />";
            if ($again)
            {
                echo setup_button('checkatttdiragain', 'Next');
                echo "<p class='error'>The directory <code>{$CONFIG['attachment_fspath']}</code> must be created before setup can continue.</p>";
            }
        }
        elseif (is_writable($CONFIG['attachment_fspath']) == FALSE)
        {
            echo "<h2>Attachments Directory</h2>";
            echo "<p>SiT! requires that the attachments directory is writable by the web server.</p>";
            if (DIRECTORY_SEPARATOR == '/')
            {
                echo "<br />You can run the following shell command to make it writable.<br /><br /><var>chmod ugo+w {$CONFIG['attachment_fspath']}</var>";
            }
            else
            {
                echo "<br />You may need to set windows file permissions to set the folder <var>{$CONFIG['attachment_fspath']}</var> writable.";
            }
            echo "</p>";

            echo setup_button('checkatttdiragain', 'Next');
        }
        else
        {
            $sql = "SHOW TABLES LIKE '{$dbUsers}'";
            $result = @mysql_query($sql);
            if (mysql_error() OR mysql_num_rows($result) < 1)
            {
                echo "<p>Next we will create a database schema</p>";
                echo setup_button('', 'Next');
            }
            else
            {
                echo "<p class='info'>You can now go ahead and run SiT!.</p>";
                echo "<form action='index.php' method='get'>";
                echo "<input type='submit' value=\"Run SiT!\" />";
                echo "</form>\n";
            }
        }
        break;
    case 'createattdir':
        // Note this creates a directory with 777 permissions!
        $dir = @mkdir($CONFIG['attachment_fspath'], '0777');
        if ($dir)
        {
            echo setup_button('checkatttdir', 'Next');
        }
        else
        {
            echo "<p class='error'>Sorry, the attachment directory could not be created for you.</p>"; // FIXME more help
            echo "<p>Please manually create a directory named <code>{$CONFIG['attachment_fspath']}</code></p>";

            if (substr($CONFIG['attachment_fspath'], 0, 14) == './attachments-')
            {
                echo "<p class='info'>Setup has chosen this random looking directory name on purpose, ";
                echo "please create the directory named exactly as shown above.</p>";
            }
            echo setup_button('checkatttdiragain', 'Next');
        }
        break;
    default:
        require (APPLICATION_LIBPATH . 'tablenames.inc.php');
        // Connect to Database server
        $db = @mysql_connect($CONFIG['db_hostname'], $CONFIG['db_username'], $CONFIG['db_password']);
        if (@mysql_error())
        {
            echo setup_configure();
        }
        else
        {
            // Connected to database
            // Select database
            mysql_select_db($CONFIG['db_database'], $db);
            if (mysql_error())
            {
                if (!empty($CONFIG['db_username']))
                {
                    echo "<p class='error'>".mysql_error()."<br />Could not select database";
                    if ($CONFIG['db_database']!='')
                    {
                        echo " '{$CONFIG['db_database']}', check the database name you have configured matches the database in MySQL";
                    }
                    else
                    {
                        echo ", the database name was not configured, please set the <code>\$CONFIG['db_database'] config variable";
                        $CONFIG['db_database'] = 'sit';
                    }
                    echo "</p>";
                    echo setup_button('reconfigure', 'Reconfigure SiT!');
                    echo "<p>or</p>";
                    echo setup_button('createdb', 'Create a database', "<br /><label><input type='checkbox' name='sampledata' value='yes' checked='checked' /> With sample data</label>");
                    //echo "<p><a href='{$_SERVER['PHP_SELF']}?action=reconfigure'>Reconfigure</a> SiT!</p>";
                }
                else
                {
                    // Username and Password are set, but the db could not be selected

                }

                // FIMXE INL temp removed
//                 else
//                 {
//                     echo "<p class='help'>If this is the first time you have used SiT! you may need to create the database, ";
//                     echo "if you have the necessary MySQL permissions you can create the database automatically.<br />";
//                     echo "Alternatively you can create it manually by executing the SQL statement <br /><code>{$sql};</code></p";
//                     echo "<p><a href='setup.php?action=createdatabase' class='button'>Create Database</a></p>";
//                 }
//                 //echo "<p>After creating the database run <a href='setup.php' class='button'>setup</a> again to create the database schema</p>";
                if (empty($CONFIG['db_database']) OR empty($CONFIG['db_username']))
                {
                    echo "<p>You need to configure SiT to be able access the MySQL database.</p>";
                    echo setup_configure();
                }
            }
            else
            {
                require (APPLICATION_LIBPATH . 'functions.inc.php');

                // Load the empty schema
                require ('setup-schema.php');

                // Connected to database and db selected
                echo "<p>Connected to database - ok</p>";
                // Check to see if we're already installed
                $sql = "SHOW TABLES LIKE '{$dbUsers}'";
                $result = mysql_query($sql);
                if (mysql_error())
                {
                    echo "<p class='error'>Could not find a users table, an error occurred ".mysql_error()."</p>";
                    exit;
                }
                if (mysql_num_rows($result) < 1)
                {
                    echo "<h2>Creating new database schema...</h2>";
                    // No users table or empty users table, proceed to install
//                     $installed_schema = 0;
//                     $installed_schema = substr(end(array_keys($upgrade_schema[$application_version*100])),1);
                    $errors = setup_exec_sql($schema);
                    if ($_SESSION['sampledata'] == TRUE)
                    {
                        // Install sample data
                        echo "<p>Installing sample data...</p>";
                        $errors = $errors + setup_exec_sql($sampledata_sql);
                    }
                    // Update the system version
                    if ($errors < 1)
                    {
                        $vsql = "REPLACE INTO `{$dbSystem}` ( `id`, `version`) VALUES (0, $application_version)";
                        mysql_query($vsql);
                        if (mysql_error())
                        {
                            $html .= "<p class='error'>Could not store new schema version number '{$application_version}'. ".mysql_error()."</p>";
                        }
                        else
                        {
                            $html .= "<p>Schema successfully created as version {$application_version}</p>";
                        }
                    }
                    else
                    {
                        $html .= "<p class='error'><strong>Summary</strong>: {$errors} Error(s) occurred while creating the schema, ";
                        $html .= "please resolve the problems reported and then try running setup again.</p>";
                    }
                    echo $html;
/*                    // Set the system version number
                    $sql = "REPLACE INTO `{$dbSystem}` ( id, version) VALUES (0, $application_version)";
                    mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);*/
                    $installed_version = $application_version;
                    echo "<h2>Database schema created</h2>";
                    if ($errors > 0)
                    {
                        echo "<p>If these errors do not appear to be caused by your configuration or setup, ";
                        echo "please log a bug <a href='http://sitracker.org/wiki/Bugs'>here</a>";
                        echo ", with the full error message.</p>";
                    }
                    else
                    {
                        echo "<p>You can now proceed with the next step.</p>";
                    }
                    echo setup_button('checkinstallcomplete', 'Next');
                }
                else
                {
                    // users table exists and has at least one record, must be already installed
                    // Do upgrade

                    // Have a look what version is installed
                    // First look to see if the system table exists
                    $exists = mysql_query("SELECT 1 FROM `{$dbSystem}` LIMIT 0");
                    if (!$exists)
                    {
                        echo "<p class='error'>Could not find a 'system' table which probably means you have a version prior to v3.21</p>";
                        $installed_version = 3.00;
                    }
                    else
                    {
                        $sql = "SELECT `version` FROM `{$dbSystem}` WHERE id = 0";
                        $result = mysql_query($sql);
                        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                        list($installed_version) = mysql_fetch_row($result);

/*                        if ($installed_version >= 3.35)
                        {
                            $sql = "SELECT `schemaversion` FROM `{$dbSystem}` WHERE id = 0";
                            $result = mysql_query($sql);
                            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                            list($installed_schema) = mysql_fetch_row($result);
                        }
                        else
                        {
                            $installed_schema = 334;
                            $sql = "SHOW COLUMNS FROM `{$dbSystem}` WHERE Field='schema'";
                            $result = mysql_query($sql);
                            if (mysql_num_rows($result) < 1)
                            {
                                $sql = "ALTER TABLE `{$dbSystem}` ADD `schemaversion` BIGINT UNSIGNED NOT NULL COMMENT 'DateTime in YYYYMMDDHHMM format'";
                                $result = mysql_query($sql);
                                if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                            }
                        }*/
                    }

                    if (empty($installed_version))
                    {
                        echo "<p class='error'>Fatal setup error - Could not determine version of installed software.  Try wiping your installation and installing from clean. (sorry)</p>";
                        echo setup_button('', 'Restart setup');
                        exit;
                    }

                    echo "<h2>Installed OK</h2>";

                    if ($_REQUEST['action'] == 'upgrade')
                    {
                        /*****************************
                         * Do pre-upgrade tasks here *
                         *****************************/

                        if ($installed_version < 3.35)
                        {
                            //Get anyone with var_notify_on_reassign on so we can add them a trigger later
                            $sql = "SELECT * FROM `{$dbUsers}` WHERE var_notify_on_reassign='true'";
                            if ($result = @mysql_query($sql))
                            {
                                while ($row = mysql_fetch_object($result))
                                {
                                    $assign_notify_users[] = $row->id;
                                }
                            }

                            //any kbarticles with private content, change whole type
                            $sql = "SELECT docid, distribution FROM `{$dbKBContent} WHERE distribution!='public'";
                            if ($result = @mysql_query($sql))
                            {
                                while ($row = @mysql_fetch_object($result))
                                {
                                    if ($row->distribution == 'private')
                                    {
                                        $kbprivate[] = $row->docid;
                                    }
                                    elseif (!in_array($row->docid, $kbprivate))
                                    {
                                        $kbrestricted[] = $row->docid;
                                    }
                                }
                            }
                        }

                        if ($installed_version < 3.45)
                        {
                            $sql = "SELECT i.id FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbContacts']}` AS c, `{$dbServiceLevels}` AS sl ";
                            $sql .= "WHERE c.id = i.contact ";
                            $sql .= "AND sl.tag = i.servicelevel AND sl.priority = i.priority AND sl.timed = 'yes' ";
                            $sql .= "AND i.status = 2 "; // Only want closed incidents, dont want awaiting closure as they could be reactivated

                            $result = mysql_query($sql);
                            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                            if (mysql_num_rows($result) > 0)
                            {
                                while ($obj = mysql_fetch_object($result))
                                {
                                    $asql = "SELECT DISTINCT origcolref, linkcolref ";
                                    $asql .= "FROM `{$dbLinks}` AS l, `{$dbLinkTypes}` AS lt ";
                                    $asql .= "WHERE l.linktype = 6 ";
                                    $asql .= "AND linkcolref = {$obj->id} ";
                                    $asql .= "AND direction = 'left'";
                                    $aresult = mysql_query($asql);
                                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

                                    if (mysql_num_rows($aresult) == 0)
                                    {
                                        $billing_upgrade[] = $obj->id;
                                    }
                                }
                            }
                        }

                        /*****************************
                         * UPGRADE THE SCHEMA        *
                         *****************************/
                        for ($v=(($installed_version*100)+1); $v<=($application_version*100); $v++)
                        {
                            $html = '';
                            if (!empty($upgrade_schema[$v]))
                            {
                                $newversion = number_format(($v/100),2);
                                echo "<p>Updating schema from {$installed_version} to v{$newversion}&hellip;</p>";
                                $errors = setup_exec_sql($upgrade_schema[$v]);
                                // Update the system version
                                if ($errors < 1)
                                {
                                    $vsql = "REPLACE INTO `{$dbSystem}` ( `id`, `version`) VALUES (0, $newversion)";
                                    mysql_query($vsql);
                                    if (mysql_error())
                                    {
                                        $html .= "<p class='error'>Could not store new schema version number '{$newversion}'. ".mysql_error()."</p>";
                                    }
                                    else
                                    {
                                        $html .= "<p>Schema successfully updated to version {$newversion}.</p>";
                                    }
                                    $installed_version = $newversion;
                                    $upgradeok = TRUE;
                                }
                                else
                                {
                                    $html .= "<p class='error'><strong>Summary</strong>: {$errors} Error(s) occurred while updating the schema, ";
                                    $html .= "please resolve the problems reported and then try running setup again.</p>";
                                }
                                echo $html;
                            }
                        }

                        /******************************
                         * Do Post-upgrade tasks here *
                         ******************************/
                        if ($installed_version < 3.21)
                        {
                            echo "<p>Upgrading incidents data from version prior to 3.21...</p>";
                            // Fill the new servicelevel field in the incidents table using information from the maintenance contract
                            echo "<p>Upgrading incidents table to store service level...</p>";
                            $sql = "SELECT *,i.id AS incidentid FROM `{$dbIncidents}` AS i, `{$dbMaintenance}` AS m, {$dbServiceLevels}` AS sl WHERE i.maintenanceid=m.id AND ";
                            $sql .= "m.servicelevelid = sl.id ";
                            $result = mysql_query($sql);
                            while ($row = mysql_fetch_object($result))
                            {
                                $sql = "UPDATE `{$dbIncidents}` SET servicelevel='{$row->tag}' WHERE id='{$row->incidentid}' AND servicelevel IS NULL LIMIT 1";
                                mysql_query($sql);
                                if (mysql_error())
                                {
                                    trigger_error(mysql_error(),E_USER_WARNING);
                                    echo "<p><strong>FAILED:</strong> $sql</p>";
                                    $upgradeok = FALSE;
                                }
                                else echo "<p><strong>OK:</strong> $sql</p>";
                            }
                            echo "<p>".mysql_num_rows($result)." incidents upgraded</p>";
                        }

                        if ($installed_version < 3.35)
                        {
                            if ($CONFIG['closure_delay'] > 0 AND $CONFIG['closure_delay'] != 554400)
                            {
                                echo "<p>Inserting value from deprecated config variable <var>closure_delay</var> into scheduler</p>";
                                $sql = "UPDATE `{$dbScheduler}` SET params = '{$CONFIG['closure_delay']}' WHERE action = 'CloseIncidents' LIMIT 1";
                                mysql_query($sql);
                                if (mysql_error())
                                {
                                    trigger_error(mysql_error(),E_USER_WARNING);
                                    echo "<p><strong>FAILED:</strong> $sql</p>";
                                    $upgradeok = FALSE;
                                }
                                else echo "<p><strong>OK:</strong> $sql</p>";
                            }

                            //add trigger to users, NOTE we do user 1(admin's) in the schema
                            $sql = "SELECT id FROM `{$dbUsers}` WHERE id > 1";
                            if ($result = @mysql_query($sql))
                            {
                                echo '<p>Adding default triggers to existing users.</p>';
                                while ($row = mysql_fetch_row($result))
                                {
                                    setup_user_triggers($row->id);
                                }
                            }

                            //add the triggers for var_notify users from above
                            if (is_array($assign_notify_users))
                            {
                                echo '<p>Replacing "Notify on reassign" option with triggers</p>';
                                foreach ($assign_notify_users as $assign_user)
                                {
                                    $sql = "INSERT INTO `{$dbTriggers}`(triggerid, userid, action, template, checks) ";
                                    $sql .= "VALUES('TRIGGER_INCIDENT_ASSIGNED', '$assign_user', 'ACTION_EMAIL', 'EMAIL_INCIDENT_REASSIGNED_USER_NOTIFY', '{userstatus} ==  {$assign_user}')";
                                    mysql_query($sql);
                                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                                }
                            }

                            //fix the visibility for KB articles
                            if (is_array($kbprivate))
                            {
                                foreach ($kbprivate as $article)
                                {
                                    $articleID = intval($article);
                                    $sql = "UPDATE `{$dbKBArticles}` SET visibility='private' WHERE id='{$articleID}'";
                                    mysql_query($sql);
                                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                                }
                            }

                            if (is_array($kbrestricted))
                            {
                                foreach ($kbrestricted as $article)
                                {
                                    $articleID = intval($article);
                                    $sql = "UPDATE `{$dbKBArticles}` SET visibility='restricted' WHERE id='{$articleID}'";
                                    mysql_query($sql);
                                    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                                }
                            }

                            if (is_array($billing_upgrade))
                            {
                                foreach ($billing_upgrade AS $incident)
                                {
                                    $r = close_billable_incident($incident);
                                    if (!$r)
                                    {
                                        trigger_error("Error upgrading {$incident} to new billing format", E_USER_WARNING);
                                    }
                                }
                            }
                        }

                        if ($installed_version < 3.40)
                        {
                            //remove any brackets from checks as per mantis 197
                            $sql = "UPDATE `triggers` SET `checks` = REPLACE(`checks`, '(', ''); ";
                            $sql .= "UPDATE `triggers` SET `checks` = REPLACE(`checks`, ')', '')";
                            mysql_query($sql);
                            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                        }

                        if ($installed_version == $application_version)
                        {
                            echo "<p>Everything is up to date</p>";
                            echo "<p>See the <code>doc/UPGRADE</code> file for further upgrade notes.<br />";
                        }
                        else
                        {
                            $upgradeok = TRUE;
                            echo "<p>See the <code>doc/UPGRADE</code> file for further upgrade instructions and help.<br />";

                        }

                        if ($installed_version >= 3.24)
                        {
                            //upgrade dashboard components.

                            $sql = "SELECT * FROM `{$dbDashboard}` WHERE enabled = 'true'";
                            $result = mysql_query($sql);
                            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

                            //echo "<h2>Dashboard</h2>";
                            while ($dashboardnames = mysql_fetch_object($result))
                            {
                                $version = 1;
                                include (dirname( __FILE__ ).DIRECTORY_SEPARATOR."plugins/dashboard_{$dashboardnames->name}.php");
                                $func = "dashboard_{$dashboardnames->name}_get_version";

                                if (function_exists($func))
                                {
                                    $version = $func();
                                }

                                if ($version > $dashboardnames->version)
                                {
                                    echo "<p>Upgrading {$dashboardnames->name} dashlet to v$version...</p>";
                                    // apply all upgrades since running version
                                    $upgrade_func = "dashboard_{$dashboardnames->name}_upgrade";

                                    if (function_exists($upgrade_func))
                                    {
                                        $dashboard_schema = $upgrade_func();
                                        for ($i = $dashboardnames->version; $i <= $version; $i++)
                                        {
                                            setup_exec_sql($dashboard_schema[$i]);
                                        }

                                        $upgrade_sql = "UPDATE `{$dbDashboard}` SET version = '{$version}' WHERE id = {$dashboardnames->id}";
                                        mysql_query($upgrade_sql);
                                        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

                                        echo "<p>{$dashboardnames->name} upgraded</p>";
                                    }
                                    else
                                    {
                                        echo "<p>No upgrade function for {$dashboardnames->name}</p>";
                                    }
                                }
                            }
                        }

                        if ($upgradeok)
                        {
                            // Update the system version number
                            $sql = "REPLACE INTO `{$dbSystem}` ( id, version) VALUES (0, $application_version)";
                            mysql_query($sql);
                            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
                            $installed_version = $application_version;
                            echo "<h2>Upgrade complete</h2>";
                            echo "<p>Upgraded to v{$application_version}</p>";
                            include (APPLICATION_LIBPATH . 'triggers.inc.php');
                            trigger("TRIGGER_SIT_UPGRADED", array('applicationversion' => $application_version));
                        }
                        else
                        {
                            echo "<p class='error'>Upgrade failed.  Maybe you could try a fresh installation?</p>";
                        }
                    }
                    else
                    {
//                         $latest_schema = substr(end(array_keys($upgrade_schema[$application_version*100])),1);
                        echo "<p>Your database schema is v".number_format($installed_version,2);
//                          . "-{$installed_schema}";
                        //if ($installed_schema < $latest_schema)
//                         echo ", the latest available schema is v".number_format($installed_version,2) . "-{$latest_schema}";
                        if ($installed_version < $application_version) echo ", after making a backup you should upgrade your schema to v{$application_version}";
                        echo "</p>";

                        // Display SQL schema changes for svn versions
                        if (substr($application_revision, 0, 3) == 'svn')
                        {
                            echo "<p>You are running an <a href='http://sitracker.org/wiki/Development/Unreleased_Versions'>SVN version</a>, you should check that you have all of these schema changes: (some may have been added recently)</p>";
                            echo "<div style='border: 1px solid red;padding:10px; background: #FFFFC0; font-family:monospace; font-size: 80%; height:200px; overflow:scroll;'>";
                            echo nl2br($upgrade_schema[$installed_version*100]);
                            echo "</div>";
                        }

                        if (is_array($upgrade_schema[$installed_version*100]))
                        {
                            foreach ($upgrade_schema[$installed_version*100] AS $possible_schema_updates => $nothing)
                            {
                                $possible_schema_updates = substr($possible_schema_updates, 1);
                                if ($possible_schema_updates > $installed_schema) $schemaupgradeneeded = TRUE;
                                else $schemaupgradeneeded = FALSE;
                            }
                        }
                        if ($installed_version < $application_version OR $schemaupgradeneeded == TRUE)
                        {
                            echo setup_button('upgrade', 'Upgrade Schema');
                        }
                    }

                    if ($_REQUEST['action'] == 'createadminuser' AND setup_check_adminuser() == FALSE)
                    {
                        $password = mysql_real_escape_string($_POST['newpassword']);
                        $passwordagain = mysql_real_escape_string($_POST['passwordagain']);
                        if ($password == $passwordagain)
                        {
                            $password = md5($password);
                            $email = mysql_real_escape_string($_POST['email']);
                            $sql = "INSERT INTO `{$dbUsers}` (`id`, `username`, `password`, `realname`, `roleid`, `title`, `signature`, `email`, `status`, `var_style`, `lastseen`) ";
                            $sql .= "VALUES (1, 'admin', '$password', 'Administrator', 1, 'Administrator', 'Regards,\r\n\r\nSiT Administrator', '$email', '1', '8', NOW());";
                            mysql_query($sql);
                            if (mysql_error())
                            {
                               trigger_error(mysql_error(),E_USER_WARNING);
                               echo "<p><strong>FAILED:</strong> $sql</p>";
                            }
                        }
                        else
                        {
                            echo "<p class='error'>Admin account not created, the passwords you entered did not match.</p>";
                        }
                    }
                    // Check installation
                    echo "<h2>Checking installation...</h2>";
                    if ($cfg_file_writable)
                    {
                        $checkadminuser = setup_check_adminuser();
                        echo "<p class='error'>Important: The file permissions on the configuration file <var>{$config_filename}</var> file ";
                        echo "allow it to be modified, we recommend you make this file read-only.";
                        if (DIRECTORY_SEPARATOR == '/')
                        {
                            echo "<br />You can run the following shell command to make it read-only.<br /><br /><var>chmod 444 {$config_filename}</var>";
                        }
                        else
                        {
                            echo "<br />You can run the following command from windows command prompt to make it read-only.<br /><br /><var>attrib +r {$config_filename}</var>";
                        }
                        echo "</p>";
                        if ($checkadminuser == FALSE) echo "<p>You must set your config file to be read-only before setup can continue.</p>";
                        echo setup_button('', 'Re-check installation');
                        if ($checkadminuser == TRUE)
                        {
                            echo "<br />or<br /><br />";
                            echo "<form action='index.php' method='get'>";
                            echo "<input type='submit' value=\"Run SiT!\" />";
                            echo "</form>\n";
                        }
                    }
                    elseif (!isset($_REQUEST))
                    {
                        echo "<p class='error'>SiT! requires PHP 5.0.0 or later</p>";
                    }
                    elseif (@ini_get('register_globals')==1 OR strtolower(@ini_get('register_globals'))=='on')
                    {
                        echo "<p class='error'>SiT! strongly recommends that you change your php.ini setting <code>register_globals</code> to OFF.</p>";
                    }
                    elseif (setup_check_adminuser() == FALSE)
                    {
                        echo "<p><span style='color: red; font-weight: bolder;'>Important:</span> you <strong>must</strong> create an admin account before you can use SiT</p>";
                        echo "<form action='setup.php' method='post'>\n";
                        echo "<p>Username:<br /><input type='text' name='username' value='admin' disabled='disabled' /> (cannot be changed)</p>";
                        echo "<p><label>Password:<br /><input type='password' name='newpassword' size='30' maxlength='50' /></label></p>";
                        echo "<p><label>Confirm Password:<br /><input type='password' name='passwordagain' size='30' maxlength='50' /></label></p>";
                        echo "<p><label>Email:<br /><input type='text' name='email' size='30' maxlength='255' /></label></p>";
                        echo "<p><input type='submit' value='Create Admin User' />";
                        echo "<input type='hidden' name='action' value='createadminuser' />";
                        echo "</form>";
                    }
                    else
                    {
                        if ($installed_version < $application_version)
                        {
                            echo "<p>SiT! v".number_format($application_version,2)." is installed ";
                            echo "but the database schema, which is for v".number_format($installed_version,2).", is out of date.";
                        }
                        else
                        {
                            echo "<p>SiT! v".number_format($installed_version,2)." is installed ";
                            echo "and ready.";
                        }
                        echo "</p>";
                        echo "<form action='index.php' method='get'>";
                        echo "<input type='submit' value=\"Run SiT!\" />";
                        echo "</form>\n";

                        if ($_SESSION['userid'] == 1)
                        {
                            echo "<br /><p>As administrator you can <a href='config.php'>reconfigure</a> SiT!</p>";
                        }
                    }
                }
            }
        }
}
echo "<div style='margin-top: 50px;'>";
echo "<hr style='width: 50%; margin-left: 0px;'/>";
echo "<p><a href='http://sitracker.org/'>{$CONFIG['application_name']}</a> Setup | <a href='http://sitracker.org/wiki/Installation'>Installation Help</a></p>";
echo "<p></p>";
echo "</div>";
echo "\n</body>\n</html>";
?>