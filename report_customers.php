<?php
// cust_export.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!  4Feb06

require ('core.php');
$permission = PERM_REPORT_RUN; // Run Reports
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strCustomerExport;

$mode = clean_fixed_list($_REQUEST['mode'], array('','report'));
if (empty($mode))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('reports', 32)." {$strCustomerExport}</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
    echo "<table class='vertical'>";
    echo "<tr><th colspan='2' align='center'>{$strInclude}".help_link('CTRLAddRemove')."</th></tr>";
    // echo "<th align='center' width='300' class='shade1'>Exclude</th>";
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
    echo "<tr>";
    echo "<td colspan='2'>";
    echo "{$strOutput}: <select name='output'>";
    echo "<option value='screen'>{$strScreen}</option>";
    // echo "<option value='printer'>Printer</option>";
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
    echo "<table class='maintable'><tr><td>";
    echo "<h4>{$strCSVFileFormatAsFollows}:</h4>";
    echo "<strong>{$strField1}:</strong> {$strForenames}<br />";
    echo "<strong>{$strField2}:</strong> {$strSurname}<br />";
    echo "<strong>{$strField3}:</strong> {$strEmail}<br />";
    echo "<strong>{$strField4}:</strong> {$strAddress1}<br />";
    echo "<strong>{$strField5}:</strong> {$strAddress2}<br />";
    echo "<strong>{$strField6}:</strong> {$strCity}<br />";
    echo "<strong>{$strField7}:</strong> {$strCounty}<br />";
    echo "<strong>{$strField8}:</strong> {$strPostcode}<br />";
    echo "<strong>{$strField9}:</strong> {$strCountry}<br />";
    echo "<strong>{$strField10}:</strong> {$strTelephone}<br />";
    echo "<strong>{$strField11}:</strong> {$strSite}<br />";
    echo "<strong>{$strField12}:</strong> {$strProducts} <em>({$strListsAllTheCustomersProducts})</em><br />";
    echo "</td></tr></table>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($mode == 'report')
{
    if (is_array($_POST['exc']) && is_array($_POST['exc']))
    {
        $_POST['inc'] = array_values(array_diff($_POST['inc'],$_POST['exc']));  // don't include anything excluded
    }
    $includecount = count($_POST['inc']);
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
    /*
    $excludecount=count($_POST['exc']);
    if ($excludecount >= 1)
    {
    // $html .= "<strong>Exclude:</strong><br />";
    $excsql .= "(";
    for ($i = 0; $i < $excludecount; $i++)
    {
        // $html .= "{$_POST['exc'][$i]} <br />";
        $excsql .= "siteid!={$_POST['exc'][$i]}";
        if ($i < ($excludecount-1)) $excsql .= " OR ";
    }
    $excsql .= ")";
    }
    */
    $sql = "SELECT *, c.id AS contactid, s.name AS site, c.email AS cemail FROM `{$dbContacts}` AS c ";
    $sql .= "LEFT JOIN `{$dbSites}` AS s ON c.siteid = s.id ";

    if (empty($incsql) == FALSE OR empty($excsql) == FALSE) $sql .= "WHERE ";
    if (!empty($incsql)) $sql .= "$incsql";
    if (empty($incsql) == FALSE AND empty($excsql) == FALSE) $sql .= " AND ";
    if (!empty($excsql)) $sql .= "$excsql";

    $sql .= " ORDER BY c.email ASC ";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $numrows = mysql_num_rows($result);

    $html .= "<h2>".icon('reports', 32)." {$strCustomerExport}</h2>";
    $html .= "<p align='center'>".sprintf($strThisReportShowsContactForSelectedSites, $numrows)."</p>";
    $html .= "<table width='99%' align='center'>";
    $html .= "<tr><th>{$strForenames}</th><th>{$strSurname}</th><th>{$strEmail}</th><th>{$strAddress1}</th>";
    $html .= "<th>{$strAddress2}</th><th>{$strCity}</th><th>{$strCounty}</th><th>{$strPostcode}</th><th>{$strCountry}</th><th>{$strTelephone}</th><th>{$strSite}</th><th>{$strProducts}</th></tr>";
    $csvfieldheaders .= "\"{$strForenames}\",\"{$strSurname}\",\"{$strEmail}\",\"{$strAddress1}\",\"{$strAddress2}\",\"{$strCity}\",\"{$strCounty}\",\"{$strPostcode}\",\"{$strCountry}\",\"{$strTelephone}\",\"{$strSite}\",\"{$strProducts}\"\r\n";
    $rowcount = 0;
    while ($row = mysql_fetch_object($result))
    {
        $html .= "<tr class='shade2'><td>{$row->forenames}</td><td>{$row->surname}</td>";
        if ($row->dataprotection_email != 'Yes') $html .= "<td>{$row->cemail}</td>";
        else $html .= "<td><em style='color: red';>{$strWithheld}</em></td>";
        if ($row->dataprotection_address != 'Yes')
    	{
            $html .= "<td>{$row->address1}</td><td>{$row->address2}</td><td>{$row->city}</td><td>{$row->county}</td><td>{$row->postcode}</td><td>{$row->country}</td>";
    	}
        else
        {
            $html .= "<td colspan='6'><em style='color: red';>{$strWithheld}</em></td>";
        }
        if ($row->dataprotection_phone != 'Yes') $html .= "<td>{$row->phone}</td>";
        else $html .= "<td><em style='color: red';>{$strWithheld}</em></td>";

        $html .= "<td>{$row->site}</td>";

        $psql = "SELECT * FROM `{$dbSupportContacts}` AS sc, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p WHERE ";
        $psql .= "sc.maintenanceid = m.id AND ";
        $psql .= "m.product = p.id ";
        $psql .= "AND sc.contactid = '{$row->contactid}' ";
        $html .= "<td>";

        $csv .= "\"".strip_comma($row->forenames).'","'  . strip_comma($row->surname).'","';
        if ($row->dataprotection_email != 'Yes') $csv .= strip_comma(strtolower($row->cemail)).'","';
        else $csv .= '","';

        $csv  .= strip_comma($row->address1).'","'
            . strip_comma($row->address2).'","'
            . strip_comma($row->city).'","'
            . strip_comma($row->county).'","'
            . strip_comma($row->postcode).'","'
            . strip_comma($row->country).'","';

        if ($row->dataprotection_phone != 'Yes') $csv .= strip_comma(strtolower($row->phone)).'","';
        else $csv .= '","';

        $csv .= strip_comma($row->site).'","';

        $presult = mysql_query($psql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $numproducts = mysql_num_rows($presult);
        $productcount = 1;

        while ($product = mysql_fetch_object($presult))
        {
            $html .= strip_comma($product->name);
            $csv .=  strip_comma($product->name);
            if ($productcount < $numproducts) { $html .= " - "; $csv.=' - '; }
            $productcount++;
        }

        $html .= "</td>";
        $csv .= strip_comma($row->name) ."\"\r\n";

        $rowcount++;
    }
    $html .= "</table>";
    $html .= "<p align='center'>".sprintf($strXRecords, $rowcount)."</p>";

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
        header("Content-disposition: filename=qbe_report.csv");
        echo $csvfieldheaders;
        echo $csv;
    }
}
?>
