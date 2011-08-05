<?php
// dashboard_watch_incidents.php - Watch incidents on your dashboard either from a site, a customer or a user
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paulheaney[at]users.sourceforge.net>

$dashboard_watch_incidents_version = 2;

function dashboard_watch_incidents($dashletid)
{
    global $sit, $CONFIG, $iconset;

    $content = "<p align='center'><img src='{$CONFIG['application_webpath']}images/ajax-loader.gif' alt='Loading icon' /></p>";
    echo dashlet('watch_incidents', $dashletid, icon('support', 16), $GLOBALS['strWatchIncidents'], '', $content);
}

function dashboard_watch_incidents_install()
{
    global $CONFIG;
    $schema = "CREATE TABLE IF NOT EXISTS `{$CONFIG['db_tableprefix']}dashboard_watch_incidents` (
        `userid` smallint(6) NOT NULL,
        `type` tinyint(4) NOT NULL,
        `id` int(11) NOT NULL,
        PRIMARY KEY  (`userid`,`type`,`id`)
        ) ENGINE=MyISAM ;";

    $result = mysql_query($schema);
    if (mysql_error())
    {
        echo "<p>Dashboard watch incidents failed to install, please run the following SQL statement on the SiT database to create the required schema.</p>";
        echo "<pre>{$schema}</pre>";
        $res = FALSE;
    }
    else $res = TRUE;

    return $res;
}


function dashboard_watch_incidents_display($dashletid)
{
    global $CONFIG, $sit;

    $html = "";

// FIXME, commented out the queue selector, needs recoding to work with one-file dashboards - Ivan 22May08

//     $html .= "<form action='{$_SERVER['PHP_SELF']}' style='text-align: center;'>";
//     $html .= "{$GLOBALS['strQueue']}: <select class='dropdown' name='queue' onchange='window.location.href=this.options[this.selectedIndex].value'>\n";
//     $html .= "<option ";
//     if ($queue == 5)
//     {
//         $html .= "selected='selected' ";
//     }
//     $html .= "value=\"javascript:get_and_display('display_watch_incidents.inc.php?queue=5','watch_incidents_windows');\">{$GLOBALS['strAll']}</option>\n";
//     $html .= "<option ";
//     if ($queue == 1)
//     {
//         $html .= "selected='selected' ";
//     }
//     $html .= "value=\"javascript:get_and_display('display_watch_incidents.inc.php?queue=1','watch_incidents_windows');\">{$GLOBALS['strActionNeeded']}</option>\n";
//     $html .= "<option ";
//     if ($queue == 3)
//     {
//         $html .= "selected='selected' ";
//     }
//     $html .= "value=\"javascript:get_and_display('display_watch_incidents.inc.php?queue=3','watch_incidents_windows');\">{$GLOBALS['strAllOpen']}</option>\n";
//     $html .= "</select>\n";
//     $html .= "</form>";

    $sql = "SELECT type, id FROM `{$CONFIG['db_tableprefix']}dashboard_watch_incidents` WHERE userid = {$sit[2]} ORDER BY type";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error().$sql,E_USER_WARNING);


    if (mysql_num_rows($result) > 0)
    {
        $header_printed = FALSE;
        $previous = 0;
        while ($obj = mysql_fetch_object($result))
        {
            if ($obj->type !=3 AND $previous == 3)
            {
                $html .= "</table>";
            }

            if ($obj->type == 3 AND !$header_printed)
            {
                $html .= "<table>";
            }
            else if ($obj->type != 3)
            {
                $html .= "<table>";
            }

            switch ($obj->type)
            {
                case '0': //Site
                    $sql = "SELECT i.id, i.title, i.status, i.servicelevel, i.maintenanceid, i.priority, c.forenames, c.surname, c.siteid ";
                    $sql .= "FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbContacts']}`  AS c ";
                    $sql .= "WHERE i.contact = c.id AND c.siteid = {$obj->id} ";
                    $sql .= "AND i.status != ".STATUS_CLOSED." AND i.status != ".STATUS_CLOSING." ";

                    $lsql = "SELECT name FROM `{$GLOBALS['dbSites']}` WHERE id = {$obj->id}";
                    $lresult = mysql_query($lsql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    $lobj = mysql_fetch_object($lresult);
                    $html .= "<tr><th colspan='3'>{$lobj->name} ({$GLOBALS['strSite']})</th></tr>";
                    break;
                case '1': //contact
                    $sql = "SELECT i.id, i.title, i.status, i.servicelevel, i.maintenanceid, i.priority, c.forenames, c.surname, c.siteid ";
                    $sql .= "FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbContacts']}`  AS c ";
                    $sql .= "WHERE i.contact = c.id AND i.contact = {$obj->id} ";
                    $sql .= "AND i.status != ".STATUS_CLOSED." AND i.status != ".STATUS_CLOSING." ";

                    $lsql = "SELECT forenames, surname FROM `{$GLOBALS['dbContacts']}` WHERE id = {$obj->id} ";
                    $lresult = mysql_query($lsql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    $lobj = mysql_fetch_object($lresult);
                    $html .= "<tr><th colspan='3'>{$lobj->forenames} {$lobj->surname} ({$GLOBALS['strContact']})</th></tr>";
                    break;
                case '2': //engineer
                    $sql = "SELECT i.id, i.title, i.status, i.servicelevel, i.maintenanceid, i.priority, c.forenames, c.surname, c.siteid ";
                    $sql .= "FROM `{$GLOBALS['dbIncidents']}` AS i, `{$GLOBALS['dbContacts']}`  AS c ";
                    $sql .= "WHERE i.contact = c.id AND (i.owner = {$obj->id} OR i.towner = {$obj->id}) ";
                    $sql .= "AND i.status != ".STATUS_CLOSED." AND i.status != ".STATUS_CLOSING." ";

                    $lsql = "SELECT realname FROM `{$GLOBALS['dbUsers']}` WHERE id = {$obj->id}";
                    $lresult = mysql_query($lsql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    $lobj = mysql_fetch_object($lresult);
                    $html .= "<tr><th colspan='3'>";
                    $html .= sprintf($GLOBALS['strIncidentsForEngineer'], $lobj->realname);
                    $html .= "</th></tr>";

                    break;
                case '3': //incident
                    $sql = "SELECT i.id, i.title, i.status, i.servicelevel, i.maintenanceid, i.priority ";
                    $sql .= "FROM `{$GLOBALS['dbIncidents']}` AS i ";
                    $sql .= "WHERE i.id = {$obj->id} ";
                    //$sql .= "AND incidents.status != 2 AND incidents.status != 7";
                    break;
                default:
                    $sql = '';
            }

            if (!empty($sql))
            {
                switch ($queue)
                {
                    case 1: // awaiting action
                        $sql .= "AND ((timeofnextaction > 0 AND timeofnextaction < $now) OR ";
                        $sql .= "(IF ((status >= 5 AND status <=8), ($now - lastupdated) > ({$CONFIG['regular_contact_days']} * 86400), 1=2 ) ";  // awaiting
                        $sql .= "OR IF (status='1' OR status='3' OR status='4', 1=1 , 1=2) ";  // active, research, left message - show all
                        $sql .= ") AND timeofnextaction < $now ) ";
                        break;
                    case 3: // All Open
                        $sql .= "AND status!='2' ";
                        break;
                    case 5: // ALL
                    default:
                        break;
                }

                $iresult = mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

                if (mysql_num_rows($iresult) > 0)
                {
                    if ($obj->type == 3 AND !$header_printed)
                    {
                        $html .= "<tr><th colspan='4'>{$GLOBALS['strIncidents']}</th></tr>";
                        $html .= "<tr>";
                        $html .= colheader('id', $GLOBALS['strID']);
                        $html .= colheader('title', $GLOBALS['strTitle']);
                        //$html .= colheader('customer', $GLOBALS['strCustomer']);
                        $html .= colheader('status', $GLOBALS['strStatus']);
                        $html .= "</tr>\n";
                        $header_printed = TRUE;
                    }
                    else if ($obj->type != 3)
                    {
                        $html .= "<tr>";
                        $html .= colheader('id', $GLOBALS['strID']);
                        $html .= colheader('title', $GLOBALS['strTitle']);
                        //$html .= colheader('customer', $GLOBALS['strCustomer']);
                        $html .= colheader('status', $GLOBALS['strStatus']);
                        $html .= "</tr>\n";
                    }

                    $shade = 'shade1';
                    while ($incident = mysql_fetch_object($iresult))
                    {
                        $html .= "<tr class='$shade'>";
                        $html .= "<td>{$incident->id}</td>";
                        $html .= "<td>";
                        $tooltip = "<strong>{$GLOBALS['strCustomer']}:</strong> ".sprintf($GLOBALS['strXofX'], "{$incident->forenames} {$incident->surname}",site_name($incident->siteid));
                        list($update_userid, $update_type, $update_currentowner, $update_currentstatus, $update_body, $update_timestamp, $update_nextaction, $update_id)=incident_lastupdate($incident->id);
                        $update_body = parse_updatebody($update_body);
                        if (!empty($update_body) AND $update_body!='...')
                        {
                            $tooltip .= "<br />{$update_body}";
                        }
                        $html .= html_incident_popup_link($incident->id, $incident->title, $tooltip);
                        $html .= "</td>";
                        $html .= "<td>".incidentstatus_name($incident->status)."</td>";
                        $html .= "</tr>\n";
                        if ($shade=='shade1') $shade='shade2';
                        else $shade='shade1';
                    }
                }
                else
                {
                    if ($obj->type == 3 AND !$header_printed)
                    {
                        $html .= "<tr><th colspan='3'>{$GLOBALS['strIncidents']}</th></tr>";
                        $html .= "<tr><td colspan='3'>{$GLOBALS['strNoIncidents']}</td></tr>\n";
                        $header_printed = TRUE;
                    }
                    else if ($obj->type != 3)
                    {
                        $html .= "<tr><td colspan='3'>{$GLOBALS['strNoIncidents']}</td></tr>\n";
                    }
                }
            }
            if ($obj->type == 3 AND !$header_printed)
            {
                $html .= "</table>\n";
            }

            $previous = $obj->type;
        }
    }
    else
    {
        $html .= user_alert($GLOBALS['strNoRecords'], E_USER_NOTICE);
    }

    return $html;
}


function dashboard_watch_incidents_edit($dashletid)
{
    global $CONFIG, $sit;
    $editaction = $_REQUEST['editaction'];

    switch ($editaction)
    {
        case 'add':
            $type = clean_int($_REQUEST['type']);
            echo "<h2>{$GLOBALS['strWatchAddSet']}</h2>";
            echo "<form id='dwiaddform' action='{$_SERVER['PHP_SELF']}?editaction=do_new&type={$type}' method='post' onsubmit='return false'>";
            echo "<table class='vertical'>";
            echo "<tr><td>";

            switch ($type)
            {
                case '0': //site
                    echo "{$GLOBALS['strSite']}: ";
                    echo site_drop_down('id','');
                    break;
                case '1': //contact
                    echo "{$GLOBALS['strContact']}: ";
                    echo contact_drop_down('id','');
                    break;
                case '2': //engineer
                    echo "{$GLOBALS['strEngineer']}: ";
                    echo user_drop_down('id','',FALSE);
                    break;
                case '3': //Incident
                    echo "{$GLOBALS['strIncident']}:";
                    echo "<input class='textbox' name='id' size='30' />";
                    break;
            }

            echo "</td><tr>";
            echo "</table>";
            echo "<p align='center'>";
            echo dashlet_link('watch_incidents', $dashletid, $GLOBALS['strNew'], 'save', array('editaction' => 'do_new', 'type'=>$type), false, 'dwiaddform');
            echo "</p>";
            break;

        case 'do_new':
            $id =clean_int($_REQUEST['id']);
            $type = clean_int($_REQUEST['type']);
            $sql = "INSERT INTO `{$CONFIG['db_tableprefix']}dashboard_watch_incidents` VALUES ({$sit[2]},'{$type}','{$id}')";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

            if (!$result)
            {
                echo "<p class='error'>{$GLOBALS['strWatchAddFailed']}</p>";
            }
            else
            {
                echo "<p>{$GLOBALS['strAddedSuccessfully']}</p>";
                echo dashlet_link('watch_incidents', $dashletid, $GLOBALS['strBackToList'], '', '', TRUE);
            }
            break;
        case 'delete':
            $id =clean_int($_REQUEST['id']);
            $type = clean_int($_REQUEST['type']);
            $sql = "DELETE FROM `{$CONFIG['db_tableprefix']}dashboard_watch_incidents` WHERE id = '{$id}' AND userid = {$sit[2]} AND type = '{$type}'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

            if (!$result)
            {
                echo "<p class='error'>{$GLOBALS['strWatchDeleteFailed']}</p>";
            }
            else
            {
                echo "<p>{$GLOBALS['strSuccess']}</p>";
                echo dashlet_link('watch_incidents', $dashletid, $GLOBALS['strBackToList'], '', '', TRUE);
            }
            break;
        default:
            echo "<h3>{$GLOBALS['strEditWatchedIncidents']}</h3>";

            echo "<table class='maintable'>";
            for($i = 0; $i < 4; $i++)
            {
                $sql = "SELECT * FROM `{$CONFIG['db_tableprefix']}dashboard_watch_incidents` WHERE userid = {$sit[2]} AND type = {$i}";

                $result = mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

                echo "<tr><td align='left'><strong>";
                switch ($i)
                {
                    case 0: echo $GLOBALS['strSites'];
                        break;
                    case 1: echo $GLOBALS['strContacts'];
                        break;
                    case 2: echo $GLOBALS['strEngineers'];
                        break;
                    case 3: echo $GLOBALS['strIncidents'];
                        break;
                }
                echo "</strong></td><td align='right'>";
                switch ($i)
                {
                    case 0: $linktext = $GLOBALS['strNewSite'];
                        break;
                    case 1: $linktext = $GLOBALS['strNewContact'];
                        break;
                    case 2: $linktext = $GLOBALS['strNewUser'];
                        break;
                    case 3: $linktext = $GLOBALS['strNewIncident'];
                        break;
                }
                echo dashlet_link('watch_incidents', $dashletid, $linktext, 'edit', array('editaction' => 'add', 'type' => $i));
                echo "</td></tr>";

                if (mysql_num_rows($result) > 0)
                {
                    $shade = 'shade1';
                    while ($obj = mysql_fetch_object($result))
                    {
                        $name = '';
                        switch ($obj->type)
                        {
                            case 0: //site
                                $sql = "SELECT name FROM `{$GLOBALS['dbSites']}` WHERE id = {$obj->id}";
                                $iresult = mysql_query($sql);
                                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                                $iobj = mysql_fetch_object($iresult);
                                $name = $iobj->name;
                                break;
                            case 1: //contact
                                $sql = "SELECT forenames, surname FROM `{$GLOBALS['dbContacts']}` WHERE id = {$obj->id}";
                                $iresult = mysql_query($sql);
                                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                                $iobj = mysql_fetch_object($iresult);
                                $name = $iobj->forenames.' '.$iobj->surname;
                                break;
                            case 2: //Engineer
                                $sql = "SELECT realname FROM `{$GLOBALS['dbUsers']}` WHERE id = {$obj->id}";
                                $iresult = mysql_query($sql);
                                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                                $iobj = mysql_fetch_object($iresult);
                                $name = $iobj->realname;
                                break;
                            case 3: //Incident
                                $sql = "SELECT title FROM `{$GLOBALS['dbIncidents']}` WHERE id = {$obj->id}";
                                $iresult = mysql_query($sql);
                                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                                $iobj = mysql_fetch_object($iresult);
                                if ($_SESSION['userconfig']['incident_popup_onewindow'] == 'FALSE')
                                {
                                    $windowname = "incident{$obj->id}";
                                }
                                else
                                {
                                    $windowname = "sit_popup";
                                }
                                $name = html_incident_popup_link($obj->id, "[{$obj->id}] {$iobj->title}");
                                break;
                        }

                        echo "<tr class='$shade'><td>{$name}</td><td>";
                        echo dashlet_link('watch_incidents', $dashletid,
                                          $GLOBALS['strRemove'], 'edit',
                                          array('editaction' => 'delete',
                                          'id' => $obj->id, 'type' => $i));
                        if ($shade == 'shade1') $shade = 'shade2';
                        else $shade = 'shade1';
                    }
                }
                else
                {
                    echo "<tr><td colspan='2'>{$GLOBALS['strNoIncidentsBeingWatchOfType']}</td></tr>";
                }
            }
            echo "</table>";
            break;
    }

    return $html;
}


function dashboard_watch_incidents_upgrade()
{
    $upgrade_schema[2] = "
        -- INL 20May09
       ALTER TABLE `{$CONFIG['db_tableprefix']}dashboard_watch_incidents` CHANGE `userid` `userid` SMALLINT( 6 ) NOT NULL;
    ";

    return $upgrade_schema;
}
function dashboard_watch_incidents_get_version()
{
    global $dashboard_watch_incidents_version;
    return $dashboard_watch_incidents_version;
}


?>
