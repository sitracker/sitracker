<?php
// triggers.php - Page for setting user trigger preferences
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Kieran Hogg <kieran[at]sitracker.org>
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


$permission = 71;
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$adminuser = user_permission($sit[2],22); // Admin user

// External vars
// We only allow setting the selecteduser if the user is an admin, otherwise we use self
if ($adminuser AND is_numeric($_REQUEST['user']))
{
    $selecteduser = $_REQUEST['user'];
}
else
{
    $selecteduser = $_SESSION['userid'];
}

if ($_GET['user'] == '0')
{
    $title = $strSystemActions;
}
else
{
    $title = $strNotifications;
}

switch ($_REQUEST['mode'])
{
    case 'delete':
        $id = cleanvar($_GET['id']);
        if (!is_numeric($id))
        {
            html_redirect($_SERVER['PHP_SELF'], FALSE);
        }
        $triggerowner = db_read_column('userid', $dbTriggers, $id);
        if ($triggerowner == 0 AND !user_permission($sit[2], 72))
        {
            html_redirect($_SERVER['PHP_SELF']."?user={$selecteduser}", FALSE, $strPermissionDenied);
        }
        elseif ($triggerowner != 0 AND $triggerowner != $sit[2] AND !user_permission($sit[2], 72))
        {
            html_redirect($_SERVER['PHP_SELF']."?user={$selecteduser}", FALSE, $strPermissionDenied);
        }
        elseif ($triggerowner == $sit[2] AND !user_permission($sit[2], 71))
        {
            html_redirect($_SERVER['PHP_SELF']."?user={$selecteduser}", FALSE, $strPermissionDenied);
        }
        else
        {
            $sql = "DELETE FROM `{$dbTriggers}` WHERE id = $id LIMIT 1";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            if (mysql_affected_rows() >= 1)
            {
                html_redirect($_SERVER['PHP_SELF']."?user={$selecteduser}");
            }
            else
            {
                html_redirect($_SERVER['PHP_SELF']."?user={$selecteduser}", FALSE);
            }
        }
        break;

    case 'new':
        $id = cleanvar($_GET['id']);
        // Check that this is a defined trigger
        if (!array_key_exists($id, $triggerarray))
        {
            html_redirect($_SERVER['PHP_SELF'], FALSE);
            exit;
        }

        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        ?>
        <script type="text/javascript">
        //<![CDATA[

        function insertRuletext(tvar)
        {
            tvar = tvar + ' ';
            var start = $('rules').selectionStart;
            var end = $('rules').selectionEnd;
            $('rules').value = $('rules').value.substring(0, start) + tvar + $('rules').value.substring(end, $('rules').textLength);
        }

        function resetRules()
        {
            $('rules').value = '';
        }

        function switch_template()
        {
            if ($('new_action').value == 'ACTION_NOTICE')
            {
                $('noticetemplatesbox').show();
//                 $('parametersbox').show();
                $('emailtemplatesbox').hide();
                $('journalbox').hide();
                $('none').hide();
                $('rulessection').show();
            }
            else if ($('new_action').value == 'ACTION_EMAIL')
            {
                $('emailtemplatesbox').show();
//                 $('parametersbox').show();
                $('noticetemplatesbox').hide();
                $('journalbox').hide();
                $('none').hide();
                $('rulessection').show();

            }
            else if ($('new_action').value == 'ACTION_JOURNAL')
            {
//                 $('parametersbox').show();
                $('journalbox').show();
                $('emailtemplatesbox').hide();
                $('noticetemplatesbox').hide();
                $('none').hide();
            }
            else
            {
                $('noticetemplatesbox').hide();
                $('emailtemplatesbox').hide();
//                 $('parametersbox').hide();
                $('journalbox').hide();
                $('none').show();
                $('rulessection').hide();

            }
        }
        //]]>
        </script>
        <?php
        echo "<h2>".icon('triggeraction', 32)." ";

        if ($selecteduser >= 1)
        {
            echo user_realname($selecteduser).': ';
        }

        echo "{$strTriggerActions}</h2>";

        if (!empty($triggerarray[$id]['name']))
        {
            $name = $triggerarray[$id]['name'];
        }
        else
        {
            $name = $id;
        }
        echo "<div id='container'>";
        echo "<h3>{$strTrigger}</h3>";
        echo "<strong>{$name}</strong>";

        echo "<h3>{$strOccurance}</h3>";
        echo $GLOBALS[$triggerarray[$id]['description']];

        echo "<h3>{$strAction}</h3>";
        echo "<form name='addtrigger' action='{$_SERVER['PHP_SELF']}' method='post'>";
        // echo "<th>Extra {$strParameters}</th>";
        // FIXME extra, rules

        // ACTION_NOTICE is only applicable when a userid is specified or for 'all'
        echo "<select name='new_action' id='new_action' onchange='switch_template();'>";
        echo "<option value='ACTION_NONE'>{$strNone}</option>\n";
        echo "<option value='ACTION_EMAIL'>{$strEmail}</option>\n";
        if ($selecteduser != 0)
        {
            echo "<option value='ACTION_NOTICE'>{$strNotice}</option>\n";
        }
        echo "<option value='ACTION_JOURNAL'>{$strJournal}</option>\n";
        echo "</select>";
        echo "<h3>{$strTemplate}</h3>";
        echo "<div id='noticetemplatesbox' style='display:none;'>";
        echo notice_templates('noticetemplate');
        echo "</div>\n";
        echo "<div id='emailtemplatesbox' style='display:none;'>";
        if ($selecteduser == 0)
        {
            echo email_templates('emailtemplate', 'system');
        }
        else
        {
            echo email_templates('emailtemplate', 'user');
        }
        echo "</div>\n";
        echo "<div id='journalbox' style='display:none;'>{$strNone}</div>";
        echo "<div id='none'>{$strNone}</div>";
//         echo "<td><div id='parametersbox' style='display:none;'><input type='text' name='parameters' size='30' /></div></td>";

        echo "<div id='rulessection' style='display:none'>";
        echo "<h3><label for='rules'>{$strRules}</label></h3>";
        if (is_array($triggerarray[$id]['params']))
        {
            echo "{$strTheFollowingVariables}<br /><br />";
            echo "<div class='bbcode_toolbar' id='paramlist'>";
            // Add built in params
            $triggerarray[$id]['params'][] = 'currentuserid';
            foreach ($triggerarray[$id]['params'] AS $param)
            {
                $replace = "{".$param."}";
                $linktitle = $ttvararray[$replace]['description'];
                echo "<a href='javascript:void(0);' title=\"{$linktitle}\" onclick=\"insertRuletext('{{$param}}');\">{{$param}}</a> ";
            }
            $compoperators = array('==', '!=', '<', '>', '<=', '>=');
            foreach ($compoperators AS $op)
            {
                echo "<var><strong><a href='javascript:void(0);' onclick=\"insertRuletext('{$op}');\">".htmlentities($op)."</a></strong></var> ";
            }
            $logicaloperators = array('OR', 'AND');
            foreach ($logicaloperators AS $op)
            {
                echo "<var><strong><a href='javascript:void(0);' onclick=\"insertRuletext('{$op}');\">{$op}</a></strong></var> ";
            }
            $values = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 'TRUE', 'FALSE');
            foreach ($values AS $value)
            {
                echo "<var><strong><a href='javascript:void(0);' onclick=\"insertRuletext('{$value}');\">{$value}</a></strong></var> ";
            }
            echo "</div>";
            echo "<textarea cols='30' rows='5' id='rules' name='rules' readonly='readonly'></textarea><br />";
            echo "<a href='javascript:void(0);' onclick='resetRules();'>{$strReset}</a>";
        }
        else
        {
            echo "{$strRulesNotDefinable}";
        }
        echo "</div>";
        echo "<input type='hidden' name='mode' value='save' />";
        echo "<input type='hidden' name='id' value='{$id}' />";
        echo "<input type='hidden' name='user' value='{$selecteduser}' />";
        echo "<p><input type='submit' value=\"{$strSave}\" /></p>";
        echo "</form>";

        echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}'>{$strBackToList}</a></p>\n";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        break;

    case 'save':
        $id = cleanvar($_POST['id']);
        $userid = cleanvar($_POST['user']);
        // Check that this is a defined trigger
        if (!array_key_exists($id, $triggerarray))
        {
            html_redirect($_SERVER['PHP_SELF'], FALSE);
            exit;
        }
        $action = cleanvar($_POST['new_action']);
        $noticetemplate = cleanvar($_POST['noticetemplate']);
        $emailtemplate = cleanvar($_POST['emailtemplate']);
        $parameters = cleanvar($_POST['parameters']);
        $rules = cleanvar($_POST['rules']);
        $rules = str_replace("(", "", $rules);
        $rules = str_replace(")", "", $rules);

        //TODO if we do more than one of these, we should probably use
        //trigger_replace_specials()
        $rules = str_replace("{currentuserid}", $sit[2], $rules);

        if ($action == 'ACTION_NOTICE')
        {
            $templateid = $noticetemplate;
        }
        elseif ($action == 'ACTION_EMAIL')
        {
            $templateid = $emailtemplate;
        }
        else
        {
            $templateid = 0;
        }

        //check if we already have this trigger
        if(check_trigger_exists($id, $userid, $action, $templateid, $rules, $parameters))
        {
            html_redirect($_SERVER['PHP_SELF'], FALSE, $strADuplicateAlreadyExists);
        }
        else
        {
            $sql = "INSERT INTO `{$dbTriggers}` (triggerid, userid, action, template, parameters, checks) ";
            $sql .= "VALUES ('{$id}', '{$userid}', '{$action}', '{$templateid}', '{$parameters}', '{$rules}')";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            else html_redirect($_SERVER['PHP_SELF'], TRUE);
        }
        break;
    case 'list':
    default:
        //display the list
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');

        echo "<h2>".icon('trigger', 32)." ";
        echo "$title</h2>";
        echo "<p align='center'>{$strAListOfAvailableTriggers}</p>";

        if ($_GET['user'] != '0')
        {
            if ($adminuser)
            {
                $sql  = "SELECT id, realname FROM `{$dbUsers}` WHERE status > 0 ";
                $sql .= "ORDER BY realname ASC";
                $result = mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

                $userarr[-1] = $strAll;
                //$userarr[0] = $CONFIG['application_shortname'];

                while ($userobj = mysql_fetch_object($result))
                {
                    $userarr[$userobj->id] = $userobj->realname;
                }
                echo "<form action=''>";
                echo "<p>{$strUser}: ";
                echo array_drop_down($userarr, 'user', $selecteduser,
                                    "onchange=\"window.location.href='".
                                    "{$_SERVER['PHP_SELF']}?user=' + ".
                                    "this.options[this.selectedIndex].value;\"",
                                    TRUE);
                echo "</p></form>\n";
            }
            else
            {
                // User has no admin rights so force the selection to the current user
                $selecteduser = $sit[2];
            }
        }
        echo "<table class='maintable'><tr><th>{$strTrigger}</th>";
        echo "<th>{$strActions}</th><th>{$strOperation}</th></tr>\n";

        $shade = 'shade1';
        foreach ($triggerarray AS $trigger => $triggervar)
        {
            $perm = TRUE;
            //echo $trigger;
            if (isset($triggervar['permission']))
            {
                //$triggervar['permission'] = trigger_replace_specials($trigger, $triggervar['permission'], array());
                eval("\$res = {$triggervar['permission']}");
                if (!$res)
                {
                    $perm = FALSE;
                }
            }
            if ($perm)
            {
                if (($triggervar['type'] != 'system' AND !$adminuser) OR $adminuser)
                {
                    echo "<tr class='$shade'>";
                    echo "<td style='vertical-align: top; width: 25%;'>";
                    echo trigger_description($triggervar);
                    echo "</td>";
                    // List actions for this trigger
                    echo "<td>";
                    $sql = "SELECT * FROM `{$dbTriggers}` WHERE triggerid = '$trigger' ";
                    if ($selecteduser > -1)
                    {
                        $sql .= "AND userid = '{$selecteduser}' ";
                    }
                    $sql .= "ORDER BY action, template ";
                    if (!$adminuser)
                    {
                        $sql .= "AND userid='{$sit[2]}'";
                    }
                    $result = mysql_query($sql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                    if (mysql_num_rows($result) >= 1)
                    {
                        while ($trigaction = mysql_fetch_object($result))
                        {
                            echo triggeraction_description($trigaction, TRUE);

                            echo " <a href='{$_SERVER['PHP_SELF']}?mode=delete&amp;";
                            echo "id={$trigaction->id}' title=\"{$strDelete}\">";
                            echo icon('delete', 12)."</a>";
                            if ($selecteduser == -1)
                            {
                                if ($trigaction->userid == 0)
                                {
                                    echo " (<img src='{$CONFIG['application_webpath']}";
                                    echo "images/sit_favicon.png' />)";
                                }
                                else
                                {
                                    echo " (".icon('user', 16)." ";
                                    echo user_realname($trigaction->userid).')';
                                }
                            }
                            echo "<br />\n";
                        }
                    }
                    else
                    {
                        echo "{$strNone}";
                    }
                    echo "</td>";
                    echo "<td>";
                    if ($selecteduser != -1)
                    {
                        echo "<a href='{$_SERVER['PHP_SELF']}?mode=add&amp;id={$trigger}&amp;user={$selecteduser}'>{$strNewAction}</a>";
                    }
                    echo "</td>";
                    echo "</tr>\n";
                    if ($shade == 'shade1')
                    {
                        $shade = 'shade2';
                    }
                    else
                    {
                        $shade = 'shade1';
                    }
                }
            }
        }
        echo "</table>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>