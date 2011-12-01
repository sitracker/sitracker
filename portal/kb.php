<?php
//portal/kb.php - Show knowledgebase entries
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Kieran Hogg <kieran[at]sitracker.org>

require ('..'.DIRECTORY_SEPARATOR.'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

session_name($CONFIG['session_name']);
session_start();

$accesslevel = 'any';

if ($CONFIG['portal_kb_enabled'] !== 'Public' OR $_SESSION['portalauth'] == TRUE)
{
    include (APPLICATION_LIBPATH . 'portalauth.inc.php');
    $view = clean_fixed_list($_GET['view'], array('', 'all'));
}
else
{
    $view = 'all';
}
if ($CONFIG['kb_enabled'] AND $CONFIG['portal_kb_enabled'] !== 'Disabled')
{
    include (APPLICATION_INCPATH . 'portalheader.inc.php');

    echo "<h2>".icon('kb', 32, $strKnowledgeBase)." {$strKnowledgeBase}</h2>";
    $perpage = 20;
$order = clean_fixed_list($_GET['order'], array('', 'a', 'ASC', 'd', 'DESC'));
$sort = clean_fixed_list($_GET['sort'], array('', 'title', 'date', 'author', 'keywords'));

$start = clean_int($_GET['start']);

    $end = $start + $perpage;
    $filter = array('start' => $start, 'view' => $view);

    // $sql = "SELECT k.*, s.name FROM `{$dbKBArticles}` AS k,
    //                                $dbKBSoftware,
    //                                 `{$dbSoftware}` as s
    //         WHERE ((k.docid = kbs.docid AND kbs.softwareid = s.id) OR 1=1) AND k.distribution = 'public' ";
    // $sql = "
    // SELECT k.*, s.name FROM `{$dbKBArticles}` AS k,
    // LEFT OUTER JOIN `$dbKBSoftware`, `{$dbSoftware}`
    // ON k.docid = kbs.docid AND kbs.softwareid = s.id ";


    $sql = "SELECT k.*, s.name FROM `{$dbKBArticles}` AS k, `{$dbSoftware}` as s ";
    $sql .= "LEFT OUTER JOIN `{$dbKBSoftware}` as kbs ";
    $sql .= "ON kbs.softwareid=s.id ";
    $sql .= "WHERE (k.docid = kbs.docid OR 1=1) AND k.distribution='public' ";
    if ($CONFIG['portal_kb_enabled'] != 'Public')
    {
        if ($view != 'all')
        {
            $softwares = contract_software();
            $sql .= "AND (1=1 ";
            if (is_array($softwares))
            {
                foreach ($softwares AS $software)
                {
                    $sql .= "OR kbs.softwareid={$software} ";
                }
            }
            $sql .= ")";

            echo "<p class='info'>{$strShowingOnlyRelevantArticles} - ";
            echo "<a href='{$_SERVER['PHP_SELF']}?view=all'>{$strShowAll}</a></p>";
        }
        else
        {
            echo "<p class='info'>{$strShowingAllArticles} - ";
            echo "<a href='{$_SERVER['PHP_SELF']}'>{$strShowOnlyRelevant}</a></p>";
        }
    }
    //get the full SQL so we can see the total rows
    $countsql = $sql;
    $sql .= "GROUP BY k.docid ";
    if (!empty($sort))
    {
        if ($sort == 'title') $sql .= "ORDER BY k.title ";
        elseif ($sort == 'date') $sql .= " ORDER BY k.published ";
        elseif ($sort == 'author') $sql .= " ORDER BY k.author ";
        elseif ($sort == 'keywords') $sql .= " ORDER BY k.keywords ";
        else $sql .= " ORDER BY k.docid ";

        if ($order == 'a' OR $order == 'ASC' OR $order == '') $sql .= "ASC";
        else $sql .= "DESC";
    }
    else
    {
        $sql .= " ORDER BY k.docid DESC ";
    }
    $sql .= " LIMIT {$start}, {$perpage} ";

    if ($result = mysql_query($sql))
    {
        $countresult = mysql_query($countsql);
        $numtotal = mysql_num_rows($countresult);
        if ($end > $numtotal)
        {
            $end = $numtotal;
        }

        if ($numtotal > 0)
        {
            echo "<p align='center'>".sprintf($strShowingXtoXofX, $start+1, $end, $numtotal)."</p>";

            echo "<p align='center'>";

            if (!empty($_GET['start']))
            {
                echo " <a href='{$_SERVER['PHP_SELF']}?start=";
                echo $start-$perpage."&amp;sort={$sort}&amp;order={$order}&amp;view={$view}nerw'>{$strPrevious}</a> ";
            }
            else
            {
                echo $strPrevious;
            }
            echo " | ";
            if ($end != $numtotal)
            {
                echo " <a href='{$_SERVER['PHP_SELF']}?start=";
                echo $start+$perpage."&amp;sort={$sort}&amp;order={$order}&amp;view={$view}'>{$strNext}</a> ";
            }
            else
            {
                echo $strNext;
            }
            echo "</p>";

            echo "<table align='center' width='80%'><tr>";
            echo colheader('id', $strID, $sort, $order, $filter, '', '10');
            echo colheader('title', $strTitle, $sort, $order, $filter);
            echo colheader('date', $strDate, $sort, $order, $filter, '', '15');
            echo colheader('author', $strAuthor, $sort, $order, $filter);
            echo colheader('keywords', $strKeywords, $sort, $order, $filter, '', '15');
            echo "</tr>";
            $shade = 'shade1';
            while($row = mysql_fetch_object($result))
            {
                $url = "kbarticle.php?id={$row->docid}";
                // Tell the article page that we're in the portal so it can show menu etc.
                if ($_SESSION['portalauth'])
                {
                    $url .= "&amp;p=1";
                }
                echo "<tr class='{$shade}'>";
                echo "<td><a href=\"$url\">";
                echo icon('kb', 16, $strID);
                echo " {$CONFIG['kb_id_prefix']}{$row->docid}</a></td>";
                echo "<td>{$row->name}<br />";
                echo "<a href=\"$url\">{$row->title}</a></td>";
                echo "<td>";
                echo ldate($CONFIG['dateformat_date'], mysql2date($row->published));
                echo "</td>";
                echo "<td>{$row->author}</td>";
                echo "<td>{$row->keywords}</td></tr>";

                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }
            echo "</table>";
        }
        else
        {
            echo user_alert($strNoRecords, E_USER_NOTICE);
        }
    }
    else
    {
        user_alert($strNoRecords, E_USER_NOTICE);
    }
    if ($CONFIG['portal_kb_enabled'] == 'Public' AND $_SESSION['portalauth'] != TRUE)
    {
        echo "<p class='return'><a href=\"../index.php\">{$strBackToLoginPage}</a></p>";
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    html_redirect('../index.php', FALSE, $strDisabled); // KB Disabled
    exit;
}

?>