<?php
// about.php - Credit, Copyright and Licence page
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
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

echo "<div id='aboutsit'>";
echo "<img src='images/sitlogo_270x100.png' width='270' height='100' alt='SiT! Support Incident Tracker' />";
echo "<p align='center'>{$strVersion}: {$application_version} {$application_revision}";
if ($CONFIG['debug'] == TRUE) echo " (debug mode)";
echo "</p>";
debug_log("{$strVersion}: {$application_version} {$application_revision}", TRUE);

echo "<table summary='by Ivan Lucas' align='center' width='65%'>\n";
// echo "<tr><td class='shade1' colspan='2'>{$strAbout} {$CONFIG['application_shortname']}&hellip;</td></tr>\n";
// echo "<tr><td class='shade2' colspan='2' style='text-align:center; padding-top: 10px;' >";
//background-image: url(images/sitting_man_logo64x64.png); ";
//echo "background-repeat: no-repeat; background-position: 1% bottom;'>";

//echo "<h2>{$CONFIG['application_name']}</h2>";
// Reenable when we have schema versions once again
// echo "{$strSchemaVersion}: ".database_schema_version()."</p><br />";
// echo "</td></tr>\n";
echo "<tr><td class='shade1' colspan='2'>{$strCredits}:</td></tr>\n";
$fp = fopen($CONFIG['creditsfile'], "r");

while (!feof($fp))
{
    $line = trim(fgets($fp, 4096));
    if (substr($line, 0, 1) != '#' AND substr($line, 0, 1) != ' ' AND substr($line, 0, 1) != '') $credits[] = $line;
}

fclose($fp);
$creditcount = count($credits);
shuffle($credits);
$count = 1;
// TODO would be nice to scroll these credits using Javascript (degrading nicely)
echo "<tr><td class='shade2' colspan='2'><p align='center'>{$strManyThanks}</p><h4>";
foreach ($credits AS $credit)
{
    $creditpart = explode('--',$credit);
    $creditpart[0] = preg_replace("/\[at\]/", "@", $creditpart[0]);
    $creditpart[0] = trim(preg_replace("/(.*?)\s<(.*?)>/", "<a href = 'mailto:$2' style='color:#000;'>$1</a>", $creditpart[0]));
    $creditpart[1] = htmlentities(trim($creditpart[1]), ENT_COMPAT, $i18ncharset);
    echo "{$creditpart[0]} <span style='font-size: 70%; font-weight: normal;'>({$creditpart[1]})</span>";
    $count++;
    if ($count <= $creditcount) echo ", ";
}
echo "</h4></td></tr>\n";
echo "<tr><td class='shade1' colspan='2'>{$strLicenseAndCopyright}:</td></tr>";
echo "<tr><td class='shade2' colspan='2'>";
?>
<h4>SiT! Support Incident Tracker</h4>
<p align='center'>
Copyright &copy; 2010 <a href='http://sitracker.org'>The Support Incident Tracker Project</a><br />
Copyright &copy; 2000-2009 Salford Software Ltd. and Contributors<br />
Licensed under the GNU General Public License.<br /></p>

<p align='center'>Incorporating:</p>

<p align='center'>KDEClassic Icon theme<br />
Completely free for commercial and non-commercial use.</p>

<p align='center'>whatever:hover (csshover.htc) 1.41 by <a href='http://www.xs4all.nl/~peterned/'>Peter Nederlof</a><br />
&copy; 2005 - Peter Nederlof.  Licensed under the LGPLv2.</p>

<p align='center'><a href='https://sourceforge.net/projects/nusoap/'>NuSOAP</a> 0.7.3 by <a href='http://www.nusphere.com/'>NuSphere Corporation</a><br />
Copyright &copy; 2002 NuSphere Corporation. Licensed under the LGPL v2.</p>

<p align='center'>MagpieRSS 0.72 by <a href='http://magpierss.sourceforge.net/'>Kellan Elliott-McCrea</a><br />
Copyright &copy; Kellan Elliott-McCrea. Licensed under the GPL v2.</p>

<p align='center'>Prototype JavaScript framework 1.6.0.3 by <a href='http://www.prototypejs.org/'>Sam Stephenson</a><br />
Copyright &copy; 2005-2008 Sam Stephenson. Licensed under an MIT style license.</p>

<p align='center'>script.aculo.us by <a href='http://script.aculo.us'>Thomas Fuchs</a><br />
Copyright &copy; 2005-2007 Thomas Fuchs. Licensed under an MIT style license.</p>

<p align='center'>Icons from the Crystal Project by <a href='http://www.everaldo.com/'>Everaldo Coelho</a><br />
Copyright &copy;  2006-2007 Everaldo Coelho. Licensed under the LGPLv2</p>

<p align='center'>Icons from the <a href='http://www.oxygen-icons.org/'>Oxygen Project</a><br />
Copyright &copy; 2008 The Oxygen Project. Licensed under the LGPLv2</p>

</td></tr>
<?php
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
if (is_array($CONFIG['plugins']) AND count($CONFIG['plugins']) >= 1)
{
    foreach ($CONFIG['plugins'] AS $plugin)
    {
        echo "<p><strong>$plugin</strong>";
        if ($PLUGININFO[$plugin]['version'] != '') echo " version ".number_format($PLUGININFO[$plugin]['version'], 2)."<br />";
        else echo "<br />";

        if ($PLUGININFO[$plugin]['description'] != '') echo "{$PLUGININFO[$plugin]['description']}<br />";
        if ($PLUGININFO[$plugin]['author'] != '') echo "{$strAuthor}: {$PLUGININFO[$plugin]['author']}<br />";
        if ($PLUGININFO[$plugin]['legal'] != '') echo "{$PLUGININFO[$plugin]['legal']}<br />";
        if ($PLUGININFO[$plugin]['sitminversion'] > $application_version) echo "<strong class='error'>This plugin was designed for {$CONFIG['application_name']} version {$PLUGININFO[$plugin]['sitminversion']} or later</strong><br />";
        if (!empty($PLUGININFO[$plugin]['sitmaxversion']) AND $PLUGININFO[$plugin]['sitmaxversion'] < $application_version) echo "<strong class='error'>This plugin was designed for {$CONFIG['application_name']} version {$PLUGININFO[$plugin]['sitmaxversion']} or earlier</strong><br />";
        echo "</p>";
    }
}
else echo "<p>{$strNone}</p>";
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
