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


$permission = PERM_NOT_REQUIRED; 

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strAbout;
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$about_tabs = array('about', 'thanks', 'licence');

// External variables
$seltab = clean_fixed_list($_REQUEST['tab'], $about_tabs);

echo "<div id='aboutsit'>";
echo "<img src='images/sitlogo_270x100.png' width='270' height='100' alt='SiT! Support Incident Tracker' />";
echo "<br />";
echo "<br />";
$TABI18n['about'] = $strAbout;
$TABI18n['thanks'] = $strThanksTo;
$TABI18n['licence'] = $strLicense;

foreach ($about_tabs AS $tab)
{
    $tabs[$TABI18n[$tab]] = "{$_SERVER['PHP_SELF']}?tab={$tab}";
}

echo draw_tabs($tabs, $seltab);
echo "<div id='aboutbox'>";
switch ($seltab)
{
    // TODO in future we could list current authors separately
    //     case 'authors':
    //         echo "<h2>{$strAuthors}</h2>";
    //         break;

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
        // shuffle($creditlines);
        $count = 0;
        $creditperson = array();
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

        echo "<div id='aboutcreditcontent'>";
        // echo print_r($credit,true);
        echo "<p>";
        foreach ($credit AS $c)
        {
            echo "<strong>{$c['name']}</strong> &mdash; {$c['workedon']}<br />";
        }
        echo "</p>";

        echo "<h4>$strIncorporating:</h4>
            <p>whatever:hover (csshover.htc) 1.41 by <a href='http://www.xs4all.nl/~peterned/'>Peter Nederlof</a><br />
            &copy; 2005 - Peter Nederlof.  Licensed under the LGPLv2.</p>

            <p><a href='https://sourceforge.net/projects/nusoap/'>NuSOAP</a> 0.7.3 by <a href='http://www.nusphere.com/'>NuSphere Corporation</a><br />
            Copyright &copy; 2002 NuSphere Corporation. Licensed under the LGPL v2.</p>

            <p>MagpieRSS 0.72 by <a href='http://magpierss.sourceforge.net/'>Kellan Elliott-McCrea</a><br />
            Copyright &copy; Kellan Elliott-McCrea. Licensed under the GPL v2.</p>

            <p>pChart 2.1.0 from the <a href='http://www.pchart.net'>pChart project</a>.   Licensed under GPLv3</p>

            <p>Prototype JavaScript framework 1.7 by <a href='http://www.prototypejs.org/'>Sam Stephenson</a><br />
            Copyright &copy; 2005-2010 Sam Stephenson. Licensed under an MIT style license.</p>

            <p>script.aculo.us 1.9.0 by <a href='http://script.aculo.us'>Thomas Fuchs</a><br />
            Copyright &copy; 2005-2010 Thomas Fuchs. Licensed under an MIT style license.</p>

            <p>Icons from the Crystal Project by <a href='http://www.everaldo.com/'>Everaldo Coelho</a><br />
            Copyright &copy;  2006-2007 Everaldo Coelho. Licensed under the LGPLv2</p>

            <p>Icons from the <a href='http://www.oxygen-icons.org/'>Oxygen Project</a><br />
            Copyright &copy; 2008 The Oxygen Project. Licensed under the LGPLv2</p>

            <p>MIME parser class 1.80 by <a href='http://www.phpclasses.org/package/3169-PHP-Decode-MIME-e-mail-messages.html'>Manuel Lemos</a><br />
            Copyright &copy; 2006-2008 Manuel Lemos. Licensed under the BSD License</p>";
        break;

    case 'licence':
        $fp = fopen($CONFIG['licensefile'], "r");
        $contents = htmlentities(fread($fp, filesize($CONFIG['licensefile'])), ENT_COMPAT, $i18ncharset);
        fclose($fp);
        echo "<h2>{$strLicense}</h2><div id='aboutcontent'>";
        echo $contents;
        echo "</div>";
        break;

    case 'about':
    default:
        echo "<h2>SiT! Support Incident Tracker</h2>";

        echo "<p>{$strVersion}: {$application_version} {$application_revision}";
        if ($CONFIG['debug'] == TRUE) echo " (debug mode)";
        echo "</p>";
        debug_log("{$strVersion}: {$application_version} {$application_revision}", TRUE);

        echo "<p>Copyright &copy; 2010-2011 <a href='http://sitracker.org'>The Support Incident Tracker Project</a><br />";
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
echo "</div>";

plugin_do('about');

echo "</div>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>