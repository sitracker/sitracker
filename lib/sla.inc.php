<?php
// sla.inc.php - functions relating to SLA / Service Levels
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


/**
    * Create an array containing the service level history
    * @author Ivan Lucas, Tom Gerrard
    * @returns array
*/
function incident_sla_history($incidentid)
{
    global $CONFIG, $dbIncidents, $dbServiceLevels, $dbUpdates;
    $working_day_mins = ($CONFIG['end_working_day'] - $CONFIG['start_working_day']) / 60;

    // Not the most efficient but..
    $sql = "SELECT * FROM `{$dbIncidents}` WHERE id='{$incidentid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $incident = mysql_fetch_object($result);

    // Get service levels
    $sql = "SELECT * FROM `{$dbServiceLevels}` WHERE tag='{$incident->servicelevel}' AND priority='{$incident->priority}' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $level = mysql_fetch_object($result);

    // Loop through the updates in ascending order looking for service level events
    $sql = "SELECT * FROM `{$dbUpdates}` WHERE type='slamet' AND incidentid='{$incidentid}' ORDER BY id ASC, timestamp ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $prevtime = 0;
    $idx = 0;
    while ($history = mysql_fetch_object($result))
    {
        $slahistory[$idx]['targetsla'] = $history->sla;
        switch ($history->sla)
        {
            case 'initialresponse':
                $slahistory[$idx]['targettime'] = $level->initial_response_mins;
                break;
            case 'probdef':
                $slahistory[$idx]['targettime'] = $level->prob_determ_mins;
                break;
            case 'actionplan':
                $slahistory[$idx]['targettime'] = $level->action_plan_mins;
                break;
            case 'solution':
                $slahistory[$idx]['targettime'] = ($level->resolution_days * $working_day_mins);
                break;
            default:
                $slahistory[$idx]['targettime'] = 0;
        }
        if ($prevtime > 0)
        {
            $slahistory[$idx]['actualtime'] = calculate_incident_working_time($incidentid, $prevtime, $history->timestamp);
        }
        else
        {
            $slahistory[$idx]['actualtime'] = 0;
        }

        $slahistory[$idx]['timestamp'] = $history->timestamp;
        $slahistory[$idx]['userid'] = $history->userid;
        if ($slahistory[$idx]['actualtime'] <= $slahistory[$idx]['targettime'])
        {
            $slahistory[$idx]['targetmet'] = TRUE;
        }
        else
        {
            $slahistory[$idx]['targetmet'] = FALSE;
        }

        $prevtime = $history->timestamp;
        $idx++;
    }
    // Get next target, but only if incident is still open
    if ($incident->status != 2 AND $incident->status != 7)
    {
        $target = incident_get_next_target($incidentid);
        $slahistory[$idx]['targetsla'] = $target->type;
        switch ($target->type)
        {
            case 'initialresponse':
                $slahistory[$idx]['targettime'] = $level->initial_response_mins;
                break;
            case 'probdef':
                $slahistory[$idx]['targettime'] = $level->prob_determ_mins;
                break;
            case 'actionplan':
                $slahistory[$idx]['targettime'] = $level->action_plan_mins;
                break;
            case 'solution':
                $slahistory[$idx]['targettime'] = ($level->resolution_days * $working_day_mins);
                break;
            default:
                $slahistory[$idx]['targettime'] = 0;
        }
        $slahistory[$idx]['actualtime'] = $target->since;
        if ($slahistory[$idx]['actualtime'] <= $slahistory[$idx]['targettime'])
        {
            $slahistory[$idx]['targetmet'] = TRUE;
        }
        else
        {
            $slahistory[$idx]['targetmet'] = FALSE;
        }

        $slahistory[$idx]['timestamp'] = 0;
    }
    return $slahistory;
}


/**
* @param string $name name of select
* @param int $id The ID which should be chosen
* @param bool $collapse Only show the tag rather than tag + priority
* @param string $select additional parameter to the select clause e.g. onchange code
* @return String HTML of the SLA drop down
*/
function servicelevel_drop_down($name, $id, $collapse = FALSE, $select = '')
{
    global $dbServiceLevels;

    if ($collapse)
    {
        $sql = "SELECT DISTINCT id, tag FROM `{$dbServiceLevels}`";
    }
    else
    {
        $sql  = "SELECT id, priority FROM `{$dbServiceLevels}`";
    }
    $result = mysql_query($sql);

    $html = "<select id='{$name}' name='{$name}' {$select}>\n";
    // INL 30Mar06 Removed this ability to select a null service level
    // if ($id == 0) $html .= "<option selected='selected' value='0'></option>\n";
    while ($servicelevels = mysql_fetch_object($result))
    {
        $html .= "<option ";
        $html .= "value='{$servicelevels->id}' ";
        if ($servicelevels->id == $id)
        {
            $html .= "selected='selected'";
        }

        $html .= ">";
        if ($collapse)
        {
            $html .= $servicelevels->tag;
        }
        else
        {
            $html .= "{$servicelevels->tag} ".priority_name($servicelevels->priority);
        }

        $html .= "</option>\n";
    }
    $html .= "</select>";
    return $html;
}


function serviceleveltag_drop_down($name, $tag, $collapse = FALSE)
{
    global $dbServiceLevels;

    if ($collapse)
    {
        $sql = "SELECT DISTINCT tag FROM `{$dbServiceLevels}`";
    }
    else
    {
        $sql  = "SELECT tag, priority FROM `{$dbServiceLevels}`";
    }
    $result = mysql_query($sql);


    $html = "<select name='$name'>\n";
    if ($tag == '')
    {
        $html .= "<option selected='selected' value=''></option>\n";
    }

    while ($servicelevels = mysql_fetch_object($result))
    {
        $html .= "<option ";
        $html .= "value='{$servicelevels->tag}' ";
        if ($servicelevels->tag == $tag)
        {
            $html .= "selected='selected'";
        }

        $html .= ">";
        if ($collapse)
        {
            $html .= $servicelevels->tag;
        }
        else
        {
            $html .= "{$servicelevels->tag} ".priority_name($servicelevels->priority);
        }

        $html .= "</option>\n";
    }
    $html .= "</select>";
    return $html;
}


/* Returns a string representing the name of   */
/* the given servicelevel. Returns an empty string if the     */
/* priority does not exist.                                   */
function servicelevel_name($id)
{
    global $CONFIG;

    $servicelevel = db_read_column('tag', $GLOBALS['dbServiceLevels'], $id);

    if ($servicelevel == '')
    {
        $servicelevel = $CONFIG['default_service_level'];
    }
    return $servicelevel;
}


/**
 * Find whether a given servicelevel is timed
 * @author Ivan Lucas
 * @param string Service level tag
 * @returns. bool. TRUE if any part of the service level is timed, otherwise returns FALSE
 */
function servicelevel_timed($sltag)
{
    global $dbServiceLevels;
    $timed = FALSE;

    $sql = "SELECT COUNT(tag) FROM `{$dbServiceLevels}` WHERE tag = '{$sltag}' AND timed = 'yes'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    list($count) = mysql_fetch_row($result);
    if ($count > 0) $timed = TRUE;

    return $timed;
}


/**
 * @author Ivan Lucas
 * @deprecated
 * @note DEPRECATED service level tags should be used in favour of service level ID's
 * @note Temporary solution, eventually we will move away from using servicelevel id's  and just use tags instead
 * Find the maximum priority of a service level
 * @author Paul Heaney
 * @param string $slatag The SLA to find the max priority of
 * @return int The maximum priority of an SLA, 0 if invalid SLA
 */
function servicelevel_maxpriority($slatag)
{
    global $dbServiceLevels;
    $priority = 0;

    $sql = "SELECT MAX(priority) FROM `{$dbServiceLevels}` WHERE tag = '{$slatag}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($priority) = mysql_fetch_row($result);
    return $priority;
}


/**
 * @author Ivan Lucas
 * @deprecated
 * @note DEPRECATED service level tags should be used in favour of service level ID's
 * @note Temporary solution, eventually we will move away from using servicelevel id's  and just use tags instead
 */
function servicelevel_id2tag($id)
{
    global $dbServiceLevels;
    return db_read_column('tag', $dbServiceLevels, $id);
}


/**
 * @author Ivan Lucas
 * @deprecated
 * @note DEPRECATED service level tags should be used in favour of service level ID's
 * @note Temporary solution, eventually we will move away from using servicelevel id's  and just use tags instead
 */
function servicelevel_tag2id($sltag)
{
    $sql = "SELECT id FROM `{$GLOBALS['dbServiceLevels']}` WHERE tag = '{$sltag}' AND priority=1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($id) = mysql_fetch_row($result);

    return $id;
}

?>