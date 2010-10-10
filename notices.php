<?php
// notices.php - modify and add global notices
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Kieran Hogg[at]users.sourceforge.net>



$permission = 78;
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'auth.inc.php');
include (APPLICATION_INCPATH . 'htmlheader.inc.php');


$action = cleanvar($_REQUEST['action']);
if ($action == 'new')
{
    echo "<h2>".icon('info', 32)." {$strNotices}</h2>";
    echo "<p align='center'>{$strNoticesBlurb}</p>";
    echo "<div align='center'><form action='{$_SERVER[PHP_SELF]}?action=post' method='post'>";
    echo "<table align='center'>";
    echo "<tr><th><h3>{$strNotice}</h3></th>";
    echo "<td>";
    echo bbcode_toolbar('noticetext');
    echo "<input type='text' id='noticetext' size='60' maxlength='255' name='text' /><br />";
    echo "</td></tr>";
    echo "<tr><th><label for='durability'>{$strDurability}:</label></th>";
    echo "<td><select name='durability'><option value='sticky'>{$strSticky}</option><option value='session'>{$strSession}</option></select></td></tr>";
    echo "<tr><th><label for='type'>{$strType}:</label></th>";
    echo "<td><select name='type'><option value='".NORMAL_NOTICE_TYPE."'>{$strInfo}</option><option value='".WARNING_NOTICE_TYPE."'>{$strWarning}</option></select></td></tr>";
    echo "</table>";
    echo "<input type='submit' value='{$strSave}' />";
    echo "</form></div>";
    echo "<p align='center'><a href='notices.php'>{$strReturnWithoutSaving}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == 'post')
{
    $text = cleanvar($_REQUEST['text']);
    $type = clean_int($_REQUEST['type']);
    $durability = cleanvar($_REQUEST['durability']);
    $gid = md5($text);

    //post new notice
    $sql = "SELECT id FROM `{$dbUsers}` WHERE status != 0";
    $result = mysql_query($sql);

    //do this once so we can get a referenceID
    $user = mysql_fetch_object($result);
    $sql = "INSERT INTO `{$dbNotices}` (userid, type, text, timestamp, durability) ";
    $sql .= "VALUES({$user->id}, {$type}, '{$text}', NOW(), '{$durability}')";
    mysql_query($sql);
    if (mysql_error())
    {
        trigger_error(mysql_error(),E_USER_WARNING);
    }
    else
    {
        $refid = mysql_insert_id();
        $sql = "UPDATE `$dbNotices` SET referenceid='{$refid}' WHERE id='{$refid}'";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

        while ($user = mysql_fetch_object($result))
        {
            $sql = "INSERT INTO `{$dbNotices}` (userid, referenceid, type, text, timestamp, durability) ";
            $sql .= "VALUES({$user->id}, '{$refid}', {$type}, '{$text}', NOW(), '{$durability}')";
            mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        }
        html_redirect('notices.php');
    }
}
elseif ($action == 'delete')
{
    $noticeid = clean_int($_REQUEST['id']);

    $sql = "SELECT referenceid, type FROM `{$dbNotices}` WHERE id='{$noticeid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $noticeobj = mysql_fetch_object($result);

    $sql = "DELETE FROM `{$dbNotices}` WHERE referenceid='{$noticeobj->referenceid}' ";
    $sql .= "AND type='{$noticeobj->type}' ";
    mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    html_redirect('notices.php');
}
else
{
    echo "<h2>".icon('info', 32)." {$strNotices}</h2>";

    //get all notices
    $sql = "SELECT * FROM `{$dbNotices}` WHERE type=".NORMAL_NOTICE_TYPE." OR type=".WARNING_NOTICE_TYPE." ";
    $sql .= "GROUP BY referenceid";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $shade = 'shade1';
    if (mysql_num_rows($result) > 0)
    {
        echo "<table align='center'>";
        echo "<tr><th>{$strID}</th><th>{$strDate}</th><th>{$strNotice}</th><th>{$strOperation}</th></tr>\n";
        while ($notice = mysql_fetch_object($result))
        {
            echo "<tr class='$shade'><td>{$notice->id}</td><td>{$notice->timestamp}</td>";
            echo "<td>".bbcode($notice->text)."</td>";
            echo "<td>";
            echo "<a href='{$_SERVER[PHP_SELF]}?action=delete&amp;id=";
            echo "{$notice->id}'>{$strRevoke}</a>".help_link('RevokeNotice');
            echo "</td></tr>\n";
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        echo "</table>\n";
    }
    else
    {
        echo "<p align='center'>{$strNoRecords}</p>";
    }

    echo "<p align='center'><a href='{$_SERVER[PHP_SELF]}?action=new'>{$strPostNewNotice}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>