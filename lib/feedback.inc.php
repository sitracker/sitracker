<?php
// feedback.inc.php - functions relating to feedback
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
 * Identifies whether feedback should be send for this contract,
 * This checks against $CONFIG['no_feedback_contracts'] to see if the contract is set to receive no feedback
 * @param $contractid int The contract ID to check
 * @return bool TRUE if feedback should be sent, false otherwise
 * @author Paul Heaney
 */
function send_feedback($contractid)
{
    global $CONFIG;
    if (!$CONFIG['feedback_enabled'])
    {
        return FALSE;
    }
    else
    {
        foreach ($CONFIG['no_feedback_contracts'] AS $contract)
        {
            if ($contract == $contractid)
            {
                return FALSE;
            }
        }
    }

    return TRUE;
}

/**
 * Creates a blank feedback form response
 * @param $formid int The feedback form to use
 * @param $incidentid int The incident to generate the form for
 * @return int The form ID
 */
function create_incident_feedback($formid, $incidentid)
{
    global $dbFeedbackRespondents;
    $contactid = incident_contact($incidentid);
    $email = contact_email($contactid);

    $sql = "INSERT INTO `{$dbFeedbackRespondents}` (formid, contactid, email, incidentid) VALUES (";
    $sql .= "'".mysql_real_escape_string($formid)."', ";
    $sql .= "'".mysql_real_escape_string($contactid)."', ";
    $sql .= "'".mysql_real_escape_string($email)."', ";
    $sql .= "'".mysql_real_escape_string($incidentid)."') ";
    mysql_query($sql);
    if (mysql_error()) trigger_error ("MySQL Error: ".mysql_error(), E_USER_ERROR);
    $blankformid = mysql_insert_id();
    return $blankformid;
}


/**
 * Generates a feedback form hash
 * @author Kieran Hogg
 * @param $formid int ID of the form to use
 * @param $contactid int ID of the contact to send it to
 * @param $incidentid int ID of the incident the feedback is about
 * @return string the hash
 */
function feedback_hash($formid, $contactid, $incidentid)
{
    $hashtext = urlencode($formid)."&&".urlencode($contactid)."&&".urlencode($incidentid);
    $hashcode4 = str_rot13($hashtext);
    $hashcode3 = gzcompress($hashcode4);
    $hashcode2 = base64_encode($hashcode3);
    $hashcode1 = trim($hashcode2);
    $hashcode = urlencode($hashcode1);
    return $hashcode;
}

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

    $html = "<table>\n";
    if (empty($promptleft) == FALSE OR empty($promptright) == FALSE)
    {
        $html .= "<tr width='25%'>";
        $html .= "<th colspan='{$score_max}' style='text-align: left;'>";
        $html .= "<div style='float: right;'>{$promptright}</div><div>{$promptleft}</div></th>";
        if ($required != 'true')
        {
            $html .= "<th>{$GLOBALS['strNotApplicableAbbrev']}</th>";
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
        else
        {
            $html .= "<td width='{$colwidth}'";
        }
        


        $html .= "/></td>";
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


?>