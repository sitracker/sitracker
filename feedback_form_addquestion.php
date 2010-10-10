<?php
// feedback_question_add.php - Form for adding feedback questions
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// by Ivan Lucas, June 2004

// FIXME i18n Whole Page


$permission = 48; // Add Feedback Forms

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

if ($_SESSION['tcs'] != $tcs) { header("Location: login.php"); exit; }

// External variables
$qid = clean_int($_REQUEST['id']);
$fid = clean_int($_REQUEST['fid']);
$formid = clean_int($_POST['formid']);
$question = clean_dbstring($_POST['question']);
$questiontext = clean_dbstring($_POST['questiontext']);
$sectiontext = clean_dbstring($_POST['sectiontext']);
$taborder = clean_int($_POST['taborder']);
$type = clean_dbstring($_POST['type']);
$required = clean_fixed_list($_POST['required'], array('false','true'));
$options = clean_dbstring($_POST['options']);

switch ($_REQUEST['action'])
{
    case 'save':
        $sql = "INSERT INTO `{$dbFeedbackQuestions}` ";
        $sql .= "(formid, question, questiontext, sectiontext, taborder, type, required, options) VALUES (";
        $sql .= "'{$formid}',";
        $sql .= "'{$question}',";
        $sql .= "'{$questiontext}',";
        $sql .= "'{$sectiontext}',";
        $sql .= "'{$taborder}',";
        $sql .= "'{$type}',";
        $sql .= "'{$required}',";
        $sql .= "'{$options}')";
        mysql_query($sql);
        if (mysql_error()) trigger_error ("MySQL Error: ".mysql_error(), E_USER_ERROR);
        $newqid = $qid + 1;
        header("Location: feedback_form_addquestion.php?fid={$formid}&qid={$newqid}");
        exit;
        break;

    default:
        $title = "{$strAddFeedbackQuestion}";
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        $qid = strip_tags($_REQUEST['qid']);

        echo "<h2 align='center'>$title</h2>\n";

        echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
        echo "<table summary='Form' align='center'>";
        echo "<tr>";

        echo "<th>{$strSectionText}:<br /></th>";
        echo "<td><textarea name='sectiontext' cols='80' rows='5'>";
        echo $question->sectiontext."</textarea>";
        echo "({$strLeaveBlankForNewSection})";
        echo "</td>";
        echo "</tr>\n<tr>";

        echo "<th>{$strQuestion} #:</th>";
        echo "<td><input type='text' name='taborder' size='3' maxlength='5' value='{$qid}' /></td>";
        echo "</tr>\n<tr>";

        echo "<th>{$strQuestion}:</th>";
        echo "<td><input type='text' name='question' size='35' maxlength='255' value='".$question->question."' /></td>";
        echo "</tr>\n<tr>";

        echo "<th>{$strQuestionText}:</th>";
        echo "<td><textarea name='questiontext' cols='80' rows='5'>";
        echo $question->questiontext."</textarea></td>";
        echo "</tr>\n<tr>";

        echo "<th>{$strType}:</th>";
        echo "<td>";
        echo feedback_qtype_listbox($question->type);
        echo "</td></tr>\n<tr>";

        echo "<th>$strOptionsOnePerLine:</th>";
        echo "<td><textarea name='options' cols='80' rows='10'>";
        echo $question->options."</textarea></td>";
        echo "</tr>\n<tr>";

        echo "<th>{$strRequired}:</th>";
        echo "<td><label>";
        if ($question->required == 'true') echo "<input type='checkbox' name='required' value='true' checked='checked' />";
        else echo "<input type='checkbox' name='required' value='true' />";
        echo " {$strRequired}</label>";
        echo "</td></tr>\n<tr>";

        echo "<td><input type='hidden' name='id' value='{$qid}' />";
        echo "<input type='hidden' name='formid' value='{$fid}' />";
        echo "<input type='hidden' name='action' value='save' /></td>";
        echo "<td><input type='submit' value='{$strSave}' /></td>";
        echo "</tr>";
        echo "</table>";
        echo "</form>";
        echo "<p><a href='feedback_form_edit.php?id={$fid}'>{$strReturnToPreviousPage}</a></p>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>