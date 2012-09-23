<?php
// portal/admin.php - Perform admin tasks
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

if ($_POST['submit'])
{
    $errors = 0;
    foreach (array_keys($_POST['visibility']) as $id)
    {
        $id = clean_int($id);

        if ($id != 0)
        {
            switch ($_POST['visibility'][$id])
            {
                case 'all':
                    $visiblesql = "SET var_incident_visible_all = 'yes', ";
                    $visiblesql .= "var_incident_visible_contacts = 'no' ";
                    break;

                case 'named':
                    $visiblesql = "SET var_incident_visible_contacts = 'yes', ";
                    $visiblesql .= "var_incident_visible_all = 'no' ";
                    break;

                case 'no-one':
                default:
                    $visiblesql = "SET var_incident_visible_contacts = 'no', ";
                    $visiblesql .= "var_incident_visible_all = 'no' ";
                    break;
            }
        }

        $sql = "UPDATE `{$dbMaintenance}` ";
        $sql .= $visiblesql;
        $sql .= "WHERE id = {$id}";

        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error(mysql_error(),E_USER_ERROR);
            $errors++;
        }
    }

    if ($errors == 0)
    {
        html_redirect('admin.php', TRUE);
        exit;
    }
    else
    {
        html_redirect('admin.php', FALSE);
        exit;
    }
}
include (APPLICATION_INCPATH . 'portalheader.inc.php');
echo "<h2>".icon('settings', 32, $strAdmin)." ";
echo $strAdmin."</h2>";

if ($CONFIG['portal_site_incidents'])
{
    $contracts = admin_contact_contracts($_SESSION['contactid'], $_SESSION['siteid']);

    echo "<p align='center'>{$strAdminContactForContracts}</p>";

    echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
    echo "<table align='center' width='60%'><tr>";
    //echo colheader('id', $strID);
    echo colheader('product', $strContract);
    echo colheader('expiry', $strExpiryDate);
    echo colheader('visbility', $strVisibility);
    echo colheader('actions', $strActions);
    echo "</tr>";
    $shade = 'shade1';
    foreach ($contracts as $contract)
    {
        $sql = "SELECT *, m.id AS id ";
        $sql .= "FROM `{$dbMaintenance}` AS m, `{$dbProducts}` AS p ";
        $sql .= "WHERE m.id={$contract} ";
        $sql .= "AND (m.expirydate > UNIX_TIMESTAMP(NOW()) OR m.expirydate = -1) ";
        $sql .= "AND m.product=p.id ";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        if ($row = mysql_fetch_object($result))
        {
            if ($row->expirydate == -1)
            {
                $row->expirydate = $strUnlimited;
            }
            else
            {
                $row->expirydate = ldate("jS F Y", $row->expirydate);
            }
            echo "<tr class='{$shade}'>";
            //echo "<td>{$row->id}</td>";
            echo "<td>{$row->name}</td><td>{$row->expirydate}</td>";
            echo "<td>";

            if ($row->allcontactssupported == 'yes')
            {
                echo "<select disabled='disabled'>";
                echo "<option>{$strAllSiteContactsSupported}</option>";
                echo "</select>";
                echo "</td>";
            }
            else
            {
                echo "<select name='visibility[$row->id]'>";
                echo "<option value='no-one'";
                if ($row->var_incident_visible_contacts == 'no' AND $row->var_incident_visible_all == 'no')
                {
                    echo " selected='selected'";
                }
                echo ">{$strNobody}</option>";
                echo "<option value='named'";
                    if ($row->var_incident_visible_contacts == 'yes')
                {
                    echo " selected='selected'";
                }
                echo ">{$strNamedContacts}</option>";
                echo "<option value='all'";
                    if ($row->var_incident_visible_all == 'yes')
                {
                    echo " selected='selected'";
                }
                echo ">{$strAllContacts}</option></select>";
                echo " ".help_link('SiteIncidentVisibility');
                echo "</td>";
            }
            echo "<td><a href='contracts.php?id={$row->id}'>{$strViewContract}</a></td></tr>";
        }

        if ($shade == 'shade1')
        {
            $shade = 'shade2';
        }
        else
        {
            $shade = 'shade1';
        }
    }
    echo "</table>";
    echo "<p class='formbuttons'><input type='submit' id='submit' name='submit'  value='{$strUpdate}' /></form></p>";

}
echo "<br />";
echo "<h2>".icon('contact', 32)." {$strContacts}: ".site_name($_SESSION['siteid'])."</h2>";
echo "<table width='30%' align='center'><tr>";
echo colheader('name', $strName);
echo colheader('action', $strAction, FALSE, FALSE, FALSE, FALSE, 10);
echo "</tr>";

$sql = "SELECT * FROM `{$dbContacts}` ";
$sql .= "WHERE siteid='{$_SESSION['siteid']}' ";
$sql .= "AND active = TRUE";

if ($result = mysql_query($sql))
{
    $shade = 'shade1';
    while ($row = mysql_fetch_object($result))
    {
        echo "<tr class='{$shade}'><td>{$row->forenames} {$row->surname}</td>";
        echo "<td><a href='contactdetails.php?id={$row->id}'>{$strView}/{$strEdit}</a> </td></tr>";

        if ($shade == 'shade1')
        {
            $shade = 'shade2';
        }
        else
        {
            $shade = 'shade1';
        }
    }
}
echo "</table>";
include '../inc/htmlfooter.inc.php';
?>