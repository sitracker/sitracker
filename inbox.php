<?php
// inbox.php - View/Respond to incoming email
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Authors: Tom Gerrard, Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//                       Paul Heaney <paulheaney[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional! 31Oct05


$permission = 42;
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$sort = cleanvar($_REQUEST['sort']);
$order = cleanvar($_REQUEST['order']);
$filter = cleanvar($_REQUEST['filter']);
$displayid = cleanvar($_REQUEST['id']);


$refresh = 60;
$title = $strInbox;
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

if (empty($sort)) $sort='date';

function contact_info($contactid, $email, $name)
{
    global $strUnknown;
    //$info .= "<span style='float:right;'>".gravatar($email, 16) . '</span>';
    if (!empty($contactid))
    {
        $info .= "<a href='contact_details.php?id={$contactid}'>";
        $info .= icon('contact', 16);
        $info .= "</a>";
    }
    else
    {
        $info .= icon('email', 16);
    }
    $info .= ' ';
    if (!empty($email)) $info .= "<a href=\"mailto:{$email}\" class='info'>";
    if (!empty($name)) $info .= "{$name}";
    elseif (!empty($email)) $info .= "{$email}";
    else $info .= "{$strUnknown}";
    if (!empty($email))
    {
        $info .= "<span>".gravatar($email, 50, FALSE);
        $info .= "{$email}";
        $info .= "</span>";
        $info .= "</a>";
    }

    if (!empty($contactid))
    {
        $info .= " (".contact_site($contactid).")";
    }

    return $info;
}




if (empty($displayid))
{
    echo "<h2>".icon('email', 32)." {$CONFIG['email_address']}: {$strInbox}</h2>";
    if ($CONFIG['enable_inbound_mail'] == 'disabled')
    {
        echo "<p class='warning'>{$strInboundEmailIsDisabled}</p>";
    }

    echo "<p align='center'>{$strIncomingEmailText}.  <a href='{$_SERVER['PHP_SELF']}'>{$strRefresh}</a></p>";


    // Perform action on selected items
    if (!empty($_REQUEST['action']))
    {
        // FIXME BUGBUG remove for release. temporary message
        echo "<p>Action: {$_REQUEST['action']}</p>";
        if (is_array($_REQUEST['selected']))
        {
            foreach ($_REQUEST['selected'] AS $item => $selected)
            {
                $tsql = "SELECT updateid FROM `{$dbTempIncoming}` WHERE id={$selected}";
                $tresult = mysql_query($tsql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                if ($tresult AND mysql_num_rows($tresult) > 0)
                {
                    $temp = mysql_fetch_object($tresult);
                    if ($CONFIG['debug']) echo "<p>action on: $selected</p>"; // FIXME BUGBUG remove for release. temporary message
                    switch ($_REQUEST['action'])
                    {
                        case 'deleteselected':
                            $dsql = "DELETE FROM `{$dbUpdates}` WHERE id={$temp->updateid}";
                            mysql_query($dsql);
                            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                            $dsql = "DELETE FROM `{$dbTempIncoming}` WHERE id={$selected}";
                            mysql_query($dsql);
                            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                           break;
                    }
                }
        }
        }
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
        echo "<form action='{$_SERVER['PHP_SELF']}' id='inboxform' name='inbox'  method='post'>";
        $shade = 'shade1';
        echo "<table align='center' id='inboxtable'>";
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
            echo "<td>".contact_info($incoming->contactid, $incoming->from, $incoming->emailfrom)."</td>";
            echo "</td>";
            // Subject
            echo "<td>";
            if (($incoming->locked != $sit[2]) AND ($incoming->locked > 0))
            {
                echo sprintf($strLockedByX, user_realname($update->locked,TRUE));
            }
            else
            {
                // TODO option for popup or not (Mantis 619)
                // $url = "javascript:incident_details_window('{$incoming->id}','incomingview');";
                // $url = "incident_details.php?id={$incoming->id}&amp;win=incomingview";
                $url = "inbox.php?id={$incoming->id}";
                echo "<a href=\"{$url}\" id='update{$incoming->updateid}' class='info'";
                echo " title='{$strViewAndLockHeldEmail}'>";
                if (!empty($incoming->incident_id)) echo icon('support',16) . ' ';
                echo htmlentities($incoming->subject,ENT_QUOTES, $GLOBALS['i18ncharset']);
                if (!empty($update->bodytext)) echo '<span>'.parse_updatebody(truncate_string($update->bodytext, 1024)).'</span>';
                echo "</a>";
                if ($num_attachments > 0) echo ' '.icon('attach', 16, '', "{$strAttachments}: {$num_attachments}");
            }

            echo "</td>";
            // echo "<td><pre>".print_r($incoming,true)."</pre><hr /></td>";
            // Date
            echo "<td>";
            if (!empty($update->timestamp)) echo date($CONFIG['dateformat_datetime'], $update->timestamp);
            echo "</td>";
            // Size
            echo "<td style='white-space:nowrap;'>";
            echo readable_file_size(strlen($update->bodytext));
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
        echo "<option value='lockselected'>{$strLock}</option>";
        echo "<option value='deleteselected'>{$strDelete}</option>";
        echo "<option value='assignselected'>{$strAssign}</option>";
        echo "</select>";
        echo "<input type='submit' value=\"{$strGo}\" />";
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        echo "</form>\n";
    }
    else
    {
        echo "<p class='info'>{$strNoRecords}</p>";
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
        echo " {$incoming->subject}";
        if (empty($_REQUEST['reply'])) echo " &mdash; <a href='{$_SERVER['PHP_SELF']}?id={$displayid}&reply=true'>{$strReply}</a>";
        echo "</div>";
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
        echo "<p class='warning'>{$strNoRecords}</p>";
    }
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>