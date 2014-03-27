<?php
// main.php - Front page
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivan[at]sitracker.org>
// Author: Paul Heaney <paul[at]sitracker.org>
// This Page is Valid XHTML 1.0 Transitional!

require ('core.php');

$permission = PERM_NOT_REQUIRED; // not required

require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');


if (user_permission($sit[2], PERM_ADMIN))
{
    // Check if scheduler is running (bug 108)
    $failure = 0;

    $schedulersql = "SELECT `interval`, `lastran` FROM {$dbScheduler} WHERE status='enabled'";
    $schedulerresult = mysql_query($schedulersql);
    if (mysql_error()) debug_log("scheduler_check: Failed to fetch data from the database", TRUE);

    while ($schedule = mysql_fetch_object($schedulerresult))
    {
        $sqlinterval = ("$schedule->interval");
        $sqllastran = mysql2date("$schedule->lastran");
        $dateresult = $sqlinterval + $sqllastran + 60;
        if ($dateresult < date(U))
        {
            $failure ++;
        }
    }
    $num = mysql_num_rows($schedulerresult);
    $num = $num / 2;
    if ($failure > $num)
    {
        $noticecount = 0;
        $text = mysql_real_escape_string("{$strSchedulerNotRunning} <a target='_blank' href='http://sitracker.org/wiki/Scheduler'> {$strTheDocumentation} </a>", $db);
        $type = WARNING_NOTICE_TYPE;
        $durability = 'session';
        $sql = "SELECT COUNT(id) FROM `{$dbNotices}` WHERE userid={$sit[2]} AND type={$type} AND durability='{$durability}' AND text='{$text}'";
        $noticeresult = mysql_query($sql);
        list($noticecount) = mysql_fetch_array($noticeresult);
        if ($noticecount < 1)
        {
            $sql = "INSERT INTO `{$dbNotices}` (userid, type, text, timestamp, durability) ";
            $sql .= "VALUES($sit[2], {$type}, '{$text}', NOW(), '{$durability}')";
            mysql_query($sql);
            if (mysql_error())
            {
                trigger_error(mysql_error(),E_USER_WARNING);
            }
        }
    }
}


// --------------------------------------------------------------------------------------------
// Dashboard widgets

$sql = "SELECT * FROM `{$dbDashboard}` WHERE enabled='true' ORDER BY id";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
while ($dashboard = mysql_fetch_object($result))
{
   include (APPLICATION_PLUGINPATH . "dashboard_{$dashboard->name}.php");
   $DASHBOARDCOMP["dashboard_{$dashboard->name}"] = "dashboard_{$dashboard->name}";
}

// Valid user
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$sql = "SELECT dashboard FROM `{$dbUsers}` WHERE id = '".$_SESSION['userid']."'";
$result = mysql_query($sql);
if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

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
        case 0:
            $col0++;
            $cols0 .= $c[1].",";
            break;
        case 1:
            $col1++;
            $cols1 .= $c[1].",";
            break;
        case 2:
            $col2++;
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
echo "<a href=\"javascript:save_dashboard_layout();\" id='savelayout' title='{$strSaveDashbaordLayout}'>".icon('save', 16)."</a></p>";
plugin_do('main');
echo "\n<table border=\"0\" width=\"99%\" id='cols'><tr>\n"; //id='dashboardlayout'
echo "<td width=\"33%\" valign='top' id='col0'>";

$arr = explode(",", $cols0);
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

$arr = explode(",", $cols2);
foreach ($arr AS $a)
{
    show_dashboard_component(2, $a);
}

echo "</td></tr>";
plugin_do('dashboard_table');
echo "</table>\n";
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

Sortable.create('col0', { tag:'div', only:'windowbox', onUpdate: save_dashboard_layout});
Sortable.create('col1', { tag:'div', only:'windowbox', onUpdate: save_dashboard_layout});
Sortable.create('col2', { tag:'div', only:'windowbox', onUpdate: save_dashboard_layout});

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

/* ]]> */
</script>
<?php
if ($CONFIG['debug']) $dbg .= "\nLang: {$_SESSION['lang']}\n";
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>