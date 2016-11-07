<?php
// engineer.inc.php - Feedback report by engineer
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivan[at]sitracker.org>
//          Paul Heaney <paul[at]sitracker.org>


// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


$formid = clean_int($CONFIG['feedback_form']);
$userid = clean_int($_REQUEST['userid']);

echo "<div style='margin: 20px'>";
echo "<h2><a href='{$CONFIG['application_webpath']}reports/feedback.php'>{$strFeedback}</a> {$strScores}: {$strByEngineer}</h2>";
echo feedback_between_dates();
echo "<p align='center'>{$strCustomerFeedbackReportSiteMsg}:</p>";

$usql = "SELECT * FROM `{$dbUsers}` WHERE status > 0 ";
if ($userid > 0) $usql .= "AND id={$userid} ";
else $usql .= "ORDER BY username";
$uresult = mysqli_query($db, $usql);
if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
if (mysqli_num_rows($uresult) >= 1)
{
    while ($user = mysqli_fetch_object($uresult))
    {
        $totalresult = 0;
        $numquestions = 0;
        $html = "<h2>".ucfirst($user->realname)."</h2>";
        $qsql = "SELECT * FROM `{$dbFeedbackQuestions}` WHERE formid='{$formid}' AND type='rating' ORDER BY taborder";
        $qresult = mysqli_query($db, $qsql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

        if (mysqli_num_rows($qresult) >= 1)
        {
            while ($qrow = mysqli_fetch_object($qresult))
            {
                $numquestions++;
                $html .= "Q{$qrow->taborder}: {$qrow->question} &nbsp;";
                $sql = "SELECT r.result FROM `{$dbFeedbackRespondents}` AS fr, `{$dbIncidents}` AS i, `{$dbUsers}` AS u, `{$dbFeedbackResults}` AS r ";
                $sql .= "WHERE fr.incidentid = i.id ";
                $sql .= "AND i.owner = u.id ";
                $sql .= "AND fr.id = r.respondentid ";
                $sql .= "AND r.questionid = '$qrow->id' ";
                $sql .= "AND u.id = '$user->id' ";
                $sql .= "AND fr.completed = 'yes' \n";

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

                    //echo "DATES {$dates}";
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

                $sql .= "ORDER BY i.owner, i.id";
                $result = mysqli_query($db, $sql);
                if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
                $numresults = 0;
                $cumul = 0;
                $percent = 0;
                $average = 0;
                $calcnumber = (100 / ($CONFIG['feedback_max_score'] -1));
                ## echo "=== $sql<br /> ";
                while ($row = mysqli_fetch_object($result))
                {
                    if (!empty($row->result))
                    {
                        $cumul += $row->result;
                        $numresults++;
                    }
                }
                if ($numresults > 0)
                {
                    $average = number_format(($cumul/$numresults), 2);
                }

                $percent = number_format((($average -1) * ($calcnumber)), 0);
                if ($percent < 0)
                {
                    $percent = 0;
                }

                $totalresult += $average;
                $html .= "{$average} <strong>({$percent}%)</strong><br />";
            }
            $total_average = number_format($totalresult/$numquestions,2);
            $total_percent = number_format((($total_average -1) * (100 / ($CONFIG['feedback_max_score'] -1))), 0);
            if ($total_percent < 0) $total_percent=0;
            $html .= "<p>{$strPositivity}: {$total_average} <strong>({$total_percent}%)</strong> ".sprintf($strAfterXSurveys, $numresults)."</p>";
            $surveys += $numresults;
            $html .= "<hr />\n";

            //if ($total_average>0)
            echo $html;
            echo "\n\n\n<!-- $surveys -->\n\n\n";
        }
        else
        {
            echo user_alert($strNoFeedbackFound, E_USER_WARNING);
        }
    }
}
else
{
    echo user_alert($strFoundNoUsersToReport, E_USER_WARNING);;
}
echo "</div>\n";

?>
