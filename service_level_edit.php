<?php
// edit_service_level.php - Edit a service level
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
$permission = PERM_SLA_EDIT; // Edit Service Levels
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strEditServiceLevel;

// External variables
$tag = cleanvar($_REQUEST['tag']);
$priority = clean_int($_REQUEST['priority']);
$action = clean_fixed_list($_REQUEST['action'], array('showform', 'edit'));

if (empty($action) OR $action == "showform")
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('sla', 32)." {$title}</h2>";
    echo "<p align='center'>{$tag} ".priority_name($priority)."</p>";

    $sql = "SELECT * FROM `{$dbServiceLevels}` WHERE tag='{$tag}' AND priority='{$priority}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    $sla = mysqli_fetch_object($result);

    echo "<form name='edit_servicelevel' action='{$_SERVER['PHP_SELF']}' method='post'>";
    echo "<table class='vertical'>";
    echo "<tr><th>".icon('initialresponse', 16)." {$strInitialResponse}</th>";
    echo "<td><input type='text' size='5' name='initial_response_mins' maxlength='5' value='{$sla->initial_response_mins}' /> {$strMinutes}</td></tr>";
    echo "<tr><th>".icon('probdef', 16)." {$strProblemDefinition}</th>";
    echo "<td><input type='text' size='5' name='prob_determ_mins' maxlength='5' value='{$sla->prob_determ_mins}' /> {$strMinutes}</td></tr>";
    echo "<tr><th>".icon('actionplan', 16)." {$strActionPlan}</th>";
    echo "<td><input type='text' size='5' name='action_plan_mins' maxlength='5' value='{$sla->action_plan_mins}' /> {$strMinutes}</td></tr>";
    echo "<tr><th>".icon('solution', 16)." {$strResolutionReprioritisation}</th>";
    echo "<td><input type='text' size='5' name='resolution_days' maxlength='3' value='{$sla->resolution_days}' /> {$strDays}</td></tr>";
    echo "<tr><th>".icon('review', 16)." {$strReview} </th>";
    echo "<td><input type='text' size='5' name='review_days' maxlength='3' value='{$sla->review_days}' /> {$strDays}</td></tr>";
    $attributes = '';
    if ($sla->timed == 'yes') $attributes = "disabled='disabled' ";
    echo "<tr><th>{$strAllowIncidentReopen}</th><td>".html_checkbox('allow_reopen', $sla->allow_reopen, '', $attributes)."</td></tr>\n";
    echo "<tr><th>".icon('timer', 16)." {$strTimed}</th><td>";
    $timed_display = '';
    if ($sla->timed == 'yes')
    {
        echo "<input type='checkbox' name='timed' id='timed' onchange='enableBillingPeriod();' checked='checked' />";
        $billingSQL = "SELECT * FROM `{$dbBillingPeriods}` WHERE priority = {$priority} AND tag = '{$tag}'";
        $billingResult = mysqli_query($db, $billingSQL);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        $billing = mysqli_fetch_object($billingResult);

        $customerPeriod = $billing->customerperiod;
        $engineerPeriod = $billing->engineerperiod;
        $limit = $billing->limit;
    }
    else
    {
        echo "<input type='checkbox' name='timed' id='timed' onchange='enableBillingPeriod();' />";
        // Set some defaults
        $customerPeriod = "120";
        $engineerPeriod = "60";
        $timed_display = "style='display:none;'";
    }
    echo help_link('ServiceLevelTimed');
    echo "</td></tr>";
    echo "<tr id='engineerBillingPeriod' {$timed_display}><th>{$strBillingEngineerPeriod} ".help_link('ServiceLevelEngineerPeriod')."</th><td><input type='text' size='5' name='engineerPeriod' maxlength='5' value='{$engineerPeriod}' /> {$strMinutes}</td></tr>";
    echo "<tr id='customerBillingPeriod' {$timed_display}><th>{$strBillingCustomerPeriod} ".help_link('ServiceLevelCustomerPeriod')."</th><td><input type='text' size='5' name='customerPeriod' maxlength='5' value='{$customerPeriod}' /> {$strMinutes}</td></tr>";
    echo "<tr><th>{$strActive}</th><td>".html_checkbox('active', $sla->active)."</td></tr>\n";
    echo "</table>";
    echo "<input type='hidden' name='action' value='edit' />";
    echo "<input type='hidden' name='tag' value='{$tag}' />";
    echo "<input type='hidden' name='priority' value='{$priority}' />";
    echo "<p class='formbuttoms'><input name='reset' type='reset' value='{$strReset}' /> <input type='submit' value='{$strSave}' /></p>";
    echo "<p class='return'><a href='service_levels.php'>{$strBackToList}</a></p>";
    echo "</form>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == "edit")
{
    // External variables
    $initial_response_mins = clean_int($_POST['initial_response_mins']);
    $prob_determ_mins = clean_int($_POST['prob_determ_mins']);
    $action_plan_mins = clean_int($_POST['action_plan_mins']);
    $resolution_days = clean_int($_POST['resolution_days']);
    $review_days = clean_int($_POST['review_days']);
    $engineerPeriod = clean_int($_POST['engineerPeriod']);
    $customerPeriod = clean_int($_POST['customerPeriod']);
    $allow_reopen = cleanvar($_POST['allow_reopen']);
    $active = cleanvar($_POST['active']);
    if (!empty($allow_reopen))
    {
        $allow_reopen = 'yes';
    }
    else
    {
        $allow_reopen = 'no';
    }
    if (empty($active)) $active = 'false';
    else $active = true;

    if (!empty($_POST['timed']))
    {
        $timed = 1;
        // Force allow_reopen=no for timed incidents, since reopening will break billing
        $allow_reopen = 'no';
    }
    else $timed = 0;

    $sql = "UPDATE `{$dbServiceLevels}` SET initial_response_mins='{$initial_response_mins}', ";
    $sql .= "prob_determ_mins='{$prob_determ_mins}', ";
    $sql .= "action_plan_mins='{$action_plan_mins}', ";
    $sql .= "resolution_days='{$resolution_days}', ";
    $sql .= "review_days='{$review_days}' ";
    $sql .= "WHERE tag='{$tag}' AND priority='{$priority}'";
    mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_ERROR);
    else
    {
        $sql = "UPDATE `{$dbServiceLevels}` SET ";
        $sql .= "timed='{$timed}', ";
        $sql .= "allow_reopen='{$allow_reopen}', ";
        $sql .= "active='{$active}' ";
        $sql .= "WHERE tag='{$tag}'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        
        
        $billingSQL = "SELECT * FROM `{$dbBillingPeriods}` WHERE priority = {$priority} AND tag = '{$tag}'";
        $billingResult = mysqli_query($db, $billingSQL);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        $billing = mysqli_fetch_object($billingResult);

        if (!empty($billing))
        {
            //update
            $sql = "UPDATE `{$dbBillingPeriods}` SET customerperiod = '{$customerPeriod}', engineerperiod = '{$engineerPeriod}' ";
            $sql .= "WHERE priority = {$priority} AND tag = '{$tag}'";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        }
        else
        {
            //insert
            $sql = "INSERT INTO `{$dbBillingPeriods}` (priority, tag, customerperiod, engineerperiod) ";
            $sql .= "VALUES ('{$priority}', '{$tag}', '{$customerPeriod}', '{$engineerPeriod}')";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        }

        header("Location: service_levels.php");
        exit;
    }
}
?>