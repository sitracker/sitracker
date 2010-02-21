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
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$adminuser = user_permission($sit[2],22); // Admin user
$title = 'New Triggers Interface';
include (APPLICATION_INCPATH . 'htmlheader.inc.php');
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

function switch_template()
{
    if ($('new_action').value == 'ACTION_NOTICE')
    {
        $('noticetemplatesbox').show();
//                 $('parametersbox').show();
        $('emailtemplatesbox').hide();
        $('journalbox').hide();
        $('none').hide();
        $('rulessection').show();
    }
    else if ($('new_action').value == 'ACTION_EMAIL')
    {
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
        var url =  "ajaxdata.php";
        var params = "action=triggerpairmatch&triggertype="+triggertype;
        xmlhttp.open("POST", url, true)
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.setRequestHeader("Content-length", params.length);
        xmlhttp.setRequestHeader("Connection", "close");


        xmlhttp.onreadystatechange=function()
        {
            if (xmlhttp.readyState==4)
            {
                if (xmlhttp.responseText != '')
                {
                    alert(xmlhttp.responseText);
                    $(xmlhttp.responseText).selected = true;
                }
            }
        }
        xmlhttp.send(params);
//                 $('parametersbox').show();
        $('noticetemplatesbox').hide();
        $('journalbox').hide();
        $('none').hide();
        $('rulessection').show();

    }
    else if ($('new_action').value == 'ACTION_JOURNAL')
    {
//                 $('parametersbox').show();
        $('journalbox').show();
        $('emailtemplatesbox').hide();
        $('noticetemplatesbox').hide();
        $('none').hide();
    }
    else
    {
        $('noticetemplatesbox').hide();
        $('emailtemplatesbox').hide();
//                 $('parametersbox').hide();
        $('journalbox').hide();
        $('none').show();
        $('rulessection').hide();

    }
}
//]]>
</script>

<?php
echo "<h2>".icon('trigger', 32)." {$title}</h2>";
echo "<div id='newtrigger'><p>When... ";
echo "<select id='triggertype'>";
foreach($triggerarray as $name => $trigger)
{
    echo "<option id='{$name}' value='{$name}'>{$trigger['description']}</option>";
}
echo "</select>";

echo "<select id='new_action' onchange='switch_template()'>";
foreach($actionarray as $name => $action)
{
    echo "<option id='{$name}' value='{$name}'>{$action['description']}</option>";
}
echo "</select>";

echo "<span id='emailtemplatesbox' style='display:none'>";
echo email_templates('emailtemplate')."</span>";
echo "<span id='noticetemplatesbox' style='display:none'>";
echo notice_templates('noticetemplate')."</span>";
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>