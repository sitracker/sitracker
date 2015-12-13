<?php
// reseller_new.php - Add a new reseller contract
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_RESELLER_ADD;
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$action = clean_fixed_list($_REQUEST['action'], array('','new'));

switch ($action)
{
    case 'new':
        $name = clean_dbstring($_REQUEST['reseller_name']);

        $errors = 0;
        if (empty($name))
        {
            $_SESSION['formerrors']['new_reseller']['name'] = sprintf($strFieldMustNotBeBlank, $strName);
            $errors++;
        }
        plugin_do('reseller_new_submitted');

        if ($errors != 0)
        {
            html_redirect($_SERVER['PHP_SELF'], FALSE);
        }
        else
        {
            $sql = "INSERT INTO `{$dbResellers}` (name) VALUES ('$name')";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

            if (!$result)
            {
                $addition_errors = 1;
                $addition_errors_string .= "<p class='error'>{$strAdditionFail}</p>\n";
            }


            if ($addition_errors == 1)
            {
                // show addition error message
                include (APPLICATION_INCPATH . 'htmlheader.inc.php');
                echo $addition_errors_string;
                include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
            }
            else
            {
                plugin_do('reseller_new_saved');
                // show success message
                $id = mysqli_insert_id($db);
                journal(CFG_LOGGING_NORMAL, 'Reseller Added', "Reseller $id Added", CFG_JOURNAL_MAINTENANCE, $id);
                clear_form_errors('formerrors');

                html_redirect("main.php");
            }
        }
        break;
    default:
        $title = $strNewReseller;
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo show_form_errors('new_reseller');
        clear_form_errors('formerrors');
        echo "<h2>".icon('reseller', 32)." {$strNewReseller}</h2>";
        plugin_do('reseller_new');
        echo "<form action='{$_SERVER['PHP_SELF']}?action=new' method='post' ";
        echo "onsubmit=\"return confirm_action('{$strAreYouSureAdd}')\">";
        echo "<table class='maintable vertical'>";
        echo "<tr><th>{$strName}</th><td><input type='text' name='reseller_name' class='required' /> <span class='required'>{$strRequired}</span></td></tr>";
        plugin_do('reseller_new_form');
        echo "</table>";
        echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
        echo "<input name='submit' type='submit' value='{$strSave}' /></p>";
        echo "<p class='return'><a href=\"contracts.php\">{$strReturnWithoutSaving}</a></p>";
        echo "</form>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        break;
}

?>