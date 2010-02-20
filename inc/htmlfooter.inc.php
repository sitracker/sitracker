<?php
// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

// This Page Is Valid XHTML 1.0 Transitional! 27Oct05
echo "\n</div>"; // mainframe
echo "\n<div id='statusbar'>";
if ($_SESSION['auth'] == TRUE) echo "<a href='about.php'>";
echo "<img src='{$CONFIG['application_webpath']}images/sitting_man_logo16x16.png' width='16' height='16' border='0' alt='About {$CONFIG['application_shortname']}' />";
if ($_SESSION['auth'] == TRUE) echo "</a>";
echo " <strong><a href='http://sitracker.org/'>Support Incident Tracker</a>";
if ($_SESSION['auth'] == TRUE) echo " {$application_version_string}";
echo "</strong>";
if ($_SESSION['auth'] == TRUE)
{
    echo " running ";
    if ($CONFIG['demo']) echo "in DEMO mode ";
    echo "on ".strip_tags($_SERVER["SERVER_SOFTWARE"]);
    echo " at ".ldate('H:i',$now, FALSE);
}
echo "</div>\n";
if ($_SESSION['auth'] == TRUE
    AND (!empty($application_revision) AND (substr($application_revision, 0, 4)=='beta')
    OR (substr($application_revision, 0, 5)=='alpha')
    OR (substr($application_revision, 0, 3)=='svn')))
{
    echo "<p class='warning'>".sprintf($strPreReleaseNotice, "v{$application_version} {$application_revision}");
    echo ". <a href=\"{$CONFIG['bugtracker_url']}\" target='_blank' >{$strReportBug}</a></p>";
}

if ($CONFIG['debug'] == TRUE)
{
    echo "\n<div id='tail'><strong>DEBUG</strong><br />";
    $exec_time_end = getmicrotime();
    $exec_time = $exec_time_end - $exec_time_start;
    echo "<p>CPU Time: ".number_format($exec_time,3)." seconds</p>";
    if (isset($dbg)) echo "<hr /><pre>".print_r($dbg,true)."</pre>";
    echo "</div>";
}
echo "\n</body>\n</html>\n";
?>