<?php
// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}
session_name($CONFIG['session_name']);
session_start();
plugin_do('before_page');
echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\"  xml:lang=\"{$_SESSION['lang']}\" lang=\"{$_SESSION['lang']}\"";
if (isset($i18ndirection) AND !empty($i18ndirection))
{
    echo " dir=\"{$i18ndirection}\"";
}
echo ">\n<head><title>";
if (!empty($incidentid)) echo "{$incidentid} - ";
if (isset($title))
{
    echo $title;
}
else
{
    echo $CONFIG['application_shortname'];
}

echo "</title>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset={$i18ncharset}\" />\n";
echo "<meta name=\"GENERATOR\" content=\"{$CONFIG['application_name']} {$application_version_string}\" />\n";
echo "<style type='text/css'>@import url('{$CONFIG['application_webpath']}styles/sitbase.css');</style>\n";

if ($_SESSION['auth'] == TRUE)
{
    $theme = $_SESSION['userconfig']['theme'];
    $iconset = $_SESSION['userconfig']['iconset'];
}
else
{
    $theme = $CONFIG['default_interface_style'];
    $iconset = $CONFIG['default_iconset'];
}
if (empty($iconset)) $iconset = 'sit';
echo "<link rel='stylesheet' href='{$CONFIG['application_webpath']}styles/{$theme}/{$theme}.css' />\n";

echo "<script src='{$CONFIG['application_webpath']}scripts/prototype/prototype.js' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/sit.js.php' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/webtrack.js' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/activity.js' type='text/javascript'></script>\n";
// To include a script for a single page, add the filename to the $pagescripts variable before including htmlheader.inc.php
if (is_array($pagescripts))
{
    foreach ($pagescripts AS $pscript)
    {
        echo "<script src='{$CONFIG['application_webpath']}scripts/{$pscript}' type='text/javascript'></script>\n";
    }
    unset($pagescripts, $pscript);
}
// javascript popup date library
echo "<script src='{$CONFIG['application_webpath']}scripts/calendar.js' type='text/javascript'></script>\n";

//update last seen
$lastseensql = "UPDATE LOW_PRIORITY `{$dbUsers}` SET lastseen=NOW() WHERE id='{$_SESSION['userid']}' LIMIT 1";
mysql_query($lastseensql);
if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

// FIXME put here some js to set action field then post form

plugin_do('html_head');
echo "</head>";
echo "<body onload=\"self.focus()\">";

$incidentid = $id;
// Retrieve incident
// extract incident details
$sql  = "SELECT *, i.id AS incidentid, ";
$sql .= "c.id AS contactid, c.notes AS contactnotes, servicelevel ";
$sql .= "FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c ";
$sql .= "WHERE (i.id='{$incidentid}' AND i.contact = c.id) ";
$sql .= " OR i.contact=NULL ";
$incidentresult = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
$incident = mysql_fetch_object($incidentresult);
$sitesql = "SELECT name, notes FROM `{$dbSites}` WHERE id = '{$incident->siteid}'";
$siteresult = mysql_query($sitesql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
$site = mysql_fetch_object($siteresult);
$site_name = $site->name;

if (!empty($site->notes))
{
    $site_notes = icon('site', 16)." <strong>{$strSiteNotes}</strong><br />".nl2br($site->notes);
}
else
{
    $site_notes = '';
}

unset($site);
if (!empty($incident->contactnotes))
{
    $contact_notes = icon('contact', 16)." <strong>{$strContactNotes}</strong><br />".nl2br($incident->contactnotes);
}
else
{
    $contact_notes='';
}

$product_name = product_name($incident->product);
if ($incident->softwareid > 0)
{
    $software_name = software_name($incident->softwareid);
}

$servicelevel_tag = $incident->servicelevel;
if ($servicelevel_tag == '')
{
    $servicelevel_tag = maintenance_servicelevel_tag($incident->maintenanceid);
}

if ($incident->closed == 0)
{
    $closed = time();
}
else
{
    $closed = $incident->closed;
}

$opened_for = format_seconds($closed - $incident->opened);

$priority = $incident->priority;

// Lookup the service level times
$slsql = "SELECT * FROM `{$dbServiceLevels}` WHERE tag='{$servicelevel_tag}' AND priority='{$incident->priority}' ";
$slresult = mysql_query($slsql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
$servicelevel = mysql_fetch_object($slresult);

// Get next target
$target = incident_get_next_target($incidentid);
// Calculate time remaining in SLA
$working_day_mins = ($CONFIG['end_working_day'] - $CONFIG['start_working_day']) / 60;
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

if ($slatarget > 0)
{
    $slaremain = ($slatarget - $target->since);
}
else
{
    $slaremain = 0;
}

$targettype = target_type_name($target->type);

// Get next review time
$reviewsince = incident_time_since_review($incidentid);  // time since last review in minutes
$reviewtarget = ($servicelevel->review_days * 1440);          // how often reviews should happen in minutes (1440 minutes in a day)
if ($reviewtarget > 0)
{
    $reviewremain = ($reviewtarget - $reviewsince);
}
else
{
    $reviewremain = 0;
}

// Color the title bar according to the SLA and priority
$class = 'normal';
if ($slaremain != 0 AND $incident->status != STATUS_CLOSED)
{
    if (($slaremain - ($slatarget * ((100 - $CONFIG['notice_threshold']) /100))) < 0 ) $class = 'notice';
    if (($slaremain - ($slatarget * ((100 - $CONFIG['urgent_threshold']) /100))) < 0 ) $class = 'urgent';
    if (($slaremain - ($slatarget * ((100 - $CONFIG['critical_threshold']) /100))) < 0 ) $class = 'critical';
    if ($incidents["priority"] == 4) $class = 'critical';  // Force critical incidents to be critical always
}

// Print a table showing summary details of the incident

if ($_REQUEST['win'] == 'incomingview')
{
    echo "<h1 class='review'>{$strIncoming}</h1>";
}
else
{
    echo "<h1 class='{$class}'>{$title}: {$incidentid} - {$incident->title}</h1>";
}

echo "<div id='navmenu'>";
if ($menu != 'hide')
{
    if ($_REQUEST['win'] == 'incomingview')
    {
        $insql = "SELECT emailfrom, `from`, contactid, updateid, ti.id, timestamp, ti.locked
                FROM `{$dbTempIncoming}` AS ti, `{$dbUpdates}` AS u
                WHERE ti.id = '{$id}'
                AND ti.updateid = u.id";
        $insresult = mysql_query($insql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($insresult) > 0)
        {
            $inupdate = mysql_fetch_object($insresult);

            if ($inupdate->locked == $sit[2] OR empty($inupdate->locked))
            {
                echo "<a class='barlink' href='unlock_update.php?id={$id}'>{$strUnlock}</a> | ";
                echo "<a class='barlink' href=\"javascript:window.location='move_update.php?id={$id}&amp;updateid={$inupdate->updateid}&amp;contactid={$inupdate->contactid}&amp;win=incomingview'\" >{$strAssign}</a> | ";
                echo "<a class='barlink' href=\"javascript:window.opener.location='incident_new.php?action=findcontact&amp;incomingid={$id}&amp;search_string={$inupdate->emailfrom}&amp;from={$inupdate->from}&amp;contactid={$inupdate->contactid}&amp;win=incomingcreate'; window.close();\">{$strCreate}</a> | ";
                echo "<a class='barlink' href=\"javascript:window.opener.location='delete_update.php?updateid={$inupdate->updateid}&amp;tempid={$inupdate->id}&amp;timestamp={$inupdate->timestamp}'; window.close(); \">{$strDelete}</a>";
            }
        }
        elseif (incident_status($id) != STATUS_CLOSED)
        {
            echo "<a href= \"javascript:wt_winpopup('incident_reassign.php?id={$id}&amp;reason=Initial%20assignment%20to%20engineer&amp;popup=yes','mini');\" title='Assign this incident'>{$strAssign}</a>";
        }
    }
    elseif (incident_status($id) != STATUS_CLOSED)
    {
        echo "<a class='barlink' href='{$CONFIG['application_webpath']}incident_update.php?id={$id}&amp;popup={$popup}' accesskey='U'>{$strUpdate}</a> | ";
        echo "<a class='barlink' href='javascript:close_window({$id});' accesskey='C'>{$strClose}</a> | ";
        echo "<a class='barlink' href='{$CONFIG['application_webpath']}incident_reassign.php?id={$id}&amp;popup={$popup}' accesskey='R'>{$strReassign}</a> | ";
        echo "<a class='barlink' href='{$CONFIG['application_webpath']}incident_edit.php?id={$id}&amp;popup={$popup}' accesskey='T'>{$strEdit}</a> | ";
        echo "<a class='barlink' href='{$CONFIG['application_webpath']}incident_service_levels.php?id={$id}&amp;popup={$popup}' accesskey='S'>{$strService}</a> | ";
        echo "<a class='barlink' href='{$CONFIG['application_webpath']}incident_relationships.php?id={$id}&amp;tab=relationships' accesskey='L'>{$strRelations}</a> | ";
        if ($CONFIG['enable_outbound_email'] == TRUE)
        {
            echo "<a class='barlink' href='javascript:email_window({$id})' accesskey='E'>{$strEmail}</a> | ";
        }
        echo "<a class='barlink' href='{$CONFIG['application_webpath']}incident_attachments.php?id={$id}&amp;popup={$popup}' accesskey='F'>{$strFiles}</a> | ";
        if ($servicelevel->timed == 'yes') echo "<a class='barlink' href='{$CONFIG['application_webpath']}tasks.php?incident={$id}'>{$strActivities}</a> | ";
        echo "<a class='barlink' href='{$CONFIG['application_webpath']}incident_details.php?id={$id}&amp;popup={$popup}' accesskey='D'>{$strDetailsAndLog}</a> | ";

        echo "<a class='barlink' target='top.opener' href='{$CONFIG['application_webpath']}help.php'>{$strHelpChar}</a>";
        if (!empty($_REQUEST['popup'])) echo " | <a class=barlink href='javascript:window.close();'>{$strCloseWindow}</a>";
    }
    else
    {
        echo "<a class='barlink' href='{$CONFIG['application_webpath']}incident_reopen.php?id={$id}&amp;popup={$popup}'>{$strReopen}</a> | ";
        echo "<a class='barlink' href='{$CONFIG['application_webpath']}incident_service_levels.php?id={$id}&amp;popup={$poup}' accesskey='S'>{$strService}</a> | ";
        echo "<a class='barlink' href='{$CONFIG['application_webpath']}incident_relationships.php?id={$id}&amp;tab=relationships'>{$strRelations}</a> | ";
        echo "<a class='barlink' href='{$CONFIG['application_webpath']}incident_attachments.php?id={$id}&amp;popup={$popup}' accesskey='F'>{$strFiles}</a> | ";
        if ($servicelevel->timed == 'yes') echo "<a class='barlink' href='{$CONFIG['application_webpath']}tasks.php?incident={$id}'>{$strActivities}</a> | ";
        echo "<a class='barlink' href='{$CONFIG['application_webpath']}incident_details.php?id={$id}&amp;popup={$popup}' accesskey='D'>{$strDetailsAndLog}</a> | ";
        echo "<a class='barlink' target='top.opener' href='{$CONFIG['application_webpath']}help.php'>{$strHelpChar}</a>";
        if (!empty($_REQUEST['popup'])) echo " | <a class='barlink' href='javascript:window.close();'>{$strCloseWindow}</a>";
    }
}
else
{
    echo "<a class='barlink' href='javascript:window.close();'>{$strCloseWindow}</a>";
}
echo "</div>";
?>
