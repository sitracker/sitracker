<?php
// site_new.php - Form for adding sites
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// This Page Is Valid XHTML 1.0 Transitional! 27Oct05

require ('core.php');
$permission = PERM_SITE_ADD; // Add new site
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNewSite;

$action = $_REQUEST['action'];

if ($action == "showform" OR $action == '')
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo show_form_errors('new_site');
    clear_form_errors('new_site');

    echo "<h2>".icon('site', 32)." ";
    echo "{$strNewSite}</h2>";
    plugin_do('site_new');
    echo "<form action='{$_SERVER['PHP_SELF']}?action=new' method='post' ";
    echo "onsubmit='return confirm_action(\"{$strAreYouSureAdd}\");'>";
    echo "<table class='maintable vertical'>";
    echo "<tr><th>{$strName}</th><td><input maxlength='255' class='required' ";
    echo "name='name' size='30' ";
    echo "value='{$_SESSION['formdata']['new_site']['name']}'";
    echo " /> <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr><th>{$strDepartment}</th><td><input maxlength='255' name='department' size='30'";
    echo "value='{$_SESSION['formdata']['new_site']['department']}' /></td></tr>\n";

    echo "<tr><th>{$strAddress1}</th><td>";
    echo "<input class='required' maxlength='255' name='address1' size='30'";
    echo "value='{$_SESSION['formdata']['new_site']['address1']}' />";
    echo " <span class='required'>{$strRequired}</span></td></tr>\n";

    echo "<tr><th>{$strAddress2}</th><td><input maxlength='255' name='address2' size='30' ";
    echo "value='{$_SESSION['formdata']['new_site']['address2']}' /></td></tr>\n";

    echo "<tr><th>{$strCity}</th><td><input maxlength='255' name='city' size='30'";
    echo "value='{$_SESSION['formdata']['new_site']['city']}' /></td></tr>\n";

    echo "<tr><th>{$strCounty}</th><td><input maxlength='255' name='county' size='30'";
    echo "value='{$_SESSION['formdata']['new_site']['county']}' /></td></tr>\n";

    echo "<tr><th>{$strCountry}</th><td>";
    if ($_SESSION['formdata']['new_site']['country'] != '')
    {
        echo country_drop_down('country', $_SESSION['formdata']['new_site']['country'])."</td></tr>\n";
    }
    else
    {
        echo country_drop_down('country', $CONFIG['home_country'])."</td></tr>\n";
    }

    echo "<tr><th>{$strPostcode}</th><td><input maxlength='255' name='postcode' size='30'";
    if ($_SESSION['formdata']['new_site']['postcode'] != '')
    {
        echo "value='{$_SESSION['formdata']['new_site']['postcode']}'";
    }
    echo " /></td></tr>\n";

    echo "<tr><th>{$strTelephone}</th><td><input maxlength='255' name='telephone' size='30'";
    echo "value='{$_SESSION['formdata']['new_site']['telephone']}' /></td></tr>\n";

    echo "<tr><th>{$strEmail}</th><td>";
    echo "<input maxlength='255' name='email' size='30'";
    echo "value='{$_SESSION['formdata']['new_site']['email']}' />";
    echo "</td></tr>\n";

    echo "<tr><th></th><td><a href=\"javascript:void(0);\" onclick=\"$('hidden').toggle();\">{$strMore}</a></td></tr>\n";
    echo "<tbody id='hidden' class='hidden' style='display:none'>";
    echo "<tr><th>{$strFax}</th><td><input maxlength='255' name='fax' size='30'";
    echo "value='{$_SESSION['formdata']['new_site']['fax']}' /></td></tr>\n";

    echo "<tr><th>{$strWebsite}</th><td><input maxlength='255' name='websiteurl' size='30'";
    echo "value='{$_SESSION['formdata']['new_site']['websiteurl']}' /></td></tr>\n";

    echo "<tr><th>{$strSiteType}</th><td>";
    if ($_SESSION['formdata']['new_site']['typeid'] != '')
    {
        echo sitetype_drop_down('typeid', $_SESSION['formdata']['new_site']['typeid'])."</td></tr>\n";
    }
    else
    {
        echo sitetype_drop_down('typeid', 1)."</td></tr>\n";
    }

    echo "<tr><th>{$strSalesperson}</th><td>";
    if ($_SESSION['formdata']['new_site']['owner'] != '')
    {
        user_drop_down('owner', $_SESSION['formdata']['new_site']['owner'], FALSE);
    }
    else
    {
        user_drop_down('owner', 0, FALSE);
    }

    echo "</td></tr>\n";
    echo "<tr><th>{$strNotes}</th><td><textarea cols='30' name='notes' rows='5'>";
    echo $_SESSION['formdata']['new_site']['notes'];
    echo "</textarea></td></tr>\n";
    plugin_do('site_new_form_more');
    echo "</tbody>";
    plugin_do('site_new_form');
    echo "</table>\n";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "<p class='warning'>{$strAvoidDupes}</p>\n";
    echo "</form>\n";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

    clear_form_data('new_site');
}
elseif ($action == "new")
{
    $name = cleanvar($_POST['name']);
    $department = convert_string_null_safe(cleanvar($_POST['department']));
    $address1 = cleanvar($_POST['address1']);
    $address2 = convert_string_null_safe(cleanvar($_POST['address2']));
    $city = convert_string_null_safe(cleanvar($_POST['city']));
    $county = convert_string_null_safe(cleanvar($_POST['county']));
    $country = convert_string_null_safe(cleanvar($_POST['country']));
    $postcode = convert_string_null_safe(cleanvar($_POST['postcode']));
    $telephone = convert_string_null_safe(cleanvar($_POST['telephone']));
    $fax = convert_string_null_safe(cleanvar($_POST['fax']));
    $email = convert_string_null_safe(cleanvar($_POST['email']));
    $websiteurl = convert_string_null_safe(cleanvar($_POST['websiteurl']));
    $notes = convert_string_null_safe(cleanvar($_POST['notes']));
    $typeid = clean_int($_POST['typeid']);
    $owner = clean_int($_POST['owner']);

    $_SESSION['formdata']['new_site'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);

    $errors = 0;

    if ($name == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_site']['name'] = sprintf($strFieldMustNotBeBlank, $strSiteName);
    }
    if ($address1 == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_site']['address1'] = sprintf($strFieldMustNotBeBlank, $strAddress1);
    }
    plugin_do('site_new_submitted');

    if ($errors == 0)
    {
        if ($owner == '') $owner = 0;
        $sql  = "INSERT INTO `{$dbSites}` (name, department, address1, address2, city, county, country, postcode, telephone, fax, email, websiteurl, notes, typeid, owner) ";
        $sql .= "VALUES ('{$name}', {$department}, '{$address1}', {$address2}, {$city}, {$county}, {$country}, {$postcode}, ";
        $sql .= "{$telephone}, {$fax}, {$email}, {$websiteurl}, {$notes}, '{$typeid}', '{$owner}')";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        $id = mysql_insert_id();

        if (!$result)
        {
            trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            html_redirect(application_url() . 'site_new.php', FALSE, $strNewSiteFailed);
        }
        else
        {
            plugin_do('site_new_saved');

            // show success message
            clear_form_data('new_site');
            clear_form_errors('new_site');

            $t = new TriggerEvent('TRIGGER_NEW_SITE', array('siteid' => $id, 'userid' => $sit[2]));
            html_redirect("site_details.php?id={$id}");
        }
    }
    else
    {
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}
?>