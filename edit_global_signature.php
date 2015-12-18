<?php
// edit_global_signature.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// This Page Is Valid XHTML 1.0 Transitional!   4Nov05

// Authors: Ivan Lucas <ivan[at]sitracker.org>
//          Paul Heaney <paul[at]sitracker.org>


function get_globalsignature($sig_id)
{
    global $dbEmailSig, $db;
    $sql = "SELECT signature FROM `{$dbEmailSig}` WHERE id = {$sig_id}";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    list($signature) = mysqli_fetch_row($result);
    mysqli_free_result($result);
    return $signature;
}

function delete_signature($sig_id)
{
    global $dbEmailSig, $db;
    $sql = "DELETE FROM `{$dbEmailSig}` WHERE id = {$sig_id}";
    mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

    journal(CFG_LOGGING_NORMAL, 'Global Signature deleted', "A global signature was deleted", CFG_JOURNAL_ADMIN, 0);
    html_redirect("edit_global_signature.php");
    exit;
}

require ('core.php');
$permission = PERM_GLOBALSIG_EDIT; // Edit global signature
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strGlobalSignature;

// External variables
$action = clean_fixed_list($_REQUEST['action'], array('', 'new','delete','edit'));
$sig_id = clean_int($_REQUEST['sig_id']);
$signature = clean_dbstring($_REQUEST['signature']);
$formaction = clean_fixed_list($_REQUEST['formaction'], array('new','edit'));

if (!empty($signature))
{
    //we've been passed a signature - ie we must either be deleting or editing on actual signature
    switch ($formaction)
    {
        case 'new':
            //then we're adding a new signature
            $sql = "INSERT INTO `{$dbEmailSig}` (signature) VALUES ('$signature') ";
            mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

            journal(CFG_LOGGING_NORMAL, 'Global Signature added', "A new global signature was added", CFG_JOURNAL_ADMIN, 0);
            html_redirect("edit_global_signature.php");
            break;

        case 'edit':
            $sql = "UPDATE `{$dbEmailSig}` SET signature = '$signature' WHERE id = {$sig_id}";
            mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

            journal(CFG_LOGGING_NORMAL, 'Global Signature updated', "A global signature was updated", CFG_JOURNAL_ADMIN, 0);
            html_redirect("edit_global_signature.php");
          break;
  }

}
elseif (empty($action))
{
    //The just view the global signatures
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('edit', 32)." {$title}</h2>";

    $sql = "SELECT id, signature FROM `{$dbEmailSig}` ORDER BY id ASC";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);

    echo "<p align='center'>{$strOneOfTheSignaturesWillBeInserted}<br /><br />";
    echo "{$strGlobalSignatureRemember}</p>";

    echo "<p align='center'><a href='edit_global_signature.php?action=new'>{$strNew}</a></p>";

    echo "<table align='center' width='60%'>";
    echo "<tr><th>{$strGlobalSignature}</th><th>{$strActions}</th></tr>";
    while ($signature = mysqli_fetch_object($result))
    {
        $id = $signature->id;
        echo "<tr>";
        echo "<td class='shade1' width='70%'>".ereg_replace("\n", "<br />", $signature->signature)."</td>";
        echo "<td class='shade2' align='center'><a href='edit_global_signature.php?action=edit&amp;sig_id={$id}'>{$strEdit}</a> | ";
        echo "<a href='edit_global_signature.php?action=delete&amp;sig_id={$id}'>{$strDelete}</a></td>";
        echo "</tr>";
    }
    echo "</table>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif (!empty($action))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    switch ($action)
    {
        case 'new':
            echo "<h2>".icon('edit', 32)." {$strGlobalSignature}: {$strNew}</h2>";
            echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
            echo "<input type='hidden' name='formaction' value='new' />";
            echo "<table class='vertical' width='50%'>";
            echo "<tr>";

            echo "<td align='right' valign='top' class='shade1'><strong>{$strGlobalSignature}</strong>:<br />\n";
            echo "{$strGlobalSignatureDescription}<br /><br />";
            echo $strGlobalSignatureRemember;
            echo "</td>";

            echo "<td class='shade1'><textarea name='signature' rows='15' cols='65'></textarea></td>";
            echo "</tr>";
            echo "</table>";
            echo "<p class='formbuttoms'><input name='reset' type='reset' value='{$strReset}' /> <input name='submit' type='submit' value=\"{$strSave}\" /></p>";
            echo "<p class='return'><a href=\"{$_SERVER['PHP_SELF']}\">{$strReturnWithoutSaving}</a></p>";
            echo "</form>\n";
            break;

        case 'delete':
            delete_signature($sig_id);
            break;

        case 'edit':
            echo "<h2>".icon('edit', 32)." {$strGlobalSignature}: {$strEdit}</h2>";
            echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
            echo "<input type='hidden' name='formaction' value='edit' />";
            echo "<input type='hidden' name='sig_id' value='{$sig_id}' />";
            echo "<table class='vertical' width='50%'>";
            echo "<tr>";
            echo "<td align='right' valign='top' class='shade1'><strong>{$strGlobalSignature}</strong>:<br />\n";
            echo "{$strGlobalSignatureDescription}<br /><br />";
            echo $strGlobalSignatureRemember;
            echo "</td>";
            echo "<td class='shade1'><textarea name='signature' rows='15' cols='65'>".get_globalsignature($sig_id)."</textarea></td>";
            echo "</tr>";
            echo "</table>";

            echo "<p class='formbuttoms'><input name='reset' type='reset' value='{$strReset}' /> <input name='submit' type='submit' value=\"{$strSave}\" /></p>";
            echo "<p class='return'><a href=\"{$_SERVER['PHP_SELF']}\">{$strReturnWithoutSaving}</a></p>";
            echo "</form>\n";
            break;
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>