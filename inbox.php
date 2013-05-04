<?php
// inbox.php - View/Respond to incoming email
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// FIXME complete this code

require ('core.php');
$permission = PERM_UPDATE_DELETE;
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$sort = cleanvar($_REQUEST['sort']);
$order = cleanvar($_REQUEST['order']);
$filter = cleanvar($_REQUEST['filter']);
$displayid = cleanvar($_REQUEST['id']);
$action = clean_fixed_list($_REQUEST['action'], array('','delete','lock','unlock'));

if (empty($displayid))
{
    $refresh = $_SESSION['userconfig']['incident_refresh'];
}
else
{
    $refresh = 0;
}

$title = $strInbox;
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

if (empty($sort)) $sort = 'date';

function contact_info($contactid, $email, $name, $subject)
{
    global $strUnknown, $strIncidentsMulti, $strOpen, $strContact, $strEmail;

    $linktext = $strUnknown;
    $contactname = '';
    $info .= icon('email', 16, $strEmail, $strEmail);
    if (!empty($contactid))
    {
        $info .= " <a href='contact_details.php?id={$contactid}'>";
        $info .= icon('contact', 16, $strContact, $strContact);
        $info .= "</a>";
        $contactname = contact_realname($contactid);
    }
    $info .= ' ';

    if (!empty($contactname) AND $contactname != $strUnknown)
    {
        $linktext = $contactname;
    }
    elseif (!empty($name))
    {
        $linktext = "{$name}";
    }
    elseif (!empty($email))
    {
        $linktext = "{$email}";
    }
    else
    {
        $linktext .= "{$strUnknown}";
    }

    if (!empty($email))
    {
        $mailto = "mailto:{$email}";
        if (!empty($subject))
        {
            $mailto .= "?subject=".urlencode($subject);
        }
        $info .= "<a href=\"{$mailto}\" class='info'>";
    }
    $info .= $linktext;

    if (!empty($email))
    {
        $info .= "<span>";
        $info .= gravatar($email, 50, FALSE);
        $info .= "<div class='popupcontactinfo' style='float:right'>";
        if (!empty($contactid))
        {
            $info .= contact_realname($contactid) . '<br />';
            $openincidents = contact_count_open_incidents($contactid);
            if ($openincidents > 0)
            {
                $info .= "<strong>{$strOpen}</strong>: " . sprintf($strIncidentsMulti, $openincidents) . '<br />';
            }
        }
        $info .= "{$email}";
        $info .= "</div>";
        $info .= "</span>";
        $info .= "</a>";
    }

    if (!empty($contactid))
    {
        $info .= " (".contact_site($contactid).")";
    }

    return $info;
}

// Perform action on selected items
if (!empty($action))
{
    if (!is_array($_REQUEST['selected']))
    {
        $_REQUEST['selected'] = array($_REQUEST['selected']);
    }

    foreach ($_REQUEST['selected'] AS $item => $selected)
    {
        $selected = clean_int($selected);
        $tsql = "SELECT updateid, locked FROM `{$dbTempIncoming}` WHERE id={$selected}";
        $tresult = mysql_query($tsql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        if ($tresult AND mysql_num_rows($tresult) > 0)
        {
            $temp = mysql_fetch_object($tresult);

            switch ($action)
            {
                case 'delete':
                    if ($temp->locked == $sit[2])
                    {
                        // Only allow the person who has the update located delete it
                        $dsql = "DELETE FROM `{$dbUpdates}` WHERE id={$temp->updateid}";
                        mysql_query($dsql);
                        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                        $dsql = "DELETE FROM `{$dbTempIncoming}` WHERE id={$selected}";
                        mysql_query($dsql);
                        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                    }
                    break;
                case 'lock':
                    $lockeduntil = date('Y-m-d H:i:s', $now + $CONFIG['record_lock_delay']);
                    $sql = "UPDATE `{$dbTempIncoming}` SET locked='{$sit[2]}', lockeduntil='{$lockeduntil}' ";
                    $sql .= "WHERE id='{$selected}' AND (locked = 0 OR locked IS NULL)";
                    $result = mysql_query($sql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                    break;
                case 'unlock':
                    $sql = "UPDATE `{$dbTempIncoming}` AS t SET locked=NULL, lockeduntil=NULL ";
                    $sql .= "WHERE id='{$selected}' AND locked = '{$sit[2]}'";
                    $result = mysql_query($sql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                default:
                    trigger_error('Unrecognised form action', E_USER_ERROR);
            }
        }
    }
}


if (empty($displayid))
{
    if ($CONFIG['enable_inbound_mail'] == 'disabled')
    {
        echo "<p class='warning'>{$strInboundEmailIsDisabled}</p>";
    }
    else
    {
        echo "<h2>".icon('email', 32)." {$CONFIG['email_address']}: {$strInbox}</h2>";
        plugin_do('inbox');
        echo "<p align='center'>{$strIncomingEmailText}.  <a href='{$_SERVER['PHP_SELF']}'>{$strRefresh}</a></p>";
    }

    // Show list of items in inbox
    $sql = "SELECT * FROM `{$dbTempIncoming}` ";

    if (!empty($sort))
    {
        if ($order == 'a' OR $order == 'ASC' OR $order == '') $sortorder = "ASC";
        else $sortorder = "DESC";
        switch ($sort)
        {
            case 'from':
                $sql .= " ORDER BY `from` {$sortorder}";
                break;
            case 'subject':
                $sql .= " ORDER BY `subject` {$sortorder}";
                break;
            case 'date':
                $sql .= " ORDER BY `arrived` {$sortorder}";
                break;
            default:
                $sql .= " ORDER BY `id` DESC";
                break;
        }

    }
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    $countresults = mysql_num_rows($result);

    if ($countresults > 0)
    {
        echo "<form action='{$_SERVER['PHP_SELF']}' id='inboxform' name='inbox' method='post'>";
        $shade = 'shade1';
        echo "<table class='maintable' id='inboxtable'>";
        echo "<tr>";
        echo colheader('select', '', FALSE, '', '', '', '1%');
        echo colheader('from', $strFrom, $sort, $order, $filter, '', '25%');
        echo colheader('subject', $strSubject, $sort, $order, $filter);
        echo colheader('date', $strDate, $sort, $order, $filter, '', '15%');
        echo colheader('size', $strSize);
        echo "</tr>";
        while ($incoming = mysql_fetch_object($result))
        {
            $num_attachments = 0;
            if (!empty($incoming->updateid))
            {
                $usql = "SELECT * FROM `{$dbUpdates}` WHERE id = '{$incoming->updateid}' LIMIT 1";
                $uresult = mysql_query($usql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                $update = mysql_fetch_object($uresult);

                $asql = "SELECT COUNT(*) FROM `{$dbLinks}` WHERE linktype = 5 AND direction = 'left' AND origcolref = {$incoming->updateid}";
                $aresult = mysql_query($asql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                list($num_attachments) = mysql_fetch_row($aresult);
            }

            echo "<tr class='{$shade}' onclick='trow(event);'>";
            echo "<td>".html_checkbox('selected[]', FALSE, $incoming->id);
            echo "</td>";
            echo "<td>".contact_info($incoming->contactid, $incoming->from, $incoming->emailfrom, $incoming->subject)."</td>";
            echo "</td>";
            // Subject
            echo "<td>";
            if (($incoming->locked != $sit[2]) AND ($incoming->locked > 0))
            {
                echo icon('locked', 16) . ' ';
                echo sprintf($strLockedByX, user_realname($incoming->locked, TRUE));
                echo " ({$incoming->reason})";
                echo " &mdash; <a name='locked' class='info'>";
                echo htmlentities($incoming->subject,ENT_QUOTES, $GLOBALS['i18ncharset']);
            }
            else
            {
                if ($incoming->locked > 0)
                {
                    echo icon('locked', 16) . ' ';
                }
                // TODO option for popup or not (Mantis 619)
                // $url = "javascript:incident_details_window('{$incoming->id}','incomingview');";
                // $url = "incident_details.php?id={$incoming->id}&amp;win=incomingview";
                $url = "inbox.php?id={$incoming->id}";
                echo "<a href=\"{$url}\" id='update{$incoming->updateid}' class='info'";
                echo " title='{$strViewAndLockHeldEmail}'>";
                if (!empty($incoming->incident_id)) echo icon('support',16) . ' ';
                echo htmlentities($incoming->subject,ENT_QUOTES, $GLOBALS['i18ncharset']);
            }
            if (!empty($update->bodytext)) echo '<span>'.parse_updatebody(truncate_string($update->bodytext, 1024)).'</span>';
            echo "</a>";
            if ($num_attachments > 0) echo ' '.icon('attach', 16, '', "{$strAttachments}: {$num_attachments}");

            echo "</td>";
            // echo "<td><pre>".print_r($incoming,true)."</pre><hr /></td>";
            // Date
            echo "<td>";
            $arrived = mysql2date($incoming->arrived);
            // If there's no arrival time on the email we use the update timestamp
            if ($arrived == 0)
            {
                $arrived = $update->timestamp;
            }
            if (!empty($update->timestamp)) echo ldate($CONFIG['dateformat_datetime'], $arrived);
            echo "</td>";
            // Size
            echo "<td style='white-space:nowrap;'>";
            echo readable_bytes_size(mb_strlen($update->bodytext));
            echo "</td>";
            echo "</tr>";
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }

        echo "<tr>";
        // Select All
        echo "<td>".html_checkbox('item', FALSE, '', "onclick=\"checkAll('inboxform', this.checked);\"")."</td>";
        // Operation
        echo "<td colspan='*'>";
        echo "<select name='action'>";
        echo "<option value='' selected='selected'></option>";
        echo "<option value='lock'>{$strLock}</option>";
        echo "<option value='unlock'>{$strUnlock}</option>";
        echo "<option value='delete'>{$strDelete}</option>";
        // echo "<option value='assign'>{$strAssign}</option>";
        echo "</select>";
        echo "<input type='submit' value=\"{$strGo}\" />";
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        echo "</form>\n";
    }
    else
    {
        echo user_alert($strNoRecords, E_USER_NOTICE);
    }
}
else
{
    // Display single message

    $sql = "SELECT * FROM `{$dbTempIncoming}` WHERE id = {$displayid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if ($result AND mysql_num_rows($result) > 0)
    {
        $incoming = mysql_fetch_object($result);
        $usql = "SELECT * FROM `{$dbUpdates}` WHERE id = '{$incoming->updateid}' LIMIT 1";
        $uresult = mysql_query($usql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $update = mysql_fetch_object($uresult);
        echo "<div class='detailhead'>";
        echo "<div class='detaildate'>";
        if (!empty($update->timestamp)) echo date($CONFIG['dateformat_datetime'], $update->timestamp);
        echo "</div>";
        echo icon('email',16);
        if (!empty($_REQUEST['reply']))
        {
            echo " <strong>{$strReplyTo}:</strong> ";
        }
        echo " {$incoming->subject}";
        if (!empty($_REQUEST['reply']))
        {
            echo " &mdash; ";
            echo "<a href='{$_SERVER['PHP_SELF']}?id={$displayid}'>{$strView}</a>";
        }

        // Inbox item locking
        $lockedbyyou = false;

        if (!$incoming->locked)
        {
            //it's not locked, lock for this user
            $lockeduntil = date('Y-m-d H:i:s', $now + $CONFIG['record_lock_delay']);
            $sql = "UPDATE `{$dbTempIncoming}` SET locked='{$sit[2]}', lockeduntil='{$lockeduntil}' WHERE id='{$displayid}' AND (locked = 0 OR locked IS NULL)";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            $lockedbyname = $strYou;
            $lockedbyyou = true;
        }
        elseif ($incoming->locked != $sit[2])
        {
            $lockedby = $incoming->locked;
            $lockedbysql = "SELECT realname FROM `{$dbUsers}` WHERE id={$lockedby}";
            $lockedbyresult = mysql_query($lockedbysql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            while ($row = mysql_fetch_object($lockedbyresult))
            {
                $lockedbyname = $row->realname;
            }
        }
        else
        {
            $lockedbyname = $strYou;
            $lockedbyyou = true;
        }

        if (empty($_REQUEST['reply']))
        {
            echo " &mdash; ";
            echo "<a href='{$_SERVER['PHP_SELF']}?id={$displayid}&reply=true'>{$strReply}</a>";
            echo " | <a href='{$_SERVER['PHP_SELF']}?action=delete&amp;selected={$displayid}'>{$strDelete}</a>"; // FIXME
            if (!$incoming->locked)
            {
                echo " | <a href='{$_SERVER['PHP_SELF']}?id={$displayid}&amp;action=lock&amp;selected={$displayid}'>{$strLock}</a>"; // FIXME
            }
            elseif ($lockedbyyou)
            {
                echo " | <a href='{$_SERVER['PHP_SELF']}?id={$displayid}&amp;action=unlock&amp;selected={$displayid}'>{$strUnlock}</a>"; // FIXME
            }
            echo " | <a href=\"incident_new.php?action=findcontact&amp;incomingid={$displayid}&amp;search_string=".urlencode("{$incoming->forenames} {$incoming->surname}")."&amp;from={$incoming->emailfrom}&amp;contactid={$incoming->contactid}&amp;win=incomingcreate\" title=\"{$strCreateAnIncident}\">{$strCreateNewIncident}</a>"; // FIXME
            echo " | <a href=\"move_update.php?id={$displayid}&amp;updateid={$incoming->updateid}&amp;contactid={$incoming->contactid}&amp;win=incomingview\" title=\"{$strUpdateIncident}\">";
            echo "{$strMoveToIncident}</a>"; // FIXME needs help

            if ($lockedbyyou)
            {
                echo "<div class='detaildate'>";
                echo "<form method='post' action='{$_SERVER['PHP_SELF']}?id={$displayid}&win=incomingview&action=updatereason'>";
                echo "{$strMessage}: <input name='newreason' type='text' value=\"{$incoming->reason}\" size='25' maxlength='100' />";
                echo "<input type='submit' value='{$strSave}' />";
                echo "</form>";
                echo "</div>";
            }
            else
            {
                echo "<div class='detaildate'>{$incoming->reason}</div>";
            }
        }
        echo "</div>";
        // Reply form
        echo "<div class='detailentry'>\n";
        if (!empty($_REQUEST['reply']))
        {
            echo "{$strSubject}: <input type='text' value=\"Re: {$incoming->subject}\" size='40' />";
            echo "<textarea style='width: 98%' rows='30'>";
            echo quote_message($update->bodytext);
            echo "</textarea>";
        }
        else
        {
            echo parse_updatebody($update->bodytext, FALSE);
        }
        echo "</div>";
        echo "<p><a href='{$_SERVER['PHP_SELF']}'>&lt; {$strBackToList}</a></p>";
    }
    else
    {
        user_alert($strNoRecords, E_USER_NOTICE);
    }
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>