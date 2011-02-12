<?php
// main.php - Front page
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// Author: Paul Heaney <paulheaney[at]users.sourceforge.net>
// This Page is Valid XHTML 1.0 Transitional!

$permission = 0; // not required
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// --------------------------------------------------------------------------------------------
// Dashboard widgets

$sql = "SELECT * FROM `{$dbDashboard}` WHERE enabled='true' ORDER BY id";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
while ($dashboard = mysql_fetch_object($result))
{
   include (APPLICATION_PLUGINPATH . "dashboard_{$dashboard->name}.php");
   $DASHBOARDCOMP["dashboard_{$dashboard->name}"] = "dashboard_{$dashboard->name}";
}

// Valid user
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$sql = "SELECT dashboard FROM `{$dbUsers}` WHERE id = '".$_SESSION['userid']."'";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

$dashboardcomponents = array();
if (mysql_num_rows($result) > 0)
{
    $obj = mysql_fetch_object($result);
    $dashboardcomponents = explode(",",$obj->dashboard);
}

$col0 = 0;
$col1 = 0;
$col2 = 0;

$cols0 = '';
$cols1 = '';
$cols2 = '';

foreach ($dashboardcomponents AS $db)
{
    $c = explode("-",$db);
    switch ($c[0])
    {
        case 0: $col0++;
            $cols0 .= $c[1].",";
            break;
        case 1: $col1++;
            $cols1 .= $c[1].",";
            break;
        case 2: $col2++;
            $cols2 .= $c[1].",";
            break;
    }
}

$colstr = $col0.",".$col1.",".$col2;

$cols0 = mb_substr($cols0, 0, -1);
$cols1 = mb_substr($cols1, 0, -1);
$cols2 = mb_substr($cols2, 0, -1);
echo "<p id='pageoptions'>".help_link("Dashboard")." <a href='manage_user_dashboard.php' title='{$strManageYourDashboard}'>";
echo icon('dashboardadd', 16)."</a> ";
echo "<a href=\"javascript:save_layout();\" id='savelayout' title='{$strSaveDashbaordLayout}'>".icon('save', 16)."</a></p>";
echo "\n<table border=\"0\" width=\"99%\" id='cols'><tr>\n"; //id='dashboardlayout'
echo "<td width=\"33%\" valign='top' id='col0'>";

$arr = explode(",",$cols0);
foreach ($arr AS $a)
{
    show_dashboard_component(0, $a);
}

echo "</td>\n<td width=\"33%\" valign='top' id='col1'>";

$arr = explode(",",$cols1);
foreach ($arr AS $a)
{
    show_dashboard_component(1, $a);
}

echo "</td>\n<td width=\"33%\" valign=\"top\" id='col2'>";

$arr = explode(",",$cols2);
foreach ($arr AS $a)
{
    show_dashboard_component(2, $a);
}

echo "</td></tr></table>\n";
?>
<script type="text/javascript">
/* <![CDATA[ */
//var cols = [1,3,1];
var cols = [<?php echo $colstr; ?>];
var cols0 = [<?php echo $cols0; ?>];
var cols1 = [<?php echo $cols1; ?>];
var cols2 = [<?php echo $cols2; ?>];

var dashlets = $$('div.windowbox');

var contain0 = ['col1', 'col2'];
var contain1 = ['col0', 'col2'];
var contain2 = ['col0', 'col1'];

Droppables.add('col0', {ghosting: true, onDrop: moveItem, hoverclass: 'droptarget', containment: contain0});
Droppables.add('col1', {ghosting: true, onDrop: moveItem, hoverclass: 'droptarget', containment: contain1});
Droppables.add('col2', {ghosting: true, onDrop: moveItem, hoverclass: 'droptarget', containment: contain2});

Sortable.create('col0', { tag:'div', only:'windowbox', onUpdate: save_layout});
Sortable.create('col1', { tag:'div', only:'windowbox', onUpdate: save_layout});
Sortable.create('col2', { tag:'div', only:'windowbox', onUpdate: save_layout});

// Set drop area by default  non cleared.
$('col0').cleared = false;
$('col1').cleared = false;
$('col2').cleared = false;
$('savelayout').style.display='none';

window.onload = function() {
   dashlets.each(
       function(item) {
        new Draggable(item, {revert: true});
       }
   );
}

// The target drop area contains a snippet of instructional
// text that we want to remove when the first item
// is dropped into it.
function moveItem( draggable,droparea){
//    if (!droparea.cleared) {
// //       droparea.innerHTML = '';
//       droparea.cleared = true;
//    }
// //    draggable.parentNode.removeChild(draggable);
   droparea.appendChild(draggable);
   save_layout();
}


function save_layout(){
        var xmlhttp=false;
        /*@cc_on @*/
        /*@if (@_jscript_version >= 5)
        // JScript gives us Conditional compilation, we can cope with old IE versions.
        // and security blocked creation of the objects.
        try {
        xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {
        try {
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        } catch (E) {
        xmlhttp = false;
        }
        }
        @end @*/
        if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
            try {
                xmlhttp = new XMLHttpRequest();
            } catch (e) {
                xmlhttp=false;
            }
        }
        if (!xmlhttp && window.createRequest) {
            try {
                xmlhttp = window.createRequest();
            } catch (e) {
                xmlhttp=false;
            }
        }

        var toPass = '';
        for (var i = 0; i < 3; i++)
        {
            colid = 'col' + i;
            var col = $(colid).childNodes;
            var s = '';
//             alert(colid + '=' + col.length);
            for (var x = 0; x < col.length; x++){
                // s = s+col.item(x).id.substr(5)+"-";
//                 alert('x = '+x + col.item(x).id);
                s = s+i+"-"+col.item(x).id.substr(5)+",";
            }
            //alert(s);
            toPass = toPass+s.substr(0,s.length-1)+",";
        }


        xmlhttp.open("GET", "ajaxdata.php?action=storedashboard&id="+<?php echo $_SESSION['userid']; ?>+"&val="+escape(toPass), true);

        xmlhttp.onreadystatechange=function() {
            //remove this in the future after testing
            if (xmlhttp.readyState==4) {
                if (xmlhttp.responseText != ''){
                    //alert(xmlhttp.responseText);
                }
            }
        }
        xmlhttp.send(null);
        $('savelayout').style.display='none';
    }

/* ]]> */
</script>
<?php
if ($CONFIG['debug']) $dbg .= "\nLang: {$_SESSION['lang']}\n";
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>