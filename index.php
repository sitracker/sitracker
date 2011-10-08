<?php
// index.php - Welcome screen and login form
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// This Page Is Valid XHTML 1.0 Transitional! 31Oct05

if (!@include ('core.php'))
{
    $msg = urlencode(base64_encode("Could not find database connection/config information (core.php)"));
    header("Location: {$CONFIG['application_webpath']}setup.php?msg={$msg}");
    exit;
}

session_name($CONFIG['session_name']);
session_start();
include (APPLICATION_LIBPATH . 'strings.inc.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

if ($_SESSION['auth'] != TRUE)
{
    // External variables
    $id = clean_int($_REQUEST['id']);
    $page = clean_url($_REQUEST['page']);
    $page = str_replace(':','', $page);
    $page = str_replace('//','', $page);
    $page = str_replace('..','', $page);
    $page = strip_tags($page);
    $page = htmlentities($page, ENT_COMPAT, $GLOBALS['i18ncharset']);

    // Invalid user, show log in form
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<p class='error'>If you are not a developer, please under no circumstances run this version of SiT!, it is now destined for 4.0, at best it will be barely be useable, at worst it might injure you.</p>";

    if ($id == 1)
    {
        echo "<p class='error'>";
        echo sprintf($strEnterCredentials, $CONFIG['application_shortname']);
        echo "</p><br />";
    }

    if ($id == 2)
    {
        echo user_alert($strSessionExpired, E_USER_WARNING);
    }

    if ($id == 3)
    {
        echo user_alert($strInvalidCredentials, E_USER_ERROR);
    }

    // Language selector
    if (!empty($CONFIG['available_i18n']))
    {
        $available_languages = i18n_code_to_name($CONFIG['available_i18n']);
    }
    else
    {
        $available_languages = available_languages();
    }
    if (count($available_languages) == 1 AND array_key_exists($CONFIG['default_i18n'], $available_languages))
    {
        echo "<!-- Language: {$CONFIG['default_i18n']} -->";
    }
    else
    {
        $available_languages = array_merge(array('xx-xx'=>$strDefault),$available_languages);
        echo "<div id='languageselection'>";
        if ($CONFIG['i18n_selection'] == 'dropdown')
        {
            echo "<form id='langselectform' action='login.php' method='post'>";
            echo icon('language', 16, $strLanguage)." <label for='lang'>";
            echo "{$strLanguage}</label>:  ";

            if (!empty($_SESSION['lang'])) $setting = $_SESSION['lang'];
            else $setting = 'default';

            echo array_drop_down($available_languages, 'lang', $setting, "onchange='this.form.submit();'", TRUE);
            echo "</form>";
        }
        else
        {
            // Alternative language selection as an experiment, duplicating the drop-down at the moment,
            // but lets see if people like it - INL 3/9/2011
            echo "<br />";
            echo icon('language', 16, $strLanguage) . ' ';
            foreach ($available_languages AS $alangcode => $alang)
            {
                $operations[$alang] = "login.php?lang={$alangcode}";
            }
            echo html_action_links($operations);
            unset($operations);
        }
        echo "</div>";
    }
    plugin_do('index');
    echo "<div id='login' class='windowbox'>\n";
    echo "<div class='windowtitle'>{$CONFIG['application_shortname']} - ";
    echo "{$strLogin}</div>\n";
    echo "<div class='window'>\n";
    echo "<form id='loginform' action='login.php' method='post'>";
    echo "<label>{$strUsername}:<br /><input id='username' ";
    echo "name='username' size='28' type='text' /></label><br />";
    echo "<label>{$strPassword}:<br /><input id='password' ";
    echo "name='password' size='28' type='password' /></label><br />";
    echo "<input type='hidden' name='page' value='$page' />";
    echo "<input type='submit' value='{$strLogIn}' /><br />";
    echo "<br /><a href='forgotpwd.php'>{$strForgottenDetails}</a>";
    if ($CONFIG['kb_enabled'] AND $CONFIG['portal'] AND $CONFIG['portal_kb_enabled'] == 'Public')
    {
        echo "<br /><a href='portal/kb.php'>{$strKnowledgeBase}</a>";
    }
    echo "</form>\n";
    echo "</div>\n</div>\n";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    // User is validated, jump to main
    header("Location: main.php");
    exit;
}
?>