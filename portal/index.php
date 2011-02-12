<?php
// portal/index.php - Lists incidents in the portal
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

$accesslevel = 'any';

include (APPLICATION_LIBPATH . 'portalauth.inc.php');
include (APPLICATION_INCPATH . 'portalheader.inc.php');
$showclosed = cleanvar($_REQUEST['showclosed']);
$site = clean_int($_REQUEST['site']);

if ($CONFIG['debug']) $dbg .= "Sess: ".print_r($_SESSION,true);

function portal_incident_table($sql)
{
    global $CONFIG, $showclosed;
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $numincidents = mysql_num_rows($result);

    if ($numincidents >= 1)
    {
        $shade = 'shade1';
        $html .=  "<table align='center' width='70%'>";
        $html .=  "<tr>";
        $html .=  colheader('id', $GLOBALS['strID']);
        $html .=  colheader('title', $GLOBALS['strTitle']);
        $html .=  colheader('owner', $GLOBALS['strOwner']);
        $html .=  colheader('lastupdated', $GLOBALS['strLastUpdated']);
        $html .=  colheader('contact', $GLOBALS['strContact']);
        $html .=  colheader('status', $GLOBALS['strStatus']);
        if ($showclosed != "true")
        {
            $html .=  colheader('actions', $GLOBALS['strOperation']);
        }

        $html .=  "</tr>\n";
        while ($incident = mysql_fetch_object($result))
        {
            $html .=  "<tr class='$shade'><td align='center'>";
            $html .=  "<a href='incident.php?id={$incident->id}'>{$incident->id}</a></td>";
            $html .=  "<td>";
            if (!empty($incident->softwareid))
            {
                $html .=  software_name($incident->softwareid)."<br />";
            }

            $html .= "<strong><a href='incident.php?id={$incident->id}'>{$incident->title}</a></strong></td>";
            $html .= "<td align='center'>".user_realname($incident->owner)."</td>";
            $html .= "<td align='center'>".ldate($CONFIG['dateformat_datetime'], $incident->lastupdated)."</td>";
            $html .= "<td align='center'><a href='contactdetails.php?id={$incident->contactid}'>";
            $html .= "{$incident->forenames} {$incident->surname}</a></td>";
            $html .= "<td align='center'>".incidentstatus_name($incident->status, 'external')."</td>";
            if ($showclosed != "true")
            {
                $html .=  "<td align='center'><a href='update.php?id={$incident->id}'>{$GLOBALS['strUpdate']}</a> | ";

                //check if the customer has requested a closure
                $lastupdate = list($update_userid, $update_type, $update_currentowner, $update_currentstatus, $update_body, $update_timestamp, $update_nextaction, $update_id)=incident_lastupdate($incident->id);

                if ($lastupdate[1] == "customerclosurerequest")
                {
                    $html .=  "{$GLOBALS['strClosureRequested']}</td>";
                }
                else
                {
                    $html .=  "<a href='close.php?id={$incident->id}'>{$GLOBALS['strRequestClosure']}</a></td>";
                }
            }
            $html .= "</tr>";
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        $html .=  "</table>";
    }
    else
    {
        $html .= "<p class='info'>{$GLOBALS['strNoIncidents']}</p>";
    }

    return $html;
}


if (empty($showclosed))
{
    $showclosed = "false";
}

if ($showclosed == "true")
{
    echo "<h2>".icon('support', 32, $strYourClosedIncidents);
    echo "{$strYourClosedIncidents}</h2>";
    echo "<p align='center'>";
    echo icon('reopen', 16, $strShowOpenIncidents);
    echo " <a href='$_SERVER[PHP_SELF]?page=incidents&amp;showclosed=false'>";
    echo "{$strShowOpenIncidents}</a>";
    echo "</p>";
    $sql = "SELECT i.*, c.id AS contactid, c.forenames, c.surname FROM `{$dbIncidents}` AS i, ";
    $sql .= "`{$dbContacts}` AS c ";
    $sql .= "WHERE status = 2 AND c.id = i.contact ";
    $sql .= "AND contact = '{$_SESSION['contactid']}' ";
    $sql .= "ORDER BY closed DESC";
}
else
{
    echo "<h2>".icon('support', 32, $strYourCurrentOpenIncidents);
    echo " {$strYourCurrentOpenIncidents}</h2>";
    echo "<p align='center'>";
    echo icon('close', 16, $strShowClosedIncidents);
    echo " <a href='{$_SERVER['PHP_SELF']}?page=incidents&amp;showclosed=true'>{$strShowClosedIncidents}</a>";
    echo "</p>";
    $sql = "SELECT i.*, c.id AS contactid, c.forenames, c.surname FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c WHERE status != 2 ";
    $sql .= "AND c.id = i.contact ";
    $sql .= "AND i.contact = '{$_SESSION['contactid']}' ";
    $sql .= "ORDER by i.id DESC";
}


echo portal_incident_table($sql);
echo "<p align='center'>";

if (!$CONFIG['portal_creates_incidents'])
{
    echo "<a href='new.php'>";
}
elseif (sizeof($_SESSION['entitlement']) == 1)
{
    //only one contract
    $contractid = unserialize($_SESSION['entitlement'][0])->id;
    echo "<a href='new.php?contractid={$contractid}'>";
}
else
{
    echo "<a href='entitlement.php'>";
}

echo icon('new', 16, $strNewIncident)." {$strNewIncident}</a></p>";

//find list of other incidents we're allowed to see
$otherincidents = array();
$contracts = $_SESSION['contracts'];
if (!empty($contracts))
{
    $sql = "SELECT DISTINCT i.id
        FROM `{$dbIncidents}` AS i, `{$dbMaintenance}` AS m
        WHERE (1=0 ";

    foreach ($contracts AS $contract)
    {
        $sql .= "OR i.maintenanceid = {$contract} ";
    }
    $sql .= ")";
    $result = mysql_query($sql);
    while($incidents = mysql_fetch_object($result))
    {
        $otherincidents[] = $incidents->id;
    }
}


if ($CONFIG['portal_site_incidents'] AND $otherincidents != NULL)
{
    if ($showclosed == "true")
    {
        echo "<h2>{$strYourSitesClosedIncidents}</h2>";
        $sql = "SELECT DISTINCT i.id AS id, i.*, c.id AS contactid, ";
        $sql .= "c.forenames, c.surname ";
        $sql .= "FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c, `{$dbSites}` AS s ";
        $sql .= "WHERE status = 2 ";
        $sql .= "AND c.id=i.contact ";
        $sql .= "AND i.contact != {$_SESSION['contactid']} ";
        $sql .= "AND opened > ".($CONFIG['hide_closed_incidents_older_than'] * 86400)." ";
        $sql .= "AND c.siteid=s.id AND s.id={$_SESSION['siteid']} ";
        $sql .= "AND (1=0 ";

        foreach ($otherincidents AS $incident)
        {
            $sql .= "OR i.id={$incident} ";
        }

        $sql .= ") ORDER BY closed DESC ";
    }
    else
    {
        echo "<h2>".icon('site', 32, $strYourSitesIncidents);
        echo " {$strYourSitesIncidents}</h2>";
        $sql = "SELECT DISTINCT i.id AS id, i.*, c.id AS contactid, ";
        $sql .= "c.forenames, c.surname ";
        $sql .= "FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c, `{$dbSites}` AS s ";
        $sql .= "WHERE status != 2 ";
        $sql .= "AND c.id=i.contact ";
        $sql .= "AND i.contact != {$_SESSION['contactid']} ";
        $sql .= "AND c.siteid=s.id AND s.id={$_SESSION['siteid']} ";
        $sql .= "AND (1=0 ";

        foreach ($otherincidents AS $incident)
        {
            $sql .= "OR i.id={$incident} ";
        }

        $sql .= ") ORDER by i.id DESC";
    }

    echo portal_incident_table($sql);

}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>