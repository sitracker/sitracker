<?php
// setup.inc.php - functions used during seup of SiT
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
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
        if (mb_substr($var, mb_strlen($var) - mb_strlen($poss)) == $poss)
        {
            $recognised = TRUE;
        }
    }
    return $recognised;
}


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
                    if (mb_strlen($CONFIG[$setupvar]) < 65)
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
            if ($schemaversion != '0') $schemaversion = mb_substr($schemaversion, 1);

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
                            $html .= "<p class='{$severity}'>";
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
 * @return A form with a button
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


/**
 * Runs the install script for all installed dashboards
 * @author Paul Heaney
 * @return int number of errors encountered 
 */
function install_dashboard_components()
{
    global  $dbDashboard;
    $sql = "SELECT * FROM `{$dbDashboard}` WHERE enabled = 'true'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    //echo "<h2>Dashboard</h2>";
    
    $errors = array();
    
    while ($dashboardnames = mysql_fetch_object($result))
    {
        $version = 1;
        include (APPLICATION_PLUGINPATH . "dashboard_{$dashboardnames->name}.php");
        $func = "dashboard_{$dashboardnames->name}_install";

        if (function_exists($func))
        {
            if (!$func()) $errors[] = $dashboardnames->name;
        }
    }
    
    return $errors;
}


/**
 * Upgrades all installed dashlets to the current version
 * @author Paul Heaney
 * @return String HTML status of the upgrade
 */
function upgrade_dashlets()
{
    $sql = "SELECT * FROM `{$GLOBALS['dbDashboard']}` WHERE enabled = 'true'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    $html = '';

    while ($dashboardnames = mysql_fetch_object($result))
    {
        $version = 1;
        include (APPLICATION_PLUGINPATH . "dashboard_{$dashboardnames->name}.php");
        $func = "dashboard_{$dashboardnames->name}_get_version";

        if (function_exists($func))
        {
            $version = $func();
        }

        if ($version > $dashboardnames->version)
        {
            $html .= "<p>Upgrading {$dashboardnames->name} dashlet to v{$version}...</p>";
            // apply all upgrades since running version
            $upgrade_func = "dashboard_{$dashboardnames->name}_upgrade";

            if (function_exists($upgrade_func))
            {
                $dashboard_schema = $upgrade_func();
                for ($i = $dashboardnames->version; $i <= $version; $i++)
                {
                    setup_exec_sql($dashboard_schema[$i]);
                }

                $upgrade_sql = "UPDATE `{$GLOBALS['dbDashboard']}` SET version = '{$version}' WHERE id = {$dashboardnames->id}";
                mysql_query($upgrade_sql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);

                $html .= "<p>{$dashboardnames->name} upgraded</p>";
            }
            else
            {
                $html .= "<p>No upgrade function for {$dashboardnames->name}</p>";
            }
        }
    }

    return $html;
}


/**
 * Upgrades the schema from one version to the current
 * Prints upgrade status as it goes
 * @author Ivan Lucas
 * @param int $installed_version The currently installed version
 * @return int The version of the schema which is installed following the upgrade 
 */
function upgrade_schema($installed_version)
{
    global $application_version, $upgrade_schema;

    for ($v = (($installed_version * 100) + 1); $v <= ($application_version * 100); $v++)
    {
        $html = '';
        if (!empty($upgrade_schema[$v]))
        {
            $newversion = number_format(($v / 100), 2);
            echo "<p>Updating schema from {$installed_version} to v{$newversion}&hellip;</p>";

            $errors = setup_exec_sql($upgrade_schema[$v]);

            // Update the system version
            if ($errors < 1)
            {
                $html .= update_sit_version_number($newversion);
                $installed_version = $newversion;
            }
            else
            {
                $html .= "<p class='error'><strong>Summary</strong>: {$errors} Error(s) occurred while updating the schema, ";
                $html .= "please resolve the problems reported and then try running setup again.</p>";
            }
            echo $html;
        }
    }

    return $installed_version;
}


/**
 * Create the admin user
 * @author Ivan Lucas
 * @param String $password  The admin users password - in plain text
 * @param String $email The admin users email address
 * @return String HTML if an error occurs
 */
function create_admin_user($password, $email)
{
    $html = '';
    $password = md5($password);
    $sql = "INSERT INTO `{$GLOBALS['dbUsers']}` (`id`, `username`, `password`, `realname`, `roleid`, `title`, `signature`, `email`, `status`, `lastseen`) ";
    $sql .= "VALUES (1, 'admin', '{$password}', 'Administrator', 1, 'Administrator', 'Regards,\r\n\r\nSiT Administrator', '{$email}', '1', NOW());";
    mysql_query($sql);
    if (mysql_error())
    {
       trigger_error(mysql_error(), E_USER_WARNING);
       $html .= "<p><strong>FAILED:</strong> {$sql}</p>";
    }

    return $html;
}


/**
 * Updates the version number in the systems table
 * @author Ivan Lucas
 * @param String $version The version to set as running
 * @return String HTML
 */
function update_sit_version_number($version)
{
    $html = '';
    $sql = "REPLACE INTO `{$GLOBALS['dbSystem']}` ( `id`, `version`) VALUES (0, {$version})";
    mysql_query($sql);
    if (mysql_error())
    {
        $html .= "<p class='error'>Could not store new schema version number '{$version}'. ".mysql_error()."</p>";
    }
    else
    {
        $html .= "<p>Schema successfully updated to version {$version}.</p>";
    }

    return $html;
}


/**
 * Gets the currently running schema version
 * @author Ivan Lucas
 * @return float The running version, 0 if no details can be found
 */
function current_schema_version()
{
    $$installed_version = 0;
    $sql = "SELECT `version` FROM `{$GLOBALS['dbSystem']}` WHERE id = 0";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        list($installed_version) = mysql_fetch_row($result);
    }

    return $installed_version;
}


/**
 * Fetches the columns currently in the table
 * @author Nico du Toit
 * @param string $table_name The name of the table we want the column names for
 * @param string $column_name The name of the column to verify exists in the table
 * @return boolean True if the column exists, False if it does not exist
 * @note We now don't handle any errors from this query as
         we're only interested in either TRUE or FALSe results here. (INL)
 */
function setup_check_column_exists($table_name, $column_name)
{
    $sql = "SHOW COLUMNS FROM {$table_name} ";
    $result = mysql_query($sql);
    while ($tb_result = mysql_fetch_row($result))
    {
        $column_array[] = $tb_result[0];
    }

    if (in_array($column_name, $column_array)) return TRUE;
    else return FALSE;
}


/**
*
* Checks that the current user has the necessary privileges for setup
* @author Paul Heaney
* @param array $privs - Array of the required privileges
* @return array - Array of the privileges missing, an empty array is returned when all privilelges are present
*/
function check_mysql_privileges($privs)
{
    $rtn = array();

    $granted = array();

    $sql = "SHOW GRANTS FOR CURRENT_USER()";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    while ($row = mysql_fetch_row($result))
    {
        if (preg_match('/(GRANT )(.*) ON.*$/', $row[0], $m))
        {
            $m[2] = str_replace(", ", ",", $m[2]);
            $granted = array_merge(explode(",", $m[2]), $granted);
        }
    }

    if (in_array('ALL', $granted))
    {
        // User has all privileges
        $rtn = array();
        return $rtn;
    }
    
    if (in_array('ALL PRIVILEGES', $granted))
    {
        // User has all privileges
        $rtn = array();
        return $rtn;
    }

    foreach ($privs AS $p)
    {
        if (!in_array($p, $granted))
        {
            $rtn[] = $p;
        }
    }

    return $rtn;
}


/**
 * Looks through the schema upgrade to see what rights are required and returns them as an array
 * @author Paul Heaney
 * @param int $installed_version The version currently installed
 * @return array Strings - The permissions required to upgrade
 */
function upgrade_required_perms($installed_version)
{
    global $installed_schema, $upgrade_schema, $application_version;

    $required = array();

    for ($v = (($installed_version * 100) + 1); $v <= ($application_version * 100); $v++)
    {
        if (!empty($upgrade_schema[$v]))
        {
            $newversion = number_format(($v / 100), 2);

            $sqlquerylist = $upgrade_schema[$v]; 

            if (!is_array($sqlquerylist)) $sqlquerylist = array($sqlquerylist);

            // Loop around the queries
            foreach ($sqlquerylist AS $schemaversion => $queryelement)
            {
                if ($schemaversion != '0') $schemaversion = mb_substr($schemaversion, 1);

                if ($schemaversion == 0 OR $installed_schema < $schemaversion)
                {
                    $sqlqueries = explode( ';', $queryelement);
                    // We don't need the last entry it's blank, as we end with a ;
                    array_pop($sqlqueries);
                    $errors = 0;
                    foreach ($sqlqueries AS $sql)
                    {
                        $p = explode(" ", $sql);
                        $permission = trim($p[0]);
                        if (!empty($permission) AND $permission != '--')
                        {
                            $required[$permission] = $permission;
                        }
                    }
                }
            }
        }
    }
    return $required;
}


function upgrade_390_migrate_user_config()
{
    global $CONFIG;
    $dbInterfaceStyles = "{$CONFIG['db_tableprefix']}interfacestyles";

    $errors = 0;

    $sql_oldconfig = "SELECT id, var_emoticons, var_utc_offset, var_i18n, var_style, var_incident_refresh, var_update_order, var_num_updates_view FROM `{$GLOBALS['dbUsers']}`";
    $result_oldconfig = mysql_query($sql_oldconfig);
    if (mysql_error())
    {
        trigger_error(mysql_error(), E_USER_WARNING);
        echo "error ".mysql_error()."\n\n";
        $errors++;
    }

    while ($obj = mysql_fetch_object($result_oldconfig))
    {
        $s = array();
        if (!empty($obj->var_emoticons)) $s[] = "({$obj->id}, 'show_emoticons', '".strtoupper($obj->var_emoticons)."')";
        if (!empty($obj->var_utc_offset)) $s[] = "({$obj->id}, 'utc_offset', '{$obj->var_utc_offset}')";
        if (!empty($obj->var_i18n)) $s[] = "({$obj->id}, 'language', '{$obj->var_i18n}')";
        if (!empty($obj->var_incident_refresh)) $s[] = "({$obj->id}, 'incident_refresh', '{$obj->var_incident_refresh}')";
        if (!empty($obj->var_update_order)) $s[] = "({$obj->id}, 'incident_log_order', '{$obj->var_update_order}')";
        if (!empty($obj->var_num_updates_view)) $s[] = "({$obj->id}, 'updates_per_page', '{$obj->var_num_updates_view}')";

        $sql_style = "SELECT iconset FROM `{$dbInterfaceStyles}` WHERE id = {$obj->var_style}";
        $result_style = mysql_query($sql_style);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        if ($obj_style = mysql_fetch_object($result_style))
        {
            $s[] = "({$obj->id}, 'iconset', '{$obj_style->iconset}')";
        }

        if (!empty($s))
        {
            $sql_insert = "INSERT INTO `{$GLOBALS['dbUserConfig']}` VALUES ".implode(", ", $s);
            $result_insert = mysql_query($sql_insert);
            if (mysql_error())
            {
                trigger_error(mysql_error(), E_USER_WARNING);
                $errors++;
            }
        }
    }

    if ($errors == 0)
    {
        $sql_alter = "ALTER TABLE `{$GLOBALS['dbUsers']}` DROP COLUMN var_incident_refresh, DROP COLUMN var_update_order, DROP COLUMN var_num_updates_view, ";
        $sql_alter .= "DROP COLUMN var_style, DROP COLUMN var_hideautoupdates, DROP COLUMN var_hideheader, DROP COLUMN var_monitor, ";
        $sql_alter .= "DROP COLUMN var_i18n, DROP COLUMN var_utc_offset, DROP COLUMN var_emoticons ";
        $result_alter = mysql_query($sql_alter);
        if (mysql_error())
        {
            trigger_error(mysql_error(), E_USER_WARNING);
        }
        $sql_drop = "DROP DATABASE IF EXISTS `{$dbInterfaceStyles}`";
        $result_drop = mysql_query($sql_drop);
        if (mysql_error())
        {
            trigger_error(mysql_error(), E_USER_WARNING);
        }
    }
    else
    {
        echo "Error migrating user config settings, old data retained";
    }
}


function update_390_country_list()
{
    // array[iso_code] = old name
    $countryList['AL'] = 'ALBANIA';
    $countryList['DZ'] = 'ALGERIA';
    $countryList['AS'] = 'AMERICAN SAMOA';
    $countryList['AD'] = 'ANDORRA';
    $countryList['AO'] = 'ANGOLA';
    $countryList['AI'] = 'ANGUILLA';
    $countryList['AG'] = 'ANTIGUA';
    $countryList['AR'] = 'ARGENTINA';
    $countryList['AM'] = 'ARMENIA';
    $countryList['AW'] = 'ARUBA';
    $countryList['AU'] = 'AUSTRALIA';
    $countryList['AT'] = 'AUSTRIA';
    $countryList['AZ'] = 'AZERBAIJAN';
    $countryList['BS'] = 'BAHAMAS';
    $countryList['BH'] = 'BAHRAIN';
    $countryList['BD'] = 'BANGLADESH';
    $countryList['BB'] = 'BARBADOS';
    $countryList['BY'] = 'BELARUS';
    $countryList['BE'] = 'BELGIUM';
    $countryList['BZ'] = 'BELIZE';
    $countryList['BJ'] = 'BENIN';
    $countryList['BM'] = 'BERMUDA';
    $countryList['BT'] = 'BHUTAN';
    $countryList['BO'] = 'BOLIVIA';
    $countryList['BQ'] = 'BONAIRE';
    $countryList['BA'] = 'BOSNIA HERZEGOVINA';
    $countryList['BW'] = 'BOTSWANA';
    $countryList['BR'] = 'BRAZIL';
    $countryList['BN'] = 'BRUNEI';
    $countryList['BG'] = 'BULGARIA';
    $countryList['BF'] = 'BURKINA FASO';
    $countryList['BI'] = 'BURUNDI';
    $countryList['KH'] = 'CAMBODIA';
    $countryList['CM'] = 'CAMEROON';
    $countryList['CA'] = 'CANADA';
    $countryList['IC'] = 'CANARY ISLANDS'; // Only reserved (Exceptional Reservation)
    $countryList['CV'] = 'CAPE VERDE ISLANDS';
    $countryList['KY'] = 'CAYMAN ISLANDS';
    $countryList['CF'] = 'CENTRAL AFRICAN REPUBLIC';
    $countryList['TD'] = 'CHAD';
    $countryList['JE'] = 'CHANNEL ISLANDS'; // Channel Islands = Jersey + Guernsey which both have ISO codes (JE and GG) have picked Jersey as its the island with greater population  
    $countryList['CL'] = 'CHILE';
    $countryList['TW'] = 'CHINA';
    $countryList['CO'] = 'COLOMBIA';
    $countryList['KM'] = 'COMOROS ISLANDS'; // ?
    $countryList['CD'] = 'CONGO';
    $countryList['CK'] = 'COOK ISLANDS';
    $countryList['CR'] = 'COSTA RICA';
    $countryList['HR'] = 'CROATIA';
    $countryList['CU'] = 'CUBA';
    $countryList['CW'] = 'CURACAO';
    $countryList['CY'] = 'CYPRUS';
    $countryList['CZ'] = 'CZECH REPUBLIC';
    $countryList['DK'] = 'DENMARK';
    $countryList['DJ'] = 'DJIBOUTI';
    $countryList['DO'] = 'DOMINICA';
    $countryList['DO'] = 'DOMINICAN REPUBLIC';
    $countryList['EC'] = 'ECUADOR';
    $countryList['EG'] = 'EGYPT';
    $countryList['SV'] = 'EL SALVADOR';
    $countryList['GQ'] = 'EQUATORIAL GUINEA';
    $countryList['ER'] = 'ERITREA';
    $countryList['EE'] = 'ESTONIA';
    $countryList['ET'] = 'ETHIOPIA';
    $countryList['FO'] = 'FAROE ISLANDS';
    $countryList['FJ'] = 'FIJI ISLANDS';
    $countryList['FI'] = 'FINLAND';
    $countryList['FR'] = 'FRANCE';
    $countryList['GN'] = 'FRENCH GUINEA'; // Now just Guinea
    $countryList['GA'] = 'GABON';
    $countryList['GM'] = 'GAMBIA';
    $countryList['GS'] = 'GEORGIA';
    $countryList['DE'] = 'GERMANY';
    $countryList['GH'] = 'GHANA';
    $countryList['GI'] = 'GIBRALTAR';
    $countryList['GR'] = 'GREECE';
    $countryList['GL'] = 'GREENLAND';
    $countryList['GD'] = 'GRENADA';
    $countryList['GP'] = 'GUADELOUPE';
    $countryList['GU'] = 'GUAM';
    $countryList['GT'] = 'GUATEMALA';
    $countryList['GN'] = 'GUINEA REPUBLIC';
    $countryList['GW'] = 'GUINEA-BISSAU';
    $countryList['GY'] = 'GUYANA';
    $countryList['HT'] = 'HAITI';
    $countryList['HN'] = 'HONDURAS REPUBLIC';
    $countryList['HK'] = 'HONG KONG';
    $countryList['HU'] = 'HUNGARY';
    $countryList['IS'] = 'ICELAND';
    $countryList['IN'] = 'INDIA';
    $countryList['ID'] = 'INDONESIA';
    $countryList['IR'] = 'IRAN';
    $countryList['IE'] = 'IRELAND, REPUBLIC';
    $countryList['IL'] = 'ISRAEL';
    $countryList['IT'] = 'ITALY';
    $countryList['CI'] = 'IVORY COAST';
    $countryList['JM'] = 'JAMAICA';
    $countryList['JP'] = 'JAPAN';
    $countryList['JO'] = 'JORDAN';
    $countryList['KZ'] = 'KAZAKHSTAN';
    $countryList['KE'] = 'KENYA';
    $countryList['KI'] = 'KIRIBATI, REP OF';
    $countryList['KR'] = 'KOREA, SOUTH';
    $countryList['KW'] = 'KUWAIT';
    $countryList['KG'] = 'KYRGYZSTAN';
    $countryList['LA'] = 'LAOS'; // Lao Peoples Democratic Republic
    $countryList['LV'] = 'LATVIA';
    $countryList['LB'] = 'LEBANON';
    $countryList['LS'] = 'LESOTHO';
    $countryList['LR'] = 'LIBERIA';
    $countryList['LY'] = 'LIBYA';
    $countryList['LI'] = 'LIECHTENSTEIN';
    $countryList['LT'] = 'LITHUANIA';
    $countryList['LU'] = 'LUXEMBOURG';
    $countryList['MO'] = 'MACAU'; // Now Macau
    $countryList['MK'] = 'MACEDONIA';
    $countryList['MG'] = 'MADAGASCAR';
    $countryList['MW'] = 'MALAWI';
    $countryList['MY'] = 'MALAYSIA';
    $countryList['MV'] = 'MALDIVES';
    $countryList['SO'] = 'MALI';
    $countryList['MT'] = 'MALTA';
    $countryList['MH'] = 'MARSHALL ISLANDS';
    $countryList['MQ'] = 'MARTINIQUE';
    $countryList['MR'] = 'MAURITANIA';
    $countryList['MU'] = 'MAURITIUS';
    $countryList['MX'] = 'MEXICO';
    $countryList['MD'] = 'MOLDOVA, REP OF';
    $countryList['MC'] = 'MONACO';
    $countryList['MN'] = 'MONGOLIA';
    $countryList['MS'] = 'MONTSERRAT';
    $countryList['MA'] = 'MOROCCO';
    $countryList['MZ'] = 'MOZAMBIQUE';
    $countryList['MM'] = 'MYANMAR';
    $countryList['NA'] = 'NAMIBIA';
    $countryList['NR'] = 'NAURU, REP OF';
    $countryList['NP'] = 'NEPAL';
    $countryList['NL'] = 'NETHERLANDS';
    $countryList['KN'] = 'NEVIS';
    $countryList['NC'] = 'NEW CALEDONIA';
    $countryList['NZ'] = 'NEW ZEALAND';
    $countryList['NI'] = 'NICARAGUA';
    $countryList['NG'] = 'NIGER';
    $countryList['NG'] = 'NIGERIA';
    $countryList['NU'] = 'NIUE';
    $countryList['NO'] = 'NORWAY';
    $countryList['RO'] = 'OMAN';
    $countryList['PK'] = 'PAKISTAN';
    $countryList['PA'] = 'PANAMA';
    $countryList['PG'] = 'PAPUA NEW GUINEA';
    $countryList['PY'] = 'PARAGUAY';
    $countryList['PE'] = 'PERU';
    $countryList['PH'] = 'PHILLIPINES';
    $countryList['PL'] = 'POLAND';
    $countryList['PT'] = 'PORTUGAL';
    $countryList['PR'] = 'PUERTO RICO';
    $countryList['QA'] = 'QATAR';
    $countryList['RE'] = 'REUNION ISLAND';
    $countryList['RO'] = 'ROMANIA';
    $countryList['RU'] = 'RUSSIAN FEDERATION';
    $countryList['RW'] = 'RWANDA';
    $countryList['MP'] = 'SAIPAN'; // Northern Mariana Islands
    $countryList['ST'] = 'SAO TOME & PRINCIPE';
    $countryList['SA'] = 'SAUDI ARABIA';
    $countryList['SN'] = 'SENEGAL';
    $countryList['SC'] = 'SEYCHELLES';
    $countryList['SL'] = 'SIERRA LEONE';
    $countryList['SG'] = 'SINGAPORE';
    $countryList['SK'] = 'SLOVAKIA';
    $countryList['SI'] = 'SLOVENIA';
    $countryList['SB'] = 'SOLOMON ISLANDS';
    $countryList['ZA'] = 'SOUTH AFRICA';
    $countryList['ES'] = 'SPAIN';
    $countryList['LK'] = 'SRI LANKA';
    $countryList['BL'] = 'ST BARTHELEMY';
    $countryList['BQ'] = 'ST EUSTATIUS'; // Bonaire, Sint Eustatius and Saba
    $countryList['KN'] = 'ST KITTS';
    $countryList['LC'] = 'ST LUCIA';
    $countryList['SX'] = 'ST MAARTEN'; // SX for dutch part, ML for French part
    $countryList['VC'] = 'ST VINCENT';
    $countryList['SD'] = 'SUDAN';
    $countryList['SR'] = 'SURINAME';
    $countryList['SZ'] = 'SWAZILAND';
    $countryList['SE'] = 'SWEDEN';
    $countryList['CH'] = 'SWITZERLAND';
    $countryList['SY'] = 'SYRIA';
    $countryList['PF'] = 'TAHITI'; // French polynesia
    $countryList['TW'] = 'TAIWAN';
    $countryList['TJ'] = 'TAJIKISTAN';
    $countryList['TZ'] = 'TANZANIA';
    $countryList['TH'] = 'THAILAND';
    $countryList['TG'] = 'TOGO';
    $countryList['TO'] = 'TONGA';
    $countryList['TT'] = 'TRINIDAD & TOBAGO';
    $countryList['TR'] = 'TURKEY';
    $countryList['TM'] = 'TURKMENISTAN';
    $countryList['TC'] = 'TURKS & CAICOS ISLANDS';
    $countryList['TV'] = 'TUVALU';
    $countryList['UG'] = 'UGANDA';
    $countryList['UA'] = 'UKRAINE';
    $countryList['GB'] = 'UNITED KINGDOM';
    $countryList['UM'] = 'UNITED STATES';
    $countryList['UY'] = 'URUGUAY';
    $countryList['AE'] = 'UTD ARAB EMIRATES';
    $countryList['UZ'] = 'UZBEKISTAN';
    $countryList['VU'] = 'VANUATU';
    $countryList['VE'] = 'VENEZUELA';
    $countryList['VN'] = 'VIETNAM';
    $countryList['VI'] = 'VIRGIN ISLANDS';
    $countryList['VG'] = 'VIRGIN ISLANDS (UK)';
    $countryList['EH'] = 'WESTERN SAMOA';
    $countryList['YE'] = 'YEMAN, REP OF';
    $countryList['RS'] = 'YUGOSLAVIA'; // Serbia picked, Bosnia and Herzegovina and Croatia where already on the list so Serbia was choosen based on size, anyone witha Yugoslavia address probably needs revisiting
    $countryList['CD'] = 'ZAIRE';  // Now DR of the Congo
    $countryList['ZM'] = 'ZAMBIA';
    $countryList['ZW'] = 'ZIMBABWE';

    // TODO flag if any countries listed as Channel Islands or Yugoslavia as the address will need revisiting

    $countriesToAlert = array('YUGOSLAVIA', 'CHANNEL ISLANDS');

    $alertContacts = array();
    $alertSites = array();

    foreach ($countryList AS $code => $oldName)
    {
        $sql = "UPDATE `{$GLOBALS['dbContacts']}` SET country = '{$code}' WHERE country = '{$oldName}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (in_array($oldName, $countriesToAlert) AND mysql_affected_rows() > 0)
        {
            // We need to alert
            $sql = "SELECT c.id, c.forenames, c.surname, s.name FROM `{$GLOBALS['dbContacts']}` AS c, `{$GLOBALS['dbSites']}` AS s ";
            $sql .= "WHERE c.siteid = s.id AND c.country = '{$code}'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            while ($obj = mysql_fetch_object($result))
            {
                $a = array('code' => $code, 'contactid' => $obj->id, 'sitename' => $obj->name, 'firstname' => $obj->forenames, 'surname' => $obj->surname);
                $alertContacts[] = $a;
            }
        }

        $sql = "UPDATE `{$GLOBALS['dbSites']}` SET country = '{$code}' WHERE country = '{$oldName}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (in_array($oldName, $countriesToAlert) AND mysql_affected_rows() > 0)
        {
            // We need to alert
            $sql = "SELECT s.id, s.name FROM `{$GLOBALS['dbSites']}` AS s ";
            $sql .= "WHERE s.country = '{$code}'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            while ($obj = mysql_fetch_object($result))
            {
                $a = array('code' => $code, 'siteid' => $obj->id, 'sitename' => $obj->name);
                $alertSites[] = $a;
            }
        }

        $sql = "UPDATE `{$GLOBALS['dbConfig']}` SET value = '{$code}' WHERE value = '{$oldName}' AND config = 'home_country'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    }

    // Change to a two character column now
    $sql = "ALTER TABLE `{$GLOBALS['dbContacts']}` CHANGE `country` `country` CHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    $sql = "ALTER TABLE `{$GLOBALS['dbSites']}` CHANGE `country` `country` CHAR( 2 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    $str = '';

    if (empty($alertContacts) AND empty($alertSites))
    {
        $str .= "<p>Country data transfered successfully</p>";
    }
    else 
    {
        $str .= "<p class='info'>Country data transfered, you had some data using one of the following countries Yugoslavia or Channel Islands these have been mapped to Jersey and Serbia, please update the following records if incorrect, the affected data is listed below:</p>";
        if (!empty($alertContacts))
        {
            $str .= "<p>Contacts:<ul>";
            foreach ($alertContacts AS $ac)
            {
                $str .= "<li>{$ac['firstname']} {$ac['lastname']} from {$ac['sitename']}</li>";
            }
            $str .= "</ul></p>";
        }

        if (!empty($alertSites))
        {
            $str .= "<p>Sites:<ul>";
            foreach ($alertSites AS $as)
            {
                $str .= "<li>{$ac['sitename']}</li>";
            }
            $str .= "</ul></p>";
        }
    }

    return $str;

    // TODO reduce size of columns
}