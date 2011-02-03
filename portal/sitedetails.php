<?php
// portal/sitedetails.inc.php - Displays the site details to admins
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Kieran Hogg <kieran[at]sitracker.org>

require ('..'.DIRECTORY_SEPARATOR.'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

$accesslevel = 'admin';

include (APPLICATION_LIBPATH . 'portalauth.inc.php');
include (APPLICATION_INCPATH . 'portalheader.inc.php');

$site = intval($_SESSION['siteid']);

if (isset($_POST['submit']))
{
	// External Variables
    $incident_pools = explode(',', "0,{$CONFIG['incident_pools']}");
    $incident_quantity = $incident_pools[$_POST['incident_poolid']];
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
    $typeid = clean_int($_POST['typeid']);
    $owner = clean_int($_POST['owner']);
    $site = clean_int($_POST['site']);
    $tags = cleanvar($_POST['tags']);
    $active = cleanvar($_POST['active']);

    // Edit site, update the database
    $errors = 0;

    if ($name == '')
    {
        $errors = 1;
        $errors_string .= user_alert(sprintf($strFieldMustNotBeBlank, "'{$strName}'"), E_USER_ERROR);;
    }

    if ($email == '')
    {
    	$errors = 1;
    	$errors_string .= user_alert(sprintf($strFieldMustNotBeBlank, "'{$strEmail}'"), E_USER_ERROR);
    }

    if ($telephone == '')
    {
        $errors = 1;
    	$errors_string .= user_alert(sprintf($strFieldMustNotBeBlank, "'{$strTelephone}'"), E_USER_ERROR);
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

        if ($active == 'true')
        {
            $activeStr = 'true';
        }
        else
        {
            $activeStr = 'false';
        }

        $sql = "UPDATE `{$dbSites}` SET name='$name', department='$department', address1='$address1', address2='$address2', city='$city', ";
        $sql .= "county='$county', postcode='$postcode', country='$country', telephone='$telephone', fax='$fax', email='$email', ";
        $sql .= "websiteurl='$websiteurl', notes='$notes', typeid='$typeid', owner='$owner', freesupport='$incident_quantity', active='$activeStr' WHERE id='$site' LIMIT 1";

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
        echo $errors_string;
    }
}

echo show_edit_site($site, 'external');



include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>