<?php
// kb.inc.php - functions relating to knowledgebase
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
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


