<?php
// edit_feedback_question.php - Form for editing feedback questions
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// by Ivan Lucas, June 2004
$permission = 49; // Edit Feedback Forms

require ('core.php');
require (APPLICATION_LIBPATH.'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

function qtype_listbox($type)
{
    global $CONFIG;

    $html .= "<select name='type'>\n";
    $html .= "<option value='rating'";
    if ($type=='rating') $html .= " selected";
    $html .= ">{$strRating}</option>";

    $html .= "<option value='options'";
    if ($type=='options') $html .= " selected";
    $html .= ">{$strOptions}</option>";

    $html .= "<option value='multioptions'";
    if ($type=='multioptions') $html .= " selected";
    $html .= ">{$strMultipleOptions}</option>";

    $html .= "<option value='text'";
    if ($type=='text') $html .= " selected";
    $html .= ">{$strText}</option>";

    $html .= "</select>\n";

    return $html;
}
$title = $strEditFeedbackQuestion;

$qid = cleanvar($_REQUEST['qid']);
$fid = cleanvar($_REQUEST['fid']);
$action = cleanvar($_REQUEST['action']);


switch ($action)
{
    case 'save':
        // External variables
        $question = cleanvar($_POST['question']);
        $questiontext = cleanvar($_POST['questiontext']);
        $sectiontext = cleanvar($_POST['sectiontext']);
        $taborder = cleanvar($_POST['taborder']);
        $type = cleanvar($_POST['type']);
        $required = cleanvar($_POST['required']);
        $options = cleanvar($_POST['options']);

        $sql = "UPDATE `{$dbFeedbackQuestions}` SET ";
        $sql .= "question='{$question}', ";
        $sql .= "questiontext='{$questiontext}', ";
        $sql .= "sectiontext='{$sectiontext}', ";
        $sql .= "taborder='{$taborder}', ";
        $sql .= "type='{$type}', ";
        $sql .= "required='{$required}', ";
        $sql .= "options='{$options}' ";
        $sql .= "WHERE id='$qid' LIMIT 1";
        mysql_query($sql);
        if (mysql_error()) trigger_error ("MySQL Error: ".mysql_error(), E_USER_ERROR);
        header("Location: feedback_form_edit.php?formid={$fid}");
        exit;
    break;

    default:
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');

        echo "<h2 align='center'>{$title}</h2>\n";

        $sql = "SELECT * FROM `{$dbFeedbackQuestions}` WHERE id = '$qid'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error ("MySQL Error: ".mysql_error(), E_USER_WARNING);

        while ($question = mysql_fetch_object($result))
        {
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
            echo "<td><input type='text' name='taborder' size='3' maxlength='5' value=\"{$question->taborder}\" /></td>";
            echo "</tr>\n<tr>";

            echo "<th>{$strQuestion}:</th>";
            echo "<td><input type='text' name='question' size='70' maxlength='255' value=\"{$question->question}\" /></td>";
            echo "</tr>\n<tr>";

            echo "<th>{$strQuestionText}:<br /></th>";
            echo "<td><textarea name='questiontext' cols='80' rows='5'>";
            echo $question->questiontext."</textarea></td>";
            echo "</tr>\n<tr>";

            echo "<th>{$strType}:</th>";
            echo "<td>";
            echo qtype_listbox($question->type);
            echo "</td></tr>\n<tr>";

            echo "<th>$strOptionsOnePerLine:<br /></th>";
            echo "<td><textarea name='options' cols='80' rows='10'>";
            echo $question->options."</textarea></td>";
            echo "</tr>\n<tr>";

            echo "<th>{$strRequired}:</th>";
            echo "<td>";
            if ($question->required=='true') echo "<input type='checkbox' name='required' value='true' checked='checked' />";
            else echo "<input type='checkbox' name='required' value='true' />";
            echo "</td></tr>\n<tr>";

            echo "<td><input type='hidden' name='qid' value='{$qid}' />";
            echo "<input type='hidden' name='fid' value='{$fid}' />";
            echo "<input type='hidden' name='action' value='save' /></td>";
            echo "<td><input type='submit' value='{$strSave}' /></td>";
            echo "</tr>";
            echo "</table>";
            echo "<p><a href='feedback_form_edit.php?id={$fid}'>{$strReturnToPreviousPage}</a></p>";
        }
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    break;
}
?>
