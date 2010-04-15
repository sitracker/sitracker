<?php
// triggers.php - Page for setting user trigger preferences
//
// SiT (Support Incident Tracker) - Support call tracking system
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
require (APPLICATION_LIBPATH . 'auth.inc.php');

$adminuser = user_permission($sit[2],22); //Admin user
$title = 'New Triggers Interface';
include (APPLICATION_INCPATH . 'htmlheader.inc.php');
($_GET['user'] == 'admin') ? $user = 0 : $user = $sit[2];
?>
<script type="text/javascript">
//<![CDATA[

function insertRuletext(tvar)
{
    tvar = tvar + ' ';
    var start = $('rules').selectionStart;
    var end = $('rules').selectionEnd;
    $('rules').value = $('rules').value.substring(0, start) + tvar + $('rules').value.substring(end, $('rules').textLength);
}

function resetRules()
{
    $('rules').value = '';
}

function get_checks()
{
      var xmlhttp=false;

      if (!xmlhttp && typeof XMLHttpRequest!='undefined')
      {
          try
          {
              xmlhttp = new XMLHttpRequest();
          }
          catch (e)
          {
              xmlhttp=false;
          }
      }
      if (!xmlhttp && window.createRequest)
      {
          try
          {
              xmlhttp = window.createRequest();
          }
          catch (e)
          {
              xmlhttp=false;
          }
      }
      var triggertype = $('triggertype').value;
      var url =  "ajaxdata.php";
      var params = "action=checkhtml&triggertype="+triggertype;;
      xmlhttp.open("POST", url, true)
      xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xmlhttp.setRequestHeader("Content-length", params.length);
      xmlhttp.setRequestHeader("Connection", "close");
      xmlhttp.send(params);

      xmlhttp.onreadystatechange=function()
      {
          if (xmlhttp.readyState==4)
          {
              if (xmlhttp.responseText != '')
              {
                  //alert(xmlhttp.responseText);
                  $("checkshtml").update(xmlhttp.responseText);
                  alert(xmlhttp.responseText);
              }
          }
      }
}

function switch_template()
{
    get_checks();
    //FIXME functionise the js here
    if ($('new_action').value == 'ACTION_NOTICE')
    {
        $('noticetemplatesbox').show();
        var xmlhttp=false;

        if (!xmlhttp && typeof XMLHttpRequest!='undefined')
        {
            try
            {
                xmlhttp = new XMLHttpRequest();
            }
            catch (e)
            {
                xmlhttp=false;
            }
        }
        if (!xmlhttp && window.createRequest)
        {
            try
            {
                xmlhttp = window.createRequest();
            }
            catch (e)
            {
                xmlhttp=false;
            }
        }
        var triggertype = $('triggertype').value;
        var triggeraction = $('new_action').value;
        var url =  "ajaxdata.php";
        var params = "action=triggerpairmatch&triggertype="+triggertype+"&triggeraction="+triggeraction;
        xmlhttp.open("POST", url, true)
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.setRequestHeader("Content-length", params.length);
        xmlhttp.setRequestHeader("Connection", "close");
        xmlhttp.send(params);

        xmlhttp.onreadystatechange=function()
        {
            if (xmlhttp.readyState==4)
            {
                if (xmlhttp.responseText != '')
                {
                    $(xmlhttp.responseText).selected = true;
                }
            }
        }
        $('emailtemplatesbox').hide();
        $('parametersbox').show();
        $('journalbox').hide();
        $('none').hide();
        $('rulessection').show();
    }
    else if ($('new_action').value == 'ACTION_EMAIL')
    {
        $('noticetemplatesbox').hide();
        $('emailtemplatesbox').show();

        var xmlhttp=false;

        if (!xmlhttp && typeof XMLHttpRequest!='undefined')
        {
            try
            {
                xmlhttp = new XMLHttpRequest();
            }
            catch (e)
            {
                xmlhttp=false;
            }
        }
        if (!xmlhttp && window.createRequest)
        {
            try
            {
                xmlhttp = window.createRequest();
            }
            catch (e)
            {
                xmlhttp=false;
            }
        }
        
        var triggertype = $('triggertype').value;
        var triggeraction = $('new_action').value;
        var url =  "ajaxdata.php";
        var params = "action=triggerpairmatch&triggertype="+triggertype+"&triggeraction="+triggeraction;
        xmlhttp.open("POST", url, true)
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.setRequestHeader("Content-length", params.length);
        xmlhttp.setRequestHeader("Connection", "close");
        xmlhttp.send(params);

        xmlhttp.onreadystatechange=function()
        {
            if (xmlhttp.readyState==4)
            {
                if (xmlhttp.responseText != '')
                {
                    $(xmlhttp.responseText).selected = true;
                }
            }
        }

        $('parametersbox').show();
        $('journalbox').hide();
        $('none').hide();
        $('rulessection').show();

    }
    else if ($('new_action').value == 'ACTION_JOURNAL')
    {
        $('parametersbox').show();
        $('journalbox').show();
        $('emailtemplatesbox').hide();
        $('noticetemplatesbox').hide();
        $('none').hide();
    }
    else
    {
        $('noticetemplatesbox').hide();
        $('emailtemplatesbox').hide();
        $('parametersbox').hide();
        $('journalbox').hide();
        $('none').show();
        $('rulessection').hide();

    }
}
//]]>
</script>
<?php
if (isset($_GET['id']))
{
    $mode = 'edit';
}
else
{
    $mode = 'new';
}
$trigger_mode = 'user';
if (isset($_GET['user']))
{
    //FIXME perms
    if ($_GET['user'] == 'admin')
    {
        $user_id = 0;
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


if (!empty($_POST['triggertype']))
{
    print_r($_POST);
}
else
{
    echo "<h2>New action</h2>";
    echo "<div id='container'>";
    echo "<form id='newtrigger' method='post' action='{$_SERVER['PHP_SELF']}'>";
    echo "<h3>Step one</h3>";
    echo "<p>Choose which action you would like to be notified about</p>";
    echo "<select id='triggertype' name='triggertype' onchange='switch_template()' onkeyup='switch_template()'>";
    foreach($trigger_types as $name => $trigger)
    {
        if (($trigger['type'] == 'system' AND $trigger_mode == 'system') OR
            ($trigger['type'] == 'user' OR !isset($trigger['type'])))
        {
            echo "<option id='{$name}' value='{$name}'>{$trigger['description']}</option>\n";
        }
    }
    echo "</select>";

    echo "<h3>Step two</h3>";
    echo "<p>Choose which method of notification</p>";
    echo "<select id='new_action' name='new_action' onchange='switch_template()' onkeyup='switch_template()'>";
    echo "<option/>";
    foreach($actionarray as $name => $action)
    {
        if (($trigger_mode == 'system' AND $action['type'] == 'system') OR
            ($action['type'] == 'user' OR !isset($action['type'])))
        {
            echo "<option id='{$name}' value='{$name}'>{$action['description']}</option>\n";
        }
    }
    echo "</select>";

    echo "<span id='emailtemplatesbox' style='display:none'>";
    echo "<h3>Step three</h3> ";
    echo "<p>Choose which template you would like to use. If this is already filled in, a sensible default has been chosen for you. You shoud only change this if you would like to use a template you have created yourself</p>";
    echo email_templates('emailtemplate', $trigger_mode)."</span></p>";
    echo "<span id='noticetemplatesbox' style='display:none'>";
    echo "<h3>Step three</h3> ";
    echo notice_templates('noticetemplate')."</span></p>";
    echo '<h3>Step four</h3>';
    echo "<p>This action has variables associated with it, you can add a rule to only fire in certain circumstances.</p>";
    echo "<p>E.g. The action for 'When an incident is assigned' has two variables, {userid} and {userstatus}.</p><p>
    If you leave the check blank, you will receive a notification every time an incident is assigned. If you set {userid} to your own, you will only get a notification when you are assigned an incident. If you set {userstatus} to 'Out of Office', you will receive a notification every time an incident is assigned to a user who is out of the office. You can use more than one variable in a check.</p>";
    echo "<input onblur='get_checks()' />";
    echo "<div id='checkshtml'></div>";
    echo "<br /><p><input type='submit' name='submit' value='{$strAdd}' /></p></form>";

//     foreach ($ttvararray as $trigger => $data)
//     {
//         if (is_numeric($trigger)) $data = $data[0];
//         if (isset($data['checkreplace'])) 
//         {
//             echo 'Only notify when '. $data['description']. ' is ' .$data['checkreplace'](),"<br />";
//         }
//     }


}

?>
