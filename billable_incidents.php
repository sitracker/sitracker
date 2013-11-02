<?php
// billable_incidents.php - Report for billing incidents
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_SITE_VIEW; // View sites, more granular permissions are defined on the more sensitive sections
require (APPLICATION_LIBPATH . 'functions.inc.php');

require_once (APPLICATION_LIBPATH . 'billing.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strBillableIncidents;
$startdateorig = cleanvar($_REQUEST['startdate']);
$enddateorig = cleanvar($_REQUEST['enddate']);

$startdate = strtotime(cleanvar($_REQUEST['startdate']));
$enddate = strtotime(cleanvar($_REQUEST['enddate']));
$mode = cleanvar($_REQUEST['mode']);
$sites = $_REQUEST['sites'];
$output = cleanvar($_REQUEST['output']);
if (empty($output)) $output = 'html';
$showonlyapproved = cleanvar($_REQUEST['showonlyapproved']);

if (empty($enddate)) $enddate = $now;

$sitelistsql = "SELECT DISTINCT m.site, s.name ";
$sitelistsql .= "FROM `{$dbMaintenance}` AS m, `{$dbServiceLevels}` AS sl, `{$dbSites}` AS s, `{$dbMaintenanceServiceLevels}` AS msl ";
$sitelistsql .= "WHERE m.id = msl.maintenanceid AND msl.servicelevel = sl.tag AND sl.timed = 'yes' AND m.site = s.id ";

$sitestr = '';

if (!empty($sites))
{
    foreach ($sites AS $s)
    {
        if (empty($sitestr)) $sitestr .= "m.site = {$s} ";
        else $sitestr .= "OR m.site = {$s} ";
    }

    $sitelistsql .= "AND {$sitestr}";
}

if (empty($mode))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('billing', 32, $strBilling)." {$strBilling}</h2>";

    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' id='billableincidents'>";
    echo "<table class='vertical'>";

    echo "<tr><th>{$strType}:</th><td>";
    echo "<label><input type='radio' name='mode' value='summarypage' id='summarypage' onclick=\"$('startdatesection').hide();" .
            " $('enddatesection').hide(); $('sitebreakdownsection').hide(); $('displaysection').show(); $('showfoc').show(); $('showfocaszero').show(); $('showexpiredaszero').show();\" checked='checked' />{$strSummary}</label> ";

    if (user_permission($sit[2], PERM_INCIDENT_BILLING_APPROVE) == TRUE)
    {
        echo "<label><input type='radio' name='mode' value='approvalpage' id='approvalpage' onclick=\"$('startdatesection').show();" .
                " $('enddatesection').show(); $('sitebreakdownsection').hide(); $('displaysection').hide(); $('showfoc').hide(); $('showfocaszero').hide(); $('showexpiredaszero').hide();\" />{$strApprove}</label> ";
    }

    if (user_permission($sit[2], PERM_BILLING_TRANSACTION_VIEW) == TRUE)
    {
        echo "<label><input type='radio' name='mode' value='transactions' id='transactions' onclick=\"$('startdatesection').show(); " .
                "$('enddatesection').show(); $('sitebreakdownsection').show(); $('displaysection').show(); $('showfoc').show(); $('showfocaszero').show(); $('showexpiredaszero').hide();\" />{$strTransactions}</label> ";
    }
    echo "</td></tr>\n";

    echo "<tbody style='display:none' id='startdatesection' ><tr><th>{$strStartDate}:</th>";
    echo "<td><input type='text' name='startdate' id='startdate' size='10' /> ";
    echo date_picker('billableincidents.startdate');
    echo "</td></tr></tbody>\n";
    echo "<tbody style='display:none' id='enddatesection' ><tr><th>{$strEndDate}:</th>";
    echo "<td><input type='text' name='enddate' id='enddate' size='10' /> ";
    echo date_picker('billableincidents.enddate');
    echo "</td></tr></tbody>\n";

    echo "<tbody  style='display:none' id='sitebreakdownsection' ><tr><th>{$strSiteBreakDown}:</th>";
    echo "<td><input type='checkbox' name='sitebreakdown' id='sitebreakdown' size='10' /> ";
    echo "</td></tr></tbody>\n";

    echo "<tbody id='showfoc'><tr><th>{$strShowFreeOfCharge}</th>";
    echo "<td><input type='checkbox' id='foc' name='foc' value='show' checked='checked' /></td></tr></tbody>";

    echo "<tbody id='showfocaszero'><tr><th>{$strShowFreeOfChargeAsZero}</th>";
    echo "<td><input type='checkbox' id='focaszero' name='focaszero' value='show' checked='checked' /></td></tr></tbody>";

    echo "<tbody id='showexpiredaszero'><tr><th>{$strShowExpiredAsZero}</th>";
    echo "<td><input type='checkbox' id='expiredaszero' name='expiredaszero' value='show' checked='checked' /></td></tr></tbody>";

    echo "<tbody id='displaysection' ><tr><th>{$strOutput}:</th>";
    echo "<td><label><input type='radio' name='display' value='html' checked='checked' /> {$strScreen}</label>";
    echo "<label><input type='radio' name='display' value='csv' /> {$strCSVfile}</label> ";
    echo "</td></tr></tbody>\n";

    $sitelistsql .= " ORDER BY s.name";

    $result = mysql_query($sitelistsql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        echo "<tr><th>{$strSite}:<br />{$strSelectNoneAssumesAll}</th><td>";
        echo "<select name='sites[]' id='sites' multiple='multiple'>\n";
        while ($obj = mysql_fetch_object($result))
        {
            echo "<option id='site{$obj->site}' value='{$obj->site}'>{$obj->name}</option>\n";
        }
        echo "</select></td></tr>\n";
    }

    echo "</table>";

    echo "<p align='center'>";
    echo "<input type='submit' name='runreport' value='{$strRunReport}' /></p>";
    echo "</form>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($mode == 'approvalpage')
{
    if (user_permission($sit[2], PERM_INCIDENT_BILLING_APPROVE) == FALSE)
    {
        header("Location: {$CONFIG['application_webpath']}noaccess.php?id=".PERM_INCIDENT_BILLING_APPROVE);
        exit;
    }
    // Loop around all active sites - those with contracts

    // Need a breakdown of incidents so loop though each site and list the incidents

    /*
     SITE (total: x):
        Incident a - c
        Incident b - d
    */

    if ($output == 'html')
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>".icon('billing', 32, $strBillableIncidents)." {$strBillableIncidents} - {$strApprove}</h2>";

        echo "<p align='center'>{$strThisReportShowsIncidentsClosedInThisPeriod} ";
        echo ldate($CONFIG['dateformat_date'], $startdate)." - ".ldate($CONFIG['dateformat_date'], $enddate)."</p>";
    }

    if (!empty($startdate))
    {
        $sitelistsql .= "AND m.expirydate >= {$startdate} ";
    }

    $resultsite = mysql_query($sitelistsql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($resultsite) > 0)
    {
        while ($objsite = mysql_fetch_object($resultsite))
        {
            $sitename = $objsite->name;

            $sitenamenospaces = preg_replace("/ /", "_", $sitename);
            $sitenamenospaces = preg_replace("/'/", "_", $sitenamenospaces);

            echo "<h3>{$sitename}</h3>";

            $str = '';
            
            $sqlbillabletypes = "SELECT billingtype AS billingtype, id FROM `{$dbMaintenance}` WHERE site = {$objsite->site} AND billingtype IS NOT NULL GROUP BY billingtype ORDER BY billingtype";
            $resultbillabletypes = mysql_query($sqlbillabletypes);
            if (mysql_num_rows($resultbillabletypes) > 0)
            {
                while ($objbillabletypes = mysql_fetch_object($resultbillabletypes))
                {
                    $b = get_billable_object_from_contract_id($objbillabletypes->id);
                    $str .= $b->produce_site_approvals_table($objsite->site, $sitenamenospaces, $startdate, $enddate);
                }
            }
            
            
            if (!empty($str))
            {
                if ($output == 'html')
                {
                    echo "<form action='{$_SERVER['PHP_SELF']}?mode=approve&amp;startdate={$startdateorig}&amp;enddate={$enddateorig}' name='{$sitenamenospaces}' id='{$sitenamenospaces}'  method='post'>";
                    echo $str;                    
                    echo "</form>";


                    if ($unapprovable)
                    {
                    	echo "<p align='center'>{$strUnapprovableBilledIncidentsDesc}</p>";
                    }
                }
            }
            else
            {
                echo "<p align='center'>{$strNoInicdentsToApprove}</p>";
            }
        }
    }

    plugin_do('billing_approve_form');

    if ($output == 'html')
    {
        echo "<p class='return'><a href='" . htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES, $i18ncharset) . "'>{$strReturnToPreviousPage}</a></p>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
}
elseif ($mode == 'summarypage')
{
    include (APPLICATION_INCPATH . 'billing_summary.inc.php');
}
elseif ($mode == 'transactions')
{
    if (user_permission($sit[2], PERM_BILLING_TRANSACTION_VIEW) == FALSE)
    {
        header("Location: {$CONFIG['application_webpath']}noaccess.php?id=".PERM_BILLING_TRANSACTION_VIEW);
        exit;
    }

    include ('transactions.php');
}
elseif ($mode == 'approve')
{
    if (user_permission($sit[2], PERM_INCIDENT_BILLING_APPROVE) == FALSE)
    {
        header("Location: {$CONFIG['application_webpath']}noaccess.php?id=".PERM_INCIDENT_BILLING_APPROVE);
        exit;
    }

    $transactionid = clean_int($_REQUEST['transactionid']);
    $selected = clean_int($_POST['selected']);

    if (!empty($transactionid))
    {
        $status = true;
        $status = approve_incident_transaction($transactionid);
        $maintid = maintid_from_transaction($transactionid);
        $percent = get_service_percentage($maintid);
    }
    elseif (!empty($selected))
    {
        $status = TRUE;
        foreach ($selected AS $s)
        {
            $l = approve_incident_transaction($s);

            $status = $status AND $l;

            $maintid = maintid_from_transaction($s);
            $percent = get_service_percentage($maintid);
            if ($percent == FALSE) $percent = true;
        }
    }

    if ($percent !== FALSE)
    {
        $siteid = db_read_column('site', $dbMaintenance, $maintid);
        $t = new TriggerEvent('TRIGGER_SERVICE_LIMIT', array('contractid' => $maintid, 'serviceremaining' => $percent, 'siteid' => $siteid));
    }

    if ($status)
    {
        html_redirect("{$_SERVER['PHP_SELF']}?mode=approvalpage&amp;startdate={$startdateorig}&amp;enddate={$enddateorig}&amp;showonlyapproved={$showonlyapproved}");
    }
    else
    {
        html_redirect("{$_SERVER['PHP_SELF']}?mode=approvalpage&amp;startdate={$startdateorig}&amp;enddate={$enddateorig}&amp;showonlyapproved={$showonlyapproved}", FALSE, $strError);
    }
}

?>