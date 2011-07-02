<?php
// search2.php - New search
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Kieran Hogg <kieran[at]sitracker.org>
// TODO eventually this needs refactorising, just couldn't do it well enough for this release

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');
$q = cleanvar($_GET['q']);

$title = $strSearch;

$resultsperpage = 20;
$domain = cleanvar($_GET['domain']);
$sort = cleanvar($_GET['sort']);
$order = cleanvar($_GET['order']);
$filter = array('start' => $start, 'order' => $order, 'q' => $q);
$hits = 0;
if (!isset($_GET['start']))
{
    $start = 0;
}
else
{
    $start = $_GET['start'];
}

$domain = cleanvar($_GET['domain']);

if (isset($_GET['q']))
{
    $q = cleanvar($_GET['q']);
}
elseif (isset($_GET['search_string']))
{
    $q = $_GET['search_string'];
}

$filter = array('start' => $start, 'domain' => $domain, 'q' => $q);

/**
 * Highlight a string to show it as matched, within a search result
 * @author Ivan Lucas
 * @param string $x the search result
 * @param string $var the term to be highlighted within the search result
 */
function search_highlight($x,$var)
{
    //$x is the string, $var is the text to be highlighted
    $x = strip_tags($x);
    $x = str_replace("\n", '', $x);
    // Trim the string to a reasonable length
    $pos1 = stripos($x, $var);
    if ($pos1 === FALSE) $pos1 = 0;
    if ($pos1 > 30) $pos1 -= 25;
    $pos2 = mb_strlen($var) + 70;
    $x = mb_substr($x, $pos1, $pos2);

    if ($var != '')
    {
        $xtemp = '';
        $i = 0;

        while ($i < mb_strlen($x))
        {
            if ((($i + mb_strlen($var)) <= mb_strlen($x)) && (strcasecmp($var, mb_substr($x, $i, mb_strlen($var))) == 0))
            {
                $xtemp .= "<span class='search_highlight'>" . mb_substr($x, $i , mb_strlen($var)) . "</span>";
                $i += mb_strlen($var);
            }
            else
            {
                $xtemp .= $x{$i};
                $i++;
            }
        }
        $x = $xtemp;
    }
    return $x;
}


include (APPLICATION_INCPATH . 'htmlheader.inc.php');


if (is_numeric($q))
{
    if (FALSE !== incident_status($q))
    {
        $js = '';
        $sql = "SELECT id FROM `{$dbIncidents}` WHERE id='{$q}'";
        $result = mysql_query($sql);
        if (mysql_num_rows($result) > 0)
        {
            echo "<script type='text/javascript'>//<![CDATA[\n";
            if ($_SESSION['userconfig']['incident_popup_onewindow'])
            {
                echo "window.location = 'incident_details.php?id={$q};";
            }
            else
            {
                echo "window.location = 'incident_details.php?id={$q}&win=jump&return=";
                if (!empty($_SERVER['HTTP_REFERER']))
                {
                    echo $_SERVER['HTTP_REFERER'];
                }
                else
                {
                    echo $_CONFIG['application_webpath'];
                }
            }

            echo "';\n";
            echo "//]]></script>";
        }
    }
}

echo "<h2>".icon('search', 32)." {$strSearch} {$CONFIG['application_shortname']}</h2>";

if (!empty($q))
{
    //for the search plugin
    $search = $q;

    //INCIDENT RESULTS
    // MySQL doesn't normally do fulltext index for words 3 characters or shorter
    // See the MySQL option ft_min_word_len
    if (mb_strlen($search > 3))
    {
        $incidentsql = "SELECT SQL_CALC_FOUND_ROWS *,incidentid AS id, i.title, ";
        $incidentsql .= "MATCH (bodytext) AGAINST ('{$search}' IN BOOLEAN MODE) AS score ";
        $incidentsql .= "FROM `{$dbUpdates}` as u, `{$dbIncidents}` as i ";
        $incidentsql .= "WHERE (MATCH (bodytext) AGAINST ('{$search}' IN BOOLEAN MODE)) ";
        $incidentsql .= "AND u.incidentid=i.id ";
        $incidentsql .= "GROUP BY u.incidentid ";
    }
    else
    {
        $incidentsql = "SELECT SQL_CALC_FOUND_ROWS *,incidentid AS id, i.title, ";
        $incidentsql .= "1 AS score ";
        $incidentsql .= "FROM `{$dbUpdates}` as u, `{$dbIncidents}` as i ";
        $incidentsql .= "WHERE bodytext LIKE '% {$search} %' ";
        $incidentsql .= "AND u.incidentid=i.id ";
        $incidentsql .= "GROUP BY u.incidentid ";
    }

    if ($domain == 'incidents' AND !empty($sort))
    {
        if ($sort == 'id')
        {
            $incidentsql .= "ORDER BY i.id ";
        }
        elseif ($sort == 'incident')
        {
            $incidentsql .= " ORDER BY i.title ";
        }
        else
        {
            $incidentsql .= " ORDER BY score ";
        }

        if ($order == 'a' OR $order == 'ASC' OR $order == '')
        {
            $incidentsql .= "ASC";
        }
        else
        {
            $incidentsql .= "DESC";
        }
    }
    else
    {
        $incidentsql .= " ORDER BY score, i.id DESC ";
    }

    if ($domain == 'incidents')
    {
        $incidentsql .= "LIMIT {$start}, {$resultsperpage} ";
    }
    else
    {
        $incidentsql .= "LIMIT 0, {$resultsperpage} ";
    }
    $incidentresult = mysql_query($incidentsql);
    $resultq = mysql_query("SELECT FOUND_ROWS() AS rows");
    $resulto = mysql_fetch_object($resultq);
    $results = $resulto->rows;
    if ($incidentresult AND $results > 0)
    {
        echo "<h3>".icon('support', 32)." {$strIncidents}</h3>";
        $hits++;

        if ($domain == 'incidents')
        {
            $end = $start + $resultsperpage;
            $begin = $start;
        }
        else
        {
            $end = $resultsperpage;
            $begin = 0;
        }

        if ($end > $results)
        {
            $end = $results;
        }
        echo "<p align='center'>".sprintf($strShowingXtoXofX,
                                          "<strong>".($begin + 1)."</strong>",
                                          "<strong>".$end."</strong>",
                                          "<strong>{$results}</strong>")."</p>";
        echo "<p align='center'>";
        if (!empty($start) AND $domain == 'incidents')
        {
            echo " <a href='{$_SERVER['PHP_SELF']}?domain=incidents&q=".urlencode($q)."&amp;start=";
            echo $begin-$resultsperpage."&amp;sort={$sort}&amp;order={$order}&amp;view={$view}'>";
            echo icon('leftarrow', 16, $strPrevious)." {$strPrevious}</a> ";
        }
        else
        {
            echo "{$strPrevious}";
        }

        echo " | ";
        if ($end < $results)
        {
            echo " <a href='{$_SERVER['PHP_SELF']}?domain=incidents&q=".urlencode($q)."&amp;start=";
            echo $begin+$resultsperpage."&amp;sort={$sort}&amp;order={$order}&amp;view={$view}'>{$strNext} ";
            echo icon('rightarrow', 16, $strNext)."</a> ";
        }
        else
        {
            echo "{$strNext}";
        }

        echo "</p>";
        echo "<table align='center' width='80%'>";
        $filter['domain'] = 'incident';
        echo "<tr>".colheader(id, $strID, $sort, $order, $filter);
        echo colheader(incident, $strIncident, $sort, $order, $filter);
        echo colheader(result, $strResult, $sort, $order, $filter);
        //echo colheader(score, $strScore, $sort, $order, $filter);
        echo colheader(date, $strDate, $sort, $order, $filter);

        $shade = 'shade1';
        while($row = mysql_fetch_object($incidentresult))
        {
            echo "<tr class='{$shade}'>
                    <td><a href=\"incident_details.php?id={$row->id}\">{$row->id}</a></td>
                    <td>".html_incident_popup_link($row->id, search_highlight($row->title, $search))."</td>
                    <td>".search_highlight($row->bodytext, $search)."</td>
                    <td>".ldate($CONFIG['dateformat_datetime'], $row->timestamp)."</td></tr>";

            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        plugin_do('search_incidents');
        echo "</table>";
    }


    //SITE RESULTS
    $sitesql = "SELECT *,MATCH (name) AGAINST ('{$search}' IN BOOLEAN MODE) AS score ";
    $sitesql .= "FROM `{$dbSites}` as s ";
    $sitesql .= "WHERE MATCH (name) AGAINST ('{$search}' IN BOOLEAN MODE) ";

    if ($domain == 'sites' AND !empty($sort))
    {
        if ($sort == 'id') $sitesql .= "ORDER BY k.title ";
        elseif ($sort == 'incident') $sitesql .= " ORDER BY k.published ";
        elseif ($sort == 'date') $sitesql .= " ORDER BY k.keywords ";
        else $sitesql .= " ORDER BY u.score ";

        if ($order == 'a' OR $order == 'ASC' OR $order == '') $sitesql .= "ASC";
        else $sitesql .= "DESC";
    }
    else
    {
        $sitesql .= " ORDER BY score DESC ";
    }

    $countsql = $sitesql;

    if ($domain == 'sites')
    {
        $sitesql .= "LIMIT {$start}, {$resultsperpage} ";
    }

    if ($siteresult = mysql_query($sitesql) AND mysql_num_rows($siteresult) > 0)
    {
        echo "<h3>".icon('site', 32)." {$strSites}</h3>";
        $hits++;
        $results = mysql_num_rows($siteresult);
        $countresult = mysql_query($countsql);
        $results = mysql_num_rows($countresult);

        if ($domain == 'sites')
        {
            $end = $start + $resultsperpage;
            $begin = $start;
        }
        else
        {
            $end = $resultsperpage;
            $begin = 0;
        }

        if ($end > $results)
        {
            $end = $results;
        }
        echo "<p align='center'>".sprintf($strShowingXtoXofX,
                                          "<strong>".($begin+1)."</strong>",
                                          "<strong>".$end."</strong>",
                                          "<strong>".$results."</strong>")."<br />";
        if (!empty($start) AND $domain == 'sites')
        {
            echo " <a href='{$_SERVER['PHP_SELF']}?domain=sites&q={$q}&start=";
            echo $begin-$resultsperpage."&amp;sort={$sort}&amp;order={$order}&amp;view={$view}'>";
            echo "".icon('leftarrow', 16, $strPrevious)." {$strPrevious}</a> ";
        }
        else
        {
            echo "{$strPrevious}";
        }

        echo " | ";
        if ($end < $results)
        {
            echo " <a href='{$_SERVER['PHP_SELF']}?domain=sites&q={$q}&start=";
            echo $begin+$resultsperpage."&amp;sort={$sort}&amp;order={$order}&amp;view={$view}'>{$strNext} ";
            echo icon('rightarrow', 16, $strNext)."</a>";
        }
        else
        {
            echo "{$strNext}";;
        }
        echo "</p>";
        echo "<table align='center' width='30%'>";
        $filter['domain'] = 'sites';
        echo "<tr>".colheader(id, $strID, $sort, $order, $filter);
        echo colheader(sitename, $strSiteName, $sort, $order, $filter);
        echo colheader(dept, $strDepartment, $sort, $order, $filter);

        $shade = 'shade1';
        while ($row = mysql_fetch_object($siteresult))
        {
            echo "<tr class='{$shade}'>
                    <td>{$row->id}</td>
                    <td><a href='site_details.php?id={$row->id}&action=show'>{$row->name}</a></td>
                    <td>{$row->department}</td>
                  </tr>";

            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        plugin_do('search_sites');
        echo "</table>";
    }

    //CONTACT RESULTS
    $contactsql = "SELECT *, c.id AS contactid, s.name AS sitename, MATCH (forenames, surname) AGAINST ('{$search}' IN BOOLEAN MODE) AS score ";
    $contactsql .= "FROM `{$dbContacts}` as c, `{$dbSites}` AS s ";
    $contactsql .= "WHERE MATCH (forenames, surname) AGAINST ('{$search}' IN BOOLEAN MODE) ";
    $contactsql .= "AND c.siteid=s.id ";

    if ($domain == 'contacts' AND !empty($sort))
    {
        if ($sort == 'id') $contactsql .= "ORDER BY k.title ";
        elseif ($sort == 'incident') $contactsql .= " ORDER BY k.published ";
        elseif ($sort == 'date') $$contactsql .= " ORDER BY k.keywords ";
        else $contactsql .= " ORDER BY u.score ";

        if ($order == 'a' OR $order == 'ASC' OR $order == '') $contactsql .= "ASC";
        else $contactsql .= "DESC";
    }
    else
    {
        $contactsql .= " ORDER BY score DESC ";
    }


    $countsql = $contactsql;

    if ($domain == 'contacts')
    {
        $contactsql .= "LIMIT {$start}, {$resultsperpage} ";
    }

    if ($contactresult = mysql_query($contactsql) AND mysql_num_rows($contactresult) > 0)
    {
        echo "<h3>".icon('contact', 32)." {$strContacts}</h3>\n";
        $hits++;
        $results = mysql_num_rows($contactresult);
        $countresult = mysql_query($countsql);
        $results = mysql_num_rows($countresult);
        if ($domain == 'contacts')
        {
            $end = $start + $resultsperpage;
            $begin = $start;
        }
        else
        {
            $end = $resultsperpage;
            $begin = 0;
        }

        if ($end > $results)
        {
            $end = $results;
        }
        echo "<p align='center'>".sprintf($strShowingXtoXofX,
                                          "<strong>".($begin+1)."</strong>",
                                          "<strong>".$end."</strong>",
                                          "<strong>".$results."</strong>")."</p>\n";
        echo "<p align='center'>\n";
        if (!empty($start) AND $domain == 'contacts')
        {
            echo " <a href='{$_SERVER['PHP_SELF']}?domain=contacts&q={$q}&start=";
            echo $begin-$resultsperpage."&amp;sort={$sort}&amp;order={$order}";
            echo "&amp;view={$view}'>\n";
            echo icon('leftarrow', 16, $strPrevious)." {$strPrevious}</a> ";
        }
        else
        {
            echo "{$strPrevious}";
        }

        echo " | ";
        if ($end < $results)
        {
            echo " <a href='{$_SERVER['PHP_SELF']}?domain=contacts&q={$q}&start=";
            echo $begin+$resultsperpage."&amp;sort={$sort}&amp;order={$order}";
            echo "&amp;view={$view}'>{$strNext} ";
            echo icon('rightarrow', 16, $strNext)."</a>\n";
        }
        else
        {
            echo "{$strNext}";;
        }
        echo "</p>";
        echo "<table align='center' width='80%'>\n";
        $filter['domain'] = 'contacts';
        echo "<tr>".colheader(name, $strName, $sort, $order, $filter);
        echo colheader(site, $strSiteName, $sort, $order, $filter);
        echo colheader(email, $strEmail, $sort, $order, $filter);
        echo colheader(telephone, $strTelephone, $sort, $order, $filter);
        echo colheader(fax, $strFax, $sort, $order, $filter);
        echo colheader(action, $strAction, $sort, $order, $filter);
        echo "</tr>";

        $shade = 'shade1';
        while($row = mysql_fetch_object($contactresult))
        {
            echo "<tr class='{$shade}'>
                    <td>
                        <a href='contact_details.php?id={$row->contactid}'>
                        {$row->forenames} {$row->surname}</a>
                    </td>
                    <td>{$row->sitename}</td>
                    <td>{$row->email}</td>
                    <td>{$row->telephone}</td>
                    <td>{$row->fax}</td>
                    <td><a href='incident_new.php?action=findcontact&amp;contactid={$row->contactid}'>
                        {$strNewIncident}</a>
                    </td>
                  </tr>";

            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        plugin_do('search_contacts');
        echo "</table>";
    }

    //USER RESULTS
    $usersql = "SELECT *,MATCH (realname) AGAINST ('{$search}' IN BOOLEAN MODE) AS score ";
    $usersql .= "FROM `{$dbUsers}` ";
    $usersql .= "WHERE MATCH (realname) AGAINST ('{$search}' IN BOOLEAN MODE) ";

    if ($domain == 'users' AND !empty($sort))
    {
        if ($sort == 'id') $usersql .= "ORDER BY k.title ";
        elseif ($sort == 'incident') $usersql .= " ORDER BY k.published ";
        elseif ($sort == 'date') $usersql .= " ORDER BY k.keywords ";
        else $usersql .= " ORDER BY u.score ";

        if ($order == 'a' OR $order == 'ASC' OR $order == '') $usersql .= "ASC";
        else $usersql .= "DESC";
    }
    else
    {
        $usersql .= " ORDER BY score DESC ";
    }


    $countsql = $usersql;

    if ($domain == 'users')
    {
        $usersql .= "LIMIT {$start}, {$resultsperpage} ";
    }

    if ($userresult = mysql_query($usersql) AND mysql_num_rows($userresult) > 0)
    {
        echo "<h3>".icon('user', 32)." {$strUsers}</h3>\n";
        $hits++;
        $results = mysql_num_rows($userresult);
        $countresult = mysql_query($countsql);
        $results = mysql_num_rows($countresult);
        if ($domain == 'users')
        {
            $end = $start + $resultsperpage;
            $begin = $start;
        }
        else
        {
            $end = $resultsperpage;
            $begin = 0;
        }

        if ($end > $results)
        {
            $end = $results;
        }
        echo "<p align='center'>".sprintf($strShowingXtoXofX,
                                          "<strong>".($begin+1)."</strong>",
                                          "<strong>".$end."</strong>",
                                          "<strong>".$results."</strong>")."</p>\n";
        echo "<p align='center'>";
        if (!empty($_GET['start']))
        {
            echo " <a href='{$_SERVER['PHP_SELF']}?domain=users&q={$q}&start=";
            echo $begin-$resultsperpage."&amp;sort={$sort}&amp;order={$order}&amp;view={$view}'>";
            echo icon('leftarrow', 16,  $strPrevious)." {$strPrevious}</a> ";
        }
        else
        {
            echo "{$strPrevious}";
        }
        echo " | ";
        if ($end < $results)
        {
            echo " <a href='{$_SERVER['PHP_SELF']}?domain=users&q={$q}&start=";
            echo $begin+$resultsperpage."&amp;sort={$sort}&amp;order={$order}&amp;view={$view}'>{$strNext} ";
            echo icon('rightarrow', 16,  $strNext)."</a> ";
        }
        else
        {
            echo "{$strNext}";;
        }
        echo "</p>\n";
        echo "<table align='center' width='50%'>\n";
        $filter['domain'] = 'users';
        echo "<tr>".colheader(name, $strID, $sort, $order, $filter);
        echo colheader(email, $strEmail, $sort, $order, $filter);
        echo colheader(telephone, $strTelephone, $sort, $order, $filter);
        echo "</tr>";

        $shade = 'shade1';
        while($row = mysql_fetch_object($userresult))
        {
            echo "<tr class='{$shade}'>
                    <td>".user_online_icon($row->id)." {$row->realname}</td>
                    <td>{$row->email}</td>
                    <td>{$row->phone}</td>
                  </tr>";

            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        plugin_do('search_user_results');
        echo "</table>";
    }

    //KB RESULTS
    $kbsql = "SELECT *,MATCH (title, keywords) AGAINST ('{$search}' IN BOOLEAN MODE) AS score ";
    $kbsql .= "FROM `{$dbKBArticles}` as k ";
    $kbsql .= "WHERE MATCH (title, keywords) AGAINST ('{$search}' IN BOOLEAN MODE) ";

    if ($domain == 'kb' AND !empty($sort))
    {
        if ($sort == 'id') $kbsql .= "ORDER BY k.title ";
        elseif ($sort == 'incident') $kbsql .= " ORDER BY k.published ";
        elseif ($sort == 'date') $kbsql .= " ORDER BY k.keywords ";
        else $kbsql .= " ORDER BY k.score ";

        if ($order == 'a' OR $order == 'ASC' OR $order == '') $kbsql .= "ASC";
        else $kbsql .= "DESC";
    }
    else
    {
        $kbsql .= " ORDER BY score DESC ";
    }


    $countsql = $kbsql;

    if ($domain == 'kb')
    {
        $kbsql .= "LIMIT {$start}, {$resultsperpage} ";
    }

    if ($kbresult = mysql_query($kbsql) AND mysql_num_rows($kbresult) > 0)
    {
        echo "<h3>".icon('kb', 32)." {$strKnowledgeBase}</h3>";
        $hits++;
        $results = mysql_num_rows($kbresult);
        $countresult = mysql_query($countsql);
        $results = mysql_num_rows($countresult);
        if ($domain == 'users')
        {
            $end = $start + $resultsperpage;
            $begin = $start;
        }
        else
        {
            $end = $resultsperpage;
            $begin = 0;
        }

        if ($end > $results)
        {
            $end = $results;
        }
        echo "<p align='center'>".sprintf($strShowingXtoXofX,
                                          "<strong>".($begin+1)."</strong>",
                                          "<strong>".$end."</strong>",
                                          "<strong>".$results."</strong>")."</p>";
        echo "<p align='center'>";
        if (!empty($_GET['start']) AND $domain == 'kb')
        {
            echo " <a href='{$_SERVER['PHP_SELF']}?domain=kb&q={$q}&start=";
            echo $begin-$resultsperpage."&amp;sort={$sort}&amp;order={$order}&amp;view={$view}'>";
            echo icon('leftarrow', 16,  $strPrevious)." {$strPrevious}</a> ";        }
        else
        {
            echo "{$strPrevious}";
        }
        echo " | ";
        if ($end < $results)
        {
            echo " <a href='{$_SERVER['PHP_SELF']}?domain=kb&q={$q}&start=";
            echo $begin+$resultsperpage."&amp;sort={$sort}&amp;order={$order}&amp;view={$view}'>{$strNext} ";
            echo icon('rightarrow', 16,  $strNext)."</a> ";
        }
        else
        {
            echo "{$strNext}";;
        }
        echo "</p>";
        echo "<table align='center' width='80%'>";
        $filter['domain'] = 'kb';
        echo "<tr>".colheader(id, $strID, $sort, $order, $filter);
        echo colheader(title, $strTitle, $sort, $order, $filter);
        echo colheader(date, $strDate, $sort, $order, $filter);
        echo colheader(author, $strAuthor, $sort, $order, $filter);
        echo colheader(keywords, $strKeywords, $sort, $order, $filter);

        $shade = 'shade1';
        while($row = mysql_fetch_object($kbresult))
        {
            echo "<tr class='{$shade}'>
                    <td><a href='kb_view_article.php?id={$row->docid}'>
                        {$CONFIG['kb_id_prefix']}{$row->docid}</a></td>
                    <td>{$row->title}</td>
                    <td>{$row->published}</td>
                    <td>".user_realname($row->author)."</td>
                    <td>{$row->keywords}</td>
                  </tr>";

            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        plugin_do('search_kb');
        echo "</table>";
    }

    $sql = "SELECT * FROM `{$dbTags}` WHERE name LIKE '%{$q}%'";

    $result = mysql_query($sql);
    if (mysql_num_rows($result) > 0)
    {
        echo "<h3>{$strTags}</h3>";
        echo "<p align='center'>";
        while ($row = mysql_fetch_object($result))
        {
            $countsql = "SELECT COUNT(id) AS counted FROM `{$dbSetTags}` ";
            $countsql .= "WHERE tagid='{$row->tagid}' ";
            $countsql .= "GROUP BY tagid ";
            $countsql .= "ORDER BY counted ASC LIMIT 1";
            $countresult = mysql_query($countsql);
            $countrow = mysql_fetch_object($countresult);

            echo "<a href='view_tags.php?tagid=$row->tagid' class='taglevel1' style='font-size: 400%; font-weight: normal;' title='{$countrow->counted}'>";
            if (array_key_exists($row->name, $CONFIG['tag_icons']))
            {
                echo "{$row->name}&nbsp;<img src='images/icons/{$iconset}/32x32/{$CONFIG['tag_icons'][$row->name]}.png' alt='' />";
            }
            else echo $row->name;
            echo "</a>";
            echo " ({$countrow->counted}) ";
        }
        echo  "</p>";
    }
}
if (!empty($q) AND mb_strlen($q) < 3)
{
    echo "<p class='info'>{$strSearchTooShort}</p>";
}
elseif (!empty($q) AND $hits == 0)
{
    echo "<p align='center'>".sprintf($strNoResultsFor, "<strong>'".$q."'</strong>")."<br />";
    echo "<a href='search.php'>{$strSearchAgain}</a></p>";
}

echo "<br />";

$search_domain = cleanvar($_REQUEST['domain']);
if (empty($search_domain) OR strtolower($search_domain) == 'all')
{
    $domain = 'incidents';
}

$sort = cleanvar($_REQUEST['sort']);
if (empty($sort)) $sort = 'date';
$order = cleanvar($_REQUEST['order']);
if (empty($order)) $order = 'd';

echo "<form action='{$_SERVER['PHP_SELF']}' method='get'>";
echo "<table align='center'>";
echo "<tr><th>";
echo "{$strSearch}: ";
echo "</th>";
echo "<td>";
echo "<input maxlength='100' name='q' size='35' type='text' value='".strip_tags(urldecode($q))."' /> ";
echo html_action_links(array($strAdvanced => 'search_incidents_advanced.php',
                             $strTagCloud => 'view_tags.php'));
echo "</td>";
echo "</tr>\n";
echo "</table>\n";
echo "<p align='center'><input type='submit' value='";
if (empty($q))
{
    echo $strSearch;
}
else
{
    echo $strSearchAgain;
}

echo "' /></p></form>";

echo "<h3>".icon('help', 32, 'Help', 'Help', 'search_help')."   Search Help:</h3>";
echo "<div class='help' id='help'>";
echo file_get_help_textfile('SearchHelp');
echo "<a class = 'helplink' href='http://sitracker.org/wiki/Search'>{$strReadWikiArticleHere} ..</a></div>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>