<?php
// configfuncs.inc.php - functions relating to confg center
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
 * HTML for a config variable input box
 * @author Ivan Lucas
 * @param string $setupvar The setup variable key name
 * @param int $userid UserID or 0 for system config
 * @param bool $showvarnames Whether to display the config variable name
 * @return string HTML
 */
function cfgVarInput($setupvar, $userid = 0, $showvarnames = FALSE)
{
    global $CONFIG, $CFGVAR;

    if ($userid == 'current') $userid = $_SESSION['userid'];

    if (array_key_exists('type', $CFGVAR[$setupvar]) && ($CFGVAR[$setupvar]['type'] == 'languageselect'
        OR $CFGVAR[$setupvar]['type'] == 'languagemultiselect'))
    {
        $available_languages = available_languages();
    }
    elseif (array_key_exists('type', $CFGVAR[$setupvar]) && $CFGVAR[$setupvar]['type'] == 'userlanguageselect')
    {
        if (!empty($CONFIG['available_i18n']))
        {
            $available_languages = i18n_code_to_name($CONFIG['available_i18n']);
        }
        else
        {
            $available_languages = available_languages();
        }
        $available_languages = array_merge(array(''=>$GLOBALS['strDefault']),$available_languages);
    }
    elseif (array_key_exists('type', $CFGVAR[$setupvar]) && $CFGVAR[$setupvar]['type'] == 'timezoneselect')
    {
        global $availabletimezones;
    }

    $html .= "<div class='configvar'>";
    if ($CFGVAR[$setupvar]['title'] != '') $title = $CFGVAR[$setupvar]['title'];
    else $title = $setupvar;
    $html .= "<h4>{$title}</h4>";
    if ($CFGVAR[$setupvar]['help']!='') $html .= "<p class='helptip'>{$CFGVAR[$setupvar]['help']}</p>\n";

    $value = '';
    if (!$cfg_file_exists OR ($cfg_file_exists AND $cfg_file_writable))
    {
        if ($userid > 0)
        {
            $value = $_SESSION['userconfig'][$setupvar];
        }
        else
        {
            $value = $CONFIG[$setupvar];
        }
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
        case 'checkbox':
            // Checkbox values are stored 'TRUE' / 'FALSE'
            if ($value == 'TRUE')
            {
                $state = TRUE;
            }
            else
            {
                $state = FALSE;
            }
            $html .= "<label>";
            $html .= html_checkbox($setupvar, $state, 'TRUE');
            $html .= " {$title}</label>";
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
        case 'userlanguageselect':
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
        case 'timezoneselect':
            if ($value == '') $value = 0;
            foreach ($availabletimezones AS $offset=>$tz)
            {
                $tz = $tz . '  ('.date('H:i',utc_time($now) + ($offset*60)).')';
                $availtz[$offset] = $tz;
            }
            $html .= array_drop_down($availtz, $setupvar, $value, '', TRUE);
            break;
        case 'timezoneselect':
            if ($value == '') $value = 0;
            foreach ($availabletimezones AS $offset=>$tz)
            {
                $tz = $tz . '  ('.date('H:i',utc_time($now) + ($offset*60)).')';
                $availtz[$offset] = $tz;
            }
            $html .= array_drop_down($availtz, 'utcoffset', $value, '', TRUE);
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
            $html .= " &nbsp; <a href='javascript:void(0);' onclick=\"checkLDAPDetails('status{$setupvar}');\">{$GLOBALS['strCheckLDAPDetails']}</a>";
            break;
        case 'ldapgroup':
            $html .= "<input type='text' name='{$setupvar}' id='{$setupvar}'  size='60' value=\"{$value}\" /> <a href=\"javascript:ldap_browse_window('', '{$setupvar}')\">{$GLOBALS['strBrowse']}</a>";
            break;
        case 'textreadonly':
            $html .= "<input type='text' name='{$setupvar}' id='{$setupvar}'  size='60' value=\"{$value}\" readonly='readonly' />";
            break;
        case 'timeselector':
            $inmins = $value / 60; // Seconds -> Minutes
            $hours = floor($inmins / 60);
            $minutes = $inmins % 60;
            $html .= time_picker($hours, $minutes, $setupvar);
            break;
        case 'weekdayselector':
            $replace = array('array(', ')', "'");
            $value = str_replace($replace, '',  $value);
            $days = array('0' => 'Sunday', '1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday',
                            '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday');
            $value = explode(',', $value);
            $html .= array_drop_down($days, $setupvar, $value, '', TRUE, TRUE, TRUE);
            break;
        case 'chartselector':
            $html .= chart_selector($value);
            break;
        case 'text':
        default:
            if (mb_strlen($CONFIG[$setupvar]) < 65)
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

    if ($showvarnames)
    {
        if ($userid < 1)
        {
            $html .= "<br />(<var>\$CONFIG['$setupvar']</var>)";
        }
        else
        {
            $html .= "<br />(<var>userconfig: '$setupvar'</var>)";
        }
    }

    if ($CFGVAR[$setupvar]['statusfield'] == 'TRUE')
    {
        $html .= "<div id='status{$setupvar}'></div>";
    }

    $html .= "</div>";
    $html .= "<br />\n";

    return $html;
}


/**
 * Save configuration
 * @param array $setupvars. An array of setup variables $setupvars['setting'] = 'foo';
 * @param int $namespace NAMESPACE_SIT, NAMESPACE_USER, NAMESPACE_CONTACT, OR NAMESPACE_SITE
 * @param int $id. Namespace reference (e.g. User ID)
 * @todo  TODO, need to make setup.php use this  INL 5Dec08
 * @author Ivan Lucas
 */
function cfgSave($setupvars, $namespace = NAMESPACE_SIT, $id = 0)
{
    global $dbConfig, $dbUserConfig, $dbContactConfig, $dbSiteConfig, $db;

    if ($namespace == NAMESPACE_USER)
    {
        if ($id == 'current')
        {
            $id = $_SESSION['userid'];
        }
    }
    foreach ($setupvars AS $key => $value)
    {
        switch ($namespace)
        {
            case NAMESPACE_USER:
                $sql = "REPLACE INTO `{$dbUserConfig}` (`userid`, `config`, `value`) VALUES ('{$id}', '{$key}', '{$value}')";
                break;
            case NAMESPACE_CONTACT:
                $sql = "REPLACE INTO `{$dbContactConfig}` (`userid`, `config`, `value`) VALUES ('{$id}', '{$key}', '{$value}')";
                break;
            case NAMESPACE_SITE:
                $sql = "REPLACE INTO `{$dbSiteConfig}` (`userid`, `config`, `value`) VALUES ('{$id}', '{$key}', '{$value}')";
                break;
            default:
                $sql = "REPLACE INTO `{$dbConfig}` (`config`, `value`) VALUES ('{$key}', '{$value}')";
        }
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db). "  $sql", E_USER_WARNING);
    }
    return TRUE;
}



