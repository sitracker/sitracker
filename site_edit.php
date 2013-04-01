<?php
// edit_site.php - Form for editing a site
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional!  6Feb06

require ('core.php');
$permission = PERM_SITE_EDIT; // Edit existing site details
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$action = clean_fixed_list($_REQUEST['action'], array('showform','edit','update'));
$site = clean_int($_REQUEST['site']);

$title = $strEditSite;

// Show select site form
if (empty($action) OR $action == "showform" OR empty($site))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h3>{$title}</h3>";
    echo "<form action='{$_SERVER['PHP_SELF']}?action=edit' method='post'>";
    echo "<table class='vertical'>";
    echo "<tr><th>{$strSite}:</th><td>".site_drop_down("site", 0)."</td></tr>\n";
    echo "</table>\n";
    echo "<p><input name='submit' type='submit' value=\"{$strContinue}\" /></p>\n";
    echo "</form>\n";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == "edit")
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    if ($site == 0)
    {
        user_alert(sprintf($strFieldMustNotBeBlank, "'{$strSite}'"), E_USER_ERROR);
    }
    else
    {
        $sql = "SELECT * FROM `{$GLOBALS['dbSites']}` WHERE id='{$site}' ";
        $siteresult = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        echo show_form_errors('site_edit');
        clear_form_errors('site_edit');

        while ($obj = mysql_fetch_object($siteresult))
        {
            echo "<h2>".icon('site', 32)." {$GLOBALS['strEditSite']}: {$site} - ";
            echo site_name($site)."</h2>";
            plugin_do('site_edit');
    
            echo "<form name='site_edit' action='{$_SERVER['PHP_SELF']}?action=update' method='post' onsubmit='return confirm_action(\"{$strAreYouSureMakeTheseChanges}\")'>";
            echo "<table class='maintable vertical'>";
            echo "<tr><th>{$strName}:</th>";
            echo "<td><input class='required' maxlength='50' name='name' size='40' value=\"{$obj->name}\" />";
            echo " <span class='required'>{$strRequired}</span></td></tr>\n";
            echo "<tr><th>{$strTags}:</th><td><textarea rows='2' cols='60' name='tags'>";
            echo list_tags($site, TAG_SITE, false)."</textarea>\n";
            echo "<tr><th>{$strDepartment}:</th>";
            echo "<td><input maxlength='50' name='department' size='40' value=\"{$obj->department}\" />";
            echo "</td></tr>\n";
            echo "<tr><th>{$strAddress1}:</th>";
            echo "<td><input maxlength='50' name='address1' class='required' ";
            echo "size='40' value=\"{$obj->address1}\" /> <span class='required'>{$strRequired}</span>";
            echo "</td></tr>\n";
            echo "<tr><th>{$strAddress2}: </th><td><input maxlength='50' name='address2' size='40' value=\"{$obj->address2}\" /></td></tr>\n";
            echo "<tr><th>{$strCity}:</th><td><input maxlength='255' name='city' size='40' value=\"{$obj->city}\" /></td></tr>\n";
            echo "<tr><th>{$strCounty}:</th><td><input maxlength='255' name='county' size='40' value=\"{$obj->county}\" /></td></tr>\n";
            echo "<tr><th>{$strPostcode}:</th><td><input maxlength='255' name='postcode' size='40' value=\"{$obj->postcode}\" /></td></tr>\n";
            echo "<tr><th>{$strCountry}:</th><td>".country_drop_down('country', $obj->country)."</td></tr>\n";
            echo "<tr><th>{$strTelephone}:</th><td>";
            echo "<input maxlength='255' name='telephone' size='40' value='{$obj->telephone}' />";
            echo "</td></tr>\n";
            echo "<tr><th>{$strFax}:</th><td>";
            echo "<input maxlength='255' name='fax' size='40' value='{$obj->fax}' /></td></tr>\n";
            echo "<tr><th>{$strEmail}:</th><td>";
            echo "<input maxlength='255' name='email' size='40' value=\"{$obj->email}\" />";
            echo "</td></tr>\n";
            echo "<tr><th>{$strWebsite}:</th><td>";
            echo "<input maxlength='255' name='websiteurl' size='40' value='{$obj->websiteurl}' /></td></tr>\n";
            echo "<tr><th>{$strSiteType}:</th><td>\n";
            echo sitetype_drop_down('typeid', $obj->typeid);
            echo "</td></tr>\n";
            echo "<tr><th>{$strSalesperson}:</th><td>";
            echo user_drop_down('owner', $obj->owner, $accepting = FALSE, '', '', TRUE);
            echo "</td></tr>\n";
            echo "<tr><th>{$strIncidentPool}:</th>";
            $incident_pools = explode(',', "{$strNone},{$CONFIG['incident_pools']}");
            if (array_key_exists($obj->freesupport, $incident_pools) == FALSE)
            {
                array_unshift($incident_pools, $obj->freesupport);
            }
            echo "<td>".array_drop_down($incident_pools,'incident_pool',$obj->freesupport)."</td></tr>";
            echo "<tr><th>{$strActive}:</th><td><input type='checkbox' name='active' ";
            if ($obj->active == 'true')
            {
                echo "checked='{$obj->active}'";
            }
            echo " value='true' /></td></tr>\n";
            echo "<tr><th>{$strNotes}:</th><td>";
            echo "<textarea rows='5' cols='30' name='notes'>{$obj->notes}</textarea>";
            echo "</td></tr>\n";
            plugin_do('site_edit_form');

            echo "</table>\n";
            echo "<input name='site' type='hidden' value='{$site}' />";
            echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
            echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
            echo "<p><a href=\"site_details.php?id={$site}\">{$strReturnWithoutSaving}</a></p>";
            echo "</form>";
        }
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == "update")
{
    // Fix for Mantis 1128 Incident pool dropdown is broken, dropdown now passes pool value, not ID
    $incident_quantity = clean_dbstring($_POST['incident_pool']);
    $name = clean_dbstring($_POST['name']);
    $department = convert_string_null_safe(clean_dbstring($_POST['department']));
    $address1 = clean_dbstring($_POST['address1']);
    $address2 = convert_string_null_safe(clean_dbstring($_POST['address2']));
    $city = convert_string_null_safe(clean_dbstring($_POST['city']));
    $county = convert_string_null_safe(clean_dbstring($_POST['county']));
    $postcode = convert_string_null_safe(clean_dbstring($_POST['postcode']));
    $country = convert_string_null_safe(clean_dbstring($_POST['country']));
    $telephone = convert_string_null_safe(clean_dbstring($_POST['telephone']));
    $fax = convert_string_null_safe(clean_dbstring($_POST['fax']));
    $email = convert_string_null_safe(clean_dbstring($_POST['email']));
    $websiteurl = convert_string_null_safe(clean_dbstring($_POST['websiteurl']));
    $notes = convert_string_null_safe(clean_dbstring($_POST['notes']));
    $typeid = clean_int($_POST['typeid']);
    $owner = clean_int($_POST['owner']);
    $site = clean_int($_POST['site']);
    $tags = clean_dbstring($_POST['tags']);
    $active = clean_dbstring($_POST['active']);

    $errors = 0;

    if ($name == '')
    {
        $errors++;
        $_SESSION['formerrors']['site_edit']['name'] = sprintf($strFieldMustNotBeBlank, $strName);       
    }
    
    if ($name == '')
    {
        $errors++;
        $_SESSION['formerrors']['site_edit']['address1'] = sprintf($strFieldMustNotBeBlank, $strAddress1);
    }

    plugin_do('site_edit_submitted');

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

        if ($active == 'true')
        {
            $activeStr = 'true';
        }
        else
        {
            $activeStr = 'false';
        }

        $sql = "UPDATE `{$dbSites}` SET name='{$name}', department={$department}, address1='{$address1}', address2={$address2}, city={$city}, ";
        $sql .= "county={$county}, postcode={$postcode}, country={$country}, telephone={$telephone}, fax={$fax}, email={$email}, ";
        $sql .= "websiteurl={$websiteurl}, notes={$notes}, typeid='{$typeid}', owner='{$owner}', freesupport='{$incident_quantity}', active='{$activeStr}' WHERE id='{$site}' LIMIT 1";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        else
        {
            plugin_do('site_edit_saved');
            journal(CFG_LOGGING_NORMAL, $strSiteEdited, sprintf($strSiteXEdited,$site) , CFG_JOURNAL_SITES, $site);
            html_redirect("site_details.php?id={$site}");
            exit;
        }
    }
    else
    {
        html_redirect("site_edit.php?action=edit&site={$site}", FALSE);
    }
}
?>