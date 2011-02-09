<?php
// templates.php - Manage email and notice templates
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


$permission = 17; // Edit Template

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// FIXME i18n Whole page

// External variables
$id = cleanvar($_REQUEST['id']);
$action = $_REQUEST['action'];
$templatetype = cleanvar($_REQUEST['template']);

if (empty($action) OR $action == 'showform' OR $action == 'list')
{
    // Show select email type form
    $title = $strTemplates;
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('templates', 32)." ";
    echo "{$strTemplates}</h2>";
    echo "<p align='center'><a href='triggers.php'>{$strTriggers}</a> | <a href='template_new.php'>{$strNewTemplate}</a></p>";

    $sql = "SELECT * FROM `{$dbEmailTemplates}` ORDER BY id";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    while ($email = mysql_fetch_object($result))
    {
        $templates[$email->id] = array('id' => $email->id, 'template' => 'email', 'name'=> $email->name,'type' => $email->type, 'desc' => $email->description);
    }
    $sql = "SELECT * FROM `{$dbNoticeTemplates}` ORDER BY id";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    while ($notice = mysql_fetch_object($result))
    {
        $templates[$notice->name] = array('id' => $notice->id, 'template' => 'notice', 'name'=> $notice->name, 'type' => $notice->type, 'desc' => $notice->description);
    }
    ksort($templates);
    $shade='shade1';
    echo "<table align='center'>";
    echo "<tr><th>{$strType}</th><th>{$strUsed}</th><th>{$strTemplate}</th><th>{$strOperation}</th></tr>";
    foreach ($templates AS $template)
    {
        $system = FALSE;
        $tsql = "SELECT COUNT(id) FROM `{$dbTriggers}` WHERE template='{$template['name']}'";
        $tresult = mysql_query($tsql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        list($numtriggers) = mysql_fetch_row($tresult);

        $editurl = "{$_SERVER['PHP_SELF']}?id={$template['id']}&amp;action=edit&amp;template={$template['template']}";
        if ($numtriggers < 1 AND ($template['template'] == 'email' AND $template['type'] != 'incident')) $shade = 'expired';
        echo "<tr class='{$shade}'>";
        echo "<td>";
        if ($template['template'] == 'notice')
        {
            echo icon('info', 16).' '.$strNotice;
        }
        elseif ($template['template'] == 'email')
        {
            echo icon('email', 16).' '.$strEmail;
        }
        else
        {
            echo $strOther;
        }
        echo "</td>";
        //echo "<td>{$template['type']} {$template['template']}</td>";
        echo "<td>";
        if ($template['template'] == 'email' AND $template['type'] == 'incident')
        {
            echo icon('support',16, $strIncident);
        }
        if ($numtriggers > 0) echo icon('trigger',16, $strTrigger);
        if ($numtriggers > 1) echo " (&#215;{$numtriggers})";
        echo "</td>";
        echo "<td><a href='{$editurl}'>{$template['name']}</a>";
        if (!empty($template['desc']))
        {
            if (substr_compare($template['desc'], 'str', 0, 3) === 0)
            {
                echo "<br />{$GLOBALS[$template['desc']]}";
                $system = TRUE;
            }
            else
            {
                echo "<br />{$template['desc']}";
            }
        }
        echo "</td>";
        if (!$system)
        {
            echo "<td><a href='{$editurl}'>{$strEdit}</a></td>";
        }
        else
        {
            echo "<td></td>";
        }
        echo "</tr>\n";
        if ($shade == 'shade1') $shade = 'shade2';
        else $shade = 'shade1';
    }
    echo "</table>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == "edit")
{
    ?>
    <script type='text/javascript'>
    //<![CDATA[

    /**
      * @author Ivan Lucas
    **/
    function recordFocusElement(element)
    {
        $('focuselement').value = element.identify();
        $('templatevariables').show();
    }

    /**
      * @author Ivan Lucas
    **/
    function clearFocusElement()
    {
        $('focuselement').value = '';
        $('templatevariables').hide();
    }

    /**
      * @author Ivan Lucas
    **/
    function insertTemplateVar(tvar)
    {
        var element = $('focuselement').value;
        if (element.length > 0)
        {
            var start = $(element).selectionStart;
            var end = $(element).selectionEnd;
//             alert('start:' + start + '  end: ' + end + 'len: ' + $(element).textLength);
            if ($(element).readAttribute('readonly') != 'readonly')
            {
                $(element).value = $(element).value.substring(0, start) + tvar + $(element).value.substring(end, $(element).textLength);
            }
        }
        else
        {
            alert('<?php echo $strSelectAFieldForTemplates?>');
        }
    }
//]]>
</script>
    <?php


    // Retrieve the template from the database, whether it's email or notice
    switch ($templatetype)
    {
        case 'email':
            if (!is_numeric($id)) $sql = "SELECT * FROM `{$dbEmailTemplates}` WHERE name='$id' LIMIT 1";
            else $sql = "SELECT * FROM `{$dbEmailTemplates}` WHERE id='$id'";
            $title = "{$strEdit}: $strEmailTemplate";
            $templateaction = 'ACTION_EMAIL';
            break;
        case 'notice':
        default:
            if (!is_numeric($id)) $sql = "SELECT * FROM `{$dbNoticeTemplates}` WHERE name='$id' LIMIT 1";
            else $sql = "SELECT * FROM `{$dbNoticeTemplates}` WHERE id='$id' LIMIT 1";
            $title = "{$strEdit}: {$strNoticeTemplate}";
            $templateaction = 'ACTION_NOTICE';
    }
    $result = mysql_query($sql);
    $template = mysql_fetch_object($result);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    if (mysql_num_rows($result) > 0)
    {
        echo "<h2>{$title}</h2>";
        echo "<p align='center'>".sprintf($strMandatoryMarked,"<sup class='red'>*</sup>")."</p>";
        echo "<div style='width: 48%; float: left;'>";
        echo "<form name='edittemplate' action='{$_SERVER['PHP_SELF']}?action=update' method='post' onsubmit=\"return confirm_action('{$strAreYouSureMakeTheseChanges}')\">";
        echo "<table class='vertical' width='100%'>";

        $tsql = "SELECT * FROM `{$dbTriggers}` WHERE action = '{$templateaction}' AND template = '$id' LIMIT 1";
        $tresult = mysql_query($tsql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($tresult) >= 1)
        {
            $trigaction = mysql_fetch_object($tresult);
            echo "<tr><th>{$strTrigger}</th><td>".trigger_description($triggerarray[$trigaction->triggerid])."<br /><br />";
            echo triggeraction_description($trigaction)."</td></tr>";
        }
        else
        {
            echo "<tr><th>{$strTrigger}</th><td>{$strNone}</td></tr>\n";
        }

        // Set template type to the trigger type if no type is already specified
        if (empty($template->type)) $template->type = $triggerarray[$trigaction->triggerid]['type'];


        echo "<tr><th>{$strID}: <sup class='red'>*</sup></th><td>";
        echo "<input maxlength='50' name='name' size='5' value='{$template->id} 'readonly='readonly' disabled='disabled' /></td></tr>\n";
        echo "<tr><th>{$strTemplateType}:</th><td>";
        if ($templatetype == 'notice')
        {
            echo icon('info', 32).' '.$strNotice;
        }
        elseif ($templatetype == 'email')
        {
            echo icon('email', 32).' '.$strEmail;
        }
        else
        {
            echo $strOther;
        }
        // Set up required params, each template type needs an entry here TODO add the rest
        if ($template->type == 'user') $required = array('incidentid', 'userid');
        elseif ($template->type == 'incident') $required = array('incidentid', 'triggeruserid');
        else $required = $triggerarray[$trigaction->triggerid]['required'];
        if (!empty($required) AND $CONFIG['debug'])
        {
            debug_log("Variables required by email template {$template->id}: ".print_r($required, TRUE));
        }
        echo "</td><tr>";

        echo "<tr><th>{$strTemplate}: <sup class='red'>*</sup></th><td><input maxlength='100' name='name' size='40' value=\"{$template->name}\" /></td></tr>\n";
        echo "<tr><th>{$strDescription}: <sup class='red'>*</sup></th>";
        echo "<td><textarea name='description' cols='50' rows='5' onfocus=\"clearFocusElement(this);\"";
        if (strlen($template->description) > 3 AND substr_compare($template->description, 'str', 0, 3) === 0)
        {
             echo " readonly='readonly' ";
             $template->description = ${$template->description};
         }
        echo ">{$template->description}</textarea></td></tr>\n";
        switch ($templatetype)
        {
            case 'email':
                echo "<tr><th colspan='2'>{$strEmail}</th></tr>";
                echo "<tr><td colspan='2'>{$strTemplatesShouldNotBeginWith}</td></tr>";
                echo "<tr><th>{$strTo} <sup class='red'>*</sup></th>";
                echo "<td><input id='tofield' maxlength='100' name='tofield' size='40' value=\"{$template->tofield}\" onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                echo "<tr><th>{$strFrom} <sup class='red'>*</sup></th>";
                echo "<td><input id='fromfield' maxlength='100' name='fromfield' size='40' value=\"{$template->fromfield}\" onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                echo "<tr><th>{$strReplyTo} <sup class='red'>*</sup></th>";
                echo "<td><input id='replytofield' maxlength='100' name='replytofield' size='40' value=\"{$template->replytofield}\" onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                echo "<tr><th>{$strCC}</th>";
                echo "<td><input id='ccfield' maxlength='100' name='ccfield' size='40' value=\"{$template->ccfield}\" onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                echo "<tr><th>{$strBCC}</th>";
                echo "<td><input id='bccfield' maxlength='100' name='bccfield' size='40' value=\"{$template->bccfield}\" onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                echo "<tr><th>{$strSubject}</th>";
                echo "<td><input id='subject' maxlength='255' name='subjectfield' size='60' value=\"{$template->subjectfield}\" onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                break;
            case 'notice':
                echo "<tr><th>{$strLinkText}</th>";
                echo "<td><input id='linktext' maxlength='50' name='linktext' size='50' ";
                if (strlen($template->linktext) > 3 AND substr_compare($template->linktext, 'str', 0, 3) === 0)
                {
                    echo " readonly='readonly' ";
                    $template->linktext = $SYSLANG[$template->linktext];
                }
                echo "value=\"{$template->linktext}\" onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                echo "<tr><th>{$strLink}</th>";
                echo "<td><input id='link' maxlength='100' name='link' size='50' value=\"{$template->link}\"  onfocus=\"recordFocusElement(this);\" /></td></tr>\n";
                echo "<tr><th>{$strDurability}</th>";
                echo "<td><select id='durability' onfocus=\"recordFocusElement(this);\">";
                echo "<option";
                if ($template->durability == 'sticky')
                {
                    echo " checked='checked' ";
                }
                echo ">sticky</option>";
                echo "<option";
                if ($template->durability == 'session')
                {
                    echo " checked='checked' ";
                }
                echo ">session</option>";
                echo "</option></select>";
        }

        //if ($trigaction AND $template->type != $triggerarray[$trigaction->triggerid]['type']) echo "<p class='warning'>Trigger type mismatch</p>";
        echo "</td></tr>\n";


        if ($templatetype == 'email') $body = $template->body;
        else $body = $template->text;
        echo "<tr><th>{$strText}</th>";
        echo "<td>";
        if ($templatetype == 'notice') echo bbcode_toolbar('bodytext');

        echo "<textarea id='bodytext' name='bodytext' rows='20' cols='50' onfocus=\"recordFocusElement(this);\"";
        if (strlen($body) > 3 AND substr_compare($body, 'str', 0, 3) === 0)
        {
            echo " readonly='readonly' ";
            $body = $SYSLANG[$body];
        }
        echo ">{$body}</textarea></td>";

        if ($template->type == 'incident')
        {
            echo "<tr><th></th><td><label><input type='checkbox' name='storeinlog' value='Yes' ";
            if ($template->storeinlog == 'Yes')
            {
                echo "checked='checked'";
            }
            echo " /> {$strStoreInLog}</label>";
            echo " &nbsp; (<input type='checkbox' name='cust_vis' value='yes' ";
            if ($template->customervisibility == 'show')
            {
                echo "checked='checked'";
            }
            echo " /> {$strVisibleToCustomer})";
            echo "</td></tr>\n";
        }
        echo "</table>\n";

        echo "<p>";
        echo "<input name='type' type='hidden' value='{$template->type}' />";
        echo "<input name='template' type='hidden' value='{$templatetype}' />";
        echo "<input name='focuselement' id='focuselement' type='hidden' value='' />";
        echo "<input name='id' type='hidden' value='{$id}' />";
        echo "<input name='submit' type='submit' value=\"{$strSave}\" />";
        echo "</p>\n";
        // FIXME when to allow deletion?
        if ($template->type == 'user')
        {
            echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?action=delete&amp;id={$id}'>{$strDelete}</a></p>";
        }
        echo "</form>";
        echo "</div>";

        // FIXME i18n email templates
        // Show a list of available template variables.  Only variables that have 'requires' matching the 'required'
        // that the trigger provides is shown
        echo "<div id='templatevariables' style='display:none;'>";
        echo "<h4>{$strTemplateVariables}</h4>";
        echo "<p align='center'>{$strFollowingSpecialIdentifiers}</p>";
        if (!is_array($required)) echo "<p class='info'>{$strSomeOfTheseIdentifiers}</p>";

        echo "<dl>";

        foreach ($ttvararray AS $identifier => $ttvar)
        {
            $showtvar = FALSE;

            // if we're a multiply-defined variable, get the actual data
            if (!isset($ttvar['name']) AND !isset($ttvar['description']))
            {
                $ttvar = $ttvar[0];
            }

            if (empty($ttvar['requires']) AND $ttvar['show'] !== FALSE)
            {
                $showtvar = TRUE;
            }
            elseif ($ttvar['show'] === FALSE)
            {
                $showtvar = FALSE;
            }
            else
            {
                if (!is_array($ttvar['requires'])) $ttvar['requires'] = array($ttvar['requires']);
                foreach ($ttvar['requires'] as $needle)
                {
                    if (!is_array($required) OR in_array($needle, $required)) $showtvar = TRUE;
                }
            }

            if ($showtvar)
            {
                echo "<dt><code><a href=\"javascript:insertTemplateVar('{$identifier}');\">{$identifier}</a></code></dt>";
                if (!empty($ttvar['description'])) echo "<dd>{$ttvar['description']}";
                {
                    if (!empty($ttvar[0]['description'])) echo "<dd>{$ttvar[0]['description']}";
                }
                echo "<br />";
            }
        }

        echo "</dl>";
        plugin_do('emailtemplate_list');
        echo "</table>\n";
        echo "</div>";

        echo "<p style='clear:both; margin-top: 2em;' align='center'><a href='{$_SERVER['PHP_SELF']}'>{$strBackToList}</a></p>";

        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
    else
    {
        echo user_alert(sprintf($strFieldMustNotBeBlank, "'{$strEmailTemplate}'"), E_USER_ERROR);
    }
}
elseif ($action == "delete")
{
    if (empty($id) OR is_numeric($id) == FALSE)
    {
        // id must be filled and be a number
        header("Location: {$_SERVER['PHP_SELF']}?action=showform");
        exit;
    }
    // We only allow user templates to be deleted
    $sql = "DELETE FROM `{$dbEmailTemplates}` WHERE id='$id' AND type='user' LIMIT 1";
    mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
    header("Location: {$_SERVER['PHP_SELF']}?action=showform");
    exit;
}
elseif ($action == "update")
{
    // External variables
    $template = cleanvar($_POST['template']);
    $name = cleanvar($_POST['name']);
    $description = cleanvar($_POST['description']);

    $tofield = cleanvar($_POST['tofield']);
    $fromfield = cleanvar($_POST['fromfield']);
    $replytofield = cleanvar($_POST['replytofield']);
    $ccfield = cleanvar($_POST['ccfield']);
    $bccfield = cleanvar($_POST['bccfield']);
    $subjectfield = cleanvar($_POST['subjectfield']);
    $bodytext = cleanvar($_POST['bodytext']);

    $link = cleanvar($_POST['link']);
    $linktext = cleanvar($_POST['linktext']);
    $durability = cleanvar($_POST['durability']);

    $cust_vis = cleanvar($_POST['cust_vis']);
    $storeinlog = cleanvar($_POST['storeinlog']);
    $id = cleanvar($_POST['id']);
    $type = cleanvar($_POST['type']);

//     echo "<pre>".print_r($_POST,true)."</pre>";

    // User templates may not have _ (underscore) in their names, we replace with spaces
    // in contrast system templates must have _ (underscore) instead of spaces, so we do a replace
    // the other way around for those
    // We do this to help prevent user templates having names that clash with system templates
    if ($type == 'user') $name = str_replace('_', ' ', $name);
    else $name = str_replace(' ', '_', strtoupper(trim($name)));

    if ($cust_vis == 'yes') $cust_vis = 'show';
    else $cust_vis = 'hide';

    if ($storeinlog == 'Yes') $storeinlog = 'Yes';
    else $storeinlog = 'No';

    switch ($template)
    {
        case 'email':
            $sql  = "UPDATE `{$dbEmailTemplates}` SET name='{$name}', description='{$description}', tofield='{$tofield}', fromfield='{$fromfield}', ";
            $sql .= "replytofield='{$replytofield}', ccfield='{$ccfield}', bccfield='{$bccfield}', subjectfield='{$subjectfield}', ";
            $sql .= "body='{$bodytext}', customervisibility='{$cust_vis}', storeinlog='{$storeinlog}' ";
            $sql .= "WHERE id='$id' LIMIT 1";
            break;
        case 'notice':
            $sql  = "UPDATE `{$dbNoticeTemplates}` SET name='{$name}', description='{$description}', type='".USER_DEFINED_NOTICE_TYPE."', ";
            $sql .= "linktext='{$linktext}', link='{$link}', durability='{$durability}', ";
            $sql .= "text='{$bodytext}' ";
            $sql .= "WHERE id='{$id}' LIMIT 1";
            break;
        default:
            trigger_error('Error: Invalid template type', E_USER_WARNING);
            html_redirect($_SERVER['PHP_SELF'], FALSE);
    }

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    if ($result)
    {
        journal(CFG_LOGGING_NORMAL, 'Email Template Updated', "Email Template {$type} was modified", CFG_JOURNAL_ADMIN, $type);
        html_redirect($_SERVER['PHP_SELF']);
    }
    else
    {
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}
?>