<?php
// reassign_incident.php - Form for re-assigning an incident to another user
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


$permission = 13; // Reassign Incident
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$forcepermission = user_permission($sit[2],40);

// External variables
$bodytext = cleanvar($_REQUEST['bodytext']);
$id = clean_int($_REQUEST['id']);
$incidentid = $id;
$backupid = clean_int($_REQUEST['backupid']);
$originalid = clean_int($_REQUEST['originalid']);
$reason = cleanvar($_REQUEST['reason']);
$action = cleanvar($_REQUEST['action']);
$title = $strReassignIncident;

switch ($action)
{
    case 'save':
        // External variables
        $tempnewowner = cleanvar($_REQUEST['tempnewowner']);
        $permnewowner = cleanvar($_REQUEST['permnewowner']);
        $removetempowner = cleanvar($_REQUEST['removetempowner']);
        $fullreassign = cleanvar($_REQUEST['fullreassign']);
        $newstatus = clean_int($_REQUEST['newstatus']);
        $userid = clean_int($_REQUEST['userid']);
        $temporary = cleanvar($_REQUEST['temporary']);
        $id = clean_int($_REQUEST['id']);

        if ($tempnewowner == 'yes') $temporary = 'yes';

        // Retrieve current incident details
        $sql = "SELECT * FROM `{$dbIncidents}` WHERE id='$id' LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $incident = mysql_fetch_object($result);

        if ($newstatus != $incident->status)
        {
            $bodytext = "Status: ".incidentstatus_name($incident->status)." -&gt; <b>" . incidentstatus_name($newstatus) . "</b>\n\n" . $bodytext;
        }

        // Update incident
        $sql = "UPDATE `{$dbIncidents}` SET ";

        if ($fullreassign == 'yes')
        {
            //full reassign with temp owner, fixes http://bugs.sitracker.org/view.php?id=141
            $sql .= "owner='{$userid}', towner=0, ";
            $triggeruserid = $userid;
        }
        elseif ($temporary != 'yes' AND $incident->towner > 0 AND $sit[2] == $incident->owner)
        {
            $sql .= "owner='{$sit[2]}', towner=0, "; // make current user = owner
            $triggeruserid = $sit[2];
        }
        elseif ($temporary != 'yes' AND $sit[2]==$incident->towner)
        {
            $sql .= "towner=0, "; // temp owner removing temp ownership
            $triggeruserid = $incident->owner;
        }
        elseif ($temporary == 'yes' AND $tempnewowner != 'yes' AND $incident->towner < 1 AND $sit[2]!=$incident->owner)
        {
            $sql .= "towner={$sit[2]}, "; // Temp to self
            $triggeruserid = $sit[2];
        }
        elseif ($temporary == 'yes' AND $tempnewowner != 'yes'  AND $userid==$incident->owner)
        {
            $sql .= "owner='{$userid}', towner=0, ";
            $triggeruserid = $userid;
        }
        elseif ($temporary == 'yes')
        {
            $sql .= "towner='{$userid}', ";
            $triggeruserid = $userid;
        }
        else
        {
            $sql .= "owner='{$userid}', ";
            $triggeruserid = $userid;
        }
        $sql .= "status='$newstatus', lastupdated='$now' WHERE id='$id' LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        if (isset($triggeruserid))
        {
            trigger('TRIGGER_INCIDENT_ASSIGNED', array('userid' => $triggeruserid, 'incidentid' => $incidentid));
        }
//         if ($CONFIG['debug'])
//         {
//             echo "<pre>";
//                 print_r($_REQUEST);
//                 print_r($incident);
//                 echo "<hr>$sql";
//                 exit;
//         }

        // add update
        if (strtolower(user_accepting($userid)) != "yes")
        {
            $bodytext = "({$strIncidentAssignmentWasForcedUserNotAccept})<hr>\n" . $bodytext;   
        }

        if ($temporary == 'yes') $assigntype = 'tempassigning';
        else $assigntype = 'reassigning';

        if ($_REQUEST['cust_vis'] == 'yes') $customervisibility='show';
        else $customervisibility='hide';

        $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, bodytext, type, timestamp, currentowner, currentstatus, customervisibility) ";
        $sql .= "VALUES ($id, $sit[2], '$bodytext', '$assigntype', '$now', ";
        if ($fullreassign == 'yes')
        {
            $sql .= "'{$userid}', ";
        }
        elseif ($temporary != 'yes' AND $incident->towner > 0 AND $sit[2] == $incident->owner)
        {
            $sql .= "'{$sit[2]}', ";
        }
        elseif ($temporary != 'yes' AND $sit[2] == $incident->towner)
        {
            $sql .= "'{$incident->owner}', ";
        }
        elseif ($temporary == 'yes' AND $tempnewowner != 'yes' AND $incident->towner < 1 AND $sit[2] != $incident->owner)
        {
            $sql .= "'{$sit[2]}', ";
        }
        elseif ($temporary == 'yes')
        {
            $sql .= "'{$userid}', ";
        }
        else
        {
            $sql .= "'{$userid}', ";
        }

        $sql .= "'$newstatus', '$customervisibility')";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        // Remove any tempassigns that are pending for this incident
        $sql = "DELETE FROM `{$dbTempAssigns}` WHERE incidentid='$id'";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        html_redirect("incident_details.php?id={$id}");
        break;

    default:
        // No submit detected show reassign form
        $title = $strReassign;
        include (APPLICATION_INCPATH . 'incident_html_top.inc.php');


        $sql = "SELECT * FROM `{$dbIncidents}` WHERE id='{$id}' LIMIT 1";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $incident = mysql_fetch_object($result);

        if ($incident->towner == $sit[2]) $suggested = suggest_reassign_userid($id);
        else $suggested = suggest_reassign_userid($id, $incident->owner);
        if ($suggested === FALSE)
        {
            $suggested = 0;
            $dbg .= "<p>No users suggested</p>";
        }

        echo "<form name='assignform' action='{$_SERVER['PHP_SELF']}?id={$id}' method='post'>";

        $sql = "SELECT * FROM `{$dbUsers}` WHERE status != 0 ";
        $sql .= "AND id != {$incident->owner} ";
        if ($suggested > 0) $sql .= "AND id != '$suggested' ";
        if (!$forcepermission) $sql .= "AND accepting = 'Yes' ";
        $sql .= "ORDER BY realname";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $countusers = mysql_num_rows($result);

        echo "<p style='font-size: 18px'>{$strOwner}: <strong>";
        if ($sit[2] == $incident->owner) echo "{$strYou} (".user_realname($incident->owner, TRUE).")";
        else echo user_realname($incident->owner, TRUE);
        echo "</strong>";

        if ($incident->towner > 0)
        {
            echo " ({$strTemp}: ";
            if ($sit[2] == $incident->towner)
            {
                echo "{$strYou} (".user_realname($incident->towner, TRUE).")";
            }
            else echo user_realname($incident->towner, TRUE);
            echo ")";
        }
        echo "</p>";

        if ($countusers > 0 OR ($countusers == 0 AND $suggested > 0))
        {
            echo "<div id='reassignlist'>";
            echo "<table align='center'>";
            if ($countusers >= 1 AND $suggested > 0) echo "<thead>\n";
            echo "<tr>
                <th colspan='2'>{$strReassignTo}:</th>
                <th colspan='5'>{$strIncidentsinQueue}</th>
                <th>{$strAccepting}</th>
                </tr>";
            echo "<tr>
                <th>{$strName}</th>
                <th>{$strStatus}</th>
                <th align='center'>{$strActionNeeded} / {$strOther}</th>";
            echo "<th align='center'>".priority_icon(4)."</th>";
            echo "<th align='center'>".priority_icon(3)."</th>";
            echo "<th align='center'>".priority_icon(2)."</th>";
            echo "<th align='center'>".priority_icon(1)."</th>";
            echo "<th></th></tr>\n";

            if ($suggested > 0)
            {
                // Suggested user is shown as the first row
                $sugsql = "SELECT * FROM `{$dbUsers}` WHERE id='{$suggested}' LIMIT 1";
                $sugresult = mysql_query($sugsql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                $suguser = mysql_fetch_object($sugresult);
                echo "<tr class='idle'>";
                echo "<td><label><input type='radio' name='userid' checked='checked' value='{$suguser->id}' /> ";
                // Have a look if this user has skills with this software
                $ssql = "SELECT softwareid FROM `{$dbUserSoftware}` WHERE userid={$suguser->id} AND softwareid={$incident->softwareid} ";
                $sresult = mysql_query($ssql);
                if (mysql_error()) trigger_error("MySQL Query Error".mysql_error(), E_USER_WARNING);
                if (mysql_num_rows($sresult) >=1 )
                {
                    echo "<strong>{$suguser->realname}</strong>";
                }
                else
                {
                    echo $suguser->realname;
                }

                echo "</label></td>";
                echo "<td>".user_online_icon($suguser->id).userstatus_name($suguser->status)."</td>";
                $incpriority = user_incidents($suguser->id);
                $countincidents = ($incpriority['1']+$incpriority['2']+$incpriority['3']+$incpriority['4']);

                if ($countincidents >= 1)
                {
                    $countactive = user_activeincidents($suguser->id);
                }
                else
                {
                    $countactive = 0;
                }

                $countdiff = $countincidents-$countactive;
                echo "<td align='center'>$countactive / {$countdiff}</td>";
                echo "<td align='center'>".$incpriority['4']."</td>";
                echo "<td align='center'>".$incpriority['3']."</td>";
                echo "<td align='center'>".$incpriority['2']."</td>";
                echo "<td align='center'>".$incpriority['1']."</td>";
                echo "<td align='center'>";
                echo $suguser->accepting == 'Yes' ? $strYes : "<span class='error'>{$strNo}</span>";
                echo "</td>";
                echo "</tr>\n";
            }

            if ($countusers >= 1)
            {
                // Other users are shown in a optional section
                if ($suggested > 0) echo "</thead><tbody id='moreusers' style='display:none;'>";
                $shade = 'shade1';

                while ($users = mysql_fetch_object($result))
                {
                    echo "<tr class='$shade'>";
                    echo "<td><label><input type='radio' name='userid' value='{$users->id}' /> ";
                    // Have a look if this user has skills with this software
                    $ssql = "SELECT softwareid FROM `{$dbUserSoftware}` WHERE userid={$users->id} AND softwareid={$incident->softwareid} ";
                    $sresult = mysql_query($ssql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                    if (mysql_num_rows($sresult) >=1 ) echo "<strong>{$users->realname}</strong>";
                    else echo $users->realname;
                    echo "</label></td>";
                    echo "<td>".user_online_icon($users->id).userstatus_name($users->status)."</td>";
                    $incpriority = user_incidents($users->id);
                    $countincidents = ($incpriority['1']+$incpriority['2']+$incpriority['3']+$incpriority['4']);

                    if ($countincidents >= 1) $countactive = user_activeincidents($users->id);
                    else $countactive = 0;
                    $countdiff = $countincidents - $countactive;
                    echo "<td align='center'>$countactive / {$countdiff}</td>";
                    echo "<td align='center'>".$incpriority['4']."</td>";
                    echo "<td align='center'>".$incpriority['3']."</td>";
                    echo "<td align='center'>".$incpriority['2']."</td>";
                    echo "<td align='center'>".$incpriority['1']."</td>";
                    echo "<td align='center'>";
                    if ($users->accepting == 'Yes') echo $strYes;
                    else echo "<span class='error'>{$strNo}</span>";
                    echo "</td>";
                    echo "</tr>\n";
                    if ($shade == 'shade1') $shade = 'shade2';
                    else $shade = 'shade1';
                }
                if ($suggested > 0) echo "</tbody>";
            }
            echo "\n</table><br />\n";
            if ($suggested > 0 AND $countusers >= 1)
            {
                echo "<p id='morelink'><a href=\"#\" onclick=\"$('moreusers').toggle();$('morelink').toggle();\">";
                echo "{$countusers} {$strMore}</a></p>";
            }
            echo "</div>\n"; // reassignlist

            echo "<table class='vertical'>";

            echo "<tr><td colspan='2'><br />{$strReassignText}</td></tr>\n";
            echo "<tr><th>{$strUpdate}:</th>";
            echo "<td>";
            echo "<textarea name='bodytext' wrap='soft' rows='10' cols='65'>";  // FIXME wrap isn't valid XHTML
            if (!empty($reason)) echo $reason;
            echo "</textarea>";
            echo "</td></tr>\n";
            if ($incident->towner > 0 AND ($sit[2] == $incident->owner OR $sit[2] == $incident->towner))
            {
                echo "<tr><th>{$strOwner}:</th><td>";
                echo "<label><input type='checkbox' name='fullreassign' value='yes' onchange=\"$('reassignlist').show();\" /> {$strChangeOwner}</label>";
                echo "<label><input type='checkbox' name='temporary' value='yes' onchange=\"$('reassignlist').show();\" /> {$strChangeTemporaryOwner}</label>";
                echo "<label><input type='checkbox' name='removetempowner' value='yes' onchange=\"$('reassignlist').hide();\" /> {$strRemoveTemporaryOwner}</label> ";
                echo "</td></tr>\n";
            }
            elseif ($sit[2] != $incident->owner)
            {
                // $incident->towner < 1 AND
                echo "<tr><th>{$strTemporaryOwner}:</th><td>";
                echo "<label><input type='checkbox' name='temporary' value='yes' onchange=\"$('reassignlist').toggle();\" /> ";
                echo "{$strAssignTemporarilyTo} <strong>{$strYou}</strong> ({$_SESSION['realname']})</label><br />";
                echo "<label><input type='checkbox' name='tempnewowner' value='yes'  /> ";
                echo "{$strAssignTemporarily}</label>";
                echo "</td></tr>";
            }
            else
            {
                echo "<tr><th>{$strTemporaryOwner}:</th><td><label><input type='checkbox' name='temporary' value='yes' ";
                if ($sit[2] != $incident->owner AND $sit[2] != $incident->towner)
                {
                    echo "disabled='disabled' ";
                }
                echo "/> ";
                if ($incident->towner > 0) echo "{$strChangeTemporaryOwner}";
                else echo "{$strAssignTemporarily}";
                echo "</label></td></tr>\n";
            }
            echo "<tr><th>{$strVisibility}:</th><td><label>";
            echo "<input type='checkbox' name='cust_vis' value='yes' /> {$strVisibleToCustomer}</label></td></tr>\n";

            echo "<tr><th>{$strNewIncidentStatus}:</th>";
            echo "<td>".incidentstatus_drop_down("newstatus", $incident->status)."</td></tr>\n";
            echo "</table>\n\n";
            echo "<input type='hidden' name='action' value='save' />";
            echo "<p align='center'><input name='submit' type='submit' value=\"{$strReassign}\" /></p>";
            echo "</form>\n";
        }
        else
        {
            echo "<p class='warning'>{$strNoRecords}</p>";  // FIXME 3.41 better message here
        }
        include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
}

?>