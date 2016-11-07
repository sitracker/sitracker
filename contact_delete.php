<?php
// delete_contact.php - Form for deleting contacts, moves any associated records to another contact the user chooses
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!   31Oct05

require ('core.php');
$permission = PERM_SITE_DELETE; // Delete Sites/Contacts
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$process = clean_fixed_list($_REQUEST['process'], array('', 'true'));
$id = clean_int($_REQUEST['id']);
$newcontact = clean_int($_REQUEST['newcontact']);
$title = $strDeleteContact;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');
if (empty($process))
{
    if (empty($id))
    {
        echo "<h2>{$strDeleteContact}</h2>";
        plugin_do('contact_delete');
        echo "<form action='{$_SERVER['PHP_SELF']}?action=delete' method='post'>";
        echo "<table class='maintable'>";
        echo "<tr><th>{$strContact}:</th><td>".contact_site_drop_down("id", 0)."</td></tr>";
        plugin_do('contact_delete_form');
        echo "</table>";
        echo "<p><input name='submit1' type='submit' value=\"{$strDelete}\" /></p>";
        echo "</form>";
    }
    else
    {
        echo "<h2>{$strDeleteContact}</h2>\n";
        plugin_do('contact_delete');
        $sql = "SELECT * FROM `{$dbContacts}` WHERE id='{$id}' ";
        $contactresult = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        while ($contactobj = mysqli_fetch_object($contactresult))
        {
            echo "<table class='maintable vertical'>";
            echo "<tr><th>{$strName}:</th><td><h3>{$contactobj->forenames} {$contactobj->surname}</h3></td></tr>";
            echo "<tr><th>{$strSite}:</th><td><a href='site_details.php?id={$contactobj->siteid}'>".site_name($contactobj->siteid)."</a></td></tr>";
            echo "<tr><th>{$strDepartment}:</th><td>{$contactobj->department}</td></tr>";
            echo "<tr><th>{$strEmail}:</th><td><a href='mailto:{$contactobj->email}'>{$contactobj->email}</a></td></tr>";
            echo "<tr><th>{$strTelephone}:</th><td>{$contactobj->phone}</td></tr>";
            echo "<tr><th>{$strNotes}:</th><td>{$contactobj->notes}</td></tr>";
        }
        mysqli_free_result($contactresult);
        echo "</table>\n";

        plugin_do('contact_delete_submitted');

        $totalincidents = contact_count_incidents($id);
        if ($totalincidents > 0)
        {
            echo user_alert(sprintf($strThereAreXIncidentsAssignedToThisContact, $totalincidents), E_USER_WARNING);
        }
        $sql  = "SELECT sc.maintenanceid AS maintenanceid, m.product, p.name AS productname, ";
        $sql .= "m.expirydate, m.term ";
        $sql .= "FROM `{$dbSupportContacts}` AS sc, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p ";
        $sql .= "WHERE sc.maintenanceid = m.id AND m.product = p.id AND sc.contactid = '{$id}' ";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        $totalcontracts = mysqli_num_rows($result);
        if ($totalcontracts>0)
        {
            echo user_alert(sprintf($strThereAreXcontractsAssignedToThisPerson, $totalcontracts), E_USER_WARNING);
        }

        if ($totalincidents > 0 || $totalcontracts > 0)
        {
            echo "<form action='{$_SERVER['PHP_SELF']}' onsubmit=\"return confirm_action('{$strAreYouSureDelete}', true)\" method='post'>\n";
            echo "<p align='center'>{$strBeforeDeleteContact}</p>";
            $sql  = "SELECT id, forenames, surname, siteid FROM `{$dbContacts}` ORDER BY surname ASC";
            $result = mysqli_query($db, $sql);
            echo "<p align='center'>";
            echo "<select name='newcontact'>";
            if ($id == 0)
            {
                echo "<option selected='selected' value='0'>Select A Contact\n";
            }

            while ($contacts = mysqli_fetch_object($result))
            {
                $site = '';
                if ($contacts->siteid != '' && $contacts->siteid != 0)
                {
                    $site=" of ".site_name($contacts->siteid);
                }
                if ($contacts->id != $id)
                {
                    echo "<option value='{$contacts->id}'>";
                    echo htmlspecialchars($contacts->surname.', '.$contacts->forenames.$site);
                    echo "</option>\n";
                }
            }
            echo "</select><br />";
            echo "<br />";
            echo "<input type='hidden' name='id' value='{$id}' />";
            echo "<input type='hidden' name='process' value='true' />";
            echo "<input type='submit' value='{$strDelete}' />";
            echo "</p>";
            echo "</form>";
            echo "<p class='return'><a href=\"contact_details.php?id={$contact}\">{$strReturnWithoutSaving}</a></p>";
        }
        else
        {
            // plain delete
            echo "<br />";
            echo "<form action='{$_SERVER['PHP_SELF']}' onsubmit=\"return confirm_action('{$strAreYouSureDelete}', true)\" method='post'>\n";
            echo "<input type='hidden' name='newcontact' value='' />";  // empty
            echo "<input type='hidden' name='id' value='{$id}' />";
            echo "<input type='hidden' name='process' value='true' />";
            echo "<p class='formbuttons'>";
            echo "<input type='submit' value='{$strDelete}' />";
            echo "</p>";
            echo "</form>\n";
            echo "<p class='return'><a href=\"contact_details.php?id={$contact}\">{$strReturnWithoutSaving}</a></p>";
        }
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else
{
    // save to db
    if (!empty($newcontact))
    {
        $sql = "UPDATE `{$dbSupportContacts}` SET contactid='{$newcontact}' WHERE contactid='{$id}' ";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        $sql = "UPDATE `{$dbIncidents}` SET contact='{$newcontact}' WHERE contact='{$id}' ";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        $sql = "UPDATE `{$dbMaintenance}` SET admincontact='{$newcontact}' WHERE admincontact='{$id}' ";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
    }

    // do the delete
    $sql = "DELETE FROM `{$dbContacts}` WHERE id='{$id}' LIMIT 1";
    mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

    plugin_do('contact_delete_saved');
    journal(CFG_LOGGING_NORMAL, 'Contact Deleted', "Contact {$id} was deleted", CFG_JOURNAL_CONTACTS, $id);

    if (!empty($newcontact)) html_redirect("contact_details.php?id={$newcontact}");
    else  html_redirect("contacts.php");
}
?>