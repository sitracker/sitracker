<?php
// billable_incidents.php - Report for billing incidents
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney Paul Heaney <paulheaney[at]users.sourceforge.net>


$permission = 11; // View sites, more granular permissions are defined on the more sensitive sections

require ('core.php');
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
$sitelistsql .= "FROM `{$dbMaintenance}` AS m, `{$dbServiceLevels}` AS sl, `{$dbSites}` AS s ";
$sitelistsql .= "WHERE  m.servicelevel = sl.tag AND sl.timed = 'yes' AND m.site = s.id ";

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

    echo "<h2>".icon('billing', 32)." {$strBilling}</h2>";

    ?>
    <script type="text/javascript">
    //<![CDATA[
    function processForm()
    {
        // confirm_action('Are you sure you wish to update the last billed time to {$enddateorig}');

        var approval = $('approvalpage');
        var invoice = $('invoicepage');

        var enddate = $('enddate').value;

        var toReturn = true;

        if (invoice.checked)
        {
            toReturn = confirm_action(strAreYouSureUpdateLastBilled);
        }

        return toReturn;
    }

    //]]>
    </script>
    <?php

    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' id='billableincidents'>";
    echo "<table class='vertical'>";

    echo "<tr><th>{$strType}:</th><td>";
    echo "<label><input type='radio' name='mode' value='summarypage' id='summarypage' onclick=\"$('startdatesection').hide();" .
            " $('enddatesection').hide(); $('sitebreakdownsection').hide(); $('displaysection').show(); $('showfoc').show(); $('showfocaszero').show(); $('showexpiredaszero').show();\" checked='checked' />{$strSummary}</label> ";

    if (user_permission($sit[2], 73) == TRUE)
    {
        echo "<label><input type='radio' name='mode' value='approvalpage' id='approvalpage' onclick=\"$('startdatesection').show();" .
                " $('enddatesection').show(); $('sitebreakdownsection').hide(); $('displaysection').hide(); $('showfoc').hide(); $('showfocaszero').hide(); $('showexpiredaszero').hide();\" />{$strApprove}</label> ";
    }

    if (user_permission($sit[2], 76) == TRUE)
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

//    echo "<tbody style='display:none' id='showapprovedsection' ><tr><th>Show only awaiting approved:</th>";
//    echo "<td><input type='checkbox' name='showonlyapproved' value='true' checked='checked' />";
//    echo "</td></tr></tbody>\n";

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
    echo "<input type='submit' name='runreport' value='{$strRunReport}' onclick=\"return processForm();\" /></p>";
    echo "</form>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($mode == 'approvalpage')
{
    if (user_permission($sit[2], 73) == FALSE)
    {
        header("Location: {$CONFIG['application_webpath']}noaccess.php?id=73");
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
        echo "<h2>".icon('billing', 32)." {$strBillableIncidents} - {$strApprove}</h2>";

        echo "<p align='center'>{$strThisReportShowsIncidentsClosedInThisPeriod} ";
        echo ldate($CONFIG['dateformat_date'], $startdate)." - ".ldate($CONFIG['dateformat_date'], $enddate)."</p>";
    }

    if (!empty($startdate))
    {
        $sitelistsql .= "AND m.expirydate >= {$startdate} ";
    }

    $resultsite = mysql_query($sitelistsql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    $multipliers = get_all_available_multipliers();

    if (mysql_num_rows($resultsite) > 0)
    {
        while ($objsite = mysql_fetch_object($resultsite))
        {
            unset($billtotalssite);
            unset($billtotalssiteunapproved);
            $sitetotals = 0;
            $sitetotalsbillable = 0;
            $sitetotalrefunds = 0;

            $sitetotalawaitingapproval = 0;
            $sitetotalsawaitingapproval = 0;
            $sitetotalsbillableawaitingapproval = 0;
            $billableunitsincidentunapproved = 0;
            $refundedunapproved = 0;

            $sitename = $objsite->name;

            $sitenamenospaces = preg_replace("/ /", "_", $sitename);

            $str = "<form action='{$_SERVER['PHP_SELF']}?mode=approve&amp;startdate={$startdateorig}&amp;enddate={$enddateorig}' name='{$sitenamenospaces}' id='{$sitenamenospaces}'  method='post'>";

            // $sitename .= "<h3>{$sitename}</h3>";

            $str .= "<table align='center' width='80%'>";

            $str .= "<tr>";
            $str .= "<th><input type='checkbox' name='selectAll' value='CheckAll' onclick=\"checkAll({$sitenamenospaces}, this.checked);\" /></th>";
            $str .= "<th>{$strID}</th><th>{$strIncidentTitle}</th><th>{$strContact}</th>";
            $str .= "<th>{$strEngineer}</th><th>{$strOpened}</th><th>{$strClosed}</th>";

            foreach ($multipliers AS $m)
            {
                $str .= "<th>{$m}&#215;</th>";
            }

            $str .= "<th>{$strTotalUnits}</th><th>{$strTotalBillableUnits}</th>";
            $str .= "<th>{$strCredits}</th>";
            $str .= "<th>{$strBill}</th><th>{$strApprove}</th></tr>\n";

            $used = false;

            /*
            $sql = "SELECT i.* FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbContacts']}` AS c, `{$dbServiceLevels}` AS sl ";
            $sql .= "WHERE c.id = i.contact AND c.siteid = {$objsite->site} ";
            $sql .= "AND sl.tag = i.servicelevel AND sl.priority = i.priority AND sl.timed = 'yes' ";
            $sql .= "AND i.status = 2 "; // Only want closed incidents, dont want awaiting closure as they could be reactivated
            if ($startdate != 0)
            {
                $sql .= "AND closed >= {$startdate} ";
            }

            if ($enddate != 0)
            {
                $sql .= "AND closed <= {$enddate} ";
            }

            $sql .= "ORDER by closed ";
            */
            /*
             * WORKED
             * SELECT * FROM `transactions` t, `links` l, `incidents` i, `contacts` c WHERE t.transactionid = l.origcolref AND t.status = 5 AND linktype= 6 AND l.linkcolref = i.id AND i.contact = c.id
             */

            $sql = "SELECT i.id, i.owner, i.contact, i.title, i.closed, i.opened, t.transactionid FROM `{$GLOBALS['dbTransactions']}` AS t, `{$GLOBALS['dbLinks']}` AS l, `{$GLOBALS['dbIncidents']}` AS i ";
            $sql .= ", `{$GLOBALS['dbContacts']}` AS c WHERE ";
            $sql .= "t.transactionid = l.origcolref AND t.transactionstatus = ".BILLING_AWAITINGAPPROVAL." AND linktype= 6 AND l.linkcolref = i.id AND i.contact = c.id AND c.siteid = {$objsite->site} ";
            if ($startdate != 0)
            {
                $sql .= "AND i.closed >= {$startdate} ";
            }

            if ($enddate != 0)
            {
                $sql .= "AND i.closed <= {$enddate} ";
            }
            $sql .= "ORDER BY i.closed";
            $result = mysql_query($sql);
            if (mysql_error())
            {
                trigger_error(mysql_error(),E_USER_WARNING);
                return FALSE;
            }

            $units = 0;

            if (mysql_num_rows($result) > 0)
            {
                $shade = 'shade1';

                while ($obj = mysql_fetch_object($result))
                {
                    $a = make_incident_billing_array($obj->id);
                    $unapprovable = FALSE;
                    unset($billtotalsincident);

                    if ($a[-1]['totalcustomerperiods'] > 0)
                    {
                        $billableunitsincident = 0;

                        // $isapproved = is_billable_incident_approved($obj->id);
                        $isapproved = false;

                        $unitrate = get_unit_rate(incident_maintid($obj->id));

                        if ($unitrate == -1) $unapprovable = TRUE;

                        $line = "<tr class='{$shade}'><td style='text-align: center'>";

                        if (!$isapproved AND !$unapprovable)
                        {
                            $line .= "<input type='checkbox' name='selected[]' value='{$obj->transactionid}' />";
                        }
                        $line .= "</td>";
                        $line .= "<td>".html_incident_popup_link($obj->id, $obj->id)."</td>";
                        $line .= "<td>{$obj->title}</td><td>".contact_realname($obj->contact)."</td>";
                        $line .= "<td>".user_realname($obj->owner)."</td>";
                        $line .= "<td>".ldate($CONFIG['dateformat_datetime'], $obj->opened)."</td><td>".ldate($CONFIG['dateformat_datetime'], $obj->closed)."</td>";

                        $bills = get_incident_billable_breakdown_array($obj->id);

                        foreach ($bills AS $bill)
                        {
                            foreach ($multipliers AS $m)
                            {
                                if (!empty($bill[$m]))
                                {
                                    $billtotalssite[$m] += $bill[$m]['count'];
                                    $billtotalsincident[$m] += $bill[$m]['count'];

                                    if (!$isapproved)
                                    {
                                        $billtotalssiteunapproved[$m] += $bill[$m]['count'];
                                    }
                                }
                            }
                        }

                        foreach ($multipliers AS $m)
                        {
                            $line .= "<td>";
                            if (!empty($billtotalsincident[$m]))
                            {
                                $line .= $billtotalsincident[$m];

                                $billableunitsincident += $m * $billtotalsincident[$m];

                                if (!$isapproved)
                                {
                                    $billableunitsincidentunapproved += $m * $billtotalsincident[$m];
                                }
                            }
                            else
                            {
                                $line .= "0";
                            }

                            $line .= "</td>";
                        }

                        $actualunits = ($billableunitsincident + $a[-1]['refunds']);

                        $sitetotalrefunds += $a[-1]['refunds'];

                        $cost = $actualunits * $unitrate;

                        $line .= "<td>{$a[-1]['totalcustomerperiods']}</td>";
                        $line .= "<td>{$billableunitsincident}</td>";
                        $line .= "<td>{$a[-1]['refunds']}</td>";
                        $bill = number_format($cost, 2);
                        if ($unapprovable) $bill = "?";
                        $line .= "<td>{$CONFIG['currency_symbol']}{$bill}</td>";

                        $line .= "<td>";
                        // Approval ?

                        if ($isapproved)
                        {
                            $line .= $strApproved;
                        }
                        elseif ($unapprovable)
                        {
                        	$line .= $strUnapprovable;
                        }
                        else
                        {
                            $line .= "<a href='{$_SERVER['PHP_SELF']}?mode=approve&amp;transactionid={$obj->transactionid}&amp;startdate={$startdateorig}&amp;enddate={$enddateorig}&amp;showonlyapproved={$showonlyapproved}'>{$strApprove}</a> | ";
                            $line .= "<a href='billing_update_incident_balance.php?incidentid={$obj->id}'>{$strAdjust}</a>";
                            $sitetotalawaitingapproval += $cost;

                            $sitetotalsawaitingapproval += $a[-1]['totalcustomerperiods'];
                            $sitetotalsbillablewaitingapproval += $billableunitsincident;
                            $refundedunapproved += $a[-1]['refunds'];
                        }

                        $line .= "</td>";

                        $line .= "</tr>\n";

                        $sitetotals += $a[-1]['totalcustomerperiods'];
                        $sitetotalsbillable += $billableunitsincident;

                        if ($shade == "shade1") $shade = "shade2";
                        else $shade = "shade1";

                        $used = true;

                        if (($showonlyapproved AND !$isapproved) OR !$showonlyapproved)
                        {
                            $str .= $line;
                        }
                    }
                }
            }

            $str .= "<tr><td><input type='submit' value='{$strApprove}' />";
            $str .= "</td><td colspan='5'></td>";

            if (!$showonlyapproved)
            {
                $str .= "<td>{$strTOTALS}</td>";

                foreach ($multipliers AS $m)
                {
                    $str .= "<td>";
                    if (!empty($billtotalssite[$m])) $str .= $billtotalssite[$m];
                    else $str .= "0";
                    $str .= "</td>";
                }

                $str .= "<td>{$sitetotals}</td>";
                $str .= "<td>{$sitetotalsbillable}</td>";
                $str .= "<td>{$sitetotalrefunds}</td>";

                $cost = ($sitetotalsbillable + $sitetotalrefunds) * $unitrate;

                $str.= "<td>{$CONFIG['currency_symbol']}".number_format($cost, 2)."</td><td></td>";

                $str .= "</tr>\n";

                $str .= "<tr><td align='right' colspan='6'></td>";
            }

            $str .= "<td>{$strAwaitingApproval}</td>";

            foreach ($multipliers AS $m)
            {
                $str .= "<td>";
                if (!empty($billtotalssiteunapproved[$m]))
                {
                    $str .= $billtotalssiteunapproved[$m];
                }
                else
                {
                    $str .= "0";
                }
                $str .= "</td>";
            }

            $str .= "<td>{$sitetotalsawaitingapproval}</td>";
            $str .= "<td>{$billableunitsincidentunapproved}</td>";
            $str .= "<td>{$refundedunapproved}</td>";


            $str .= "<td>{$CONFIG['currency_symbol']}".number_format($sitetotalawaitingapproval, 2)."</td><td></td></tr>";

            $str .= "</table></form>";

            echo "<h3>{$sitename}</h3>";

            if ($used)
            {
                if ($output == 'html')
                {
                    echo $str;

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
        echo "<p align='center'><a href='{$_SERVER['HTTP_REFERER']}'>{$strReturnToPreviousPage}</a></p>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
}
elseif ($mode == 'invoicepage')
{
    if ($output == 'html')
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        $str .= "<h2>".icon('billing', 32)." {$strBillableIncidentsInvoice}</h2>";

        $resultsite = mysql_query($sitelistsql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        $multipliers = get_all_available_multipliers();

        if (mysql_num_rows($resultsite) > 0)
        {
            while ($objsite = mysql_fetch_object($resultsite))
            {
                unset($servicesapproved);

                $servicestr = '';

                $str .= "<table width='80%' align='center'><tr><th colspan='3'>{$objsite->name}</th></tr>\n";
                $str .= "<tr><th>{$strDate}</th><th>{$strDescription}</th><th>{$strAmount}</th></tr>\n";

                $sql = "SELECT t.* FROM `{$dbTransactions}` AS t, `{$dbService}` AS p, `{$dbMaintenance}` AS m ";
                $sql .= "WHERE t.serviceid = p.serviceid AND p.contractid = m.id AND t.dateupdated <= '{$enddateorig}' ";
                $sql .= "AND t.dateupdated > p.lastbilled AND m.site = {$objsite->site} ";

                $result = mysql_query($sql);
                if (mysql_error())
                {
                    trigger_error(mysql_error(), E_USER_WARNING);
                    return FALSE;
                }

                if (mysql_num_rows($result) > 0)
                {
                    $shade = 'shade1';

                    while ($obj = mysql_fetch_object($result))
                    {
                        $str .= "<tr class='{$shade}'>";
                        $str .= "<td>{$obj->date}</td>";
                        $str .= "<td>{$obj->description}</td>";
                        $str .= "<td>".number_format($obj->amount, 2)."</td>";
                        $str .= "</tr>\n";

                        if ($shade == "shade1") $shade = "shade2";
                        else $shade = "shade1";

                        if (empty($servicesapproved[$obj->serviceid]))
                        {
                            $servicesapproved[$obj->serviceid] = $obj->serviceid;
                            update_last_billed_time($obj->serviceid, $enddateorig);

                            $servicestr .= "<p align='center'>".sprintf($strServiceIDXLastInvoiceUptoX, $obj->serviceid, $enddateorig)."</p>";
                        }
                    }
                }
                else
                {
                    $str .= "<tr><td colspan='3' align='center'>{$strNone}</td></tr>\n";
                }

                $str .= "</table>";
                $str .= $servicestr;
            }
        }
    }


    if ($output == 'html')
    {
        echo $str;
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
}
elseif ($mode == 'summarypage')
{
    include (APPLICATION_INCPATH . 'billing_summary.inc.php');
}
elseif ($mode == 'transactions')
{
    if (user_permission($sit[2], 76) == FALSE)
    {
        header("Location: {$CONFIG['application_webpath']}noaccess.php?id=76");
        exit;
    }

    include ('transactions.php');
}
elseif ($mode == 'approve')
{
    if (user_permission($sit[2], 73) == FALSE)
    {
        header("Location: {$CONFIG['application_webpath']}noaccess.php?id=73");
        exit;
    }

    $transactionid = clean_int($_REQUEST['transactionid']);
    $selected = cleanvar($_POST['selected']);

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
            $p = get_service_percentage($maintid);
            if (p == FALSE) $percent = true;
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