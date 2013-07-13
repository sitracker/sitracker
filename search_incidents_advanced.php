<?php
// advanced_search_incidents.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// Removed mention of contactproducts - INL 08Oct01
// This Page Is Valid XHTML 1.0 Transitional!   - INL 6Apr06

require ('core.php');
$permission = PERM_INCIDENT_LIST;  // view incidents
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// Don't return more than this number of results
$maxresults = 1000;

// External variables
$search_title = cleanvar($_REQUEST['search_title']);
$search_id = clean_int($_REQUEST['search_id']);
$search_externalid = clean_int($_REQUEST['search_externalid']);
$search_contact = cleanvar($_REQUEST['search_contact']);
$search_servicelevel = cleanvar($_REQUEST['search_servicelevel']);
$search_details = cleanvar($_REQUEST['search_details']);
$search_range = cleanvar($_REQUEST['search_range']);
$search_date = cleanvar($_REQUEST['search_date']);
$search_user = clean_int($_REQUEST['search_user']);
$action = clean_fixed_list($_REQUEST['action'], array('','search'));


include (APPLICATION_INCPATH . 'htmlheader.inc.php');
// show search incidents form
if (empty($action))
{
    echo "<h2>".icon('search', 32)." ";
    echo "{$strSearch} ({$strAdvanced})</h2>";
    echo "<form action=\"{$_SERVER['PHP_SELF']}\" method='get'>";
    echo "<table class='vertical'>";
    echo "<tr><th colspan='2'>{$strIncidents}</th><tr>\n";
    echo "<tr><th>{$strTitle}:</th><td><input maxlength='100' name='search_title' size='30' type='text' /></td></tr>\n";
    echo "<tr><th>{$strIncident} ID:</th><td><input maxlength='100' name='search_id' size='30' type='text' /></td></tr>\n";
    echo "<tr><th>{$strExternalID}:</th><td><input maxlength='100' name='search_externalid' size='30' type='text' /></td></tr>\n";
    echo "<tr><th>{$strServiceLevel}:</th><td>".serviceleveltag_drop_down('search_servicelevel', 0, TRUE)."</td></tr>\n";
    echo "<tr><th>{$strContact}:</th><td><input maxlength='100' name='search_contact' size='30' type='text' /></td></tr>\n";
    echo "<tr><th>{$strPriority}:</th><td>".priority_drop_down('search_priority', 0)."</td></tr>\n";
    echo "<tr><th>{$strProduct}:</th><td>".product_drop_down('search_product', 0)."</td></tr>\n";
    echo "<tr><th>{$strDetails}:</th><td><input maxlength='100' name='search_details' size='30' type='text' /></td></tr>\n";
    echo "<tr><th>{$strStatus}<br />{$strOpen}/{$strClosed}:</th><td>";
    echo "<select size='1' name='search_range'>";
    echo "<option selected='selected' value='All'>{$strAll}</option>";
    echo "<option value='Open'>{$strAllOpen}</option>";
    echo "<option value='Closed'>{$strAllClosed}</option>";
    echo "</select>\n";
    echo "</td></tr>\n";
    echo "<tr><th>{$strLastUpdated}:</th><td width='300'>";
    echo "<select size='1' name='search_date'>";
    echo "<option selected='selected' value='All'>{$strAll}</option>";
    echo "<option value='Recent180'>".sprintf($strPreviousXMonths, 6)."</option>";
    echo "<option value='Recent90'>".sprintf($strPreviousXMonths, 3)."</option>";
    echo "<option value='Recent30'>".sprintf($strPreviousXMonths, 30)."</option>";
    echo "<option value='Recent14'>".sprintf($strPreviousXMonths, 14)."</option>";
    echo "<option value='Recent7'>".sprintf($strPreviousXDays, 7)."</option>";
    echo "<option value='Recent1'>{$strToday}</option>";
    echo "<option value='RecentHour'>&lt; ".sprintf($strXMinutes, 60)."</option>";
    echo "<option value='OldHour'>&gt; ".sprintf($strXMinutes, 60)."</option>";
    echo "<option value='Old7'>&gt; ".sprintf($strXDays, 7)."</option>";
    echo "<option value='Old30'>&gt; ".sprintf($strXDays, 30)."</option>";
    echo "<option value='Old90'>&gt; ".sprintf($strXMonths, 3)."</option>";
    echo "<option value='Old180'>&gt; ".sprintf($strXMonths, 6)."</option>";
    echo "</select>";
    echo "</td></tr>\n";
    echo "<tr><th>{$strOwner}:</th><td width='300'>";
    user_drop_down('search_user',0);
    echo "</td></tr>";
    echo "<tr><th>{$strSortResults}:</th><td width='300'>";
    echo "<select size='1' name='sort_results'>
    <option selected='selected' value='DateDESC'>{$strByDate} ({$strNewestAtTop})</option>
    <option value='DateASC'>{$strByDate} ({$strNewestAtBottom})</option>
    <option value='IDASC'>{$strID}</option>
    <option value='TitleASC'>{$strTitle}</option>
    <option value='ContactASC'>{$strContact}</option>
    <option value='SiteASC'>{$strSite}</option>
    </select>";
    echo "</td></tr>\n";
    echo "<tr><td></td><td><input type='hidden' name='action' value='search' />";
    echo "<input name='reset' type='reset' value=\"{$strReset}\" />&nbsp;";
    echo "<input name='submit' type='submit' value=\"{$strSearch}\" />";
    echo "</td></tr>\n";
    echo "</table>\n";
    echo "</form>\n";
}
else
{
    // perform search

    // search for criteria
    if ($errors == 0)
    {
        // build SQL
        $recent_sixmonth = time() - (180 * 86400);
        $recent_threemonth = time() - (90 * 86400);
        $recent_month = time() - (30 * 86400);
        $recent_fortnight = time() - (14 * 86400);
        $recent_week = time() - (7 * 86400);
        $recent_today = time() - (1 * 86400);
        $recent_hour = time() - (3600);

        if ($search_details =='')
        {
            $sql = "SELECT DISTINCT i.id, externalid, title, priority, siteid, owner, type, forenames, surname, lastupdated, status, opened, servicelevel ";
            $sql .= "FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c WHERE i.contact = c.id  ";
        }
        if ($search_details !='')
        {
            $sql = "SELECT DISTINCT i.id, u.incidentid, i.externalid, i.title, i.priority, i.owner, i.type, i.lastupdated, i.status, c.forenames, c.surname, c.siteid, i.opened ";
            $sql .= "FROM `{$dbUpdates}` AS u, `{$dbIncidents}` AS i, `{$dbContacts}` AS c ";
            $sql .= "WHERE u.incidentid = i.id AND i.contact = c.id AND bodytext LIKE ('%$search_details%') ";
        }

        if ($search_title != '') $sql.= "AND title LIKE ('%{$search_title}%') ";
        if ($search_id != '') $sql.= "AND i.id LIKE ('%{$search_id}%') ";
        if ($search_externalid !='') $sql.= "AND externalid LIKE ('%{$search_externalid}%') ";
        if ($search_contact != '') $sql.= "AND (c.surname LIKE '%{$search_contact}%' OR forenames LIKE '%{$search_contact}%') ";
        if ($search_servicelevel != '') $sql.= "AND (i.servicelevel = '{$search_servicelevel}') ";
        if ($search_range == 'Closed') $sql.= "AND closed != '0' ";
        if ($search_range == 'Open') $sql.= "AND closed = '0' ";
        if ($search_date == 'Recent180}') $sql.= "AND lastupdated >= '{$recent_sixmonth}' ";
        if ($search_date == 'Recent90}') $sql.= "AND lastupdated >= '{$recent_threemonth}' ";
        if ($search_date == 'Recent30}') $sql.= "AND lastupdated >= '{$recent_month}' ";
        if ($search_date == 'Recent14}') $sql.= "AND lastupdated >= '{$recent_fortnight}' ";
        if ($search_date == 'Recent7}') $sql.= "AND lastupdated >= '{$recent_week}' ";
        if ($search_date == 'Recent1}') $sql.= "AND lastupdated >= '{$recent_today}' ";
        if ($search_date == 'RecentHour}') $sql.= "AND lastupdated >= '{$recent_hour}' ";
        if ($search_date == 'Old180}') $sql.= "AND lastupdated <= '{$recent_sixmonth}' ";
        if ($search_date == 'Old90}') $sql.= "AND lastupdated <= '{$recent_threemonth}' ";
        if ($search_date == 'Old30}') $sql.= "AND lastupdated <= '{$recent_month}' ";
        if ($search_date == 'Old7}') $sql.= "AND lastupdated <= '{$recent_week}' ";
        if ($search_date == 'OldHour}') $sql.= "AND lastupdated <= '{$recent_hour}' ";
        if ($search_user != 0) $sql.= "AND owner = '{$search_user}' ";
        if ($search_priority != 0) $sql.= "AND priority = '{$search_priority}' ";
        if ($search_product != 0) $sql.="AND product = '{$search_product}' ";

        // Sorting
        if ($sort_results == 'DateASC') $sql.="ORDER BY lastupdated ASC ";
        if ($sort_results == 'DateDESC') $sql.="ORDER BY lastupdated DESC ";
        if ($sort_results == 'IDASC') $sql.="ORDER BY i.id ASC ";
        if ($sort_results == 'TitleASC') $sql.="ORDER BY i.title ASC ";
        if ($sort_results == 'ContactASC') $sql.="ORDER BY c.surname ASC ";
        if ($sort_results == 'SiteASC') $sql.="ORDER BY c.siteid ASC ";

        $sql .= "LIMIT {$maxresults}";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        $countresults = mysql_num_rows($result);
        if ($countresults == 0)
        {
            echo "<h2>{$strNoResults}</h2>\n";
            echo "<p align='center'><a href=\"search_incidents_advanced.php\">{$strSearchAgain}</a></p>";
        }
        else
        {
            echo "<h2>".sprintf($strResultsNum, $countresults)."</h2>";
            echo "<table class='maintable'>
            <tr>
            <th>{$strID} (Ext ID)</th>
            <th>{$strTitle}</th>
            <th>{$strContact}</th>
            <th>{$strSite}</th>
            <th>{$strPriority}</th>
            <th>{$strOwner}</th>
            <th>{$strOpened}</th>
            <th>{$strLastUpdated}</th>
            <th>{$strType}</th>
            <th>{$strStatus}</th>
            </tr>";
            $shade = 'shade1';
            while ($results = mysql_fetch_object($result))
            {
                echo "<tr class='{$shade}'>";
                echo "<td align='center'  width='100'>{$results->id} (";
                if ($results->externalid == '') echo $strNone;
                else echo $results->externalid;
                echo "</td>";
                echo "<td width='150'>".html_incident_popup_link($results->id, $results->title)."</td>";
                echo "<td align='center' width='100'>{$results->forenames}' '{$results->surname}</td>";
                echo "<td align='center' width='100'>".site_name($results->siteid)."</td>";
                echo "<td align='center' width='50'>{$results->servicelevel}<br />".priority_name($results->priority)."</td>";
                echo "<td align='center' width='100'>".user_realname($results->owner, TRUE)."</td>";
                echo "<td align='center' width='150'>".ldate($CONFIG['dateformat_datetime'], $results->opened)."</td>";
                echo "<td align='center' width='150'>".ldate($CONFIG['dateformat_datetime'], $results->lastupdated)."></td>";
                echo "<td align='center' width='50'>{$results->type}</td>";
                echo "<td align='center' width='50'>".incidentstatus_name($results->status)."</td>";
                echo "</tr>";

                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }
        }
        echo "</table>";
        echo "<br />";
        echo "<p align='center'><a href=\"search_incidents_advanced.php\">{$strSearchAgain}</a></p>";
        // MANTIS 1849 Replace maxresults limit with paging
        if ($countresults >= $maxresults) printf($strMaxResults, $maxresults);
    }
}
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>