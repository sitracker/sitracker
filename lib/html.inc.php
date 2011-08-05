<?php
// html.inc.php - functions that return generic HTML elements, e.g. input boxes
//                or convert plain text to HTML ...
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
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
 * @param bool $close. Will close a window with javascript when TRUE
 * @return string HTML page with redirect
 * @note Replaces confirmation_page() from versions prior to 3.35
 *       If a header HTML has already been displayed a continue link is printed
 *       a meta redirect will also be inserted, which is invalid HTML but appears
 *       to work in most browswers.
 *
 * @note The recommended way to use this function is to call it without headers/footers
 *       already displayed.
 */
function html_redirect($url, $success = TRUE, $message = '', $close = FALSE)
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

    if ($close === FALSE)
    {
        $refresh = "{$refreshtime}; url={$url}";
    }

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

    if ($close)
    {
        if ($_SESSION['userconfig']['show_confirmation_close_window'] == 'TRUE')
        {
            ?>
            <script type='text/javascript'>
            //<![CDATA[

            if (window.confirm(strEmailSentSuccessfullyConfirmWindowClosure))
            {
                close_page_redirect('<?php echo $url; ?>');
            }

            //]]>
            </script>
            <?php
        }
        else
        {
            // We  use a PeriodicalExecutor as we don't want to close the window instantly, we want users to be able to read the message
            ?>
            <script type='text/javascript'>
            //<![CDATA[

            new PeriodicalExecuter(function(pe) {
                                            window.close();
                                        },
                                        <?php echo $refreshtime ?>);

            //]]>
            </script>
            <?php
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
 * @return string HTML
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
 * @return string - HTML img tag
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
    $html .= "<img src='{$grav_url}' width='{$size}' height='{$size}' alt='' ";
    $html .= "class='gravatar' />";
    if ($hyperlink) $html .= "</a>";

    return $html;
}


/**
 * Produces HTML for a percentage indicator
 * @author Ivan Lucas
 * @param int $percent. Number between 0 and 100
 * @return string HTML
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
 * @param string $sort Sorts this column when set to the name of the column.
 * @param string $order ASC or DESC
 * @param array $filter assoc. array of variables to pass on the link url
 * @param string $defaultorder The order to display by default (a = ASC, d = DESC)
 * @param string $width cell width
 * @return string HTML
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
        $qsappend = '';
    }

    if ($sort == $colname)
    {
        if ($order == 'a')
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
 * @return string HTML
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
    //<strong>{$info}</strong>:
    $html .= "{$message}";
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
        $alt = "Missing icon: '{$filename}.png', ({$file}) size {$size}";
        if ($CONFIG['debug']) trigger_error($alt, E_USER_WARNING);
        $urlpath = "{$CONFIG['application_webpath']}/images/icons/sit";
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
 * @return string HTML
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
 * @return string HTML javascript block
 * @note The page that calls this function MUST include the required
 * javascript libraries. e.g.
 *   $pagescripts = array('AutoComplete.js');
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
 * @return string HTML javascript block
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


/**
 * A HTML Form and Select listbox for user groups, with javascript to reload page
 * @param int $selected. Group ID to preselect
 * @param string $urlargs. (Optional) text to pass after the '?' in the url (parameters)
 * @return int Number of groups found
 * @note outputs a HTML form directly
 */
function group_selector($selected, $urlargs='')
{
    $gsql = "SELECT * FROM `{$GLOBALS['dbGroups']}` ORDER BY name";
    $gresult = mysql_query($gsql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    while ($group = mysql_fetch_object($gresult))
    {
        $grouparr[$group->id] = $group->name;
    }
    $numgroups = mysql_num_rows($gresult);

    if (!empty($urlargs)) $urlargs = "&amp;{$urlargs}";
    if ($numgroups >= 1)
    {
        echo "<form action='{$_SERVER['PHP_SELF']}?{$urlargs}' class='filterform' method='get'>";
        echo "{$GLOBALS['strGroup']}: <select name='choosegroup' onchange='window.location.href=this.options[this.selectedIndex].value'>";
        echo "<option value='{$_SERVER['PHP_SELF']}?gid=all{$urlargs}'";
        if ($selected == 'all') echo " selected='selected'";
        echo ">{$GLOBALS['strAll']}</option>\n";
        echo "<option value='{$_SERVER['PHP_SELF']}?gid=allonline{$urlargs}'";
        if ($selected == 'allonline') echo " selected='selected'";
        echo ">{$GLOBALS['strAllOnline']}</option>\n";
        foreach ($grouparr AS $groupid => $groupname)
        {
            echo "<option value='{$_SERVER['PHP_SELF']}?gid={$groupid}{$urlargs}'";
            if ($groupid == $selected) echo " selected='selected'";
            echo ">{$groupname}</option>\n";
        }
        echo "<option value='{$_SERVER['PHP_SELF']}?gid=0{$urlargs}'";
        if ($selected === '0') echo " selected='selected'";
        echo ">{$GLOBALS['strUsersNoGroup']}</option>\n";
        echo "</select>\n";
        echo "</form>\n";
    }

    return $numgroups;
}


/**
 * Creates HTML for a tabbed interface
 * @author Ivan Lucas
 * @param array $tabsarray
 * @param string $selected (optional)
 * @param string $divclass (optional)
 * @return string HTML
 */
function draw_tabs($tabsarray, $selected='', $divclass='tabcontainer')
{
    if ($selected == '') $selected = key($tabsarray);
    $html .= "<div class='{$divclass}'>";
    $html .= "<ul>";
    foreach ($tabsarray AS $tab => $url)
    {
        $html .= "<li";
        if (strtolower($tab) == strtolower($selected))
        {
            $html .= " class='active'";
        }
        $html .= ">";
        $tab = str_replace('_', ' ', $tab);
        $html .= "<a href='{$url}'>$tab</a></li>\n";
    }
    $html .= "</ul>";
    $html .= "</div>";

    return $html;
}


/**
 * Converts BBcode to HTML
 * @author Paul Heaney
 * @param string $text. Text with BBCode
 * @return string HTML
 */
function bbcode($text)
{
    global $CONFIG;
    $bbcode_regex = array(0 => "/\[b\](.*?)\[\/b\]/s",
                        1 => "/\[i\](.*?)\[\/i\]/s",
                        2 => "/\[u\](.*?)\[\/u\]/s",
                        3 => "/\[quote\](.*?)\[\/quote\]/s",
                        4 => "/\[size=(.+?)\](.+?)\[\/size\]/is",
                        5 => "/\[url\](.*?)\[\/url\]/s",
                        6 => "/\[size=(.+?)\](.+?)\[\/size\]/is",
                        7 => "/\[img\](.*?)\[\/img\]/s",
                        8 => "/\[size=(.+?)\](.+?)\[\/size\]/is",
                        9 => "/\[color\](.*?)\[\/color\]/s",
                        10 => "/\[size=(.+?)\](.+?)\[\/size\]/is",
                        11 => "/\[size\](.*?)\[\/size\]/s",
                        12 => "/\[code\](.*?)\[\/code\]/s",
                        13 => "/\[hr\]/s",
                        14 => "/\[s\](.*?)\[\/s\]/s",
                        15 => "/\[\[att\=(.*?)]](.*?)\[\[\/att]]/s",
                        16 => "/\[url=(.+?)\](.+?)\[\/url\]/is");

    $bbcode_replace = array(0 => "<strong>$1</strong>",
                            1 => "<em>$1</em>",
                            2 => "<u>$1</u>",
                            3 => "<blockquote><p>$1</p></blockquote>",
                            4 => "<blockquote cite=\"$1\"><p>$1 said:<br />$2</p></blockquote>",
                            5 => '<a href="$1" title="$1">$1</a>',
                            6 => "<a href=\"$1\" title=\"$1\">$2</a>",
                            7 => "<img src=\"$1\" alt=\"User submitted image\" />",
                            8 => "<span style=\"color:$1\">$2</span>",
                            9 => "<span style=\"color:red;\">$1</span>",
                            10 => "<span style=\"font-size:$1\">$2</span>",
                            11 => "<span style=\"font-size:large\">$1</span>",
                            12 => "<code>$1</code>",
                            13 => "<hr />",
                            14 => "<span style=\"text-decoration:line-through\">$1</span>",
                            15 => "<a href=\"{$CONFIG['application_webpath']}download.php?id=$1\">$2</a>",
                            16 => "<a href=\"$1\">$2</a>");

    $html = preg_replace($bbcode_regex, $bbcode_replace, $text);
    return $html;
}




function strip_bbcode_tooltip($text)
{
    $bbcode_regex = array(0 => '/\[url\](.*?)\[\/url\]/s',

                        1 => '/\[url\=(.*?)\](.*?)\[\/url\]/s',
                        2 => '/\[color\=(.*?)\](.*?)\[\/color\]/s',
                        3 => '/\[size\=(.*?)\](.*?)\[\/size\]/s',
                        4 => '/\[blockquote\=(.*?)\](.*?)\[\/blockquote\]/s',
                        5 => '/\[blockquote\](.*?)\[\/blockquote\]/s',
                        6 => "/\[s\](.*?)\[\/s\]/s");

    $bbcode_replace = array(0 => '$1',
                            1 => '$2',
                            2 => '$2',
                            3 => '$2',
                            4 => '$2',
                            5 => '$1',
                            6 => '$1'
                            );

    return preg_replace($bbcode_regex, $bbcode_replace, $text);
}


/**
 * Produces a HTML toolbar for use with a textarea or input box for entering bbcode
 * @author Ivan Lucas
 * @param string $elementid. HTML element ID of the textarea or input
 * @return string HTML
 */
function bbcode_toolbar($elementid)
{
    $html = "\n<div class='bbcode_toolbar'>BBCode: ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[b]', '[/b]')\">B</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[i]', '[/i]')\">I</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[u]', '[/u]')\">U</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[s]', '[/s]')\">S</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[quote]', '[/quote]')\">Quote</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[url]', '[/url]')\">Link</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[img]', '[/img]')\">Img</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[color]', '[/color]')\">Color</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[size]', '[/size]')\">Size</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '[code]', '[/code]')\">Code</a> ";
    $html .= "<a href=\"javascript:insertBBCode('{$elementid}', '', '[hr]')\">HR</a> ";
    $html .= "</div>\n";
    return $html;
}


/**
 * Produces a HTML form for adding a note to an item
 * @param $linkid int The link type to be used
 * @param $refid int The ID of the item this note if for
 * @return string The HTML to display
 */
function new_note_form($linkid, $refid)
{
    global $now, $sit, $iconset;
    $html = "<form name='addnote' action='note_new.php' method='post'>";
    $html .= "<div class='detailhead note'> <div class='detaildate'>".readable_date($now)."</div>\n";
    $html .= icon('note', 16, $GLOBALS['strNote ']);
    $html .= " ".sprintf($GLOBALS['strNewNoteByX'], user_realname($sit[2]))."</div>\n";
    $html .= "<div class='detailentry note'>";
    $html .= "<textarea rows='3' cols='40' name='bodytext' style='width: 94%; margin-top: 5px; margin-bottom: 5px; margin-left: 3%; margin-right: 3%; background-color: transparent; border: 1px dashed #A2A86A;'></textarea>";
    if (!empty($linkid))
    {
        $html .= "<input type='hidden' name='link' value='{$linkid}' />";
    }
    else
    {
        $html .= "&nbsp;{$GLOBALS['strLInk']} <input type='text' name='link' size='3' />";
    }

    if (!empty($refid))
    {
        $html .= "<input type='hidden' name='refid' value='{$refid}' />";
    }
    else
    {
        $html .= "&nbsp;{$GLOBALS['strRefID']} <input type='text' name='refid' size='4' />";
    }

    $html .= "<input type='hidden' name='action' value='addnote' />";
    $html .= "<input type='hidden' name='rpath' value='{$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']}' />";
    $html .= "<div style='text-align: right'><input type='submit' value='{$GLOBALS['strNewNote']}' /></div>\n";
    $html .= "</div>\n";
    $html .= "</form>";
    return $html;
}


/**
 * Produces HTML of all notes assigned to an item
 * @param $linkid int The link type
 * @param $refid int The ID of the item the notes are linked to
 * @param $delete bool Whether its possible to delet notes (default TRUE)
 * @return string HTML of the notes
 */
function show_notes($linkid, $refid, $delete = TRUE)
{
    global $sit, $iconset, $dbNotes, $strDelete, $strAreYouSureDelete;
    $sql = "SELECT * FROM `{$dbNotes}` WHERE link='{$linkid}' AND refid='{$refid}' ORDER BY timestamp DESC, id DESC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    $countnotes = mysql_num_rows($result);
    if ($countnotes >= 1)
    {
        while ($note = mysql_fetch_object($result))
        {
            $html .= "<div class='detailhead note'> <div class='detaildate'>".readable_date(mysqlts2date($note->timestamp));
            if ($delete)
            {
                $html .= "<a href='note_delete.php?id={$note->id}&amp;rpath=";
                $html .= "{$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']}' ";
                if ($_SESSION['userconfig']['show_confirmation_delete'])
                {
                    $html .= "onclick=\"return confirm_action('{$strAreYouSureDelete}', true);\"";
                }
                $html .= ">";
                $html .= icon('delete', 16, $strDelete)."</a>";
            }
            $html .= "</div>\n"; // /detaildate
            $html .= icon('note', 16)." ";
            $html .= sprintf($GLOBALS['strNoteAddedBy'], user_realname($note->userid,TRUE));
            $html .= "</div>\n"; // detailhead
            $html .= "<div class='detailentry note'>";
            $html .= nl2br(bbcode($note->bodytext));
            $html .= "</div>\n";
        }
    }
    return $html;
}


/**
 * Produces a HTML dashlet 'window' for display on the dashboard
 * @author Ivan Lucas
 * @param string $dashboard. Dashboard component name.
 * @param string $dashletid. The table row ID of that we are 'drawing' this dashlet into and
 *                           the ID of the dashboard component instance as recorded in the users settings
 *                           as a single string, this is received by the dashlet from dashboard_do()
 * @param string $icon. HTML for an icon to be displayed on the dashlet window
 * @param string $title. A title for the dashlet, also displayed on the dashlet window
 * @param string $link. URL of a page to link to from the dashlet window (link on the title)
 * @param string $content. HTML content to display inside the dashlet window
 * @note This function looks for the existence of two dashboard component functions
 *       dashboard_*_display() and dashboard_*_edit(), (where * is the name of the dashlet)
 *       if these are found the dashlet will use ajax and call these functions for it's
 *       main display (and refreshing) and to edit settings.
 * @return string HTML
 */
function dashlet($dashboard, $dashletid, $icon, $title='', $link='', $content='')
{
    global $strLoading;
    if (empty($icon)) $icon = icon('dashboard', 16);
    if (empty($title)) $title = $GLOBALS['strUntitled'];
    $displayfn = "dashboard_{$dashboard}_display";
    $editfn = "dashboard_{$dashboard}_edit";

    $html .= "<div class='windowbox' id='{$dashletid}'>";
    $html .= "<div class='windowtitle'>";
    $html .= "<div class='innerwindow'>";
    if (function_exists($displayfn))
    {
        $html .= "<a href=\"javascript:get_and_display('ajaxdata.php?action=dashboard_display&amp;dashboard={$dashboard}&amp;did={$dashletid}','win{$dashletid}',true);\">";
        $html .= icon('reload', 16, '', '', "refresh{$dashletid}")."</a>";
    }

    if (function_exists($editfn))
    {
        $html .= "<a href=\"javascript:get_and_display('ajaxdata.php?action=dashboard_edit&amp;dashboard={$dashboard}&amp;did={$dashletid}','win{$dashletid}',false);\">";
        $html .= icon('edit', 16)."</a>";
    }
    $html .= "</div>";
    if (!empty($link)) $html .= "<a href=\"{$link}\">{$icon}</a> <a href=\"{$link}\">{$title}</a>";
    else $html .= "{$icon} {$title}";
    $html .= "</div>\n";
    $html .= "<div class='window' id='win{$dashletid}'>";
    $html .= $content;
    $displayfn = "dashboard_{$dashboard}_display";
    if (function_exists($displayfn))
    {
        $html .= "<script type='text/javascript'>\n//<![CDATA[\nget_and_display('ajaxdata.php?action=dashboard_display&dashboard={$dashboard}','win{$dashletid}',true);\n//]]>\n</script>\n";
    }
    $html .= "</div></div>";

    return $html;
}


/**
 * Creates a link that opens within a dashlet window
 * @author Ivan Lucas
 * @param string $dashboard. Dashboard component name.
 * @param string $dashletid. The table row ID of that we are 'drawing' this dashlet into and
 *                           the ID of the dashboard component instance as recorded in the users settings
 *                           as a single string, this is received by the dashlet from dashboard_do()
 * @param string $text. The text of the hyperlink for the user to click
 * @param string $action. edit|save|display
 edit = This is a link to a dashlet config form page
 save = Submit a dashlet config form (see $formid param)
 display = Display a regular dashlet page
 * @param array $params. Associative array of parameters to pass on the URL of the link
 * @param bool $refresh. The link will be automatically refreshed when TRUE
 * @param string $formid. The form element ID to be submitted when using 'save' action
 * @return string HTML
 */
function dashlet_link($dashboard, $dashletid, $text='', $action='', $params='', $refresh = FALSE, $formid='')
{
    if ($action == 'edit') $action = 'dashboard_edit';
    elseif ($action == 'save') $action = 'dashboard_save';
    else $action = 'dashboard_display';
    if (empty($text)) $text = $GLOBALS['strUntitled'];

    // Ensure the dashlet ID is always correct, 'win' gets prepended with each subpage
    // We only need it once
    $dashletid = 'win'.str_replace('win', '', $dashletid);

    // Convert refresh boolean to javascript text for boolean
    if ($refresh) $refresh = 'true';
    else $refresh = 'false';

    if ($action == 'dashboard_save' AND $formid == '') $formid = "{$dashboard}form";

    if ($action == 'dashboard_save') $html .= "<a href=\"javascript:ajax_save(";
    else  $html .= "<a href=\"javascript:get_and_display(";
    $html .= "'ajaxdata.php?action={$action}&dashboard={$dashboard}&did={$dashletid}";
    if (is_array($params))
    {
        foreach ($params AS $pname => $pvalue)
        {
            $html .= "&{$pname}={$pvalue}";
        }
    }
    //$html .= "&editaction=do_new&type={$type}";

    if ($action != 'dashboard_save')
    {
        $html .= "', '{$dashletid}'";
        $html .= ", $refresh";
    }
    else
    {
        $html .= "', '{$formid}'";
    }
    $html .= ");\">{$text}</a>";

    return $html;
}


/**
 * HTML for an ajax help link
 * @author Ivan Lucas
 * @param string $context. The base filename of the popup help file in
                          help/en-GB/ (without the .txt extension)
 * @return string HTML
 */
function help_link($context)
{
    global $strHelpChar;
    $html = "<span class='helplink'>[<a href='#' tabindex='-1' onmouseover=\"";
    $html .= "contexthelp(this, '$context'";
    if ($_SESSION['portalauth'] == TRUE) $html .= ",'portal'";
    else $html .= ",'standard'";
    $html .= ");return false;\">{$strHelpChar}<span>";
    $html .= "</span></a>]</span>";

    return $html;
}


/**
 * Function to return an user error message when a file fails to upload
 * @author Paul Heaney
 * @param errorcode The error code from $_FILES['file']['error']
 * @param name The file name which was uploaded from $_FILES['file']['name']
 * @return String containing the error message (in HTML)
 */
function get_file_upload_error_message($errorcode, $name)
{
    $str = "<div class='detailinfo'>\n";

    $str .=  sprintf($GLOBALS['strErrorOccuredUploadingX'], $_FILES['attachment']['name']);

    $str .=  "<p class='error'>";
    switch ($errorcode)
    {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $str .=  $GLOBALS['strAttachedFilesExceedMaxSize'];
            break;
        case UPLOAD_ERR_PARTIAL:
            $str .=  $GLOBALS['strFileOnlyPartiallyUploaded'];
            break;
        case UPLOAD_ERR_NO_FILE:
            $str .=  $GLOBALS['strnoFileUploaded'];
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $str .=  $GLOBALS['strTemporaryFolderMissing'];
            break;
        default:
            $str .=  $GLOBALS['strAnUnknownErrorOccured'];
            break;
    }
    $str .=  "</p>";
    $str .=  "</div>";

    return $str;
}


/**
 * Return the html of contract detatils
 * @author Kieran Hogg
 * @param int $maintid - ID of the contract
 * @param string $mode. 'internal' or 'external'
 * @return array of supported contracts, NULL if none
 * @todo FIXME not quite generic enough for a function ?
 */
function contract_details($id, $mode='internal')
{
    global $CONFIG, $iconset, $dbMaintenance, $dbSites, $dbResellers, $dbLicenceTypes, $now;

    $sql  = "SELECT m.*, m.notes AS maintnotes, s.name AS sitename, ";
    $sql .= "r.name AS resellername, lt.name AS licensetypename ";
    $sql .= "FROM `{$dbMaintenance}` AS m, `{$dbSites}` AS s, ";
    $sql .= "`{$dbResellers}` AS r, `{$dbLicenceTypes}` AS lt ";
    $sql .= "WHERE s.id = m.site ";
    $sql .= "AND m.id='{$id}' ";
    $sql .= "AND m.reseller = r.id ";
    $sql .= "AND (m.licence_type IS NULL OR m.licence_type = lt.id) ";
    if ($mode == 'external') $sql .= "AND m.site = '{$_SESSION['siteid']}'";

    $maintresult = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    $maint = mysql_fetch_object($maintresult);

    $html = "<table class='maintable vertical'>";
    $html .= "<tr><th>{$GLOBALS['strContract']} {$GLOBALS['strID']}:</th>";
    $html .= "<td><h3>".icon('contract', 32)." ";
    $html .= "{$maint->id}</h3></td></tr>";
    $html .= "<tr><th>{$GLOBALS['strStatus']}:</th><td>";
    if ($maint->term == 'yes')
    {
        $html .= "<strong>{$GLOBALS['strTerminated']}</strong>";
    }
    else
    {
        $html .= $GLOBALS['strActive'];
    }

    if ($maint->expirydate < $now AND $maint->expirydate != '-1')
    {
        $html .= "<span class='expired'>, {$GLOBALS['strExpired']}</span>";
    }
    $html .= "</td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strSite']}:</th>";

    if ($mode == 'internal')
    {
        $html .= "<td><a href=\"site_details.php?id=".$maint->site."\">".$maint->sitename."</a></td></tr>";
    }
    else
    {
        $html .= "<td><a href=\"sitedetails.php\">".$maint->sitename."</a></td></tr>";
    }
    $html .= "<tr><th>{$GLOBALS['strAdminContact']}:</th>";

    if ($mode == 'internal')
    {
        $html .= "<td><a href=\"contact_details.php?id=";
        $html .= "{$maint->admincontact}\">";
        $html .= contact_realname($maint->admincontact)."</a></td></tr>";
    }
    else
    {
        $html .= "<td><a href='contactdetails.php?id={$maint->admincontact}'>";
        $html .= contact_realname($maint->admincontact)."</a></td></tr>";
    }

    $html .= "<tr><th>{$GLOBALS['strReseller']}:</th><td>";

    if (empty($maint->resellername))
    {
        $html .= $GLOBALS['strNoReseller'];
    }
    else
    {
        $html .= $maint->resellername;
    }
    $html .= "</td></tr>";
    $html .= "<tr><th>{$GLOBALS['strProduct']}:</th><td>".product_name($maint->product)."</td></tr>";
    $html .= "<tr><th>{$GLOBALS['strIncidents']}:</th>";
    $html .= "<td>";
    $incidents_remaining = $maint->incident_quantity - $maint->incidents_used;

    if ($maint->incident_quantity == 0)
    {
        $quantity = $GLOBALS['strUnlimited'];
    }
    else
    {
        $quantity = $maint->incident_quantity;
    }

    $html .= sprintf($GLOBALS['strUsedNofN'], $maint->incidents_used, $quantity);
    if ($maint->incidents_used >= $maint->incident_quantity AND
        $maint->incident_quantity != 0)
    {
        $html .= " ({$GLOBALS['strZeroRemaining']})";
    }

    $html .= "</td></tr>";
    if ($maint->licence_quantity != '0')
    {
        $html .= "<tr><th>{$GLOBALS['strLicense']}:</th>";
        $html .= "<td>{$maint->licence_quantity} {$maint->licensetypename}</td></tr>\n";
    }

    $html .= "<tr><th>{$GLOBALS['strServiceLevel']}:</th><td>".get_sla_name($maint->servicelevel)."</td></tr>";
    $html .= "<tr><th>{$GLOBALS['strExpiryDate']}:</th><td>";
    if ($maint->expirydate == '-1')
    {
        $html .= "{$GLOBALS['strUnlimited']}";
    }
    else
    {
        $html .= ldate($CONFIG['dateformat_date'], $maint->expirydate);
    }

    $html .= "</td></tr>";

    if ($mode == 'internal')
    {
        $timed = servicelevel_timed($maint->servicelevel);
        $html .= "<tr><th>{$GLOBALS['strService']}</th><td>";
        $html .= contract_service_table($id, $timed);
        $html .= "</td></tr>\n";

        if ($timed)
        {
            $html .= "<tr><th>{$GLOBALS['strBalance']}</th><td>{$CONFIG['currency_symbol']}".number_format(get_contract_balance($id, TRUE, TRUE), 2);
            $multiplier = get_billable_multiplier(strtolower(date('D', $now)), date('G', $now));
            $html .= " (&cong;".contract_unit_balance($id, TRUE, TRUE)." units)";
            $html .= "</td></tr>";
        }
    }

    if ($maint->maintnotes != '' AND $mode == 'internal')
    {
        $html .= "<tr><th>{$GLOBALS['strNotes']}:</th><td>{$maint->maintnotes}</td></tr>";
    }
    $html .= "</table>";

    if ($mode == 'internal')
    {
        $operations = array();
        $operations[$GLOBALS['strEditContract']] = "contract_edit.php?action=edit&amp;maintid=$id";


        if ($maint->term != 'yes')
        {
            $operations[$GLOBALS['strNewService']] = "contract_new_service.php?contractid={$id}";
        }
        $html .= "<p align='center'>".html_action_links($operations)."</p>";
    }

    $html .= "<h3>{$GLOBALS['strNamedContacts']}</h3>";

    if (mysql_num_rows($maintresult) > 0)
    {
        if ($maint->allcontactssupported == 'yes')
        {
            $html .= "<p class='info'>{$GLOBALS['strAllSiteContactsSupported']}</p>";
        }
        else
        {
            $allowedcontacts = $maint->supportedcontacts;

            $supportedcontacts = supported_contacts($id);
            $numberofcontacts = 0;

            $numberofcontacts = sizeof($supportedcontacts);
            if ($allowedcontacts == 0)
            {
                $allowedcontacts = $GLOBALS['strUnlimited'];
            }
            $html .= "<table class='maintable'>";
            $supportcount = 1;

            if ($numberofcontacts > 0)
            {
                foreach ($supportedcontacts AS $contact)
                {
                    $html .= "<tr><th>{$GLOBALS['strContact']} #{$supportcount}:</th>";
                    $html .= "<td>".icon('contact', 16)." ";
                    if ($mode == 'internal')
                    {
                        $html .= "<a href=\"contact_details.php?";
                    }
                    else
                    {
                        $html .= "<a href=\"contactdetails.php?";
                    }
                    $html .= "id={$contact}\">".contact_realname($contact)."</a>, ";
                    $html .= contact_site($contact). "</td>";

                    if ($mode == 'internal')
                    {
                        $html .= "<td><a href=\"contract_delete_contact.php?contactid={$contact}&amp;maintid={$id}&amp;context=maintenance\">{$GLOBALS['strRemove']}</a></td></tr>\n";
                    }
                    else
                    {
                        $html .= "<td><a href=\"{$_SERVER['PHP_SELF']}?id={$id}&amp;contactid={$contact}&amp;action=remove\">{$GLOBALS['strRemove']}</a></td></tr>\n";
                    }
                    $supportcount++;
                }
            }
            else
            {
                $html .= "<tr><td>".user_alert($GLOBALS['strNoRecords'], E_USER_NOTICE)."</td></tr>";
            }
            $html .= "</table>";
        }

        if ($maint->allcontactssupported != 'yes')
        {
            $html .= "<p align='center'>";
            $html .= sprintf($GLOBALS['strUsedNofN'],
                            "<strong>".$numberofcontacts."</strong>",
                            "<strong>".$allowedcontacts."</strong>");
            $html .= "</p>";

            if ($numberofcontacts < $allowedcontacts OR $allowedcontacts == 0 AND $mode == 'internal')
            {
                $html .= "<p align='center'><a href='contract_new_contact.php?maintid={$id}&amp;siteid={$maint->site}&amp;context=maintenance'>";
                $html .= "{$GLOBALS['strNewNamedContact']}</a></p>";
            }
            else
            {
                $html .= "<h3>{$GLOBALS['strNewNamedContact']}</h3>";
                $html .= "<form action='{$_SERVER['PHP_SELF']}?id={$id}&amp;action=";
                $html .= "add' method='post' >";
                $html .= "<p align='center'>{$GLOBLAS['strNewSupportedContact']} ";
                $html .= contact_site_drop_down('contactid',
                                                'contactid',
                                                maintenance_siteid($id),
                                                supported_contacts($id));
                $html .= help_link('NewSupportedContact');
                $html .= " <input type='submit' value='{$GLOBALS['strNew']}' /></p></form>";
            }
            if ($mode == 'external')
            {
                $html .= "<p align='center'><a href='newcontact.php'>";
                $html .= "{$GLOBALS['strNewSiteContact']}</a></p>";
            }
        }

        $html .= "<br />";
        $html .= "<h3>{$GLOBALS['strSkillsSupportedUnderContract']}:</h3>";
        // supported software
        $sql = "SELECT * FROM `{$GLOBALS[dbSoftwareProducts]}` AS sp, `{$GLOBALS[dbSoftware]}` AS s ";
        $sql .= "WHERE sp.softwareid = s.id AND productid='{$maint->product}' ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($result)>0)
        {
            $html .="<table class='maintable'>";
            while ($software = mysql_fetch_object($result))
            {
                $software->lifetime_end = mysql2date($software->lifetime_end);
                $html .= "<tr><td> ".icon('skill', 16)." ";
                if ($software->lifetime_end > 0 AND $software->lifetime_end < $now)
                {
                    $html .= "<span class='deleted'>";
                }
                $html .= $software->name;
                if ($software->lifetime_end > 0 AND $software->lifetime_end < $now)
                {
                    $html .= "</span>";
                }
                $html .= "</td></tr>\n";
            }
            $html .= "</table>\n";
        }
        else
        {
            $html .= "<p align='center'>{$GLOBALS['strNone']} / {$GLOBALS['strUnknown']}<p>";
        }
    }
    else
    {
        $html = user_alert($GLOBALS['strNothingToDisplay'], E_USER_NOTICE);
    }

    return $html;
}


/**
 * Function to return a HTML table row with two columns.
 * Giving radio boxes for groups and if the level is 'management' then you are able to view the users (de)selcting
 * @param string $title - text to go in the first column
 * @param string $level either management or engineer, management is able to (de)select users
 * @param int $groupid  Defalt group to select
 * @param string $type - Type of buttons to use either radio or checkbox
 * @return table row of format <tr><th /><td /></tr>
 * @author Paul Heaney
 */
function group_user_selector($title, $level = 'engineer', $groupid = '', $type='radio')
{
    global $dbUsers, $dbGroups;

    $str .= "<tr><th>{$title}</th>";
    $str .= "<td align='center'>";

    $sql = "SELECT DISTINCT(g.name), g.id FROM `{$dbUsers}` AS u, `{$dbGroups}` AS g ";
    $sql .= "WHERE u.status > 0 AND u.groupid = g.id ORDER BY g.name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        while ($row = mysql_fetch_object($result))
        {
            if ($type == 'radio')
            {
                $str .= "<input type='radio' name='group' id='{$row->name}' onclick='groupMemberSelect(\"{$row->name}\", \"TRUE\")' ";
            }
            elseif ($type == 'checkbox')
            {
                $str .= "<input type='checkbox' name='{$row->name}' id='{$row->name}' onclick='groupMemberSelect(\"{$row->name}\", \"FALSE\")' ";
            }

            if ($groupid == $row->id)
            {
                $str .= " checked='checked' ";
                $groupname = $row->name;
            }

            $str .= "/>{$row->name} \n";
        }

        $str .="<br />";


        $sql = "SELECT u.id, u.realname, g.name FROM `{$dbUsers}` AS u, `{$dbGroups}` AS g ";
        $sql .= "WHERE u.status > 0 AND u.groupid = g.id ORDER BY username";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if ($level == "management")
        {
            $str .= "<select name='users[]' id='include' multiple='multiple' size='20'>\n";
        }
        elseif ($level == "engineer")
        {
            $str .= "<select name='users[]' id='include' multiple='multiple' size='20' style='display:none'>\n";
        }

        while ($row = mysql_fetch_object($result))
        {
            $str .= "<option value='{$row->id}' ";
            if ($row->name == $groupname) $str .= "selected='selected' ";
            $str .= ">{$row->realname} ({$row->name})</option>\n";
        }
        $str .= "</select>\n";
        $str .= "<br />";
        if ($level == "engineer")
        {
            $visibility = " style='display:none'";
        }

        $str .= "<input type='button' id='selectall' onclick='doSelect(true, \"include\")' value='Select All' {$visibility} />";
        $str .= "<input type='button' id='clearselection' onclick='doSelect(false, \"include\")' value='Clear Selection' {$visibility} />";
    }
    else
    {
        echo $strNoneAvailable;
    }

    $str .= "</td>";
    $str .= "</tr>\n";

    return $str;
}


/**
 * Converts emoticon text to HTML
 * Will only show emoticons if the user has chosen in their settings
 * that they would like to see them, otherwise shows original text
 * @author Kieran Hogg
 * @param string $text. Text with smileys in it
 * @return string HTML
 */
function emoticons($text)
{
    global $CONFIG;

    $html = '';
    if ($_SESSION['userconfig']['show_emoticons'] == 'TRUE')
    {
        $smiley_url = "{$CONFIG['application_uriprefix']}{$CONFIG['application_webpath']}images/emoticons/";
        $smiley_regex = array(0 => "/\:[-]?\)/s",
                            1 => "/\:[-]?\(/s",
                            2 => "/\;[-]?\)/s",
                            3 => "/\:[-]?[pP]/s",
                            4 => "/\:[-]?@/s",
                            5 => "/\:[-]?[Oo]/s",
                            6 => "/\:[-]?\\$/s",
                            7 => "/\\([Yy]\)/s",
                            8 => "/\\([Nn]\)/s",
                            9 => "/\\([Bb]\)/s",
                            10 => "/\:[-]?[dD]/s"
                            );

        $smiley_replace = array(0 => "<img src='{$smiley_url}smile.png' alt='$1' title='$1' />",
                                1 => "<img src='{$smiley_url}sad.png' alt='$1' title='$1' />",
                                2 => "<img src='{$smiley_url}wink.png' alt='$1' title='$1' />",
                                3 => "<img src='{$smiley_url}tongue.png' alt='$1' title='$1' />",
                                4 => "<img src='{$smiley_url}angry.png' alt='$1' title='$1' />",
                                5 => "<img src='{$smiley_url}omg.png' alt='$1' title='$1' />",
                                6 => "<img src='{$smiley_url}embarassed.png' alt='$1' title='$1' />",
                                7 => "<img src='{$smiley_url}thumbs_up.png' alt='$1' title='$1' />",
                                8 => "<img src='{$smiley_url}thumbs_down.png' alt='$1' title='$1' />",
                                9 => "<img src='{$smiley_url}beer.png' alt='$1' title='$1' />",
                                10 => "<img src='{$smiley_url}teeth.png' alt='$1' title='$1' />"
                                );

        $html = preg_replace($smiley_regex, $smiley_replace, $text);
    }
    else
    {
        $html = $text;
    }

    return $html;
}


/**
 * HTML for an alphabetical index of links
 * @author Ivan Lucas
 * @param string $baseurl start of a URL, the letter will be appended to this
 * @param bool $displayinactive
 * @return HTML
 */
function alpha_index($baseurl = '#', $displayinactive = FALSE)
{
    global $i18nAlphabet, $strAll;

    if ($displayinactive === TRUE OR $displayinactive === 'true')
    {
        $inactivestring="displayinactive=true";
    }
    else
    {
        $inactivestring="displayinactive=false";
    }

    $html = '';
    if (!empty($i18nAlphabet))
    {
        $html .= "<span class='separator'> | </span>";
        $len = mb_strlen($i18nAlphabet);
        for ($i = 0; $i < $len; $i++)
        {
            $html .= "<a href=\"{$baseurl}";
            $html .= urlencode(mb_substr($i18nAlphabet, $i, 1))."\">";
            $html .= mb_substr($i18nAlphabet, $i, 1)."</a><span class='separator'> | </span> \n";
        }
        $html .= "<a href='{$_SERVER['PHP_SELF']}?search_string=*&amp;{$inactivestring}'>{$strAll}</a>\n";
    }
    return $html;
}


/**
 * HTML for a hyperlink to hide/reveal a password field
 * @author Ivan Lucas
 */
function password_reveal_link($id)
{
    $html = "<a href=\"javascript:password_reveal('$id')\" id=\"link{$id}\">{$GLOBALS['strReveal']}</a>";
    return $html;
}


function qtype_listbox($type)
{
    global $CONFIG, $strRating, $strOptions, $strMultipleOptions, $strText;

    $html .= "<select name='type'>\n";
    $html .= "<option value='rating'";
    if ($type == 'rating') $html .= " selected='selected'";
    $html .= ">{$strRating}</option>";

    $html .= "<option value='options'";
    if ($type == 'options') $html .= " selected='selected'";
    $html .= ">{$strOptions}</option>";

    $html .= "<option value='multioptions'";
    if ($type == 'multioptions') $html .= " selected='selected'";
    $html .= ">{$strMultipleOptions}</option>";

    $html .= "<option value='text'";
    if ($type == 'text') $html .= " selected='selected'";
    $html .= ">{$strText}</option>";

    $html .= "</select>\n";

    return $html;
}



function feedback_qtype_listbox($type)
{
    global $CONFIG, $strRating, $strOptions, $strMultipleOptions, $strText;

    $html .= "<select name='type'>\n";
    $html .= "<option value='rating'";
    if ($type == 'rating') $html .= " selected='selected'";
    $html .= ">{$strRating}</option>";

    $html .= "<option value='options'";
    if ($type == 'options') $html .= " selected='selected'";
    $html .= ">{$strOptions}</option>";

    $html .= "<option value='multioptions'";
    if ($type == 'multioptions') $html .= " selected='selected'";
    $html .= ">{$strMultipleOptions}</option>";

    $html .= "<option value='text'";
    if ($type == 'text') $html .= " selected='selected'";
    $html .= ">{$strText}</option>";

    $html .= "</select>\n";

    return $html;
}





/**
 * Recursive function to list links as a tree
 * @author Ivan Lucas
 */
function show_links($origtab, $colref, $level=0, $parentlinktype='', $direction='lr')
{
    global $dbLinkTypes, $dbLinks;
    // Maximum recursion
    $maxrecursions = 15;

    if ($level <= $maxrecursions)
    {
        $sql = "SELECT * FROM `{$dbLinkTypes}` WHERE origtab='$origtab' ";
        if (!empty($parentlinktype)) $sql .= "AND id='{$parentlinktype}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        while ($linktype = mysql_fetch_object($result))
        {
            // Look up links of this type
            $lsql = "SELECT * FROM `{$dbLinks}` WHERE linktype='{$linktype->id}' ";
            if ($direction == 'lr') $lsql .= "AND origcolref='{$colref}'";
            elseif ($direction == 'rl') $lsql .= "AND linkcolref='{$colref}'";
            $lresult = mysql_query($lsql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
            if (mysql_num_rows($lresult) >= 1)
            {
                if (mysql_num_rows($lresult) >= 1)
                {
                    $html .= "<ul>";
                    $html .= "<li>";
                    while ($link = mysql_fetch_object($lresult))
                    {
                        $recsql = "SELECT {$linktype->selectionsql} AS recordname FROM {$linktype->linktab} WHERE ";
                        if ($direction == 'lr') $recsql .= "{$linktype->linkcol}='{$link->linkcolref}' ";
                        elseif ($direction == 'rl') $recsql .= "{$linktype->origcol}='{$link->origcolref}' ";
                        $recresult = mysql_query($recsql);
                        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
                        while ($record = mysql_fetch_object($recresult))
                        {
                            if ($link->direction == 'bi')
                            {
                                $html .= "<strong>{$linktype->name}</strong> ";
                            }
                            elseif ($direction == 'lr')
                            {
                                $html .= "<strong>{$linktype->lrname}</strong> ";
                            }
                            elseif ($direction == 'rl')
                            {
                                $html .= "<strong>{$linktype->rlname}</strong> ";
                            }
                            else
                            {
                                $html = $GLOBALS['strError'];
                            }

                            if ($direction == 'lr')
                            {
                                $currentlinkref = $link->linkcolref;
                            }
                            elseif ($direction == 'rl')
                            {
                                $currentlinkref = $link->origcolref;
                            }

                            $viewurl = str_replace('%id%',$currentlinkref,$linktype->viewurl);

                            $html .= "{$currentlinkref}: ";
                            if (!empty($viewurl)) $html .= "<a href='$viewurl'>";
                            $html .= "{$record->recordname}";
                            if (!empty($viewurl)) $html .= "</a>";
                            $html .= " - ".user_realname($link->userid,TRUE);
                            $html .= show_links($linktype->linktab, $currentlinkref, $level+1, $linktype->id, $direction); // Recurse
                            $html .= "</li>\n";
                        }
                    }
                    $html .= "</ul>\n";
                }
                else
                {
                    $html .= "<p>{$GLOBALS['strNone']}</p>";
                }
            }
        }
    }
    else
    {
        $html .= "<p class='error'>{$GLOBALS['strError']}: Maximum number of {$maxrecursions} recursions reached</p>";
    }
    return $html;
}


/**
 * Interface for creating record 'links' (relationships)
 * @author Ivan Lucas
 */
function show_create_links($table, $ref)
{
    global $dbLinkTypes;
    $html .= "<p align='center'>{$GLOBALS['strNewLink']}: ";
    $sql = "SELECT * FROM `{$dbLinkTypes}` WHERE origtab='$table' OR linktab='$table' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $numlinktypes = mysql_num_rows($result);
    $rowcount = 1;
    while ($linktype = mysql_fetch_object($result))
    {
        if ($linktype->origtab == $table AND $linktype->linktab != $table)
        {
            $html .= "<a href='link_new.php?origtab=tasks&amp;origref={$ref}&amp;linktype={$linktype->id}'>{$linktype->lrname}</a>";
        }
        elseif ($linktype->origtab != $table AND $linktype->linktab == $table)
        {
            $html .= "<a href='link_new.php?origtab=tasks&amp;origref={$ref}&amp;linktype={$linktype->id}'>{$linktype->rlname}</a>";
        }
        else
        {
            $html .= "<a href='link_new.php?origtab=tasks&amp;origref={$ref}&amp;linktype={$linktype->id}'>{$linktype->lrname}</a> | ";
            $html .= "<a href='link_new.php?origtab=tasks&amp;origref={$ref}&amp;linktype={$linktype->id}&amp;dir=rl'>{$linktype->rlname}</a>";
        }

        if ($rowcount < $numlinktypes) $html .= " | ";
        $rowcount++;
    }
    $html .= "</p>";
    return $html;
}


/**
 * Output the html for the edit site form
 *
 * @param int $site ID of the site
 * @param string $mode whether this is internal or external facing, defaults to internal
 * @return string $html edit site form html
 * @author Kieran Hogg
 */
function show_edit_site($site, $mode='internal')
{
    global $CONFIG, $strRequired;
    $sql = "SELECT * FROM `{$GLOBALS['dbSites']}` WHERE id='$site' ";
    $siteresult = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    echo show_form_errors('site_edit');
    clear_form_errors('site_edit');
    while ($obj = mysql_fetch_object($siteresult))
    {
        if ($mode == 'internal')
        {
            $html .= "<h2>".icon('site', 32)." {$GLOBALS['strEditSite']}: {$site} - ";
            $html .= site_name($site)."</h2>";
        }
        else
        {
            $html .= "<h2>".icon('site', 32)." ".site_name($site)."</h2>";
        }

        $html .= "<form name='edit_site' action='{$_SERVER['PHP_SELF']}";
        $html .= "?action=update' method='post' onsubmit='return ";
        $html .= "confirm_action(\"{$GLOBALS['strAreYouSureMakeTheseChanges']}\")'>";
        $html .= "<table class='maintable vertical'>";
        $html .= "<tr><th>{$GLOBALS['strName']}:</th>";
        $html .= "<td><input class='required' maxlength='50' name='name' size='40' value='{$obj->name}' />";
        $html .= " <span class='required'>{$GLOBALS['strRequired']}</span></td></tr>\n";
        if ($mode == 'internal')
        {
            $html .= "<tr><th>{$GLOBALS['strTags']}:</th><td><textarea rows='2' cols='60' name='tags'>";
            $html .= list_tags($site, TAG_SITE, false)."</textarea>\n";
        }
        $html .= "<tr><th>{$GLOBALS['strDepartment']}:</th>";
        $html .= "<td><input maxlength='50' name='department' size='40' value='{$obj->department}' />";
        $html .= "</td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strAddress1']}:</th>";
        $html .= "<td><input maxlength='50' name='address1' class='required' ";
        $html .= "size='40' value='{$obj->address1}' /> <span class='required'>{$strRequired}</span>";
        $html .= "</td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strAddress2']}: </th><td><input maxlength='50' name='address2' size='40' value='{$obj->address2}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strCity']}:</th><td><input maxlength='255' name='city' size='40' value='{$obj->city}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strCounty']}:</th><td><input maxlength='255' name='county' size='40' value='{$obj->county}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strPostcode']}:</th><td><input maxlength='255' name='postcode' size='40' value='{$obj->postcode}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strCountry']}:</th><td>".country_drop_down('country', $obj->country)."</td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strTelephone']}:</th><td>";
        $html .= "<input maxlength='255' name='telephone' size='40' value='{$obj->telephone}' />";
        $html .= "</td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strFax']}:</th><td>";
        $html .= "<input maxlength='255' name='fax' size='40' value='{$obj->fax}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strEmail']}:</th><td>";
        $html .= "<input maxlength='255' name='email' size='40' value='{$obj->email}' />";
        $html .= "</td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strWebsite']}:</th><td>";
        $html .= "<input maxlength='255' name='websiteurl' size='40' value='{$obj->websiteurl}' /></td></tr>\n";
        $html .= "<tr><th>{$GLOBALS['strSiteType']}:</th><td>\n";
        $html .= sitetype_drop_down('typeid', $obj->typeid);
        $html .= "</td></tr>\n";
        if ($mode == 'internal')
        {
            $html .= "<tr><th>{$GLOBALS['strSalesperson']}:</th><td>";
            $html .= user_drop_down('owner', $obj->owner, $accepting = FALSE, '', '', TRUE);
            $html .= "</td></tr>\n";
        }

        if ($mode == 'internal')
        {
            $html .= "<tr><th>{$GLOBALS['strIncidentPool']}:</th>";
            $incident_pools = explode(',', "{$GLOBALS['strNone']},{$CONFIG['incident_pools']}");
            if (array_key_exists($obj->freesupport, $incident_pools) == FALSE)
            {
                array_unshift($incident_pools, $obj->freesupport);
            }
            $html .= "<td>".array_drop_down($incident_pools,'incident_pool',$obj->freesupport)."</td></tr>";
            $html .= "<tr><th>{$GLOBALS['strActive']}:</th><td><input type='checkbox' name='active' ";
            if ($obj->active == 'true')
            {
                $html .= "checked='{$obj->active}'";
            }
            $html .= " value='true' /></td></tr>\n";
            $html .= "<tr><th>{$GLOBALS['strNotes']}:</th><td>";
            $html .= "<textarea rows='5' cols='30' name='notes'>{$obj->notes}</textarea>";
            $html .= "</td></tr>\n";
        }
        plugin_do('edit_site_form');
        $html .= "</table>\n";
        $html .= "<input name='site' type='hidden' value='$site' />";
        $html .= "<p class='formbuttons'><input name='reset' type='reset' value='{$GLOBALS['strReset']}' /> ";
        $html .= "<input name='submit' type='submit' value='{$GLOBALS['strSave']}' /></p>";
        if ($mode == 'internal')
        {
            $html .= "<p><a href=\"site_details.php?id={$site}\">{$GLOBALS['strReturnWithoutSaving']}</a></p>";
        }
        $html .= "</form>";
    }
    return $html;
}


/**
 * Output the html for an add contact form
 *
 * @param int $siteid - the site you want to add the contact to
 * @param string $mode - whether this is internal or external facing, defaults to internal
 * @return string $html add contact form html
 * @author Kieran Hogg
 */
function show_new_contact($siteid = 0, $mode = 'internal')
{
    global $CONFIG;
    $returnpage = cleanvar($_REQUEST['return']);
    if (!empty($_REQUEST['name']))
    {
        $name = explode(' ',cleanvar(urldecode($_REQUEST['name'])), 2);
        $_SESSION['formdata']['new_contact']['forenames'] = ucfirst($name[0]);
        $_SESSION['formdata']['new_contact']['surname'] = ucfirst($name[1]);
    }

    $html = show_form_errors('new_contact');
    clear_form_errors('new_contact');
    $html .= "<h2>".icon('contact', 32)." ";
    if ($mode == 'external')
    {
        $html .= "{$GLOBALS['strNewSiteContact']}";
    }
    else
    {
        $html .= "{$GLOBALS['strNewContact']}";
    }
    $html .= "</h2>";

    if ($mode == 'internal')
    {
        $html .= "<h5 class='warning'>{$GLOBALS['strAvoidDupes']}</h5>";
    }
    $html .= "<form name='contactform' action='{$_SERVER['PHP_SELF']}' ";
    $html .= "method='post' onsubmit=\"return confirm_action('{$GLOBALS['strAreYouSureAdd']}')\">";
    $html .= "<table class='maintable vertical'>";
    $html .= "<tr><th>{$GLOBALS['strName']}</th>\n";

    $html .= "<td>";
    $html .= "\n<table><tr><td align='center'>{$GLOBALS['strTitle']}<br />";
    $html .= "<input maxlength='50' name='courtesytitle' title=\"";
    $html .= "{$GLOBALS['strCourtesyTitle']}\" size='7'";
    if ($_SESSION['formdata']['new_contact']['courtesytitle'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['new_contact']['courtesytitle']}'";
    }
    $html .= "/></td>\n";

    $html .= "<td align='center'>{$GLOBALS['strForenames']}<br />";
    $html .= "<input class='required' maxlength='100' name='forenames' ";
    $html .= "size='15' title=\"{$GLOBALS['strForenames']}\"";
    if ($_SESSION['formdata']['new_contact']['forenames'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['new_contact']['forenames']}'";
    }
    $html .= "/></td>\n";

    $html .= "<td align='center'>{$GLOBALS['strSurname']}<br />";
    $html .= "<input class='required' maxlength='100' name='surname' ";
    $html .= "size='20' title=\"{$GLOBALS['strSurname']}\"";
    if ($_SESSION['formdata']['new_contact']['surname'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['new_contact']['surname']}'";
    }
    $html .= " /> <span class='required'>{$GLOBALS['strRequired']}</span></td></tr>\n";
    $html .= "</table>\n</td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strJobTitle']}</th><td><input maxlength='255'";
    $html .= " name='jobtitle' size='35' title=\"{$GLOBALS['strJobTitle']}\"";
    if ($_SESSION['formdata']['new_contact']['jobtitle'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['new_contact']['jobtitle']}'";
    }
    $html .= " /></td></tr>\n";
    if ($mode == 'internal')
    {
        $html .= "<tr><th>{$GLOBALS['strSite']}</th><td>";
        $html .= site_drop_down('siteid',$siteid, TRUE)."<span class='required'>{$GLOBALS['strRequired']}</span></td></tr>\n";
    }
    else
    {
        // For external always force the site to be the session site
        $html .= "<input type='hidden' name='siteid' value='{$_SESSION['siteid']}' />";
    }

    $html .= "<tr><th>{$GLOBALS['strDepartment']}</th><td><input maxlength='255' name='department' size='35'";
    if ($_SESSION['formdata']['new_contact']['department'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['new_contact']['department']}'";
    }
    $html .= "/></td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strEmail']}</th><td>";
    $html .= "<input class='required' maxlength='100' name='email' size='35'";
    if ($_SESSION['formdata']['new_contact']['email'])
    {
        $html .= "value='{$_SESSION['formdata']['new_contact']['email']}'";
    }
    $html .= "/> <span class='required'>{$GLOBALS['strRequired']}</span> ";

    $html .= "<label>";
    $html .= html_checkbox('dataprotection_email', 'No');
    $html .= "{$GLOBALS['strEmail']} {$GLOBALS['strDataProtection']}</label>".help_link("EmailDataProtection");
    $html .= "</td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strTelephone']}</th><td><input maxlength='50' name='phone' size='35'";
    if ($_SESSION['formdata']['new_contact']['phone'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['new_contact']['phone']}'";
    }
    $html .= "/> ";

    $html .= "<label>";
    $html .= html_checkbox('dataprotection_phone', 'No');
    $html .= "{$GLOBALS['strTelephone']} {$GLOBALS['strDataProtection']}</label>".help_link("TelephoneDataProtection");
    $html .= "</td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strMobile']}</th><td><input maxlength='100' name='mobile' size='35'";
    if ($_SESSION['formdata']['new_contact']['mobile'] != '')
    {
        $html .= "value='{$_SESSION['formdata']['new_contact']['mobile']}'";
    }
    $html .= "/></td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strFax']}</th><td><input maxlength='50' name='fax' size='35'";
    if ($_SESSION['formdata']['new_contact']['fax'])
    {
        $html .= "value='{$_SESSION['formdata']['new_contact']['fax']}'";
    }
    $html .= "/></td></tr>\n";

    $html .= "<tr><th>{$GLOBALS['strAddress']}</th><td><label>";
    $html .= html_checkbox('dataprotection_address', 'No');
    $html .= " {$GLOBALS['strAddress']} {$GLOBALS['strDataProtection']}</label>";
    $html .= help_link("AddressDataProtection")."</td></tr>\n";
    $html .= "<tr><th></th><td><label><input type='checkbox' name='usesiteaddress' value='yes' onclick=\"$('hidden').toggle();\" /> {$GLOBALS['strSpecifyAddress']}</label></td></tr>\n";
    $html .= "<tbody id='hidden' style='display:none'>";
    $html .= "<tr><th>{$GLOBALS['strAddress1']}</th>";
    $html .= "<td><input maxlength='255' name='address1' size='35' /></td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strAddress2']}</th>";
    $html .= "<td><input maxlength='255' name='address2' size='35' /></td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strCity']}</th><td><input maxlength='255' name='city' size='35' /></td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strCounty']}</th><td><input maxlength='255' name='county' size='35' /></td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strCountry']}</th><td>";
    $html .= country_drop_down('country', $CONFIG['home_country'])."</td></tr>\n";
    $html .= "<tr><th>{$GLOBALS['strPostcode']}</th><td><input maxlength='255' name='postcode' size='35' /></td></tr>\n";
    $html .= "</tbody>";
    if ($mode == 'internal')
    {
        $html .= "<tr><th>{$GLOBALS['strNotes']}</th><td><textarea cols='60' rows='5' name='notes'>";
        if ($_SESSION['formdata']['new_contact']['notes'] != '')
        {
            $html .= $_SESSION['formdata']['new_contact']['notes'];
        }
        $html .= "</textarea></td></tr>\n";
    }
    $html .= "<tr><th>{$GLOBALS['strEmailDetails']}</th>";
    // Check the box to send portal details, only if portal is enabled
    $html .= "<td><input type='checkbox' id='emaildetails' name='emaildetails' value='on'";
    if ($CONFIG['portal'] == TRUE) $html .= " checked='checked'";
    else $html .= " disabled='disabled'";
    $html .= " />";
    $html .= "<label for='emaildetails'>{$GLOBALS['strEmailContactLoginDetails']}</label></td></tr>";
    $html .= "</table>\n\n";
    if (!empty($returnpage)) $html .= "<input type='hidden' name='return' value='{$returnpage}' />";
    $html .= "<p class='formbuttons'><input name='reset' type='reset' value='{$GLOBALS['strReset']}' /> ";
    $html .= "<input name='submit' type='submit' value=\"{$GLOBALS['strSave']}\" /></p>";
    $html .= "</form>\n";

    //cleanup form vars
    clear_form_data('new_contact');

    return $html;
}


/**
 * Format an external ID (From an escalation partner) as HTML
 * @author Ivan Lucas
 * @param int $externalid. An external ID to format
 * @param int $escalationpath. Escalation path ID
 * @return HTML
 */
function format_external_id($externalid, $escalationpath='')
{
    global $CONFIG, $dbEscalationPaths;

    if (!empty($escalationpath))
    {
        // Extract escalation path
        $epsql = "SELECT id, name, track_url, home_url, url_title FROM `{$dbEscalationPaths}` ";
        if (!empty($escalationpath)) $epsql .= "WHERE id='$escalationpath' ";
        $epresult = mysql_query($epsql);
        if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($epresult) >= 1)
        {
            while ($escalationpath = mysql_fetch_object($epresult))
            {
                $epath['name'] = $escalationpath->name;
                $epath['track_url'] = $escalationpath->track_url;
                $epath['home_url'] = $escalationpath->home_url;
                $epath['url_title'] = $escalationpath->url_title;
            }

            if (!empty($externalid))
            {
                $epathurl = str_replace('%externalid%', $externalid, $epath['track_url']);
                $html = "<a href='{$epathurl}' title='{$epath['url_title']}'>{$externalid}</a>";
            }
            else
            {
                $epathurl = $epath['home_url'];
                $html = "<a href='{$epathurl}' title='{$epath['url_title']}'>{$epath['name']}</a>";
            }
        }
    }
    else
    {
        $html = $externalid;
    }
    return $html;
}


/**
 * Outputs a contact's contract associate, if the viewing user is allowed
 * @author Kieran Hogg
 * @param int $userid ID of the contact
 * @param string $mode ??? Defaults to Internal
 * @return string output html
 */
function contracts_for_contacts_table($userid, $mode = 'internal')
{
    global $now, $CONFIG, $sit;
    if ((!empty($sit[2]) AND user_permission($sit[2], PERM_SUPPORTED_PRODUCT_VIEW)
        OR ($_SESSION['usertype'] == 'admin'))) // view supported products
    {
        $html .= "<h4>".icon('contract', 16)." {$GLOBALS['strContracts']}:</h4>";
        // Contracts we're explicit supported contact for
        $sql  = "SELECT sc.maintenanceid AS maintenanceid, m.product, p.name AS productname, ";
        $sql .= "m.expirydate, m.term ";
        $sql .= "FROM `{$GLOBALS['dbContacts']}` AS c, ";
        $sql .= "`{$GLOBALS['dbSupportContacts']}` AS sc, ";
        $sql .= "`{$GLOBALS['dbMaintenance']}` AS m, ";
        $sql .= "`{$GLOBALS['dbProducts']}` AS p ";
        $sql .= "WHERE c.id = '{$userid}' ";
        $sql .= "AND (sc.maintenanceid=m.id AND sc.contactid='{$userid}') ";
        $sql .= "AND m.product=p.id  ";
        // Contracts we're an 'all supported' on
        $sql .= "UNION ";
        $sql .= "SELECT m.id AS maintenanceid, m.product, p.name AS productname, ";
        $sql .= "m.expirydate, m.term ";
        $sql .= "FROM `{$GLOBALS['dbContacts']}` AS c, ";
        $sql .= "`{$GLOBALS['dbMaintenance']}` AS m, ";
        $sql .= "`{$GLOBALS['dbProducts']}` AS p ";
        $sql .= "WHERE c.id = '{$userid}' AND c.siteid = m.site ";
        $sql .= "AND m.allcontactssupported = 'yes' ";
        $sql .= "AND m.product=p.id  ";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) > 0)
        {
            $html .= "<table class='maintable'>";
            $html .= "<tr>";
            $html .= "<th>{$GLOBALS['strID']}</th><th>{$GLOBALS['strProduct']}</th><th>{$GLOBALS['strExpiryDate']}</th>";
            $html .= "</tr>\n";

            $supportcount = 1;
            $shade = 'shade2';
            while ($obj = mysql_fetch_object($result))
            {
                if ($obj->term == 'yes')
                {
                    $shade = 'expired';
                }

                if ($obj->expirydate < $now AND $obj->expirydate != -1)
                {
                    $shade = 'expired';
                }

                $html .= "<tr><td class='{$shade}'>";
                $html .= ''.icon('contract', 16)." ";
                if ($mode == 'internal')
                {
                    $html .= "<a href='contract_details.php?id=";
                }
                else
                {
                    $html .= "<a href='contracts.php?id=";
                }
                $html .= "{$obj->maintenanceid}'>";
                $html .= "{$GLOBALS['strContract']}: ";
                $html .= "{$obj->maintenanceid}</a></td>";
                $html .= "<td class='{$shade}'>{$obj->productname}</td>";
                $html .= "<td class='{$shade}'>";
                if ($obj->expirydate == -1)
                {
                    $html .= $GLOBALS['strUnlimited'];
                }
                else
                {
                    $html .= ldate($CONFIG['dateformat_date'], $obj->expirydate);
                }
                if ($obj->term == 'yes')
                {
                    $html .= " {$GLOBALS['strTerminated']}";
                }

                $html .= "</td>";
                $html .= "</tr>\n";
                $supportcount++;
                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }
            $html .= "</table>\n";
        }
        else
        {
            $html .= "<p align='center'>{$GLOBALS['strNone']}</p>\n";
        }

        if ($mode == 'internal')
        {
            $html .= "<p align='center'>";
            $html .= "<a href='contract_new_contact.php?contactid={$userid}&amp;context=contact'>";
            $html .= "{$GLOBALS['strAssociateContactWithContract']}</a></p>\n";
        }
    }

    return $html;
}


/**
 *
 * @author Paul Heaney
 * @param int $hour
 * @param int $minute
 */
function time_picker($hour = '', $minute = '', $name_prefix = '')
{
    global $CONFIG;

    // FIXME TODO use $CONFIG['dateformat_shorttime']

    $m = 0;

    if (empty($hour))
    {
        $hour = floor($CONFIG['start_working_day'] / 3600);
        $m = ($CONFIG['start_working_day'] % 3600) / 60;
    }

    if (empty($minute))
    {
        $minute = $m;
    }

    $html = "<select id='{$name_prefix}time_picker_hour' name='{$name_prefix}time_picker_hour'>\n";
    for ($i = 0; $i < 24; $i++)
    {
        $html .= "<option value='{$i}'";
        if ($i == $hour) $html .= " selected='selected'";
        $html .= ">".str_pad($i, 2, '0', STR_PAD_LEFT)."</option>\n";
    }
    $html .= "</select>\n";

    $html .= ":";

    $html .= "<select id='{$name_prefix}time_picker_minute' name='{$name_prefix}time_picker_minute'>\n";
    for ($i = 0; $i < 60; $i += $CONFIG['display_minute_interval'])
    {
        $html .= "<option value='{$i}'";
        if ($i == $minute) $html .= " selected='selected'";
        $html .= ">".str_pad($i, 2, '0', STR_PAD_LEFT)."</option>\n";
    }
    $html .= "</select>\n";

    return $html;
}


/**
 * Creates an incident popup window hyperlink
 * @author Ivan Lucas
 * @param int $incidentid. ID of the incident
 * @param string $linktext. Text to use as the hyperlink anchor
 * @param string $tooltip. Tooltip text
 * @return string the hash
*/
function html_incident_popup_link($incidentid, $linktext, $tooltip = NULL)
{
    if ($_SESSION['userconfig']['incident_popup_onewindow'] == 'FALSE')
    {
        $windowname = "incident{$incidentid}";
    }
    else
    {
        $windowname = "sit_popup";
    }
    $html = "<a href=\"javascript:incident_details_window('{$incidentid}','{$windowname}')\"";
    if (!empty($tooltip))
    {
        $html .= "class='info'";
    }
    $html .= ">{$linktext}";
    if (!empty($tooltip))
    {
        $html .= "<span>{$tooltip}</span>";
    }
    $html .= "</a>";

    return $html;
}


/**
 * Generates a HTML for a status table.
 * @author Paul Heaney
 * @param StatusEntry $statusentry The entry to represent as a table row
 * @return String the HTML row
 */
function html_status_row($statusentry)
{
    $html = "<tr><td>";
    switch ($statusentry->status)
    {
        case INSTALL_OK:
            $html .= icon('solution', 16, $GLOBALS['strSuccess']);
            break;
        case INSTALL_WARN:
            $html .= icon('warning', 16, $GLOBALS['strWarning']);
            break;
        case INSTALL_FATAL:
            $html .= icon('error', 16, $GLOBALS['strError']);
            break;
        case INSTALL_INFO:
            $html .= icon('info', 16, $GLOBALS['strInfo']);
    }

    $html .= "</td><td>{$statusentry->checkname}</td><td>{$statusentry->minimum}</td><td>{$statusentry->found}</td>";

    $html .= "</tr>";
    return $html;
}

/**
 * Checked to see if a PHP extention is installed and prints the corresponding row
 * @author Paul Heaney
 * @param String $extension The extension to check is installed
 * @param String $text The text to print describing this extension
 * @param int $min_status The minimum accepted status e.g. INSTALL_FATAL if we require this, INSTALL_WARN if its optional
 * @return int either INSTALL_OK or the $min_status
 */
function html_check_extension($extension, $text, $min_status)
{
    if (extension_loaded($extension))
    {
        $str = $GLOBALS['strInstalled'];
        $toreturn = INSTALL_OK;
    }
    else
    {
        $str = $GLOBALS['strNotInstalled'];
        $toreturn = $min_status;
    }
    echo html_status_row($toreturn, $text, $GLOBALS['strInstalled'], $str);

    return $toreturn;
}


/**
 * Function to generate and display a HTML table of the staus of the sit install
 * @param Status $status The status object to print as a table
 * @author Paul Heaney
 * @return String HTML of the table
 */
function html_install_status($status)
{
    $html = "<table class='maintable'><tr><th></th><th>{$GLOBALS['strRequirement']}</th><th>{$GLOBALS['strRequired']}</th><th>{$GLOBALS['strActual']}</th></tr>";

    foreach ($status->statusentries AS $entry)
    {
        $html .= html_status_row($entry);
    }

    $html .= "</table>";

    return $html;
}


/**
 * Creates HTML horizontal list of actions from an array of URL's
 * @author Ivan Lucas
 * @param array $actions Assoc array of Labels and URL's (labels should already be internationalised).
                format example: $actions['Label'] = 'http://example.com/page.html'
                alternative format example: $actions['Label'] = array('url => 'http://example.com/page.html', perm = PERM_FOO);
 * @return string HTML.
 */
function html_action_links($actions)
{
    $access = TRUE;
    $html .= "<span class='actionlinks'>";
    $actionscount = count($actions);
    $count = 1;
    foreach ($actions AS $label => $action)
    {
        if (is_array($action))
        {
            $url = $action['url'];
            if (!user_permission($_SESSION['userid'], $action['perm']))
            {
                $url = "{$CONFIG['application_webpath']}noaccess.php?id={$action['perm']}";
                $access = FALSE;
            }
        }
        else
        {
            $url = $action;
            $access = TRUE;
        }
        $html .= "<a href=\"{$url}\"";
        if (!$access)
        {
            $html .= " class='greyed' title=\"{$GLOBALS['strNoPermission']}\"";
        }
        $html .= ">{$label}</a>";
        $count++;
        if ($count <= $actionscount)
        {
            $html .= "<span class='separator'> | </span>";
        }
    }
    $html .= "</span>";
    return $html;
}


/**
 * Creates HTML for horizontal hierarchical menu
 * @author Ivan Lucas
 * @param array $hmenu - Hierarchical menu structure
 * @return string HTML.
 */
function html_hmenu($hmenu)
{
    global $CONFIG;
    $html = "<div id='menu'>\n";
    $html .= "<ul id='menuList'>\n";
    foreach ($hmenu[0] as $top => $topvalue)
    {
        if ((!empty($topvalue['enablevar']) AND $CONFIG[$topvalue['enablevar']] !== FALSE
            AND $CONFIG[$topvalue['enablevar']] !== 'disabled')
            OR empty($topvalue['enablevar']))
        {
            $html .= "<li class='menuitem'>";
            // Permission Required: ".permission_name($topvalue['perm'])."
            if ($topvalue['perm'] > 0 AND !in_array($topvalue['perm'], $_SESSION['permissions']))
            {
                $html .= "<a href='javascript:void(0);' class='greyed'>{$topvalue['name']}</a>";
            }
            else
            {
                $html .= "<a href='{$topvalue['url']}'>{$topvalue['name']}</a>";
            }

            if ($topvalue['submenu'] > 0 AND ($topvalue['perm'] == '' OR in_array($topvalue['perm'], $_SESSION['permissions'])))
            {
                $html .= "\n<ul>"; //  id='menuSub'
                foreach ($hmenu[$topvalue['submenu']] as $sub => $subvalue)
                {
                    if ((!empty($subvalue['enablevar']) AND $CONFIG[$subvalue['enablevar']] == TRUE
                        AND $CONFIG[$subvalue['enablevar']] !== 'disabled')
                        OR empty($subvalue['enablevar']))
                    {
                        if (array_key_exists('submenu', $subvalue) AND $subvalue['submenu'] > 0)
                        {
                            $html .= "<li class='submenu'>";
                        }
                        else
                        {
                            $html .= "<li>";
                        }

                        if ($subvalue['perm'] > 0 AND !in_array($subvalue['perm'], $_SESSION['permissions']))
                        {
                            $html .= "<a href='javascript:void(0);' class='greyed'>{$subvalue['name']}</a>";
                        }
                        else
                        {
                            $html .= "<a href=\"{$subvalue['url']}\">{$subvalue['name']}</a>";
                        }

                        if (array_key_exists('submenu', $subvalue) AND $subvalue['submenu'] > 0 AND in_array($subvalue['perm'], $_SESSION['permissions']))
                        {
                            $html .= "<ul>"; // id ='menuSubSub'
                            foreach ($hmenu[$subvalue['submenu']] as $subsub => $subsubvalue)
                            {
                                if ((!empty($subsubvalue['enablevar']) AND $CONFIG[$subsubvalue['enablevar']] == TRUE
                                    AND $CONFIG[$subsubvalue['enablevar']] !== 'disabled')
                                    OR empty($subsubvalue['enablevar']))
                                {
                                    if (array_key_exists('submenu', $subsubvalue) AND $subsubvalue['submenu'] > 0)
                                    {
                                        $html .= "<li class='submenu'>";
                                    }
                                    else
                                    {
                                        $html .= "<li>";
                                    }

                                    if ($subsubvalue['perm'] >=1 AND !in_array($subsubvalue['perm'], $_SESSION['permissions']))
                                    {
                                        $html .= "<a href=\"javascript:void(0);\" class='greyed'>{$subsubvalue['name']}</a>";
                                    }
                                    else
                                    {
                                        $html .= "<a href='{$subsubvalue['url']}'>{$subsubvalue['name']}</a>";
                                    }

                                    if (array_key_exists('submenu', $subsubvalue) AND $subsubvalue['submenu'] > 0 AND in_array($subsubvalue['perm'], $_SESSION['permissions']))
                                    {
                                        $html .= "<ul>"; // id ='menuSubSubSub'
                                        foreach ($hmenu[$subsubvalue['submenu']] as $subsubsub => $subsubsubvalue)
                                        {
                                             if ((!empty($subsubsubvalue['enablevar']) AND $CONFIG[$subsubsubvalue['enablevar']])
                                                OR empty($subsubsubvalue['enablevar']))
                                            {
                                                if ($subsubsubvalue['submenu'] > 0)
                                                {
                                                    $html .= "<li class='submenu'>";
                                                }
                                                else
                                                {
                                                    $html .= "<li>";
                                                }

                                                if ($subsubsubvalue['perm'] >=1 AND !in_array($subsubsubvalue['perm'], $_SESSION['permissions']))
                                                {
                                                    $html .= "<a href='javascript:void(0);' class='greyed'>{$subsubsubvalue['name']}</a>";
                                                }
                                                else
                                                {
                                                    $html .= "<a href='{$subsubsubvalue['url']}'>{$subsubsubvalue['name']}</a>";
                                                }
                                                $html .= "</li>\n";
                                            }
                                        }
                                        $html .= "</ul>\n";
                                    }
                                    $html .= "</li>\n";
                                }
                            }
                            $html .= "</ul>\n";
                        }
                        $html .= "</li>\n";
                    }
                }
               $html .= "</ul>\n";
            }
            $html .= "</li>\n";
        }
    }
    $html .= "</ul>\n\n";
    $html .= "</div>\n";

    return $html;
}


/**
  * Return a hyperlink to an online mapping service, as configured by $CONFIG['map_url']
  * @author Ivan Lucas
  * @param string $address, address to search for
  * @note The address parameter is url encoded and passed to the URL via the {address} psuedo-variable
*/
function map_link($address)
{
    $url = str_replace('{address}', urlencode($address), $GLOBALS['CONFIG']['map_url']);
    $link = "<span class='maplink'><a target='_blank' href=\"{$url}\">{$GLOBALS['strMap']}</a></span>";

    return $link;
}


?>