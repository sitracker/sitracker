<?php
// open_incident_monitor.php - Opens a fullscreen page displaying incident statistics
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


$permission = 14; // View Users
$title="Open Incident Monitor";
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

?>
<html>
<head>
<title>Open Incident Monitor</title>
<script type="text/javascript">

<!-- Begin
function start()
{
var _loc = "incident_monitor.php ";
var _name= "monitor";
var _features="fullscreen";
var _replace=true;
bigwin=window.open(_loc,_name,_features,_replace);
}

function end()
{
bigwin.close();
}

//end-->
</script>


</head>
<body onload="start()">

<a href="javascript:start()">Open the incident monitor</a><br />
<br />
<a href="javascript:end()">Close the incident monitor</a><br />
<br />

<a href="javascript:history.back();">Return to <?php echo $CONFIG['application_shortname']; ?></a><br />


</body>
</html>

<?php
?>
