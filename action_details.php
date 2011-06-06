<?php
// action_details.php - Page for setting user trigger preferences
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


$permission = 71;
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'trigger.class.php');
//This page requires authentication
$permission = 72;

require (APPLICATION_LIBPATH . 'auth.inc.php');
$trigger_mode = 'user';
if (isset($_GET['user']))
{
    //FIXME perms
    if ($_GET['user'] == 'admin')
    {
        $trigger_mode = 'system';
    }
    else
    {
        $user_id = intval($_GET['user']);
    }
}
else
{
    $user_id = $sit[2];
}
$title = $strNewTriggerInterface;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

if (isset($_GET['id']))
{
    $id = clean_int($_GET['id']);
    $mode = 'edit';
    $trigger = Trigger::fromID($id);
}
else
{
    $mode = 'new';
}

if (!empty($_POST['triggertype']))
{
    $_POST = cleanvar($_POST);
    $checks = create_check_string($_POST['param'], $_POST['value'], $_POST['join'], $_POST['enabled'], $_POST['conditions']);

    if ($_POST['new_action'] == 'ACTION_NOTICE')
    {
        $template = $_POST['noticetemplate'];
    }
    elseif ($_POST['new_action'] == 'ACTION_EMAIL')
    {
        $template = $_POST['emailtemplate'];
    }

    $t = new Trigger($_POST['triggertype'], $user_id, $template, $_POST['new_action'], $checks, $parameters);

    $success = $t->add();
    if ($trigger_mode == 'system') $return = 'system_actions.php';
    else $return = 'notifications.php';
    html_redirect($return, $success, $t->getError_text());
}
else
{
    echo "<h2>{$strNewAction}</h2>";
    echo "<div id='container'>";
    echo "<form id='newtrigger' method='post' action='{$_SERVER['PHP_SELF']}'>";
    if ($trigger_mode == 'system')
    {
        echo "<h3>{$strUser}</h3>";
        echo "<p>{$strWhichAction}</p>";
    }
    echo "<h3>{$strAction}</h3>";
    echo "<p style='text-align:left'>{$strChooseWhichActionNotify}</p>";
    echo "<select id='triggertype' name='triggertype' onchange='switch_template()' onkeyup='switch_template()'>";
    foreach ($trigger_types as $name => $trigger)
    {
        if (($trigger['type'] == 'system' AND $trigger_mode == 'system') OR
            (($trigger['type'] == 'user' AND $trigger_mode == 'user') OR !isset($trigger['type'])))
        {
            echo "<option id='{$name}' value='{$name}'>{$trigger['description']}</option>\n";
        }
    }
    echo "</select>";

    echo "<h3>{$strNotificationMethod}</h3>";
    echo "<p style='text-align:left'>$strChooseWhichMethodNotification</p>";
    echo "<select id='new_action' name='new_action' onchange='switch_template()' onkeyup='switch_template()'>";
    echo "<option/>";
    foreach ($actionarray as $name => $action)
    {
        if (($trigger_mode == 'system' AND $action['type'] == 'system') OR
            ($action['type'] == 'user' OR !isset($action['type'])))
        {
            echo "<option id='{$name}' value='{$name}'>{$action['description']}</option>\n";
        }
    }
    echo "</select>";

    echo "<div id='emailtemplatesbox' style='display:none'>";
    echo "<h3>{$strEmailTemplate}</h3> ";

    echo "<p style='text-align:left'>$strChooseWhichTemplate</p>";
    echo email_templates('emailtemplate', $trigger_mode)."</div>";

    echo "<div id='noticetemplatesbox' style='display:none'>";

    echo "<h3>{$strNoticeTemplate}</h3> ";
    echo "<p style='text-align:left'>{$strChooseWhichTemplate}</p>";
    echo notice_templates('noticetemplate')."</div>";
    echo '<div id="checksbox" style="display:none">';

    echo "<h3>{$strConditions}</h3>";
    echo "<p style='text-align:left'>{$strSomeActionsOptionalConditions}</p>";
    echo "<p style='text-align:left'>{$strExampleWhenIncidentAssigned} ";
    echo "{$strAddingACondition}</p>" ;
    echo "<div id='checkshtml'></div></div>";
    echo "<br /><p style='text-align:left'><input type='submit' name='submit' value='{$strSave}' /></p></form>";

//     foreach ($ttvararray as $trigger => $data)
//     {
//         if (is_numeric($trigger)) $data = $data[0];
//         if (isset($data['checkreplace']))
//         {
//             echo 'Only notify when '. $data['description']. ' is ' .$data['checkreplace'](),"<br />";
//         }
//     }
    echo "<p align='center'><a href='notifications.php'>{$strBackToList}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

}
?>