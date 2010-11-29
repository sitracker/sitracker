<?php
// feedback.php - Display a form for customers to provide feedback
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>, June 2004


require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// External variables
$hashcode = $_REQUEST['ax'];
$mode = $_REQUEST['mode'];
if (!empty($hashcode)) $decodehash = str_rot13(@gzuncompress(base64_decode(urldecode($hashcode))));
else $hashcode = '';

$hashvars = explode('&&',$decodehash);
$formid = mysql_real_escape_string($hashvars['0']);
$contactid = mysql_real_escape_string($hashvars['1']);
$incidentid = urldecode(mysql_real_escape_string($hashvars['2']));
unset($errorfields);

/**
 * @author Ivan Lucas
 * @param string $name. Field name
 * @param string $required. 'true' or 'false' is the field mandatory?
 * @param string $options. delimited list of options
 * @param string $answer (optional).
 * @returns string HTML
 */
function feedback_html_rating($name, $required, $options, $answer='')
{
    global $CONFIG;
    // Rate things out of 'score_max' number
    $score_max = $CONFIG['feedback_max_score'];

    $option_list = explode('{@}', $options);
    $promptleft = $option_list[0];
    $promptright = $option_list[1];

    $colwidth = round(100/$score_max);

    $html = "<table class='feedback'>\n";
    if (empty($promptleft) == FALSE OR empty($promptright) == FALSE)
    {
        $html .= "<tr>";
        $html .= "<th colspan='{$score_max}' style='text-align: left;'>";
        $html .= "<div style='float: right;'>{$promptright}</div><div>{$promptleft}</div></th>";
        if ($required != 'true')
        {
            $html .= "<th>&nbsp;</th>";
        }
        $html .= "</tr>\n";
    }

    $html .= "<tr>\n";
    for ($c = 1; $c <= $score_max; $c++)
    {
        $html .= "<td width='{$colwidth}%' style='text-align: center;'><input type='radio' name='{$name}' value='{$c}' ";
        if ($answer == $c)
        {
            $html .= "checked='checked'";
        }
        $html .= " />$c</td>\n";
    }

    if ($required != 'true')
    {
        $html .= "<td><input type='radio' name='{$name}' value='0' ";
        if ($answer == 0)
        {
            $html .= "checked='checked'";
        }
        $html .= "/>{$strNotApplicableAbbrev}</td>";
    }
    $html .= "</tr>\n";
    $html .= "</table>\n";

    return $html;
}


/**
 * @author Ivan Lucas
 * @param string $name. Field name
 * @param string $required. 'true' or 'false' is the field mandatory?
 * @param string $options. delimited list of options
 * @param string $answer (optional).
 */
function feedback_html_options($name, $required, $options, $answer='')
{
    $option_list = explode('{@}', $options);
    $option_count = count($option_list);
    if ($option_count > 3)
    {
        $html .= "<select name='{$name}'>\n";
        foreach ($option_list AS $key=>$option)
        {
            $value = strtolower(trim(str_replace(' ', '_', $option)));
            $html .= "<option value='{$value}'";
            if ($answer == $value)
            {
                $html .= " selected='selected'";
            }
            $html .= ">".trim($option)."</option>\n";
        }
        $html .= "</select>\n";
    }
    else
    {
        foreach ($option_list AS $key=>$option)
        {
            $value = strtolower(trim(str_replace(' ', '_', $option)));
            $html .= "<input type='radio' name='{$name}' value='{$value}'";
            if ($answer == $value)
            {
                $html .= " selected='selected'";
            }
            $html .= " />".trim($option)." &nbsp; \n";
        }
    }
    return $html;
}


/**
 * @author Ivan Lucas
 * @param string $name. Field name
 * @param string $required. 'true' or 'false' is the field mandatory?
 * @param string $options. delimited list of options
 */
function feedback_html_multioptions($name, $required, $options)
{
    $option_list = explode('{@}', $options);
    $option_count = count($option_list);
    if ($option_count > 3)
    {
        $html .= "<select name='{$name}[]' multiple='multiple'>\n";
        foreach ($option_list AS $key=>$option)
        {
            $value = strtolower(trim(str_replace(' ', '_', $option)));
            $html .= "<option value='{$value}'>".trim($option)."</option>\n";
        }
        $html .= "</select>\n";
    }
    else
    {
        foreach ($option_list AS $key=>$option)
        {
            $value = strtolower(trim(str_replace(' ', '_', $option)));
            $html .= "<input type='checkbox' name='{$name}' value='{$value}' />".trim($option)." &nbsp; \n";
        }
    }
    return $html;
}


/**
 * @author Ivan Lucas
 * @param string $name. Field name
 * @param string $required. 'true' or 'false' is the field mandatory?
 * @param string $options. delimited list of options
 * @param string $answer (optional).
 */
function feedback_html_text($name, $required, $options, $answer='')
{
    $option_list = explode('{@}', $options);
    $cols = $option_list[0] ? $option_list[0] : 60;
    $rows = $option_list[1] ? $option_list[1] : 5;

    if ($rows == 1)
    {
        $html .= "<input type='text' name='{$name}' size='{$cols}' value='{$answer}' />\n";
    }
    else
    {
        $html .= "<textarea name ='{$name}' rows='{$rows}' cols='{$cols}' >{$answer}</textarea>\n";
    }

    return $html;
}


/**
 * @author Ivan Lucas
 * @param string $name. Field name
 * @param string $required. 'true' or 'false' is the field mandatory?
 * @param string $options. delimited list of options
 * @param string $answer (optional).
 */
function feedback_html_question($type, $name, $required, $options, $answer='')
{
    $options = nl2br(trim($options));
    $options = str_replace('<br>', '{@}', $options);
    $options = str_replace('<br />', '{@}', $options);
    $options = str_replace('<br/>', '{@}', $options);
    switch ($type)
    {
        case 'rating':
            $html = feedback_html_rating($name, $required, $options, $answer);
            break;
        case 'options':
            $html = feedback_html_options($name, $required, $options, $answer);
            break;
        case 'multioptions':
            $html = feedback_html_multioptions($name, $required, $options, $answer);
            break;
        case 'text':
            $html = feedback_html_text($name, $required, $options, $answer);
            break;
        default:
            $html = sprintf($GLOBALS['strErrorNoHandlerDefinedForQuestionTypeX'], $type);
            break;
  }
  return $html;
}



switch ($_REQUEST['action'])
{
    case 'save':
        // Have a look to see if this respondant has already responded to this form
        $sql = "SELECT id AS respondentid FROM `{$dbFeedbackRespondents}` ";
        $sql .= "WHERE contactid='$contactid' AND formid='{$formid}' AND incidentid='{$incidentid}' AND completed = 'no'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) < 1)
        {
            echo "<p>{$strFeedbackFormAlreadyCompleted}</p>";
        }
        else
        {
            list($respondentid) = mysql_fetch_row($result);
        }
        // Store this respondent and references

        // Loop through the questions in this form and store the results
        $sql = "SELECT * FROM `{$dbFeedbackQuestions}` WHERE formid='{$formid}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        while ($question = mysql_fetch_object($result))
        {
            $qid = $question->id;

            $options = nl2br(trim($question->options));
            $options = str_replace('<br>', '{@}', $options);
            $options = str_replace('<br />', '{@}', $options);
            $options = str_replace('<br/>', '{@}', $options);
            $option_list = explode('{@}', $options);

            $fieldname = "Q{$question->id}";

            // Check required fields are filled
            if ($question->required == 'true' AND (strlen($_POST[$fieldname]) < 1 OR
                    isset($_POST[$fieldname]) == FALSE))
                    {
                        $errorfields[] = "{$question->id}";
                    }

            // Store text responses in the appropriate field
            if ($question->type == 'text')
            {
                if (strlen($_POST[$fieldname]) < 255 AND $option_list[1] < 2)
                {
                    // If we've got just one row and less than 255 characters store it in the result field
                    $qresult = $_POST[$fieldname];
                    $qresulttext = '';
                }
                else
                {
                    // If we've got more than one row or more than 255 chars store it in the resulttext field (which is a blob)
                    $qresult = '';
                    $qresulttext = $_POST[$fieldname];
                }
            }
            else
            {
                // Store all other types of results in the result field.
                $qresult = $_POST[$fieldname];
                $qresulttext = $_POST[$fieldname];
            }

            $debugtext .= "_POST[$fieldname]={$_POST[$fieldname]}\n";

            // Put the SQL to be executed into an array to execute later
            $rsql[] = "INSERT INTO `{$dbFeedbackResults}` (respondentid, questionid, result, resulttext) VALUES ('{$respondentid}', '{$qid}','{$qresult}', '{$qresulttext}')";
            // Store the field in an array
            $fieldarray[$question->id] = $_POST[$fieldname];
        }

        if (count($errorfields) >= 1)
        {
            $error = implode(",",$errorfields);
            $fielddata = base64_encode(serialize($fieldarray));
            $errortext = urlencode($fielddata.','.$error);
            echo "<?";
            echo "xml version=\"1.0\" encoding=\"\"?";
            echo ">";

            $url = "feedback.php?ax={$hashcode}&error={$errortext}&mode={$mode}";
            html_redirect($url, FALSE, $strErrorRequiredQuestionsNotCompleted);
            exit;
        }

        if (empty($_REQUEST['rr']))
        {
            $rsql[] = "UPDATE `{$dbFeedbackRespondents}` SET completed='yes' WHERE formid='{$formid}' AND contactid='{$contactid}' AND incidentid='{$incidentid}'";
        }

        // Loop through array and execute the array to insert the form data
        foreach ($rsql AS $sql)
        {
            mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_ERROR);
            $sqltext .= $sql."\n";
        }

        $title = $strThankYou;
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h3><div id='pagecontent'><span class=\"success\">{$strThankYou}<span></h4>";
        echo "<h4>{$strThankYouCompleteForm}</h4>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        break;

    default:
        if ($_REQUEST['mode'] != 'bare')
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        }
        else
        {
            echo "<html>\n<head>\n<title>{$strFeedbackForm}</title>\n</head>\n<body>\n<div id='pagecontent'>\n\n";
        }
        $errorfields = explode(",", urldecode($_REQUEST['error']));
        $fielddata = unserialize(base64_decode($errorfields[0]));

        // Have a look to see if this person has a form waiting to be filled
        $rsql = "SELECT id FROM `{$dbFeedbackRespondents}` ";
        $rsql .= "WHERE contactid='$contactid' AND incidentid='$incidentid' AND formid='$formid' AND completed = 'no'";

        $rresult = mysql_query($rsql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

        $waitingforms = mysql_num_rows($rresult);
        $waitingform = mysql_fetch_object($rresult);

        if ($waitingforms < 1)
        {
            echo "<h3><span class='failure'>{$strError}</span></h3>";
            echo "<h4>{$strNoFeedBackFormToCompleteHere}</h4>";
            debug_log("\n\n<!-- f: $formid r:$respondent rr:$responseref dh:$decodehash  hc:$hashcode -->\n\n", TRUE);
        }
        else
        {
            $sql = "SELECT * FROM `{$dbFeedbackForms}` WHERE id='{$formid}'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($result) < 1)
            {
                echo "<h2>{$strError}</h2>";
                echo "<p>{$strNoFeedBackFormToCompleteHere}</p>";
                debug_log("\n\n<!-- f: $formid r:$respondent rr:$responseref dh:$decodehash  hc:$hashcode -->\n\n", TRUE);
            }
            else
            {
                $reqd = 0;
                while ($form = mysql_fetch_object($result))
                {
                    echo "<form action='feedback.php' method='post'>\n";
                    echo "<h2>{$form->name}</h2>\n";
                    echo "<p>{$strRelatingToIncident} <strong>#{$incidentid}</strong> &mdash; <strong>".incident_title($incidentid)."</strong><br />";
                    echo sprintf($strOpenedbyXonY, contact_realname(incident_contact($incidentid)), ldate($CONFIG['dateformat_date'],db_read_column('opened', $dbIncidents, $incidentid)));
                    echo ' &nbsp; ';
                    echo sprintf($strClosedOnX, ldate($CONFIG['dateformat_date'],db_read_column('closed', $dbIncidents, $incidentid))).".</p>";

                    if (!empty($_REQUEST['error']))
                    {
                        echo "<p style='color: red'>{$strErrorRequiredQuestionsNotCompleted}</p>";
                    }
                    echo nl2br($form->introduction);

                    $qsql  = "SELECT * FROM `{$dbFeedbackQuestions}` ";
                    $qsql .= "WHERE formid='{$form->id}' ";
                    $qsql .= "ORDER BY taborder ASC";
                    $qresult = mysql_query($qsql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                    while ($question = mysql_fetch_object($qresult))
                    {
                        if (strlen(trim($question->sectiontext)) > 3)
                        {
                            echo "<hr />{$question->sectiontext}\n";
                        }

                        echo "<h4>Q{$question->taborder}: {$question->question}";
                        if ($question->required == 'true')
                        {
                            echo "<sup style='color: red; font-size: 120%;'>*</sup>";
                            $reqd++;
                        }
                        echo "</h4>";

                        if (!empty($question->questiontext))
                        {
                            echo "<p>{$question->questiontext}</p>";
                        }
                        if (!empty($fielddata[$question->id]))
                        {
                            $answer = $fielddata[$question->id];
                        }
                        else
                        {
                            $answer = '';
                        }

                        echo feedback_html_question($question->type, "Q{$question->id}", $question->required, $question->options, $answer);
                        if (in_array($question->id, $errorfields))
                        {
                            echo "<p style='color: red'>".sprintf($strQuestionXNeedsAnsweringBeforeContinuing, $question->taborder)."</p>";
                        }
                        echo "<br />";
                    }

                    echo nl2br($form->thanks);

                    echo "<br /><input type='hidden' name='action' value='save' />\n";
                    echo "<input type='hidden' name='ax' value='".strip_tags($_REQUEST['ax'])."' />\n";
                    echo "<input type='submit' value='Submit' />\n";
                    echo "</form>\n";
                    if ($reqd >= 1)
                    {
                        echo "<p><sup style='color: red; font-size: 120%;'>*</sup> {$strQuestionRequired}</p>";
                    }
                }
            }
        }

        if ($_REQUEST['mode'] != 'bare')
        {
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        else
        {
            echo "\n</div>\n</body>\n</html>\n";
        }
        break;
}

?>
