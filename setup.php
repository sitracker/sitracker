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

// NOTE: we only support upgrades to 4.x from 3.50 or HIGHER

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//         Paul Heaney <paul[at]sitracker.org>

// Define path constants, we don't include core.php so we do this here
define ('APPLICATION_FSPATH', realpath(dirname( __FILE__ ) . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
define ('APPLICATION_LIBPATH', realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR . 'lib') . DIRECTORY_SEPARATOR);
define ('APPLICATION_HELPPATH', realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR . 'help') . DIRECTORY_SEPARATOR);
define ('APPLICATION_INCPATH', realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR . 'inc') . DIRECTORY_SEPARATOR);
define ('APPLICATION_I18NPATH', realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR . 'i18n') . DIRECTORY_SEPARATOR);
define ('APPLICATION_PORTALPATH', realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR . 'portal') . DIRECTORY_SEPARATOR);
define ('APPLICATION_PLUGINPATH', realpath(dirname( __FILE__ ).DIRECTORY_SEPARATOR . 'plugins') . DIRECTORY_SEPARATOR);

// Define permissions
require (APPLICATION_LIBPATH . 'constants.inc.php');

require (APPLICATION_LIBPATH . 'plugins.inc.php');

// Load config defaults
@include (APPLICATION_LIBPATH . 'defaults.inc.php');
// Keep the defaults as a seperate array
$DEFAULTS = $CONFIG;

require (APPLICATION_LIBPATH . 'functions.inc.php');

// Load config file with customisations
// @include ("config.inc-dist.php");
if (file_exists(APPLICATION_FSPATH . "config.inc.php") AND !include (APPLICATION_FSPATH . "config.inc.php"))
{
    die('Could not read config file config.inc.php');
}
// Server Configuration

if (file_exists('/etc/sit.conf') AND !include ('/etc/sit.conf'))
{
    die('Cound not read config file sit.conf');
}

// These are the required variables we want to configure during installation
$SETUP = array('db_hostname','db_database','db_username','db_password', 'db_tableprefix','application_webpath');

require (APPLICATION_LIBPATH . 'configvars.inc.php');
require (APPLICATION_LIBPATH . 'setup.inc.php');

$upgradeok = FALSE;
$config_filename = APPLICATION_FSPATH . 'config.inc.php';

$configfiles = get_included_files();

$systemhash = md5(date('Y-m-d') . $_SERVER['REMOTE_ADDR']
                . $_SERVER['SCRIPT_FILENAME'] . $_SERVER['HTTP_USER_AGENT']
                . $CONFIG['attachment_fspath'] . $_SERVER['SERVER_SIGNATURE'] );

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

session_name($CONFIG['session_name']);
session_start();

if (empty($_SESSION['randomhash']))
{
    $_SESSION['randomhash'] = sha1(uniqid(rand(), true));
}

// Force logout
$_SESSION['auth'] = FALSE;
$_SESSION['portalauth'] = FALSE;


require (APPLICATION_INCPATH . 'setupheader.inc.php');

echo "<h1>Support Incident Tracker - Installation &amp; Setup</h1>";

//
// Pre-flight Checks
//

if (!empty($_REQUEST['msg']))
{
    $msg = htmlspecialchars((base64_decode(urldecode($_REQUEST['msg']))), ENT_QUOTES, 'utf-8');
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

if (version_compare(PHP_VERSION, MIN_PHP_VERSION, "<"))
{
    echo "<p class='error'>You are running an older PHP version (< PHP " . MIN_PHP_VERSION . "), SiT v3.35 and later require PHP " . MIN_PHP_VERSION . " or newer, some features may not work properly.</p>";
}

if (file_exists('/etc/webtrack.conf'))
{
    echo "<p class='warning'>Warning: You have a legacy config file at /etc/webtrack.conf, as of SiT! 4.0 this file is no longer read, please use /etc/sit.conf instead</p>";
}

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
            $CONFIG['attachment_fspath'] = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "attachments-" . $_SESSION['randomhash'] . DIRECTORY_SEPARATOR;
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
                elseif (mb_substr($setupval, 0, 6) == 'array(')
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

        // INL if we leave off the php closing tag it saves people having trouble with whitespace

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
        $db = @mysql_connect($CONFIG['db_hostname'], $CONFIG['db_username'], $CONFIG['db_password']);
        if (@mysql_error())
        {
            echo "<p class='error'>Setup could not connect to the database server '{$CONFIG['db_hostname']}'. MySQL Said: ".mysql_error()."</p>";
            echo setup_configure();
        }
        else
        {
            $status = check_install_status();

            echo html_install_status($status);

            if ($status->get_status() != INSTALL_FATAL)
            {
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
                        echo setup_button('createdb', 'Create a database', "<br /><label><input type='checkbox' name='sampledata' id='sampledata' value='yes'  /> With sample data</label>
                    														<br /><label><input type='checkbox' name='promptinitialdata' id='promptinitialdata' value='yes' checked='checked' /> Prompt for initial data</label>");
                    }
                    else
                    {
                        // Username and Password are set, but the db could not be selected

                    }

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
            else
            {
                echo "<p class='error'>Fatal errors exist in your environment, please fix and rerun the setup</p>";
            }
        }
        break;
    case 'createdb':
        if ($_REQUEST['sampledata'] == 'yes' ) $_SESSION['sampledata'] = TRUE;
        else $_SESSION['sampledata'] = FALSE;
        if ($_REQUEST['promptinitialdata'] == 'yes') $_SESSION['promptinitialdata'] = TRUE;
        else $_SESSION['promptinitialdata'] = FALSE;
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
            echo "<p class='error'>Sorry, the attachment directory could not be created for you.</p>";
            echo "<p>Please manually create a directory named <code>{$CONFIG['attachment_fspath']}</code></p>";

            if (mb_substr($CONFIG['attachment_fspath'], 0, 14) == './attachments-')
            {
                echo "<p class='info'>Setup has chosen this random looking directory name on purpose, ";
                echo "please create the directory named exactly as shown above.</p>";
            }
            echo setup_button('checkatttdiragain', 'Next');
        }
        break;
    default:
        require (APPLICATION_LIBPATH . 'tablenames.inc.php');
        $db = @mysql_connect($CONFIG['db_hostname'], $CONFIG['db_username'], $CONFIG['db_password']);
        if (@mysql_error())
        {
            echo setup_configure();
        }
        else
        {
            $status = check_install_status();

            echo html_install_status($status);

            if ($status->get_status() != INSTALL_FATAL)
            {
                mysql_select_db($CONFIG['db_database'], $db);
                if (mysql_error())
                {
                    if (!empty($CONFIG['db_username']))
                    {
                        echo "<p class='error'>".mysql_error()."<br />Could not select database";
                        if ($CONFIG['db_database'] != '')
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
                        // Seems to be duplicated above ???????? TODO FIXME
                        echo setup_button('createdb', 'Create a database', "<br /><label><input type='checkbox' name='sampledata' id='sampledata' value='yes'  /> With sample data</label>
                                            														<br /><label><input type='checkbox' name='promptinitialdata' id='promptinitialdata' value='yes' checked='checked' /> Prompt for initial data</label>");
                    }
                    else
                    {
                        // Username and Password are set, but the db could not be selected

                    }

                    if (empty($CONFIG['db_database']) OR empty($CONFIG['db_username']))
                    {
                        echo "<p>You need to configure SiT to be able access the MySQL database.</p>";
                        echo setup_configure();
                    }
                }
                else
                {
                    // Load the empty schema
                    require ('setup-schema.php');

                    // Connected to database and db selected
                    echo "<p class='info'>Connected to database - ok</p>";
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
                        $errors = setup_exec_sql($schema);
                        if ($_SESSION['sampledata'] == TRUE)
                        {
                            echo "<p>Installing sample data...</p>";
                            $errors = $errors + setup_exec_sql($sampledata_sql);
                        }
                        
                        if ($_SESSION['promptinitialdata'] == TRUE)
                        {
                            
                        }

                        $dashlets = install_dashboard_components();
                        if (count($dashlets) > 0)
                        {
                            echo "<p class='error'>The following dashlets failed to install ".explode(',', $dashlets)."</p>";
                        }

                        if ($errors < 1)
                        {
                            echo update_sit_version_number($application_version);
                        }
                        else
                        {
                            echo "<p class='error'><strong>Summary</strong>: {$errors} Error(s) occurred while creating the schema, ";
                            echo "please resolve the problems reported and then try running setup again.</p>";
                        }

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
                        
                        $installed_version = current_schema_version();
                        if ($installed_version === 0)
                        {
                            echo "<p class='error'>Could not find a 'system' table which probably means you have a version prior to v3.21, we only support upgrades from v3.50 or higher</p>";
                            exit;  // We don't support upgrades from such an old version
                        }
                        elseif ($installed_version < 3.50)
                        {
                            echo "<p class='error'>You are running version {$installed_version}, only v3.50 or higher are able to be upgraded, please upgrade to the latest 3.6x release before upgrading</p>";
                            exit;  // We don't support upgrades from such an old version
                        }

                        if (empty($installed_version))
                        {
                            echo "<p class='error'>Fatal setup error - Could not determine version of installed software.  Try wiping your installation and installing from clean. (sorry).  This should not happen, if it does please contact the developers</p>";
                            echo setup_button('', 'Restart setup');
                            exit;
                        }

                        echo "<h2>Installed OK</h2>";

                        if ($_REQUEST['action'] == 'upgrade')
                        {
							/*****************************
                             * NOTE: we only support upgrades to 4.x from 3.50 or HIGHER *
                             *****************************/

                            // Move billingmatrix to contract
                            $billingmatrixerror = false;
                            // Its OK to group on contractid as previously we only supported on billing matrix
                            $sqlup1 = "SELECT billingmatrix, contractid FROM `{$dbService}` ORDER BY serviceid GROUP BY contractid";
                            $resultup1 = mysql_query($sqlup1);
                            if (mysql_error())
                            {
                                $billingmatrixerror = true;
                                trigger_error(mysql_error(), E_USER_WARNING);
                            }

                            while ($obj = mysql_fetch_object($resultup1))
                            {
                                if (empty($obj->billingmatrix))
                                {
                                    $obj->billingmatrix = "Default";
                                }
                                $sqlup2 = "UPDATE {$dbMaintenance} SET billingmatrix = '{$obj->billingmatrix}' WHERE id = {$obj->contractid}";
                                mysql_query($sqlup2);
                                if (mysql_error())
                                {
                                    $billingmatrixerror = true;
                                    trigger_error(mysql_error(), E_USER_WARNING);
                                }
                            }
                            
                            if (!$billingmatrixerror)
                            {
                                $sqlup3 = "ALTER TABLE `{$dbService}` DROP `billingmatrix`";
                                mysql_query($sqlup2);
                                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                            }
                            // END Move billingmatrix to contract
                            
                            /*****************************
                             * Do pre-upgrade tasks here *
                             *****************************/

                            /*****************************
                             * UPGRADE THE SCHEMA        *
                             *****************************/
                            $installed_version = upgrade_schema($installed_version);

                            /*******************************
                            * DISABLE INCOMPATABLE PLUGINS *
                            ********************************/
                            sit_upgrade_plugin_check(TRUE, $application_version);
                            
                            /******************************
                             * Do Post-upgrade tasks here *
                             ******************************/


                            if ($installed_version == $application_version)
                            {
                                $upgradeok = TRUE;
                                echo "<p>Everything is up to date</p>";
                                echo "<p>See the <code>doc/UPGRADE</code> file for further upgrade notes.<br />";
                            }
                            else
                            {
                                echo "<p>See the <code>doc/UPGRADE</code> file for further upgrade instructions and help.<br />";
                            }

                            echo upgrade_dashlets();

                            if ($upgradeok)
                            {
                                update_sit_version_number($application_version);
                                $installed_version = $application_version;
                                echo "<h2>Upgrade complete</h2>";
                                echo "<p>Upgraded to v{$application_version}</p>";
                                include (APPLICATION_LIBPATH . 'triggers.inc.php');
                                $t = new TriggerEvent("TRIGGER_SIT_UPGRADED", array('applicationversion' => $application_version));
                            }
                            else
                            {
                                echo "<p class='error'>Upgrade failed.  Maybe you could try a fresh installation?</p>";
                            }
                        }
                        else
                        {
                            echo sit_upgrade_plugin_check(FALSE, $application_version);

                            echo "<p>Your database schema is v".number_format($installed_version, 2);
                            if ($installed_version < $application_version)
                            {
                                echo ", after making a backup you should upgrade your schema to v{$application_version}";
                            }
                            echo "</p>";

                            // Display SQL schema changes for git versions
                            if (mb_substr($application_revision, 0, 3) == 'git')
                            {
                                echo "<p>You are running a <a target='_blank' href='http://sitracker.org/wiki/Development/Unreleased_Versions'>GIT version</a>, you should check that you have all of these schema changes: (some may have been added recently)</p>";
                                echo "<div style='border: 1px solid red;padding:10px; background: #FFFFC0; font-family:monospace; font-size: 80%; height:200px; overflow:scroll;'>";
                                echo nl2br($upgrade_schema[$installed_version * 100]);
                                echo "</div>";
                            }

                            if (is_array($upgrade_schema[$installed_version * 100]))
                            {
                                foreach ($upgrade_schema[$installed_version * 100] AS $possible_schema_updates => $nothing)
                                {
                                    $possible_schema_updates = mb_substr($possible_schema_updates, 1);
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
                                $email = mysql_real_escape_string($_POST['email']);
                                echo create_admin_user($password, $email);
                            }
                            else
                            {
                                echo "<p class='error'>Admin account not created, the passwords you entered did not match.</p>";
                            }
                        }
                        
                        if ($_REQUEST['action'] == 'createinitialdata') 
                        {
                            // Create initial set etc... TODO
                            // Maintenance - need to created based on data entered

                            $sitename = cleanvar($_REQUEST['sitename']);
                            $sitedepartment = cleanvar($_REQUEST['sitedepartment']);
                            $siteaddress1 = cleanvar($_REQUEST['siteaddress1']);
                            $siteaddress2 = cleanvar($_REQUEST['siteaddress2']);
                            $sitecity = cleanvar($_REQUEST['sitecity']);
                            $sitecounty = cleanvar($_REQUEST['sitecounty']);
                            $sitecountry = cleanvar($_REQUEST['sitecountry']);
                            $sitepostcode = cleanvar($_REQUEST['sitepostcode']);

                            // Contact
                            $courtesytitle = cleanvar($_REQUEST['courtesytitle']);
                            $contactforenames = cleanvar($_REQUEST['contactforenames']);
                            $contactsurname = cleanvar($_REQUEST['contactsurname']);
                            $contactjobtitle = cleanvar($_REQUEST['contactjobtitle']);
                            $contactdepartment = cleanvar($_REQUEST['contactdepartment']);
                            $contactemail = cleanvar($_REQUEST['contactemail']);
                            $contactphone = cleanvar($_REQUEST['contactphone']);
                            $contactmobile = cleanvar($_REQUEST['contactmobile']);

                            // Product
                            $productvendor = cleanvar($_REQUEST['productvendor']);
                            $productname = cleanvar($_REQUEST['productname']);

                            // Resellers
                            $reseller_name = cleanvar($_REQUEST['reseller_name']);
                            $_SESSION['formdata']['setupinitialdata'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);
                            
                            $errors = 0;
                            if(empty($sitename))
                            {
                                $_SESSION['formerrors']['setupinitialdata']['sitename'] = sprintf($strFieldMustNotBeBlank, 'Site Name');
                                $errors++;
                            }
                            
                            if(empty($siteaddress1))
                            {
                                $_SESSION['formerrors']['setupinitialdata']['siteaddress1'] = sprintf($strFieldMustNotBeBlank, 'Site Address 1');
                                $errors++;
                            }
                            
                            if(empty($contactforenames))
                            {
                                $_SESSION['formerrors']['setupinitialdata']['contactforenames'] = sprintf($strFieldMustNotBeBlank, 'Contact Forenames');
                                $errors++;
                            }
                            
                            if(empty($contactsurname))
                            {
                                $_SESSION['formerrors']['setupinitialdata']['contactsurname'] = sprintf($strFieldMustNotBeBlank, 'Contact Forenames');
                                $errors++;
                            }
                            
                            if(empty($contactemail))
                            {
                                $_SESSION['formerrors']['setupinitialdata']['contactemail'] = sprintf($strFieldMustNotBeBlank, 'Contact Email');
                                $errors++;
                            }
                            
                            if(empty($productvendor))
                            {
                                $_SESSION['formerrors']['setupinitialdata']['productvendor'] = sprintf($strFieldMustNotBeBlank, 'Product Vendor');
                                $errors++;
                            }
                            
                            if(empty($productname))
                            {
                                $_SESSION['formerrors']['setupinitialdata']['productname'] = sprintf($strFieldMustNotBeBlank, 'Product Name');
                                $errors++;
                            }
                            
                            if(empty($reseller_name))
                            {
                                $_SESSION['formerrors']['setupinitialdata']['reseller_name'] = sprintf($strFieldMustNotBeBlank, 'Reseller Name');
                                $errors++;
                            }
                            
                            $sitedepartment = convert_string_null_safe($sitedepartment);
                            $siteaddress2 = convert_string_null_safe($siteaddress2);
                            $sitecity = convert_string_null_safe($sitecity);
                            $sitecounty = convert_string_null_safe($sitecounty);
                            $sitecountry = convert_string_null_safe($sitecountry);
                            $sitepostcode = convert_string_null_safe($sitepostcode);
                            
                            // Contact
                            $courtesytitle = convert_string_null_safe($courtesytitle);
                            $contactjobtitle = convert_string_null_safe($contactjobtitle);
                            $contactdepartment = convert_string_null_safe($contactdepartment);
                            $contactphone = convert_string_null_safe($contactphone);
                            $contactmobile = convert_string_null_safe($contactmobile);
                            
                            
                            if ($errors == 0)
                            {
                                $sql = "INSERT INTO `{$dbSites}` (`name`, `department`, `address1`, `address2`, `city`, `county`,
                                `country`, `postcode`, `notes`, `typeid`, `freesupport`, `licenserx`,
                                 `owner`) VALUES ('{$sitename}', {$sitedepartment}, '{$siteaddress1}', {$siteaddress2},
                                {$sitecity}, {$sitecounty}, {$sitecountry}, {$sitepostcode}, 'Created during setup', 1, 0, 0, 0)";
                                $result = mysql_query($sql);
                                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                                $siteid = mysql_insert_id();

                                $username = mb_strtolower(mb_substr($contactsurname, 0, strcspn($contactsurname, " "), 'UTF-8'));
                                $sql =  "INSERT INTO `{$dbContacts}` (`username`, `password`, `forenames`, `surname`, `jobtitle`, `courtesytitle`, `siteid`, `email`, `phone`, `mobile`, `department`, `timestamp_added`, `timestamp_modified`) VALUES
                                ('{$username}', MD5(RAND()), '{$contactforenames}', '{$contactsurname}', {$contactjobtitle}, {$courtesytitle}, {$siteid}, '{$contactemail}', {$contactphone}, {$contactmobile}, {$contactdepartment}, 1132930556, 1187360933)";
                                $result = mysql_query($sql);
            					if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            					$contactid = mysql_insert_id();
            					$username = $username . $newid;
            					$sql = "UPDATE `{$dbContacts}` SET username='{$username}' WHERE id='{$contactid}'";
            					                            
                                $sql = "INSERT INTO `{$dbProducts}` (vendorid, name) VALUES ({$productvendor},'{$productname}')";
                                $result = mysql_query($sql);
                                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                                $productid = mysql_insert_id();
                                
                                $sql = "INSERT INTO `{$dbResellers}` (name) VALUES ('{$reseller_name}')";
                                $result = mysql_query($sql);
                                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                                $resellerid = mysql_insert_id();
                                
                                $expirydate = strtotime("+1 year");
                                
                                $sql = "INSERT INTO `{$dbMaintenance}` (site, product, reseller, expirydate, licence_quantity, licence_type, incident_quantity, incidents_used, notes, admincontact, term, servicelevel, incidentpoolid) ";
                                $sql .= "VALUES ({$siteid},{$productid},{$resellerid},{$expirydate},1,4,0,0,'Created during the installer',1,'no','standard',0)";
                                $result = mysql_query($sql);
                                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                                
                                $_SESSION['promptinitialdata'] = FALSE;
                                clear_form_data('setupinitialdata');
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
                        elseif (@ini_get('register_globals') == 1 OR strtolower(@ini_get('register_globals')) == 'on')
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
                        elseif ($_SESSION['promptinitialdata'] == TRUE)
                        {
                            // Setup initial data
                            echo "<p>Please setup the initial data.<br /><b>NOTE:</b> The following is only a subset of the possibe data</p>";
                            
                            echo show_form_errors('setupinitialdata');
                            clear_form_errors('setupinitialdata');
                            
                            echo "<form name='setupinitialdata' action='setup.php' method='post'>\n";

                            // Site
                            echo "<p>Site";
                            echo "<table>";
                            echo "<tr><th>Site Name</th><td><input maxlength='255' class='required' name='sitename' size='30'  ";
                            if ($_SESSION['formdata']['setupinitialdata']['sitename'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['sitename']}' ";
                            }
                            echo "/> <span class='required'>Required</span></td></tr>\n";
                            echo "<tr><th>Department</th><td><input maxlength='255' name='sitedepartment' size='30' ";
                            if ($_SESSION['formdata']['setupinitialdata']['sitedepartment'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['sitedepartment']}' ";
                            }
                            echo "/></td></tr>\n";
                            echo "<tr><th>Address 1</th><td><input class='required' maxlength='255' name='siteaddress1' size='30' ";
                            if ($_SESSION['formdata']['setupinitialdata']['siteaddress1'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['siteaddress1']}' ";
                            }
                            echo "/> <span class='required'>Required</span></td></tr>\n";
                            echo "<tr><th>Address 2</th><td><input maxlength='255' name='siteaddress2' size='30'  ";
                            if ($_SESSION['formdata']['setupinitialdata']['siteaddress2'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['siteaddress2']}' ";
                            }
                            echo "/></td></tr>\n";
                            echo "<tr><th>City</th><td><input maxlength='255' name='sitecity' size='30' ";
                            if ($_SESSION['formdata']['setupinitialdata']['sitecity'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['sitecity']}' ";
                            }
                            echo "/></td></tr>\n";
                            echo "<tr><th>County</th><td><input maxlength='255' name='sitecounty' size='30' ";
                            if ($_SESSION['formdata']['setupinitialdata']['sitecounty'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['sitecounty']}' ";
                            }
                            echo "/></td></tr>\n";
                            echo "<tr><th>Country</th><td>";
                            if ($_SESSION['formdata']['setupinitialdata']['sitecountry'] != '')
                            {
                                echo country_drop_down('sitecountry', $_SESSION['formdata']['new_site']['sitecountry']);
                            }
                            else
                            {
                                echo country_drop_down('sitecountry', $CONFIG['home_country']);
                            }
                            echo "</td></tr>\n";
                            echo "<tr><th>Postcode</th><td><input maxlength='255' name='sitepostcode' size='30' ";
                            if ($_SESSION['formdata']['setupinitialdata']['sitepostcode'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['sitepostcode']}' ";
                            }
                            echo "/></td></tr>\n";
                            echo "</table>";
                            echo "</p>";

                            // Contact
                            echo "<p>Contact";
                            echo "<table>";
                            echo "<tr><th>Name</th>\n";                           
                            echo "<td>";
                            echo "\n<table><tr><td align='center'>Title<br />";
                            echo "<input maxlength='50' name='courtesytitle' size='7' ";
                            if ($_SESSION['formdata']['setupinitialdata']['courtesytitle'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['courtesytitle']}' ";
                            }
                            echo "/></td>\n";
                            echo "<td align='center'>Forenames<br />";
                            echo "<input class='required' maxlength='100' name='contactforenames' size='15' ";
                            if ($_SESSION['formdata']['setupinitialdata']['contactforenames'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['contactforenames']}' ";
                                }
                            echo "/></td>\n";
                            echo "<td align='center'>Surname<br />";
                            echo "<input class='required' maxlength='100' name='contactsurname' size='20' ";
                            if ($_SESSION['formdata']['setupinitialdata']['contactsurname'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['contactsurname']}' ";
                            }
                            echo "/> <span class='required'>Required</span></td></tr>\n";
                            echo "</table>\n</td></tr>\n";
                            echo "<tr><th>Job Title</th><td><input maxlength='255' name='contactjobtitle' size='35' ";
                            if ($_SESSION['formdata']['setupinitialdata']['contactjobtitle'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['contactjobtitle']}' ";
                            }
                            echo "/></td></tr>\n";
                            echo "<tr><th>Department</th><td><input maxlength='255' name='contactdepartment' size='35' ";
                            if ($_SESSION['formdata']['setupinitialdata']['contactdepartment'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['contactdepartment']}' ";
                            }
                            echo "/></td></tr>\n";
                            echo "<tr><th>Email</th><td><input class='required' maxlength='100' name='contactemail' size='35' ";
                            if ($_SESSION['formdata']['setupinitialdata']['contactemail'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['contactemail']}' ";
                            }
                            echo "/> <span class='required'>Required</span></td></tr>\n";
                            echo "<tr><th>Telephone</th><td><input maxlength='50' name='contactphone' size='35' ";
                            if ($_SESSION['formdata']['setupinitialdata']['contactphone'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['contactphone']}' ";
                            }
                            echo "/></td></tr>\n";
                            echo "<tr><th>Mobile</th><td><input maxlength='100' name='contactmobile' size='35' ";
                            if ($_SESSION['formdata']['setupinitialdata']['contactmobile'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['contactmobile']}' ";
                            }
                            echo "/></td></tr>\n";
                            echo "</table>";
                            echo "</p>";

                            // Product
                            echo "<p>Product:";
                            echo "<table class='maintable'>";
                            echo "<tr><th>Vendor</th><td>";
                            if ($_SESSION['formdata']['setupinitialdata']['productvendor'] != '')
                            {
                                echo vendor_drop_down('productvendor', $_SESSION['formdata']['setupinitialdata']['productvendor'], TRUE);
                            }
                            else 
                            {
                                echo vendor_drop_down('productvendor', 0, TRUE);
                            }                            
                            echo " <span class='required'>Required</span></td></tr>\n";
                            echo "<tr><th>Product</th><td><input maxlength='50' name='productname' size='40' class='required'  ";
                            if ($_SESSION['formdata']['setupinitialdata']['productname'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['productname']}' ";
                            }
                            echo "/> <span class='required'>Required</span></td></tr>\n";
                            echo "</table>\n";
                            echo "</p>";

                            // Resellers
                            echo "<p>Reseller";
                            echo "<table class='maintable vertical'>";
                            echo "<tr><th>Name</th><td><input type='text' name='reseller_name' class='required' ";
                            if ($_SESSION['formdata']['setupinitialdata']['reseller_name'])
                            {
                                echo "value='{$_SESSION['formdata']['setupinitialdata']['reseller_name']}' ";
                            }
                            echo "/> <span class='required'>Required</span></td></tr>";
                            echo "</table>";
                            echo "</p>";

                            echo "<input type='hidden' name='action' value='createinitialdata' />";
                            echo setup_button('', 'Add Data');
                            echo "</form>";
                            
                            clear_form_data('setupinitialdata');
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
            else
            {
                echo "<p class='error'>Fatal errors exist in your environment, please fix and rerun the setup</p>";
            }

        }
}

include (APPLICATION_INCPATH . 'setupfooter.inc.php');

?>