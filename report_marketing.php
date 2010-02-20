<?php
// report_marketing.php - Print/Export a list of contacts by product
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Report Type: Marketting

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


$permission = 37; // Run Reports

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

if (empty($_REQUEST['mode']))
{
    $title = $strMarketingMailshot;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>{$strMarketingMailshot}</h2>";
    echo "<p align='center'>{$strMarketingMailshotDesc}</p>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
    echo "<table align='center' class='vertical'>";
    echo "<tr><th>{$strFilter}: {$strTag}</th><td><input type='text' ";
    echo "name='filtertags' value='' size='15' /></td></tr>";
    echo "<tr><th>{$strInclude}: {$strProducts}".help_link('CTRLAddRemove')."</th>";
    echo "<td>";
    $sql   = "SELECT * FROM `{$dbProducts}` ORDER BY name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    echo "<select name='inc[]' multiple='multiple' size='6'>";
    while ($product = mysql_fetch_object($result))
    {
        echo "<option value='{$product->id}'>$product->name</option>\n";
    }
    echo "</select>";
    echo "</td></tr>\n";
    echo "<tr>";
    echo "<th>{$strExclude}: {$strProducts}".help_link('CTRLAddRemove')."</th>";
    echo "<td>";
    $sql = "SELECT * FROM `{$dbProducts}` ORDER BY name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    echo "<select name='exc[]' multiple='multiple' size='6'>";
    while ($product = mysql_fetch_object($result))
    {
        echo "<option value='{$product->id}'>$product->name</option>\n";
    }
    echo "</select>";
    echo "</td></tr>\n";

    $sql = "SELECT * FROM `{$dbSiteTypes}`";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    if (mysql_num_rows($result) > 0)
    {
        echo "<tr><th>{$strSiteType}".help_link('CTRLAddRemove')."</th><td>";
        echo "<select name='sitetype[]' multiple='multiple' size='6'>";
        while ($obj = mysql_fetch_object($result))
        {
            echo "<option value='{$obj->typeid}'>$obj->typename</option>\n";
        }
        echo "</select>";
        echo "</td></tr>\n";
    }

    echo "<tr><td  colspan='2'><label><input type='checkbox' name='activeonly'";
    echo " value='yes' /> {$strShowActiveOnly}</label></td></tr>";

    echo "<tr><td colspan='2'>{$strOutput}: <select name='output'>";
    echo "<option value='screen'>{$strScreen}</option>";
    // echo "<option value='printer'>Printer</option>";
    echo "<option value='csv'>{$strCSVfile}</option>";
    echo "</select>";
    echo "</td></tr>";
    echo "</table>";
    echo "<p align='center'>";
    echo "<input type='hidden' name='table1' value='{$_POST['table1']}' />";
    echo "<input type='hidden' name='mode' value='report' />";
    echo "<input type='reset' value=\"{$strReset}\" /> ";
    echo "<input type='submit' value=\"{$strRunReport}\" />";
    echo "</p>";
    echo "</form>";
    echo "<h4>{$strCSVFileFormatAsFollows}:</h4>";
    echo "<div style='margin-left:35%;margin-right:35%;'>";
    echo "<strong>{$strField} 1:</strong> {$strForenames}<br />";
    echo "<strong>{$strField} 2:</strong> {$strSurname}<br />";
    echo "<strong>{$strField} 3:</strong> {$strEmail}<br />";
    echo "<strong>{$strField} 4:</strong> {$strSite}<br />";
    echo "<strong>{$strField} 5:</strong> {$strAddress1}<br />";
    echo "<strong>{$strField} 6:</strong> {$strAddress2}<br />";
    echo "<strong>{$strField} 7:</strong> {$strCity}<br />";
    echo "<strong>{$strField} 8:</strong> {$strCounty}<br />";
    echo "<strong>{$strField} 9 :</strong> {$strPostcode}<br />";
    echo "<strong>{$strField} 10:</strong> {$strCountry}<br />";
    echo "<strong>{$strField} 11:</strong> {$strTelephone}<br />";
    echo "<strong>{$strField} 12:</strong> {$strProducts} <em>";
    echo "({$strListsAllTheCustomersProducts})</em></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($_REQUEST['mode'] == 'report')
{
    $sitetype = $_POST['sitetype'];
    // echo "REPORT";
    // don't include anything excluded
    if (is_array($_POST['inc']) && is_array($_POST['exc']))
    {
        $_POST['inc'] = array_values(array_diff($_POST['inc'],$_POST['exc']));
    }

    $filtertags = cleanvar($_POST['filtertags']);
    $filtertype = cleanvar($_POST['filtertype']);

    $includecount = count($_POST['inc']);
    if ($includecount >= 1)
    {
        // $html .= "<strong>Include:</strong><br />";
        $incsql .= "(";
        for ($i = 0; $i < $includecount; $i++)
        {
            // $html .= "{$_POST['inc'][$i]} <br />";
            $incsql .= "product={$_POST['inc'][$i]}";
            if ($i < ($includecount-1)) $incsql .= " OR ";
        }
        $incsql .= ")";
    }
    $excludecount = count($_POST['exc']);
    if ($excludecount >= 1)
    {
        // $html .= "<strong>Exclude:</strong><br />";
        $excsql .= "(";
        for ($i = 0; $i < $excludecount; $i++)
        {
            // $html .= "{$_POST['exc'][$i]} <br />";
            $excsql .= "product!={$_POST['exc'][$i]}";
            if ($i < ($excludecount-1)) $excsql .= " AND ";
        }
        $excsql .= ")";
    }

    $sql  = "SELECT *, c.id AS contactid, c.email AS contactemail, ";
    $sql .= "s.name AS sitename FROM `{$dbMaintenance}` AS m ";
    $sql .= "LEFT JOIN `{$dbSupportContacts}` AS sc ON m.id = sc.maintenanceid ";
    $sql .= "LEFT JOIN `{$dbContacts}` AS c ON sc.contactid = c.id ";
    $sql .= "LEFT JOIN `{$dbSites}` AS s ON c.siteid = s.id ";

    $sitetypecount = count($sitetype);

    if (empty($incsql) == FALSE OR empty($excsql) == FALSE OR
        $_REQUEST['activeonly'] == 'yes' OR $sitetypecount > 0)
    {
        $sql .= "WHERE ";
    }

    if ($_REQUEST['activeonly'] == 'yes')
    {
        if (!empty($filtertype)) $sql .= "AND ";
        $sql .= "m.term!='yes' AND m.expirydate > '$now' ";
    }
    if (!empty($incsql))
    {
        if (!empty($filtertype) OR $_REQUEST['activeonly'] == 'yes') $sql .= "AND ";
        $sql .= "$incsql";
    }
    if (!empty($excsql))
    {
        if (!empty($filtertype) OR $_REQUEST['activeonly'] == 'yes' OR
        !empty($incsql)) $sql .= "AND ";
        $sql .= "$excsql";
    }

    if  ($sitetypecount > 0)
    {
        if (!empty($incsql) OR !empty($excsql)) $sql .= " AND ";
        $s = " (";
        for ($i = 0; $i < $sitetypecount; $i++)
        {
            // $html .= "{$_POST['exc'][$i]} <br />";
            $s .= "s.typeid = ".cleanvar($sitetype[$i]);
            if ($i < ($sitetypecount - 1)) $s  .= " AND ";
        }
        $s .= ")";

        $sql .= $s;
    }

    $sql .= " ORDER BY c.email ASC ";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $numrows = mysql_num_rows($result);

    // FIXME strip slashes from output
    $html .= "<table width='99%' align='center'>";
    $html .= "<tr><th>{$strForenames}</th><th>{$strSurname}</th><th>";
    $html .= "{$strEmail}</th><th>{$strSite}</th><th>{$strAddress1}</th>";
    $html .= "<th>{$strAddress2}</th><th>{$strCity}</th><th>{$strCounty}</th>";
    $html .= "<th>{$strPostcode}</th><th>{$strCountry}</th><th>{$strTelephone}";
    $html .= "</th><th>{$strProducts}</th></tr>";
    $csvfieldheaders .= "\"{$strForenames}\",\"{$strSurname}\",\"{$strEmail}\",\"{$strSite}\",\"";
    $csvfieldheaders .= "{$strAddress1}\",\"{$strAddress2}\",\"{$strCity}\",\"{$strCounty}\",\"";
    $csvfieldheaders .= "{$strPostcode}\",\"{$strCountry}\",\"{$strTelephone}\",\"";
    $csvfieldheaders .= "{$strProducts}\"\r\n";
    $rowcount = 0;
    while ($row = mysql_fetch_object($result))
    {
        $tags = list_tags($row->siteid, TAG_SITE, FALSE);
        $tags = explode(', ', $tags);
        if (!empty($filtertags))
        {
            if (in_array($filtertags, $tags)) $hide = FALSE;
            else $hide = TRUE;
        }
        else $hide = FALSE;
        if ($row->contactemail!=$lastemail AND $hide == FALSE)
        {
            $html .= "<tr class='shade2'><td>{$row->forenames}</td>";
            $html .= "<td>{$row->surname}</td>";
            if ($row->dataprotection_email!='Yes')
            {
                $html .= "<td>{$row->contactemail}</td>";
            }
            else
            {
                $html .= "<td><em style='color: red';>{$strWithheld}</em></td>";
            }

            $html .= "<td>{$row->sitename}</td>";
            if ($row->dataprotection_address!='Yes')
            {
                $html .= "<td>{$row->address1}</td><td>{$row->address2}</td>";
                $html .= "<td>{$row->city}</td><td>{$row->county}</td>";
                $html .="<td>{$row->postcode}</td><td>{$row->country}</td>";
            }
            else
            {
                $html .= "<td colspan='6'><em style='color: red';>";
                $html .= "{$strWithheld}</em></td>";
            }

            if ($row->dataprotection_phone!='Yes')
            {
                $html .= "<td>{$row->phone}</td>";
            }
            else
            {
                $html .= "<td><em style='color: red';>{$strWithheld}</em></td>";
            }

            $psql = "SELECT * FROM `{$dbSupportContacts}` AS sc, ";
            $psql .= "`{$dbMaintenance}` AS m, `{$dbProducts}` AS p WHERE ";
            $psql .= "sc.maintenanceid = m.id AND ";
            $psql .= "m.product = p.id ";
            $psql .= "AND sc.contactid = '{$row->contactid}' ";
            $html .= "<td>";

            $csv .= "\"".strip_comma($row->forenames).'","'
                . strip_comma($row->surname).'","';

            if ($row->dataprotection_email != 'Yes')
            {
                $csv .= strip_comma(strtolower($row->contactemail)).'","';
            }
            else
            {
                $csv .= '","';
            }

            if ($row->dataprotection_address != 'Yes')
            {
                $csv  .= strip_comma($row->sitename).'","'
                    . strip_comma($row->address1).'","'
                    . strip_comma($row->address2).'","'
                    . strip_comma($row->city).'","'
                    . strip_comma($row->county).'","'
                    . strip_comma($row->postcode).'","'
                    . strip_comma($row->country).'","';
            }
            else
            {
                $csv .= '","';
            }

            if ($row->dataprotection_phone != 'Yes')
            {
                $csv .= strip_comma(strtolower($row->phone)).'","';
            }
            else
            {
                $csv .= '","';
            }

            $presult = mysql_query($psql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            $numproducts=mysql_num_rows($presult);
            $productcount=1;

            while ($product = mysql_fetch_object($presult))
            {
                $html .= strip_comma($product->name);
                $csv .=  strip_comma($product->name);
                if ($productcount < $numproducts)
                {
                    $html .= " - "; $csv.=' - ';
                }
                $productcount++;
            }
            $html .= "</td>";
            $csv .= strip_comma($row->name) ."\"\r\n";

            $rowcount++;
        }
        $lastemail = $row->contactemail;
    }
    $html .= "</table>";
    $html .= "<p align='center'>".sprintf($strShowingXofX, $rowcount, $numrows)."</p>";
    //$html .= "<p align='center'>SQL Query used to produce this report:<br /><code>$sql</code></p>\n";

    if ($_REQUEST['output'] == 'screen')
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>{$strMarketingMailshot}</h2>";
        echo "<p align='center'>{$strMarketingMailshotDesc}</p>";
        echo $html;
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
    elseif ($_REQUEST['output'] == 'csv')
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