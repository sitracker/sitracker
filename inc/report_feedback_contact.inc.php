<?php
// feedback3.php - Feedback scores by contact
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
// Hacked: Tom Gerrard <tom.gerrard[at]salfordsoftware.co.uk>
// Converted: Paul Heaney <paulheaney[at]users.sourceforge.net>

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


$maxscore = $CONFIG['feedback_max_score'];
$formid = $CONFIG['feedback_form'];
$now = time();

echo "<div style='margin: 20px'>";
echo "<h2><a href='{$CONFIG['application_webpath']}report_feedback.php'>{$strFeedback}</a> {$strScores}: {$strByContact}</h2>";
echo feedback_between_dates();
echo "<p>{$strCustomerFeedbackReportSiteMsg}:</p>";

$qsql = "SELECT * FROM `{$dbFeedbackQuestions}` WHERE formid='{$formid}' AND type='rating' ORDER BY taborder";
$qresult = mysql_query($qsql);
if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
while ($qrow = mysql_fetch_object($qresult))
{
    $q[$qrow->taborder] = $qrow;
}


$msql = "SELECT *, cs.name AS closingstatusname, s.name AS sitename, s.id as siteid, (i.closed - i.opened) AS duration, \n";
$msql .= "fr.id AS reportid, c.id AS contactid ";
$msql .= "FROM `{$dbFeedbackRespondents}` AS fr, `{$dbIncidents}` AS i, `{$dbContacts}` AS c, `{$dbSites}` AS s, `{$dbClosingStatus}` AS cs WHERE fr.incidentid = i.id \n";
$msql .= "AND i.contact = c.id ";
$msql .= "AND c.siteid = s.id ";
$msql .= "AND i.closingstatus = cs.id ";
$msql .= "AND fr.incidentid > 0 \n";
$msql .= "AND fr.completed = 'yes' \n";

if (!empty($startdate))
{
    if ($dates == 'feedbackin')
    {
        $msql .= "AND fr.created >= '{$startdate}' ";
    }
    elseif ($dates == 'closedin')
    {
        $msql .= "AND i.closed >= '{$startdate}' ";
    }

    //echo "DATES {$dates}";
}

if (!empty($enddate))
{
    if ($dates == 'feedbackin')
    {
        $msql .= "AND fr.created <= '{$enddate}' ";
    }
    elseif ($dates == 'closedin')
    {
        $msql .= "AND i.closed <= '{$enddate}' ";
    }
}

if (!empty($id)) $msql .= "AND i.contact='{$id}' \n";
else $msql .= "ORDER BY c.surname ASC, c.forenames ASC, i.contact ASC , i.id ASC \n";
$mresult = mysql_query($msql);
if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

if (mysql_num_rows($mresult) >= 1)
{
    $prevcontactid = 0;
    $countcontacts = 0;
    for ($i = 0; $i <= 10; $i++)
    {
      $counter[$i] = 0;
    }

    $surveys = 0;
    debug_log("Feedback report sql: ".$msql, TRUE);


    $firstrun = 0;
    while ($mrow = mysql_fetch_object($mresult))
    {
        // Only print if we have a value ({$prevcontactid} / {$mrow->contactid})
        if ($prevcontactid != $mrow->contactid AND $firstrun != 0)
        {
            $numones = count($storeone);
            if ($numones > 0)
            {
                for($c = 1; $c <= $numones; $c++)
                {
                    if ($storeone[$c] > 0) $qr = number_format($storeone[$c] / $storetwo[$c], 2);
                    else $qr=0;
                    if ($storeone[$c] > 0) $qp = number_format((($qr -1) * (100 / ($maxscore - 1))), 0);
                    else $qp = 0;
                    $html .= "Q$c: {$q[$c]->question} {$qr} <strong>({$qp}%)</strong><br />";
                    $gtotal += $qr;
                }
                if ($c>0) $c--;
                $total_average = number_format($gtotal / $c,2);
                // $total_percent=number_format((($gtotal / ($maxscore * $c)) * 100), 0);
                $total_percent = number_format((($total_average -1) * (100 / ($maxscore -1))), 0);
                if ($total_percent < 0) $total_percent = 0;

                $html .= "<p>{$strPositivity}: {$total_average} <strong>({$total_percent}%)</strong>, ".sprintf($strAfterXSurveys,$surveys)."</p>";
                if ($surveys<>1) $html .= 's';
                $html .= "</p><br /><br />";
            }
            else $html = '';

            if ($total_average>0)
            {
                echo "{$html}";
                $countcontacts++;

                $counter[floor($total_percent / 10)] ++;
            }
            unset($qavgavg);
            unset($qanswer);
            unset($dbg);
            unset($storeone);
            unset($storetwo);
            unset($gtotal);
            $surveys=0;
        }
        $firstrun=1;

        // Loop through reports
        $totalresult = 0;
        $numquestions = 0;
        $surveys++;
        $html = "<h4 style='text-align: left;'><a href='../contact_details.php?id={$mrow->contactid}' title='Jump to Contact'>{$mrow->forenames} {$mrow->surname}</a>, <a href='../site_details.php?id={$mrow->siteid}&action=show' title='Jump to site'>{$mrow->sitename}</a></h4>";
        $csql = "SELECT * FROM `{$dbFeedbackQuestions}` WHERE formid='{$formid}' AND type='text' ORDER BY id DESC";
        $cresult = mysql_query($csql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        $crow = mysql_fetch_object($cresult);
        $textquestion = $crow->id;
        $csql = "SELECT DISTINCT i.id as incidentid, result, i.title as title
                 FROM `{$dbFeedbackRespondents}` AS fr, `{$dbIncidents}` AS i, `{$dbUsers}` AS u, `{$dbFeedbackResults}` AS r
                 WHERE fr.incidentid = i.id
                 AND i.owner = u.id
                 AND fr.id = r.respondentid
                 AND r.questionid = '$textquestion'
                 AND fr.id = '$mrow->reportid' ";
        if (!empty($startdate))
        {
            if ($dates == 'feedbackin')
            {
                $csql .= "AND fr.created >= '{$startdate}' ";
            }
            elseif ($dates == 'closedin')
            {
                $csql .= "AND i.closed >= '{$startdate}' ";
            }
        }

        if (!empty($enddate))
        {
            if ($dates == 'feedbackin')
            {
                $csql .= "AND fr.created <= '{$enddate}' ";
            }
            elseif ($dates == 'closedin')
            {
                $csql .= "AND i.closed <= '{$enddate}' ";
            }
        }
        $csql .= "ORDER BY i.contact, i.id";
        $cresult = mysql_query($csql);

        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);

        while ($crow = mysql_fetch_object($cresult))
        {
            if ($crow->result != '')
            {
                $html.= "<p>{$crow->result}<br /><em><a href=\"javascript:incident_details_window(\'{$crow->incidentid}\','sit_popup', false)\">{$crow->incidentid}</a> {$crow->title}</em></p>";
            }
        }

        $qsql = "SELECT * FROM `{$dbFeedbackQuestions}` WHERE formid='{$formid}' AND type='rating' ORDER BY taborder";
        $qresult = mysql_query($qsql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        while ($qrow = mysql_fetch_object($qresult))
        {
            $numquestions++;
            $sql = "SELECT * FROM `{$dbFeedbackRespondents}` AS fr, `{$dbIncidents}` AS i, `{$dbUsers}` AS u, `{$dbFeedbackResults}` AS r ";
            $sql .= "WHERE fr.incidentid = i.id ";
            $sql .= "AND i.owner = u.id ";
            $sql .= "AND fr.id = r.respondentid ";
            $sql .= "AND r.questionid = '$qrow->id' ";
            $sql .= "AND fr.id = '$mrow->reportid' ";

            if (!empty($startdate))
            {
                if ($dates == 'feedbackin')
                {
                    $sql .= "AND fr.created >= '{$startdate}' ";
                }
                elseif ($dates == 'closedin')
                {
                    $sql .= "AND i.closed >= '{$startdate}' ";
                }
            }

            if (!empty($enddate))
            {
                if ($dates == 'feedbackin')
                {
                    $sql .= "AND fr.created <= '{$enddate}' ";
                }
                elseif ($dates == 'closedin')
                {
                    $sql .= "AND i.closed <= '{$enddate}' ";
                }
            }

            $sql .= "ORDER BY i.contact, i.id";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            $numresults = 0;
            $cumul = 0;
            $percent = 0;
            $average = 0;
            $answercount = mysql_num_rows($result);

            if ($answercount > 0)
            {
                while ($row = mysql_fetch_object($result))
                {
                    // Loop through the results
                    if (!empty($row->result))
                    {
                        $cumul += $row->result;
                        $numresults++;
                        $storeone[$qrow->taborder] += $row->result;
                        $storetwo[$qrow->taborder]++;
                        $storethree[$qrow->taborder]=$qrow->id;
                    }
                }
            }

            if ($numresults > 0) $average = number_format(($cumul / $numresults), 2);
            $percent = number_format((($average / $maxscore ) * 100), 0);
            $totalresult += $average;

            $qanswer[$qrow->taborder] += $average;
            $qavgavg = $qanswer[$qrow->taborder];
        }

        $prevcontactid = $mrow->contactid;
    }
    echo "<h2>{$strSummary}</h2><p>{$strShowPositivityGraph}:</p>";

    $adjust = 13;
    $min = 4;
    for ($i = 0; $i <= 10; $i++)
    {
        if ($countcontacts > 0) $weighted = number_format((($counter[$i] / $countcontacts) * 100), 0);
        else $weighted = 0;
        echo "<div style='background: #B";
        echo dechex(floor($i*1.5));
        echo "0; color: #FFF; float:left; width: ".($min + ($weighted * $adjust))."px;'>&nbsp;</div>&nbsp; ";
        echo ($i * 10);
        if ($i < 10)
        {
            echo " - ";
            echo ($i*10) + 9;
        }
        echo "% ({$weighted}%)<br />";
    }
}
else
{
    echo user_alert($strNoFeedbackFound, E_USER_WARNING);
}

echo "</div>\n";

?>