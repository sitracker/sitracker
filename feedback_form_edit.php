<?php
// edit_feedback_form.php - Form for editing feedback forms
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// by Ivan Lucas, June 2004

require ('core.php');
$permission = PERM_FEEDBACK_FORM_EDIT; // Edit Feedback Forms
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External Variables
$formid = clean_int($_REQUEST['formid']);
$action = clean_fixed_list($_REQUEST['action'], array('','save','new'));

if (empty($formid)) $formid = 1;

switch ($action)
{
    case 'save':
        // External variables
        $name = clean_dbstring($_REQUEST['name']);
        $description = clean_dbstring($_REQUEST['description']);
        $introduction = clean_dbstring($_REQUEST['introduction']);
        $thanks = clean_dbstring($_REQUEST['thanks']);
        $isnew = clean_fixed_list($_REQUEST['isnew'], array('no','yes'));

        if ($isnew == "yes")
        {
            // need to insert
            $sql = "INSERT INTO `{$dbFeedbackForms}` (name,introduction,thanks,description) VALUES ";
            $sql .= "('{$name}','{$introduction}','{$thanks}','{$description}')";
            mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_ERROR);
            $formid = mysqli_insert_id($db);
        }
        else
        {
            $sql = "UPDATE `{$dbFeedbackForms}` ";
            $sql .= "SET name='$name', description='$description', introduction='$introduction', thanks='$thanks' ";
            $sql .= "WHERE id='$formid' LIMIT 1";
            mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_ERROR);
        }

        header("Location: feedback_form_edit.php?formid={$formid}");
        exit;
    break;

    case 'new':
        $title = "$strFeedbackForms - $strNew";
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h3>" . icon('reports', 32, $strNewFeedbackForm) . " {$strNewFeedbackForm}</h3>";
        echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
        echo "<table summary='Form' class='vertical maintable'>";
        echo "<tr>";

        /*echo "<th>Form ID:</th>";
        echo "<td><strong>{$form->id}</strong></td>";
        echo "</tr>\n<tr>";*/

        echo "<th>{$strName}:</th>";
        echo "<td><input type='text' name='name' size='35' maxlength='255' value='' /></td>";
        echo "</tr>\n<tr>";

        echo "<th>{$strDescription}:<br />({$strInternalUseNotDisplayed})</th>";
        echo "<td><textarea name='description' cols='80' rows='6'>";
        echo "</textarea></td>";
        echo "</tr>\n<tr>";

        echo "<th>{$strIntroduction}:<br />($strSimpleHTMLallowed)</th>";
        echo "<td><textarea name='introduction' cols='80' rows='10'>";
        echo "</textarea></td>";
        echo "</tr>\n<tr>";

        echo "<th>{$strClosingThanks}:<br />($strSimpleHTMLallowed)</th>";
        echo "<td><textarea name='thanks' cols='80' rows='10'>";
        echo "</textarea></td>";
        echo "</tr>\n";

        // If there are no reponses to this feedback form, allow questions to be modified also
        echo "<tr>";
        echo "<th>{$strQuestions}:</th>";
        echo "<td>";
        echo "<p>{$strSaveTheMainFormFirst}</p>";
        echo "</td></tr>\n";
        echo "</table>";
        echo "<p class='formbuttons'>";
        echo "<input type='hidden' name='formid' value='{$formid}' />";
        echo "<input type='hidden' name='isnew' value='yes' />";
        echo "<input type='hidden' name='action' value='save' />";
        echo "<input name='reset' type='reset' value='{$strReset}' /> ";
        echo "<input type='submit' value='{$strSave}' /></p>";
        echo "<p class='return'><a href=\"feedback_form_list.php\">{$strReturnWithoutSaving}</a></p>";
        echo "</form>";

        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        break;

    default:
        $sql = "SELECT * FROM `{$dbFeedbackForms}` WHERE id='{$formid}'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_WARNING);

        $title = "{$strFeedbackForms} - {$strEdit}";
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h3>{$title}</h3>";

        $sql = "SELECT * FROM `{$dbFeedbackForms}` WHERE id = '$formid'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error ("MySQL Error: ".mysqli_error($db), E_USER_WARNING);
        if (mysqli_num_rows($result) >= 1)
        {
            while ($form = mysqli_fetch_object($result))
            {
                echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
                echo "<table summary='Form' class='maintable'>";
                echo "<tr>";

                echo "<th>{$strID}:</th>";
                echo "<td><strong>{$form->id}</strong></td>";
                echo "</tr>\n<tr>";

                echo "<th>{$strName}:</th>";
                echo "<td><input type='text' name='name' size='35' maxlength='255' value=\"{$form->name}\" /></td>";
                echo "</tr>\n<tr>";

                echo "<th>{$strDescription}:</th>";
                echo "<td>{$strInternalUseNotDisplayed}<textarea name='description' cols='80' rows='6'>";
                echo "{$form->description}</textarea></td>";
                echo "</tr>\n<tr>";

                echo "<th>{$strIntroduction}:<br />({$strSimpleHTMLallowed})</th>";
                echo "<td><textarea name='introduction' cols='80' rows='10'>";
                echo "{$form->introduction}</textarea></td>";
                echo "</tr>\n<tr>";

                echo "<th>{$strClosingThanks}:<br />($strSimpleHTMLallowed)</th>";
                echo "<td><textarea name='thanks' cols='80' rows='10'>";
                echo "{$form->thanks}</textarea></td>";
                echo "</tr>\n";

                // If there are no reponses to this feedback form, allow questions to be modified also
                echo "<tr>";
                echo "<th>{$strQuestions}:</th>";
                echo "<td>";

                $qsql  = "SELECT * FROM `{$dbFeedbackQuestions}` ";
                $qsql .= "WHERE formid='$formid' ORDER BY taborder";
                $qresult = mysqli_query($db, $qsql);
                if (mysqli_num_rows($qresult) > 0)
                {
                    echo "<table width='100%'>";
                    while ($question = mysqli_fetch_object($qresult))
                    {
                        if (empty($question->question)) $question->question = $strUntitled;
                        echo "<tr>";
                        echo "<td><strong>Q{$question->taborder}</strong></td>";
                        echo "<td><a href='feedback_form_editquestion.php?qid={$question->id}&amp;fid={$formid}'><strong>{$question->question}</strong></a></td>";
                        echo "<td>{$question->questiontext}</td>";
                        echo "</tr>\n<tr>";
                        echo "<td>{$question->type}</td>";
                        echo "<td colspan='2'>";
                        if ($question->required == 'true') echo "<strong>{$strRequired}</strong> ";
                        echo "<samp>{$question->options}</samp></td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                echo "<p><a href='feedback_form_newquestion.php?fid={$formid}'>{$strNew}</a><br />{$strSaveTheMainFormFirst}</p>";
                echo "</td></tr>\n";
                echo "<tr>";
                echo "<td><input type='hidden' name='formid' value='{$formid}' />";
                echo "<input type='hidden' name='action' value='save' /></td>";
                echo "<td><input type='submit' value='{$strSave}' /></td>";
                echo "</tr>";
                echo "</table>";
                echo "</form>";
            }
        }
        else echo user_alert($strNoRecords, E_USER_NOTICE);
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    break;
}
?>