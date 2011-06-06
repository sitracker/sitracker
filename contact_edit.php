<?php
// edit_contact.php - Form for editing a contact
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional!  31Oct05

$permission = 10; // Edit Contacts

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strEditContact;

// External variables
$contact = clean_int($_REQUEST['contact']);
$action = cleanvar($_REQUEST['action']);

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

// User has access
if (empty($action) OR $action == "showform" OR empty($contact))
{
    // Show select contact form
    echo "<h2>".icon('contact', 32)." {$strEditContact}</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}?action=edit' method='post'>";
    echo "<table align='center'>";
    echo "<tr><th>{$strContact}:</th><td>".contact_site_drop_down("contact", 0)."</td></tr>";
    echo "</table>";
    echo "<p align='center'><input name='submit' type='submit' value='{$strContinue}' /></p>";
    echo "</form>\n";
}
elseif ($action == "edit" && isset($contact))
{
    // FIMXE i18n
    // Show edit contact form
    $sql="SELECT * FROM `{$dbContacts}` WHERE id='{$contact}' ";
    $contactresult = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    while ($contactobj = mysql_fetch_object($contactresult))
    {
        // User does not have access
        echo "<h2>".icon('contact', 32)." ";
        echo "{$strEditContact}: {$contact}</h2>";
        echo "<form name='contactform' action='{$_SERVER['PHP_SELF']}?action=update' method='post' onsubmit='return confirm_action(\"{$strAreYouSureMakeTheseChanges}\");'>";
        echo "<p align='center'>".sprintf($strMandatoryMarked, "<sup class='red'>*</sup>")."</p>";
        echo "<table align='center' class='vertical'>";
        echo "<tr><th>{$strName}: <sup class='red'>*</sup><br />{$strTitle}, {$strForenames}, {$strSurname}</th>";
        echo "<td><input maxlength='50' name='courtesytitle' title='Courtesy Title (Mr, Mrs, Miss, Dr. etc.)' size='7' value='{$contactobj->courtesytitle}' />\n"; // i18n courtesy title
        echo "<input maxlength='100' name='forenames' size='15' title='Firstnames (or initials)' value='{$contactobj->forenames}' />\n";
        echo "<input maxlength='100' name='surname' size='20' title='{$strSurname}' value='{$contactobj->surname}' />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strTags}:</th><td><textarea rows='2' cols='60' name='tags'>";
        echo list_tags($contact, TAG_CONTACT, false)."</textarea></td></tr>\n";
        echo "<tr><th>{$strJobTitle}:</th><td>";
        echo "<input maxlength='255' name='jobtitle' size='40' value='{$contactobj->jobtitle}' />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strSite}: <sup class='red'>*</sup></th><td>";
        echo site_drop_down('siteid', $contactobj->siteid)."</td></tr>\n";
        echo "<tr><th>{$strDepartment}:</th><td>";
        echo "<input maxlength='100' name='department' size='40' value='{$contactobj->department}' />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strEmail}: <sup class='red'>*</sup></th><td>";
        echo "<input maxlength='100' name='email' size='40' value='{$contactobj->email}' />";
        echo "<label>";
        echo html_checkbox('dataprotection_email', $contactobj->dataprotection_email);
        echo "{$strEmail} {$strDataProtection}</label>";
        echo "</td></tr>\n";
        echo "<tr><th>{$strTelephone}:</th><td>";
        echo "<input maxlength='50' name='phone' size='40' value='{$contactobj->phone}' />";
        echo "<label>";
        echo html_checkbox('dataprotection_phone', $contactobj->dataprotection_phone);
        echo "{$strTelephone} {$strDataProtection}</label>";
        echo "</td></tr>\n";
        echo "<tr><th>{$strMobile}:</th><td>";
        echo "<input maxlength='50' name='mobile' size='40' value='{$contactobj->mobile}' /></td></tr>\n";
        echo "<tr><th>{$strFax}:</th><td>";
        echo "<input maxlength='50' name='fax' size='40' value='{$contactobj->fax}' /></td></tr>\n";
        echo "<tr><th>{$strActive}:</th><td><input type='checkbox' name='active' ";
        if ($contactobj->active == 'true') echo "checked='checked'";
        echo " value='true' /></td></tr> <tr><th></th><td>";
        echo "<input type='checkbox' id='usesiteaddress' name='usesiteaddress' value='yes' onclick='togglecontactaddress();' ";
        if ($contactobj->address1 !='')
        {
            echo "checked='checked'";
            $extraattributes = '';
        }
        else
        {
          $extraattributes = "disabled='disabled' ";
        }
        echo "/> ";
        echo "{$strSpecifyAddress}</td></tr>\n";
        echo "<tr><th>{$strAddress}:</th><td><label>";
        echo html_checkbox('dataprotection_address', $contactobj->dataprotection_address);
        echo " {$strAddress} {$strDataProtection}</label></td></tr>\n";
        echo "<tr><th>{$strAddress1}:</th><td>";
        echo "<input maxlength='255' id='address1' name='address1' size='40' value='{$contactobj->address1}' {$extraattributes} />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strAddress2}:</th><td>";
        echo "<input maxlength='255' id='address2' name='address2' size='40' value='{$contactobj->address2}' {$extraattributes} />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strCity}:</th><td>";
        echo "<input maxlength='255' id='city' name='city' size='40' value='{$contactobj->city}' {$extraattributes} />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strCounty}:</th><td>";
        echo "<input maxlength='255' id='county' name='county' size='40' value='{$contactobj->county}' {$extraattributes} />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strPostcode}:</th><td>";
        echo "<input maxlength='255' id='postcode' name='postcode' size='40' value='{$contactobj->postcode}' {$extraattributes} />";
        echo "</td></tr>\n";
        echo "<tr><th>{$strCountry}:</th><td>";
        echo country_drop_down('country', $contactobj->country, $extraattributes);
        echo "</td></tr>\n";
        echo "<tr><th>{$strNotifyContact}:</th><td>";
        echo contact_site_drop_down('notify_contactid', $contactobj->notify_contactid, $contactobj->siteid, $contact, TRUE, TRUE);
        echo "</td></tr>\n";
        echo "<tr><th>{$strNotes}:</th><td>";
        echo "<textarea rows='5' cols='60' name='notes'>{$contactobj->notes}</textarea></td></tr>\n";

        plugin_do('edit_contact_form');
        echo "</table>";

        echo "<input name='contact' type='hidden' value='{$contact}' />";

        echo "<p align='center'><input name='submit' type='submit' value='{$strSave}' /></p>";
        echo "</form>\n";
    }
}
else if ($action == "update")
{
    // External variables
    $contact = clean_int($_POST['contact']);
    $courtesytitle = clean_dbstring($_POST['courtesytitle']);
    $surname = clean_dbstring($_POST['surname']);
    $forenames = clean_dbstring($_POST['forenames']);
    $siteid = clean_int($_POST['siteid']);
    $email = strtolower(clean_dbstring($_POST['email']));
    $phone = convert_string_null_safe(clean_dbstring($_POST['phone']));
    $mobile = convert_string_null_safe(clean_dbstring($_POST['mobile']));
    $fax = convert_string_null_safe(clean_dbstring($_POST['fax']));
    $address1 = convert_string_null_safe(clean_dbstring($_POST['address1']));
    $address2 = convert_string_null_safe(clean_dbstring($_POST['address2']));
    $city = convert_string_null_safe(clean_dbstring($_POST['city']));
    $county = convert_string_null_safe(clean_dbstring($_POST['county']));
    $postcode = convert_string_null_safe(clean_dbstring($_POST['postcode']));
    $country = convert_string_null_safe(clean_dbstring($_POST['country']));
    $notes = convert_string_null_safe(clean_dbstring($_POST['notes']));
    $dataprotection_email = clean_dbstring($_POST['dataprotection_email']);
    $dataprotection_address = clean_dbstring($_POST['dataprotection_address']);
    $dataprotection_phone = clean_dbstring($_POST['dataprotection_phone']);
    $active = clean_dbstring($_POST['active']);
    $jobtitle = clean_dbstring($_POST['jobtitle']);
    $department = clean_dbstring($_POST['department']);
    $notify_contactid = clean_int($_POST['notify_contactid']);
    $tags = clean_dbstring($_POST['tags']);

    // Save changes to database
    $errors = 0;

    // VALIDATION CHECKS */

    // check for blank name
    if ($surname == '')
    {
        $errors = 1;
        echo user_alert(sprintf($strFieldMustNotBeBlank, "'{$strSurname}'"), E_USER_ERROR);
    }
    // check for blank site
    if ($siteid == '')
    {
        $errors = 1;
        echo user_alert(sprintf($strFieldMustNotBeBlank, "'{$strSiteName}'"), E_USER_ERROR);
    }
    // check for blank name
    if ($email == '' OR $email == 'none' OR $email == 'n/a')
    {
        $errors = 1;
        echo user_alert(sprintf($strFieldMustNotBeBlank, "'{$strEmail}'"), E_USER_ERROR);
        echo user_alert($strMustEnterEmail, E_USER_ERROR);
    }
    // check for blank contact id
    if ($contact == '')
    {
        $errors = 1;
        trigger_error("Something weird has happened, better call technical support", E_USER_ERROR);
    }

    // edit contact if no errors
    if ($errors == 0)
    {
        // update contact
        if ($dataprotection_email != '') $dataprotection_email = 'Yes';
        else $dataprotection_email = 'No';
        if ($dataprotection_phone  != '') $dataprotection_phone = 'Yes';
        else $dataprotection_phone = 'No';
        if ($dataprotection_address  != '') $dataprotection_address = 'Yes';
        else $dataprotection_address = 'No';

        if ($active == 'true') $activeStr = 'true';
        else $activeStr = 'false';

        /*
            TAGS
        */
        replace_tags(1, $contact, $tags);

        $sql = "UPDATE `{$dbContacts}` SET courtesytitle='{$courtesytitle}', surname='{$surname}', forenames='{$forenames}', siteid='{$siteid}', email='{$email}', phone={$phone}, mobile={$mobile}, fax={$fax}, ";
        $sql .= "address1={$address1}, address2={$address2}, city={$city}, county={$county}, postcode={$postcode}, ";
        $sql .= "country={$country}, dataprotection_email='{$dataprotection_email}', dataprotection_phone='{$dataprotection_phone}', ";
        $sql .= "notes={$notes}, dataprotection_address='{$dataprotection_address}', department='{$department}', jobtitle='{$jobtitle}', ";
        $sql .= "notify_contactid='{$notify_contactid}', ";
        $sql .= "active = '{$activeStr}}', ";
        $sql .= "timestamp_modified={$now} WHERE id='{$contact}'";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (!$result)
        {
            trigger_error("Update of contact failed: {$sql}", E_USER_WARNING);
        }
        else
        {
            plugin_do('save_contact_form');

            journal(CFG_LOGGING_NORMAL, 'Contact Edited', "Contact {$contact} was edited", CFG_JOURNAL_CONTACTS, $contact);
            html_redirect("contact_details.php?id={$contact}");
            exit;
        }
    }
}
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>