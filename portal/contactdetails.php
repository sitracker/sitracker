<?php
// portal/contact_details.php - Shows contact details
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Kieran Hogg <kieran[at]sitracker.org>

require ('..' . DIRECTORY_SEPARATOR . 'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

if ($_SESSION['usertype'] == 'admin')
{
    if (empty($_REQUEST['id']))
    {
        $accesslevel = 'any';
    }
    else
    {
        $accesslevel = 'admin';
    }
}
else
{
    $accesslevel = 'any';
}

include (APPLICATION_LIBPATH . 'portalauth.inc.php');


if ($_SESSION['usertype'] == 'admin')
{
    if (empty($_REQUEST['id']))
    {
        $id = $_SESSION['contactid'];
    }
    else
    {
        $id = clean_int($_REQUEST['id']);
    }
}
else
{
    $id = $_SESSION['contactid'];
}

//if new details posted
if (cleanvar($_REQUEST['action']) == 'update')
{
    if ($CONFIG['portal_usernames_can_be_changed'] AND $_SESSION['contact_source'] == 'sit')
    {
        $username = cleanvar($_REQUEST['username']);
        $oldusername = cleanvar($_REQUEST['oldusername']);
    }
    $forenames = cleanvar($_REQUEST['forenames']);
    $surname = cleanvar($_REQUEST['surname']);
    $department = cleanvar($_REQUEST['department']);
    $address1 = convert_string_null_safe(cleanvar($_REQUEST['address1']));
    $address2 = convert_string_null_safe(cleanvar($_REQUEST['address2']));
    $county = convert_string_null_safe(cleanvar($_REQUEST['county']));
    $country = convert_string_null_safe(cleanvar($_REQUEST['country']));
    $postcode = convert_string_null_safe(cleanvar($_REQUEST['postcode']));
    $phone = convert_string_null_safe(cleanvar($_REQUEST['phone']));
    $mobile = convert_string_null_safe(cleanvar($_REQUEST['mobile']));
    $fax = convert_string_null_safe(cleanvar($_REQUEST['fax']));
    $email = cleanvar($_REQUEST['email']);
    $newpass = cleanvar($_REQUEST['newpassword']);
    $newpass2 = cleanvar($_REQUEST['newpassword2']);

    $_SESSION['formdata']['portalcontactdetails'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);
    
    $errors = 0;

    // VALIDATION CHECKS */
    if ($CONFIG['portal_usernames_can_be_changed'] AND ($oldusername != $username))
    {
        if (!valid_username($username))
        {
            $errors++;
            $_SESSION['formerrors']['portalcontactdetails']['username'] = $strInvalidUsername;
        }
    }

    if (!empty($newpass) AND empty($newpass2))
    {
        $errors++;
        $_SESSION['formerrors']['portalcontactdetails']['passwordtwice'] = $strYouMustEnterYourNewPasswordTwice;
    }
    elseif ($newpass != $newpass2)
    {
        $errors++;
        $_SESSION['formerrors']['portalcontactdetails']['passwordmatch'] = $strPasswordsDoNotMatch;
    }

    if ($surname == '')
    {
        $errors++;
        $_SESSION['formerrors']['portalcontactdetails']['surname'] = sprintf($strFieldMustNotBeBlank, $strSurname);
    }

    if ($email == '' OR $email == 'none' OR $email == 'n/a')
    {
        $errors++;
        $_SESSION['formerrors']['portalcontactdetails']['email'] = sprintf($strFieldMustNotBeBlank, $strEmail);
    }

    if ($errors == 0)
    {
        $updatesql = "UPDATE `{$dbContacts}` SET ";
        if ($CONFIG['portal_usernames_can_be_changed'] AND $_SESSION['contact_source'] == 'sit')
        {
            $updatesql .= "username='{$username}', ";
        }
        $updatesql .= " forenames='{$forenames}', surname='{$surname}', ";
        $updatesql .= "department='{$department}', address1={$address1}, address2={$address2}, ";
        $updatesql .= "county={$county}, country={$country}, postcode={$postcode}, ";
        $updatesql .= "phone={$phone}, mobile={$mobile}, fax={$fax}, email='{$email}'";
        if ($newpass != '')
        {
            $updatesql .= ", password=MD5('{$newpass}') ";
        }
        $updatesql .= "WHERE id='{$id}'";
        mysql_query($updatesql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        clear_form_data('portalcontactdetails');
        if ($_SESSION['contactid'] != $id)
        {
            html_redirect($_SERVER['PHP_SELF']."?id={$id}");
        }
        else html_redirect($_SERVER['PHP_SELF']);
    }
    else
    {
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}
elseif (isset($_POST['add']))
{
    $maintid = clean_int($_POST['maintid']);
    $contactid = clean_int($_GET['id']);

    if ($maintid == 0 OR $contactid == 0)
    {
        trigger_error("Maintid or contactid blank", E_USER_ERROR);
    }
    else
    {
        $sql = "INSERT INTO `{$dbSupportContacts}`(`maintenanceid`, `contactid`) ";
        $sql .= "VALUES('{$maintid}', '{$contactid}') ";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        html_redirect($_SERVER['PHP_SELF']."?id={$id}");
    }
}
else
{
    $sql = "SELECT c.* ";
    $sql .= "FROM `{$dbContacts}` AS c, `{$dbSites}` AS s ";
    $sql .= "WHERE c.siteid = s.id ";
    $sql .= "AND c.id={$id}";
    $query = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $user = mysql_fetch_object($query);

    include (APPLICATION_INCPATH . 'portalheader.inc.php');
    if ($user->siteid != $_SESSION['siteid'])
    {
        echo "<p class='error'>{$strPermissionDenied}</p>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        exit;
    }
    echo "<h2>".icon('contact', 32, $strContact)." {$user->forenames} {$user->surname}";
    echo ' '.gravatar($user->email, 32);
    echo "</h2>";

    echo show_form_errors('portalcontactdetails');
    clear_form_errors('portalcontactdetails');

    echo "<form action='{$_SERVER[PHP_SELF]}?action=update' method='post'>";
    echo "<table class='maintable vertical'>";

    if ($CONFIG['portal_usernames_can_be_changed'] && $_SESSION['contact_source'] == 'sit')
    {
        echo "<tr><th>{$strUsername}</th><td>";
        echo "<input class='required' name='username' value='".show_form_value('portalcontactdetails', 'username', $user->username)."' />";
        echo " <span class='required'>{$strRequired}</span>\n";
        echo "<input name='oldusername' value='{$user->username}' type='hidden' /></td></tr>\n";

    }
    echo "<tr><th>{$strForenames}</th><td>";
    if ($_SESSION['contact_source'] != 'sit' AND !empty($CONFIG['ldap_forenames']))
    {
        echo "<input type='hidden' name='forenames' value='{$user->forenames}' />".$user->forenames;
    }
    else
    {
        echo "<input class='required' name='forenames' value='".show_form_value('portalcontactdetails', 'forenames', $user->forenames)."' />";
    }
    echo " <span class='required'>{$strRequired}</span>\n";
    echo "</td></tr>\n";
    echo "<tr><th>{$strSurname}</th><td>";
    if ($_SESSION['contact_source'] != 'sit' AND !empty($CONFIG['ldap_surname']))
    {
        echo "<input type='hidden'  name='surname' value='{$user->surname}' />".$user->surname;
    }
    else
    {
        echo "<input class='required' name='surname' value='".show_form_value('portalcontactdetails', 'surname', $user->surname)."' />";
        echo " <span class='required'>{$strRequired}</span>";
    }
    echo "</td></tr>\n";
    echo "<tr><th>{$strDepartment}</th><td><input name='department' value='".show_form_value('portalcontactdetails', 'department', $user->department)."' /></td></tr>\n";
    echo "<tr><th>{$strAddress1}</th><td><input name='address1' value='".show_form_value('portalcontactdetails', 'address1', $user->address1)."' /></td></tr>\n";
    echo "<tr><th>{$strAddress2}</th><td><input name='address2' value='".show_form_value('portalcontactdetails', 'address2', $user->address2)."' /></td></tr>\n";
    echo "<tr><th>{$strCounty}</th><td><input name='county' value='".show_form_value('portalcontactdetails', 'county', $user->county)."' /></td></tr>\n";
    echo "<tr><th>{$strCountry}</th><td><input name='country' value='".show_form_value('portalcontactdetails', 'country', $user->country)."' /></td></tr>\n";
    echo "<tr><th>{$strPostcode}</th><td><input name='postcode' value='".show_form_value('portalcontactdetails', 'postcode', $user->postcode)."' /></td></tr>\n";
    echo "<tr><th>{$strTelephone}</th><td>";
    if ($_SESSION['contact_source'] != 'sit' AND !empty($CONFIG['ldap_telephone']))
    {
        echo "<input type='hidden' name='phone' value='{$user->phone}' />".$user->phone;
    }
    else
    {
        echo "<input name='phone' value='".show_form_value('portalcontactdetails', 'phone', $user->phone)."' />";
    }
    echo "</td></tr>\n";
    echo "<tr><th>{$strMobile}</th><td>";
    if ($_SESSION['contact_source'] != 'sit' AND !empty($CONFIG['ldap_mobile']))
    {
        echo "<input type='hidden' name='mobile' value='{$user->mobile}' />".$user->mobile;
    }
    else
    {
        echo "<input name='mobile' value='".show_form_value('portalcontactdetails', 'mobile', $user->mobile)."' />";
    }
    echo "</td></tr>\n";
    echo "<tr><th>{$strFax}</th><td>";
    if ($_SESSION['contact_source'] != 'sit' AND !empty($CONFIG['ldap_fax']))
    {
        echo "<input type='hidden' name='fax' value='{$user->fax}' />".$user->fax;
    }
    else
    {
        echo "<input name='fax' value='".show_form_value('portalcontactdetails', 'fax', $user->fax)."' />";
    }
    echo "</td></tr>";
    echo "<tr><th>{$strEmail}</th><td>";
    if ($_SESSION['contact_source'] != 'sit' AND !empty($CONFIG['ldap_email']))
    {
        echo "<input type='hidden' name='email' value='{$user->email}' />{$user->email}";
    }
    else
    {
        echo "<input class='required' name='email' value='".show_form_value('portalcontactdetails', 'email', $user->email)."' />";
        echo " <span class='required'>{$strRequired}</span>";
    }
    echo "</td></tr>\n";

    if ( $_SESSION['contact_source'] == 'sit' )
    {
        echo "<tr><th>{$strNewPassword}</th><td><input name='newpassword' value='' type='password' /></td></tr>\n";
        echo "<tr><th>{$strConfirmNewPassword}</th><td><input name='newpassword2' value='' type='password' /></td></tr>\n";
    }
    echo "</table>";
    echo "<p class='formbuttoms'>";
    echo "<input type='hidden' name='id' value='{$id}' />";
    echo "<input type='reset' value='{$strReset}' /> ";
    echo "<input type='submit' value='{$strSave}' /></p></form>";

    echo "<br />".contracts_for_contacts_table($id, 'external');

    if ($_SESSION['usertype'] == 'admin')
    {
        echo "<h4>{$strAssociateContactWithContract}</h4>";
        echo "<form method='post' action='{$_SERVER['PHP_SELF']}?id={$id}'>";
        $exclude = contact_contracts($id, $_SESSION['siteid'], FALSE);
        echo "<p align='center'>".maintenance_drop_down('maintid', 0, $_SESSION['siteid'], $exclude, FALSE, FALSE, $sit[2])."<br />";
        echo "<input type='submit' name='add' value='{$strSave}' /></p></form>";
    }
    
    clear_form_data('portalcontactdetails');
    
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>