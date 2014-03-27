<?php
// delete_user.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Valdemaras Pipiras <info[at]ambernet.lt>
//          Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// TODO 3.40 if we user MySQL 5's relation functions, we can simply delete the user

require ('core.php');
$permission = PERM_USER_DELETE;
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$userid = clean_int($_REQUEST['userid']);

if (!empty($userid))
{
    $errors = 0;
    // Check there are no files linked to this user
    $sql = "SELECT userid FROM `{$dbFiles}` WHERE userid={$userid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) >= 1) $errors++;

    // check there are no links linked to this product
    $sql = "SELECT userid FROM `{$dbLinks}` WHERE userid={$userid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) >= 1) $errors++;

    // check there are no notes linked to this product
    $sql = "SELECT userid FROM `{$dbNotes}` WHERE userid={$userid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) >= 1) $errors++;

    // Check there is no software linked to this user
    $sql = "SELECT softwareid FROM `{$dbUserSoftware}` WHERE userid={$userid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) >= 1) $errors++;

    // Check there are no incidents linked to this user
    $sql = "SELECT id FROM `{$dbIncidents}` WHERE owner={$userid} OR towner={$userid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) >= 1) $errors++;

    // Check there are no updates by this user
    $sql = "SELECT id FROM `{$dbUpdates}` WHERE userid={$userid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) >= 1) $errors++;
    
    // Check there are no journel entries by this user
    $sql = "SELECT id FROM `{$dbJournal}` WHERE userid={$userid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) >= 1) $errors++;
    
    // Check there are no transaction entries by this user
    $sql = "SELECT id FROM `{$dbTransactions}` WHERE userid={$userid} LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) >= 1) $errors++;

    
    // We break data integrity if we delete the user and there are things
    // related to him/her

    if ($errors == 0)
    {
        $sql = Array();
        $sql[] = "DELETE FROM `{$dbUsers}` WHERE id = {$userid} LIMIT 1";
        $sql[] = "DELETE FROM `{$dbHolidays}` WHERE userid = {$userid}";
        $sql[] = "DELETE FROM `{$dbUserGroups}` WHERE userid = {$userid}";
        $sql[] = "DELETE FROM `{$dbUserPermissions}` WHERE userid = {$userid}";
        $sql[] = "DELETE FROM `{$dbDrafts}` WHERE userid = {$userid}";
        $sql[] = "DELETE FROM `{$dbTriggers}` WHERE userid = {$userid}";
        $sql[] = "DELETE FROM `{$dbNotices}` WHERE userid = {$userid}";
        $sql[] = "DELETE FROM `{$dbUserConfig}` WHERE userid = {$userid}";
        $sql[] = "DELETE FROM `{$dbUserSoftware}` WHERE userid = {$userid}";

        foreach ($sql AS $query)
        {
            $result = mysql_query($query);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        }

        journal(CFG_LOGGING_NORMAL, 'User Removed', "User {$userid} was removed", CFG_JOURNAL_USERS, $userid);
        html_redirect("users.php");
    }
    else
    {
        html_redirect("users.php#{$userid}", FALSE, $strCannotDeleteUser);
        exit;
    }
}
else
{
    trigger_error("Cound not delete user: Parameter(s) missing", E_USER_WARNING);
}
?>