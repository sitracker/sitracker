<?php
// tags.inc.php - functions relating to Tags
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
 * @author Ivan Lucas
 */
function get_tag_id($tag)
{
    global $dbTags;
    $sql = "SELECT tagid FROM `{$dbTags}` WHERE name = LOWER('$tag')";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) == 1)
    {
        $id = mysql_fetch_row($result);
        return $id[0];
    }
    else
    {
        //need to add
        $sql = "INSERT INTO `{$dbTags}` (name) VALUES (LOWER('$tag'))";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
        return mysql_insert_id();
    }
}


/**
 * @author Ivan Lucas
 */
function new_tag($id, $type, $tag)
{
    global $dbSetTags;
    /*
    TAG TYPES
    1 - contact
    2 - incident
    3 - Site
    4 - task
    5 - product
    6 - skill
    7 - kb article
    8 - report
 */
    if ($tag!='')
    {
        $tagid = get_tag_id($tag);
        // Ignore errors, die silently
        $sql = "INSERT INTO `{$dbSetTags}` VALUES ('$id', '$type', '$tagid')";
        $result = @mysql_query($sql);
    }
    return true;
}


/**
 * @author Ivan Lucas
 */
function remove_tag($id, $type, $tag)
{
    global $dbSetTags, $dbTags;
    if ($tag != '')
    {
        $tagid = get_tag_id($tag);
        // Ignore errors, die silently
        $sql = "DELETE FROM `{$dbSetTags}` WHERE id = '$id' AND type = '$type' AND tagid = '$tagid'";
        $result = @mysql_query($sql);

        // Check tag usage count and remove disused tags completely
        $sql = "SELECT COUNT(id) FROM `{$dbSetTags}` WHERE tagid = '$tagid'";
        $result = mysql_query($sql);
        list($count) = mysql_fetch_row($result);
        if ($count == 0)
        {
            $sql = "DELETE FROM `{$dbTags}` WHERE tagid = '$tagid' LIMIT 1";
            @mysql_query($sql);
        }
        purge_tag($tagid);
    }
    return true;
}


/**
 * Remove existing tags and replace with a new set
 * @author Ivan Lucas
 */
function replace_tags($type, $id, $tagstring)
{
    global $dbSetTags;
    // first remove old tags
    $sql = "DELETE FROM `{$dbSetTags}` WHERE id = '$id' AND type = '$type'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    // Change separators to spaces
    $separators = array(', ',';',',');
    $tags = str_replace($separators, ' ', trim($tagstring));
    $tag_array = explode(" ", $tags);
    foreach ($tag_array AS $tag)
    {
        new_tag($id, $type, trim($tag));
    }
}

/**
 * Purge a single tag (if needed)
 * @author Ivan Lucas
 */
function purge_tag($tagid)
{
    // Check tag usage count and remove disused tag completely
    global $dbSetTags, $dbTags;
    $sql = "SELECT COUNT(id) FROM `{$dbSetTags}` WHERE tagid = '$tagid'";
    $result = mysql_query($sql);
    list($count) = mysql_fetch_row($result);
    if ($count == 0)
    {
        $sql = "DELETE FROM `{$dbTags}` WHERE tagid = '$tagid' LIMIT 1";
        @mysql_query($sql);
    }
}


/**
 * Purge all tags (if needed)
 * @author Ivan Lucas
 */
function purge_tags()
{
    global $dbTags;
    $sql = "SELECT tagid FROM `{$dbTags}`";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        while ($tag = mysql_fetch_object($result))
        {
            purge_tag($tag->tagid);
        }
    }
}


/**
 * Produce a list of tags
 * @author Ivan Lucas
 * @param int $recordid. The record ID to find tags for
 * @param int $type. The tag record type.
 * @param boolean $html. Return HTML when TRUE
 */
function list_tags($recordid, $type, $html = TRUE)
{
    global $CONFIG, $dbSetTags, $dbTags, $iconset;

    $sql = "SELECT t.name, t.tagid FROM `{$dbSetTags}` AS s, `{$dbTags}` AS t WHERE s.tagid = t.tagid AND ";
    $sql .= "s.type = '$type' AND s.id = '$recordid'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $numtags = mysql_num_rows($result);

    if ($html AND $numtags > 0)
    {
        $str .= "<div class='taglist'>";
    }

    $count = 1;
    while ($tags = mysql_fetch_object($result))
    {
        if ($html)
        {
            $str .= "<a href='view_tags.php?tagid={$tags->tagid}'>".$tags->name;
            if (array_key_exists($tags->name, $CONFIG['tag_icons']))
            {
                $str .= "&nbsp;<img src='images/icons/{$iconset}/16x16/{$CONFIG['tag_icons'][$tags->name]}.png' alt='' />";
            }
            $str .= "</a>";
        }
        else
        {
            $str .= $tags->name;
        }

        if ($count < $numtags) $str .= ", ";
        if ($html AND !($count%5)) $str .= "<br />\n";
        $count++;
    }
    if ($html AND $numtags > 0) $str .= "</div>";
    return trim($str);
}


/**
 * Return HTML to display a list of tag icons
 * @author Ivan Lucas
 * @return string. HTML
 */
function list_tag_icons($recordid, $type)
{
    global $CONFIG, $dbSetTags, $dbTags, $iconset;
    $sql = "SELECT t.name, t.tagid ";
    $sql .= "FROM `{$dbSetTags}` AS st, `{$dbTags}` AS t WHERE st.tagid = t.tagid AND ";
    $sql .= "st.type = '$type' AND st.id = '$recordid' AND (";
    $counticons = count($CONFIG['tag_icons']);
    $count = 1;
    foreach ($CONFIG['tag_icons'] AS $icon)
    {
        $sql .= "t.name = '{$icon}'";
        if ($count < $counticons) $sql .= " OR ";
        $count++;
    }
    $sql .= ")";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $numtags = mysql_num_rows($result);
    if ($numtags > 0)
    {
        while ($tags = mysql_fetch_object($result))
        {
            $str .= "<a href='view_tags.php?tagid={$tags->tagid}' title='{$tags->name}'>";
            $str .= "<img src='images/icons/{$iconset}/16x16/{$CONFIG['tag_icons'][$tags->name]}.png' alt='{$tags->name}' />";
            $str .= "</a> ";
        }
    }
    return $str;
}

/**
 * Generate a tag cloud
 * @author Ivan Lucas, Tom Gerrard
 * @return string. HTML
 */
function show_tag_cloud($orderby="name", $showcount = FALSE)
{
    global $CONFIG, $dbTags, $dbSetTags, $iconset;

    // First purge any disused tags
    purge_tags();
    $sql = "SELECT COUNT(name) AS occurrences, name, t.tagid FROM `{$dbTags}` AS t, `{$dbSetTags}` AS st WHERE t.tagid = st.tagid GROUP BY name ORDER BY $orderby";
    if ($orderby == "occurrences") $sql .= " DESC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    $countsql = "SELECT COUNT(id) AS counted FROM `{$dbSetTags}` GROUP BY tagid ORDER BY counted DESC LIMIT 1";
    $countresult = mysql_query($countsql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($max) = mysql_fetch_row($countresult);

    $countsql = "SELECT COUNT(id) AS counted FROM `{$dbSetTags}` GROUP BY tagid ORDER BY counted ASC LIMIT 1";
    $countresult = mysql_query($countsql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    list($min) = mysql_fetch_row($countresult);
    unset($countsql, $countresult);

    if (mb_substr($_SERVER['SCRIPT_NAME'],-8) != "main.php")
    {
        //not in the dashbaord
        $html .= "<p align='center'>{$GLOBALS['strSort']}: <a href='view_tags.php?orderby=name'>{$GLOBALS['strAlphabetically']}</a> | ";
        $html .= "<a href='view_tags.php?orderby=occurrences'>{$GLOBALS['strPopularity']}</a></p>";
    }

    if (mysql_num_rows($result) > 0)
    {
        $html .= "<table align='center'><tr><td class='tagcloud'>";
        while ($obj = mysql_fetch_object($result))
        {
            $size = round(log($obj->occurrences * 100) * 32);
            if ($size == 0) $size = 100;
            if ($size > 0 AND $size <= 100) $taglevel = 'taglevel1';
            if ($size > 100 AND $size <= 150) $taglevel = 'taglevel2';
            if ($size > 150 AND $size <= 200) $taglevel = 'taglevel3';
            if ($size > 200) $taglevel = 'taglevel4';
            $html .= "<a href='view_tags.php?tagid=$obj->tagid' class='$taglevel' style='font-size: {$size}%; font-weight: normal;' title='{$obj->occurrences}'>";
            if (array_key_exists($obj->name, $CONFIG['tag_icons']))
            {
                $html .= "{$obj->name}&nbsp;<img src='images/icons/{$iconset}/";
                if ($size <= 200)
                {
                    $html .= "16x16";
                }
                else
                {
                    $html .= "32x32";
                }
                $html .= "/{$CONFIG['tag_icons'][$obj->name]}.png' alt='' />";
            }
            else $html .= $obj->name;
            $html .= "</a>";
            if ($showcount) $html .= "({$obj->occurrences})";
            $html .= " \n";//&nbsp;\n";
        }
        $html .= "</td></tr></table>";
    }
    else $html .= "<p align='center'>{$GLOBALS['strNothingToDisplay']}</p>";
    return $html;
}

?>