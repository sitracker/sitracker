<?php
// user_skills.php - Display a list of users skills
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// This Page Is Valid XHTML 1.0 Transitional!  31Oct05


$permission = 14; // View Users

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$sort = cleanvar($_REQUEST['sort']);

$title = $strListSkills;
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$sql  = "SELECT * FROM `{$dbUsers}` WHERE status!=0";  // status=0 means account disabled

// sort users by realname by default
if (empty($sort) OR $sort == "realname")  $sql .= " ORDER BY realname ASC";

$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

echo "<h2>".icon('user', 32)." {$strListSkills} ";
echo icon('skill', 32)."</h2>";

echo "<table align='center' style='width:95%;'>";
echo "<tr>";
echo "<th width='15%'><a href='{$_SERVER['PHP_SELF']}?sort=realname'>{$strName}</a></th>";
echo "<th>{$strQualifications} / {$strSkills}</th>";
echo "</tr>";

// show results
$shade = 'shade1';
while ($users = mysql_fetch_object($result))
{
    echo "<tr class='{$shade}'>";
    echo "<td rowspan='2'><a href=\"mailto:{$users->email}\">{$users->realname}</a><br />";
    echo "{$users->title}</td>";
    echo "<td>";
    if (!empty($users->qualifications)) echo "<strong>{$users->qualifications}</strong>";
    else echo "&nbsp;";
    echo "</td></tr>\n";
    echo "<tr class='{$shade}'>";
    echo "<td>";
    $ssql = "SELECT * FROM `{$dbUserSoftware}` AS us, `{$dbSoftware}` AS s WHERE us.softwareid = s.id AND us.userid='{$users->id}' ORDER BY s.name ";
    $sresult = mysql_query($ssql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $countskills = mysql_num_rows($sresult);
    $nobackup = 0;
    if ($countskills >= 1)
    {
        $c = 1;
        while ($software = mysql_fetch_object($sresult))
        {
            echo "{$software->name}";
            if ($software->backupid > 0) echo " <em style='color: #555;'>(".user_realname($software->backupid, TRUE). ")</em>";
            if ($software->backupid == 0) $nobackup++;
            if ($c < $countskills) echo ", ";
            else
            {
                echo "<br /><br />&bull; $countskills ".$strSkills;
                if (($nobackup + 1) >= $countskills)
                {
                    echo ", <strong>{$strNoSubstitutes}</strong>.";
                }
                elseif ($nobackup > 0)
                {
                    echo ", <strong>".sprintf($strNeedsSubstitueEngineers, $nobackup)."</strong>.";
                }
            }
            $c++;
        }
    }
    else
    {
        echo "&nbsp;";
    }

    if ($users->id == $sit[2]) echo " <a href='edit_user_skills.php'>{$strMySkills}</a>";

    echo "</td>";
    echo "</tr>\n";

    if ($shade == 'shade1') $shade = 'shade2';
    else $shade = 'shade1';
}
echo "</table>\n";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>