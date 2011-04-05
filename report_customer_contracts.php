<?php
// supportbycontract.php - Shows sites and their contracts
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:   Ivan Lucas
// Email:    ivanlucas[at]users.sourceforge.net
// Comments: List supported contacts by contract


$permission = 19; /* View Maintenance Contracts */
$title = $strSupportedContactsbySite;

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$siteid = cleanvar($_REQUEST['siteid']);

if ($_REQUEST['mode'] == 'csv')
{
    // --- CSV File HTTP Header
    header("Content-type: text/csv\r\n");
    header("Content-disposition-type: attachment\r\n");
    header("Content-disposition: filename=supported_contacts_by_contract.csv");
}
else
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
}

$sql = "SELECT *, s.name AS sitename FROM `{$dbSites}` AS s ";
if (!empty($_REQUEST['siteid'])) $sql .= "WHERE id='{$siteid}'";
else $sql .= "ORDER BY s.name";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
if ($_REQUEST['mode'] == 'csv')
{
    echo "{$strSite},{$strProduct},{$strLicense},{$strExpiryDate},{$strAllContacts},{$strEngineer} 1, {$strEngineer} 2, {$strEngineer} 3, {$strEngineer} 4\n";
}
while ($site = mysql_fetch_object($result))
{
    $msql  = "SELECT m.id AS maintid, m.term AS term, p.name AS product, r.name AS reseller, ";
    $msql .= "licence_quantity, l.name AS licence_type, expirydate, admincontact, c.forenames AS admincontactsforenames, ";
    $msql .= "c.surname AS admincontactssurname, m.notes AS maintnotes, m.allcontactssupported ";
    $msql .= "FROM `{$dbMaintenance}` AS m, `{$dbContacts}` AS c, `{$dbProducts}` AS p, `{$dbLicenceTypes}` AS l, `{$dbResellers}` AS r ";
    $msql .= "WHERE m.product=p.id ";
    $msql .= "AND m.reseller=r.id AND if(licence_type = 0, 4, ifnull(licence_type, 4)) = l.id AND admincontact = c.id ";
    $msql .= "AND m.site = '{$site->id}' ";
    $msql .= "AND m.term!='yes' ";
    $msql .= "AND m.expirydate > '$now' ";     $msql .= "ORDER BY expirydate DESC";

    $mresult = mysql_query($msql);
    if (mysql_num_rows($mresult)>=1)
    {
        if ($_REQUEST['mode'] == 'csv')
        {
            while ($maint = mysql_fetch_object($mresult))
            {
                if ($maint->expirydate > $now AND $maint->term != 'yes')
                {
                    echo "{$site->sitename},";
                    echo "{$maint->product},";
                    echo "{$maint->licence_quantity} {$maint->licence_type},";
                    echo ldate($CONFIG['dateformat_date'], $maint->expirydate).",";
                    
                    if ($maint->allcontactssupported == 'yes') echo "{$strYes},";
                    else echo "{$strNo},"; 
                    
                    $csql  = "SELECT * FROM `{$dbSupportContacts}` ";
                    $csql .= "WHERE maintenanceid='{$maint->maintid}' ";
                    $csql .= "ORDER BY contactid LIMIT 4";
                    $cresult = mysql_query($csql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                    while ($contact = mysql_fetch_object($cresult))
                    {
                        echo contact_realname($contact->contactid).",";
                    }
                    echo "\n";
                    $a++;
                }
            }
        }
        else
        {
            echo "<h2>{$site->sitename}</h2>";
            echo "<table width='100%'>";
            echo "<tr><th style='text-align: left;'>{$strProduct}</th>";
            echo "<th style='text-align: left;'>{$strLicense}</th>";
            echo "<th style='text-align: left;'>{$strExpiryDate}</th>";
            echo "<th style='text-align: left;'>{$strAllContacts}</th>";
            echo "<th style='text-align: left;'>{$strEngineer} 1</th>";
            echo "<th style='text-align: left;'>{$strEngineer} 2</th>";
            echo "<th style='text-align: left;'>{$strEngineer} 3</th>";
            echo "<th style='text-align: left;'>{$strEngineer} 4</th></tr>\n";
            while ($maint = mysql_fetch_object($mresult))
            {
                if ($maint->expirydate > $now AND $maint->term != 'yes')
                {
                    echo "<tr>";
                    echo "<td width='20%'>{$maint->product}</td>";
                    echo "<td>{$maint->licence_quantity} {$maint->licence_type}</td>";
                    echo "<td>".ldate($CONFIG['dateformat_date'], $maint->expirydate)."</td>";

                    echo "<td>";
                    if ($maint->allcontactssupported == 'yes') echo $strYes;
                    else echo $strNo;
                    echo "</td>";
                    
                    $csql  = "SELECT * FROM `{$dbSupportContacts}` ";
                    $csql .= "WHERE maintenanceid='{$maint->maintid}' ";
                    $csql .= "ORDER BY contactid LIMIT 4";
                    $cresult = mysql_query($csql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                    while ($contact = mysql_fetch_object($cresult))
                    {
                        echo "<td>".contact_realname($contact->contactid)."</td>";
                    }
                    echo "</tr>\n";
                    $a++;
                }
            }
            echo "</table>";
            echo "<hr />";
        }
    }
}

if ($_REQUEST['mode'] != 'csv')
{
    echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?siteid={$siteid}&amp;mode=csv'>{$strSaveAsCSV}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>