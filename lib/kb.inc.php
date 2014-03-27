<?php
// kb.inc.php - functions relating to knowledgebase
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


/**
 * Output the html for a KB article
 *
 * @param int $id ID of the KB article
 * @param string $mode whether this is internal or external facing, defaults to internal
 * @return string $html kb article html
 * @author Kieran Hogg
 */
function kb_article($id, $mode='internal')
{
    global $CONFIG, $iconset;
    $id = intval($id);
    if (!is_numeric($id) OR $id == 0)
    {
        trigger_error("Incorrect KB ID", E_USER_ERROR);
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        exit;
    }

    $sql = "SELECT * FROM `{$GLOBALS['dbKBArticles']}` WHERE docid='{$id}' LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $kbarticle = mysql_fetch_object($result);

    if (empty($kbarticle->title))
    {
        $kbarticle->title = $GLOBALS['strUntitled'];
    }
    $html .= "<div id='kbarticle'";
    if ($kbarticle->distribution == 'private') $html .= " class='expired'";
    if ($kbarticle->distribution == 'restricted') $html .= " class='urgent'";
    $html .= ">";
    $html .= "<h2 class='kbtitle'>{$kbarticle->title}</h2>";

    if (!empty($kbarticle->distribution) AND $kbarticle->distribution != 'public')
    {
        $html .= "<h2 class='kbdistribution'>{$GLOBALS['strDistribution']}: ".ucfirst($kbarticle->distribution)."</h2>";
    }

    // Lookup what software this applies to
    $ssql = "SELECT * FROM `{$GLOBALS['dbKBSoftware']}` AS kbs, `{$GLOBALS['dbSoftware']}` AS s ";
    $ssql .= "WHERE kbs.softwareid = s.id AND kbs.docid = '{$id}' ";
    $ssql .= "ORDER BY s.name";
    $sresult = mysql_query($ssql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($sresult) >= 1)
    {
        $html .= "<h3>{$GLOBALS['strEnvironment']}</h3>";
        $html .= "<p>{$GLOBALS['strTheInfoInThisArticle']}:</p>\n";
        $html .= "<ul>\n";
        while ($kbsoftware = mysql_fetch_object($sresult))
        {
            $html .= "<li>{$kbsoftware->name}</li>\n";
        }
        $html .= "</ul>\n";
    }

    $csql = "SELECT * FROM `{$GLOBALS['dbKBContent']}` WHERE docid='{$id}' ";
    $cresult = mysql_query($csql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $restrictedcontent = 0;
    while ($kbcontent = mysql_fetch_object($cresult))
    {
        switch ($kbcontent->distribution)
        {
            case 'private':
                if ($mode != 'internal')
                {
                    echo "<p class='error'>{$GLOBALS['strPermissionDenied']}</p>";
                    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
                    exit;
                }
                $html .= "<div class='kbprivate'><h3>{$GLOBALS[$kbcontent->header]} (private)</h3>";
                $restrictedcontent++;
                break;
            case 'restricted':
                if ($mode != 'internal')
                {
                    echo "<p class='error'>{$GLOBALS['strPermissionDenied']}</p>";
                    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
                    exit;
                }
                $html .= "<div class='kbrestricted'><h3>{$GLOBALS[$kbcontent->header]}</h3>";
                $restrictedcontent++;
                break;
            default:
                $html .= "<div><h3>{$GLOBALS[$kbcontent->header]}</h3>";
        }
        //$html .= "<{$kbcontent->headerstyle}>{$kbcontent->header}</{$kbcontent->headerstyle}>\n";
        $html .= '';
        $kbcontent->content=nl2br($kbcontent->content);
        $search = array("/(?<!quot;|[=\"]|:\/{2})\b((\w+:\/{2}|www\.).+?)"."(?=\W*([<>\s]|$))/i", "/(([\w\.]+))(@)([\w\.]+)\b/i");
        $replace = array("<a href=\"$1\">$1</a>", "<a href=\"mailto:$0\">$0</a>");
        $kbcontent->content = preg_replace("/href=\"www/i", "href=\"http://www", preg_replace ($search, $replace, $kbcontent->content));
        $html .= bbcode($kbcontent->content);
        $author[]=$kbcontent->ownerid;
        $html .= "</div>\n";
    }

    if ($restrictedcontent > 0)
    {
        $html .= "<h3>{$GLOBALS['strKey']}</h3>";
        $html .= "<p><span class='keykbprivate'>{$GLOBALS['strPrivate']}</span>".help_link('KBPrivate')." &nbsp; ";
        $html .= "<span class='keykbrestricted'>{$GLOBALS['strRestricted']}</span>".help_link('KBRestricted')."</p>";
    }


    $html .= "<h3>{$GLOBALS['strArticle']}</h3>";
    //$html .= "<strong>{$GLOBALS['strDocumentID']}</strong>: ";
    $html .= "<p><strong>{$CONFIG['kb_id_prefix']}".leading_zero(4,$kbarticle->docid)."</strong> ";
    $pubdate = mysql2date($kbarticle->published);
    if ($pubdate > 0)
    {
        $html .= "{$GLOBALS['strPublished']} ";
        $html .= ldate($CONFIG['dateformat_date'],$pubdate)."<br />";
    }

    $sqlf = "SELECT f.filename, f.id, f.filedate FROM `{$GLOBALS['dbFiles']}` ";
    $sqlf .= "AS f INNER JOIN `{$GLOBALS['dbLinks']}` as l ON l.linkcolref = f.id ";
    $sqlf .= "WHERE l.linktype = 7 AND l.origcolref = '{$id}'";
    $fileresult = mysql_query($sqlf);
    if (mysql_error()) trigger_error("MySQL Error: ".mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($fileresult) > 0)
    {
        $html .= "<h3>{$GLOBALS['strFiles']}</h3>";
        $html .= "<table class='attachments'><th>{$GLOBALS['strFilename']}</th><th>{$GLOBALS['strDate']}</th>";
        while ($filename = mysql_fetch_object($fileresult))
        {
            $html .= "<tr><td><a href='download.php?id={$filename->id}&app=7&appid={$id}'>$filename->filename</a></td>";
            $html .= "<td>" . ldate($CONFIG['dateformat_filedatetime'],mysql2date($filename->filedate)) . "</td></tr>";
        }
        $html .= "</table>";
    }


    if ($mode == 'internal')
    {
        if (is_array($author))
        {
            $html .= "<p>";
            $author = array_unique($author);
            $countauthors = count($author);
            $count = 1;
            if ($countauthors > 1)
            {
                $html .= "<strong>{$GLOBALS['strAuthors']}</strong>:<br />";
            }
            else
            {
                $html .= "<strong>{$GLOBALS['strAuthor']}:</strong> ";
            }

            foreach ($author AS $authorid)
            {
                $html .= user_realname($authorid,TRUE);
                if ($count < $countauthors) $html .= ", " ;
                $count++;
            }
            $html .= "</p>";
        }
    }

    if (!empty($kbarticle->keywords))
    {
        $html .= "<strong>{$GLOBALS['strKeywords']}</strong>: ";
        if ($mode == 'internal')
        {
            $html .= preg_replace("/\[([0-9]+)\]/", "<a href=\"incident_details.php?id=$1\" target=\"_blank\">$0</a>", $kbarticle->keywords);
        }
        else
        {
            $html .= $kbarticle->keywords;
        }
        $html .= "<br />";
    }

    //$html .= "<h3>{$GLOBALS['strDisclaimer']}</h3>";
    $html .= "</p><hr />";
    $html .= $CONFIG['kb_disclaimer_html'];
    $html .= "</div>";

    if ($mode == 'internal')
    {
        $html .= "<p align='center'>";
        $html .= "<a href='kb.php'>{$GLOBALS['strBackToList']}</a> | ";
        $html .= "<a href='kb_article.php?id={$kbarticle->docid}'>{$GLOBALS['strEdit']}</a></p>";
    }
    return $html;
}


/**
 * Outputs the name of a KB article, used for triggers
 *
 * @param int $kbid ID of the KB article
 * @return string $name kb article name
 * @author Kieran Hogg
 */
function kb_name($kbid)
{
    $kbid = intval($kbid);
    $sql = "SELECT title FROM `{$GLOBALS['dbKBArticles']}` WHERE docid='{$kbid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    else
    {
        $row = mysql_fetch_object($result);
        return $row->title;
    }
}


/**
 * Checks whether an KB article exists and/or the user is allowed to view is
 * @author Kieran Hogg
 * @param $id int ID of the KB article
 * @param $mode string 'public' for portal users, 'private' for internal users
 * @return bool Whether we are allowed to see it or not
 */
function is_kb_article($id, $mode)
{
    $rtn = FALSE;
    global $dbKBArticles;
    $id = cleanvar($id);
    if ($id > 0)
    {
        $sql = "SELECT distribution FROM `{$dbKBArticles}` ";
        $sql .= "WHERE docid = '{$id}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(). "  $sql", E_USER_WARNING);
        list($visibility) = mysql_fetch_row($result);
        if ($visibility == 'public' AND $mode == 'public')
        {
            $rtn = TRUE;
        }
        else if (($visibility == 'private' OR $visibility == 'restricted') AND
                 $mode == 'private')
        {
            $rtn = TRUE;
        }
    }
    return $rtn;
}


