<?php
// inbox.php - View/Respond to incoming email
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

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
$action = clean_fixed_list($_REQUEST['action'], array('','delete','lock','unlock','updatereason'));

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
        $tresult = mysqli_query($db, $tsql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        if ($tresult AND mysqli_num_rows($tresult) > 0)
        {
            $temp = mysqli_fetch_object($tresult);

            switch ($action)
            {
                case 'delete':
                    if ($temp->locked == $sit[2] OR empty($temp->locked))
                    {
                        // Only allow the person who has the update located delete it
                        $dsql = "DELETE FROM `{$dbUpdates}` WHERE id={$temp->updateid}";
                        mysqli_query($db, $dsql);
                        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
                        $dsql = "DELETE FROM `{$dbTempIncoming}` WHERE id={$selected}";
                        mysqli_query($db, $dsql);
                        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
                    }
                    break;

                case 'lock':
                    $lockeduntil = date('Y-m-d H:i:s', $now + $CONFIG['record_lock_delay']);
                    $sql = "UPDATE `{$dbTempIncoming}` SET locked='{$sit[2]}', lockeduntil='{$lockeduntil}' ";
                    $sql .= "WHERE id='{$selected}' AND (locked = 0 OR locked IS NULL)";
                    $result = mysqli_query($db, $sql);
                    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
                    break;

                case 'unlock':
                    $sql = "UPDATE `{$dbTempIncoming}` AS t SET locked=NULL, lockeduntil=NULL ";
                    $sql .= "WHERE id='{$selected}' AND locked = '{$sit[2]}'";
                    $result = mysqli_query($db, $sql);
                    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
                    $displayid = null;
                    break;

                case 'updatereason':
                    $newreason = clean_dbstring($_REQUEST['newreason']);
                    $updatetime = date('Y-m-d H:i:s',$now);
                    $update = "UPDATE `{$dbTempIncoming}` SET reason='{$newreason}', ";
                    $update .= "reason_user='{$sit['2']}', reason_time='{$updatetime}' WHERE id={$selected}";
                    mysqli_query($db, $update);
                    if (mysqli_error($db)) trigger_error(mysqli_error($db),E_USER_ERROR);
                    break;
                    
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
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $countresults = mysqli_num_rows($result);

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
        while ($incoming = mysqli_fetch_object($result))
        {
            $num_attachments = 0;
            if (!empty($incoming->updateid))
            {
                $usql = "SELECT * FROM `{$dbUpdates}` WHERE id = '{$incoming->updateid}' LIMIT 1";
                $uresult = mysqli_query($db, $usql);
                if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
                $update = mysqli_fetch_object($uresult);

                $asql = "SELECT COUNT(*) FROM `{$dbLinks}` WHERE linktype = 5 AND direction = 'left' AND origcolref = {$incoming->updateid}";
                $aresult = mysqli_query($db, $asql);
                if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
                list($num_attachments) = mysqli_fetch_row($aresult);
            }

            echo "<tr class='{$shade}' onclick='trow(event);'>";
            echo "<td>";

            if (empty($incoming->locked) OR $incoming->locked == $sit[2])
            {
                echo html_checkbox('selected[]', FALSE, $incoming->id);
            }
            echo "</td>";
            echo "<td>".contact_info($incoming->contactid, $incoming->from, $incoming->emailfrom, $incoming->subject)."</td>";
            echo "</td>";
            // Subject
            echo "<td>";
            if (($incoming->locked != $sit[2]) AND ($incoming->locked > 0))
            {
                echo icon('locked', 16) . ' ';
                echo sprintf($strLockedByX, user_realname($incoming->locked, TRUE));
                if (!empty($incoming->reason)) 
                {
                    echo " ({$incoming->reason})";
                }
                echo " &mdash; <a name='locked' class='info'>";
                echo htmlentities($incoming->subject,ENT_QUOTES, $GLOBALS['i18ncharset']);
            }
            else
            {
                if ($incoming->locked > 0)
                {
                    echo icon('locked', 16) . ' ';
                    if (!empty($incoming->reason)) 
                    {
                        echo " ({$incoming->reason})";
                    }
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
            if (!empty($update->timestamp)) 
            {
                echo ldate($CONFIG['dateformat_datetime'], $arrived);
            }
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
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    if ($result AND mysqli_num_rows($result) > 0)
    {
        $incoming = mysqli_fetch_object($result);
        $usql = "SELECT * FROM `{$dbUpdates}` WHERE id = '{$incoming->updateid}' LIMIT 1";
        $uresult = mysqli_query($db, $usql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        $update = mysqli_fetch_object($uresult);
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
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
            $lockedbyname = $strYou;
            $lockedbyyou = true;
            $incoming->locked = true;
        }
        elseif ($incoming->locked != $sit[2])
        {
            $lockedby = $incoming->locked;
            $lockedbysql = "SELECT realname FROM `{$dbUsers}` WHERE id={$lockedby}";
            $lockedbyresult = mysqli_query($db, $lockedbysql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
            while ($row = mysqli_fetch_object($lockedbyresult))
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
            // Reply feature incomplete and therefore disabled for 3.90, I aim to finish this code soon. INL 4/5/2013
            // echo "<a href='{$_SERVER['PHP_SELF']}?id={$displayid}&reply=true'>{$strReply}</a>  | ";

            if (!empty($incoming->forenames) OR !empty($incoming->surname))
            {
                $search_string = urlencode("{$incoming->forenames} {$incoming->surname}");
            }
            else
            {
                $search_string = urlencode($incoming->emailfrom);
            }

            echo "<a href='{$_SERVER['PHP_SELF']}?action=delete&amp;selected={$displayid}'>{$strDelete}</a>"; 
            if (!$incoming->locked)
            {
                echo " | <a href='{$_SERVER['PHP_SELF']}?id={$displayid}&amp;action=lock&amp;selected={$displayid}'>{$strLock}</a>";
            }
            elseif ($lockedbyyou)
            {
                echo " | <a href='{$_SERVER['PHP_SELF']}?id={$displayid}&amp;action=unlock&amp;selected={$displayid}'>{$strUnlock}</a>";
            }
            echo " | <a href=\"incident_new.php?action=findcontact&amp;incomingid={$displayid}&amp;search_string={$search_string}&amp;from={$incoming->emailfrom}&amp;contactid={$incoming->contactid}&amp;win=incomingcreate\" title=\"{$strCreateAnIncident}\">{$strCreateNewIncident}</a>";
            echo " | <a href=\"move_update.php?id={$displayid}&amp;updateid={$incoming->updateid}&amp;contactid={$incoming->contactid}&amp;win=incomingview\" title=\"{$strUpdateIncident}\">";
            echo "{$strMoveToIncident}</a>"; 

            if ($lockedbyyou)
            {
                echo "<div class='detaildate'>";
                echo "<form method='post' action='{$_SERVER['PHP_SELF']}?selected={$displayid}&win=incomingview&action=updatereason'>";
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
            // TODO reply button and make reply actually work.
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