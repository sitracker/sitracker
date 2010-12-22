<?php
// report_customer_products_matrix.php -
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
//  Author:   Ivan Lucas

$permission = 37;  // Run Reports

require ('core.php');
include (APPLICATION_LIBPATH.'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

// External variables
$title = $strSiteProductsMatrix;

// show search renewal form
switch ($_POST['action'])
{
    case 'runreport':
        $min_expire = cleanvar($_POST['min_expire']);
        $max_expire = cleanvar($_POST['max_expire']);
        $output = cleanvar($_POST['output']);
        $vendor = cleanvar($_POST['vendor']);

        if (!empty($min_expire)) $min_expiry=strtotime($min_expire);
        else $min_expiry = $now;

        if (!empty($max_expire)) $max_expiry=strtotime($max_expire);
        else $max_expiry = $now;

        $sql = "SELECT p.id, p.name FROM `{$dbProducts}` AS p, `{$dbMaintenance}` AS m ";
        $sql .= "WHERE p.id = m.product AND ";
        $sql .= "m.expirydate <= $max_expiry AND m.term != 'yes' AND ";
        $sql .= "p.vendorid = '{$vendor}' ORDER BY name";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        while ($prod = mysql_fetch_object($result))
        {
            $product[$prod->id] = $prod->name;
        }

        $vendor = cleanvar($_POST['vendor']);

        $sql  = "SELECT m.id AS maintid, s.name AS site, s.id AS siteid, ";
        $sql .= "c.address1 AS address1, p.name AS product, ";
        $sql .= "r.name AS reseller, licence_quantity, l.name AS licence_type, ";
        $sql .= "expirydate, admincontact, c.forenames AS forenames, c.surname AS admincontactname, m.notes, ";
        $sql .= "c.department AS department, c.address2 AS address2, c.city AS city, c.county, c.country, c.postcode, st.typename AS typename ";
        $sql .= "FROM `{$dbMaintenance}` AS m, `{$dbSites}` AS s, `{$dbSiteTypes}` AS st, `{$dbContacts}` AS c, `{$dbProducts}` AS p, `{$dbLicenceTypes}` AS l, `{$dbResellers}` AS r WHERE ";
        $sql .= "(m.site = s.id ";
        $sql .= "AND s.typeid = st.typeid ";
        $sql .= "AND product = p.id ";
        if (!empty($vendor)) $sql .= "AND p.vendorid = '{$vendor}' ";
        $sql .= "AND reseller = r.id AND licence_type = l.id AND admincontact = c.id) AND ";
        $sql .= "expirydate <= $max_expiry AND expirydate >= $min_expiry AND m.term != 'yes' GROUP BY s.id ORDER BY expirydate ASC";

// echo $sql;

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

        if (mysql_num_rows($result) > 0)
        {
            $html = "<table><tr><th>Site</th>";
            $csv = "\"Site";
            // products list
            foreach ($product AS $prodid => $prodname)
            {
                $html .= "<th>{$prodname}</th>";
                $csv .= "\",\"{$prodname}";
            }
            $html .= "</tr>";
            $csv .= "\"\n";

            while ($site = mysql_fetch_object($result))
            {
                $html .= "<tr><td>{$site->site}</td>";
                $csv .= strip_comma($site->site);

                $prodsql  = "SELECT p.name AS product, p.id AS productid, m.expirydate AS expirydate, m.term AS term, ";
                $prodsql .= "m.productonly AS productonly, m.licence_type AS licencetype, ";
                $prodsql .= "m.licence_quantity AS licencequantity FROM `{$dbProducts}` AS p, `{$dbMaintenance}` AS m ";
                $prodsql .= "WHERE p.id=m.product AND m.site='{$site->siteid}' ";
                if (!empty($vendor)) $sql .= "AND p.vendorid='{$vendor}' ";
                $prodsql .= "AND expirydate <= $max_expiry AND expirydate >= $min_expiry ";
                $prodsql .= "AND m.term!='yes' ";
                $prodsql .= "ORDER BY expirydate ASC";

                $prodresult = mysql_query($prodsql);
                if (mysql_error()) trigger_error('!Error: MySQL Query Error:',mysql_error(), E_USER_WARNING);

                if (mysql_num_rows($prodresult)>0)
                {
                    $numofproducts = mysql_num_rows($prodresult);
                    while ($siteproducts = mysql_fetch_object($prodresult))
                    {
                        $supportedproduct[$site->siteid][$siteproducts->productid] = $siteproducts->product;
                    }
                }
                // products list
                foreach ($product AS $prodid => $prodname)
                {
                    if (array_key_exists($prodid, $supportedproduct[$site->siteid]))
                    {
                        $html .= "<td>{$prodname}</td>";
                        $csv .= "\",\"".strip_comma($prodname);
                    }
                    else
                    {
                        $html .= "<td></td>";
                        $csv .= "\",\"";
                    }
                }
                $html .= "</tr>\n";
                $csv .= "\"\n";
            }
            $html .= "</table>";
            mysql_free_result($result);

            // Print Headers
            if ($output == 'csv')
            {
                header("Content-type: text/csv\r\n");
                //header(\"Content-length: $fsize\\r\\n\");
                header("Content-disposition-type: attachment\r\n");
                header("Content-disposition: filename=site_products_matrix.csv");
                echo $csv;
            }
            else
            {
                include (APPLICATION_INCPATH . 'htmlheader.inc.php');
                echo $html;
                include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
            }
        }
        else
        {
            html_redirect($_SERVER['PHP_SELF'], FALSE, $strNoResults);
        }
        break;

    default:
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>{$strSiteProductsMatrix}</h2>";
        echo "<form name='report' action='{$_SERVER['PHP_SELF']}' method='post'>";
        echo "<table class='vertical'>";
        echo "<tr><th>{$strVendor}</th>";
        echo "<td>".vendor_drop_down('vendor', 0)."</td></tr>\n";
        echo "<tr><th>{$strEarliestExpiry}</th>";
        echo "<td><input maxlength='100' id='min_expire' name='min_expire' size='10' type='text' value=\"".date('Y-m-d')."\" /> ";
        echo date_picker('report.min_expire');
        echo "</td></tr>\n";
        echo "<tr><th>{$strLatestExpiry}</th>";
        echo "<td><input maxlength='100' id='max_expire' name='max_expire' size='10' type='text' value=\"".date('Y-m-d',mktime(0,0,0,date('m'),date('d'),date('Y')+1))."\" /> ";
        echo date_picker('report.max_expire');
        echo "</td></tr>\n";
        echo "<tr><th>{$strOutput}:</th>";
        echo "<td>";
        echo "<select name='output'>";
        echo "<option value='screen'>{$strScreen}</option>";
        echo "<option value='csv'>{$strCSVfile}</option>";
        echo "</select>";
        echo "</td></tr>\n";
        echo "</table>";
        echo "<p><input name='submit' type='submit' value=\"{$strRunReport}\" /></p>";
        echo "<input type='hidden' name='action' value='runreport' />";
        echo "</form>\n";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        break;
}
?>
