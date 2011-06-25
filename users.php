<?php
// users.php - List users
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional! 31Oct05
// This page seems to sometimes generate a warning
// Warning: Unknown: Your script possibly relies on a session side-effect which existed until PHP 4.2.3. Please be advised that the session extension does not consider global variables as a source of data, unless register_globals is enabled. You can disable this functionality and this warning by setting session.bug_compat_42 or session.bug_compat_warn to off, respectively. in Unknown on line 0
// Not sure why - Ivan 6Sep06


$permission = PERM_USER_VIEW; // View Users
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$sort = cleanvar($_REQUEST['sort']);
$order = cleanvar($_REQUEST['order']);
$groupid = cleanvar($_REQUEST['gid']);
$onlineonly = cleanvar($_REQUEST['onlineonly']);

// By default show users in home group
if ($groupid == 'all' OR ($groupid == '' AND $_SESSION['groupid'] == 0))
{
    $filtergroup = 'all';
}
elseif ($groupid == '')
{
    $filtergroup = $_SESSION['groupid'];
}
else
{
    $filtergroup = $groupid;
}

$title = $strUsers;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('user', 32)." ";
echo "{$strUsers}</h2>";

$numgroups = group_selector($filtergroup);

$sql  = "SELECT * FROM `{$dbUsers}` WHERE status!=0 ";  // status=0 means account disabled
if ($numgroups >= 1 AND $filtergroup == '0')
{
    $sql .= "AND (groupid='0' OR groupid='' OR groupid IS NULL) ";
}
elseif ($numgroups == '' OR $numgroups < 1 OR $filtergroup == 'all' OR $filtergroup == 'allonline')
{
    $sql .= "AND 1=1 ";
}
else
{
    $sql .= "AND groupid='{$filtergroup}' ";
}

if ($onlineonly === 'true' OR $filtergroup === 'allonline' )
{
    $sql .= "AND lastseen > '".date('Y-m-d H:i:s', $startofsession). "' ";
}

if (!empty($sort))
{
    if ($sort == "realname") $sql .= " ORDER BY realname ";
    elseif ($sort == "jobtitle") $sql .= " ORDER BY title ";
    elseif ($sort == "email") $sql .= " ORDER BY email ";
    elseif ($sort == "phone") $sql .= " ORDER BY phone ";
    elseif ($sort == "fax") $sql .= " ORDER BY fax ";
    elseif ($sort == "status") $sql .= " ORDER BY status ";
    elseif ($sort == "accepting") $sql .= " ORDER BY accepting ";
    else $sql .= " ORDER BY realname ";

    if ($order == 'a' OR $order == 'ASC' OR $order == '') $sql .= "ASC";
    else $sql .= "DESC";
}
else $sql .= "ORDER BY realname ASC ";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

echo "<table id='userslisttable' align='center'>";
echo "<tr>";
$filter = array('gid' => $filtergroup);
echo colheader('realname', $strName, $sort, $order, $filter);
echo "<th colspan='5'>{$strIncidentsinQueue}</th>";
echo colheader('phone',$strTelephone,$sort, $order, $filter);
echo colheader('mobile',$strMobile,$sort, $order, $filter);
echo colheader('status',$strStatus,$sort, $order, $filter);
echo colheader('accepting',$strAccepting,$sort, $order, $filter);
echo "<th>{$strJumpTo}</th>";
echo "</tr><tr>";
echo "<th></th>";
echo "<th align='center'>{$strActionNeeded} / {$strWaiting}</th>";
echo "<th align='center'>".priority_icon(PRIORITY_CRITICAL)."</th>";
echo "<th align='center'>".priority_icon(PRIORITY_HIGH)."</th>";
echo "<th align='center'>".priority_icon(PRIORITY_MEDIUM)."</th>";
echo "<th align='center'>".priority_icon(PRIORITY_LOW)."</th>";
echo "<th colspan='8'></th>";
echo "</tr>\n";

// show results
$shade = 'shade1';
while ($users = mysql_fetch_object($result))
{
    // print HTML for rows
    echo "<tr class='{$shade}'>";
    echo "<td>";
    echo "<a href='mailto:{$users->email}' title='{$strEmail} ";
    echo "{$users->realname}'>";
    echo icon('email', 16, $strEmail)."</a> ";
    echo "<a href='incidents.php?user={$users->id}&amp;queue=1&amp;";
    echo "type=support' class='info'>";
    if (!empty($users->message))
    {
        echo icon('messageflag', 16, $strMessage, $users->message);
    }
    echo " {$users->realname}";
    echo "<span>";
    echo gravatar($users->email, 50, FALSE);
    if (!empty($users->title))
    {
        echo "<strong>{$users->title}</strong><br />";
    }

    if ($users->groupid > 0)
    {
        echo "{$strGroup}: ".db_read_column("name", $GLOBALS['dbGroups'], $users->groupid)."<br />";
    }
    if (mb_strlen($users->aim) > 3)
    {
        echo icon('aim', 16, $users->aim);
        echo " <strong>AIM</strong>: {$users->aim}<br />";
    }
    if (mb_strlen($users->icq) > 3)
    {
        echo icon('icq', 16, $users->icq);
        echo " <strong>ICQ</strong>: {$users->icq}<br />";
    }
    if (mb_strlen($users->msn) > 3)
    {
        echo icon('msn', 16, $users->msn);
        echo " <strong>MSN</strong>: {$users->msn}<br />";
    }
    if (!empty($users->message))
    {
        echo "<br />".icon('messageflag', 16);
        echo " <strong>{$strMessage}</strong>: {$users->message}";
    }
    echo "</span>";
    echo "</a>";
    echo "</td>";
    echo "<td align='center'><a href='incidents.php?user={$users->id}&amp;";
    echo "queue=1&amp;type=support'>";
    $incpriority = user_incidents($users->id);
    $countincidents = ($incpriority['1']+$incpriority['2']+$incpriority['3']+$incpriority['4']);
    if ($countincidents >= 1)
    {
        $countactive = user_activeincidents($users->id);
    }
    else
    {
        $countactive = 0;
    }

    $countdiff = $countincidents-$countactive;

    echo $countactive;
    echo "</a> / <a href='incidents.php?user={$users->id}&amp;queue=2&amp;";
    echo "type=support'>{$countdiff}</a></td>";
    $critical += $incpriority['4'];
    $high += $incpriority['3'];
    $med += $incpriority['2'];
    $low += $incpriority['1'];
    echo "<td align='center'>".$incpriority['4']."</td>";
    echo "<td align='center'>".$incpriority['3']."</td>";
    echo "<td align='center'>".$incpriority['2']."</td>";
    echo "<td align='center'>".$incpriority['1']."</td>";
    echo "<td align='center'>";
    if ($users->phone == '')
    {
        echo $strNone;
    }
    else
    {
        echo $users->phone;
    }

    echo "</td>";
    echo "<td align='center'>";

    if ($users->mobile == '')
    {
        echo $strNone;
    }
    else
    {
        echo $users->mobile;
    }
    echo "</td>";
    echo "<td align='left'>";
    //see if the users has been active in the last 30mins
    echo user_online_icon($users->id)." ";
    echo userstatus_name($users->status);
    echo "</td><td align='center'>";
    if ($users->accepting == 'Yes')
    {
        echo $strYes;
    }
    else
    {
        echo "<span class='negative'>{$strNo}</span>";
    }
    echo "</td><td>";
    echo "<a href='holidays.php?user={$users->id}' title='{$strHolidays}'>";
    echo icon('holiday', 16, $strHolidays)."</a> ";
    echo "<a href='tasks.php?user={$users->id}' title='{$strTasks}'>";
    echo icon('task', 16, $strTask)."</a> ";
    $sitesql = "SELECT COUNT(id) FROM `{$dbSites}` WHERE owner='{$users->id}'";
    $siteresult = mysql_query($sitesql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($ownedsites) = mysql_fetch_row($siteresult);
    if ($ownedsites > 0)
    {
        echo "<a href='sites.php?owner={$users->id}'";
        echo " title='{$strSites}'>";
        echo icon('site', 16, $strSite)."</a> ";
    }
    echo "</td>";
    echo "</tr>";

    if ($shade == 'shade1') $shade = 'shade2';
    else $shade = 'shade1';
}
$total = $critical + $high + $med + $low;
echo "<tr align='center'><td></td><td align='right'>";
echo "<strong>{$strTotal}</strong> ({$total})</td><td>{$critical}</td>";
echo "<td>{$high}</td><td>{$med}</td><td>{$low}</td>";

echo "</tr></table>\n";

mysql_free_result($result);

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>