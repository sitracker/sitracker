<?php
// incident_details.php - Show incident details
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

$permission = 61; // View Incident Details
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

require_once (APPLICATION_LIBPATH . 'billing.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$incidentid = clean_int($_REQUEST['id']);
$id = $incidentid;

if ($_REQUEST['win'] == 'incomingview')
{
    $title = $strIncoming;
    $incidentid = '';
    include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
    include (APPLICATION_INCPATH . 'incident_incoming.inc.php');
    exit;
}
elseif ($_REQUEST['win'] == 'jump')
{
    if (incident_owner($incidentid) > 0)
    {
        echo "<html><head>";
        echo "<script src='{$CONFIG['application_webpath']}scripts/prototype/prototype.js' type='text/javascript'></script>\n";
        echo "<script src='{$CONFIG['application_webpath']}scripts/webtrack.js' type='text/javascript'></script>\n";
        if (!empty($_GET['return']))
        {
            $return = cleanvar($_GET['return']);
            echo "</head><body onload=\"\"><a href=\"$return\">{$strPleaseWaitRedirect}</a>";
            echo "<script type='text/javascript'>\n//<![CDATA[\n";
            echo "var popwin = incident_details_window($incidentid,'win', true);\n";
            echo "if (!popwin) alert('{$strDidYourBrowserBlockPopupWindow}');\n";
            echo "else window.location='{$return}';\n";
            echo "\n//]]>\n</script>\n";
            echo "</body></html>";
        }
        else
        {
            echo "</head><body onload=\"\"><a href=\"{$_SERVER['HTTP_REFERER']}\"{$strPleaseWaitRedirect}</a>";
            echo "<script type='text/javascript'>\n//<![CDATA[\n";
            echo "var popwin = incident_details_window($incidentid,'win', true);\n";
            echo "if (!popwin) alert('{$strDidYourBrowserBlockPopupWindow}');\n";
            echo "else history.go(-1);\n";
            echo "\n//]]>\n</script>\n";
            echo "</body></html>";
        }
    }
    else
    {
        // return without loading popup
        echo "<html><head>";
        echo "<script src='{$CONFIG['application_webpath']}scripts/prototype/prototype.js' type='text/javascript'></script>\n";
        echo "<script src='{$CONFIG['application_webpath']}scripts/webtrack.js' type='text/javascript'></script>\n";
        if (!empty($_GET['return']))
        {
            $return = cleanvar($_GET['return']);
            echo "</head><body onload=\"incident_details_window($incidentid,'win');window.location='{$return}';\"></body></html>";
        }
        else
        {
            echo "</head><body onload=\"incident_details_window($incidentid,'win');window.location='{$_SERVER['HTTP_REFERER']}';\"></body></html>";
        }
    }
    exit;
}
elseif ($_REQUEST['win'] == 'holdingview')
{
    $_REQUEST['win'] = 'incomingview';
    $title = $strIncoming;
    $incidentid = '';
}
else
{
    $title = $strDetails;
}

// Check for asked incident ID
$sql = "SELECT id FROM {$dbIncidents} ";
$sql .= "WHERE id = {$id} ";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
if (mysql_num_rows($result) == 0)
{
    // Incident doesn't exist
    // FIXME better error message - CJ 21/3-10
    html_redirect("main.php", FALSE, $strNotApplicableAbbrev);

}
else
{
    include (APPLICATION_INCPATH . 'incident_html_top.inc.php');

    echo "<div id='detailsummary'>";

    // Two column table FIXME can be divs
    echo "<table>";
    echo "<tr><td>";
    // First column: Contact Details
    $contact = "<a href='contact_details.php?id={$incident->contactid}' title=\"{$strContact}\" target='top.opener' class='info'>{$incident->forenames} {$incident->surname}";
    if (!empty($contact_notes)) $contact .= "<span>{$contact_notes}</span>";
    $contact .= "</a> ";
    $site = "<a href='site_details.php?id={$incident->siteid}' title='{$strSite}' target='top.opener' class='info'>{$site_name}";
    if (!empty($site_notes)) $site .= "<span>{$site_notes}</span>";
    $site .= "</a> ";
    $site .= list_tag_icons($incident->siteid, TAG_SITE); // site tag icons
    $site .= "<br />\n";
    echo sprintf($strContactofSite, $contact, $site)." ";
    echo "<a href=\"mailto:{$incident->email}\">{$incident->email}</a><br />\n";
    if ($incident->ccemail != '') echo "CC: <a href=\"mailto:{$incident->ccemail}\">{$incident->ccemail}</a><br />\n";
    if ($incident->phone != '' OR $incident->mobile != '')
    {
        if ($incident->phone != '')
        {
            echo "{$strTel}: {$incident->phone} ";
            plugin_do('incident_details_phone');
        }
        if ($incident->mobile != '')
        {
            echo " {$strMob}: {$incident->mobile} ";
            plugin_do('incident_details_mobile');
        }
        echo "<br />\n";
    }
    else
    {
        $sitetelephone = site_telephone($incident->siteid);
        if (!empty($sitetelephone))
        {
            echo "{$strTel} ({$strSite}): {$sitetelephone} ";
            plugin_do('incident_details_phone');
            echo "<br />\n";
        }
    }
    if ($incident->externalid != '' OR $incident->escalationpath > 0)
    {
        echo "{$strEscalated}: ";
        echo format_external_id($incident->externalid,$incident->escalationpath)."<br />\n";
    }
    if ($incident->externalengineer != '')
    {
        echo $incident->externalengineer;
        if ($incident->externalemail != '') echo ", <a href=\"mailto:{$incident->externalemail}\">{$incident->externalemail}</a>";
        echo "<br />\n";
    }

    $sql = "SELECT * FROM {$dbLinks} AS l, {$dbInventory} AS i ";
    $sql .= "WHERE linktype = 7 ";
    $sql .= "AND origcolref = {$incidentid} ";
    $sql .= "AND i.id = linkcolref ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        $inventory = mysql_fetch_object($result);
        echo "<a href='inventory_view.php?id={$inventory->id}'>";
        echo "$inventory->name";
        if (!empty($inventory->identifier))
        {
            echo " ({$inventory->identifier})";
        }
        elseif (!empty($inventory->address))
        {
            echo " ({$inventory->address})";
        }
    }

    $tags = list_tags($id, TAG_INCIDENT, TRUE);
    if (!empty($tags)) echo "{$tags}\n";
    echo "</td>";

    echo "<td>";
    // Second column, Product and Incident details
    if ($incident->owner != $sit[2] OR ($incident->towner > 0 AND $incident->towner != $incident->owner))
    {
        echo "{$strOwner}: <strong>".user_realname($incident->owner,TRUE)."</strong> ";
        $incidentowner_phone = user_phone($incident->owner);
        if ($incidentowner_phone != '') echo "({$strTel}: {$incidentowner_phone}) ";
        if ($incident->towner > 0 AND $incident->towner != $incident->owner)
        {
           echo "({$strTemp}: ".user_realname($incident->towner,TRUE).")";
        }
        echo "<br />";
    }
    if ($software_name != '' OR $incident->productversion != '' OR $incident->productservicepacks != '')
    {
        echo $software_name;
        if ($incident->productversion != '' OR $incident->productservicepacks != '')
        {
            echo " (".$incident->productversion;
            if ($incident->productservicepacks!='') echo $incident->productservicepacks;
            echo ")";
        }
        echo "<br />\n";
    }
    echo priority_icon($incident->priority)." ".priority_name($incident->priority);
    if ($product_name != '')
    {
        echo " <a href='contract_details.php?id={$incident->maintenanceid}' title='{$strContractDetails}' target='top.opener'>";
        echo "{$product_name}";
        echo "</a>";
    }
    elseif ($incident->maintenanceid > 0)
    {
        echo "<a href='contract_details.php?id={$incident->maintenanceid}' title='{$strContractDetails}' target='top.opener'>";
        echo "{$strContract} {$incident->maintenanceid}";
        echo "</a>";
    }
    else echo " <strong>{$strSiteSupport}</strong>";
    echo " / ";

    echo "{$servicelevel_tag}<br />\n ";

    switch (does_contact_have_billable_contract($incident->contactid))
    {
        case CONTACT_HAS_BILLABLE_CONTRACT:
            echo "{$strContactHasBillableContract} (&cong;".contract_unit_balance(get_billable_contract_id($incident->contactid))." units)<br />";
            break;
        case SITE_HAS_BILLABLE_CONTRACT:
            echo "{$strSiteHasBillableContract} (&cong;".contract_unit_balance(get_billable_contract_id($incident->contactid))." units)<br />";
            break;
    }

    $num_open_activities = open_activities_for_incident($incidentid);
    if (count($num_open_activities) > 0)
    {
        echo "<a href='tasks.php?incident={$incidentid}' class='info'>";
        echo icon('timer', 16, $strOpenActivities);
        echo "</a> ";
    }

    if (drafts_waiting_on_incident($incidentid, 'email'))
    {
        echo "<a href='javascript:email_window($incidentid)' class='info'>";
        echo icon('email', 16, $strDraftsEmailExist);
        echo "</a> ";
    }

    if (drafts_waiting_on_incident($incidentid, 'update'))
    {
        echo "<a href='incident_update.php?id={$incidentid}&amp;popup=' class='info'>";
        echo icon('note', 16, $strDraftsUpdateExist);
        echo "</a> ";
    }

    // Product Info
    if (!empty($incident->product))
    {
        $pisql = "SELECT pi.information AS label, ipi.information AS information ";
        $pisql .= "FROM `{$dbIncidentProductInfo}` AS ipi, `{$dbProductInfo}` AS pi ";
        $pisql .= "WHERE pi.id = ipi.productinfoid AND ipi.incidentid = {$incidentid}";
        $piresult = mysql_query($pisql);
        if (mysql_num_rows($piresult) > 0)
        {
            while ($pi = mysql_fetch_object($piresult))
            {
                echo "{$pi->label}: {$pi->information} <br />\n";
            }
        }
    }

    echo sprintf($strOpenForX, $opened_for)." - ";
    echo incidentstatus_name($incident->status);
    if ($incident->status == STATUS_CLOSED) echo " (" . closingstatus_name($incident->closingstatus) . ")";
    echo "<br />\n";

    // Total billable duration
    $upsql = "SELECT incidentid, duration ";
    $upsql .= "FROM `{$dbUpdates}`";
    $upsql .= "WHERE incidentid = {$incidentid}";
    $upresult = mysql_query($upsql);
    if (mysql_num_rows($upresult) > 0)
    {
        while ($du = mysql_fetch_object($upresult))
        {
            $totalduration = $totalduration + $du->duration;
        }
        // FIXME for 12/24H clock choice
        if ($totalduration > 0)
        {
            echo ("{$strDuration}: " . format_seconds($totalduration*60) . "<br />\n");
        }
    }

    // Show sla target/review target if incident is still open
    if ($incident->status != STATUS_CLOSED AND $incident->status != STATUS_CLOSING)
    {
        if ($targettype != '')
        {
            if ($slaremain > 0)
            {
                echo sprintf($strSLAInX, $targettype, format_workday_minutes($slaremain));
            }
            elseif ($slaremain < 0)
            {
                echo " ".sprintf($strSLAXLate, $targettype, format_workday_minutes((0-$slaremain)));
            }
            else
            {
                echo " ".sprintf($strSLAXDueNow , $targettype);
            }
        }

        if ($reviewremain <= 0)
        {
            if ($reviewremain > -86400)
            {
                echo "<br />".icon('review', 16)." ".sprintf($strReviewDueAgo ,format_seconds(($reviewremain*-1) * 60));
            }
            else
            {
                echo "<br />".icon('review', 16)." {$strReviewDueNow}";
            }
        }
        if ($servicelevel->timed == 'yes')
        {
            echo "<br />";
            switch (count($num_open_activities))
            {
                case 0: //start
                    echo "<a href='task_new.php?incident={$id}'>{$strStartNewActivity}</a>";
                    break;
                case 1: //stop
                    echo "<a href='view_task.php?id={$num_open_activities[0]}&amp;mode=incident&amp;incident={$id}'>{$strViewActivity}</a> | ";
                    $sql = "SELECT * FROM `{$dbNotes}` WHERE link='10' AND refid='{$num_open_activities[0]}'";
                    $result = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    if (mysql_num_rows($result) >= 1)
                    {
                        echo "<a href='task_edit.php?id={$num_open_activities[0]}&amp;action=markcomplete&amp;incident={$id}'>{$strStopActivity}</a>";
                    }
                    else
                    {
                        // Notes needed before closure
                        echo $strActivityContainsNoNotes;
                    }
                    break;
                default:  //greyed out
                    echo "<a href='tasks.php?incident={$id}'>{$strMultipleActivitiesRunning}</a>";
            }
        }
    }

    echo "</td>";
    echo "</tr>\n";

    // Incident relationships
    $rsql = "SELECT * FROM `{$dbRelatedIncidents}` WHERE incidentid='{$id}' OR relatedid='{$id}'";
    $rresult = mysql_query($rsql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($rresult) >= 1)
    {
        echo "<tr><td colspan='2'>{$strRelations}: ";
        while ($related = mysql_fetch_object($rresult))
        {
            if ($related->relatedid == $id)
            {
                if ($related->relation == 'child') $linktitle = 'Child';
                else $linktitle = 'Sibling';
                $linktitle .= ": ".incident_title($related->incidentid);
                echo "<a href='incident_details.php?id={$related->incidentid}' title='{$linktitle}'>{$related->incidentid}</a> ";
            }
            else
            {
                if ($related->relation == 'child') $linktitle = 'Parent';
                else $linktitle = 'Sibling';
                $linktitle .= ": ".incident_title($related->relatedid);
                echo "<a href='incident_details.php?id={$related->relatedid}' title='{$linktitle}'>{$related->relatedid}</a> ";
            }
            echo " &nbsp;";
        }
        echo "</td></tr>";

    }

    echo "</table>";

    plugin_do('incident_details');

    echo "</div>\n\n";


    $offset = clean_int($_REQUEST['offset']);
    if (empty($offset))
    {
        $offset = 0;
    }

    /**
     * @author Ivan Lucas
     */
    function count_updates($incidentid)
    {
        $count_updates = 0;
        $sql = "SELECT COUNT(id) FROM `{$GLOBALS['dbUpdates']}` WHERE incidentid='{$incidentid}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        list ($count_updates) = mysql_fetch_row($result);

        return $count_updates;
    }

    $count_updates = count_updates($incidentid);


    /**
     * @author Paul Heaney
     */
    function log_nav_bar()
    {
        global $incidentid;
        global $firstid;
        global $updateid;
        global $offset;
        global $count_updates;
        global $records;

        $updates_per_page = intval($_SESSION['userconfig']['updates_per_page']);
        if ($offset > $updates_per_page)
        {
            $previous = $offset - $updates_per_page;
        }
        else
        {
            $previous = 0;
        }
        $next = $offset + $updates_per_page;

        $nav .= "<table width='98%' align='center'><tr>";
        $nav .= "<td align='left' style='width: 33%;'>";
        if ($offset > 0)
        {
            $nav .= "<a href='{$_SERVER['PHP_SELF']}?id={$incidentid}&amp;";
            $nav .= "javascript=enabled&amp;offset={$previous}'>&lt;&lt; ";
            $nav .= "{$GLOBALS['strPrevious']}</a>";
        }
        $nav .= "</td>";
        $nav .= "<td align='center' style='width: 34%;'>";
        if ($count_updates > $updates_per_page)
        {
            if ($records != 'all')
            {
                $nav .= "<a href='{$_SERVER['PHP_SELF']}?id={$incidentid}&amp;";
                $nav .= "javascript=enabled&amp;offset=0&amp;records=all'>";
                $nav .= "{$GLOBALS['strShowAll']}</a>";
            }
            else if ($updates_per_page != 0)
            {
                $nav .= "<a href='{$_SERVER['PHP_SELF']}?id={$incidentid}&amp;";
                $nav .= "javascript=enabled&amp;offset=0'>{$GLOBALS['strShowPaged']}</a>";
            }
        }
        $nav .= "</td>";
        $nav .= "<td align='right' style='width: 33%;'>";
        if ($offset < ($count_updates - $updates_per_page) AND
            $records != 'all')
        {
            $nav .= "<a href='{$_SERVER['PHP_SELF']}?id={$incidentid}&amp;";
            $nav .= "javascript=enabled&amp;offset={$next}'>";
            $nav .= "{$GLOBALS['strNext']} &gt;&gt;</a>";
        }
        $nav .= "</td>";
        $nav .= "</tr></table>\n";

        return $nav;
    }

    $records = strtolower(cleanvar($_REQUEST['records']));

    if (intval($_SESSION['userconfig']['updates_per_page']) == 0)
    {
        $records = 'all';
    }

    if ($incidentid == '' OR $incidentid < 1)
    {
        trigger_error("Incident ID cannot be zero or blank", E_USER_ERROR);
    }

    $sql  = "SELECT * FROM `{$dbUpdates}` WHERE incidentid='{$incidentid}' ";
    $sql .= "ORDER BY timestamp {$_SESSION['userconfig']['incident_log_order']}, id {$_SESSION['userconfig']['incident_log_order']} ";

    if (empty($records))
    {
        $numupdates = intval($_SESSION['userconfig']['updates_per_page']);
        if ($numupdates != 0)
        {
            $sql .= "LIMIT {$offset},{$numupdates}";
        }
    }
    elseif (is_numeric($records))
    {
        $sql .= "LIMIT {$offset},{$records}";
    }

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error $sql".mysql_error(), E_USER_WARNING);

    $keeptags = array('b','i','u','hr','&lt;', '&gt;');
    foreach ($keeptags AS $keeptag)
    {
        if (substr($keeptag,0,1) == '&')
        {
            $origtag[] = "$keeptag";
            $temptag[] = "[[".substr($keeptag, 1, strlen($keeptag)-1)."]]";
            $origtag[] = strtoupper("$keeptag");
            $temptag[] = "[[".strtoupper(substr($keeptag, 1, strlen($keeptag)-1))."]]";
        }
        else
        {
            $origtag[] = "<{$keeptag}>";
            $origtag[] = "</{$keeptag}>";
            $origtag[] = "<'.strtoupper($keeptag).'>";
            $origtag[] = "</'.strtoupper($keeptag).'>";
            $temptag[] = "[[{$keeptag}]]";
            $temptag[] = "[[/{$keeptag}]]";
            $temptag[] = "[['.strtoupper($keeptag).']]";
            $temptag[] = "[[/'.strtoupper($keeptag).']]";
        }
    }

    echo log_nav_bar();
    $count = 0;
    $billable_incident_approved = is_billable_incident_approved($incidentid);

    // An idea for a 'common actions' menu, see Mantis 461, commented out for 3.50 release
    // if (!$_GET['win'])
    // {
    //     echo "<div id='log_container'>";
    //     echo "<div id='portalleft'>";
    //     echo "<h3>Quick Actions</h3>";
    //     echo "<u>Status</u><br />";
    //     echo "<a>Awaiting customer</a><br />";
    //     echo "<a>Active</a>";
    //     echo "</p><p>";
    //     echo "<u>Ownership</u><br />";
    //     echo "<a>Assign to me</a><br />";
    //     echo "<a>Temp assign to me</a><br />";
    //     echo "</p>";
    //     echo "<p><u>Quick update</u>";
    //     echo "<textarea></textarea><br /><input type='submit' value='Update'>";
    //     echo "</p></div>";
    //     echo "<div id='portalright'>";
    // }

    // Style quoted text
    $quote[0] = "/^(&gt;([\s][\d\w]).*)[\n\r]$/m";
    $quote[1] = "/^(&gt;&gt;([\s][\d\w]).*)[\n\r]$/m";
    $quote[2] = "/^(&gt;&gt;&gt;+([\s][\d\w]).*)[\n\r]$/m";
    $quote[3] = "/^(&gt;&gt;&gt;(&gt;)+([\s][\d\w]).*)[\n\r]$/m";
    $quote[4] = "/(-----\s?Original Message\s?-----.*-{3,})/s";
    $quote[5] = "/(-----BEGIN PGP SIGNED MESSAGE-----)/s";
    $quote[6] = "/(-----BEGIN PGP SIGNATURE-----.*-----END PGP SIGNATURE-----)/s";
    $quote[7] = "/^(&gt;)[\r]*$/m";
    $quote[8] = "/^(&gt;&gt;)[\r]*$/m";
    $quote[9] = "/^(&gt;&gt;(&gt;){1,8})[\r]*$/m";

    $quotereplace[0] = "<span class='quote1'>\\1</span>";
    $quotereplace[1] = "<span class='quote2'>\\1</span>";
    $quotereplace[2] = "<span class='quote3'>\\1</span>";
    $quotereplace[3] = "<span class='quote4'>\\1</span>";
    $quotereplace[4] = "<span class='quoteirrel'>\\1</span>";
    $quotereplace[5] = "<span class='quoteirrel'>\\1</span>";
    $quotereplace[6] = "<span class='quoteirrel'>\\1</span>";
    $quotereplace[7] = "<span class='quote1'>\\1</span>";
    $quotereplace[8] = "<span class='quote2'>\\1</span>";
    $quotereplace[9] = "<span class='quote3'>\\1</span>";

    while ($update = mysql_fetch_object($result))
    {
        if (empty($firstid))
        {
            $firstid = $update->id;
        }

        $updateid = $update->id;
        $updatebody = trim($update->bodytext);
        $updatebodylen = strlen($updatebody);
        $updatebody = str_replace($origtag, $temptag, $updatebody);
        $updatebody = str_replace($temptag, $origtag, $updatebody);

        // Put the header part (up to the <hr /> in a seperate DIV)
        if (strpos($updatebody, '<hr>') !== FALSE)
        {
            $updatebody = "<div class='iheader'>".str_replace('<hr>', "</div>", $updatebody);
        }

        // Lookup some extra data
        $updateuser = user_realname($update->userid,TRUE);
        $updatetime = readable_date($update->timestamp);
        $currentowner = user_realname($update->currentowner,TRUE);
        $currentstatus = incident_status($update->currentstatus);

        $updateheadertext = $updatetypes[$update->type]['text'];
        if ($currentowner != $updateuser)
        {
            $updateheadertext = str_replace('currentowner', $currentowner, $updateheadertext);
        }
        else
        {
            $updateheadertext = str_replace('currentowner', $strSelf, $updateheadertext);
        }

        $updateheadertext = str_replace('updateuser', $updateuser, $updateheadertext);
        $updateheadertext = str_replace('updateuser', $updateuser, $updateheadertext);

        if ($update->type == 'reviewmet' AND
            ($update->sla == 'opened' OR $update->userid == 0))
        {
            $updateheadertext = str_replace('updatereview', $strPeriodStarted, $updateheadertext);
        }
        elseif ($update->type == 'reviewmet' AND $update->sla == '')
        {
            $updateheadertext = str_replace('updatereview', $strCompleted, $updateheadertext);
        }

        if ($update->type == 'slamet')
        {
            $updateheadertext = str_replace('updatesla', $slatypes[$update->sla]['text'], $updateheadertext);
        }

        echo "<a name='update{$count}'></a>";

        // Print a header row for the update
        if ($updatebody == '' AND $update->customervisibility == 'show')
        {
            echo "<div class='detailinfo'>";
        }
        elseif ($updatebody == '' AND $update->customervisibility != 'show')
        {
            echo "<div class='detailinfohidden'>";
        }
        elseif ($updatebody != '' AND $update->customervisibility == 'show')
        {
            echo "<div class='detailhead'>";
        }
        else
        {
            echo "<div class='detailheadhidden'>";
        }

        if ($offset > $updates_per_page)
        {
            $previous = $offset - intval($_SESSION['userconfig']['updates_per_page']);
        }
        else
        {
            $previous = 0;
        }
        $next = $offset + intval($_SESSION['userconfig']['updates_per_page']);

        echo "<div class='detaildate'>";
        if ($count == 0)
        {
            // Put the header part (up to the <hr /> in a seperate DIV)
            if (strpos($updatebody, '<hr>') !== FALSE)
            {
                echo "<a href='{$_SERVER['PHP_SELF']}?id={$incidentid}&amp;";
                echo "javascript=enabled&amp;offset={$previous}&amp;direction=";
                echo "previous' class='info'>";
                echo icon('navup', 16, $strPreviousUpdate)."</a>";
            }
        }
        else
        {
            echo "<a href='#update".($count-1)."' class='info'>";
            echo icon('navup', 16, $strPreviousUpdate)."</a>";
        }

        $updatebody = preg_replace($quote, $quotereplace, $updatebody);

        // Make URL's into Hyperlinks
        /* This breaks BBCode by replacing URls in a tags PH 19/10/2008
        $search = array("/(?<!quot;|[=\"]|:[\\n]\/{2})\b((\w+:\/{2}|www\.).+?)"."(?=\W*([<>\s]|$))/i");
        $replace = array("<a href=\"\\1\">\\1</a>");
        $updatebody = preg_replace ($search, $replace, $updatebody);
        */

        // [begin] Insert link for old-style attachments [[att]]file.ext[[/att]] format
        // This code is required to support attachments written prior to v3.35
        // Please don't remove without a plan for what to do about old-style
        // attachments.  INL 14Dec08
        if (file_exists("{$CONFIG['attachment_fspath']}{$update->incidentid}/{$update->timestamp}"))
        {
            $attachment_webpath = "{$CONFIG['attachment_webpath']}{$update->incidentid}/{$update->timestamp}";
        }
        else
        {
            $attachment_webpath = "{$CONFIG['attachment_webpath']}updates/{$update->id}";
        }
        $updatebody = preg_replace("/\[\[att\]\](.*?)\[\[\/att\]\]/", "<a href = '{$attachment_webpath}/$1'>$1</a>", $updatebody);
        // [end] Insert link for old-style attachments [[att]]file.ext[[/att]] format

        $updatebody = preg_replace("/href=\"(?!http[s]?:\/\/)/", "href=\"http://", $updatebody);
        $updatebody = bbcode($updatebody);
        $updatebody = preg_replace("!([\n\t ]+)(http[s]?:/{2}[\w\.]{2,}[/\w\-\.\?\&\=\#\$\%|;|\[|\]~:]*)!e", "'\\1<a href=\"\\2\" title=\"\\2\">'.(strlen('\\2')>=70 ? substr('\\2',0,70).'...':'\\2').'</a>'", $updatebody);

        // Make KB article references into a hyperlink
        $updatebody = preg_replace("/\b{$CONFIG['kb_id_prefix']}([0-9]{3,4})\b/", "<a href=\"kb_view_article.php?id=$1\" title=\"View KB Article $1\">$0</a>", $updatebody);

        if ($currentowner != $updateuser)
        {
            echo "<a href='{$_SERVER['PHP_SELF']}?id={$incidentid}&amp;";
            echo "javascript=enabled&amp;offset={$next}&amp;direction=next' ";
            echo "class='info'>";
            echo icon('navdown', 16, $strNextUpdate)."</a>";
        }
        else
        {
            echo "<a href='#update".($count+1)."' class='info'>";
            echo icon('navdown', 16, $strNextUpdate)."</a>";
        }
        echo "</div>";

        // Specific header
        echo "<div class='detaildate'>{$updatetime}</div>";

        if ($update->customervisibility == 'show')
        {
            $newmode = 'hide';
        }
        else
        {
            $newmode = 'show';
        }

        echo "<a href='incident_showhide_update.php?mode={$newmode}&amp;";
        echo "incidentid={$incidentid}&amp;updateid={$update->id}&amp;view";
        echo "={$view}&amp;expand={$expand}";

        if ($records == 'all')
        {
            echo "&amp;offset=0&amp;records=all";
        }
        else
        {
            echo "&amp;offset={$offset}";
        }

        echo "' name='{$update->id}' class='info'>";

        if (array_key_exists($update->type, $updatetypes))
        {
            if ($update->customervisibility == 'show')
            {
                $showhide = $strHideInPortal;
            }
            else
            {
                $showhide = $strMakeVisibleInPortal;
            }

            if (!empty($update->sla) AND $update->type == 'slamet')
            {
                echo icon($slatypes[$update->sla]['icon'], 16, $showhide);
            }
            echo icon($updatetypes[$update->type]['icon'], 16, $showhide);

            echo "</a> {$updateheadertext}";
        }
        else
        {
            echo icon($updatetypes['research']['icon'], 16, $strResearch);
            if ($update->customervisibility == 'show')
            {
                echo "<span>{$strHideInPortal}</span>";
            }
            else
            {
                echo "<span>{$strMakeVisibleInPortal}</span>";
            }

            if ($update->sla != '')
            {
                echo icon($slatypes[$update->sla]['icon'], 16, $update->type);
            }
            echo "</a>" . sprintf($strUpdatedXbyX, "(".$update->type.")", $updateuser);
        }

        echo "</div>\n";
        if (!empty($updatebody))
        {
            if ($update->customervisibility == 'show')
            {
                echo "<div class='detailentry'>\n";
            }
            else
            {
                echo "<div class='detailentryhidden'>\n";
            }

            if ($updatebodylen > 5)
            {
                /*
                 * @modifier: Rick Bonkestoter
                 * @desc: some webmail systems use the wrong encodeing (\r\n) instead of (\n\r)
                 */
                echo str_replace('\r\n', "<br />", nl2br($updatebody));
            }
            else
            {
                /*
                 * @modifier: Rick Bonkestoter
                 * @desc: some webmail systems use the wrong encodeing (\r\n) instead of (\n\r)
                 */
                echo str_replace('\r\n', "<br />", nl2br($updatebody));
            }

            if (!empty($update->nextaction) OR $update->duration != 0)
            {
                echo "<div class='detailhead'>";

                if ($update->duration != 0)
                {
                    $inminutes = ceil($update->duration); // Always round up
                    echo  "{$strDuration}: {$inminutes} {$strMinutes}";

                    // Permision to adjust durations is 81
                    if ($CONFIG['allow_duration_adjustment'] AND user_permission($sit[2], 81) AND !$billable_incident_approved)
                    {
                        echo " <a href='billing_edit_activity_duration.php?mode=showform&amp;incidentid={$incidentid}&amp;updateid={$update->id}'>{$strEdit}</a>";
                    }

                    echo "<br />";
                }

                if (!empty($update->nextaction))
                {
                    echo "{$strNextAction}: {$update->nextaction}";
                }

                echo "</div>";
            }
            echo "</div>";
        }

        $count++;
    }

    if (intval($_SESSION['userconfig']['updates_per_page']) > 0)
    {
        echo log_nav_bar();
    }
    if (!$_GET['win'])
    {
        echo "</div>";
    }

    include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
}
?>