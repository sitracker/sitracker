<?php
// view_tags.php - Page to view the tags on either a record or in general
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paulheaney[at]users.sourceforge.net>


$permission = 0; // not required
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$tagid = clean_int($_REQUEST['tagid']);
$orderby = cleanvar($_REQUEST['orderby']);

if (empty($orderby)) $orderby = "name";

if (empty($tagid))
{
    //show all tags
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('tag', 32)." ";
    echo "{$strTags}</h2>";
    echo show_tag_cloud($orderby, TRUE);
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    $sql = "SELECT name FROM `{$dbTags}` WHERE tagid = '{$tagid}' LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($tagname) = mysql_fetch_row($result);

    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('tag', 32)." <a href='view_tags.php'>{$strTag}</a>: {$tagname}";
    if (array_key_exists($tagname, $CONFIG['tag_icons']))
    {
        echo "&nbsp;<img src='images/icons/{$iconset}/32x32/{$CONFIG['tag_icons'][$tagname]}.png' alt='' />";
    }
    echo "</h2>";


    //show only this tag
    $sql = "SELECT * FROM `{$dbSetTags}` WHERE tagid = '{$tagid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $col = 0;
    $count = 0;
    $num_tags = mysql_num_rows($result);
    if ($num_tags > 0)
    {
        echo "<table align='center'>";
        while ($obj = mysql_fetch_object($result))
        {
            if ($col == 0) echo "<tr style='text-align: left;'>";

            switch ($obj->type)
            {
                case TAG_CONTACT: //contact
                    $sql = "SELECT forenames, surname FROM `{$dbContacts}` WHERE id = '{$obj->id}'";
                    $resultcon = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    if (mysql_num_rows($resultcon) > 0)
                    {
                        $objcon = mysql_fetch_object($resultcon);
                        echo "<th>".icon('contact', 16)." {$strContact}</th><td><a href='contact_details.php?id={$obj->id}'>";
                        echo "{$objcon->forenames} {$objcon->surname}</a></td>";
                    }
                    break;
                case TAG_INCIDENT: //incident
                    $sql = "SELECT title FROM `{$dbIncidents}` WHERE id = '{$obj->id}'";
                    $resultinc = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    if (mysql_num_rows($resultinc) > 0)
                    {
                        $objinc = mysql_fetch_object($resultinc);
                        echo "<th>".icon('support', 16)." {$strIncident}</th><td>".html_incident_popup_link($obj->id, "{$obj->id}: {$objinc->title}")."</td>";
                    }
                    break;
                case TAG_SITE: //site
                    $sql = "SELECT name FROM `{$dbSites}` WHERE id = '{$obj->id}'";
                    $resultsite = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    if (mysql_num_rows($resultsite) > 0)
                    {
                        $objsite = mysql_fetch_object($resultsite);
                        echo "<th>".icon('site', 16)." {$strSite}</th><td><a href='site_details.php?id={$obj->id}&amp;action=show'>";
                        echo "{$objsite->name}</a></td>";
                    }
                    break;
                case TAG_TASK: // task
                    $sql = "SELECT name FROM `{$dbTasks}` WHERE id = '{$obj->id}'";
                    $resulttask = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    if (mysql_num_rows($resulttask) > 0)
                    {
                        $objtask = mysql_fetch_object($resulttask);
                        echo "<th>".icon('task', 16)." {$strTask}</th><td><a href='view_task.php?id={$obj->id}'>";
                        echo "{$objtask->name}</a></td>";
                    }
                    break;
                case TAG_SKILL:
                    $sql = "SELECT name FROM `{$dbSoftware}` WHERE id = '{$obj->id}'";
                    $resultskill = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    if (mysql_num_rows($resultskill) > 0)
                    {
                        $objtask = mysql_fetch_object($resultskill);
                        echo "<th>".icon('skill', 16)." {$strSkill}</th><td>";
                        echo "{$objtask->name}</td>";
                    }
                    break;
                case TAG_PRODUCT:
                    $sql = "SELECT name FROM `{$dbProducts}` WHERE id = '{$obj->id}'";
                    $resultprod = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                    if (mysql_num_rows($resultprod) > 0)
                    {
                        $objtask = mysql_fetch_object($resultprod);
                        echo "<th>".icon('product', 16)." {$strProduct}</th>";
                        echo "<td><a href='products.php?productid={$obj->id}'>{$objtask->name}</a></td>";
                    }
                    break;
                default:
                    echo "<th>{$strOther}</th><td>{$obj->id}/{$obj->type}</td>";
            }
            $col++;
            $count++;
            if ($col >= 3 OR $count == $num_tags)
            {
                echo "</tr>\n";
                $col = 0;
            }
        }
        echo "</table>";
        echo "<p align='center'>".sprintf($strTagsMulti, $num_tags)."</p>";
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>