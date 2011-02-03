<?php
// escalation_path_add.php - Display a form for adding an escalation path
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paulheaney[at]users.sourceforge.net>

//// This Page Is Valid XHTML 1.0 Transitional!  (1 Oct 2006)


$permission = 64; // Manage escalation paths

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$submit = $_REQUEST['submit'];

$title = $strNewEscalationPath;

if (empty($submit))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo show_form_errors('add_escalation_path');
    clear_form_errors('add_escalation_path');

    echo "<h2>{$title}</h2>";

    echo "<form action='".$_SERVER['PHP_SELF']."' method='post' onsubmit='return confirm_action(\"{$strAreYouSureAdd}\")'>";
    echo "<table class='vertical'>";

    echo "<tr><th>{$strName}</th><td><input name='name' class='required' ";
    if ($_SESSION['formdata']['add_escalation_path']['name'] != '')
    {
        echo "value='{$_SESSION['formdata']['add_escalation_path']['name']}'";
    }
    echo "/> <span class='required'>{$strRequired}</span></td></tr>";

    echo "<tr><th>{$strType}</th><td>";
    $type = array('internal' => 'Internal','external' => 'External');
    echo array_drop_down($type, 'type', $_SESSION['formdata']['add_escalation_path']['type']);
    echo "</td></tr>";

    echo "<tr><th>{$strTrackURL}<br /></th><td><input name='trackurl'";
    if ($_SESSION['formdata']['add_escalation_path']['trackurl'] != '')
    {
        echo "value='{$_SESSION['formdata']['add_escalation_path']['trackurl']}'";
    }
    echo "/><br />{$strNoteInsertExternalID}</td></tr>";

    echo "<tr><th>{$strHomeURL}</th><td><input name='homeurl'";
    if ($_SESSION['formdata']['add_escalation_path']['homeurl'] != '')
    {
        echo "value='{$_SESSION['formdata']['add_escalation_path']['homeurl']}'";
    }
    echo "/></td></tr>";

    echo "<tr><th>{$strTitle}</th><td><input name='title'";
    if ($_SESSION['formdata']['add_escalation_path']['title'] != '')
    {
        echo "value='{$_SESSION['formdata']['add_escalation_path']['title']}'";
    }
    echo "/></td></tr>";

    echo "<tr><th>{$strEmailDomain}</th><td><input name='emaildomain'";
    if ($_SESSION['formdata']['add_escalation_path']['emaildomain'] != '')
    {
        echo "value='{$_SESSION['formdata']['add_escalation_path']['emaildomain']}'";
    }
    echo "/></td></tr>";

    echo "</table>";

    echo "<p align='center'><input type='submit' name='submit' value='{$strAdd}' /></p>";

    echo "</form>";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    clear_form_data('add_escalation_path');

}
else
{
    // External variables
    $name = cleanvar($_REQUEST['name']);
    $type = cleanvar($_REQUEST['type']);
    $trackurl = cleanvar($_REQUEST['trackurl']);
    $homeurl = cleanvar($_REQUEST['homeurl']);
    $title = cleanvar($_REQUEST['title']);
    $emaildomain = cleanvar($_REQUEST['emaildomain']);

    $_SESSION['formdata']['add_escalation_path'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);

    $errors = 0;
    if (empty($name))
    {
        $errors++;
        $_SESSION['formerrors']['add_escalation_path']['name'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strName}'"), E_USER_ERROR);
    }

    if ($errors == 0)
    {
        $sql = "INSERT INTO `{$dbEscalationPaths}` (name,track_url,home_url,url_title,email_domain) VALUES ";
        $sql .= " ('{$name}','{$trackurl}','{$homeurl}','{$title}','{$emaildomain}')";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);

        if (!$result)
        {
            $_SESSION['formerrors']['add_escalation_path']['error'] = "{$strError}: {$strFailed}";
        }
        else
        {
            html_redirect("escalation_paths.php");
        }
        clear_form_errors('add_escalation_path');
        clear_form_data('add_escalation_path');
    }
    else
    {
        html_redirect("escalation_path_add.php", FALSE);
    }
}

?>