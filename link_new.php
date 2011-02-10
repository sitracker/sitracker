<?php
// link_new.php - Add a link between two tables
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


$permission = 0; // Allow all auth users

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNewLink;

// External variables
$action = $_REQUEST['action'];
$origtab = cleanvar($_REQUEST['origtab']);
$origref = cleanvar($_REQUEST['origref']);
$linkref = cleanvar($_REQUEST['linkref']);
$linktypeid = clean_int($_REQUEST['linktype']);
$direction = cleanvar($_REQUEST['dir']);
if ($direction == '') $direction = 'lr';
$redirect = cleanvar($_REQUEST['redirect']);

switch ($action)
{
    case 'addlink':
        // Insert the link
        if ($direction == 'lr')
        $sql = "INSERT INTO `{$dbLinks}` ";
        $sql .= "(linktype, origcolref, linkcolref, userid) ";
        $sql .= "VALUES ('{$linktypeid}', '$origref', '$linkref', '{$sit[2]}')";
        mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

        html_redirect($redirect);
        break;
    case '':
    default:
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');

        // Find out what kind of link we are to make
        $sql = "SELECT * FROM `{$dbLinkTypes}` WHERE id='{$linktypeid}'";
        $result = mysql_query($sql);
        while ($linktype = mysql_fetch_object($result))
        {
            if ($direction == 'lr')
            {
                echo "<h2>Link {$linktype->lrname}</h2>";
            }
            elseif ($direction == 'rl')
            {
                echo "<h2>Link {$linktype->rlname}</h2>";
            }

            echo "<p align='center'>Make a {$linktype} link for origtab {$origtab}, origref {$origref}</p>"; // FIMXE i18n
            $recsql = "SELECT {$linktype->linkcol} AS recordref, {$linktype->selectionsql} AS recordname FROM `{$CONFIG['db_tableprefix']}{$linktype->linktab}` ";
            $recsql .= "WHERE {$linktype->linkcol} != '{$origref}'";

            $recresult = mysql_query($recsql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            if (mysql_num_rows($recresult) >= 1)
            {
                echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
                echo "<p>";
                echo "<select name='linkref'>";
                while ($record = mysql_fetch_object($recresult))
                {
                    echo "<option value='{$record->recordref}'>{$record->recordname}</option>\n";
                }
                echo "</select>";
                echo "</p>";
                echo "<p><input name='submit' type='submit' value='{$strNew}' /></p>";
                echo "<input type='hidden' name='action' value='addlink' />";
                echo "<input type='hidden' name='origtab' value='{$origtab}' />";
                echo "<input type='hidden' name='origref' value='{$origref}' />";
                echo "<input type='hidden' name='linktype' value='{$linktypeid}' />";
                echo "<input type='hidden' name='dir' value='{$direction}' />";
                echo "<input type='hidden' name='redirect' value='{$_SERVER['HTTP_REFERER']}' />";
                echo "</form>";
            }
            else echo "<p class='error'>{$strNothingToLink}</p>";
        }
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>