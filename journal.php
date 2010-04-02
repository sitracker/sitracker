<?php
// browse_journal.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!   4Nov05


$permission = 22; // administrate
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

$title = $strBrowseJournal;

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$offset = cleanvar($_REQUEST['offset']);
$page = cleanvar($_REQUEST['page']);
$perpage = cleanvar($_REQUEST['perpage']);
$search_string = cleanvar($_REQUEST['search_string']);
$type = cleanvar($_REQUEST['type']);
$sort = cleanvar($_REQUEST['sort']);
$order = cleanvar($_REQUEST['order']);

if (empty($perpage)) $perpage = 30;
if (empty($page)) $page = 1;

if (empty($search_string)) $search_string='a';

include (APPLICATION_INCPATH . 'htmlheader.inc.php');
echo "<h2>{$title}</h2>";


// Count number of journal records
$sql = "SELECT COUNT(id) FROM `{$dbJournal}`";
$result = mysql_query($sql);
list($totaljournals) = mysql_fetch_row($result);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

if ($offset == '' AND $page > 0) $offset = (($page -1) * $perpage);
elseif ($offset=='' AND empty($page)) $offset=0;

$sql = "SELECT * FROM `{$dbJournal}` ";
if (!empty($type)) $sql .= "WHERE journaltype='{$type}' ";
// Create SQL for Sorting
if (!empty($sort))
{
    if ($order == 'a' OR $order == 'ASC' OR $order == '') $sortorder = "ASC";
    else $sortorder = "DESC";
    switch ($sort)
    {
        case 'userid':
            $sql .= " ORDER BY userid $sortorder";
            break;
        case 'timestamp':
            $sql .= " ORDER BY timestamp $sortorder";
            break;
        case 'refid':
            $sql .= " ORDER BY c.surname $sortorder, c.forenames $sortorder";
            break;
        default:
            $sql .= " ORDER BY timestamp DESC";
            break;
    }
}
else
{
    $sql .= " ORDER BY timestamp DESC";
}
$sql .= " LIMIT $offset, $perpage ";
$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

// FIXME needs i18n
$journaltype[CFG_JOURNAL_DEBUG] = 'Debug';
$journaltype[CFG_JOURNAL_LOGIN] = 'Logon/Logoff';
$journaltype[CFG_JOURNAL_SUPPORT] = 'Support Incidents';
$journaltype[CFG_JOURNAL_SALES] = 'Sales Incidents';  // Obsolete
$journaltype[CFG_JOURNAL_SITES] = 'Sites';
$journaltype[CFG_JOURNAL_CONTACTS] = 'Contacts';
$journaltype[CFG_JOURNAL_ADMIN] = 'Admin';
$journaltype[CFG_JOURNAL_USER] = 'User Management';
$journaltype[CFG_JOURNAL_MAINTENANCE] = 'Maintenance';
$journaltype[CFG_JOURNAL_PRODUCTS] = 'Products';
$journaltype[CFG_JOURNAL_OTHER] = 'Other';
$journaltype[CFG_JOURNAL_TRIGGERS] = 'Triggers';
$journaltype[CFG_JOURNAL_KB] = 'Knowledge Base';
$journaltype[CFG_JOURNAL_TASKS] = 'Tasks';

$journal_count = mysql_num_rows($result);
if ($journal_count >= 1)
{
    echo "<table align='center'>";
    echo "<tr>";
    $filter = array('page' => $page);
    echo colheader('userid',$strUser,$sort, $order, $filter);
    echo colheader('timestamp',"{$strTime}/{$strDate}",$sort, $order, $filter);
    echo colheader('event',$strEvent);
    echo colheader('action',$strOperation);
    echo colheader('type',$strType);
    echo "</tr>\n";
    $shade = 0;
    while ($journal = mysql_fetch_object($result))
    {
        // define class for table row shading
        if ($shade) $class = "shade1";
        else $class = "shade2";
        echo "<tr class='$class'>";
        echo "<td>".user_realname($journal->userid,TRUE)."</td>";
        echo "<td>".ldate($CONFIG['dateformat_datetime'], mysqlts2date($journal->timestamp))."</td>";
        echo "<td>{$journal->event}</td>";
        echo "<td>";
        switch ($journal->journaltype)
        {
            case 2:
                echo "<a href='incident_details.php?id={$journal->refid}' target='_blank'>{$journal->bodytext}</a>";
                break;
            case 5:
                echo "<a href='contact_details.php?id={$journal->refid}' target='_blank'>{$journal->bodytext}</a>";
                break;
            default:
                echo "{$journal->bodytext}";
                if (!empty($journal->refid)) echo "(Ref: {$journal->refid})";
                break;
        }
        echo "</td>";
        echo "<td><a href='{$_SERVER['PHP_SELF']}?type={$journal->journaltype}'>{$journaltype[$journal->journaltype]}</a></td>";
        echo "</tr>\n";
        // invert shade
        if ($shade == 1) $shade = 0;
        else $shade = 1;
    }
    echo "</table>\n";

    printf("<p align='center'>{$strXRecords}</p>", $totaljournals);
    $pages = ceil($totaljournals / $perpage);
    $numpagelinks = $pages > 10 ? $numpagelinks = 10: $numpagelinks = $pages;

    echo "<p align='center'>";

    if ($page > 3 AND $pages > 10) $minpage = $page - 3;
    else $minpage = ($page - 2);
    if ($minpage < 1) $minpage = 1;
    $maxpage = $minpage + $numpagelinks;
    if ($maxpage > $pages + 1) $maxpage = $pages + 1;
    if ($minpage >= ($maxpage - $numpagelinks)) $minpage = $maxpage - $numpagelinks;

    $prev = $page - 1;
    $next = $page + 1;

    if ($page > 1) echo "<a href='{$_SERVER['PHP_SELF']}?page={$prev}'>&lt; {$strPrevious}</a>&nbsp;";
    if ($minpage > 3)
    {
        echo "<a href='{$_SERVER['PHP_SELF']}?page=1'>1</a> {$strEllipsis} ";
    }

    for ($i=$minpage;$i<$maxpage;$i++)
    {
        if ($i <> $page) echo "<a href='{$_SERVER['PHP_SELF']}?page={$i}'>$i</a> ";
        else echo "<strong>{$i}</strong> ";
    }

    if ($maxpage < ($pages -3))
    {
        echo " {$strEllipsis} <a href='{$_SERVER['PHP_SELF']}?page=$pages'>$pages</a>";
    }

    if ($page < $pages)
    {
        echo "&nbsp;";
        echo "<a href='{$_SERVER['PHP_SELF']}?page={$next}'>{$strNext} &gt;</a>";
    }
    echo "</p>";
}
else
{
    echo "<p>{$strNoResults}</p>";
}
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>
