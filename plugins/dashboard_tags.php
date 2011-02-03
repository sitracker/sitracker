<?php
// dashboard_tags.php - Show tags
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

$dashboard_tags_version = 1;

function dashboard_tags($dashletid)
{
    global $CONFIG, $iconset;

    $content = show_tag_cloud();

    echo dashlet('tags', $dashletid, icon('tag', 16), $GLOBALS['strTags'], '', $content);
}

function dashboard_tags_get_version()
{
    global $dashboard_tags_version;
    return $dashboard_tags_version;
}


?>
