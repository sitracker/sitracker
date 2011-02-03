<?php
// site_details.php - Show all site details
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// Created: 9th March 2001
// This Page Is Valid XHTML 1.0 Transitional! 27Oct05


$permission = 11; // View Sites
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require_once (APPLICATION_LIBPATH . 'billing.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$id = clean_int($_REQUEST['id']);

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

if ($id=='')
{
    echo "<p class='error'>{$strMustSelectASite}</p>";
    exit;
}

// Display site
echo "<table align='center' class='vertical'>";
$sql="SELECT * FROM `{$dbSites}` WHERE id='$id' ";
$siteresult = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
while ($siterow = mysql_fetch_array($siteresult))
{
    echo "<tr><th>{$strSite}:</th><td>";
    echo "<h3>".icon('site', 32)." ".$siterow['name']."</h3>";
    echo "</td></tr>";
    if ($siterow['active'] == 'false')
    {
        echo "<tr><th>{$strStatus}:</th><td><span class='expired'>{$strInactive}</span></td></tr>";
    }
    $tags = list_tags($id, TAG_SITE, TRUE);
    if (!empty($tags))
    {
        echo "<tr><th>{$strTags}:</th><td>{$tags}</td></tr>";
    }

    echo "<tr><th>{$strDepartment}:</th><td>{$siterow['department']}</td></tr>";
    echo "<tr><th>{$strAddress1}:</th><td>{$siterow['address1']}</td></tr>";
    echo "<tr><th>{$strAddress2}:</th><td>{$siterow['address2']}</td></tr>";
    echo "<tr><th>{$strCity}:</th><td>{$siterow['city']}</td></tr>";
    echo "<tr><th>{$strCounty}:</th><td>{$siterow['county']}</td></tr>";
    echo "<tr><th>{$strCountry}:</th><td>{$siterow['country']}</td></tr>";
    echo "<tr><th>{$strPostcode}:</th><td>{$siterow['postcode']} ";
    if (!empty($siterow['postcode']))
    {
        //TODO make this support different links via config
        echo "(<a href='http://www.google.com/maps?q={$siterow['postcode']}'>{$strMap}</a>)";
    }
    echo "</td></tr>";
    echo "<tr><th>{$strTelephone}:</th><td>{$siterow['telephone']}</td></tr>";
    echo "<tr><th>{$strFax}:</th><td>{$siterow['fax']}</td></tr>";
    echo "<tr><th>{$strEmail}:</th><td><a href=\"mailto:".$siterow['email']."\">".$siterow['email']."</a></td></tr>";
    echo "<tr><th>{$strWebsite}:</th><td>";
    if (!empty($siterow['websiteurl']))
    {
        echo "<a href=\"{$siterow['websiteurl']}\">{$siterow['websiteurl']}</a>";
    }

    echo "</td></tr>";
    echo "<tr><th>{$strNotes}:</th><td>".nl2br($siterow['notes'])."</td></tr>";
    echo "<tr><td colspan='2'>&nbsp;</td></tr>";
    echo "<tr><th>{$strIncidents}:</th>";
    echo "<td>".site_count_incidents($id)." <a href=\"contact_support.php?id=".$siterow['id']."&amp;mode=site\">{$strSeeHere}</a></td></tr>";
    echo "<tr><th>{$strBillableIncidents}:</th><td><a href='transactions.php?site={$siterow['id']}'>{$strSeeHere}</a></td></tr>";

    $balance = $awaiting = $reserved = 0;

    $billable_contract = get_site_billable_contract_id($id);

    if ($billable_contract != -1)
    {
        $balance = contract_balance($billable_contract, TRUE, TRUE, TRUE);
        $awaiting = contract_transaction_total($billable_contract, BILLING_AWAITINGAPPROVAL);
        $reserved = contract_transaction_total($billable_contract, BILLING_RESERVED);
    }

    echo "<tr><th>{$strServiceBalance}</th><td>";
    echo "{$GLOBALS['strBalance']}: {$CONFIG['currency_symbol']}".number_format($balance, 2);
    if ($awaiting > 0) echo "<br />{$GLOBALS['strAwaitingApproval']}: {$CONFIG['currency_symbol']}".number_format($awaiting, 2);
    if ($reserved > 0) echo "<br />{$GLOBALS['strReserved']}: {$CONFIG['currency_symbol']}".number_format($reserved, 2);

    echo "</td></tr>";

    echo "<tr><th>{$strActivities}:</th><td>".open_activities_for_site($siterow['id'])." <a href='tasks.php?siteid={$siterow['id']}'>{$strSeeHere}</a></td></tr>";
    echo "<tr><th>{$strInventory}:</th>";
    echo "<td>".site_count_inventory_items($id);
    echo " <a href='inventory.php?site={$id}'>{$strSeeHere}</a></td></tr>";
    $billableunits = billable_units_site($siterow['id'], $now-2678400); // Last 31 days
    if ($billableunits > 0)
    {
        echo "<tr><th>".sprintf($strUnitsUsedLastXdays, 31).":</th><td>{$billableunits}</td></tr>"; // More appropriate label
    }
    echo "<tr><th>{$strIncidentPool}:</th><td>".sprintf($strRemaining, $siterow['freesupport'])."</td></tr>";
    echo "<tr><th>{$strSalesperson}:</th><td>";
    if ($siterow['owner'] >= 1)
    {
        echo user_realname($siterow['owner'],TRUE);
    }
    else
    {
        echo $strNotSet;
    }

    echo "</td></tr>\n";
}

plugin_do('site_details');
mysql_free_result($siteresult);

echo "</table>\n";
echo "<p align='center'><a href='site_edit.php?action=edit&amp;site={$id}'>{$strEdit}</a> | ";
echo "<a href='site_delete.php?id={$id}'>{$strDelete}</a>";
echo "</p>";

// Display Contacts
echo "<h3>{$strContacts}</h3>";

// List Contacts

$sql = "SELECT * FROM `{$dbContacts}` WHERE siteid='{$id}' ";
if ($_SESSION['userconfig']['show_inactive_data'] != 'TRUE')
{
    $sql .= "AND active = 'true' ";
}
$sql .= "ORDER BY active, surname, forenames";
$contactresult = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

$countcontacts = mysql_num_rows($contactresult);
if ($countcontacts > 0)
{
    echo "<p align='center'>".sprintf($strContactsMulti, $countcontacts)."</p>";
    echo "<table align='center'>";
    echo "<tr><th>{$strName}</th><th>{$strJobTitle}</th>";
    echo "<th>{$strDepartment}</th><th>{$strTelephone}</th>";
    echo "<th>{$strEmail}</th><th>{$strAddress}</th>";
    echo "<th>{$strDataProtection}</th><th>{$strNotes}</th></tr>";

    $shade = 'shade1';

    while ($contactrow = mysql_fetch_array($contactresult))
    {
        if ($contactrow['active'] == 'false') $shade='expired';
        echo "<tr class='$shade'>";
        echo "<td>".icon('contact', 16, $strContact);
        echo " <a href=\"contact_details.php?id=".$contactrow['id']."\">{$contactrow['forenames']} {$contactrow['surname']}</a></td>";
        echo "<td>{$contactrow['jobtitle']}</td>";
        echo "<td>{$contactrow['department']}</td>";
        if ($contactrow['dataprotection_phone'] != 'Yes')
        {
            echo "<td>{$contactrow['phone']}</td>";
        }
        else
        {
            echo "<td><strong>{$strWithheld}</strong></td>";
        }

        if ($contactrow['dataprotection_email'] != 'Yes')
        {
            echo "<td>{$contactrow['email']}</td>";
        }
        else
        {
            echo "<td><strong>{$strWithheld}</strong></td>";
        }

        if ($contactrow['dataprotection_address'] != 'Yes')
        {
            echo "<td>";
            if (!empty($contactrow['address1']))
            {
                echo $contactrow['address1'];
            }
            echo "</td>";
        }
        else echo "<td><strong>{$strWithheld}</strong></td>";

        echo "<td>";
        if ($contactrow['dataprotection_email'] == 'Yes')
        {
            echo "<strong>{$strNoEmail}</strong>, ";
        }

        if ($contactrow['dataprotection_phone'] == 'Yes')
        {
            echo "<strong>{$strNoCalls}</strong>, ";
        }

        if ($contactrow['dataprotection_address'] == 'Yes')
        {
            echo "<strong>{$strNoPost}</strong>";
        }

        echo "</td>";
        echo "<td>".nl2br(substr($contactrow['notes'], 0, 500))."</td>";
        echo "</tr>";
        if ($shade == 'shade1') $shade = 'shade2';
        else $shade = 'shade1';
    }
    echo "</table>\n";
}
else
{
    echo "<p align='center'>{$strNoContactsForSite}</p>";
}
echo "<p align='center'><a href='contact_add.php?siteid={$id}'>{$strAddContact}</a></p>";


// Valid user, check perms
if (user_permission($sit[2],19)) // View contracts
{
    echo "<h3>{$strContracts}<a id='contracts'></a></h3>";

    // Display contracts
    $sql  = "SELECT m.id AS maintid, m.term AS term, p.name AS product, r.name AS reseller, ";
    $sql .= "licence_quantity, lt.name AS licence_type, expirydate, admincontact, ";
    $sql .= "c.forenames AS admincontactsforenames, c.surname AS admincontactssurname, m.notes AS maintnotes ";
    $sql .= "FROM `{$dbContacts}` AS c, `{$dbProducts}` AS p, `{$dbMaintenance}` AS m ";
    $sql .= "LEFT JOIN `{$dbLicenceTypes}` AS lt ON m.licence_type = lt.id ";
    $sql .= "LEFT JOIN `{$dbResellers}` AS r ON r.id = m.reseller ";
    $sql .= "WHERE m.product = p.id ";
    $sql .= "AND admincontact = c.id AND m.site = '{$id}' ";
    if ($activeonly=='yes' OR$_SESSION['userconfig']['show_inactive_data'] != 'TRUE')
    {
        $sql .= "AND m.term != 'yes' ";
    }
    $sql .= "ORDER BY expirydate DESC";

    // connect to database and execute query
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $countcontracts = mysql_num_rows($result);
    if ($countcontracts > 0)
    {
        ?>
        <script type="text/javascript">
        //<![CDATA[
        function support_contacts_window(maintenanceid)
        {
            URL = "support_contacts.php?maintid=" + maintenanceid;
            window.open(URL, "support_contacts_window", "toolbar=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=450,height=240");
        }
        function contact_details_window(contactid)
        {
            URL = "contact_details.php?contactid=" + contactid;
            window.open(URL, "contact_details_window", "toolbar=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=450,height=240");
        }
        //]]>
        </script>
        <p align='center'>
        <?php
        echo mysql_num_rows($result)." $strContracts</p>";
        echo "<table align='center'>
        <tr>
            <th>{$strContractID}</th>
            <th>{$strProduct}</th>
            <th>{$strReseller}</th>
            <th>{$strLicense}</th>
            <th>{$strExpiryDate}</th>
            <th>{$strAdminContact}</th>
            <th>{$strNotes}</th>
        </tr>";
        $shade = 'shade1';
        while ($results = mysql_fetch_array($result))
        {
            if ($results['term'] == 'yes' OR
                ($results['expirydate'] < $now AND
                $results['expirydate'] != -1))
            {
            	$shade = "expired";
            }
            echo "<tr>";
            echo "<td class='{$shade}'>".icon('contract', 16)." ";
            echo "<a href='contract_details.php?id={$results['maintid']}'>{$strContract} {$results['maintid']}</a></td>";
            echo "<td class='{$shade}'>{$results['product']}</td>";
            echo "<td class='{$shade}'>";
            if (empty($results['reseller']))
            {
                echo $strNoReseller;
            }
            else
            {
                echo $results['reseller'];
            }

            echo "</td>";
            echo "<td class='{$shade}'>";

            if (empty($results['licence_type']))
            {
                echo $strNoLicense;
            }
            else
            {
                if ($results['licence_quantity'] == 0)
                {
                    echo "{$strUnlimited} ";
                }
                else
                {
                    echo "{$results['licence_quantity']} ";
                }
                echo $results['licence_type'];
            }

            echo "</td>";
            echo "<td class='{$shade}'>";
            if ($results['expirydate'] == -1)
            {
                echo $strUnlimited;
            }
            else
            {
                echo ldate($CONFIG['dateformat_date'], $results['expirydate']);
            }
            echo "</td>";
            echo "<td class='{$shade}'>{$results['admincontactsforenames']}  {$results['admincontactssurname']}</td>";
            echo "<td class='{$shade}'>";
            if ($results['maintnotes'] == '')
            {
                echo '&nbsp;';
            }
            else
            {
                echo nl2br($results['maintnotes']);
            }
            echo "</td>";
            echo "</tr>";

            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        echo "</table>\n";
    }
    else echo "<p align='center'>{$strNoContractsForSite}</p>";
    echo "<p align='center'><a href='contract_add.php?action=showform&amp;siteid=$id'>{$strAddContract}</a></p>";
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>