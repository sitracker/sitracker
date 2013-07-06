<?php
// maintenance_details.php - Show contract details
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// Created: 20th August 2001
// Purpose: Show All Maintenance Contract Details
// This Page Is Valid XHTML 1.0 Transitional! 27Oct05

require ('core.php');
$permission = PERM_CONTRACT_VIEW;  // view Maintenance contracts
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$id = clean_int($_REQUEST['id']);
$title = ("$strContract - $strContractDetails");

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

plugin_do('contract_details');

// Display Maintenance
$sql  = "SELECT m.*, m.notes AS maintnotes, s.name AS sitename, ";
$sql .= "r.name AS resellername, lt.name AS licensetypename, p.name AS productname, p.active AS productactive ";
$sql .= "FROM `{$dbMaintenance}` AS m, `{$dbSites}` AS s, ";
$sql .= "`{$dbResellers}` AS r, `{$dbLicenceTypes}` AS lt, ";
$sql .= "`{$dbProducts}` AS p ";
$sql .= "WHERE s.id = m.site ";
$sql .= "AND m.id='{$id}' ";
$sql .= "AND m.reseller = r.id ";
$sql .= "AND m.product = p.id ";
$sql .= "AND (m.licence_type IS NULL OR m.licence_type = lt.id) ";

$maintresult = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

$maint = mysql_fetch_object($maintresult);

echo "<table class='maintable vertical'>";
echo "<tr><th>{$strContract} {$strID}:</th>";
echo "<td><h3>".icon('contract', 32)." ";
echo "{$maint->id}</h3></td></tr>";
echo "<tr><th>{$strStatus}:</th><td>";
$active = true;
if ($maint->term == 'yes')
{
    echo "<strong>{$strTerminated}</strong>";
    $active = false;
}
else
{
    if ($maint->expirydate < $now AND $maint->expirydate != '-1')
    {
        echo "<span class='expired'>{$strExpired}</span>";
        $active = false;
    }
    else
    {
        echo $strActive;
    }
}


echo "</td></tr>\n";
echo "<tr><th>{$strSite}:</th>";

echo "<td><a href=\"site_details.php?id={$maint->site}\">{$maint->sitename}</a></td></tr>";

echo "<tr><th>{$strAdminContact}:</th>";

echo "<td><a href=\"contact_details.php?id=";
echo "{$maint->admincontact}\">";
echo contact_realname($maint->admincontact)."</a></td></tr>";

echo "<tr><th>{$strReseller}:</th><td>";

if (empty($maint->resellername))
{
    echo $strNoReseller;
}
else
{
    echo $maint->resellername;
}
echo "</td></tr>";
if ($maint->productactive == 'false')
{
    $style = "class='terminatedtext'";
    $productstr = "<br />{$strProductNoLongerAvailable}";
}
echo "<tr><th>{$strProduct}:</th><td {$style}>{$maint->productname} {$productstr}</td></tr>";
echo "<tr><th>{$strIncidents}:</th>";
echo "<td>";
$incidents_remaining = $maint->incident_quantity - $maint->incidents_used;

if ($maint->incident_quantity == 0)
{
    $quantity = $strUnlimited;
}
else
{
    $quantity = $maint->incident_quantity;
}

echo sprintf($strUsedNofN, $maint->incidents_used, $quantity);
if ($maint->incidents_used >= $maint->incident_quantity AND
    $maint->incident_quantity != 0)
{
    echo " ({$strZeroRemaining})";
}

echo "</td></tr>";
if ($maint->licence_quantity != '0')
{
    echo "<tr><th>{$strLicense}:</th>";
    echo "<td>{$maint->licence_quantity} {$maint->licensetypename}</td></tr>\n";
}

echo "<tr><th>{$strServiceLevel}:</th><td>".get_sla_name($maint->servicelevel)."</td></tr>";
echo "<tr><th>{$strExpiryDate}:</th><td>";
if ($maint->expirydate == '-1')
{
    echo "{$strUnlimited}";
}
else
{
    echo ldate($CONFIG['dateformat_date'], $maint->expirydate);
}

echo "</td></tr>";

$timed = servicelevel_timed($maint->servicelevel);
echo "<tr><th>{$strService}</th><td>";
echo contract_service_table($id, $timed);
echo "</td></tr>\n";

if ($timed)
{
    $billingObj = get_billable_object_from_contract_id($id);
    echo "<tr><th>{$strBalance}</th><td>".$billingObj->format_amount(get_contract_balance($id, TRUE, TRUE), 2);
    echo " (&cong;".contract_unit_balance($id, TRUE, TRUE)." units)";
    echo "</td></tr>";
}

if ($maint->maintnotes != '')
{
    echo "<tr><th>{$strNotes}:</th><td>{$maint->maintnotes}</td></tr>";
}
plugin_do('contract_details_table');
echo "</table>";

$operations = array();
$operations[$strEditContract] = array('url' => "contract_edit.php?action=edit&amp;maintid={$id}", 'perm' => PERM_CONTRACT_EDIT);

if ($maint->term != 'yes')
{
    $operations[$strNewService] = "contract_new_service.php?contractid={$id}";
}
echo "<p align='center'>".html_action_links($operations)."</p>";

echo "<h3>{$strNamedContacts}</h3>";

if (mysql_num_rows($maintresult) > 0)
{
    if ($maint->allcontactssupported == 'yes')
    {
        echo "<p class='info'>{$strAllSiteContactsSupported}</p>";
    }
    else
    {
        $allowedcontacts = $maint->supportedcontacts;

        $supportedcontacts = supported_contacts($id);
        $numberofcontacts = 0;

        $numberofcontacts = sizeof($supportedcontacts);
        if ($allowedcontacts == 0)
        {
            $allowedcontacts = $strUnlimited;
        }
        echo "<table class='maintable'>";
        $supportcount = 1;

        if ($numberofcontacts > 0)
        {
            foreach ($supportedcontacts AS $contact)
            {
                echo "<tr><th>{$strContact} #{$supportcount}:</th>";
                echo "<td>".icon('contact', 16)." ";
                echo "<a href=\"contact_details.php?";
                echo "id={$contact}\">".contact_realname($contact)."</a>, ";
                echo contact_site($contact). "</td>";

                echo "<td><a href=\"contract_delete_contact.php?contactid={$contact}&amp;maintid={$id}&amp;context=maintenance\">{$strRemove}</a></td></tr>\n";
                $supportcount++;
            }
        }
        else
        {
            echo "<tr><td>".user_alert($strNoRecords, E_USER_NOTICE)."</td></tr>";
        }
        echo "</table>";
    }

    if ($maint->allcontactssupported != 'yes')
    {
        echo "<p align='center'>";
        echo sprintf($strUsedNofN,
                        "<strong>{$numberofcontacts}</strong>",
                        "<strong>{$allowedcontacts}</strong>");
        echo "</p>";

        if ($numberofcontacts < $allowedcontacts OR $allowedcontacts == 0)
        {
            echo "<p align='center'><a href='contract_new_contact.php?maintid={$id}&amp;siteid={$maint->site}&amp;context=maintenance'>";
            if ($active) echo "{$strNewNamedContact}</a></p>";
        }
    }

    echo "<br />";
    echo "<h3>{$strSkillsSupportedUnderContract}:</h3>";
    // supported software
    $sql = "SELECT * FROM `{$dbSoftwareProducts}` AS sp, `{$dbSoftware}` AS s ";
    $sql .= "WHERE sp.softwareid = s.id AND productid='{$maint->product}' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result)>0)
    {
        echo"<table class='maintable'>";
        while ($software = mysql_fetch_object($result))
        {
            $software->lifetime_end = mysql2date($software->lifetime_end);
            echo "<tr><td> ".icon('skill', 16)." ";
            if ($software->lifetime_end > 0 AND $software->lifetime_end < $now)
            {
                echo "<span class='deleted'>";
            }
            echo $software->name;
            if ($software->lifetime_end > 0 AND $software->lifetime_end < $now)
            {
                echo "</span>";
            }
            echo "</td></tr>\n";
        }
        echo "</table>\n";
    }
    else
    {
        echo "<p align='center'>{$strNone} / {$strUnknown}</p>";
    }
}
else
{
    $html = user_alert($strNothingToDisplay, E_USER_NOTICE);
}


include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>