<?php
// new_feedback.php - Feedback report menu
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Report Type: Feedback

// Author:  Paul Heaney Paul Heaney <paulheaney[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_REPORT_RUN; // Run Reports
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$type = cleanvar($_REQUEST['type']);
$dates = cleanvar($_REQUEST['dates']);
$startdate = strtotime(cleanvar($_REQUEST['startdate']));
$enddate = strtotime(cleanvar($_REQUEST['enddate']));
$formid = clean_int($CONFIG['feedback_form']);

$title = $strFeedbackReport;


/// echo "Start: {$startdate}";

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('reports', 32)." {$strFeedbackReport}</h2>";

function feedback_between_dates()
{
    global $dates, $startdate, $enddate, $CONFIG;
    if (!empty($startdate))
    {
        if (!empty($enddate))
        {
            if ($dates == 'feedbackin')
            {
                $str = "<p>".sprintf($GLOBALS['strFeedbackBetweenXandY'], ldate($CONFIG['dateformat_date'], $startdate), ldate($CONFIG['dateformat_date'], $enddate))."</p>";
            }
            elseif ($dates == 'closedin')
            {
                $str = "<p>Closed between ".ldate($CONFIG['dateformat_date'], $startdate)." and ".ldate($CONFIG['dateformat_date'], $enddate)."</p>";
            }
        }
        else
        {
            if ($dates == 'feedbackin')
            {
                $str = "<p>".sprintf($GLOBALS['strFeedbackAfterX'], ldate($CONFIG['dateformat_date'], $startdate))."</p>";
            }
            elseif ($dates == 'closedin')
            {
                $str = "<p>".sprintf($GLOBALS['strClosedAfterX'], ldate($CONFIG['dateformat_date'], $startdate))."</p>";
            }
        }
    }
    elseif (!empty($enddate))
    {
        if ($dates == 'feedbackin')
        {
            $str = "<p>".sprintf($GLOBALS['strFeedbackBeforeX'], ldate($CONFIG['dateformat_date'], $enddate))."</p>";
        }
        elseif ($dates == 'closedin')
        {
            $str = "<p>".srintf($GLOBALS['strClosedBeforeX'], ldate($CONFIG['dateformat_date'], $enddate))."</p>";
        }
    }
    return $str;
}

if (empty($type))
{
    include (APPLICATION_INCPATH . 'report_feedback_form.inc.php');
}
elseif ($type == 'byengineer')
{
    include (APPLICATION_INCPATH . 'report_feedback_engineer.inc.php');
}
elseif ($type == 'bycustomer')
{
    include (APPLICATION_INCPATH . 'report_feedback_contact.inc.php');
}
elseif ($type == 'bysite')
{
    include (APPLICATION_INCPATH . 'report_feedback_site.inc.php');
}
elseif ($type == 'byproduct')
{
    include (APPLICATION_INCPATH . 'report_feedback_product.inc.php');
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>