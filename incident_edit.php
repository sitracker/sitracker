<?php
// incident_edit.php - Form for editing incident title and other fields
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

require ('core.php');
$permission = PERM_INCIDENT_EDIT; // Edit Incidents
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

// External variables
$submit = cleanvar($_REQUEST['submit']);
$id = clean_int($_REQUEST['id']);
$incidentid = $id;

// No submit detected show edit form
if (empty($submit))
{
    $title = $strEdit;
    include (APPLICATION_INCPATH . 'incident_html_top.inc.php');

    // extract incident details
    $sql  = "SELECT * FROM `{$dbIncidents}` WHERE id='{$id}'";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    $incident = mysqli_fetch_object($result);

    plugin_do('incident_edit');

    echo show_form_errors('edit_incident');
    clear_form_errors('edit_incident');
    echo "<form action='{$_SERVER['PHP_SELF']}' method='post' name='editform'>";
    echo "<table class='vertical'>";
    echo "<tr><th>{$strTitle}</th><td><input maxlength='150' name='title' size='40' type='text' value=\"{$incident->title}\" class='required' /> ";
    echo "<span class='required'>{$strRequired}</span></td></tr>\n";
    echo "<tr><th>{$strTags}</th><td><textarea rows='2' cols='40' name='tags'>".list_tags($id, 2, false)."</textarea></td></tr>\n";
    echo "<tr><th>{$strImportant}</th>";
    echo "<td>{$strChangingContact}. ";
    if ($incident->maintenanceid >= 1)
    {
        echo sprintf($strLoggedUnder, $incident->maintenanceid).". ";
    }

    else echo "{$strIncidentNoContract}. ";
    echo "{$strToChangeContract}.";
    echo "</td></tr>\n";
    echo "<tr><th>{$strContract}</th></td><td>";
    echo maintenance_drop_down('contract', $incident->maintenanceid, contact_siteid($incident->contact), '', FALSE, TRUE, '', $incident->servicelevel)." {$strOnlyShowsContractsOfSameSLA}</td></tr>\n";
    echo "<tr><th>{$strContact}</th><td>";
    echo contact_drop_down("contact", $incident->contact, TRUE)."</td></tr>\n";
    flush();
    $maintid = maintenance_siteid($incident->maintenanceid);
    echo "<tr><th>{$strSite}</th><td>".site_name($maintid)."</td></tr>";
    echo "<tr><th>{$strSkill}</th><td>".softwareproduct_drop_down("software", $incident->softwareid, $incident->product)."</td></tr>\n";
    echo "<tr><th>{$strVersion}</th>";
    echo "<td><input maxlength='50' name='productversion' size='30' type='text' value=\"{$incident->productversion}\" /></td></tr>\n";
    echo "<tr><th>{$strServicePacksApplied}</th>";
    echo "<td><input maxlength='100' name='productservicepacks' size='30' type='text' value=\"{$incident->productservicepacks}\" /></td></tr>\n";
    echo "<tr><th>CC {$strEmail}</th>";
    echo "<td><input maxlength='255' name='ccemail' size='30' type='text' value=\"{$incident->ccemail}\" /></td></tr>\n";
    echo "<tr><th>{$strCustomerReference}</th>";
    echo "<td><input maxlength='50' name='customerid' size='30' type='text' value=\"{$incident->customerid}\" /></td></tr>\n";
    echo "<tr><th>{$strEscalation}</th>";
    echo "<td>".escalation_path_drop_down('escalationpath', $incident->escalationpath)."</td></tr>";
    echo "<tr><th>{$strExternalID}</th>";
    echo "<td><input maxlength='50' name='externalid' size='30' type='text' value=\"{$incident->externalid}\" /></td></tr>\n";
    echo "<tr><th>{$strExternalEngineersName}</th>";
    echo "<td><input maxlength='80' name='externalengineer' size='30' type='text' value=\"{$incident->externalengineer}\" /></td></tr>\n";
    echo "<tr><th>{$strExternalEmail}</th>";
    echo "<td><input maxlength='255' name='externalemail' size='30' type='text' value=\"{$incident->externalemail}\" /></td></tr>\n";
    plugin_do('incident_edit_form');
    echo "</table>\n";
    echo "<p class='formbuttons'>";
    echo "<input name='type' type='hidden' value='Support' />";

    echo "<input name='id' type='hidden' value=\"{$id}\" />";
    echo "<input name='oldtitle' type='hidden' value=\"{$incident->title}\" />";
    echo "<input name='oldcontact' type='hidden' value=\"{$incident->contact}\" />";
    echo "<input name='oldccemail' type='hidden' value=\"{$incident->ccemail}\" />";
    echo "<input name='oldescalationpath' type='hidden' value=\"".db_read_column('name', $dbEscalationPaths, $incident->escalationpath)."\" />";
    echo "<input name='oldcustomerid' type='hidden' value=\"{$incident->customerid}\" />";
    echo "<input name='oldexternalid' type='hidden' value=\"{$incident->externalid}\" />";
    echo "<input name='oldexternalengineer' type='hidden' value=\"{$incident->externalengineer}\" />";
    echo "<input name='oldexternalemail' type='hidden' value=\"{$incident->externalemail}\" />";
    echo "<input name='oldpriority' type='hidden' value=\"{$incident->priority}\" />";
    echo "<input name='oldstatus' type='hidden' value=\"{$incident->status}\" />";
    echo "<input name='oldproductversion' type='hidden' value=\"{$incident->productversion}\" />";
    echo "<input name='oldproductservicepacks' type='hidden' value=\"{$incident->productservicepacks}\" />";
    echo "<input name='oldsoftware' type='hidden' value=\"{$incident->softwareid}\" />";

    echo "<input name='submit' type='reset' value='{$strReset}' /> <input name='submit' type='submit' value='{$strSave}' /></p>";
    echo "</form>\n";

    include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
}
else
{
    // External variables
    $customerid = trim(clean_dbstring($_POST['customerid']));
    $externalid = clean_dbstring($_POST['externalid']);
    $type = cleanvar($_POST['type']);
    $ccemail = cleanvar($_POST['ccemail']);
    $escalationpath = cleanvar($_POST['escalationpath']);
    $externalengineer = cleanvar($_POST['externalengineer']);
    $externalemail = cleanvar($_POST['externalemail']);  
    $title = cleanvar($_POST['title']);
    $contact = clean_int($_POST['contact']);
    $software = clean_int($_POST['software']);
    $productversion = cleanvar($_POST['productversion']);
    $productservicepacks = cleanvar($_POST['productservicepacks']);
    $id = clean_int($_POST['id']);
    $oldtitle = cleanvar($_POST['oldtitle']);
    $oldcontact = clean_int($_POST['oldcontact']);
    $maintid = clean_int($_POST['maintid']);
    $oldescalationpath = cleanvar($_POST['oldescalationpath']);
    $oldcustomerid = clean_dbstring($_POST['oldcustomerid']);
    $oldexternalid = clean_dbstring($_POST['oldexternalid']);
    $oldexternalemail = cleanvar($_POST['oldexternalemail']);
    $oldproduct = clean_int($_POST['oldproduct']);
    $oldproductversion = cleanvar($_POST['oldproductversion']);
    $oldproductservicepacks = cleanvar($_POST['oldproductservicepacks']);
    $oldccemail = cleanvar($_POST['oldccemail']);
    $oldexternalengineer = cleanvar($_POST['oldexternalengineer']);
    $oldsoftware = clean_int($_POST['oldsoftware']);
    $tags = cleanvar($_POST['tags']);

    // Edit the incident
    // check form input
    $errors = 0;

    // Initilise here so we can overwrite in plugin
    $header = '';

    // check for blank contact
    if ($contact == 0)
    {
        $errors += 1;
        $_SESSION['formerrors']['edit_incident']['contact'] = sprintf($strFieldMustNotBeBlank, $strContact);
    }
    // check for blank title
    if ($title == '')
    {
        $errors += 1;
        $_SESSION['formerrors']['edit_incident']['contact'] = sprintf($strFieldMustNotBeBlank, $strTitle);
    }
    plugin_do('incident_edit_submitted');

    if ($errors > 0)
    {
        html_redirect("incident_edit.php?id={$id}", FALSE);
        exit;
    }

    if ($errors == 0)
    {
        $addition_errors = 0;

        replace_tags(2, $id, $tags);

        // update support incident
        $sql = "UPDATE `{$dbIncidents}` ";
        $sql .= "SET externalid='{$externalid}', ccemail='{$ccemail}', ";
        $sql .= "escalationpath='{$escalationpath}', externalengineer='{$externalengineer}', externalemail='{$externalemail}', title='{$title}', ";
        $sql .= "contact='{$contact}', softwareid='{$software}', productversion='{$productversion}', customerid='{$customerid}', ";
        $sql .= "productservicepacks='{$productservicepacks}', lastupdated='{$now}' WHERE id='{$id}'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
        if (!$result)
        {
            $addition_errors = 1;
            $addition_errors_string .= "<p class='error'>Update of incident failed</p>\n";
        }

        if ($addition_errors == 0)
        {
            // dump details to incident update
            if ($oldtitle != $title) $header .= "Title: {$oldtitle} -&gt; <b>{$title}</b>\n";
            if ($oldcontact != $contact)
            {
                $contactname = contact_realname($contact);
                $contactsite = contact_site($contact);
                $header .= "Contact: " . contact_realname($oldcontact) . " -&gt; <b>{$contactname}</b>\n";
                $maintsiteid = maintenance_siteid(incident_maintid($id));
                if ($maintsiteid > 0 AND contact_siteid($contact) != $maintsiteid)
                {
                    $maintcontactsite = site_name($maintsiteid);
                    $header .= "Assigned to <b>{$contactname} of {$contactsite}</b> on behalf of {$maintcontactsite} (The contract holder)\n";
                }
            }
            
            if ($oldcustomerid != $customerid)
            {
                $header .= "{$strCustomerReference}: ";
                if ($oldcustomerid != '')
                {
                    $header .= $oldcustomerid;
                }
                else
                {
                    $header .= "None";
                }
                
                $header .= " -&gt; <b>";
                if ($customerid != '')
                {
                    $header .= $customerid;
                }
                else
                {
                    $header .= "None";
                }
                
                $header .= "</b>\n";
            }
            
            if ($oldexternalid != $externalid)
            {
                $header .= "External ID: ";
                if ($oldexternalid != '')
                {
                    $header .= $oldexternalid;
                }
                else
                {
                    $header .= "None";
                }

                $header .= " -&gt; <b>";
                if ($externalid != '')
                {
                    $header .= $externalid;
                }
                else
                {
                    $header .= "None";
                }

                $header .= "</b>\n";
            }
            $escalationpath = db_read_column('name', $dbEscalationPaths, $escalationpath);
            if ($oldccemail != $ccemail)
            {
                $header .= "CC Email: {$oldccemail} -&gt; <b>{$ccemail}</b>\n";
            }

            if ($oldescalationpath != $escalationpath)
            {
                $header .= "Escalation: {$oldescalationpath} -&gt; <b>{$escalationpath}</b>\n";
            }
            if ($oldexternalengineer != $externalengineer)
            {
                $header .= "External Engineer: {$oldexternalengineer} -&gt; <b>{$externalengineer}</b>\n";
            }

            if ($oldexternalemail != $externalemail)
            {
                $header .= "External email: {$oldexternalemail} -&gt; <b>{$externalemail}</b>\n";
            }

            if ($oldsoftware != $software)
            {
                $header .= "Skill: ".software_name($oldsoftware)." -&gt; <b>".software_name($software)."</b>\n";
            }

            if ($oldproductversion != $productversion)
            {
                $header .= "Version: {$oldproductversion} -&gt; <b>{$productversion}</b>\n";
            }

            if ($oldproductservicepacks != $productservicepacks)
            {
                $header .= "Service Packs Applied: {$oldproductservicepacks} -&gt; <b>{$productservicepacks}</b>\n";
            }

            if (!empty($header)) $header .= "<hr>";
            // get current incident status
            $sql = "SELECT status FROM `{$dbIncidents}` WHERE id={$id}";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
            $status = mysqli_fetch_object($result);
            $status = $status->status;
            $owner = incident_owner($id);
            $bodytext = $header . $bodytext;
            $bodytext = mysqli_real_escape_string($db, $bodytext);
            $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, currentowner, currentstatus, bodytext, timestamp) ";
            $sql .= "VALUES ('{$id}', '{$sit[2]}', 'editing', '{$owner}', '{$status}', '{$bodytext}', '{$now}')";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

            if (!$result)
            {
                $addition_errors = 1;
                $addition_errors_string .= "<p class='error'>Addition of incident update failed</p>\n";
            }

            plugin_do('incident_edited');
        }

        if ($addition_errors == 0)
        {
            plugin_do('incident_edit_saved');
            journal(CFG_LOGGING_NORMAL, 'Incident Edited', "Incident {$id} was edited", CFG_JOURNAL_INCIDENTS, $id);
            html_redirect("incident_details.php?id={$id}");
        }
        else
        {
            include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
            echo $addition_errors_string;
            include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
        }
    }
    else
    {
        include (APPLICATION_INCPATH . 'incident_html_top.inc.php');
        echo $error_string;
        include (APPLICATION_INCPATH . 'incident_html_bottom.inc.php');
    }

}
?>
