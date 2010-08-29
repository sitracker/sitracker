<?php
// edit_site.php - Form for editing a site
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional!  6Feb06


$permission = 3; // Edit existing site details
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$action = $_REQUEST['action'];
$site = cleanvar($_REQUEST['site']);

$title = $strEditSite;
include (APPLICATION_INCPATH . 'htmlheader.inc.php');


// Show select site form
if (empty($action) OR $action == "showform" OR empty($site))
{
    echo "<h3>{$title}</h3>";
    echo "<form action='{$_SERVER['PHP_SELF']}?action=edit' method='post'>";
    echo "<table class='vertical'>";
    echo "<tr><th>{$strSite}:</th><td>".site_drop_down("site", 0)."</td></tr>\n";
    echo "</table>\n";
    echo "<p><input name='submit' type='submit' value=\"{$strContinue}\" /></p>\n";
    echo "</form>\n";
}
elseif ($action == "edit")
{
    if ($site == 0)
    {
        user_alert(sprintf($strFieldMustNotBeBlank, "'{$strSite}'"), E_USER_ERROR);
    }
    else
    {
        //  Show edit site form
        echo show_edit_site($site);
    }
}
elseif ($action == "update")
{
    // Fix for Manits 1128 Incident pool dropdown is broken, dropdown now passes pool value, not ID
    $incident_quantity = intval(cleanvar($_POST['incident_pool']));
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
    $typeid = cleanvar($_POST['typeid']);
    $owner = cleanvar($_POST['owner']);
    $site = cleanvar($_POST['site']);
    $tags = cleanvar($_POST['tags']);
    $active = cleanvar($_POST['active']);

    // Edit site, update the database
    $errors = 0;
    // check for blank name
    if ($name == '')
    {
        $errors = 1;
        $errors_string .= user_alert(sprintf($strFieldMustNotBeBlank, "'{$strName}'"), E_USER_ERROR);
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

        if ($active == 'true')
        {
            $activeStr = 'true';
        }
        else
        {
            $activeStr = 'false';
        }

        $sql = "UPDATE `{$dbSites}` SET name='{$name}', department='{$department}', address1='{$address1}', address2='{$address2}', city='{$city}', ";
        $sql .= "county='{$county}', postcode='{$postcode}', country='{$country}', telephone='{$telephone}', fax='{$fax}', email='{$email}', ";
        $sql .= "websiteurl='{$websiteurl}', notes='{$notes}', typeid='{$typeid}', owner='{$owner}', freesupport='{$incident_quantity}', active='{$activeStr}' WHERE id='{$site}' LIMIT 1";

        // licenserx='$licenserx'
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        else
        {
            plugin_do('edit_site_save');
            journal(CFG_LOGGING_NORMAL, $strSiteEdited, sprintf($strSiteXEdited,$site) , CFG_JOURNAL_SITES, $site);
            html_redirect("site_details.php?id={$site}");
            exit;
        }
    }
    else
    {
        echo $errors_string;
    }
}
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>