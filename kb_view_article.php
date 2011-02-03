<?php
// kb_view_article.php - Display a single knowledge base article
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Ivan Lucas <ivanlucas[at]users.sourceforge.net>, Tom Gerrard

$permission = 54; // View KB

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strKnowledgeBaseArticle;

if (!empty($_REQUEST['id']))
{
    $id = clean_int($_REQUEST['id']);
}
if (!empty($_REQUEST['kbid']))
{
    $id = clean_int($_REQUEST['kbid']);
}
if (empty($id))
{
    header("Location: kb.php");
    exit;
}
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('kb', 32)." {$strKnowledgeBaseArticle}</h2>";
echo kb_article($id);

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>