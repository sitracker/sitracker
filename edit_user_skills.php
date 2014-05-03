<?php
// edit_user_skills.php - Form to set users skills
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

if (empty($_REQUEST['user'])
    OR $_REQUEST['user'] == 'current'
    OR $_REQUEST['userid'] == $_SESSION['userid'])
{
    $permission = PERM_MYSKILLS_SET; // Edit your software skills
}
else
{
    $permission = PERM_USER_SKILLS_SET; // Manage users software skills
}

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External Variables
$submit = cleanvar($_REQUEST['submit']);
if (empty($_REQUEST['user']) || $_REQUEST['user'] == 'current') $user = $sit[2];
else $user = clean_int($_REQUEST['user']);

$title = $strEditUserSkills;

if (empty($submit))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    $sql = "SELECT * FROM `{$dbUserSoftware}` AS us, `{$dbSoftware}` AS s ";
    $sql .= "WHERE us.softwareid = s.id AND userid = '{$user}' ORDER BY name";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) >= 1)
    {
        while ($software = mysql_fetch_object($result))
        {
            $expertise[] = $software->id;
        }
    }
    echo "<h2>".icon('skill', 32)." ";
    echo sprintf($strSkillsFor, user_realname($user,TRUE))."</h2>";
    plugin_do('edit_user_skills');
    echo "<p align='center'>{$strSelectYourSkills}</p>";
    echo "<form name='softwareform' id='softwareform' action='{$_SERVER['PHP_SELF']}' method='post' ";
    echo "onsubmit=\"populateHidden(document.softwareform.elements['expertise[]'], document.softwareform.choices); populateHidden(document.softwareform.elements['noskills[]'], document.softwareform.ns)\">";
    echo "<table class='maintable'>";
    echo "<tr><th>{$strNOSkills}</th><th>&nbsp;{$strActions}&nbsp;</th><th>{$strHAVESkills}</th></tr>";
    echo "<tr><td align='center' class='shade1'>";
    echo "\n<select name='noskills[]' multiple='multiple' size='20' style='width: 100%; min-width: 300px; min-height: 200px;'>";
    $sql = "SELECT * FROM `{$dbSoftware}` ORDER BY name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $count = 0;
    if (mysql_num_rows($result) > 0)
    {
        while ($software = mysql_fetch_object($result))
        {
            if (is_array($expertise))
            {
                if (!in_array($software->id,$expertise))
                {
                    echo "<option value='{$software->id}'>{$software->name}</option>\n";
                    $count++;
                }
            }
            else
            {
                echo "<option value='{$software->id}'>{$software->name}</option>\n";
                $count++;
            }
        }
    }
    if ($count < 1)
    {
        echo "<option value=''>{$strNone}</option>"; // Always have at least one option for valid HTML
    }
    echo "</select>\n";
    echo "</td>";
    echo "<td class='shade2'  style='text-align:center;'>";
    echo "<input type='button' value='&gt;' title='Add Selected' onclick=\"deleteBlankOption(this.form.elements['expertise[]']);copySelected(this.form.elements['noskills[]'],this.form.elements['expertise[]'])\" /><br />";
    echo "<input type='button' value='&lt;' title='Remove Selected' onclick=\"deleteBlankOption(this.form.elements['noskills[]']);copySelected(this.form.elements['expertise[]'],this.form.elements['noskills[]'])\" /><br />";
    echo "<input type='button' value='&gt;&gt;' title='Add All' onclick=\"deleteBlankOption(this.form.elements['expertise[]']);copyAll(this.form.elements['noskills[]'],this.form.elements['expertise[]'])\" /><br />";
    echo "<input type='button' value='&lt;&lt;' title='Remove All' onclick=\"deleteBlankOption(this.form.elements['noskills[]']);copyAll(this.form.elements['expertise[]'],this.form.elements['noskills[]'])\" /><br />";
    echo "</td>";
    echo "<td class='shade1' style='width: 300px;'>";
    echo "<select name='expertise[]' multiple='multiple' size='20' style='width: 100%;  min-width: 300px; min-height: 200px;'>";
    $sql = "SELECT * FROM `{$dbUserSoftware}` AS us, `{$dbSoftware}` AS s ";
    $sql .= "WHERE us.softwareid = s.id AND userid = '{$user}' ORDER BY name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        while ($software = mysql_fetch_object($result))
        {
            echo "<option value='{$software->id}'>{$software->name}</option>\n";
        }
    }
    else
    {
        echo "<option value=''>{$strNone}</option>"; // Always have at least one option for valid HTML
    }
    echo "</select>";
    echo "<input type='hidden' name='user' value='{$user}' />";
    echo "</td></tr>\n";
    plugin_do('edit_user_skills_form');
    echo "</table>";
    echo "<input type='hidden' name='choices' id='choices' />";
    echo "<input type='hidden' name='ns' id='ns' />";

    echo "<p class='formbuttons'>";
    echo "<input name='reset' type='reset' value='{$strReset}' />";
    echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "</form>\n";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    // Update user profile
    $selections = urldecode($_POST['choices']);
    $expertise = explode(';', $selections);

    $ns = urldecode($_POST['ns']);
    $noskills = explode(';', $ns);

    plugin_do('edit_user_skills_submitted');

    // Mantis 1855 and 1856 need resolving to handle users removing skills

    $user = clean_int($user);

    if (is_array($expertise))
    {
        $expertise = array_unique($expertise);
        foreach ($expertise AS $value)
        {
            $value = clean_int($value);
            if (!empty($value))
            {
                $checksql = "SELECT userid FROM `{$dbUserSoftware}` WHERE userid='{$user}' AND softwareid='{$value}' LIMIT 1";
                $checkresult = mysql_query($checksql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                if (mysql_num_rows($checkresult)< 1)
                {
                    if ($value > 0)
                    {
                        $sql = "INSERT INTO `{$dbUserSoftware}` (userid, softwareid) VALUES ('{$user}', '{$value}')";
                        mysql_query($sql);
                        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                    }
                }
                $softlist[] = $value;
            }
        }
    }

    // Make sure we're not being backup support for all the software we have no skills in.
    if (is_array($noskills))
    {
        $noskills = array_unique($noskills);
        foreach ($noskills AS $value)
        {
            $value = clean_int($value);
            // Remove the software listed that we don't support
            $sql = "DELETE FROM `{$dbUserSoftware}` WHERE userid='{$user}' AND softwareid='{$value}' LIMIT 1";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            // If we are providing backup for a skill we don't have - reset that back to nobody providing backup
            $sql = "UPDATE `{$dbUserSoftware}` SET backupid='0' WHERE backupid='{$user}' AND softwareid='{$value}' LIMIT 1";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        }
    }

    plugin_do('edit_user_skills_saved');
    journal(CFG_LOGGING_MAX, 'Skillset Updated', "Users Skillset was Changed", CFG_JOURNAL_USER, 0);

    // Have a look to see if any of the software we support is lacking a backup/substitute engineer
    $sql = "SELECT userid FROM `{$dbUserSoftware}` WHERE userid='{$user}' AND backupid='0' LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $lacking = mysql_num_rows($result);
    if ($lacking >= 1)
    {
        html_redirect("edit_backup_users.php?user={$user}", TRUE, $strYouShouldNowDefineSubstituteEngineers);
    }
    else
    {
        if ($user == $sit[2]) html_redirect("edit_user_skills.php?user={$user}");
        else html_redirect("manage_users.php");
    }
}
?>