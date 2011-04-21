<?php
// browse_contracts.php - List of contracts
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

$permission = 19; // View Maintenance Contracts

require ('core.php');
require (APPLICATION_LIBPATH.'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$title = $strBrowseContracts;
$pagescripts = array('AutoComplete.js');

// External variables
$productid = clean_int($_REQUEST['productid']);
$resellerid = clean_int($_REQUEST['resellerid']);
$search_string = cleanvar($_REQUEST['search_string']);
$sort = cleanvar($_REQUEST['sort']);
$order = cleanvar($_REQUEST['order']);
$activeonly = cleanvar($_REQUEST['activeonly']);

include (APPLICATION_INCPATH . 'htmlheader.inc.php');
echo "<h2>".icon('contract', 32)." ";
echo "{$title}</h2>";
echo "<table summary='alphamenu' align='center'><tr><td align='center'>";
echo "<form action='{$_SERVER['PHP_SELF']}' method='get'>";
echo "{$strBrowseContractsBySite}:"; // <!--<input type="text" name="search_string" />-->
echo "<input type='text' id='search_string' style='width: 300px;' name='search_string' />";
echo autocomplete('search_string', 'contract');
if ($_SESSION['userconfig']['show_inactive_data'] == 'TRUE')
{
    echo "<label><input type='checkbox' name='activeonly' value='yes' ";
    if ($activeonly == 'yes') echo "checked='checked' ";
    echo "/> {$strShowActiveOnly}</label>";
}
echo "<br />{$strByProduct}: ";
echo product_drop_down('productid', $productid);

echo "{$strByReseller}: ";
echo reseller_drop_down('resellerid', $resellerid);
echo "<input type='submit' value=\"{$strGo}\" />";

echo "</form>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td valign='middle'>";

echo "<a href='contract_new.php'>{$strNewContract}</a> | ";
echo alpha_index("{$_SERVER['PHP_SELF']}?search_string=");
echo "<a href='{$_SERVER['PHP_SELF']}?search_string=*'>{$strAll}</a>";

echo "</td>";
echo "</tr>";
echo "</table>";

// check input
/*
if (empty($search_string) && empty($productid))
{
    $errors = 1;
    echo "<p class='error'>You must enter a search string</p>\n";
}
*/
// search for criteria
$sql  = "SELECT DISTINCT  m.id AS maintid, s.name AS site, p.name AS product, ";
$sql .= "r.name AS reseller, licence_quantity, ";
$sql .= "l.name AS licence_type, expirydate, admincontact, ";
$sql .= "c.forenames AS admincontactforenames, c.surname AS admincontactsurname, ";
$sql .= "m.notes, s.id AS siteid, m.term AS term, m.productonly AS productonly ";
$sql .= "FROM `{$dbMaintenance}` AS m, `{$dbSites}` AS s, `{$dbContacts}` AS c, ";
$sql .= "`{$dbProducts}` AS p, `{$dbLicenceTypes}` AS l, `{$dbResellers}` AS r ";
$sql .= "WHERE m.site = s.id AND m.admincontact = c.id AND m.product = p.id ";
$sql .= "AND ((reseller = r.id AND reseller IS NOT NULL) OR reseller IS NULL) ";
$sql .= "AND (licence_type IS NULL OR (licence_type = l.id AND licence_type IS NOT NULL)) ";

if ($activeonly == 'yes' OR $_SESSION['userconfig']['show_inactive_data'] != 'TRUE')
{
    $sql .= "AND term!='yes' AND (expirydate > $now OR expirydate = '-1') ";
}

if ($search_string != '*')
{
    if (mb_strlen(utf8_decode($search_string)) == 1)
    {
        // $sql .= "AND SUBSTRING(s.name,1,1)=('$search_string') ";
        $sql .= "AND s.name LIKE '{$search_string}%' ";
    }
    else
    {
        $sql .= "AND (s.name LIKE '%{$search_string}%' ";
        $sql .= "OR m.id = '{$search_string}') ";
    }

    if ($productid)
    {
        $sql .= "AND m.product='{$productid}' ";
    }

    if (!empty($resellerid))
    {
        $sql .= "AND m.reseller='{$resellerid}' ";
    }
}
$sql .= " GROUP BY m.id ";

if (!empty($sort))
{
    if ($sort == 'expiry') $sql .= "ORDER BY expirydate ";
    elseif ($sort == 'id') $sql .= "ORDER BY m.id ";
    elseif ($sort == 'product') $sql .= " ORDER BY p.name ";
    elseif ($sort == 'site') $sql .= " ORDER BY s.name ";
    elseif ($sort == 'reseller') $sql .= " ORDER BY r.name ";
    else $sql .= " ORDER BY s.name ";

    if ($order == 'a' OR $order == 'ASC' OR $order == '') $sql .= "ASC";
    else $sql .= "DESC";
}
$dbg .= "<p>$sql</p>";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

if (mysql_num_rows($result) == 0)
{
    echo "<p align='center'>";
    if (empty($search_string)) echo $strNoRecords;
    else printf($strSorryNoRecordsMatchingX, "<em>{$search_string}</em>");
    echo "</p>\n";
}
else
{
    echo "<p align='center'>".sprintf($strResultsNum, mysql_num_rows($result))."</p>\n";

    echo "<table align='center' style='width: 95%;'>";
    echo "<tr>";

    $filter = array('search_string' => $search_string,
                  'productid' => $productid,
                  'resellerid' => $resellerid);
    echo colheader('id', $strID, $sort, $order, $filter);
    echo colheader('product', $strProduct, $sort, $order, $filter);
    echo colheader('site', $strSite, $sort, $order, $filter);
    echo colheader('reseller', $strReseller, $sort, $order. $filter);
    echo "<th>{$strLicense}</th>";
    echo colheader('expiry', $strExpiryDate, $sort, $order, $filter);
    echo "<th width='200'>{$strNotes}</th>";
    echo "<th>{$strActions}</th>";
    echo "</tr>\n";
    $shade = 'shade1';
    while ($results = mysql_fetch_object($result))
    {
        // define class for table row shading
        if (($results->expirydate < $now AND $results->expirydate != '-1') || ( $results->term == 'yes' AND $results->productonly == 'no'))
        {
            $shade = 'expired';
        }
        elseif ($results->productonly == 'yes')
        {
            $shade = 'notice';
        }
        else
        {
            // invert shade
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }

        echo "<tr class='{$shade}'>";
        echo "<td><a href='contract_details.php?id={$results->maintid}'>{$strContract} {$results->maintid}</a></td>";
        echo "<td>{$results->product}</td>";
        echo "<td><a href='site_details.php?id={$results->siteid}#contracts'>".htmlspecialchars($results->site)."</a><br />";
        echo "{$strAdminContact}: <a href='contact_details.php?mode=popup&amp;id={$results->admincontact}' target='_blank'>{$results->admincontactforenames} {$results->admincontactsurname}</a></td>";

        echo "<td>";

        if (empty($results->reseller))
        {
            echo $strNoReseller;
        }
        else
        {
            echo $results->reseller;
        }

        echo "</td><td>";

        if (empty($results->licence_type))
        {
            echo $strNoLicense;
        }
        else
        {
            if ($results->licence_quantity == 0)
            {
                echo "{$strUnlimited} ";
            }
            else
            {
                echo "{$results->licence_quantity} ";
            }

            echo $results->licence_type;
        }

        echo "</td><td>";
        if ($results->expirydate == '-1')
        {
            echo $strUnlimited;
        }
        else
        {
            echo ldate($CONFIG['dateformat_date'], $results->expirydate);
        }
        echo "</td>";

        echo "<td>";
        if ($results->notes == '')
        {
            echo "&nbsp;";
        }
        else
        {
            echo nl2br($results->notes);
        }

        echo "</td>";
        echo "<td><a href='contract_edit.php?action=edit&amp;maintid={$results->maintid}'>{$strEdit}</a></td>";
        echo"</tr>";
    }

    echo "</table>";
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>