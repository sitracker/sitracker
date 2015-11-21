<?php
// portal/sitedetails.inc.php - Displays the site details to admins
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Kieran Hogg <kieran[at]sitracker.org>

require ('..' . DIRECTORY_SEPARATOR . 'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

$accesslevel = 'admin';

include (APPLICATION_LIBPATH . 'portalauth.inc.php');

$site = intval($_SESSION['siteid']);

if (isset($_POST['submit']))
{
    // External Variables
    $incident_pools = explode(',', "0,{$CONFIG['incident_pools']}");
    $incident_quantity = $incident_pools[clean_int($_POST['incident_poolid'])];
    $name = cleanvar($_POST['name']);
    $department = cleanvar($_POST['department']);
    $address1 = cleanvar($_POST['address1']);
    $address2 = cleanvar($_POST['address2']);
    $city = cleanvar($_POST['city']);
    $county = cleanvar($_POST['county']);
    $postcode = cleanvar($_POST['postcode']);
    $country = cleanvar($_POST['country']);
    $telephone = cleanvar($_POST['telephone']);
    $fax = cleanvar($_POST['fax']);
    $email = cleanvar($_POST['email']);
    $websiteurl = cleanvar($_POST['websiteurl']);
    $notes = cleanvar($_POST['notes']);
    $owner = clean_int($_POST['owner']);
    $site = clean_int($_POST['site']);
    $tags = cleanvar($_POST['tags']);

    // Edit site, update the database
    $errors = 0;

    if ($name == '')
    {
        $errors = 1;
        $_SESSION['formerrors']['site_edit']['name'] = sprintf($strFieldMustNotBeBlank, $strName);
    }

    if ($address1 == '')
    {
        $errors = 1;
        $_SESSION['formerrors']['site_edit']['address1'] = sprintf($strFieldMustNotBeBlank, $strAddress1);
    }

    // edit site if no errors
    if ($errors == 0)
    {
        replace_tags(3, $site, $tags);
        if (isset($licenserx))
        {
            $licenserx = '1';
        }
        else
        {
            $licenserx = '0';
        }
        // update site

        $sql = "UPDATE `{$dbSites}` SET name='{$name}', department='{$department}', address1='{$address1}', address2='{$address2}', city='{$city}', ";
        $sql .= "county='{$county}', postcode='{$postcode}', country='{$country}', telephone='{$telephone}', fax='{$fax}', email='{$email}', ";
        $sql .= "websiteurl='{$websiteurl}', notes='{$notes}', owner='{$owner}', freesupport='{$incident_quantity}' WHERE id='{$site}' LIMIT 1";

        // licenserx='$licenserx'
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        else
        {
            plugin_do('edit_site_save');
            journal(CFG_LOGGING_NORMAL, $strSiteEdited, sprintf($strSiteXEdited,$site) , CFG_JOURNAL_SITES, $site);
            html_redirect($_SERVER['PHP_SELF']);
            exit;
        }
    }
    else
    {
        html_redirect($_SERVER['PHP_SELF'], FALSE, $errors_string);
        exit;
    }
}

include (APPLICATION_INCPATH . 'portalheader.inc.php');

$sql = "SELECT * FROM `{$GLOBALS['dbSites']}` WHERE id='{$site}' ";
$siteresult = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
echo show_form_errors('site_edit');
clear_form_errors('site_edit');
while ($obj = mysql_fetch_object($siteresult))
{
    echo "<h2>".icon('site', 32)." ".site_name($site)."</h2>";
    echo "<form name='edit_site' action='{$_SERVER['PHP_SELF']}";
    echo "?action=update' method='post' onsubmit='return ";
    echo "confirm_action(\"{$strAreYouSureMakeTheseChanges}\")'>";
    echo "<table class='maintable vertical'>";
    echo "<tr><th>{$strName}:</th>";
    echo "<td><input class='required' maxlength='50' name='name' size='40' value='{$obj->name}' />";
    echo " <span class='required'>{$strRequired}</span></td></tr>\n";
    echo "<tr><th>{$strDepartment}:</th>";
    echo "<td><input maxlength='50' name='department' size='40' value='{$obj->department}' />";
    echo "</td></tr>\n";
    echo "<tr><th>{$strAddress1}:</th>";
    echo "<td><input maxlength='50' name='address1' class='required' size='40' value='{$obj->address1}' /> <span class='required'>{$strRequired}</span>";
    echo "</td></tr>\n";
    echo "<tr><th>{$strAddress2}: </th><td><input maxlength='50' name='address2' size='40' value='{$obj->address2}' /></td></tr>\n";
    echo "<tr><th>{$strCity}:</th><td><input maxlength='255' name='city' size='40' value='{$obj->city}' /></td></tr>\n";
    echo "<tr><th>{$strCounty}:</th><td><input maxlength='255' name='county' size='40' value='{$obj->county}' /></td></tr>\n";
    echo "<tr><th>{$strPostcode}:</th><td><input maxlength='255' name='postcode' size='40' value='{$obj->postcode}' /></td></tr>\n";
    echo "<tr><th>{$strCountry}:</th><td>".country_drop_down('country', $obj->country)."</td></tr>\n";
    echo "<tr><th>{$strTelephone}:</th><td>";
    echo "<input maxlength='255' name='telephone' size='40' value='{$obj->telephone}' />";
    echo "</td></tr>\n";
    echo "<tr><th>{$strFax}:</th><td>";
    echo "<input maxlength='255' name='fax' size='40' value='{$obj->fax}' /></td></tr>\n";
    echo "<tr><th>{$strEmail}:</th><td>";
    echo "<input maxlength='255' name='email' size='40' value='{$obj->email}' />";
    echo "</td></tr>\n";
    echo "<tr><th>{$strWebsite}:</th><td>";
    echo "<input maxlength='255' name='websiteurl' size='40' value='{$obj->websiteurl}' /></td></tr>\n";
    plugin_do('portal_site_edit_form');

    echo "</table>\n";
    echo "<input name='site' type='hidden' value='{$site}' />";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input name='submit' type='submit' value='{$strSave}' /></p>";

    echo "</form>";
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>