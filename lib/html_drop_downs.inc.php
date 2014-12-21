<?php
// html_drop_downs.inc.php - functions that return generic HTML dropdowns
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
 * Takes an array and makes an HTML selection box
 * @author Ivan Lucas
 * @param array $array - The array of options to display in the drop-down
 * @param string $name - The HTML name attribute (also used for id)
 * @param mixed $setting - The value to pre-select
 * @param string $attributes - Extra attributes for the select tag
 * @param mixed $usekey - (optional) Set the option value to be the array key instead
 *                        of the array value.
 *                        When TRUE the array key will be used as the option value
 *                        When FALSE the array value will be usedoption value
 *                        When NULL the function detects which is most appropriate
 * @param bool $multi - When TRUE a multiple selection box is returned and $setting
 *                      can be an array of values to pre-select
 * @param bool $showall - When true show all elements
 *                        When false use the built in algorithm to enable scrolling in the items
 * @retval string HTML select element
 */
function array_drop_down($array, $name, $setting='', $attributes='', $usekey = NULL, $multi = FALSE, $showall = FALSE)
{
    if ($multi AND mb_substr($name, -2) != '[]') $name .= '[]';
    $html = "<select name='{$name}' id='{$name}' ";
    if (!empty($attributes))
    {
         $html .= "$attributes ";
    }

    if ($multi)
    {
        $items = count($array);
        if ($showall)
        {
            $size = $items;
        }
        else
        {
            if ($items > 5) $size = floor($items / 3);
            if ($size > 10) $size = 10;
        }
        $html .= "multiple='multiple' size='{$size}' ";
    }
    $html .= ">\n";

    if ($usekey === '')
    {
        if ((array_key_exists($setting, $array) AND
            in_array((string)$setting, $array) == FALSE) OR
            $usekey == TRUE)
        {
            $usekey = TRUE;
        }
        else
        {
            $usekey = FALSE;
        }
    }

    foreach ($array AS $key => $value)
    {
        $value = htmlentities($value, ENT_COMPAT, $GLOBALS['i18ncharset']);
        if ($usekey)
        {
            $html .= "<option value='{$key}'";
            if ($multi === TRUE AND is_array($setting))
            {
                if (in_array($key, $setting))
                {
                    $html .= " selected='selected'";
                }
            }
            elseif ($key == $setting)
            {
                $html .= " selected='selected'";
            }

        }
        else
        {
            $html .= "<option value='{$value}'";
            if ($multi === TRUE AND is_array($setting))
            {
                if (in_array($value, $setting))
                {
                    $html .= " selected='selected'";
                }
            }
            elseif ($value == $setting)
            {
                $html .= " selected='selected'";
            }
        }

        $html .= ">{$value}</option>\n";
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
 * @return string. HTML
 */
function userstatus_drop_down($name, $id = 0, $userdisable = FALSE)
{
    global $dbUserStatus;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbUserStatus}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    $html = "<select name='{$name}'>\n";
    if ($userdisable)
    {
        $html .= "<option class='disable' selected='selected' value='0'>{$GLOBALS['strAccountDisabled']}</option>\n";
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
 * @return string. HTML
 */
function userstatus_bardrop_down($name, $id)
{
    global $dbUserStatus;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbUserStatus}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    $html = "<select id='userstatus_dropdown' name='{$name}' title='{$GLOBALS['strSetYourStatus']}' ";
    $html .= "onchange=\"set_user_status();\" onblur=\"hide_status_drop_down();\">";
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

            $html .= "value='{$statuses->id}'>";
            $html .= "{$GLOBALS[$statuses->name]}</option>\n";
        }
    }
    $html .= "<option value='Yes' class='enable separator'>";
    $html .= "{$GLOBALS['strAccepting']}</option>\n";
    $html .= "<option value='No' class='disable'>{$GLOBALS['strNotAccepting']}";
    $html .= "</option></select>\n";

    return $html;
}


/**
 * Return HTML for a select box of user email templates
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of Template to pre-select. None selected if 0 or blank.
 * @param string $type. Type to display.
 * @return string. HTML
 */
function emailtemplate_drop_down($name, $id, $type)
{
    global $dbEmailTemplates;
    // INL 22Apr05 Added a filter to only show user templates

    $sql  = "SELECT id, name, description FROM `{$dbEmailTemplates}` WHERE type='{$type}' ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

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
 * Return HTML for a select box for accepting yes/no. The given user's accepting status is displayed.
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $userid. The user ID to check
 * @return string. HTML
 */
function accepting_drop_down($name, $userid)
{
    if (user_accepting($userid) == "Yes")
    {
        $html = "<select name=\"{$name}\">\n";
        $html .= "<option selected='selected' value=\"Yes\">{$GLOBALS['strYes']}</option>\n";
        $html .= "<option value=\"No\">{$GLOBALS['strNo']}</option>\n";
        $html .= "</select>\n";
    }
    else
    {
        $html = "<select name=\"{$name}\">\n";
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
 * @return string. HTML
 */
function escalation_path_drop_down($name, $id)
{
    global $dbEscalationPaths;
    $sql  = "SELECT id, name FROM `{$dbEscalationPaths}` ";
    $sql .= "ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
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
 * Return HTML for a box to select interface style/theme
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param string $id. Chosen interface style
 * @return string.  HTML
 */
function interfacestyle_drop_down($name, $setting)
{
    $handle = opendir('.'.DIRECTORY_SEPARATOR.'styles');
    while ($file = readdir($handle))
    {
        if ($file == '.' || $file == '..')
        {
            continue;
        }
        if (is_dir('.'.DIRECTORY_SEPARATOR.'styles'.DIRECTORY_SEPARATOR.$file))
        {
            $themes[$file] = ucfirst(str_replace('_', ' ', $file));
        }
    }
    asort($themes);

    $html = array_drop_down($themes, $name, $setting, '', TRUE);

    return $html;
}


/**
 * A HTML Select listbox for user groups
 * @author Ivan Lucas
 * @param string $name. name attribute to use for the select element
 * @param int $selected.  Group ID to preselect
 * @return HTML select
 */
function group_drop_down($name = '', $selected = '')
{
    global $grouparr, $numgroups;
    $html = "<select name='$name'>";
    $html .= "<option value='0'>{$GLOBALS['strNone']}</option>\n";
    if ($numgroups >= 1)
    {
        foreach ($grouparr AS $groupid => $groupname)
        {
            $html .= "<option value='{$groupid}'";
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
 * HTML for a drop down list of products
 * @author Ivan Lucas
 * @param string $name. name/id to use for the select element
 * @param int $id. Product ID
 * @param bool $required.
 * @param bool $showinactive - Whether to show products which are marked as inactive
 * @return string. HTML select
 * @note With the given name and with the given id selected.
 */
function product_drop_down($name, $id, $required = FALSE, $showinactive = FALSE)
{
    global $dbProducts;
    // extract products
    $sql  = "SELECT id, name FROM `{$dbProducts}` ";
    if ($showinactive == FALSE) $sql .= "WHERE active = 'true' ";
    $sql .= "ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

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
 * HTML for a drop down list of skills
 * @author Ivan Lucas
 * @param string $name. name/id to use for the select element
 * @param int $id. Software/Skill ID to preselect
 * @returns HTML select
 * @note Skills were named 'software' in legacy versions of SiT.
 */
function skill_drop_down($name, $id)
{
    global $now, $dbSoftware, $strEOL;

    $sql  = "SELECT id, name, lifetime_end FROM `{$dbSoftware}` ";
    $sql .= "ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    $html = "<select name='{$name}' id='{$name}' >";

    if ($id < 1)
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
 * Generates a HTML dropdown of Skills
 * @author Kieran Hogg
 * @param string $name. name/id to use for the select element
 * @return HTML select
 * @note Software was the old name for what we now call 'Skills'
 */
function softwareproduct_drop_down($name, $id, $productid, $visibility='internal')
{
    global $dbSoftware, $dbSoftwareProducts, $strRequired;
    // extract software
    $sql  = "SELECT id, name FROM `{$dbSoftware}` AS s, ";
    $sql .= "`{$dbSoftwareProducts}` AS sp WHERE s.id = sp.softwareid ";
    $sql .= "AND productid = '{$productid}' ";
    $sql .= "ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    $numrows = mysql_num_rows($result);
    if ($numrows > 0)
    {
        $html = "<select name='{$name}' id='{$name}'";
        if ($visibility == 'internal' AND $id == 0)
        {
            $html .= " class='required'";
        }
        $html .= ">";

        if ($numrows > 1)
        {
            if ($visibility == 'internal' AND $id == 0)
            {
                $html .= "<option selected='selected' value='0'></option>\n";
            }
            elseif ($visiblity = 'external' AND $id == 0)
            {
                $html .= "<option selected='selected' value=''>{$GLOBALS['strUnknown']}</option>\n";
            }
        }

        while ($software = mysql_fetch_object($result))
        {
            $html .= "<option";
            if ($software->id == $id OR $numrows == 1)
            {
                $html .= " selected='selected'";
            }
            $html .= " value='{$software->id}'>{$software->name}</option>\n";
        }
        $html .= "</select>\n";
        if ($visibility == 'internal' AND $id == 0)
        {
            $html .= "<span class='required'>{$strRequired}</span>";
        }
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
 * @param bool $required whether the field is required
 * @return HTML select
 */
function vendor_drop_down($name, $id, $required = FALSE)
{
    global $dbVendors;
    $sql = "SELECT id, name FROM `{$dbVendors}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $html = "<select name='$name'";
    if ($required)
    {
        $html .= " class='required' ";
    }
    $html .= ">";
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
 * @param bool $required. adds required field class when TRUE
 * @todo TODO i18n needed site types
 * @return HTML select
 */
function sitetype_drop_down($name, $id, $required = FALSE)
{
    global $dbSiteTypes;
    $sql = "SELECT typeid, typename FROM `{$dbSiteTypes}` ORDER BY typename ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $html .= "<select name='$name'";
    if ($required)
    {
        $html .= " class='required'";
    }
    $html .= ">\n";
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
 * A HTML Select listbox for user roles
 * @author Ivan Lucas
 * @param string $name. name to use for the select element
 * @param int $id. Role ID to preselect
 * @return HTML select
 */
function role_drop_down($name, $id)
{
    global $dbRoles;
    $sql  = "SELECT id, rolename FROM `{$dbRoles}` ORDER BY rolename ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

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
 * Generates a HTML drop down of sites within SiT!
 * @param string $name The name of the field
 * @param int $id The ID of the selected item
 * @param bool $required Whether this is a mandetory field, defaults to false
 * @param bool $showinactive Whether to show the sites marked inactive, defaults to false
 * @return string The HTML for the drop down
 */
function site_drop_down($name, $id = '', $required = FALSE, $showinactive = FALSE)
{
    global $dbSites, $strEllipsis;
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

        if (mb_strlen($text) >= 55)
        {
            $text = mb_substr(trim($text), 0, 55, 'UTF-8').$strEllipsis;
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


/**
 * Prints the HTML for a drop down list of maintenance contracts
 * @param string $name. name of the drop down box
 * @param int $id. the contract id to preselect
 * @param int $siteid. Show records from this SiteID only, blank for all sites
 * @param array $excludes. Hide contracts with ID's in this array
 * @param bool $required. Whether the field is required or not.
 * @param bool $showonlyactive. True show only active (with a future expiry date), false shows all
 * @note in versions prior to 3.90 the fifth paramater of this function was "bool $return. Whether to return to HTML or echo" since then it always
 returns HTML.
 */
function maintenance_drop_down($name, $id = '', $siteid = '', $excludes = '', $required = FALSE, $showonlyactive = FALSE, $adminid = '', $sla = FALSE)
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

    if ($sla !== FALSE)
    {
        $sql .= "AND servicelevel = '{$sla}' ";
    }

    $sql .= "ORDER BY s.name ASC";
    $result = mysql_query($sql);
    $results = 0;
    // print HTML
    $html .= "<select name='{$name}'";
    if ($required)
    {
        $html .= " class='required'";
    }
    $html .= ">";
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
            if (!empty($siteid))
            {
                $html .= "value='{$maintenance->id}'>{$maintenance->productname}</option>";
            }
            else
            {
                $html .= "value='{$maintenance->id}'>{$maintenance->sitename} | {$maintenance->productname}</option>";
            }
            $html .= "\n";
            $results++;
        }
    }

    if ($results == 0)
    {
        $html .= "<option>{$GLOBALS['strNoRecords']}</option>";
    }
    $html .= "</select>";

    return $html;
}


//  prints the HTML for a drop down list of resellers, with the given name and with the given id
// selected.                                                  */
function reseller_drop_down($name, $id)
{
    global $dbResellers;
    $sql  = "SELECT id, name FROM `{$dbResellers}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

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
 * HTML select box listing substitute engineers
 * @author Ivan Lucas
 */
function software_backup_dropdown($name, $userid, $softwareid, $backupid)
{
    global $dbUsers, $dbUserSoftware, $dbSoftware;
    $sql = "SELECT *, u.id AS userid FROM `{$dbUserSoftware}` AS us, `{$dbSoftware}` AS s, `{$dbUsers}` AS u ";
    $sql .= "WHERE us.softwareid = s.id ";
    $sql .= "AND s.id = '{$softwareid}' ";
    $sql .= "AND userid != '{$userid}' AND u.status > ".USERSTATUS_ACCOUNT_DISABLED;
    $sql .= " AND us.userid = u.id ";
    $sql .= " ORDER BY realname";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $countsw = mysql_num_rows($result);
    if ($countsw >= 1)
    {
        $html = "<select name='{$name}'>\n";
        $html .= "<option value='0'";
        if ($user->userid == 0) $html .= " selected='selected'";
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
        $html .= "<input type='hidden' name='{$name}' value='0' />{$GLOBALS['strNoneAvailable']}";
    }
    return $html;
}


/**
 * Print a listbox of countries
 * @author Paul Heaney
 * @param string $name - HTML select 'name' attribute
 * @param string $country - Country to pre-select (default to config file setting)
 * @param string $extraattributes - Extra attributes to put on the select tag
 * @return HTML
 * @todo TODO i18n country list (propose this is done using either a macro/placeholder in DB or just i18n based on iso code)
 */
function country_drop_down($name, $country, $extraattributes='')
{
    global $CONFIG;
    if ($country == '') $country = $CONFIG['home_country'];

    $sql = "SELECT * FROM `{$GLOBALS['dbCountryList']}`";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

    $html = "<select id=\"{$name}\" name=\"{$name}\" {$extraattributes}>";
    while ($obj = mysql_fetch_object($result))
    {
        $html .= "<option value='{$obj->isocode}'";
        if ($obj->isocode == $country)
        {
            $html .= " selected='selected'";
        }
        $html .= ">{$obj->name}</option>\n";
    }
    $html .= "</select>";

    return $html;
}


/**
 * Generates a drop down of all configured billing multipliers
 *
 * @author Paul Heaney
 * @param String $name  The name and id of the <select> element
 * @param float $selected  If multiplier to select
 * @return String HTML for the dropdown
 */
function billing_multiplier_dropdown($name, $selected='')
{
    global $CONFIG;
    $html = "<select id='{$name}' name='{$name}'>\n";

    if (empty($selected)) $selected = $CONFIG['billing_default_multiplier'];

    foreach ($CONFIG['billing_matrix_multipliers'] AS $multiplier)
    {
        $html .= "<option value='{$multiplier}'";
        if ($multiplier == $selected) $html .= " selected='selected' ";
        $html .= ">&#215;{$multiplier}</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * Generates a drop down of the available charting libraries
 * @author Paul Heaney
 */
function chart_selector($selected)
{
    global $CONFIG;

    $html = "<select id='default_chart' name='default_chart'>";

    foreach ($CONFIG['available_charts'] AS $c)
    {
        $html .= "<option value='{$c}' ";
        if ($selected == $c) $html .= "selected='selected'";
        $html .= ">{$c}</option>";
    }

    $html .= "</select>";

    return  $html;
}


function user_dropdown($name, $selected='', $self='')
{
    global $dbUsers, $dbUserSoftware, $dbSoftware;
    $sql = "SELECT * FROM `{$dbUsers}` AS u ";
    $sql .= "WHERE  u.status > ".USERSTATUS_ACCOUNT_DISABLED;
    $sql .= " ORDER BY realname";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $count = mysql_num_rows($result);
    if ($count >= 1)
    {
        $html = "<select name='{$name}' id='{$name}'>\n";
        $html .= "<option value='0'";
        if (empty($selected)) $html .= " selected='selected'";
        $html .= ">{$GLOBALS['strNone']}</option>\n";
        while ($user = mysql_fetch_object($result))
        {
            if ($user->id != $self)
            {
                $html .= "<option value='{$user->id}'";
                if ($user->id == $selected) $html .= " selected='selected'";
                $html .= ">{$user->realname}</option>\n";
            }
        }
        $html .= "</select>\n";
    }
    else
    {
        $html .= "<input type='hidden' name='{$name}' value='0' />{$GLOBALS['strNoneAvailable']}";
    }
    return $html;
}


/**
 * Creates a dropdown of incident types
 * 
 * @param string $name The name of the field
 * @param string $selected The ID of the selected value
 * @return String HTML for the dropdown
 * @author Paul Heaney
 */
function incident_types_dropdown($name, $selected=1)
{
    global $dbIncidentTypes;
    $sql = "SELECT * FROM `{$dbIncidentTypes}` AS it ";
    $sql .= " ORDER BY name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $count = mysql_num_rows($result);
    if ($count >= 1)
    {
        $html = "<select name='{$name}' id='{$name}'>";
        while ($obj = mysql_fetch_object($result))
        {
            if ($obj->id != $self)
            {
                $html .= "<option value='{$obj->id}'";
                if ($obj->id == $selected) $html .= " selected='selected'";
                $html .= ">{$obj->name}</option>";
            }
        }
        $html .= "</select>";
    }
    else
    {
        $html .= "<input type='hidden' name='{$name}' value='0' />{$GLOBALS['strNoneAvailable']}";
    }
    return $html;
}

?>
