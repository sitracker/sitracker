<?php
// kbarticle.php - Display a single portal knowledge base article
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Kieran Hogg <kieran[at]sitracker.org>

require ('..' . DIRECTORY_SEPARATOR . 'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

$accesslevel = 'any';

if ($CONFIG['portal_kb_enabled'] !== 'Public' OR !empty($_REQUEST['p']))
{
    include (APPLICATION_LIBPATH . 'portalauth.inc.php');
}

$can_view = FALSE;
if (!empty($_REQUEST['id']))
{
    $id = clean_int($_REQUEST['id']);
    $can_view = is_kb_article($id, 'public');
}
if (empty($id) OR !$can_view)
{
    header("Location: kb.php");
    exit;
}
include (APPLICATION_INCPATH . 'portalheader.inc.php');

echo "<h2>".icon('kb', 32)." {$strKnowledgeBaseArticle}</h2>";
echo kb_article($id, 'external');

echo "<p class='return'><a href='kb.php'>{$strBackToList}</a></p>";
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>