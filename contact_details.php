<?php
// contact_details.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// Created: 24th May 2001
// Purpose: Show All Contact Details
// This Page Is Valid XHTML 1.0 Transitional! 27Oct05


$permission = PERM_CONTACT_VIEW;  // view contacts

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
$title = 'Contact Details';

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$id = clean_int($_REQUEST['id']);
$output = cleanvar($_REQUEST['output']);
$title = $strContactDetails;

if ($output == 'vcard')
{
    header("Content-type: text/x-vCard\r\n");
    header("Content-disposition-type: attachment\r\n");
    header("Content-disposition: filename=contact.vcf");
    echo contact_vcard($id);
    exit;
}

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

// Display contacts
$sql = "SELECT * FROM `{$dbContacts}` WHERE id='{$id}' ";
$contactresult = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
while ($contact = mysql_fetch_object($contactresult))
{
    // Lookup the site address if this contact hasn't got a specific address set
    if ($contact->address1 == '')
    {
        $sitesql = "SELECT * FROM `{$dbSites}` WHERE id='{$contact->siteid}' LIMIT 1";
        $siteresult = mysql_query($sitesql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $site = mysql_fetch_object($siteresult);
        $address1 = $site->address1;
        $address2 = $site->address2;
        $city = $site->city;
        $county = $site->county;
        $country = $site->country;
        $postcode = $site->postcode;
    }
    else
    {
        $address1 = $contact->address1;
        $address2 = $contact->address2;
        $city = $contact->city;
        $county = $contact->county;
        $country = $contact->country;
        $postcode = $contact->postcode;
    }

    echo "<h2>".icon('contact', 32)." {$strContactDetails}</h2>";

    echo "<table align='center' class='vertical'>";
    echo "<tr><th colspan='2'><h3>".gravatar($contact->email, 32)." {$contact->forenames} {$contact->surname}";
    echo "</h3></th></tr>\n";
    if ($contact->active == 'false')
    {
        echo "<tr><th>{$strStatus}:</th><td><span class='expired'>{$strInactive}</span></td></tr>\n";
    }
    $tags = list_tags($id, 1, TRUE);
    if (!empty($tags))
    {
        echo "<tr><th>{$strTags}:</th><td>{$tags}</td></tr>\n";
    }

    echo "<tr><th>{$strJobTitle}:</th><td>{$contact->jobtitle}</td></tr>\n";
    echo "<tr><th>{$strSite}:</th><td>";
    echo "<a href='site_details.php?id={$contact->siteid}'>";
    echo site_name($contact->siteid)."</a></td></tr>\n";
    if (!empty($contact->department))
    {
        echo "<tr><th>{$strDepartment}:</th>";
        echo "<td>{$contact->department}</td></tr>\n";
    }

    if ($contact->dataprotection_address != 'Yes')
    {
        echo "<tr><th>{$strAddress1}:</th><td>{$address1}</td></tr>\n";
        echo "<tr><th>{$strAddress2}:</th><td>{$address2}</td></tr>\n";
        echo "<tr><th>{$strCity}:</th><td>{$city}</td></tr>\n";
        echo "<tr><th>{$strCounty}:</th><td>{$county}</td></tr>\n";
        echo "<tr><th>{$strPostcode}:</th><td>{$postcode}";
        if (!empty($postcode))
        {
            //TODO make this support different links via config
            echo "(<a target='_new' href='http://www.google.com/maps?q={$postcode}'>{$strMap}</a>)";
        }
        echo "</td></tr>\n";
        echo "<tr><th>{$strCountry}:</th><td>{$country}</td></tr>\n";
    }

    if ($contact->dataprotection_email != 'Yes')
    {
        echo "<tr><th>{$strEmail}:</th>";
        echo "<td><a href=\"mailto:{$contact->email}\">{$contact->email}</a></td></tr>\n";
    }

    if ($contact->dataprotection_phone != 'Yes')
    {
        echo "<tr><th>{$strTelephone}</th><td>{$contact->phone}</td></tr>\n";
        echo "<tr><th>{$strMobile}</th><td>{$contact->mobile}</td></tr>\n";
        echo "<tr><th>{$strFax}</th><td>{$contact->fax}</td></tr>\n";
    }
    echo "<tr><th>{$strDataProtection}</th><td> ";

    if ($contact->dataprotection_email == 'Yes')
    {
        echo "<strong>{$strNoEmail}</strong>, ";
    }
    else
    {
        echo "{$strEmailOK}, ";
    }

    if ($contact->dataprotection_phone == 'Yes')
    {
        echo "<strong>{$strNoCalls}</strong>, ";
    }
    else
    {
        echo "{$strCallsOK}, ";
    }

    if ($contact->dataprotection_address == 'Yes')
    {
        echo "<strong>{$strNoPost}</strong>";
    }
    else
    {
        echo "{$strPostOK} ";
    }

    echo "</td></tr>\n";
    echo "<tr><th>{$strNotes}</th><td>";
    echo nl2br($contact->notes)."</td></tr>\n";

    echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
    // Only show access details if portal is enabled
    if ($CONFIG['portal'] == TRUE)
    {
        echo "<tr><th>{$strAccessDetails}</th>";
        echo "<td>{$strUsername}: <code>{$contact->username}</code>";
        echo ", <a href='forgotpwd.php?action=sendpwd&amp;contactid=".urlencode($contact->id)."'>{$strSendPassword}</a>";
        // echo ", password: <code>".$contact->password']."</code>";  ## Passwords no longer controlled from SiT INL 23Nov04
        echo "</td></tr>\n";
    }
    echo "<tr><th>{$strIncidents}:</th><td>";
    $openincidents = contact_count_open_incidents($id);
    $totalincidents = contact_count_incidents($id);
    if ($totalincidents == 0)
    {
        echo $strNone;
    }

    if ($openincidents >= 1)
    {
        echo sprintf($strNumOpenIncidents, $openincidents).", ";
    }

    if ($totalincidents >= 1)
    {
        echo "$totalincidents $strLogged, <a href='contact_support.php?id={$id}'>{$strSeeHere}</a>";
    }

    echo "</td></tr>\n";

    if ($contact->notify_contactid > 0)
    {
        echo "<tr><th>{$strNotifyContact}</th><td>";
        echo contact_realname($contact->notify_contactid);
        $notify_contact1 = contact_notify($contact->notify_contactid, 1);
        if ($notify_contact1 > 0)
        {
            echo " -&gt; ".contact_realname($notify_contact1);
        }

        $notify_contact2 = contact_notify($contact->notify_contactid, 2);
        if ($notify_contact2 > 0)
        {
            echo " -&gt; ".contact_realname($notify_contact2);
        }

        $notify_contact3 = contact_notify($contact->notify_contactid, 3);
        if ($notify_contact3 > 0)
        {
            echo " -&gt; ".contact_realname($notify_contact3);
        }
        echo "</td></tr>\n";
    }

    if ($contact->timestamp_modified > 0)
    {
        echo "<tr><th>{$strLastUpdated}</th>";
        echo "<td>".ldate($CONFIG['dateformat_datetime'], $contact->timestamp_modified)."</td></tr>\n";
    }

    echo "<tr><th>{$strInventoryItems}</th>";
    echo "<td>".contact_count_inventory_items($id)." ";
    echo "<a href='inventory.php?site=".contact_siteid($id)."'>{$strSeeHere}</a>";
    echo "</td></tr>";
    plugin_do('contact_details');

    echo "</table>\n";

    echo "<p align='center'>";
    echo "<a href='incident_new.php?action=findcontact&amp;contactid={$id}'>{$strNewIncident}</a> | ";
    echo "<a href='contact_details.php?id={$id}&amp;output=vcard'>".icon('vcard', 16)." vCard</a> | ";
    echo "<a href='contact_edit.php?action=edit&amp;contact={$id}'>{$strEdit}</a> | ";
    echo "<a href='contact_delete.php?id={$id}'>{$strDelete}</a>";
    echo "</p>\n";

    // Check if user has permission to view maintenace contracts, if so display those related to this contact

    echo contracts_for_contacts_table($id);
}
mysql_free_result($contactresult);

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>