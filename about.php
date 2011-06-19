<?php
// about.php - Credit, Copyright and Licence page
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional! 28Oct05


$permission = 41; // View Status

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strAbout;
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

// External variables
$seltab = clean_fixed_list($_REQUEST['tab'], array('about', 'authors', 'thanks'));

echo "<div id='aboutsit'>";
echo "<img src='images/sitlogo_270x100.png' width='270' height='100' alt='SiT! Support Incident Tracker' />";

$tabs['about'] = "{$_SERVER['PHP_SELF']}?tab=about";
$tabs['authors'] = "{$_SERVER['PHP_SELF']}?tab=authors";
$tabs['thanks'] = "{$_SERVER['PHP_SELF']}?tab=thanks";

echo draw_tabs($tabs, $seltab);
echo "<div id='aboutbox'>";
switch ($seltab)
{
    case 'authors':
        echo "<h2>{$strAuthors}</h2>";
        break;

    case 'thanks':
        echo "<h2>{$strThanksTo}</h2>";

$fp = fopen($CONFIG['creditsfile'], "r");

while (!feof($fp))
{
    $line = trim(fgets($fp, 4096));
    if (mb_substr($line, 0, 1) != '#' AND mb_substr($line, 0, 1) != ' ' AND mb_substr($line, 0, 1) != '') $creditlines[] = $line;
}
fclose($fp);
$creditcount = count($creditlines);
shuffle($creditlines);
$count = 0;
// TODO would be nice to scroll these credits using Javascript (degrading nicely)
$creditperson = array();
// echo "<tr><td class='shade2' colspan='2'><p align='center'>{$strManyThanks}</p><h4>";
foreach ($creditlines AS $creditline)
{
    // works preg_match("/(.*)\\s+--\\s+(.*)/i", $creditline, $matches); //<(.*)>\\s+--\\s+(*.)
    preg_match("/(.*)\\s+--\\s+(.*)/i", $creditline, $matches); //<(.*)>\\s+--\\s+(*.)
    preg_match("/<(.*\[at\].*)>/i", $creditline, $mailmatch); //<(.*)>\\s+--\\s+(*.)
    $credit[$count]['name'] = htmlentities(strip_tags($matches[1]), ENT_COMPAT, $i18ncharset);
    $credit[$count]['workedon'] = htmlentities($matches[2], ENT_COMPAT, $i18ncharset);
    if (!empty($mailmatch[1])) $credit[$count]['email'] = preg_replace("/\[at\]/", "@", $mailmatch[1]);;
    $count++;
//     echo "<pre>".print_r($matches,true)."</pre>";;
}


        echo "<p align='center'>Incorporating:</p>
            <p align='center'>whatever:hover (csshover.htc) 1.41 by <a href='http://www.xs4all.nl/~peterned/'>Peter Nederlof</a><br />
            &copy; 2005 - Peter Nederlof.  Licensed under the LGPLv2.</p>

            <p align='center'><a href='https://sourceforge.net/projects/nusoap/'>NuSOAP</a> 0.7.3 by <a href='http://www.nusphere.com/'>NuSphere Corporation</a><br />
            Copyright &copy; 2002 NuSphere Corporation. Licensed under the LGPL v2.</p>

            <p align='center'>MagpieRSS 0.72 by <a href='http://magpierss.sourceforge.net/'>Kellan Elliott-McCrea</a><br />
            Copyright &copy; Kellan Elliott-McCrea. Licensed under the GPL v2.</p>

            <p align='center'>pChart 2.1.0 from the <a href='http://www.pchart.net'>pChart project</a>.   Licensed under GPLv3</p>

            <p align='center'>Prototype JavaScript framework 1.7 by <a href='http://www.prototypejs.org/'>Sam Stephenson</a><br />
            Copyright &copy; 2005-2010 Sam Stephenson. Licensed under an MIT style license.</p>

            <p align='center'>script.aculo.us 1.9.0 by <a href='http://script.aculo.us'>Thomas Fuchs</a><br />
            Copyright &copy; 2005-2010 Thomas Fuchs. Licensed under an MIT style license.</p>

            <p align='center'>Icons from the Crystal Project by <a href='http://www.everaldo.com/'>Everaldo Coelho</a><br />
            Copyright &copy;  2006-2007 Everaldo Coelho. Licensed under the LGPLv2</p>

            <p align='center'>Icons from the <a href='http://www.oxygen-icons.org/'>Oxygen Project</a><br />
            Copyright &copy; 2008 The Oxygen Project. Licensed under the LGPLv2</p>

            <p align='center'>MIME parser class 1.80 by <a href='http://www.phpclasses.org/package/3169-PHP-Decode-MIME-e-mail-messages.html'>Manuel Lemos</a><br />
            Copyright &copy; 2006-2008 Manuel Lemos. Licensed under the BSD License</p>";

        break;

    case 'about':
    default:
        echo "<h2>SiT! Support Incident Tracker</h2>";

        echo "<p align='center'>{$strVersion}: {$application_version} {$application_revision}";
        if ($CONFIG['debug'] == TRUE) echo " (debug mode)";
        echo "</p>";
        debug_log("{$strVersion}: {$application_version} {$application_revision}", TRUE);

        echo "<p align='center'>Copyright &copy; 2010-2011 <a href='http://sitracker.org'>The Support Incident Tracker Project</a><br />";
        echo "Copyright &copy; 2000-2009 Salford Software Ltd. and Contributors<br />";
        echo "Licence: GNU General Public License Version 2.<br /></p>";

        if (is_array($CONFIG['plugins']) AND $CONFIG['plugins'][0] != '' AND count($CONFIG['plugins']) > 0)
        {
            foreach ($CONFIG['plugins'] AS $plugin)
            {
                $plugin = trim($plugin);
                echo "<p>{$strPlugin}: <strong>{$plugin}</strong>";
                if ($PLUGININFO[$plugin]['version'] != '') echo " v".number_format($PLUGININFO[$plugin]['version'], 2);
                if ($PLUGININFO[$plugin]['author'] != '') echo " {$strby} {$PLUGININFO[$plugin]['author']}<br />";
                if ($PLUGININFO[$plugin]['legal'] != '') echo "{$PLUGININFO[$plugin]['legal']}<br />";
                echo "</p>";
            }
        }
}


echo "</div>";

echo "<br /><br /><br /><br />";






// echo "<table summary='by Ivan Lucas' align='center' width='65%'>\n";
// echo "<tr><td class='shade1' colspan='2'>{$strAbout} {$CONFIG['application_shortname']}&hellip;</td></tr>\n";
// echo "<tr><td class='shade2' colspan='2' style='text-align:center; padding-top: 10px;' >";
//background-image: url(images/sitting_man_logo64x64.png); ";
//echo "background-repeat: no-repeat; background-position: 1% bottom;'>";

//echo "<h2>{$CONFIG['application_name']}</h2>";
// Reenable when we have schema versions once again
// echo "{$strSchemaVersion}: ".database_schema_version()."</p><br />";
// echo "</td></tr>\n";
// echo "<tr><td class='shade1' colspan='2'>{$strCredits}:</td></tr>\n";

echo "<div id='creditcontent'>";
// echo print_r($credit,true);
foreach ($credit AS $c)
{
    echo "<h4>{$c['name']}</h4>";
    echo "<p>{$c['workedon']}</p>";
}
// echo "<pre>".print_r($credits,true)."</pre>";

?>
<h4>SiT! Support Incident Tracker</h4>
<p align='center'>
Copyright &copy; 2010-2011 <a href='http://sitracker.org'>The Support Incident Tracker Project</a><br />
Copyright &copy; 2000-2009 Salford Software Ltd. and Contributors<br />
Licensed under the GNU General Public License.<br /></p>

echo "</div>";

<?php
echo "</td></tr>";
echo "<tr><td class='shade1' colspan='2'>{$strLicense}:</td></tr>";
echo "<tr><td class='shade2' colspan='2'>";
echo "<textarea cols='100%' rows='10' readonly='readonly' style='background: transparent;'>";
$fp = fopen($CONFIG['licensefile'], "r");
$contents = htmlentities(fread($fp, filesize($CONFIG['licensefile'])), ENT_COMPAT, $i18ncharset);
fclose($fp);
echo $contents;
echo "</textarea></td></tr>\n";
echo "<tr><td class='shade1' colspan='2'>{$strReleaseNotes}:</td></tr>";
echo "<tr><td class='shade2' colspan='2'><p align='center'><a href='releasenotes.php'>{$strReleaseNotes}</a></p></td></tr>\n";
echo "<tr><td class='shade1' colspan='2'>{$strPlugins}:</td></tr>";
echo "<tr><td class='shade2' colspan='2'>";
if (is_array($CONFIG['plugins']) AND $CONFIG['plugins'][0] != '' AND count($CONFIG['plugins']) > 0)
{
    foreach ($CONFIG['plugins'] AS $plugin)
    {
        $plugin = trim($plugin);
        echo "<p><strong>{$plugin}</strong>";
        if ($PLUGININFO[$plugin]['version'] != '') echo " version ".number_format($PLUGININFO[$plugin]['version'], 2)."<br />";
        else echo "- <span class='error'>{$strFailed}</span><br />";

        if ($PLUGININFO[$plugin]['description'] != '') echo "{$PLUGININFO[$plugin]['description']}<br />";
        if ($PLUGININFO[$plugin]['author'] != '') echo "{$strAuthor}: {$PLUGININFO[$plugin]['author']}<br />";
        if ($PLUGININFO[$plugin]['legal'] != '') echo "{$PLUGININFO[$plugin]['legal']}<br />";

        if ($PLUGININFO[$plugin]['sitminversion'] > $application_version)
        {
            echo "<strong class='error'>This plugin was designed for {$CONFIG['application_name']} version {$PLUGININFO[$plugin]['sitminversion']} or later</strong><br />";
        }

        if (!empty($PLUGININFO[$plugin]['sitmaxversion']) AND $PLUGININFO[$plugin]['sitmaxversion'] < $application_version)
        {
            echo "<strong class='error'>This plugin was designed for {$CONFIG['application_name']} version {$PLUGININFO[$plugin]['sitmaxversion']} or earlier</strong><br />";
        }
        echo "</p>";
    }
}
else
{
    echo "<p>{$strNone}</p>";
}
echo "</td></tr>";
if ($CONFIG['kb_enabled'] == FALSE OR
    $CONFIG['portal_kb_enabled'] == FALSE OR
    $CONFIG['tasks_enabled'] == FALSE OR
    $CONFIG['calendar_enabled'] == FALSE OR
    $CONFIG['holidays_enabled'] == FALSE OR
    $CONFIG['feedback_enabled'] == FALSE OR
    $CONFIG['portal'] == FALSE)
{
    echo "<tr><td class='shade1' colspan='2'>{$strAdditionalInfo}:</td></tr>";
    echo "<tr><td class='shade2' colspan='2'>";
    if ($CONFIG['portal'] == FALSE) echo "<p>{$strPortal} - {$strDisabled}</p>";
    if ($CONFIG['kb_enabled'] == FALSE) echo "<p>{$strKnowledgeBase} - {$strDisabled}</p>";
    if ($CONFIG['portal'] == TRUE AND $CONFIG['portal_kb_enabled'] == FALSE) echo "<p>{$strKnowledgeBase} ({$strPortal}) - {$strDisabled}</p>";
    if ($CONFIG['tasks_enabled'] == FALSE) echo "<p>{$strTasks} - {$strDisabled}</p>";
    if ($CONFIG['calendar_enabled'] == FALSE) echo "<p>{$strCalendar} - {$strDisabled}</p>";
    if ($CONFIG['holidays_enabled'] == FALSE) echo "<p>{$strHolidays} - {$strDisabled}</p>";
    if ($CONFIG['feedback_enabled'] == FALSE) echo "<p>{$strFeedback} - {$strDisabled}</p>";
    echo "</td></tr>";

}
echo "</table>\n";

plugin_do('about');

echo "</div>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>
