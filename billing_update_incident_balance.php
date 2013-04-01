<?php
// ???
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author:  Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_EDIT_INCIDENT_BALANCE;
require_once (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require_once (APPLICATION_LIBPATH . 'auth.inc.php');
require_once (APPLICATION_LIBPATH . 'billing.inc.php');

$mode = cleanvar($_REQUEST['mode']);
$incidentid = clean_int($_REQUEST['incidentid']);
$title = "{$strUpdateIncidentXsBalance}, $incidentid";

if (empty($mode))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".sprintf($strUpdateIncidentXsBalance, $incidentid)."</h2>";

    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' id='modifyincidentbalance'>";

    echo "<table class='vertical'><tr><th>{$strAmount}</th><td>";
    echo "<input type='text' name='amount' id='amount' size='10' /> {$strMinutes}<br />{$strForRefundsThisShouldBeNegative}</td></tr>";

    echo "<tr><th>{$strDescription}</th><td>";
    echo "<textarea cols='40' name='description' rows='5'></textarea>";
    echo "</tr>";

    echo "</table>";

    echo "<input type='hidden' id='incidentid' name='incidentid' value='{$incidentid}' />";
    echo "<input type='hidden' id='mode' name='mode' value='update' />";

    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' />  <input type='submit' name='Sumbit' value='{$strSave}'  /></p>";
    echo "</form>";
    echo "<p class='return'><a href='billable_incidents.php'>{$strReturnWithoutSaving}</a></p>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($mode == 'update')
{
    $amount = clean_int($_REQUEST['amount']);
    $description = clean_dbstring($_REQUEST['description']);

    $sql = "SELECT closed, status, owner FROM `{$dbIncidents}` WHERE id = {$incidentid}";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $obj = mysql_fetch_object($result);

        $description = "[b]{$strAmount}[/b]: {$amount} {$strMinutes}\n\n{$description}";

        $sqlInsert = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp, duration) VALUES ";
        $sqlInsert .= "('{$incidentid}', '{$sit[2]}', 'editing', '{$obj->owner}', '{$obj->status}', '{$description}', '{$now}', '{$amount}')";
        $resultInsert = mysql_query($sqlInsert);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (mysql_affected_rows() > 0) $success = TRUE;
        else $success = FALSE;

        if ($success)
        {
            $b = get_billable_object_from_incident_id($incidentid);
            if ($b AND $b->update_incident_transaction_record($incidentid))
            {
                html_redirect('billable_incidents.php', TRUE, $strUpdateSuccessful);
            }
            else
            {
                html_redirect('billable_incidents.php', FALSE, $strUpdateFailed);
            }
        }
        else
        {
            html_redirect('billable_incidents.php', FALSE, $strUpdateFailed);
        }
    }
    else
    {
        html_redirect('billable_incidents.php', FALSE, $strFailedToFindDateIncidentClosed);
    }
}

?>