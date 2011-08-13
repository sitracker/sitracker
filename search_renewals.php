<?php
// search_renewals.php - Show contracts due for renewal
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_CONTRACT_VIEW; // View Maintenance Contracts
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strShowRenewals;

// External variables
$expire = cleanvar($_REQUEST['expire']);

// show search renewal form
if (empty($expire))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('contract', 32)." {$strShowRenewals}</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' >";

    printf("<p>{$strContractsExpiringWithinXdays}</p>", "<input maxlength='4' name='expire' size='3' type='text' />");
    echo "<p><input name='submit' type='submit' value=\"{$strSearch}\" /></p>";
    echo "</form>\n";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    // perform search
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    // check input
    if ($expire == '')
    {
        $errors = 1;
        echo "<p class='error'>{$strEnterNumberOfDays}</p>\n";
    }
    elseif (!is_numeric($expire))
    {
        $errors = 1;
        echo "<p class='error'>{$strEnterNumericValue}</p>\n";
    }
    if ($errors == 0)
    {
        // convert number of days into a timestamp
        $now = time();
        $max_expiry = $now + ($expire * 86400);
        // build SQL
        $sql  = "SELECT m.id AS maintid, s.name AS site, p.name AS product, r.name AS reseller, ";
        $sql .= "licence_quantity, l.name AS licence_type, expirydate, admincontact, ";
        $sql .= "c.forenames AS admincontactforenames, c.surname AS admincontactsurname, m.notes ";
        $sql .= "FROM `{$dbMaintenance}` AS m, `{$dbSites}` AS s, `{$dbContacts}` AS c, `{$dbProducts}` AS p, `{$dbLicenceTypes}` AS l, `{$dbResellers}` AS r ";
        $sql .= "WHERE (m.site = s.id AND product = p.id AND reseller = r.id AND if(licence_type = 0, 4, ifnull(licence_type, 4))=l.id  AND admincontact = c.id) AND ";
        $sql .= "expirydate <= {$max_expiry} AND expirydate >= {$now} ORDER BY expirydate ASC";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($result) == 0)
        {
            printf("<h2>".icon('contract', 32)." {$strContractsExpiringWithinXdays}</h2>", $expire);
            echo "<h5 class='warning'>{$strSorryNoSearchResults}</h5>\n";
        }
        else
        {
            printf("<h2>".icon('contract', 32)." {$strContractsExpiringWithinXdays}</h2>", $expire);
            printf("<h5>{$strResultsNum}</h5>", mysql_num_rows($result));
            echo "
            <table class='maintable'>
            <tr>
            <th>{$strID}</th>
            <th>{$strSite}</th>
            <th>{$strProduct}</th>
            <th>{$strReseller}</th>
            <th>{$strLicense}</th>
            <th>{$strExpiryDate}</th>
            <th>{$strAdminContact}</th>
            <th>{$strNotes}</th>
            </tr>";
            $shade = 'shade1';
            while ($results = mysql_fetch_object($result))
            {
                echo "<tr class='{$shade}'>";
                echo "<td align='center' width='50'><a href='contract_edit.php?action=edit&amp;maintid={$results->maintid}'>{$results->maintid}</a></td>";
                echo "<td align='center' width='100'>{$results->site}</td>";
                echo "<td align='center' width='100'>{$results->product}</td>";
                echo "<td align='center' width='100'>{$results->reseller}</td>";
                echo "<td align='center' width='75'>{$results->licence_quantity} {$results->licence_type}</td>";
                echo "<td align='center' width='100'>".ldate($CONFIG['dateformat_date'], $results->expirydate)."</td>";
                echo "<td align='center' width='100'><a href=\"javascript: contact_details_window('contact_details.php?id={$results->admincontact}')\">{$results->admincontactforenames} {$results->admincontactsurname}</a></td>";
                if ($results->notes == '')
                {
                    $notes = "&nbsp;";
                }
                else
                {
                    $notes = nl2br($results->notes);
                }
                echo "<td align='center'  width='150'>{$notes}</td>";
                echo "</tr>";

                if ($shade == 'shade1') $shade = "shade2";
                else $shade = "shade1";
            }

            echo "</table>";
        }
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>
