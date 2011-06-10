<?php
// incidents_table.inc.php - Prints out a table of incidents based on the query that was executed in the page that included this file
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

if ($CONFIG['debug']) echo "<!-- Support Incidents Table -->";

echo "<table align='center' style='width:95%;'>";
echo "<col width='7%'></col>";
echo "<col width='22%'></col>";
echo "<col width='17%'></col>";
echo "<col width='6%'></col>";
echo "<col width='7%'></col>";
echo "<col width='15%'></col>";
echo "<col width='17%'></col>";
echo "<col width='10%'></col>";
echo "<col width='8%'></col>";
echo "<tr>";

$filter = array('queue' => $queue,
                'user' => $user,
                'type' => $type);
echo colheader('id',$strID,$sort, $order, $filter);
echo colheader('title',$strTitle,$sort, $order, $filter);
echo colheader('contact',$strContact,$sort, $order, $filter);
echo colheader('priority',$strPriority,$sort, $order, $filter);
echo colheader('status',$strStatus,$sort, $order, $filter);
echo colheader('lastupdated',$strLastUpdated,$sort, $order, $filter);
echo colheader('nextaction',$strSLATarget,$sort, $order, $filter);
echo colheader('info', $strInfo, $sort, $order, $filter);
echo "</tr>";
// Display the Support Incidents Themselves
$shade = 0;

$number_of_slas = number_of_slas();

while ($incidents = mysql_fetch_object($result))
{
    // calculate time to next action string
    if ($incidents->timeofnextaction == 0)
    {
        $timetonextaction_string = "&nbsp;";  // was 'no time set'
    }
    else
    {
        if (($incidents->timeofnextaction - $now) > 0)
        {
            $timetonextaction_string = format_seconds($incidents->timeofnextaction - $now);
        }
        else
        {
            $timetonextaction_string = "<strong>{$strNow}</strong>";
        }
    }

    // Used to store the ellipsis if shortened, we do htmlspecialchars on $site and don't want this to be converted
    // If you do &hellips; becomes &amp;hellips;
    $postsitetext = '';

    if (mb_strlen($incidents->site) > 30)
    {
        $incidents->site = mb_substr($incidents->site, 0, 30, 'UTF-8');
        $postsitetext .= $strEllipsis;
    }


    // Make a readble last updated field
    if ($incidents->lastupdated > $now - 300)
    {
        $when = sprintf($strAgo, format_seconds($now - $incidents->lastupdated));
        if ($when == 0) $when = $strJustNow;
        $updated = "<em class='updatednow'>{$when}</em>";
    }
    elseif ($incidents->lastupdated > $now - 1800)
    {
        $updated = "<em class='updatedveryrecently'>".sprintf($strAgo, format_seconds($now - $incidents->lastupdated))."</em>";
    }
    elseif ($incidents->lastupdated > $now - 3600)
    {
        $updated = "<em class='updatedrecently'>".sprintf($strAgo, format_seconds($now - $incidents->lastupdated))."</em>";
    }
    elseif (date('dmy', $incidents->lastupdated) == date('dmy', $now))
    {
        $updated = "{$strToday} @ ".ldate($CONFIG['dateformat_time'], $incidents->lastupdated);
    }
    elseif (date('dmy', $incidents->lastupdated) == date('dmy', ($now - 86400)))
    {
        $updated = "{$strYesterday} @ ".ldate($CONFIG['dateformat_time'], $incidents->lastupdated);
    }
    elseif ($incidents->lastupdated < $now - 86400 AND
            $incidents->lastupdated > $now - (86400 * 6))
    {
        $updated = ldate('l', $incidents->lastupdated)." @ ".ldate($CONFIG['dateformat_time'], $incidents->lastupdated);
    }
    else
    {
        $updated = ldate($CONFIG['dateformat_datetime'], $incidents->lastupdated);
    }

    // Fudge for old ones
    $tag = $incidents->servicelevel;
    if ($tag == '') $tag = maintenance_servicelevel_tag($incidents->maintenanceid);

    $slsql = "SELECT * FROM `{$dbServiceLevels}` WHERE tag='{$tag}' AND priority='{$incidents->priority}' ";
    $slresult = mysql_query($slsql);
    if (mysql_error()) trigger_error("mysql query error ".mysql_error(), E_USER_WARNING);
    $servicelevel = mysql_fetch_object($slresult);
    if (mysql_num_rows($slresult) < 1) trigger_error("could not retrieve service level ({$slsql})", E_USER_WARNING);

    // Get Last Update
    list($update_userid, $update_type, $update_currentowner, $update_currentstatus, $update_body, $update_timestamp, $update_nextaction, $update_id) = incident_lastupdate($incidents->id);

    // Get next target
    $target = incident_get_next_target($incidents->id);
    $working_day_mins = ($CONFIG['end_working_day'] - $CONFIG['start_working_day']) / 60;
    // Calculate time remaining in SLA
    switch ($target->type)
    {
        case 'initialresponse':
            $slatarget = $servicelevel->initial_response_mins;
            break;
        case 'probdef':
            $slatarget = $servicelevel->prob_determ_mins;
            break;
        case 'actionplan':
            $slatarget = $servicelevel->action_plan_mins;
            break;
        case 'solution':
            $slatarget = ($servicelevel->resolution_days * $working_day_mins);
            break;
        default:
            $slaremain = 0;
            $slatarget = 0;
    }


    if ($slatarget > 0) $slaremain = ($slatarget - $target->since);
    else $slaremain = 0;

    // Get next review time
    $reviewsince = incident_time_since_review($incidents->id);  // time since last review in minutes
    $reviewtarget = ($servicelevel->review_days * 1440);          // how often reviews should happen in minutes (1440 minutes in a day)
    if ($reviewtarget > 0)
    {
        $reviewremain = ($reviewtarget - $reviewsince);
    }
    else
    {
        $reviewremain = 0;
    }

    // Remove Tags from update Body
    $update_body = parse_updatebody($update_body);
    $update_user = user_realname($update_userid, TRUE);

    // ======= Row Colors / Shading =======
    // Define Row Shading lowest to highest priority so that unimportant colors are overwritten by important ones
    switch ($queue)
    {
        case 1: // Action Needed
            $class = 'shade2';
            $explain = '';
            if ($slaremain >= 1)
            {
                if (($slaremain - ($slatarget * ((100 - $CONFIG['notice_threshold']) /100))) < 0 ) $class = 'notice';
                if (($slaremain - ($slatarget * ((100 - $CONFIG['urgent_threshold']) /100))) < 0 ) $class = 'urgent';
                if (($slaremain - ($slatarget * ((100 - $CONFIG['critical_threshold']) /100))) < 0 ) $class = 'critical';
                if ($CONFIG['force_critical_flag'] AND $incidents->priority == 4) $class = 'critical';  // Force critical incidents to be critical always
            }
            elseif ($slaremain < 0)
            {
                $class = 'critical';
            }
            else
            {
                $class = 'shade1';
                $explain = '';  // No&nbsp;Target
            }
            // if ($target->time > $now + ($target->targetval * 0.10 )) $class='critical';
            break;
        case 2: // Waiting
            $class = 'idle';
            $explain = 'No Action Set';
            break;
        case 3: // All Open
            $class = 'shade2';
            $explain = '';
            if ($slaremain >= 1)
            {
                if (($slaremain - ($slatarget * ((100 - $CONFIG['notice_threshold']) /100))) < 0 ) $class = 'notice';
                if (($slaremain - ($slatarget * ((100 - $CONFIG['urgent_threshold']) /100))) < 0 ) $class = 'urgent';
                if (($slaremain - ($slatarget * ((100 - $CONFIG['critical_threshold']) /100))) < 0 ) $class = 'critical';
                if ($incidents->priority == 4) $class = 'critical';  // Force critical incidents to be critical always
            }
            elseif ($slaremain < 0)
            {
                $class = 'critical';
            }
            else
            {
                $class = 'shade1';
                $explain = '';  // No&nbsp;Target
            }
            $explain = 'No Action Set';
            break;
        case 4: // All Closed
            $class = 'expired';
            $explain = 'No Action';
            break;
    }

    // Set Next Action text if not already set
    if ($update_nextaction == '') $update_nextaction = $explain;

    // Create URL for External ID's
    $externalid = '';
    $escalationpath = $incidents->escalationpath;
    if (!empty($incidents->escalationpath) AND !empty($incidents->externalid))
    {
        $epathurl = str_replace('%externalid%',$incidents->externalid, $epath[$escalationpath]['track_url']);
        $externalid = "<a href=\"{$epathurl}\" title=\"{$epath[$escalationpath]['url_title']}\">{$incidents->externalid}</a>";
    }
    elseif (empty($incidents->externalid) AND $incidents->escalationpath >= 1)
    {
        $epathurl = $epath[$escalationpath]['home_url'];
        $externalid = "<a href=\"{$epathurl}\" title=\"{$epath[$escalationpath]['url_title']}\">{$epath[$escalationpath]['name']}</a>";
    }
    elseif (empty($incidents->escalationpath) AND !empty($incidents->externalid))
    {
        $externalid = format_external_id($incidents->externalid);
    }

    echo "<tr class='{$class}' title ='{$rowtitle}'>";
    echo "<td align='center'>";

    echo "<a href='incident_details.php?id={$incidents->id}' class='direct'>{$incidents->id}</a>";
    if ($externalid != '') echo "<br />{$externalid}";
    echo "</td>";
    echo "<td>";

    if (!empty($incidents->softwareid)) echo software_name($incidents->softwareid)."<br />";

    if (count(open_activities_for_incident($incidents->id)) > 0)
    {
        echo icon('timer', 16, $strOpenActivities).' ';
    }

    if (drafts_waiting_on_incident($incidents->id, 'all', $sit[2]))
    {
        echo icon('draft', 16, $strDraftsExist).' ';
    }

    if (trim($incidents->title) != '')
    {
        $linktext = ($incidents->title);
    }
    else
    {
        $linktext = $strUntitled;
    }

    if (!empty($update_body) AND $update_body != '...')
    {
        $tooltip = $update_body;
    }
    else
    {
        $update_currentownername = user_realname($update_currentowner, TRUE);
        $update_headertext = $updatetypes[$update_type]['text'];
        $update_headertext = str_replace('currentowner', $update_currentownername, $update_headertext);
        $update_headertext = str_replace('updateuser', $update_user, $update_headertext);
        $tooltip = "{$update_headertext} on ".date($CONFIG['dateformat_datetime'], $update_timestamp);
    }
    echo html_incident_popup_link($incidents->id, $linktext, $tooltip);
    echo "</td>";

    echo "<td>";
    echo "<a href='contact_details.php?id={$incidents->contactid}' class='info'><span>{$incidents->phone}<br />";
    echo "{$incidents->email}</span>{$incidents->forenames} {$incidents->surname}</a><br />";
    echo "{$incidents->site} {$postsitetext} </td>";

    echo "<td align='center'>";

    // Only display th eSLA name if more than one SLA defined
    if ($number_of_slas > 1)
    {
        // Service Level / Priority
        if (!empty($incidents->maintenanceid))
        {
            echo "{$servicelevel->tag}<br />";
        }
        elseif (!empty($incidents->servicelevel))
        {
            echo "{$incidents->servicelevel}<br />";
        }
        else
        {
            echo "{$strUnknownServiceLevel}<br />";
        }
    }
//     $blinktime = (time() - ($servicelevel->initial_response_mins * 60));
    //  AND $incidents->lastupdated <= $blinktime
    if ($CONFIG['force_critical_flag'] == FALSE AND $incidents->priority == 4)
    {
        echo "<strong class='critical'>".priority_name($incidents->priority)."</strong>";
    }
    else
    {
        echo priority_name($incidents->priority);
    }
    echo "</td>\n";

    echo "<td align='center'>";
    if ($incidents->status == 5 AND $incidents->towner == $sit[2])
    {
        echo "<strong>{$strAwaitingYourResponse}</strong>";
    }
    else
    {
        echo incidentstatus_name($incidents->status);
    }

    if ($incidents->status == 2)
    {
        echo "<br />".closingstatus_name($incidents->closingstatus);
    }

    echo "</td>\n";

    echo "<td align='center'>";
    echo "{$updated}";
    echo " {$strby} {$update_user}";

    if ($incidents->towner > 0 AND $incidents->towner != $user)
    {
        if ($incidents->owner != $user OR $user == 'all')
        {
        	echo "<br />{$strOwner}: <strong>".user_realname($incidents->owner, TRUE)."</strong>";
        }
        echo "<br />{$strTemp}: <strong>".user_realname($incidents->towner, TRUE)."</strong>";
    }
    elseif ($incidents->owner != $user)
    {
        echo "<br />{$strOwner}: <strong>".user_realname($incidents->owner, TRUE)."</strong>";
    }

    echo "</td>\n";

    echo "<td align='center' title='{$explain}'>";
    // Next Action
    /*
        if ($target->time > $now) echo target_type_name($target->type);
        else echo "<strong style='color: red; background-color: white;'>&nbsp;".target_type_name($target->type)."&nbsp;</strong>";
    */
    $targettype = target_type_name($target->type);
    if ($targettype != '')
    {
        if ($slaremain > 0)
        {
            echo sprintf($strSLAInX, $targettype."<br />", format_workday_minutes($slaremain));
        }
        elseif ($slaremain < 0)
        {
            echo $targettype."<br />";
            echo sprintf($strXLate, format_workday_minutes((0 - $slaremain)));
        }
        else
        {
            echo $targettype."<br />".$strDueNow;
        }
    }
    else
    {
        echo $strNone;
    }

    if($_SESSION['userconfig']['show_next_action'] == TRUE)
    {
        $update = incident_lastupdate($incidents->id);

        if ($update[6] != '')
        {
            echo "<br />{$strNextAction}: " . $update[6];
        }
    }
    echo "</td>";

    // Final column
    if ($reviewremain > 0 AND $reviewremain <= 7200)
    {
        // Only display if review is due in the next five days (7200 is the number of minutes in 5 days)
        echo "<td align='center'>";
        // Reviews don't use working days
        echo sprintf($strReviewIn, format_seconds($reviewremain * 60));
    }
    elseif ($reviewremain <= 0)
    {
        echo "<td align='center' class='review'>";
        if ($reviewremain > -86400)
        {
            echo icon('review', 16)." ".sprintf($strReviewDueAgo ,format_seconds(($reviewremain * -1) * 60));
        }
        else
        {
            echo icon('review', 16)." {$strReviewDueNow}";
        }
    }
    else
    {
        echo "<td align='center'>";
        if ($incidents->status == 2) echo "{$strAge}: ".format_seconds($incidents->duration_closed);
        else echo sprintf($strXold, format_seconds($incidents->duration));
    }
    echo "</td>";
    echo "</tr>\n";
}
echo "</table><br />\n";

if ($_SESSION['userconfig']['show_table_legends'] == 'TRUE')
{
    echo "<br />\n<table class='legend'><tr>";
    echo "<td class='shade2'>{$strSLA}: {$strOK}</td>";
    echo "<td class='notice'>{$strSLA}: {$strNotice}</td>";
    echo "<td class='urgent'>{$strSLA}: {$strUrgent}</td>";
    echo "<td class='critical'>{$strSLA}: {$strCritical}</td>";
    echo "</tr></table>";
}

if ($rowcount != 1)
{
    echo "<p align='center'>".sprintf($strIncidentsMulti, "<strong>{$rowcount}</strong>")."</p>";
}
else
{
    echo "<p align='center'>".sprintf($strSingleIncident, "<strong>{$rowcount}</strong>")."</p>";
}

if ($CONFIG['debug']) echo "<!-- End of Support Incidents Table -->\n";

?>