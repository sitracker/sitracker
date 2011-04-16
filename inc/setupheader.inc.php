<?php
// setupheader.inc.php - Header html to be included at the top of the setup page
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// This Page Is Valid XHTML 1.0 Transitional! 27Oct05
//
// Authors: Ivan Lucas <ivanlucas[at]users.sourceforge.net>,
//          Kieran Hogg <kieran[at]sitracker.org>

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n";
echo " \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n";
echo "<head>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=UTF-8\" />\n";
echo "<style type=\"text/css\">\n";
echo "body { background-color: #FFF; font-family: Tahoma, Helvetica, sans-serif; font-size: 10pt;}\n";
echo "h1,h2,h3,h4,h5 { color: #203894; padding: 0.1em; border: 1px solid #4D485B; }\n";
if (file_exists('./images/sitlogo_270x100.png')) echo "body {background-image: url('images/sitlogo_270x100.png'); background-attachment:fixed; background-position: 98% 98%; background-repeat: no-repeat;}\n";
echo "h4 {background-color: transparent; color: #000; border: 0px; margin: 2px 0px 3px 0px; }\n";
echo "div.configvar1 {background-color: #F7FAFF; border: 1px solid #4D485B; margin-bottom: 10px; padding: 0px 5px 10px 5px; filter:alpha(opacity=75);  opacity: 0.75;  -moz-opacity:0.75; -moz-border-radius: 3px;} ";
echo "div.configvar2 {background-color: green; border: 1px solid #4D485B; margin-bottom: 10px;} ";
echo ".error {background-position: 3px 2px;
  background-repeat: no-repeat;
  padding: 3px 3px 3px 22px;
  min-height: 16px;
  -moz-border-radius: 5px;
  /* display: inline; */
  border: 1px solid #000;
  margin-left: 2em;
  margin-right: 2em;
  width: auto;
  text-align: left;
    background-image: url('images/icons/sit/16x16/warning.png');
  color: #5A3612;
  border: 1px solid #A26120;
  background-color: #FFECD7;
}

.info {
 background-position: 3px 2px;
  background-repeat: no-repeat;
  padding: 3px 3px 3px 22px;
  min-height: 16px;
  -moz-border-radius: 5px;
  /* display: inline; */
  border: 1px solid #000;
  margin-left: 2em;
  margin-right: 2em;
  width: auto;
  text-align: left;
}
p.info {
  background-image: url('images/icons/sit/16x16/info.png');
  color: #17446B;
  border: 1px solid #17446B;
  background-color: #F4F6FF;
}

a.button:link, a.button:visited
{
  float: left;
  margin: 2px 5px 2px 5px;
  padding: 2px;
  width: 100px;
  border-top: 1px solid #ccc;
  border-bottom: 1px solid black;
  border-left: 1px solid #ccc;
  border-right: 1px solid black;
  background: #ccc;
  text-align: center;
  text-decoration: none;
  font: normal 10px Verdana;
  color: black;
}

a.button:hover
{
  background: #eee;
}

a.button:active
{
  border-bottom: 1px solid #eee;
  border-top: 1px solid black;
  border-right: 1px solid #eee;
  border-left: 1px solid black;
}

var { font-family: Andale Mono, monospace; font-style: normal; }
code.small { font-size: 75%; color: #555; }
}

";
echo ".help {background: #F7FAFF; border: 1px solid #3165CD; color: #203894; padding: 2px;}\n";
echo ".helptip { color: #203894; }\n";
echo ".warning {background: #FFFFE6; border: 2px solid #FFFF31; color: red; padding: 2px;}\n";
echo "pre {background:#FFF; border:#999; padding: 1em;}\n";
echo "a.button { border: 1px outset #000; padding: 2px; background-color: #EFEFEF;} ";
echo "a:link,a:visited { color: #000099; }\n";
echo "a:hover { background: #99CCFF; }\n";
echo "hr { background-color: #4D485B; margin-top: 3em; }\n";
echo "</style>\n";
echo "<title>Support Incident Tracker Setup</title>\n";
echo "</head>\n<body>\n";