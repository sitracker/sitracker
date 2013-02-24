<?php
// portal/contracts.inc.php - Shows contact details
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Kieran Hogg <kieran[at]sitracker.org>

require ('..' . DIRECTORY_SEPARATOR . 'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

$accesslevel = 'admin';

include (APPLICATION_LIBPATH . 'portalauth.inc.php');

$id = clean_int($_GET['id']);
$contactid = clean_int($_GET['contactid']);
$action = cleanvar($_GET['action']);
if ($id != 0 AND $contactid != 0 AND $action == 'remove')
{
    if (in_array($id,
                 admin_contact_contracts($_SESSION['contactid'], $_SESSION['siteid'])))
    {
        $sql = "DELETE FROM `{$dbSupportContacts}`
                WHERE maintenanceid='{$id}'
                AND contactid='{$contactid}'
                LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        else
        {
            html_redirect($_SERVER['PHP_SELF']."?id={$id}");
            exit;
        }
    }
    else
    {
        html_redirect($_SERVER['PHP_SELF']."?id={$id}", FALSE, $strPermissionDenied);
    }
}
elseif ($id != 0 AND $action == 'add' AND intval($_POST['contactid'] != 0))
{
    $contactid = clean_int($_POST['contactid']);
    $sql = "INSERT INTO `{$dbSupportContacts}`
            (maintenanceid, contactid)
            VALUES('{$id}', '{$contactid}')";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    else
    {
        html_redirect($_SERVER['PHP_SELF']."?id={$id}");
        exit;
    }
}

include (APPLICATION_INCPATH . 'portalheader.inc.php');

echo "<h2>".icon('contract', 32, $strContract)." {$strContract}</h2>";

$sql  = "SELECT m.*, m.notes AS maintnotes, s.name AS sitename, ";
$sql .= "r.name AS resellername, lt.name AS licensetypename ";
$sql .= "FROM `{$dbMaintenance}` AS m, `{$dbSites}` AS s, ";
$sql .= "`{$dbResellers}` AS r, `{$dbLicenceTypes}` AS lt ";
$sql .= "WHERE s.id = m.site ";
$sql .= "AND m.id='{$id}' ";
$sql .= "AND m.reseller = r.id ";
$sql .= "AND (m.licence_type IS NULL OR m.licence_type = lt.id) ";
$sql .= "AND m.site = '{$_SESSION['siteid']}'";

$maintresult = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

$maint = mysql_fetch_object($maintresult);

$html = "<table class='maintable vertical'>";
echo "<tr><th>{$strContract} {$strID}:</th>";
echo "<td><h3>".icon('contract', 32)." ";
echo "{$maint->id}</h3></td></tr>";
echo "<tr><th>{$strStatus}:</th><td>";
if ($maint->term == 'yes')
{
    echo "<strong>{$strTerminated}</strong>";
}
else
{
    echo $strActive;
}

if ($maint->expirydate < $now AND $maint->expirydate != '-1')
{
    echo ", <span class='expired'>{$strExpired}</span>";
}
echo "</td></tr>\n";
echo "<tr><th>{$strSite}:</th>";

echo "<td><a href=\"sitedetails.php\">".$maint->sitename."</a></td></tr>";

echo "<tr><th>{$strAdminContact}:</th>";

echo "<td><a href='contactdetails.php?id={$maint->admincontact}'>";
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
echo "<tr><th>{$strProduct}:</th><td>".product_name($maint->product)."</td></tr>";
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

echo "</table>";

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
                echo "<a href=\"contactdetails.php?";
                echo "id={$contact}\">".contact_realname($contact)."</a>, ";
                echo contact_site($contact). "</td>";
                echo "<td><a href=\"{$_SERVER['PHP_SELF']}?id={$id}&amp;contactid={$contact}&amp;action=remove\">{$strRemove}</a></td></tr>\n";
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
                        "<strong>".$numberofcontacts."</strong>",
                        "<strong>".$allowedcontacts."</strong>");
        echo "</p>";

        echo "<h3>{$strNewNamedContact}</h3>";
        echo "<form action='{$_SERVER['PHP_SELF']}?id={$id}&amp;action=";
        echo "add' method='post' >";
        echo "<p align='center'>{$GLOBLAS['strNewSupportedContact']} ";
        echo contact_site_drop_down('contactid',
                                        'contactid',
                                        maintenance_siteid($id),
                                        supported_contacts($id));
        echo help_link('NewSupportedContact');
        echo " <input type='submit' value='{$strNew}' /></p></form>";

        echo "<p align='center'><a href='newcontact.php'>";
        echo "{$strNewSiteContact}</a></p>";
    }

    echo "<br />";
    echo "<h3>{$strSkillsSupportedUnderContract}:</h3>";
    // supported software
    $sql = "SELECT * FROM `{$GLOBALS[dbSoftwareProducts]}` AS sp, `{$GLOBALS[dbSoftware]}` AS s ";
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
        echo "<p align='center'>{$strNone} / {$strUnknown}<p>";
    }
}
else
{
    $html = user_alert($strNothingToDisplay, E_USER_NOTICE);
}


include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>