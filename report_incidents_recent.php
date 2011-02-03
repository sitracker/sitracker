<?php
// recent_incidents_table.php - Report showing a list of incidents logged in the past month
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// Comments: Shows a list of incidents that each site has logged


require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strRecentIncidents;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');
echo "<script type='text/javascript'>";
?>
//<![CDATA[
function incident_details_window_l(incidentid,second)
{
    URL = "<?php  echo $CONFIG['application_uriprefix'].$CONFIG['application_webpath'] ?>incident_details.php?id=" + incidentid + "&amp;javascript=enabled";
    window.open(URL, "sit_popup", "toolbar=yes,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=700,height=600");
}
//]]>
<?php
echo "</script>";

$sites = array();

$monthago = time()-(60 * 60 * 24 * 30.5);

echo "<h2>{$strRecentIncidents} (".sprintf($strSinceX, ldate($CONFIG['dateformat_date'], $monthago)).")</h2>";

$sql  = "SELECT *,s.id AS siteid FROM `{$dbSites}` AS s, `{$dbMaintenance}` AS m, `{$dbSupportContacts}` AS sc, `{$dbIncidents}` AS i ";
$sql .= "WHERE s.id = m.site ";
$sql .= "AND ((m.id = sc.maintenanceid ";
$sql .= "AND sc.contactid = i.contact) ";
$sql .= "OR (m.allcontactssupported = 'yes' AND i.contact in (SELECT id FROM contacts WHERE siteid = s.id))) ";
$sql .= "AND i.opened > '{$monthago}' ";
$sql .= "ORDER BY s.id, i.id";

echo $sql."<br />";

$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error: ".mysql_error(), E_USER_WARNING);

if (mysql_num_rows($result) > 0)
{
    $prvincid = 0;
    while ($row = mysql_fetch_object($result))
    {
        if ($prvincid!=$row->id)
        {
            echo "<strong>[{$row->siteid}] {$row->name}</strong> {$strIncident}: <a href=\"javascript:incident_details_window_l('{$row->id}', 'incident{$row->id}')\">{$row->id}</a>  ";
            echo "{$strDate}: ".ldate('d M Y', $row->opened)." ";
            echo "{$strProduct}: ".product_name($row->product);
            $site = $row->siteid;
            $$site++;
            $sites[] = $row->siteid;
            echo "<br />\n";
        }
        $prvincid = $row->id;
        // print_r($row);
    }
}
else
{
    echo "<p class='warning'>{$strNoRecords}</p>";
}

$sites = array_unique($sites);

/*
foreach ($sites AS $site => $val)
{
  $tot[$val] = $$val;
}

rsort($tot);

foreach ($tot AS $total => $val)
{
  echo "total: $total   value: $val <br />";
}
*/

$totals = array();

foreach ($sites AS $site => $val)
{
    if ($prev > $$val) array_push($totals, $val);
    else array_unshift($totals, $val);
    $prev=$$val;
}


// was sites
/*
foreach ($totals AS $site => $val)
{
  echo "[{$val}] ".site_name($val);
  echo "= {$$val} <br />";
}
*/

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>