<?php
// site_products.php - List products that sites have under contract
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

require ('core.php');
$permission = PERM_REPORT_RUN; // Run Reports
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strSiteProducts;

$mode = clean_fixed_list($_REQUEST['mode'], array('','report'));

if (empty($mode))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('reports', 32)." {$title}</h2>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
    echo "<table class='vertical'>";

    echo "<tr><th>{$strSiteType}:</td>";
    echo "<td>";
    echo sitetype_drop_down('type', 0, TRUE);
    echo " <span class='required'>{$strRequired}</span></td></tr>";

    echo "<tr><th>{$strOutput}:</th>";
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

    echo "<table class='maintable'><tr><td>";
    echo "<h4>{$strCSVFileFormatAsFollows}:</h4>";
    echo "<strong>{$strField1}:</strong> {$strSite}<br />";
    echo "<strong>{$strField2}:</strong> {$strAddress1}<br />";
    echo "<strong>{$strField3}:</strong> {$strAddress2}<br />";
    echo "<strong>{$strField4}:</strong> {$strCity}<br />";
    echo "<strong>{$strField5}:</strong> {$strCounty}<br />";
    echo "<strong>{$strField6}:</strong> {$strCountry}<br />";
    echo "<strong>{$strField7}:</strong> {$strPostcode}<br />";
    echo "<strong>{$strField8}:</strong> {$strProducts}<br />";
    echo "</td></tr></table>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($mode == 'report')
{
    $type = clean_int($_REQUEST['type']);
    $sql = "SELECT * FROM `{$dbSites}` WHERE typeid='{$type}' ORDER BY name";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $numrows = mysql_num_rows($result);

    $html .= "<p align='center'>{$strCustomerProductsMaintDesc}.</p>";
    $html .= "<table width='99%' align='center'>";
    $html .= "<tr><th>{$strSite}</th><th>{$strAddress1}</th>";
    $html .= "<th>{$strAddress2}</th><th>{$strCity}</th>";
    $html .= "<th>{$strCounty}</th><th>{$strCountry}</th>";
    $html .= "<th>{$strPostcode}</th><th>{$strProducts}</th></tr>";
    $csvfieldheaders .= "{$strSite},{$strAddress1},{$strAddress2},{$strCity},{$strCounty},{$strCountry},{$strPostcode},{$strProducts}\r\n";
    $rowcount = 0;
    while ($row = mysql_fetch_object($result))
    {
        $product = '';
        $nicedate = ldate('d/m/Y', $row->opened);
        $html .= "<tr class='shade2'><td>{$row->name}</td>";
        $html .= "<td>{$row->address1}</td><td>{$row->address2}</td>";
        $html .= "<td>{$row->city}</td><td>{$row->county}</td>";
        $html .= "<td>{$row->country}</td><td>{$row->postcode}</td>";
        $html .= "<td>";
        $psql  = "SELECT m.id AS maintid, m.term AS term, p.name AS product, ";
        $psql .= "m.admincontact AS admincontact, ";
        $psql .= "r.name AS reseller, licence_quantity, lt.name AS licence_type, expirydate, admincontact, c.forenames AS admincontactsforenames, c.surname AS admincontactssurname, m.notes AS maintnotes ";
        $psql .= "FROM `{$dbContacts}` AS c, `{$dbProducts}` AS p, `{$dbResellers}` AS r, `{$dbMaintenance}` AS m ";
        $psql .= "LEFT JOIN `{$dbLicenceTypes}` AS lt ON  m.licence_type = lt.id ";
        $psql .= "WHERE m.product = p.id AND m.reseller = r.id AND admincontact = c.id ";
        $psql .= "AND m.site = '{$row->id}' ";
        $psql .= "ORDER BY p.name ASC";
        $presult = mysql_query($psql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        while ($prod = mysql_fetch_object($presult))
        {
            $product.= "{$prod->product}\n";
        }
        $html .= nl2br($product)."</td>";
        $html .= "</tr>";
        $csv .= "'{$row->name}', '{$row->address1}','{$row->address2}','{$row->city}','{$row->county}','{$row->country}','{$row->postcode}',";
        $csv .= str_replace("\n", ",", $product)."\n";
    }
    $html .= "</table>";

    if ($_POST['output'] == 'screen')
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>".icon('reports', 32)." {$title}</h2>";
        echo $html;
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
    elseif ($_POST['output'] == 'csv')
    {
        // --- CSV File HTTP Header
        header("Content-type: text/csv\r\n");
        header("Content-disposition-type: attachment\r\n");
        header("Content-disposition: filename=site_products.csv");
        echo $csvfieldheaders;
        echo $csv;
    }
}
?>