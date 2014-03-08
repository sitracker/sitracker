<?php
// incoming.inc.php - Displays tempincoming data
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// Included by ../incident.php

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}
$incomingid = clean_int($_REQUEST['id']);
$action = clean_fixed_list($_REQUEST['action'], array('','updatereason'));

if ($action == "updatereason")
{
    $newreason = clean_dbstring($_REQUEST['newreason']);
    $updatetime = date('Y-m-d H:i:s',$now);
    $update = "UPDATE `{$dbTempIncoming}` SET reason='{$newreason}', ";
    $update .= "reason_user='{$sit['2']}', reason_time='{$updatetime}' WHERE id={$incomingid}";
    $result = mysql_query($update);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    unset($result);
}

$sql = "SELECT * FROM `{$dbTempIncoming}` WHERE id='{$incomingid}' LIMIT 1";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
if (mysql_num_rows($result) > 0)
{
    $incoming = @mysql_fetch_object($result);

    $lockedbyyou = false;
    
    if (!$incoming->locked)
    {
        //it's not locked, lock for this user
        $lockeduntil = date('Y-m-d H:i:s', $now + $CONFIG['record_lock_delay']);
        $sql = "UPDATE `{$dbTempIncoming}` SET locked='{$sit[2]}', lockeduntil='{$lockeduntil}' WHERE id='{$incomingid}' AND (locked = 0 OR locked IS NULL)";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        $lockedbyname = $strYou;
        $lockedbyyou = true;
    }
    elseif ($incoming->locked != $sit[2])
    {
        $lockedby = $incoming->locked;
        $lockedbysql = "SELECT realname FROM `{$dbUsers}` WHERE id={$lockedby}";
        $lockedbyresult = mysql_query($lockedbysql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        while ($row = mysql_fetch_object($lockedbyresult))
        {
            $lockedbyname = $row->realname;
        }
    }
    else
    {
        $lockedbyname = $strYou;
        $lockedbyyou = true;
    }

    echo "<div class='detailinfo'>";
    if ($lockedbyyou)
    {
        echo "<div class='detaildate'>";
        echo "<form method='post' action='{$_SERVER['PHP_SELF']}?id={$incomingid}&win=incomingview&action=updatereason'>";
        echo "{$strMessage}: <input name='newreason' type='text' value=\"{$incoming->reason}\" size='25' maxlength='100' />";
        echo "<input type='submit' value='{$strSave}' />";
        echo "</form>";
        echo "</div>";
    }
    else
    {
        echo "<div class='detaildate'>{$incoming->reason}</div>";
    }

    echo icon('locked', 16, $strLocked);
    echo " ".sprintf($strLockedByX, $lockedbyname)."</div>";

    $usql = "SELECT * FROM `{$dbUpdates}` WHERE id='{$incoming->updateid}'";
    $uresult = mysql_query($usql);
    while ($update = mysql_fetch_object($uresult))
    {
        $updatetime = readable_date($update->timestamp);
        $updatebody = preg_replace("/\[\[att=(.*?)\]\](.*?)\[\[\/att\]\]/s", "<a href='download.php?id=$1'>$2</a>", $update->bodytext);

        echo "<div class='detailhead'><div class='detaildate'>{$updatetime}</div>";
        echo icon('emailin', 16);
        echo " {$strFrom} <strong>{$incoming->emailfrom}</strong></div>";
        echo "<div class='detailentry'>";
        echo parse_updatebody($updatebody, FALSE);
        echo "</div>";
    }
}
else
{
    echo user_alert($strNoRecords, E_USER_NOTICE);
}

unset($result);
unset($uresult);

?>