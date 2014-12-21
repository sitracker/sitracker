<?php
// yearly_customer_export.php - List the numbers and titles of incidents logged by each site in the past year.
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!   15Mar06

require ('core.php');
$permission = PERM_REPORT_RUN; // Run Reports
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strIncidentsBySite;

$mode = clean_fixed_list($_REQUEST['mode'], array('', 'report'));

if (empty($mode))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('reports', 32)." {$title}</h2>";
    echo "<p align='center'>".sprintf($strReportListsIncidentsLoggedThatEachSiteLoggedOverPastXMonths, 12)."</p>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
    echo "<table summary='Site Selection Table' class='vertical'>";
    echo "<tr><th colspan='2' align='center'>{$strInclude}".help_link('CTRLAddRemove')."</th></tr>";
    echo "<tr><td align='center' colspan='2'>";
    $sql = "SELECT * FROM `{$dbSites}` ORDER BY name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    echo "<select name='inc[]' multiple='multiple' size='20'>";
    while ($site = mysql_fetch_object($result))
    {
        echo "<option value='{$site->id}'>{$site->name}</option>\n";
    }
    echo "</select>";
    echo "</td>";
    echo "</tr>\n";
    echo "<tr><th>{$strOptions}</th>";
    echo "<td>";
    echo "<label><input type='checkbox' name='showsitetotals' value='yes' /> {$strShowSiteTotals}</label><br />";
    echo "<label><input type='checkbox' name='showtotals' value='yes' /> {$strShowTotals}</label><br /><br />";
    echo "<tr><th align='right'>{$strOutput}:</th>";
    echo "<td>";
    echo "<select name='output'>";
    echo "<option value='screen'>{$strScreen}</option>";
    echo "<option value='csv'>{$strCSVfile}</option>";
    echo "</select>";
    echo "</td></tr>";
    echo "</table>";
    echo "<p class='formbuttons'>";
    echo "<input type='hidden' name='mode' value='report' />";
    echo "<input type='reset' value=\"{$strReset}\" /> ";
    echo "<input type='submit' value=\"{$strRunReport}\" />";
    echo "</p>";
    echo "</form>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($_REQUEST['mode'] == 'report')
{
    if (is_array($_POST['exc']) && is_array($_POST['exc'])) $_POST['inc'] = array_values(array_diff(clean_dbstring($_POST['inc']), clean_dbstring($_POST['exc'])));  // don't include anything excluded

    $includecount = count($_POST['inc']);
    if ($_POST['showsitetotals'] == 'yes') $showsitetotals = TRUE;
    else $showsitetotals = FALSE;

    if ($_POST['showtotals'] == 'yes') $showtotals = TRUE;
    else $showtotals = FALSE;

    if ($_POST['showgrandtotals'] == 'yes') $showgrandtotals = TRUE;
    else $showgrandtotals = FALSE;

    if ($includecount >= 1)
    {
        // $html .= "<strong>Include:</strong><br />";
        $incsql .= "(";
        for ($i = 0; $i < $includecount; $i++)
        {
            // $html .= "{$_POST['inc'][$i]} <br />";
            $incsql .= "siteid=".clean_int($_POST['inc'][$i]);
            if ($i < ($includecount-1)) $incsql .= " OR ";
        }
        $incsql .= ")";
    }
    $sql = "SELECT i.id AS incid, i.title AS title, c.id AS contactid, s.name AS site, c.email AS cemail, ";
    $sql .= "CONCAT(c.forenames,' ',c.surname) AS cname, i.opened as opened, st.typename, i.externalid AS externalid, ";
    $sql .= "s.id AS siteid ";
    $sql .= "FROM `{$dbContacts}` AS c, `{$dbSites}` AS s, `{$dbSiteTypes}` AS st, `{$dbIncidents}` AS i ";
    $sql .= "WHERE c.siteid = s.id AND s.typeid = st.typeid AND i.opened > ($now-60*60*24*365.25) ";
    $sql .= "AND i.contact=c.id";

    if (empty($incsql) == FALSE OR empty($excsql) == FALSE) $sql .= " AND ";
    if (!empty($incsql)) $sql .= "$incsql";
    if (empty($incsql) == FALSE AND empty($excsql) == FALSE) $sql .= " AND ";
    if (!empty($excsql)) $sql .= "$excsql";

    $sql .= " ORDER BY site, incid ASC ";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $numrows = mysql_num_rows($result);

    $html .= "<h2>".icon('reports', 32)." {$title}</h2>";
    $html .= "<p align='center'>".sprintf($strIncidentsBySiteReportDesc, $numrows)."</p>";
    $html .= "<table width='99%' align='center'>";
    $html .= "<tr><th>{$strOpened}</th><th>{$strIncident}</th><th>{$strExternalID}</th><th>{$strTitle}</th><th>{$strContact}</th><th>{$strSite}</th><th>{$strType}</th></tr>";
    $csvfieldheaders .= "{$strOpened},{$strIncident},{$strExternalID},{$strTitle},{$strContact},{$strSite},{$strType}\r\n";
    $rowcount = 0;
    $externalincidents = 0;
    $shade = 'shade1';
    while ($row = mysql_fetch_object($result))
    {
        $nicedate = ldate('d/m/Y',$row->opened);
        $html .= "<tr class='{$shade}'><td>$nicedate</td><td>{$row->incid}</td><td>{$row->externalid}</td><td>{$row->title}</td><td>{$row->cname}</td><td>{$row->site}</td><td>{$row->typename}</td></tr>\n";
        $csv .="'".$nicedate."', '{$row->incid}','{$row->externalid}', '{$row->title}','{$row->cname}','{$row->site}','{$row->typename}'\n";
        if (!empty($row->externalid))
        {
            $externalincidents++;
            $sitetotals[$row->siteid]['extincidents']++;
        }
        $sitetotals[$row->siteid]['incidents']++;
        if ($sitetotals[$row->siteid]['name'] == '')
        {
            $sitetotals[$row->siteid]['name'] = $row->site;
        }

        if ($shade == "shade1") $shade = "shade2";
        else $shade = "shade1";
    }

    if ($showsitetotals)
    {
        foreach ($sitetotals AS $sitetotal)
        {
            if ($sitetotal['incidents'] >= 1)
            {
                $externalpercent = number_format(($sitetotal['extincidents'] / $sitetotal['incidents'] * 100),1);
            }
            $html .= "<tr class='shade1'><td colspan='0'>";
            $html .= sprintf($strNumOfIncidentsLoggedByX, $sitetotal['name']);
            $html .= ": {$sitetotal['incidents']}, ";
            $html .= "{$strLoggedExternally}: {$sitetotal['extincidents']} ";
            $html .= "({$externalpercent}%)</td></tr>\n";
        }
    }

    if ($numrows >= 1)
    {
        $externalpercent = number_format(($externalincidents / $numrows * 100),1);
    }

    if ($showtotals)
    {
        $html .= "<tfoot><tr><td colspan='0'>".sprintf($strReportIncidentsBySiteDesc, $numrows, $externalincidents, $externalpercent)."</td></tr></tfoot>\n";
    }

    $html .= "</table>";

    // $html .= "<p align='center'>SQL Query used to produce this report:<br /><code>$sql</code></p>\n";

    if ($_POST['output'] == 'screen')
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo $html;
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
    elseif ($_POST['output'] == 'csv')
    {
        // --- CSV File HTTP Header
        header("Content-type: text/csv\r\n");
        header("Content-disposition-type: attachment\r\n");
        header("Content-disposition: filename=yearly_incidents.csv");
        echo $csvfieldheaders;
        echo $csv;
    }
}
?>