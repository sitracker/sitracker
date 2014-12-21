<?php
// note_new.php - Add a new note
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_NOT_REQUIRED; // Allow all auth users
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$action = clean_fixed_list($_REQUEST['action'], array('','addnote','form'));

switch ($action)
{
    case 'addnote':
        $link = clean_int($_REQUEST['link']);
        $refid = clean_int($_REQUEST['refid']);
        $bodytext = cleanvar($_REQUEST['bodytext'],FALSE,FALSE);
        $rpath = cleanvar($_REQUEST['rpath']);

        // Input validation
        // Validate input
        $error = array();
        if (empty($link)) $error[] = sprintf($strFieldMustNotBeBlank, $strLink);
        if (empty($refid)) $error[] = sprintf($strFieldMustNotBeBlank, $strRefid);
        if (empty($bodytext)) $error[] = sprintf($strFieldMustNotBeBlank, $strNote);
        if (count($error) >= 1)
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            echo "<p class='error'>{$strCheckEnteredData}</p>";
            echo "<ul class='error'>";
            foreach ($error AS $err)
            {
                echo "<li>{$err}</li>";
            }
            echo "</ul>";
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        else
        {
            $sql = "INSERT INTO `{$dbNotes}` (userid, bodytext, link, refid) ";
            $sql .= "VALUES ('{$sit[2]}', '{$bodytext}', '{$link}', '{$refid}')";
            mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
            if (mysql_affected_rows() < 1) trigger_error("Note insert failed", E_USER_ERROR);

            $sql = "UPDATE `{$dbTasks}` SET lastupdated=NOW() WHERE id={$refid}";
            mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

            html_redirect($rpath);
        }
        break;
    case '':
    default:
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo new_note_form(0,0);
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>