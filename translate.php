<?php
// translate.php - A simple interface for aiding translation.
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Kieran Hogg <kieran[at]sitracker.org>
//          Ivan Lucas <ivan_lucas[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_NOT_REQUIRED; // not required
require (APPLICATION_LIBPATH . 'functions.inc.php');

require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strTranslate;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$tolang = cleanvar(clean_fspath($_REQUEST['lang']));
$fromlang = cleanvar(clean_fspath($_REQUEST['from']));

$mode = clean_fixed_list($_REQUEST['mode'], array('', 'show', 'save'));

if (empty($mode))
{
    // code fix for max_input_vars set to 1000 in php5.3.9
    $max_input_vars = ini_get('max_input_vars');
    $miv = 2500;
    if ($max_input_vars <= $miv)
    {
        $errors++;
        $max_iv_error = str_replace("{miv}", "= " . $miv,  $strMaxInputVars);
        $_SESSION['formerrors']['translateform']['max_input_vars'] = $max_iv_error;
    }
    echo show_form_errors('translateform');
    clear_form_errors('translateform');

    echo "<h2>{$strTranslation}</h2>";
    echo "<div align='center'><p>{$strHelpToTranslate}</p>";
    echo "<p>{$strChooseLanguage}</p>";
    echo "<form action='{$_SERVER['PHP_SELF']}' method='get'>";
    echo "<input name='mode' value='show' type='hidden' />";
    echo "<strong>{$strFrom}</strong>: ";
    echo "<select name='from'>";
    foreach ($i18n_codes AS $langcode => $language)
    {
        echo "<option value='{$langcode}'";
        if ($langcode == 'en-GB') echo " selected = 'selected' ";
        echo ">{$langcode} - {$language}</option>\n";
    }
    echo "</select> <strong>{$strTo}</strong>: ";
    echo "<select name='lang'>";
    foreach ($i18n_codes AS $langcode => $language)
    {
        if ($langcode != 'en-GB') echo "<option value='{$langcode}'>{$langcode} - {$language}</option>\n";
    }
    echo "</select>";
    echo "<br /><br />";
    echo "<label><input type='radio' name='showtranslated' value='showtranslated' checked='checked' /> {$strShowAll}</label> ";
    echo "<label><input type='radio' name='showtranslated' value='' /> {$strHideCompleted}</label>";
    echo "<br /><br />";
    echo "<input type='submit' value='$strTranslate' />";
    echo "</form></div>\n";
    $_SESSION['translation_fromvalues'] = '';
    $_SESSION['translation_foreignvalues'] = '';
}
elseif ($mode == "show")
{
    $from = cleanvar($_REQUEST['from']);
    if (!empty($_REQUEST['showtranslated']))
    {
        $showtranslated = TRUE;
    }
    else
    {
        $showtranslated = FALSE;
    }

    if (empty($_SESSION['translation_fromvalues']))
    {
        //open source language file
        $fromfile = clean_fspath(APPLICATION_I18NPATH . "{$from}.inc.php");
        $fh = fopen($fromfile, 'r');
        $theData = fread($fh, filesize($fromfile));
        fclose($fh);
        $lines = explode("\n", $theData);
        unset($theData);
        $langstrings[$from];
        $lastkey = '';
        $fromvalues = array();

        foreach ($lines as $values)
        {
            $badchars = array("$", "\"", "\\", "<?php", "?>");
            $values = trim(str_replace($badchars, '', $values));

            //get variable and value
            $vars = explode("=", $values);

            //remove spaces
            $vars[0] = trim($vars[0]);
            $vars[1] = trim($vars[1]);

            if (mb_substr($vars[0], 0, 3) == "str")
            {
                //remove leading and trailing quotation marks
                $vars[1] = substr_replace($vars[1], "",-2);
                $vars[1] = substr_replace($vars[1], "",0, 1);
                $fromvalues[$vars[0]] = $vars[1];
            }
            elseif (mb_substr($vars[0], 0, 2) == "# ")
            {
                $comments[$lastkey] = mb_substr($vars[0], 2, 1024);
            }
            else
            {
                if (mb_substr($values, 0, 4) == "lang")
                {
                    $languagestring = $values;
                }
            }
            $lastkey = $vars[0];
        }
        $origcount = count($fromvalues);
        unset($lines);
        $_SESSION['translation_fromvalues'] = $fromvalues;
    }
    else
    {
        $fromvalues = $_SESSION['translation_fromvalues'];
    }


    if (empty($_SESSION['translation_foreignvalues']))
    {
        //open foreign (destination) file
        $myFile = clean_fspath(APPLICATION_I18NPATH . "{$tolang}.inc.php");
        if (file_exists($myFile))
        {
            $foreignvalues = array();

            $fh = fopen($myFile, 'r');
            $theData = fread($fh, filesize($myFile));
            fclose($fh);
            $lines = explode("\n", $theData);
            unset($theData);
            //print_r($lines);
            $stringcheck = false;
            $stringcount = 0;
            $meta = array();
            foreach ($lines AS $introcomment)
            {
                if (mb_substr($introcomment, 0, 2) == "//")
                {
                    $meta[] = mb_substr($introcomment, 3);
                    $stringcheck = true;
                }
                elseif (($stringcount > 0) AND ($stringcount < 3) AND ($stringcheck == false))
                {
                    $meta[] = "SiT! Language File - {$i18n_codes[$tolang]} ($tolang) by {$_SESSION['realname']} <{$_SESSION['email']}>";
                }
                if (trim($introcomment) == '') break;
                $stringcount++;
            }
            foreach ($lines as $values)
            {
                $badchars = array("$", "\"", "\\", "<?php", "?>");
                $values = trim(str_replace($badchars, '', $values));
                if (mb_substr($values, 0, 3) == "str")
                {
                    $vars = explode("=", $values);
                    $vars[0] = trim($vars[0]);
                    $vars[1] = trim(substr_replace($vars[1], "", -2));
                    $vars[1] = substr_replace($vars[1], "", 0, 1);
                    $foreignvalues[$vars[0]] = $vars[1];
                }
                elseif (mb_substr($values, 0, 12) == "i18nAlphabet")
                {
                    $values = explode('=',$values);
                    $delims = array("'", ';');
                    $i18nalphabet = str_replace($delims,'',$values[1]);;
                }

            }
            $_SESSION['translation_foreignvalues'] = $foreignvalues;
        }
        else
        {

            if (!is_array($meta))
            {
                $meta = array();
            }
            $meta[] = "SiT! Language File - {$i18n_codes[$tolang]} ({$tolang}) by {$_SESSION['realname']} <{$_SESSION['email']}>";
        }
    }
    else
    {
        $foreignvalues = $_SESSION['translation_foreignvalues'];
    }

    echo "<h2>{$strWordList}</h2>";
    echo "<p align='center'>{$strTranslateTheString}<br/>";
    echo "<strong>{$strCharsToKeepWhenTranslating}</strong></p>";
    echo "<form method='post' action='{$_SERVER[PHP_SELF]}?mode=save'>";
    echo "<table align='center' style='table-layout:fixed'>";
    echo "<col width='33%'/><col width='33%'/><col width='33%'/>";
    echo "<tr class='shade1'><td colspan='3'>";
    if (is_array($meta))
    {
        foreach ($meta AS $metaline)
        {
            $metaline = htmlspecialchars($metaline);
            echo "<input type='text' name='meta[]' value=\"{$metaline}\" size='80' style='width: 100%;' /><br />";
        }
    }
    echo "</td></tr>";
    echo "<tr class='shade2'><td><code>i18nAlphabet</code></td>";
    echo "<td colspan='2'><input type='text' name='i18nalphabet' value=\"{$i18nalphabet}\" size='80' style='width: 100%;' /></td></tr>";
    echo "<tr><th>{$strVariable}</th><th>{$from}</th><th>{$tolang}</th></tr>";

    $shade = 'shade1';
    foreach (array_keys($fromvalues) as $key)
    {
        if ($showtranslated === TRUE OR ($showtranslated === FALSE AND empty($foreignvalues[$key]) === TRUE))
        {
            if ($tolang == 'zz') $foreignvalues[$key] = $key;
            echo "<tr class='$shade'><td><label for=\"{$key}\"><code>{$key}</code></label></td>";
            echo "<td><input name='english_{$key}' value=\"".htmlentities($fromvalues[$key], ENT_QUOTES, 'UTF-8')."\" size=\"45\" readonly='readonly' /></td>";

            echo "<td><input id=\"{$key}\" ";
            if (empty($foreignvalues[$key]))
            {
                echo "class='notice' onblur=\"if ($('{$key}').value != '') { $('{$key}').removeClassName('notice'); $('{$key}').addClassName('idle');} \" ";
            }
            echo "name=\"{$key}\" value=\"".htmlentities($foreignvalues[$key], ENT_QUOTES, 'UTF-8')."\" size=\"45\" />";
            if (empty($foreignvalues[$key]))
            {
                echo "<span style='color:red;'>*</span>";
            }
            echo "</td></tr>\n";
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
            if (!empty($comments[$key]))
            {
                echo "<tr><td colspan='3' class='{$shade}'><strong>{$strNotes}:</strong> {$comments[$key]}</td></tr>\n";
            }
        }
    }
    echo "</table>";
    echo "<input type='hidden' name='origcount' value='{$origcount}' />";
    echo "<input name='lang' value='{$tolang}' type='hidden' /><input name='mode' value='save' type='hidden' />";
    echo "<div class='formbuttons'>";
    if (is_writable($myFile))
    {
        echo "<input type='submit' value='{$strSave}' />";
    }
    else
    {
        echo "<input type='submit' value='{$strSave} / $strDisplay' />";
    }
    echo "</div>";

    echo "</form>\n";
}
elseif ($mode == "save")
{
    $badchars = array('.', '/', '\\');

    $lang = str_replace($badchars, '', $tolang);
    $origcount = clean_int($_REQUEST['origcount']);
    $i18nalphabet = cleanvar($_REQUEST['i18nalphabet'], TRUE, FALSE);
    $meta = cleanvar($_REQUEST['meta'], TRUE, FALSE);

    $filename = "{$lang}.inc.php";

    $i18nfile = '';
    $i18nfile .= "<?php\n";

    if (is_array($meta))
    {
        foreach ($_REQUEST['meta'] AS $meta)
        {
            $meta = cleanvar($meta);
            $i18nfile .= "// {$meta}\n";
        }
    }
    $i18nfile .= "\n";
    $i18nfile .= "\$languagestring = '{$i18n_codes[$lang]} ($lang)';\n";
    $i18nfile .= "\$i18ncharset = 'UTF-8';\n\n";

    $i18nfile .= "// List of letters of the alphabet for this language\n";
    $i18nfile .= "// in standard alphabetical order (upper case, where applicable). Leave blank to disable alphabetical indexing.\n";
    $i18nfile .= "\$i18nAlphabet = '{$i18nalphabet}';\n\n";


    $i18nfile .= "// list of strings (Alphabetical by key)\n";

    $lastchar = '';
    $translatedcount = 0;
    foreach (array_keys($_SESSION['translation_fromvalues']) as $key)
    {
        $key = clean_alphanumeric($key);
        if (!empty($_POST[$key]) AND substr($key, 0, 3) == "str")
        {
            if ($lastchar!='' AND substr($key, 3, 1) != $lastchar) $i18nfile .= "\n";
            $i18nfile .= "\${$key} = '".addslashes($_POST[$key])."';\n";
            $lastchar = substr($key, 3, 1);
            $translatedcount++;
        }
        elseif (!empty($_SESSION['translation_foreignvalues'][$key]))
        {
            if ($lastchar!='' AND substr($key, 3, 1) != $lastchar) $i18nfile .= "\n";
            $i18nfile .= "\${$key} = '".addslashes($_SESSION['translation_foreignvalues'][$key])."';\n";
            $lastchar = substr($key, 3, 1);
            $translatedcount++;
        }
    }
    $percent = number_format($translatedcount / $origcount * 100, 2);

    $i18nfile .= "?>\n";

    // CJ 02 Jun 11 - Unfortunately mailto has a restriction for attaching body text, so we cannot do that here
    echo "<p>".sprintf($strSendTranslation, "<code>{$filename}</code>", "<code>".APPLICATION_I18NPATH."</code>", "<a href='mailto:sit-translators@lists.sitracker.org?subject={$lang} translation&body={$percent} Percent Complete %0A%0A'>sitracker-devel-discuss@lists.sourceforge.net</a>")." </p>";
    echo "<p>{$strTranslation}: <strong>{$translatedcount}</strong>/{$origcount} = {$percent}% {$strComplete}.</p>";

    $myFile = APPLICATION_I18NPATH."{$filename}";
    $fp = @fopen($myFile, 'w');
    if (!$fp)
    {
        echo "<p class='warning'>".sprintf($strCannotWriteFile, "<code>{$myFile}</code>")."</p>";
    }
    else
    {
        fwrite($fp, $i18nfile);
        fclose($fp);
        echo "<p class='info'>".sprintf($strFileSavedAs, "<code>{$myFile}</code>")."</p>";
    }

    echo "<div style='margin-left: 5%; margin-right: 5%; background-color: white; border: 1px solid #ccc; padding: 1em;'>";
    highlight_string($i18nfile);
    echo "</div>";
}
else
{
    trigger_error('Invalid mode', E_USER_ERROR);
}
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>
