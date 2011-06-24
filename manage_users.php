<?php
// manage_users.php - Overview of users, with links to managing them
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional! 16Nov05


$permission = PERM_ADMIN; // Administrate
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strManageUsers;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$sort = $_REQUEST['sort'];

$sql  = "SELECT *,u.id AS userid FROM `{$dbUsers}` AS u, `{$dbRoles}` AS r ";
$sql .= "WHERE u.roleid = r.id ";

// sort users by realname by default
if (!isset($sort) || $sort == "realname") $sql .= " ORDER BY IF(status> 0,1,0) DESC, realname ASC";
else if ($sort == "username") $sql .= " ORDER BY IF(status> 0,1,0) DESC, username ASC";
else if ($sort == "role") $sql .= " ORDER BY roleid ASC";
else if ($sort == "jobtitle") $sql .= " ORDER BY title ASC";
else if ($sort == "email") $sql .= " ORDER BY email ASC";
else if ($sort == "phone") $sql .= " ORDER BY phone ASC";
else if ($sort == "fax") $sql .= " ORDER BY fax ASC";
else if ($sort == "status")  $sql .= " ORDER BY status ASC";
else if ($sort == "accepting") $sql .= " ORDER BY accepting ASC";

$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

echo "<h2>".icon('user', 32)." {$strManageUsers}</h2>";
echo "<p class='contextmenu' align='center'>";
$operations = array();
$operations[$strNewUser] = 'user_new.php?action=showform';
$operations[$strRolePermissions] = 'edit_user_permissions.php';
$operations[$strUserGroups] = 'usergroups.php';
echo html_action_links($operations);
echo "</p>";
echo "<table align='center'>";
echo "<tr>";
echo "<th><a href='{$_SERVER['PHP_SELF']}?sort=realname'>{$strName}</a> ";
echo "(<a href='{$_SERVER['PHP_SELF']}?sort=username'>{$strUsername}</a>)</th>";
echo "<th><a href='{$_SERVER['PHP_SELF']}?sort=email'>{$strEmail}</a></th>";
echo "<th><a href='{$_SERVER['PHP_SELF']}?sort=role'>{$strRole}</a></th>";
echo "<th>{$strStatus}</th>";
if ($CONFIG['use_ldap'])
{
    echo "<th>{$strSource}".help_link('UserSource')."</th>";
}
echo "<th>{$strActions}</th>";

echo "</tr>\n";

// show results
$class = 'shade1';
while ($users = mysql_fetch_object($result))
{
    // define class for table row shading
    if ($users->status == 0) $class = 'expired';
    // print HTML
    echo "<tr class='{$class}' onclick='trow(event);'>\n";
    echo "<td>{$users->realname}";
    echo " (";
    if ($users->userid == 1) echo "<strong>";
    echo "{$users->username}";
    if ($users->userid == 1) echo "</strong>";
    echo ")</td>";

    echo "<td>";
    echo $users->email;
    echo "</td>";

    echo "<td>{$users->rolename}</td>";
    echo "<td>";
    if (user_permission($sit[2],57))
    {
        if ($users->status > 0) echo "{$strEnabled}";
        else echo "{$strDisabled}";
    }
    else echo "-";

    echo "</td>";
    if ($CONFIG['use_ldap'])
    {
        echo "<td>";
        if ($users->user_source == 'sit')
        {
            echo $CONFIG['application_shortname'];
        }
        elseif ($users->user_source == 'ldap')
        {
            echo $strLDAP;
        }
        else
        {
            echo $strUnknown;
        }
    }
    echo "</td>";
    echo "<td>";
    $operations = array();
    $operations[$strEdit] = "user_profile_edit.php?userid={$users->userid}";
    if ($users->status > 0)
    {
        if ($users->userid > 1 AND $users->user_source == 'sit')
        {
            $operations[$strResetPassword] = "forgotpwd.php?action=sendpwd&amp;userid={$users->userid}";
        }
        $operations[$strSetSkills] = "edit_user_skills.php?user={$users->userid}";
        $operations[$strSetSubstitutes] = "edit_backup_users.php?user={$users->userid}";
        if ($users->userid > 1)
        {
            $operations[$strPermissions] = "edit_user_permissions.php?action=edit&amp;user={$users->userid}";
        }
    }
    echo html_action_links($operations);
    echo "</td>";

    echo "</tr>\n";
    // invert shade
    if ($class == 'shade2') $class = "shade1";
    else $class = "shade2";

}
echo "</table>\n";

// free result and disconnect
mysql_free_result($result);

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>
