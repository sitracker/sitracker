<?php
// edit_backup_users.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!   3Nov05
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

if (empty($_REQUEST['user'])
    OR $_REQUEST['user'] == 'current'
    OR $_REQUEST['user'] == $_SESSION['userid']
    OR $_REQUEST['userid'] == $_SESSION['userid'])
{
    $permission = PERM_MYSKILLS_SET; // Edit your software skills
}
else
{
    $permission = PERM_USER_SKILLS_SET; // Manage users software skills
}

// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

// Valid user with Permission
// External variables
$save = clean_fixed_list($_REQUEST['save'], array('', 'save'));
$title = $strDefineSubstituteEngineer;

if (empty($save))
{
    // External variables
    if (empty($_REQUEST['user']) OR $_REQUEST['user'] == 'current')
    {
        $user = clean_int($sit[2]);
    }
    else
    {
        $user = clean_int($_REQUEST['user']);
    }

    $default = clean_int($_REQUEST['default']);
    $softlist = cleanvar($_REQUEST['softlist']);

    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('user', 32)." ".sprintf($strDefineSubstituteEngineersFor, user_realname($user,TRUE))."</h2>\n";
    plugin_do('edit_backup_users');
    echo "<form name='def' action='{$_SERVER['PHP_SELF']}' method='post'>";
    echo "<input type='hidden' name='user' value='{$user}' />";
    echo "<p align='center'>{$strDefaultSubstitute}: ";
    user_drop_down('default', $default, FALSE, $user, "onchange='javascript:this.form.submit();'");
    echo "</p>";
    echo "</form>";

    $sql = "SELECT * FROM `{$dbUserSoftware}` AS us, `{$dbSoftware}` AS s ";
    $sql .= "WHERE us.softwareid = s.id AND userid='{$user}' ORDER BY name";
    $result = mysql_query($sql);
    $countsw = mysql_num_rows($result);

    if ($countsw >= 1)
    {
        echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>\n";
        echo "<table class='maintable'>\n";
        echo "<tr><th>{$strSkill}</th><th>{$strSubstitute}</th></tr>";
        $class = 'shade1';
        while ($software = mysql_fetch_object($result))
        {
            echo "<tr class='$class'>";
            echo "<td><strong>{$software->id}</strong>: {$software->name}</td>";
            if ($software->backupid == 0)
            {
                $software->backupid = $default;
            }

            echo "<td>".software_backup_dropdown('backup[]', $user, $software->id, $software->backupid)."</td>";
            echo "</tr>\n";
            if ($class == 'shade2') $class = "shade1";
            else $class = "shade2";
            flush();
            $softarr[] = $software->id;
        }
        $softlist = implode(',',$softarr);
        plugin_do('edit_backup_users_form');
        echo "</table>\n";
        echo "<input type='hidden' name='user' value='{$user}' />";
        echo "<input type='hidden' name='softlist' value='{$softlist}' />";
        echo "<input type='hidden' name='save' value='vqvbgf' />";
        echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> <input type='submit' value='{$strSave}' /></p>";
        echo "</form>";
    }
    else
    {
        echo user_alert($strNoSkillsDefined, E_USER_WARNING);
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    // External variables
    $softlist = explode(',',cleanvar($_REQUEST['softlist']));
    $backup = clean_int($_REQUEST['backup']);
    $user = clean_int($_REQUEST['user']);

    // If user variable is zero edit your own
    if ($user < 1) 
    {
        $user = clean_int($sit[2]);
    }
    plugin_do('edit_backup_users_submitted');

    foreach ($backup AS $key=>$backupid)
    {
        if ($backupid > 0)
        {
            $softlist[$key] = clean_int($softlist[$key]);
            $sql = "UPDATE `{$dbUserSoftware}` SET backupid='{$backupid}' WHERE userid='{$user}' AND softwareid='{$softlist[$key]}' LIMIT 1 ";
        }
        // echo "{$softlist[$key]} -- $key -- $value<br />";
        //echo "$sql <br />";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    }
    plugin_do('edit_backup_users_saved');
    if ($user == $sit[2]) html_redirect("edit_user_skills.php", TRUE);
    else html_redirect("manage_users.php");
}

?>