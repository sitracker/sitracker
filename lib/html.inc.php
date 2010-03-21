<?php
// html.inc.php - functions that return generic HTML elements, e.g. input boxes
//                or convert plain text to HTML ...
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

/**
  * Generate HTML for a redirect/confirmation page
  * @author Ivan Lucas
  * @param string $url. URL to redirect to
  * @param bool $success. (optional) TRUE = Success, FALSE = Failure
  * @param string $message. (optional) HTML message to display on the page
  *               before redirection.
  *               This parameter is optional and only required if the default
  *               success/failure will not suffice
  * @returns string HTML page with redirect
  * @note Replaces confirmation_page() from versions prior to 3.35
  *       If a header HTML has already been displayed a continue link is printed
  *       a meta redirect will also be inserted, which is invalid HTML but appears
  *       to work in most browswers.
  *
  * @note The recommended way to use this function is to call it without headers/footers
  *       already displayed.
*/
function html_redirect($url, $success = TRUE, $message='')
{
    global $CONFIG, $headerdisplayed, $siterrors;

    if (!empty($_REQUEST['dashboard']))
    {
        $headerdisplayed = TRUE;
    }

    if (empty($message))
    {
        $refreshtime = 1;
    }
    elseif ($success == FALSE)
    {
        $refreshtime = 3;
    }
    else
    {
        $refreshtime = 6;
    }

    // Catch all, make refresh time slow if errors are detected
    if ($siterrors > 0)
    {
        $refreshtime = 10;
    }

    $refresh = "{$refreshtime}; url={$url}";

    $title = $GLOBALS['strPleaseWaitRedirect'];
    if (!$headerdisplayed)
    {
        if ($_SESSION['portalauth'] == TRUE)
        {
            include (APPLICATION_INCPATH . 'portalheader.inc.php');
        }
        else
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        }
    }
    else
    {
        echo "<meta http-equiv=\"refresh\" content=\"$refreshtime; url=$url\" />\n";
    }

    echo "<h3>";
    if ($success)
    {
        echo "<span class='success'>{$GLOBALS['strSuccess']}</span>";
    }
    else
    {
        echo "<span class='failure'>{$GLOBALS['strFailed']}</span>";
    }

    if (!empty($message))
    {
        echo ": {$message}";
    }

    echo "</h3>";
    if (empty($_REQUEST['dashboard']))
    {
        echo "<h4>{$GLOBALS['strPleaseWaitRedirect']}</h4>";
        if ($headerdisplayed)
        {
            echo "<p align='center'><a href=\"{$url}\">{$GLOBALS['strContinue']}</a></p>";
        }
    }
    // TODO 3.35 Add a link to refresh the dashlet if this is run inside a dashlet

    if ($headerdisplayed)
    {
        if ($_SESSION['portalauth'] == TRUE)
        {
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
        else
        {
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        }
    }
}


/**
  * Returns a HTML string for a checkbox
  * @author Ivan Lucas
  * @param string $name The HTML name attribute
  * @param mixed $state
  * @param string $value. (optional) Value, state is used if blank
  * @param string $attributes. (optional) Extra attributes for input tag
  * @note the 'state' value should be a 1, yes, true or 0, no, false
  * @returns string HTML
*/
function html_checkbox($name, $state, $value ='', $attributes = '')
{

    if ($state === TRUE) $state = 'TRUE';
    if ($state === FALSE) $state = 'FALSE';
    if ($state === 1 OR $state === 'Yes' OR $state === 'yes' OR
        $state === 'true' OR $state === 'TRUE')
    {
        if ($value == '') $value = $state;
        $html = "<input type='checkbox' checked='checked' name='{$name}' id='{$name}' value='{$value}' {$attributes} />" ;
    }
    else
    {
        if ($value == '') $value = $state;
        $html = "<input type='checkbox' name='{$name}' id='{$name}' value='{$value}' {$attributes} />" ;
    }
//     $html .= "(state:$state)";
    return $html;
}


/**
  * Returns HTML for a gravatar (Globally recognised avatar)
  * @author Ivan Lucas
  * @param string $email - Email address
  * @param int $size - Size in pixels (Default 32)
  * @param bool $hyperlink - Make a link back to gravatar.com, default TRUE
  * @returns string - HTML img tag
  * @note See http://en.gravatar.com/site/implement/ for implementation guide
 */
function gravatar($email, $size = 32, $hyperlink = TRUE)
{
    global $CONFIG, $iconset;
    $default = $CONFIG['default_gravatar'];

    if (isset( $_SERVER['HTTPS']) && (strtolower( $_SERVER['HTTPS'] ) != 'off' ))
    {
        // Secure
        $grav_url = "https://secure.gravatar.com";
    }
    else
    {
        $grav_url = "http://www.gravatar.com";
    }
    $grav_url .= "/avatar.php?";
    $grav_url .= "gravatar_id=".md5(strtolower($email));
    $grav_url .= "&amp;default=".urlencode($CONFIG['default_gravatar']);
    $grav_url .= "&amp;size=".$size;
    $grav_url .= "&amp;rating=G";

    if ($hyperlink) $html = "<a href='http://site.gravatar.com/'>";
    $html .= "<img src='{$grav_url}' width='{$size}' height='{$size}' alt='' />";
    if ($hyperlink) $html .= "</a>";

    return $html;
}


/**
  * Produces HTML for a percentage indicator
  * @author Ivan Lucas
  * @param int $percent. Number between 0 and 100
  * @returns string HTML
*/
function percent_bar($percent)
{
    if ($percent == '') $percent = 0;
    if ($percent < 0) $percent = 0;
    if ($percent > 100) $percent = 100;
    // #B4D6B4;
    $html = "<div class='percentcontainer'>";
    $html .= "<div class='percentbar' style='width: {$percent}%;'>  {$percent}&#037;";
    $html .= "</div></div>\n";
    return $html;
}

/**
  * Return HTML for a table column header (th and /th) with links for sorting
  * Filter parameter can be an assocative array containing fieldnames and values
  * to pass on the url for data filtering purposes
  * @author Ivan Lucas
  * @param string $colname. Column name
  * @param string $coltitle. Column title (to display in the table header)
  * @param bool $sort Whether to sort the column
  * @param string $order ASC or DESC
  * @param array $filter assoc. array of variables to pass on the link url
  * @param string $defaultorder The order to display by default (a = ASC, d = DESC)
  * @param string $width cell width
  * @returns string HTML
*/
function colheader($colname, $coltitle, $sort = FALSE, $order='', $filter='', $defaultorder='a', $width='')
{
    global $CONFIG;
    if ($width !=  '')
    {
        $html = "<th width='".intval($width)."%'>";
    }
    else
    {
        $html = "<th>";
    }

    $qsappend='';
    if (!empty($filter) AND is_array($filter))
    {
        foreach ($filter AS $key => $var)
        {
            if ($var != '') $qsappend .= "&amp;{$key}=".urlencode($var);
        }
    }
    else
    {
        $qsappend='';
    }

    if ($sort==$colname)
    {
        //if ($order=='') $order=$defaultorder;
        if ($order=='a')
        {
            $html .= "<a href='{$_SERVER['PHP_SELF']}?sort=$colname&amp;order=d{$qsappend}'>{$coltitle}</a> ";
            $html .= "<img src='{$CONFIG['application_webpath']}images/sort_a.png' width='5' height='5' alt='{$GLOBALS['strSortAscending']}' /> ";
        }
        else
        {
            $html .= "<a href='{$_SERVER['PHP_SELF']}?sort=$colname&amp;order=a{$qsappend}'>{$coltitle}</a> ";
            $html .= "<img src='{$CONFIG['application_webpath']}images/sort_d.png' width='5' height='5' alt='{$GLOBALS['strSortDescending']}' /> ";
        }
    }
    else
    {
        if ($sort === FALSE) $html .= "{$coltitle}";
        else $html .= "<a href='{$_SERVER['PHP_SELF']}?sort=$colname&amp;order={$defaultorder}{$qsappend}'>{$coltitle}</a> ";
    }
    $html .= "</th>";
    return $html;
}


/**
  * Takes an array and makes an HTML selection box
  * @author Ivan Lucas
  * @param array $array - The array of options to display in the drop-down
  * @param string $name - The HTML name attribute (also used for id)
  * @param mixed $setting - The value to pre-select
  * @param string $attributes - Extra attributes for the select tag
  * @param mixed $usekey - (optional) Set the option value to be the array key instead
  *                        of the array value.
  *                        When TRUE the array key will be used as the option value
  *                        When FALSE the array value will be usedoption value
  *                        When NULL the function detects which is most appropriate
  * @param bool $multi - When TRUE a multiple selection box is returned and $setting
  *                      can be an array of values to pre-select
  * @retval string HTML select element
*/
function array_drop_down($array, $name, $setting='', $attributes='', $usekey = NULL, $multi = FALSE)
{
    if ($multi AND substr($name, -2) != '[]') $name .= '[]';
    $html = "<select name='$name' id='$name' ";
    if (!empty($attributes))
    {
         $html .= "$attributes ";
    }
    if ($multi)
    {
        $items = count($array);
        if ($items > 5) $size = floor($items / 3);
        if ($size > 10) $size = 10;
        $html .= "multiple='multiple' size='$size' ";
    }
    $html .= ">\n";

    if ($usekey === '')
    {
        if ((array_key_exists($setting, $array) AND
            in_array((string)$setting, $array) == FALSE) OR
            $usekey == TRUE)
        {
            $usekey = TRUE;
        }
        else
        {
            $usekey = FALSE;
        }
    }

    foreach ($array AS $key => $value)
    {
        $value = htmlentities($value, ENT_COMPAT, $GLOBALS['i18ncharset']);
        if ($usekey)
        {
            $html .= "<option value='$key'";
            if ($multi === TRUE AND is_array($setting))
            {
                if (in_array($key, $setting))
                {
                    $html .= " selected='selected'";
                }
            }
            elseif ($key == $setting)
            {
                $html .= " selected='selected'";
            }

        }
        else
        {
            $html .= "<option value='$value'";
            if ($multi === TRUE AND is_array($setting))
            {
                if (in_array($value, $setting))
                {
                    $html .= " selected='selected'";
                }
            }
            elseif ($value == $setting)
            {
                $html .= " selected='selected'";
            }
        }

        $html .= ">{$value}</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
  * Prints a user alert message, these are errors caused by users
  * that can be corrected by users, as opposed to system errors that should
  * use trigger_error() instead
  * @author Ivan Lucas
  * @param string $message The message to display
  * @param int severity. Same as php error constants so
  *                      E_USER_ERROR / 256
  *                      E_USER_WARNING / 512
  *                      E_USER_NOTICE / 1024
  * @param string $helpcontext (optional) - will display a help link. [?]
  *              to the given help context
  * @returns string HTML
  * @note user_alert message should be displayed in the users local language
  * and should offer a 'next step' or help, where appropriate
  *
  *  E_USER_NOTICE would indicate pure information, nothing is wrong
  *  E_USER_WARNING would indicate that something is wrong, but nothing needs correcting
  *  E_USER_ERROR would indicate that something is wrong and needs to be corrected
  *               (not a system problem though!)
  *
*/
function user_alert($message, $severity, $helpcontext = '')
{
    switch ($severity)
    {
        case E_USER_ERROR:
            $class = 'alert error';
            $info = $GLOBALS['strError'];
        break;

        case E_USER_WARNING:
            $class = 'alert warning';
            $info = $GLOBALS['strWarning'];
        break;

        case E_USER_NOTICE:
        default:
            $class = 'alert info';
            $info = $GLOBALS['strInfo'];
    }
    $html = "<p class='{$class}'>";
    if (!empty($helpcontext)) $html .= help_link($helpcontext);
    $html .= "<strong>{$info}</strong>: {$message}";
    $html .= "</p>";

    return $html;
}


/**
  * Output the html for an icon
  *
  * @param string $filename filename of the string, minus extension, we assume .png
  * @param int $size size of the icon, from: 12, 16, 32
  * @param string $alt alt text of the icon (optional)
  * @param string $title (optional)
  * @param string $id ID attribute (optional)
  * @return string $html icon html
  * @author Kieran Hogg, Ivan Lucas
*/
function icon($filename, $size='', $alt='', $title='', $id='')
{
    global $iconset, $CONFIG;

    if (empty($iconset)) $iconset = $_SESSION['userconfig']['iconset'];
    $sizes = array(12, 16, 32);

    if (!in_array($size, $sizes) OR empty($size))
    {
        trigger_error("Incorrect image size for '{$filename}.png' ", E_USER_WARNING);
        $size = 16;
    }

    $file = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR."images/icons/{$iconset}";
    $file .= "/{$size}x{$size}/{$filename}.png";

    $urlpath = "{$CONFIG['application_webpath']}images/icons/{$iconset}";
    $urlpath .= "/{$size}x{$size}/{$filename}.png";

    if (!file_exists($file))
    {
        $alt = "Missing icon: '$filename.png', ($file) size {$size}";
        if ($CONFIG['debug']) trigger_error($alt, E_USER_WARNING);
        $urlpath = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR."images/icons/sit";
        $urlpath .= "/16x16/blank.png";
    }
    $icon = "<img src=\"{$urlpath}\"";
    if (!empty($alt))
    {
        $icon .= " alt=\"{$alt}\" ";
    }
    else
    {
        $alt = $filename;
        $icon .= " alt=\"{$alt}\" ";
    }
    if (!empty($title))
    {
        $icon .= " title=\"{$title}\"";
    }
    else
    {
        $icon .= " title=\"{$alt}\"";
    }

    if (!empty($id))
    {
        $icon .= " id=\"{$id}\"";
    }
    
    $icon .= " width=\"{$size}\" height=\"{$size}\" ";
    
    $icon .= " />";

    return $icon;
}


/**
  * Uses calendar.js to make a popup date picker
  * @author Ivan Lucas
  * @param string $formelement. form element id, eg. myform.dateinputbox
  * @returns string HTML
*/
function date_picker($formelement)
{
    global $CONFIG, $iconset;

    $divid = "datediv".str_replace('.','',$formelement);
    $html = "<img src='{$CONFIG['application_webpath']}images/icons/{$iconset}/16x16/pickdate.png' ";
    $html .= "onmouseup=\"toggleDatePicker('$divid','$formelement')\" width='16' height='16' alt='date picker' style='cursor: pointer; vertical-align: bottom;' />";
    $html .= "\n<div id='$divid' style='position: absolute;'></div>\n";
    return $html;
}


/**
  * Uses scriptaculous and AutoComplete.js to make a form text input
  * box autocomplete
  * @author Ivan Lucas
  * @param string $formelement. form element id, eg. textinput
  * @param string $action. ajaxdata.php action to return JSON data
  * @returns string HTML javascript block
  * @note The page that calls this function MUST include the required
  * javascript libraries. e.g.
  *   $pagescripts = array('scriptaculous/scriptaculous.js','AutoComplete.js');
*/
function autocomplete($formelement, $action = 'autocomplete_sitecontact')
{
    $html .= "<script type=\"text/javascript\">\n//<![CDATA[\n";
    // Disable browser autocomplete (it clashes)
    $html .= "$('$formelement').setAttribute(\"autocomplete\", \"off\"); \n";
    $html .= "new AutoComplete('{$formelement}', 'ajaxdata.php?action={$action}&s=', {\n";
    $html .= "delay: 0.25,\n";
    $html .= "resultFormat: AutoComplete.Options.RESULT_FORMAT_JSON\n";
    $html .= "}); \n//]]>\n</script>\n";

    return $html;
}


/**
  * Uses prototype.js and FormProtector.js to prevent navigating away from
  * an unsubmitted form
  * @author Ivan Lucas
  * @param string $formelement. form element id
  * @param string $message. (optional) Message to display in the warning popup
  * @returns string HTML javascript block
  * @note The page that calls this function MUST include the required
  * javascript libraries. e.g.
  *   $pagescripts = array('FormProtector.js);
*/
function protectform($formelement, $message = '')
{
    global $strRememberToSave;
    if (empty($message)) $message = $strRememberToSave;
    $html = "\n<script type='text/javascript'>\n";
    $html .= "  var fp = new FormProtector('$formelement');\n";
    $html .= "  fp.setMessage('{$message}');\n";
    $html .= "</script>\n";

    return $html;
}
?>