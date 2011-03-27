<?php
// service_level_new.php - Add a new service level
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


$permission = 22; // Administrate

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$tag = clean_dbstring($_REQUEST['tag']);
$priority = clean_dbstring($_REQUEST['priority']);
$action = $_REQUEST['action'];

if (empty($action) OR $action == "showform")
{
    $title = $strNewServiceLevel;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo show_form_errors('new_servicelevel');
    clear_form_errors('new_servicelevel');

    if (empty($_SESSION['formdata']['new_servicelevel']['low_initial_response_mins'])) $_SESSION['formdata']['new_servicelevel']['low_initial_response_mins'] = 320;
    if (empty($_SESSION['formdata']['new_servicelevel']['low_prob_determ_mins'])) $_SESSION['formdata']['new_servicelevel']['low_prob_determ_mins'] = 380;
    if (empty($_SESSION['formdata']['new_servicelevel']['low_action_plan_mins'])) $_SESSION['formdata']['new_servicelevel']['low_action_plan_mins'] = 960;
    if (empty($_SESSION['formdata']['new_servicelevel']['low_resolution_days'])) $_SESSION['formdata']['new_servicelevel']['low_resolution_days'] = 14;
    if (empty($_SESSION['formdata']['new_servicelevel']['low_review_days'])) $_SESSION['formdata']['new_servicelevel']['low_review_days'] = 28;

    if (empty($_SESSION['formdata']['new_servicelevel']['med_initial_response_mins'])) $_SESSION['formdata']['new_servicelevel']['med_initial_response_mins'] = 240;
    if (empty($_SESSION['formdata']['new_servicelevel']['med_prob_determ_mins'])) $_SESSION['formdata']['new_servicelevel']['med_prob_determ_mins'] = 320;
    if (empty($_SESSION['formdata']['new_servicelevel']['med_action_plan_mins'])) $_SESSION['formdata']['new_servicelevel']['med_action_plan_mins'] = 960;
    if (empty($_SESSION['formdata']['new_servicelevel']['med_resolution_days'])) $_SESSION['formdata']['new_servicelevel']['med_resolution_days'] = 10;
    if (empty($_SESSION['formdata']['new_servicelevel']['med_review_days'])) $_SESSION['formdata']['new_servicelevel']['med_review_days'] = 20;

    if (empty($_SESSION['formdata']['new_servicelevel']['hi_initial_response_mins'])) $_SESSION['formdata']['new_servicelevel']['hi_initial_response_mins'] = 120;
    if (empty($_SESSION['formdata']['new_servicelevel']['hi_prob_determ_mins'])) $_SESSION['formdata']['new_servicelevel']['hi_prob_determ_mins'] = 180;
    if (empty($_SESSION['formdata']['new_servicelevel']['hi_action_plan_mins'])) $_SESSION['formdata']['new_servicelevel']['hi_action_plan_mins'] = 480;
    if (empty($_SESSION['formdata']['new_servicelevel']['hi_resolution_days'])) $_SESSION['formdata']['new_servicelevel']['hi_resolution_days'] = 7;
    if (empty($_SESSION['formdata']['new_servicelevel']['hi_review_days'])) $_SESSION['formdata']['new_servicelevel']['hi_review_days'] = 14;

    if (empty($_SESSION['formdata']['new_servicelevel']['crit_initial_response_mins'])) $_SESSION['formdata']['new_servicelevel']['crit_initial_response_mins'] = 60;
    if (empty($_SESSION['formdata']['new_servicelevel']['crit_prob_determ_mins'])) $_SESSION['formdata']['new_servicelevel']['crit_prob_determ_mins'] = 120;
    if (empty($_SESSION['formdata']['new_servicelevel']['crit_action_plan_mins'])) $_SESSION['formdata']['new_servicelevel']['crit_action_plan_mins'] = 240;
    if (empty($_SESSION['formdata']['new_servicelevel']['crit_resolution_days'])) $_SESSION['formdata']['new_servicelevel']['crit_resolution_days'] = 3;
    if (empty($_SESSION['formdata']['new_servicelevel']['crit_review_days'])) $_SESSION['formdata']['new_servicelevel']['crit_review_days'] = 6;

    if (empty($_SESSION['formdata']['new_servicelevel']['engineerPeriod'])) $_SESSION['formdata']['new_servicelevel']['engineerPeriod'] = 60;
    if (empty($_SESSION['formdata']['new_servicelevel']['customerPeriod'])) $_SESSION['formdata']['new_servicelevel']['customerPeriod'] = 120;

    if (!empty($_SESSION['formdata']['new_servicelevel']['timed'])) $timedchecked = 'CHECKED';

    echo "<h2>".icon('sla', 32)." ";
    echo "{$title}</h2>";
    echo "<form name='new_servicelevel' action='{$_SERVER['PHP_SELF']}' method='post'>";

    echo "<p align='center'>{$strTag}: <input type='text' name='tag' maxlength='32' value='{$_SESSION['formdata']['new_servicelevel']['tag']}' /></p>";

    echo "<table align='center'>";
    echo "<tr><th>{$strTimed}</th><td class='shade1'><input type='checkbox' id='timed' name='timed' value='yes' onchange='enableBillingPeriod();' {$timedchecked} />".help_link('ServiceLevelTimed')."</td></tr>";
    echo "<tr><th>{$strAllowIncidentReopen}</th><td class='shade2'>".html_checkbox('allow_reopen', $sla->allow_reopen)."</td></tr>\n";
    echo "<tr id='engineerBillingPeriod' style='display:none;'><th>{$strBillingEngineerPeriod}</th><td class='shade1'><input type='text' size='5' name='engineerPeriod' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['engineerPeriod']}' /> {$strMinutes}</td></tr>";
    echo "<tr id='customerBillingPeriod' style='display:none;'><th>{$strBillingCustomerPeriod}</th><td  class='shade2'><input type='text' size='5' name='customerPeriod' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['customerPeriod']}' /> {$strMinutes}</td></tr>";
    echo "<tr id='limit' style='display:none;'><th>{$strLimit}</th><td  class='shade1' >{$CONFIG['currency_symbol']} <input type='text' size='5' name='limit' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['limit']}' /></td></tr>";
    echo "</table>";

    echo "<table align='center'>";
    echo "<tr><th>{$strPriority}</th><th>{$strInitialResponse}</th>";
    echo "<th>{$strProblemDefinition}</th><th>{$strActionPlan}</th><th>{$strResolutionReprioritisation}</th>";
    echo "<th>{$strReview}</th></tr>";
    echo "<tr class='shade1'>";
    echo "<td>{$strLow}</td>";
    echo "<td><input type='text' size='5' name='low_initial_response_mins' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['low_initial_response_mins']}' /> {$strMinutes}</td>";
    echo "<td><input type='text' size='5' name='low_prob_determ_mins' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['low_prob_determ_mins']}' /> {$strMinutes}</td>";
    echo "<td><input type='text' size='5' name='low_action_plan_mins' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['low_action_plan_mins'] }' /> {$strMinutes}</td>";
    echo "<td><input type='text' size='5' name='low_resolution_days' maxlength='3' value='{$_SESSION['formdata']['new_servicelevel']['low_resolution_days']}' /> {$strDays}</td>";
    echo "<td><input type='text' size='5' name='low_review_days' maxlength='3' value='{$_SESSION['formdata']['new_servicelevel']['low_review_days']}' /> {$strDays}</td>";
    echo "</tr>\n";
    echo "<tr class='shade2'>";
    echo "<td>{$strMedium}</td>";
    echo "<td><input type='text' size='5' name='med_initial_response_mins' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['med_initial_response_mins']}' /> {$strMinutes}</td>";
    echo "<td><input type='text' size='5' name='med_prob_determ_mins' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['med_prob_determ_mins']}' /> {$strMinutes}</td>";
    echo "<td><input type='text' size='5' name='med_action_plan_mins' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['med_action_plan_mins']}' /> {$strMinutes}</td>";
    echo "<td><input type='text' size='5' name='med_resolution_days' maxlength='3' value='{$_SESSION['formdata']['new_servicelevel']['med_resolution_days']}' /> {$strDays}</td>";
    echo "<td><input type='text' size='5' name='med_review_days' maxlength='3' value='{$_SESSION['formdata']['new_servicelevel']['med_review_days']}' /> {$strDays}</td>";
    echo "</tr>\n";
    echo "<tr class='shade1'>";
    echo "<td>{$strHigh}</td>";
    echo "<td><input type='text' size='5' name='hi_initial_response_mins' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['hi_initial_response_mins']}' /> {$strMinutes}</td>";
    echo "<td><input type='text' size='5' name='hi_prob_determ_mins' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['hi_prob_determ_mins']}' /> {$strMinutes}</td>";
    echo "<td><input type='text' size='5' name='hi_action_plan_mins' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['hi_action_plan_mins']}' /> {$strMinutes}</td>";
    echo "<td><input type='text' size='5' name='hi_resolution_days' maxlength='3' value='{$_SESSION['formdata']['new_servicelevel']['hi_resolution_days']}' /> {$strDays}</td>";
    echo "<td><input type='text' size='5' name='hi_review_days' maxlength='3' value='{$_SESSION['formdata']['new_servicelevel']['hi_review_days']}' /> {$strDays}</td>";
    echo "</tr>\n";
    echo "<tr class='shade2'>";
    echo "<td>{$strCritical}</td>";
    echo "<td><input type='text' size='5' name='crit_initial_response_mins' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['crit_initial_response_mins']}' /> {$strMinutes}</td>";
    echo "<td><input type='text' size='5' name='crit_prob_determ_mins' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['crit_prob_determ_mins']}' /> {$strMinutes}</td>";
    echo "<td><input type='text' size='5' name='crit_action_plan_mins' maxlength='5' value='{$_SESSION['formdata']['new_servicelevel']['crit_action_plan_mins']}' /> {$strMinutes}</td>";
    echo "<td><input type='text' size='5' name='crit_resolution_days' maxlength='3' value='{$_SESSION['formdata']['new_servicelevel']['crit_resolution_days']}' /> {$strDays}</td>";
    echo "<td><input type='text' size='5' name='crit_review_days' maxlength='3' value='{$_SESSION['formdata']['new_servicelevel']['crit_review_days']}' /> {$strDays}</td>";
    echo "</tr>\n";
    echo "</table>";

    echo "<input type='hidden' name='action' value='edit' />";
    echo "<p align='center'><input type='submit' value='{$strSave}' /></p>";
    echo "</form>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

    clear_form_data('new_servicelevel');
}
elseif ($action == "edit")
{
    // External variables
    $tag = trim(clean_dbstring($_POST['tag']));
    $low_initial_response_mins = clean_int($_POST['low_initial_response_mins']);
    $low_prob_determ_mins = clean_int($_POST['low_prob_determ_mins']);
    $low_action_plan_mins = clean_int($_POST['low_action_plan_mins']);
    $low_resolution_days = clean_int($_POST['low_resolution_days']);
    $low_review_days = clean_int($_POST['low_review_days']);
    $med_initial_response_mins = clean_int($_POST['med_initial_response_mins']);
    $med_prob_determ_mins = clean_int($_POST['med_prob_determ_mins']);
    $med_action_plan_mins = clean_int($_POST['med_action_plan_mins']);
    $med_resolution_days = clean_int($_POST['med_resolution_days']);
    $med_review_days = clean_int($_POST['med_review_days']);
    $hi_initial_response_mins = clean_int($_POST['hi_initial_response_mins']);
    $hi_prob_determ_mins = clean_int($_POST['hi_prob_determ_mins']);
    $hi_action_plan_mins = clean_int($_POST['hi_action_plan_mins']);
    $hi_resolution_days = clean_int($_POST['hi_resolution_days']);
    $hi_review_days = clean_int($_POST['hi_review_days']);
    $crit_initial_response_mins = clean_int($_POST['crit_initial_response_mins']);
    $crit_prob_determ_mins = clean_int($_POST['crit_prob_determ_mins']);
    $crit_action_plan_mins = clean_int($_POST['crit_action_plan_mins']);
    $crit_resolution_days = clean_int($_POST['crit_resolution_days']);
    $crit_review_days = clean_int($_POST['crit_review_days']);

    $engineerPeriod = clean_int($_POST['engineerPeriod']);
    $customerPeriod = clean_int($_POST['customerPeriod']);
    $timed = clean_dbstring($_POST['timed']);
    $allow_reopen = clean_dbstring($_POST['allow_reopen']);
    if ($allow_reopen != 'yes') $allow_reopen = 'no';
    $limit = clean_int($_POST['limit']);
    if ($limit == '') $limit = 0;

    if (empty($timed))
    {
    	$timed = 'no';
        $allow_reopen = 'yes';
    }

    $_SESSION['formdata']['new_servicelevel'] = cleanvar($_POST, TRUE, FALSE, FALSE,
                                                     array("@"), array("'" => '"'));

    // Check input
    $errors = 0;
    if (empty($tag))
    {
        $errors++;
        $_SESSION['formerrors']['new_servicelevel']['tag'] = sprintf($strFieldMustNotBeBlank, $strTag);
    }

    if (empty($engineerPeriod) AND $timed == 'yes')
    {
    	$errors++;
        $_SESSION['formerrors']['new_servicelevel']['engineerPeriod'] = sprintf($strFieldMustNotBeBlank, $strBillingEngineerPeriod);
    }

    if (empty($customerPeriod) AND $timed == 'yes')
    {
        $errors++;
        $_SESSION['formerrors']['new_servicelevel']['customerPeriod'] = sprintf($strFieldMustNotBeBlank, $strBillingCustomerPeriod);
    }

    if ($errors >= 1)
    {
        // show error message if errors
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
    else
    {
        // Insert low
        $sql = "INSERT INTO `{$dbServiceLevels}` (tag, priority, initial_response_mins, prob_determ_mins, action_plan_mins, resolution_days, review_days, timed, allow_reopen) VALUES (";
        $sql .= "'{$tag}', '1', ";
        $sql .= "'{$low_initial_response_mins}', ";
        $sql .= "'{$low_prob_determ_mins}', ";
        $sql .= "'{$low_action_plan_mins}', ";
        $sql .= "'{$low_resolution_days}', ";
        $sql .= "'{$low_review_days}', ";
        $sql .= "'{$timed}', ";
        $sql .= "'{$allow_reopen}')";
        mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        if (mysql_affected_rows() == 0) trigger_error("INSERT affected zero rows", E_USER_WARNING);

        // Insert medium
        $sql = "INSERT INTO `{$dbServiceLevels}` (tag, priority, initial_response_mins, prob_determ_mins, action_plan_mins, resolution_days, review_days, timed, allow_reopen) VALUES (";
        $sql .= "'{$tag}', '2', ";
        $sql .= "'{$med_initial_response_mins}', ";
        $sql .= "'{$med_prob_determ_mins}', ";
        $sql .= "'{$med_action_plan_mins}', ";
        $sql .= "'{$med_resolution_days}', ";
        $sql .= "'{$med_review_days}', ";
        $sql .= "'{$timed}', ";
        $sql .= "'{$allow_reopen}')";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
        if (mysql_affected_rows() == 0) trigger_error("INSERT affected zero rows", E_USER_WARNING);

        // Insert high
        $sql = "INSERT INTO `{$dbServiceLevels}` (tag, priority, initial_response_mins, prob_determ_mins, action_plan_mins, resolution_days, review_days, timed, allow_reopen) VALUES (";
        $sql .= "'{$tag}', '3', ";
        $sql .= "'{$hi_initial_response_mins}', ";
        $sql .= "'{$hi_prob_determ_mins}', ";
        $sql .= "'{$hi_action_plan_mins}', ";
        $sql .= "'{$hi_resolution_days}', ";
        $sql .= "'{$hi_review_days}', ";
        $sql .= "'{$timed}', ";
        $sql .= "'{$allow_reopen}')";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
        if (mysql_affected_rows() == 0) trigger_error("INSERT affected zero rows", E_USER_WARNING);

        // Insert critical
        $sql = "INSERT INTO `{$dbServiceLevels}` (tag, priority, initial_response_mins, prob_determ_mins, action_plan_mins, resolution_days, review_days, timed, allow_reopen) VALUES (";
        $sql .= "'{$tag}', '4', ";
        $sql .= "'{$crit_initial_response_mins}', ";
        $sql .= "'{$crit_prob_determ_mins}', ";
        $sql .= "'{$crit_action_plan_mins}', ";
        $sql .= "'{$crit_resolution_days}', ";
        $sql .= "'{$crit_review_days}', ";
        $sql .= "'{$timed}', ";
        $sql .= "'{$allow_reopen}')";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
        if (mysql_affected_rows() == 0) trigger_error("INSERT affected zero rows", E_USER_WARNING);

        clear_form_data("new_servicelevel");
        clear_form_errors("new_servicelevel");
        
        for ($i = 1; $i <= 4; $i++)
        {
            $sql = "INSERT INTO `{$dbBillingPeriods}` (priority, tag, customerperiod, engineerperiod, `limit`) ";
            $sql .= "VALUES ('{$i}', '{$tag}', '{$customerPeriod}', '{$engineerPeriod}', '{$limit}')";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        }

        header("Location: service_levels.php");
        exit;
    }
}
?>