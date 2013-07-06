<?php
// site_incidents.php - csv file showing how many incidents each site logged
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Shows Incidents CLOSED between the dates

require ('core.php');
$permission = PERM_REPORT_RUN; // Run Reports
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strSiteIncidents;

$startdate = cleanvar($_REQUEST['start']);
$enddate = cleanvar($_REQUEST['end']);
$mode = clean_fixed_list($_REQUEST['mode'], array('', 'run'));
$zerologged = clean_fixed_list($_REQUEST['zerologged'], array('','on'));
$showsitesloggedfewerthanxcalls = clean_fixed_list($_REQUEST['showsitesloggedfewerthanxcalls'], array('','on'));
$numberofcalls = clean_int($_REQUEST['numberofcalls']);
$showincidentdetails = clean_fixed_list($_REQUEST['showincidentdetails'], array('','on'));
$onlyshowactivesites = clean_fixed_list($_REQUEST['onlyshowactivesites'], array('','on'));
$slas = clean_int($_REQUEST['slas']);
$showproducts = clean_fixed_list($_REQUEST['showproducts'], array('','on'));

if (empty($mode))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    ?>
    <script type='text/javascript'>
    function toggleLessThanX()
    {
        if ($('showsitesloggedfewerthanxcalls').checked == true)
        {
            $('numberofcalls').show();
            $('labelforxcalls').show();
            $('zerologged').checked = true;
            $('zerologged').readOnly = true;
        }
        else
        {
            $('numberofcalls').hide();
            $('labelforxcalls').hide();
            $('zerologged').checked = false;
            $('zerologged').readOnly = false;
        }
    }

    function toggleDisableIncidents()
    {
    	if ($('output').value == 'screen')
        {
            $('showincidentdetails').readOnly = false;
        }
        else
        {
            $('showincidentdetails').checked = false;
        	$('showincidentdetails').readOnly = true;
        }
    }
	</script>
	<?php

    echo "<h2>".icon('reports', 32)." {$title}</h2>";

    echo "<form name='date' action='".$_SERVER['PHP_SELF']."?mode=run' method='post'>\n";
    echo "<table class='vertical'>\n";
    echo "<tr><th>{$strStartDate}:</th><td title='date picker'>\n";
    echo "<input name='start' size='10' value='{$date}' />\n";
    echo date_picker('date.start');
    echo "</td></tr>\n";
    echo "<tr><th>{$strEndDate}:</th><td align='left' class='shade1' title='date picker'>\n";
    echo "<input name='end' size='10' />\n";
    echo date_picker('date.end');
    echo "</td></tr>\n";
    echo "<tr><th>{$strExcludeSitesWith}".help_link('CTRLAddRemove')."</th><td>\n";

    $sql = "SELECT DISTINCT tag FROM `{$dbServiceLevels}`";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        echo "<label>{$strServiceLevel}:<br />";
        echo "<select name='slas[]' multiple='multiple' size='5'>\n";
        while ($obj = mysql_fetch_object($result))
        {
            echo "<option value='{$obj->tag}'>{$obj->tag}</option>\n";
        }
        echo "</select></label>\n";
    }
    echo "</td></tr>\n";
    echo "<tr><th>$strOptions</th>";
    echo "<td>";
    echo "<label><input type='checkbox' name='zerologged' id='zerologged' /> {$strShowSitesThatHaveLoggedNoIncidents}</label><br />";
    echo "<input type='checkbox' name='showsitesloggedfewerthanxcalls' id='showsitesloggedfewerthanxcalls' onclick=\"toggleLessThanX();\" /> {$strShowSitesWhichHaveLoggedLessThanCalls}\n";
    echo "<input type='text' name='numberofcalls' size='3' id='numberofcalls' style='display:none'/><label id='labelforxcalls' for='showsitesloggedfewerthanxcalls' style='display:none'> {$strIncidents}</label>";
    echo "<br />";
    echo "<label><input type='checkbox' name='showincidentdetails' id='showincidentdetails' /> {$strShowIncidentDetails}</label><br />";
    echo "<label><input type='checkbox' name='onlyshowactivesites' id='onlyshowactivesites' /> {$strOnlyShowSitesWithActiveContracts}</label><br />\n";
    echo "<label><input type='checkbox' name='showproducts' id='showproducts' /> {$strShowProducts}</label>";
    echo "</td></tr>\n";
    echo "<tr><th>{$strOutput}</th>\n";
    echo "<td><select name='output' id='output'><option value='screen' onclick='toggleDisableIncidents();'>{$strScreen}</option>\n";
    echo "<option value='csv' onclick='toggleDisableIncidents();'>{$strCSVfile}</option></select></td></tr>\n";
    echo "</table>\n";
    echo "<p class='formbuttons'>";
    echo "<input type='hidden' name='user' value='{$user}' />";
    echo "<input type='hidden' name='step' value='1' />";
    echo "<input type='reset' value=\"{$strReset}\" /> ";
    echo "<input type='submit' value=\"{$strRunReport}\" /></p>";
    echo "</form>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    $output = clean_fixed_list($_REQUEST['output'], array('screen','csv'));

    if (empty($startdate)) $startdate = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y")-1)); // 1 year ago
    if (empty($enddate)) $enddate = date('Y-m-d');

    $enddate = $enddate." 23:59:59";

    $sql = "SELECT DISTINCT s.id, s.name AS name, r.name AS resel, m.reseller, u.realname ";
    $sql .= "FROM `{$dbMaintenance}` AS m, `{$dbResellers}` AS r, `{$dbSites}` AS s ";
    $sql .= "LEFT JOIN `{$dbUsers}` AS u ON s.owner = u.id ";
    $sql .= "WHERE s.id = m.site AND r.id = m.reseller AND m.term <> 'yes' ";
    if ($onlyshowactivesites == 'on')
    {
        $sql .= "AND m.expirydate > '{$now}' ";
    }
    $sql .= "ORDER BY s.name";
    /*
        SELECT DISTINCT s.id, s.name AS name, r.name AS resel, m.reseller, u.realname
        FROM `sites` AS s, `maintenance` AS m, `resellers` AS r, `users` AS u
        WHERE s.id = m.site AND r.id = m.reseller AND m.term <> 'yes' AND s.owner = u.id AND m.expirydate > '1231609928' ORDER BY s.name
    */
    // echo $sql;
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        $shade = 'shade1';
        while ($site = mysql_fetch_object($result))
        {
            if ($showproducts == 'on')
            {
                $product = "";
                $psql  = "SELECT m.id AS maintid, m.term AS term, p.name AS product, ";
                $psql .= "m.admincontact AS admincontact, ";
                $psql .= "r.name AS reseller, licence_quantity, lt.name AS licence_type, expirydate, admincontact, c.forenames AS admincontactsforenames, c.surname AS admincontactssurname, m.notes AS maintnotes ";
                $psql .= "FROM `{$dbMaintenance}` AS m, `{$dbContacts}` AS c, `{$dbProducts}` AS p, `{$dbLicenceTypes}` AS lt, `{$dbResellers}` AS r ";
                $psql .= "WHERE m.product = p.id AND m.reseller = r.id AND licence_type = lt.id AND admincontact = c.id ";
                $psql .= "AND m.site = '{$site->id}' AND m.expirydate > '{$now}' AND term != 'yes' AND m.reseller = {$site->reseller} ";
                $psql .= "ORDER BY p.name ASC";
                $presult = mysql_query($psql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                while ($prod = mysql_fetch_object($presult))
                {
                    $product .= "{$prod->product}<br />";
                }
            }

            $rowshowed = false;

            if ((!empty($slas) AND !does_site_have_certain_sla_contract($site->id, $slas)) OR empty($slas))
            {
                $sql = "SELECT count(i.id) AS incidentz, s.name AS site FROM `{$dbContacts}` AS c, `{$dbSites}` AS s, `{$dbIncidents}` AS i, `{$dbMaintenance}` AS m ";
                $sql.= "WHERE c.siteid = s.id AND s.id={$site->id} AND i.opened > ".strtotime($startdate)." AND i.closed < ".strtotime($enddate)." AND i.contact = c.id ";
                $sql .= "AND m.id = i.maintenanceid AND m.reseller = '{$site->reseller}' ";
                $sql.= "GROUP BY site";
                // echo $sql;
                $sresult = mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                $details = mysql_fetch_object($sresult);
                $count = $details->incidentz;

                $colspan = 4;

                if (!empty($zerologged))
                {
                    if (empty($count)) $count = 0;
                    if ($showsitesloggedfewerthanxcalls == 'on' AND $count <= $numberofcalls)
                    {
                        if ($output == 'csv')
                        {
                            $csv .= "\"{$count}\",\"{$site->name}\",\"{$site->realname}\",\"{$site->resel}";
                            $rowshowed = true;
                        }
                        else
                        {
                            $csv .= "<tr class='{$shade}'><td>{$count}</td><td>{$site->name}</td><td>{$site->realname}</td><td>{$site->resel}</td>";
                            $rowshowed = true;
                        }
                        if ($showproducts == 'on')
                        {
                            $colspan = 5;
                            if ($output == 'csv') $csv .= "\",\"{$product}";
                            else $csv .= "<td>{$product}</td>";
                        }
                    }
                    else if (empty($showsitesloggedfewerthanxcalls))
                    {
                        if ($output == 'csv')
                        {
                            $csv .= "\"{$count}\",\"{$site->name}\",\"{$site->realname}\",\"{$site->resel}";
                        }
                        else
                        {
                            $csv .= "<tr class='{$shade}'><td>{$count}</td><td>{$site->name}</td><td>{$site->realname}</td><td>{$site->resel}</td>";
                            $rowshowed = true;
                        }

                        if ($showproducts == 'on')
                        {
                            $colspan = 5;
                            if ($output == 'csv') $csv .= "\",\"{$product}";
                            else $csv .= "<td>{$product}</td>";
                        }
                    }
                }
                else
                {
                    // Dont need to check $showsitesloggedfewerthanxcalls as $zerologged will always be selected
                    if ($count != 0)
                    {
                        if ($output == 'csv')
                        {
                            $csv .= "\"{$count}\",\"{$site->name}\",\"{$site->realname}\",\"{$site->resel}";
                            $rowshowed = true;
                        }
                        else
                        {
                            $csv .= "<tr class='{$shade}'><td>{$count}</td><td>{$site->name}</td><td>{$site->realname}</td><td>{$site->resel}</td>";
                            $rowshowed = true;
                        }

                        if ($showproducts == 'on')
                        {
                            $colspan = 5;
                            if ($output == 'csv') $csv .= "\",\"{$product}";
                            else $csv .= "<td>{$product}</td>";
                        }
                    }
                }
            }

            if ($output == 'csv') $csv .= "\"\n";
            else $csv .= "</tr>";

            if ($showincidentdetails == 'on' AND $output == 'screen' AND $count > 0 AND $rowshowed)
            {
                $isql = "SELECT i.id, i.title, i.softwareid, i.status, i.owner, i.opened, i.closed, i.servicelevel, c.forenames, c.surname ";
                $isql .= "FROM `{$dbContacts}` AS c, `{$dbSites}` AS s, `{$dbIncidents}` AS i ";
                $isql.= "WHERE c.siteid = s.id AND s.id={$site->id} AND i.opened >".strtotime($startdate)." AND i.closed < ".strtotime($enddate)." AND i.contact = c.id ";
                $iresult = mysql_query($isql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

                if (mysql_num_rows($iresult) > 0)
                {
                    $csv .= "<tr><td colspan='{$colspan}'>";
                    $csv .= "<table width='100%'><th>{$strID}</th><th>{$strTitle}</th><th>{$strContact}</th><th>{$strSkill}</th><th>{$strStatus}</th>";
                    $csv .= "<th>{$strEngineer}</th><th>{$strOpened}</th><th>{$strClosed}</th><th>{$strDuration}</th><th>{$strSLA}</th></tr>";

                    $shade1 = 'shade1';

                    while ($obj = mysql_fetch_object($iresult))
                    {
                        $csv .= "<tr class='{$shade1}'>";
                        $csv .= "<td>".html_incident_popup_link($obj->id, $obj->id)."</td><td>{$obj->title}</td>";
                        $csv .= "<td>{$obj->forenames} {$obj->surname}</td>";
                        $csv .= "<td>".software_name($obj->softwareid)."</td>";
                        $csv .= "<td>".incidentstatus_name($obj->status)."</td>";
                        $csv .= "<td>".user_realname($obj->owner)."</td>";
                        $csv .= "<td>".format_date_friendly($obj->opened)."</td>";
                        $csv .= "<td>";
                        if ($obj->closed > 0) $csv .= format_date_friendly($obj->closed);
                        else $csv .= $strCurrentlyOpen;
                        $csv .= "</td>";
                        $csv .= "<td>";
                        if ($obj->closed > 0) $csv .= format_workday_minutes(($obj->closed - $obj->opened) / 60);
                        $csv .= "</td>";
                        $csv .= "<td>{$obj->servicelevel}</td>";
                        $csv .= "</tr>";

                        if ($shade1 == 'shade1') $shade1 = 'shade2';
                        else $shade1 = 'shade1';
                    }

                    $csv .= "</table>";
                    $csv .= "</td></tr>";
                }
            }

            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }

        if ($output == 'csv')
        {
            $header = "\"{$strIncidents}\",\"{$strSite}\",\"{$strAccountManager}\",\"{$strReseller}";
            if ($showproducts == 'on')
            {
                $header .= "\",\"{$strProducts}";
            }
            $csv = $header."\"\n".$csv;
        }
        else
        {
            $header = "<tr><th>{$strIncidents}</th><th>{$strSite}</th><th>{$strAccountManager}</th><th>{$strReseller}</th>";
            if ($showproducts == 'on')
            {
                $header .= "<th>{$strProducts}</th>";
            }
            $csv = $header."</tr>".$csv;
        }

        if ($output == 'csv')
        {
            $csv = "\"{$strStartDate}:\",\"{$startdate}\"\n{$strEndDate}:\",\"{$enddate}".$csv;
            header("Content-type: text/csv\r\n");
            header("Content-disposition-type: attachment\r\n");
            header("Content-disposition: filename=yearly_incidents.csv");
            echo $csv;
        }
        else
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            echo "<h2>".icon('site', 32)." {$strSiteIncidents}</h2>";
            echo "<p align='center'>{$strStartDate}: {$startdate}. {$strEndDate}: {$enddate}</p>";

            echo "<table class='maintable'>{$csv}</table>";

            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }


    }
    else html_redirect($_SERVER['PHP_SELF'], FALSE, $strNoResults);

}

?>