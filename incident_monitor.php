<?php
// incident_monitor.php - Page displaying incident statistics
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// FIXME target 3.24 this page needs serious tidying up


$permission = 14; // View Users
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');
$title = $strIncidentMonitor;
?>
<html>
<head>
<title><?php  if (isset($title)) { echo $title; } else { echo $CONFIG['application_shortname']; } ?></title>
<link rel="stylesheet" href="/styles/fullscreen.css">
<meta HTTP-EQUIV=Refresh CONTENT="10; URL=<?php echo $_SERVER['PHP_SELF'] ?>">
<script type="text/javascript">
<!--
    function doIt()
    {
    self.focus();
    }
//-->
</script>

</head>
<body leftmargin=0 topmargin=0 marginheight=0 marginwidth=0 onload="doIt();">
<a href="javascript:window.close()">Close</a><br />
<table summary="monitor" width="90%" height="90%" align='center'>
<tr>
<td>
<?php

// Count incidents logged today
$sql = "SELECT id FROM `{$dbIncidents}` WHERE opened > '$todayrecent'";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
$todaysincidents = mysql_num_rows($result);
mysql_free_result($result);

// Count incidents updated today
$sql = "SELECT id FROM `{$dbIncidents}` WHERE lastupdated > '$todayrecent'";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
$todaysupdated = mysql_num_rows($result);
mysql_free_result($result);

// Count incidents closed today
$sql = "SELECT id FROM `{$dbIncidents}` WHERE closed > '$todayrecent'";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
$todaysclosed = mysql_num_rows($result);
mysql_free_result($result);

// count total number of SUPPORT incidents that are open at this time (not closed)
$sql = "SELECT id FROM `{$dbIncidents}` WHERE status!=2 AND status!=9 AND status!=7 AND type='support'";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
$supportopen = mysql_num_rows($result);
mysql_free_result($result);

$sql  = "SELECT * FROM `{$dbUsers}` WHERE var_monitor='true' ";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
$row = 1;
$col = 1;
$count = 1;
echo "<table summary=\"users\" width=\"100%\" height=\"100%\" align='center'><tr>";
while ($users = mysql_fetch_array($result))
{
    $outstanding = user_countincidents($users['id']);
    $userstatus = userstatus_name($users['status']);
    $useraccepting = strtolower($users['accepting']);
    $waiting = count_incoming_updates();
    $incidents_color = 'Blue';
    if ($outstanding >= 10) $incidents_color = 'Red';
    if ($outstanding < 5) $incidents_color = '#00BB00';
    $name_color = 'Blue';
    ?>
    <td><table summary="<?php echo $users['realname'] ?>">
    <tr>
    <?php
    echo "<td class='incidents' style='color: $incidents_color'>".$outstanding."</td>";
    echo "<td class='name'>".$users['realname']."</td>";
    // calcuate percentage
    $percentage = ($outstanding*10);
    if ($percentage > 100) $percentage = 100;
    ?>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td>
    <!-- Simple Status Bar -->
    <!-- <hr id="simpleStatBar" style="width: 10%;"> -->
    <!-- My Status Bar -->
    <div id="sb" style="border: 2 inset white; width: 200px; height: 20px; background: #F0F0F0; text-align: left;">
    <div style="position: absolute; width: <?php echo $percentage ?>%; height: 16px; filter: Alpha(Opacity=0, FinishOpacity=100, Style=1, StartX=0, StartY=0, FinishX=100, FinishY=0);">
    <div style="width: 100%; height: 100%; background: highlight; font-size: 1;"></div>
    </div>
    <div style="position: absolute; width: 100%; text-align: center; font-family: arial; font-size: 12px;"><?php  echo $percentage ?>%</div>
    </div>
    </td>
    </tr>
    <tr>
    <td align='left' colspan=2>
        <?php
        if ($useraccepting=='no') echo "<span style='color: #FF0000;'>";
        echo $userstatus;
        if ($useraccepting=='no') echo "</span>";
        if ($users['message'] != '') { echo ", ".substr($users['message'], 0, 25); } ?></td>
    </tr>
    </table>
    </td>
    <?php
    $col++;
    if ($col>2)
    {
    echo "</tr><tr>";
    $col=1;
    $row++;
    }
    $count++;
}
echo "</tr></table>";
?>
</td>
</tr>
<TR>
<td class='incidents'>
<?php
echo "Today's totals: <span style='color: #8a2be2;'>$supportopen $strOpen<span>, <span style='color: #0000AA;'>$waiting $strWaiting</span>, <span style='color: #00DD00;'>$todaysincidents $strOpened</span>, <span style='color: #BB00AA;'>$todaysupdated $strupdated</span>, <span style='color: #000000;'>$todaysclosed $strclosed</span>.";
?>
</td>
</tr>
</table>
</body>

<?php
echo "</html>";
?>
