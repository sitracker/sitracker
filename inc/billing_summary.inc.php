<?php
// billing/summary.php - Summary page - to show
// Summary of all sites and their balances and expiry date.(sf 1931092)
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney Paul Heaney <paulheaney[at]users.sourceforge.net>

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

$display = cleanvar($_REQUEST['display']);
$showfoc = cleanvar($_REQUEST['foc']);
$focaszero = cleanvar($_REQUEST['focaszero']);
$expiredaszero = cleanvar($_REQUEST['expiredaszero']);

if (empty($display)) $display = 'html';

$sql = "SELECT DISTINCT(CONCAT(m.id,sl.id)), m.site, m.product, m.expirydate AS maintexpiry, s.* ";
$sql .= "FROM `{$dbMaintenance}` AS m, `{$dbServiceLevels}` AS sl, `{$dbService}` AS s, `{$dbSites}` AS site ";
$sql .= "WHERE m.servicelevelid = sl.id AND sl.timed = 'yes' AND m.id = s.contractid AND m.site = site.id ";

if (empty($showfoc) OR $showfoc != 'show')
{
    $sql .= "AND s.foc = 'no' ";
}

$sitestr = '';

$csv_currency = html_entity_decode($CONFIG['currency_symbol'], ENT_NOQUOTES, "ISO-8859-15"); // Note using -15 as -1 doesnt support euro

if (!empty($sites))
{
    foreach ($sites AS $s)
    {
        if (empty($sitestr)) $sitestr .= "m.site = {$s} ";
        else $sitestr .= "OR m.site = {$s} ";
    }

    $sql .= "AND {$sitestr} ";
}

$sql .= "ORDER BY site.name, s.enddate";

$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

if (mysql_numrows($result) > 0)
{
    if ($display == 'html')
    {
        $str .= "<table align='center' class='vertical'><tr><th>{$strSiteName}</th><th>{$strProduct}</th>";
        $str .= "<th>{$strExpiryDate}</th><th>{$strCustomerReference}</th><th>{$strStartDate}</th><th>{$strEndDate}</th>";
        $str .= "<th>{$strFreeOfCharge}</th><th>{$strCreditAmount}</th><th>{$strBalance}</th>";
        $str .= "<th>{$strAwaitingApproval}</th><th>{$strReserved}</th><th>{$strAvailableBalance}</th>";
        $str .= "<th>{$strUnitRate}</th><th>{$strUnitsRemaingSingleTime}</th></tr>\n";
    }
    elseif ($display == 'csv')
    {
        $str .= "\"{$strSiteName}\",\"{$strProduct}\",\"{$strExpiryDate}\", \"{$strCustomerReference}\", \"{$strStartDate}\",\"{$strEndDate}\",\"{$strFreeOfCharge}\",\"{$strCreditAmount}\",\"{$strBalance}\",\"{$strAwaitingApproval}\",\"{$strReserved}\",\"{$strAvailableBalance}\",\"{$strUnitRate}\",\"{$strUnitsRemaingSingleTime}\"\n";
    }

    $lastsite = '';
    $lastproduct = '';

    $shade = 'shade1';
    while ($obj = mysql_fetch_object($result))
    {
        if ($obj->foc == 'yes' AND !empty($focaszero))
        {
			$obj->creditamount = 0;
			$obj->balance = 0;
        }

        if (!empty($expiredaszero) AND strtotime($obj->enddate) < $now)
        {
            $obj->balance = 0;
            $unitsat1times = 0;
            $actual = 0;
        }

        $totalcredit += $obj->creditamount;
        $totalbalance += $obj->balance;
        $awaitingapproval = service_transaction_total($obj->serviceid, BILLING_AWAITINGAPPROVAL)  * -1;
        $totalawaitingapproval += $awaitingapproval;
        $reserved = service_transaction_total($obj->serviceid, BILLING_RESERVED) * -1;
        $totalreserved += $reserved;

        $actual = ($obj->balance - $awaitingapproval) - $reserved;
        $totalactual +=$actual;

        if ($obj->unitrate != 0) $unitsat1times = round(($actual /$obj->unitrate), 2);
        else $unitsat1times = 0;

        $remainingunits += $unitsat1times;

        if ($display == 'html')
        {
            if ($obj->site != $lastsite OR $obj->product != $lastproduct)
            {
                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }

            $str .= "<tr class='{$shade}'>";
            if ($obj->site != $lastsite)
            {
                $str .= "<td>".site_name($obj->site)."</td>";
                $str .= "<td>".product_name($obj->product)."</td>";
            }
            else
            {
                $str .= "<td></td>";
                if ($obj->product != $lastproduct)
                {
                    $str .= "<td>".product_name($obj->product)."</td>";
                }
                else
                {
                    $str .= "<td></td>";
                }
            }
            $str .= "<td>".ldate('Y-m-d', $obj->maintexpiry)."</td>";

            $str .= "<td>{$obj->cust_ref}</td><td>{$obj->startdate}</td><td>{$obj->enddate}</td>";
            if ($obj->foc == 'yes') $str .= "<td>{$strYes}</td>";
            else $str .= "<td>{$strNo}</td>";
            $str .= "<td>{$CONFIG['currency_symbol']}".number_format($obj->creditamount,2)."</td>";
            $str .= "<td>{$CONFIG['currency_symbol']}".number_format($obj->balance,2)."</td>";
            $str .= "<td>{$CONFIG['currency_symbol']}".number_format($awaitingapproval, 2)."</td>";
            $str .= "<td>{$CONFIG['currency_symbol']}".number_format($reserved, 2)."</td>";
            $str .= "<td>{$CONFIG['currency_symbol']}".number_format($actual, 2)."</td>";
            $str .= "<td>{$CONFIG['currency_symbol']}{$obj->unitrate}</td>";
            $str .= "<td>{$unitsat1times}</td></tr>\n";

            $lastsite = $obj->site;
            $lastproduct = $obj->product;
        }
        elseif ($display == 'csv')
        {
            if ($obj->site != $lastsite)
            {
                $str .= "\"".site_name($obj->site)."\",";
                $str .= "\"".product_name($obj->product)."\",";
            }
            else
            {
                $str .= ",";
                if ($obj->product != $lastproduct)
                {
                    $str .= product_name($obj->product).",";
                }
                else
                {
                    $str .= ",";
                }
            }

            $str .= "\"{$obj->cust_ref}\",\"{$obj->startdate}\",\"{$obj->enddate}\",";
            if ($obj->foc == 'yes') $str .= "\"{$strYes}\",";
            else $str .= "\"{$strNo}\",";
            $str .= "\"{$csv_currency}{$obj->creditamount}\",\"{$csv_currency}{$obj->balance}\",";
            $str .= "\"{$awaitingapproval}\", \"{$reserved}\", \"{$actual}\", ";
            $str .= "\"{$csv_currency}{$obj->unitrate}\",";
            $str .= "\"{$unitsat1times}\"\n";
        }
    }

    if ($display == 'html')
    {
        $str .= "<tfoot><tr><td colspan='7' align='right'><strong>{$strTOTALS}</strong></td><td>{$CONFIG['currency_symbol']}".number_format($totalcredit, 2)."</td>";
        $str .= "<td>{$CONFIG['currency_symbol']}".number_format($totalbalance, 2)."</td><td>{$CONFIG['currency_symbol']}".number_format($totalawaitingapproval, 2)."</td>";
        $str .= "<td>{$CONFIG['currency_symbol']}".number_format($totalreserved, 2)."</td><td>{$CONFIG['currency_symbol']}".number_format($totalactual, 2)."</td><td></td><td>{$remainingunits}</td></tr></tfoot>";
        $str .= "</table>";
        $str .= "<p align='center'><a href='{$_SERVER['HTTP_REFERER']}'>{$strReturnToPreviousPage}</a></p>";
    }
    elseif ($display == 'csv')
    {
        $str .= ",,,,,\"{$strTOTALS}\",\"{$csv_currency}{$totalcredit}\",";
        $str .= "\"{$csv_currency}{$totalbalance}\",\"{$totalawaitingapproval}\",\"{$totalreserved}\",\"{$totalactual}\",,\"{$remainingunits}\"\n";
    }
}
else
{
    $str = $strNone;
}

if ($display == 'html')
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>{$strBillingSummary}</h2>";
    echo $str;
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($display == 'csv')
{
    header("Content-type: text/csv\r\n");
    header("Content-disposition-type: attachment\r\n");
    header("Content-disposition: filename=billing_summary.csv");
    echo $str;
}

?>