<?php
// browse_feedback.php - View a list of feedback
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// by Ivan Lucas <ivanlucas[at]users.sourceforge.net>, June 2004


$permission = 51; // View Feedback

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strBrowseFeedback;
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

// External variables
$formid = cleanvar($_REQUEST['id']);
$responseid = clean_int($_REQUEST['responseid']);
$sort = cleanvar($_REQUEST['sort']);
$order = cleanvar($_REQUEST['order']);
$mode = cleanvar($_REQUEST['mode']);
$completed = cleanvar($_REQUEST['completed']);

switch ($mode)
{
    case 'viewresponse':
        echo "<h2>".icon('contract', 32)." {$strFeedback}</h2>";
        $sql = "SELECT * FROM `{$dbFeedbackRespondents}` WHERE id='{$responseid}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        $response = mysql_fetch_object($result);
        if ($response->completed == 'yes')
        {
            $responsecompleted = $strYes;
        }
        else
        {
            $responsecompleted = $strNo;
        }
        echo "<table class='vertical' align='center'>";
        echo "<tr><th>{$strContact}</th><td>{$response->contactid} - ".contact_realname($response->contactid)."</td></tr>\n";
        echo "<tr><th>{$strIncident}</th><td>".html_incident_popup_link($response->incidentid, "{$response->incidentid} - ".incident_title($response->incidentid))."</td>\n";
        echo "<tr><th>{$strForm}</th><td>{$response->formid} - ".db_read_column('name', $dbFeedbackForms, $response->formid)." </td>\n";
        echo "<tr><th>{$strDate}</th><td>{$response->created}</td>\n";
        echo "<tr><th>{$strCompleted}</th><td>{$responsecompleted}</td>\n";
        echo "</table>\n";

        echo "<h3>{$strResponsesToFeedbackForm}</h3>";
        $numquestions=0;

        // Return Ratings
        $qsql = "SELECT * FROM `{$dbFeedbackQuestions}` WHERE formid='{$response->formid}' AND type='rating' ORDER BY taborder";
        $qresult = mysql_query($qsql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($qresult) >= 1)
        {
            $html .= "<table align='center' class='vertical'>";

            $numresults = 0;
            $cumul = 0;
            $numquestions++;
            $average = 0;
            $statquestions = 0;

            while ($qrow = mysql_fetch_object($qresult))
            {
                $html .= "<tr><th>Q{$qrow->taborder}: {$qrow->question}</th>";
                $sql = "SELECT r.result FROM `{$dbFeedbackRespondents}` AS f, `{$dbIncidents}` AS i, `{$dbUsers}` AS u, `{$dbFeedbackResults}` AS r ";
                $sql .= "WHERE f.incidentid=i.id ";
                $sql .= "AND i.owner=u.id ";
                $sql .= "AND f.id=r.respondentid ";
                $sql .= "AND r.questionid='{$qrow->id}' ";
                $sql .= "AND f.id='$responseid' ";
                $sql .= "AND f.completed = 'yes' \n";
                $sql .= "ORDER BY i.owner, i.id";
                $result = mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                while ($row = mysql_fetch_object($result))
                {
                    $numresults++;
                    if (!empty($row->result) OR ($row->result == 0))
                    {

                        if ($row->result != 0)
                        {
                            $cumul += $row->result;
                            $html .= "<td>" . $row->result . "</td></tr>";
                            $statquestions++;
                        }
                        else
                        {
                             $html .= "<td>{$strNoAnswerGiven}</td></tr>";
                        }
                    }
                }

                $calcnumber = (100 / ($CONFIG['feedback_max_score'] - 1));

                if ($statquestions>0)
                {
                    $average = number_format(($cumul / $statquestions), 2);
                    $percent = number_format((($calcnumber * ($cumul-$statquestions)) / $statquestions), 2);
                }

            }
            $html .= "</table>\n";
            $html .= "<p align='center'>{$strPositivity}: {$average} <strong>({$percent}%)</strong></p>";
            $html .= "<p align='center'>{$strAnswered}: <strong>{$statquestions}</strong>/{$numresults}</p>";

            // Return text/options/multioptions fields
            $qsql = "SELECT * FROM `{$dbFeedbackQuestions}` WHERE formid='{$response->formid}' AND type='text' OR type='options' OR type='multioptions' ORDER BY taborder";
            $qresult = mysql_query($qsql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

            if (mysql_num_rows($qresult) >= 1)
            {
                while ($qrow = mysql_fetch_object($qresult))
                {

                    $sql = "SELECT r.result FROM `{$dbFeedbackRespondents}` AS f, `{$dbIncidents}` AS i, `{$dbUsers}` AS u, `{$dbFeedbackResults}` AS r ";
                    $sql .= "WHERE f.incidentid = i.id ";
                    $sql .= "AND i.owner = u.id ";
                    $sql .= "AND f.id = r.respondentid ";
                    $sql .= "AND r.questionid = '{$qrow->id}' ";
                    $sql .= "AND f.id = '{$responseid}' ";
                    $sql .= "AND f.completed = 'yes' \n";
                    $sql .= "ORDER BY i.owner, i.id";
                    $result = mysql_query($sql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                    while ($row = mysql_fetch_object($result))
                    {
                        $html .= "<p align='center'><strong>Q{$qrow->taborder}: {$qrow->question}</strong></p>";
                        if (!empty($row->result))
                        {
                            $html .= "<p align='center'>{$row->result}</p>";
                        }
                        else
                        {
                            $html .= "<p align='center'><em>{$strNoAnswerGiven}</em></p>";
                        }
                    }
                }
            }

            $surveys += $numresults;

            //if ($total_average>0)
            echo $html;
            echo "\n\n\n<!-- $surveys -->\n\n\n";
        }
        else
        {
            echo user_alert($strNoResponseFound, E_USER_NOTICE);
        }
        plugin_do('feedback_browse_viewresponse');
        echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}'>{$strBackToList}</p>";
        break;
    default:
        $sql = "SELECT * FROM `{$dbFeedbackForms}`";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        $fresult = mysql_fetch_object($result);

        if (mysql_num_rows($result) == 0)
        {
            // no feedback forms
            echo "<h3>{$title}</h3>";
            echo user_alert($strNoFeedbackFormsDefined, E_USER_NOTICE);
            echo "<p align='center'><a href='feedback_form_edit.php?action=new'>{$strCreateNewForm}</a></p>";
        }
        else
        {
            if (empty($formid) AND !empty($CONFIG['feedback_form'])) $formid = $CONFIG['feedback_form'];
            else $formid = 1;

            $sql  = "SELECT *, fr.created as respcreated, fr.id AS respid FROM `{$dbFeedbackRespondents}` AS fr, `{$dbFeedbackForms}` AS ff ";
            $sql .= "WHERE fr.formid = ff.id ";
            if ($completed == 'no') $sql .= "AND completed='no' ";
            else $sql .= "AND completed='yes' ";
            if (!empty($formid)) $sql .= "AND formid='{$formid}'";

            if ($order == 'a' OR $order == 'ASC' OR $order == '') $sortorder = "ASC";
            else $sortorder = "DESC";

            switch ($sort)
            {
                case 'created':
                    $sql .= " ORDER BY fr.created {$sortorder}";
                    break;
                case 'contactid':
                    $sql .= " ORDER BY contactid {$sortorder}";
                    break;
                case 'incidentid':
                    $sql .= " ORDER BY incidentid {$sortorder}";
                    break;
                default:
                    $sql .= " ORDER BY fr.created DESC";
                    break;
            }
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

            $countrows = mysql_num_rows($result);

            if (!empty($formid))
            {
                if ($completed == 'no') echo "<h3>{$strFeedbackRequested}: {$formid} </h3>";
                else echo "<h3>{$strResponsesToFeedbackForm}: {$formid} - {$fresult->name}</h3>";
                echo "<p align='center'><a href='feedback_form_edit.php?formid={$formid}'>{$strEdit}</a></p>";
            }
            else
            {
                echo "<h3>{$strResponsesToAllFeedbackForms}</h3>";
            }

            if ($countrows >= 1)
            {
                echo "<table summary='feedback forms' width='95%' align='center'>";
                echo "<tr>";
                echo colheader('created', $strDate, $sort, $order, $filter);
                echo colheader('contactid', $strContact,$sort, $order, $filter);
                echo colheader('incidentid', $strIncident,$sort, $order, $filter);
                echo "<th>{$strOperation}</th>";
                echo "</tr>\n";
                $shade = 'shade1';
                while ($resp = mysql_fetch_object($result))
                {
                    $respondentarr = explode('-', $resp->respondent);
                    $responserefarr = explode('-', $resp->responseref);

                    $hashcode = feedback_hash($resp->formid, $resp->contactid, $resp->incidentid, contact_email($resp->contactid));
                    echo "<tr class='{$shade}'>";
                    echo "<td>".ldate($CONFIG['dateformat_datetime'], mysqlts2date($resp->respcreated))."</td>";
                    echo "<td><a href='contact_details.php?id={$resp->contactid}' title='{$resp->email}'>".contact_realname($resp->contactid)."</a> {$strFrom} <a href='site_details.php?id=".contact_siteid($resp->contactid)."'>".contact_site($resp->contactid)."</a> </td>";
                    echo "<td>".html_incident_popup_link($resp->incidentid, "{$strIncident} [{$resp->incidentid}]")." - ";
                    echo incident_title($resp->incidentid)."</td>";
                    $url = "feedback.php?ax={$hashcode}";
                    if ($resp->multi == 'yes') $url .= "&amp;rr=1";

                    echo "<td>";
                    if ($resp->completed == 'no') echo "<a href='{$url}' title='{$url}' target='_blank'>URL</a>";
                    $eurl = urlencode($url);
                    $eref = urlencode($resp->responseref);
                    if ($resp->completed == 'no')
                    {
                        //if ($resp->remind<1) echo "<a href='formactions.php?action=remind&amp;id={$resp->respid}&amp;url={$eurl}&amp;ref={$eref}' title='Send a reminder by email'>Remind</a>";
                        //elseif ($resp->remind == 1) echo "<a href='formactions.php?action=remind&amp;id={$resp->respid}&amp;url={$eurl}&amp;ref={$eref}' title='Send a Second reminder by email'>Remind Again</a>";
                        //elseif ($resp->remind == 2) echo "<a href='formactions.php?action=callremind&amp;id={$resp->respid}&amp;url={$eurl}&amp;ref={$eref}' title='Send a Third reminder by phone call, click here when its done'>Remind by Phone</a>";
                        //else echo "<strike title='Already sent 3 reminders'>Remind</strike>";
                        //echo " &bull; ";
                        //echo "<a href='formactions.php?action=delete&amp;id={$resp->respid}' title='Remove this form'>Delete</a>";
                    }
                    else
                    {
                        echo "<a href='{$_SERVER['PHP_SELF']}?mode=viewresponse&amp;responseid={$resp->respid}'>{$strViewResponse}</a>";
                    }
                    echo "</td>";
                    echo "</tr>\n";
                    if ($shade == 'shade1') $shade = 'shade2';
                    else $shade = 'shade1';
                }
                echo "</table>\n";
                plugin_do('feedback_browse');
            }
            else
            {
                echo user_alert($strNoResponseFound, E_USER_NOTICE);
            }
            if ($completed == 'no')
            {
                $sql = "SELECT COUNT(id) FROM `{$dbFeedbackRespondents}` WHERE formid='{$formid}' AND completed='yes'";
                $result = mysql_query($sql);
                list($completedforms) = mysql_fetch_row($result);
                if ($completedforms > 0)
                {
                    echo "<p align='center'>".sprintf($strFeedbackFormsReturned, "<a href='{$_SERVER['PHP_SELF']}'>{$completedforms}</a>")."</p>";
                }
            }
            else
            {
                $sql = "SELECT COUNT(id) FROM `{$dbFeedbackRespondents}` WHERE formid='{$formid}' AND completed='no'";
                $result = mysql_query($sql);
                list($waiting) = mysql_fetch_row($result);
                if ($waiting > 0) echo "<p align='center'>".sprintf($strFeedbackFormsWaiting, "<a href='{$_SERVER['PHP_SELF']}?completed=no'>{$waiting}</a>")."</p>";
            }
        }
}
plugin_do('feedback_browse_endpage_extend');
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>
