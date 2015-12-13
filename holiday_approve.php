<?php
// holiday_approve.php -
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
$permission = PERM_HOLIDAY_APPROVE; // Approve Holiday
require (APPLICATION_LIBPATH . 'functions.inc.php');
$title = $strApproveHolidays;

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$approve = clean_fixed_list($_REQUEST['approve'], array('true', 'false', 'free'));
$startdate = clean_dbstring($_REQUEST['startdate']);
$type = clean_int($_REQUEST['type']);
$user = clean_int($_REQUEST['user']);
$length = clean_fixed_list($_REQUEST['length'], array('day', 'am', 'pm'));
$view = clean_int($_REQUEST['view']);

// there is an existing booking so alter it
switch (strtolower($approve))
{
    case 'true':
        $sql = "UPDATE `{$dbHolidays}` SET approved='".HOL_APPROVAL_GRANTED."' ";
        break;
    case 'false':
        $sql = "UPDATE `{$dbHolidays}` SET approved='".HOL_APPROVAL_DENIED."' ";
       break;
    case 'free':
        $sql = "UPDATE `{$dbHolidays}` SET approved='".HOL_APPROVAL_GRANTED."', type='".HOL_FREE."' ";
        break;
}

$sql .= "WHERE approvedby='{$sit[2]}' AND approved=".HOL_APPROVAL_NONE." ";

if ($user != 'all')
{
    $sql .= "AND userid='{$user}' ";
}

if ($startdate != 'all')
{
    $sql.="AND `date` = '{$startdate}' AND type='{$type}' AND length='{$length}'";
}

$result = mysqli_query($db, $sql);

if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);

// Don't send email when approving 'all' to avoid an error message
// TODO this needs moving into a trigger - logged as Mantis 1567 PH
if ($user != 'all')
{
    $bodytext = "Message from {$CONFIG['application_shortname']}: ".user_realname($sit[2])." has ";
    if ($approve == 'FALSE') $bodytext .= "rejected";
    else $bodytext .= "approved";

    $bodytext.=" your request for ";

    if ($startdate == 'all') $bodytext .= "all days requested\n\n";
    else
    {
        $bodytext .= "the ";
        $bodytext .= date('l j F Y',mysql2date($startdate));
        $bodytext .= "\n";
    }
    $email_from = user_email($sit[2]);
    $email_to = user_email($user);
    $email_subject = "Re: {$CONFIG['application_shortname']}: Holiday Approval Request";
    $rtnvalue = send_email($email_to, $email_from, $email_subject, $bodytext);
}

plugin_do('holiday_acknowledge_action');

if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
header("Location: holiday_request.php?user={$view}&mode=approval");
exit;
?>