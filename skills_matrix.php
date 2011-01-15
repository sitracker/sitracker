<?php
// skills_matrix.php - Skills matrix page
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
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

$legacy = cleanvar($_REQUEST['legacy']);
$groupid = clean_int($_REQUEST['gid']);

// By default show users in home group
if ($groupid == 'all') $filtergroup = 'all';
elseif ($groupid == '') $filtergroup = $_SESSION['groupid'];
else $filtergroup = $groupid;

$title = $strSkillsMatrix;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('skill', 32)." ";
echo "$title</h2>";
echo "<p align='center'>{$strDisplay}: ";
if (empty($legacy)) echo "<a href='{$_SERVER['PHP_SELF']}?legacy=yes&amp;gid={$groupid}'>{$strAll}</a>";
else echo "<a href='{$_SERVER['PHP_SELF']}?gid={$groupid}'>{$strActive}</a>";
echo "</p>";

$gsql = "SELECT * FROM `{$dbGroups}` ORDER BY name";
$gresult = mysql_query($gsql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
while ($group = mysql_fetch_object($gresult))
{
    $grouparr[$group->id] = $group->name;
}
$numgroups = count($grouparr);
if ($numgroups >= 1)
{
    echo "<form id='skillsmatrixform' action='{$_SERVER['PHP_SELF']}' method='get'>";
    echo "{$strGroup}: <select name='choosegroup' onchange='window.location.href=this.options[this.selectedIndex].value'>";
    echo "<option value='{$_SERVER['PHP_SELF']}?gid=all";
    if (empty($legacy)) echo "'";
    else echo "&amp;legacy=yes'";
    if ($filtergroup == 'all') echo " selected='selected'";
    echo ">{$strAll}</option>\n";
    foreach ($grouparr AS $groupid => $groupname)
    {
        echo "<option value='{$_SERVER['PHP_SELF']}?gid={$groupid}";
        if (empty($legacy)) echo "'";
        else echo "&amp;legacy=yes'";
        if ($groupid == $filtergroup) echo " selected='selected'";
        echo ">$groupname</option>\n";
    }
    echo "<option value='{$_SERVER['PHP_SELF']}?gid=0";
    if (empty($legacy)) echo "'";
    else echo "&amp;legacy=yes'";
    if ($filtergroup == '0') echo " selected='selected'";
    echo ">{$strUsersWithNoGroup}</option>\n";
    echo "</select>\n";
    echo "</form>\n<br />";
}

$sql = "SELECT u.id, u.realname, s.name ";
$sql .= "FROM `{$dbUserSoftware}` AS us RIGHT JOIN `{$dbSoftware}` AS s ON (us.softwareid = s.id) ";
$sql .= "LEFT JOIN `{$dbUsers}` AS u ON us.userid = u.id ";
$sql .= " WHERE (u.status <> 0 OR u.status IS NULL) ";
if (empty($legacy)) $sql .= "AND (s.lifetime_end > NOW() OR s.lifetime_end = '0000-00-00' OR s.lifetime_end is NULL) ";
if ($numgroups >= 1 AND $filtergroup == '0') $sql .= "AND (u.groupid='0' OR u.groupid='' OR u.groupid IS NULL) ";
elseif ($numgroups < 1 OR $filtergroup == 'all') { $sql .= "AND 1=1 "; }
else $sql .= "AND (u.groupid='{$filtergroup}' OR u.groupid IS NULL)";
$sql .= " GROUP BY u.id ORDER BY u.realname";

$usersresult = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

$countusers = mysql_num_rows($usersresult);

if ($countusers > 0)
{
    while ($row = mysql_fetch_object($usersresult))
    {
        if (($row->realname != NULL) AND ($row->realname != ''))
        {
            $users[$row->id] = $row->realname;
            $counting[$row->realname] = 0;
        }
        else
        {
            $countusers--;
        }
    }
    mysql_data_seek($usersresult, 0);
    $sql = "SELECT u.id, u.realname, s.name ";
    $sql .= "FROM `{$dbUserSoftware}` AS us RIGHT JOIN `{$dbSoftware}` AS s ON (us.softwareid = s.id) ";
    $sql .= "LEFT JOIN `{$dbUsers}` AS u ON us.userid = u.id ";
    $sql .= " WHERE (u.status <> 0 OR u.status IS NULL) ";
    if (empty($legacy))
    {
        $sql .= "AND (s.lifetime_end > NOW() OR s.lifetime_end = '0000-00-00' OR s.lifetime_end is NULL) ";
    }

    if ($numgroups >= 1 AND $filtergroup == '0')
    {
    	$sql .= "AND (u.groupid='0' OR u.groupid='' OR u.groupid IS NULL) ";
    }
    elseif ($numgroups < 1 OR $filtergroup == 'all')
    {
        $sql .= "AND 1=1 ";
    }
    else
    {
        $sql .= "AND (u.groupid='{$filtergroup}' OR u.groupid IS NULL)";
    }
    $sql .= " ORDER BY s.name, u.id";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $countskills = mysql_num_rows($result);
}

if ($countskills > 0 AND $countusers > 0)
{
    $previous = '';
    while ($row = mysql_fetch_object($result))
    {
        if (($row->realname != NULL) AND ($row->realname != ''))
        {
            $skills[$row->name][$row->realname] = $row->realname;
        }
    }
    mysql_data_seek($result, 0);
    echo "<table align='center' class='vertical'>";
    $shade = 'shade1';
    echo "<thead><tr><td>{$strSkill}</td>";
    foreach ($users AS $u)
    {
        echo "<th>$u</th>";
    }
    echo "<th>{$strTotal}</th>";
    echo "</tr></thead>\n";
    $previous = '';
    while ($row = mysql_fetch_object($result))
    {
        if ($previous != $row->name)
        {
            $count = 0;
            echo "<tr><th width='20%;'>{$row->name}</th>";
            while ($user = mysql_fetch_object($usersresult))
            {
                if (($user->realname != NULL) AND ($user->realname != ''))
                {
                    //todo get the proper symbol for a cross
                    if (empty($skills[$row->name][$user->realname]))
                    {
                        // No skill in this software
                        echo "<td align='center' class='{$shade}'></td>"; // &#215;
                    }
                    else
                    {
                        //Skill in software
                        // echo "<td align='center'>&#10004;</td>"; // Doesn't work in Windows (fonts!) rubbishy O/S
                        echo "<td align='center' class='{$shade}'>";
                        echo icon('tick', 16)."</td>";
                        $counting[$user->realname]++;
                        $count++;
                    }
                }
            }
            echo "<td align='center' class='{$shade}'><strong>{$count}</strong></td>";
            echo "</tr>\n";
            $started = true;
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        mysql_data_seek($usersresult, 0);
        $previous = $row->name;
    }
    echo "<tr><th align='right'>{$strTotal}</th>";
    foreach ($counting AS $c)
    {
        echo "<td align='center'><strong>{$c}</strong></td>";
    }
    echo "</tr>\n";
    echo "</table>";
}
else
{
    echo "<p align='center'>{$strNothingToDisplay}</p>";
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>