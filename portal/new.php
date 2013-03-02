<?php
// portal/new.inc.php - Add an incident in the portal
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Kieran Hogg <kieran[at]sitracker.org>

require ('..' . DIRECTORY_SEPARATOR . 'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'triggers.inc.php');

$accesslevel = 'any';

include (APPLICATION_LIBPATH . 'portalauth.inc.php');

$contractid = clean_int($_REQUEST['contractid']);
$productid = clean_int($_REQUEST['product']);

if (!$_REQUEST['action'])
{
    include (APPLICATION_INCPATH . 'portalheader.inc.php');

    echo "<h2>".icon('new', 32, $strNewIncident)." {$strNewIncident}</h2>";

    echo show_form_errors('portalnewincident');
    clear_form_errors('portalnewincident');
    
    if ($CONFIG['portal_creates_incidents'])
    {
        //check we are allowed to log against this contract
        $sql = "SELECT *, p.id AS productid, m.id AS id, ";
        $sql .= "(m.incident_quantity - m.incidents_used) AS availableincidents ";
        $sql .= "FROM `{$dbSupportContacts}` AS s, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p ";
        $sql .= "WHERE m.product=p.id ";
        $sql .= "AND s.contactid='{$_SESSION['contactid']}' AND s.maintenanceid=m.id ";
        $sql .= "AND m.id='{$contractid}' ";

        $sql .= "UNION SELECT *, p.id AS productid, m.id AS id, ";
        $sql .= "(m.incident_quantity - m.incidents_used) AS availableincidents ";
        $sql .= "FROM `{$dbSupportContacts}` AS s, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p ";
        $sql .= "WHERE m.product=p.id ";
        $sql .= "AND m.allcontactssupported='yes' ";
        $sql .= "AND m.id='{$contractid}'";

        $checkcontract = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $contract = mysql_fetch_object($checkcontract);
        $productid = $contract->productid;

        if (mysql_num_rows($checkcontract) == 0)
        {
            echo "<p class='error'>{$strPermissionDenied}</p>";
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
            exit;
        }

        mysql_data_seek($checkcontract, 0);
        $availablecontract = 0;
        while ($legalcontract = mysql_fetch_object($checkcontract))
        {
            if (($legalcontract->term != 'yes') AND
                (($legalcontract->expirydate >= $now OR $legalcontract->expirydate == -1) OR
                ($legalcontract->incident_quantity >= 1 AND $legalcontract->incidents_used < $legalcontract->incident_quantity)))
            {
                $availablecontract++;
            }
        }

        if ($availablecontract == 0)
        {
            echo user_alert($strNoContractsFound);
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
            exit;
        }
    }
    echo "<form action='{$_SERVER[PHP_SELF]}?page=add&amp;action=submit' method='post'>";
    echo "<table class='vertical maintable' width='50%'>";
    if ($CONFIG['portal_creates_incidents'])
    {
        echo "<tr><th>{$strArea}:</th><td class='shade1'>".softwareproduct_drop_down('software', show_form_value('portalnewincident', 'software', 0), $productid, 'external')."<br />";
        echo $strNotSettingArea."</td></tr>";
    }
    echo "<tr><th>{$strTitle}:</th><td class='shade1'>";
    echo "<input class='required' maxlength='100' name='title' size='40' type='text' ";
    echo "value='" . show_form_value('portalnewincident', 'title', '') . "' />";
    echo " <span class='required'>{$strRequired}</span></td></tr>";

    $sql = "SELECT * FROM `{$dbProductInfo}` WHERE productid='{$productid}'";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result)  > 0)
    {
        while ($productinforow = mysql_fetch_object($result))
        {
            echo "<tr><th>{$productinforow->information}";
            echo "</th>";
            echo "<td>";
            if ($productinforow->moreinformation != '')
            {
                echo $productinforow->moreinformation."<br />\n";
            }
            $pinfo = "pinfo{$productinforow->id}";
            echo "<input maxlength='100' name='{$pinfo}' ";
            echo "class='required' size='40' type='text' ";
            echo "value='" . show_form_value('portalnewincident', $pinfo, '') . "' />";
            echo " <span class='required'>{$strRequired}</span></td></tr>\n";
        }
    }

    echo "<tr><th width='20%'>{$strProblemDescription}:</th><td class='shade1'>";
    echo $strTheMoreInformation;
    echo " <span class='required'>{$strRequired}</span>" . "<br />";
    echo "<textarea name='probdesc' rows='20' cols='60' class='required'>";
    echo show_form_value('portalnewincident', 'probdesc', '');
    echo "</textarea></td></tr>";

    echo "</table>";
    echo "<input name='contractid' value='{$contractid}' type='hidden' />";
    echo "<input name='productid' value='{$productid}' type='hidden' />";
    echo "<p class='formbuttons'><input type='submit' value='{$strNewIncident}' /></p>";
    echo "</form>";

    clear_form_data('portalnewincident');
    
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
else //submit
{
    $contactid = clean_int($_SESSION['contactid']);
    $contractid = clean_int($_REQUEST['contractid']);
    $software = clean_int($_REQUEST['software']);
    $incidenttitle = cleanvar($_REQUEST['title']);
    $probdesc = cleanvar($_REQUEST['probdesc']);
    $workarounds = cleanvar($_REQUEST['workarounds']);
    $reproduction = cleanvar($_REQUEST['reproduction']);
    $servicelevel = maintenance_servicelevel_tag($contractid);
    $productid = clean_int($_REQUEST['productid']);

    if (isset($_SESSION['syslang'])) $SYSLANG = $_SESSION['syslang'];
    
    $_SESSION['formdata']['portalnewincident'] = cleanvar($_POST, TRUE, FALSE, FALSE);

    $errors = 0;

    if (empty($incidenttitle))
    {
        $_SESSION['formerrors']['portalnewincident']['title'] = sprintf($strFieldMustNotBeBlank, $strIncidentTitle);
        $errors++;
    }

    if (empty($probdesc))
    {
        $_SESSION['formerrors']['portalnewincident']['probdec'] = sprintf($strFieldMustNotBeBlank, $strProblemDescription);
        $errors++;
    }

    foreach ($_POST AS $key => $value)
    {
        if (mb_substr($key, 0, 5) == 'pinfo' AND empty($value))
        {
            $id = intval(str_replace("pinfo", "", $key));
            $sql = "SELECT information FROM `{$dbProductInfo}` ";
            $sql .= "WHERE id='{$id}' ";
            $result = mysql_query($sql);
            $fieldobj = mysql_fetch_object($result);
            $field = $fieldobj->information;

            $_SESSION['formerrors']['portalnewincident'][$field] = sprintf($strFieldMustNotBeBlank, $field); // i18n fieldname
            $errors = 1;
        }
    }

    if ($errors == 0)
    {
        $updatetext = sprintf($SYSLANG['strOpenedViaThePortalByX'], "[b]".contact_realname($contactid)."[/b]");
        $updatetext .= "\n\n";
        if (!empty($probdesc))
        {
            $updatetext .= "[b]{$SYSLANG['strProblemDescription']}[/b]\n{$probdesc}\n\n";
        }

        if ($CONFIG['portal_creates_incidents'])
        {
            $incidentid = create_incident($incidenttitle, $contactid, $servicelevel,
                                    $contractid, $productid, $software);
            $_SESSION['incidentid'] = $incidentid;

            // Need to reload the entitlements data into the session
            unset($_SESSION['entitlement']);
            load_entitlements($_SESSION['contactid'], $_SESSION['siteid']);

            // Save productinfo if there is some
            $sql = "SELECT * FROM `{$dbProductInfo}` WHERE productid='{$productid}'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($result) > 0)
            {
                while ($productinforow = mysql_fetch_object($result))
                {
                    $var = "pinfo{$productinforow->id}";
                    $pinfo = clean_dbstring($_POST[$var]);
                    $pisql = "INSERT INTO `{$dbIncidentProductInfo}` (incidentid, productinfoid, information) ";
                    $pisql .= "VALUES ('{$incidentid}', '{$productinforow->id}', '{$pinfo}')";
                    mysql_query($pisql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                }
            }
        }
        else
        {
            $incidentid = 0;
        }

        $update_id = new_update($incidentid, $updatetext, 'opening');

        if ($CONFIG['portal_creates_incidents'])
        {
            $sql = "SELECT * FROM `{$dbServiceLevels}` WHERE tag='{$servicelevel}' AND priority='{$priority}' ";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            $level = mysql_fetch_object($result);

            $targetval = $level->initial_response_mins * 60;
            $initialresponse = $now + $targetval;

            // Insert the first Review update, this indicates the review period of an incident has started
            // This insert could possibly be merged with another of the 'updates' records, but for now we keep it seperate for clarity
            $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, bodytext) ";
            $sql .= "VALUES ('{$incidentid}', '0', 'reviewmet', '{$now}', '0', '1', 'hide', '')";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            $t = new TriggerEvent('TRIGGER_PORTAL_INCIDENT_CREATED', array('incidentid' => $incidentid));

            if ($CONFIG['auto_assign_incidents'])
            {
                $suggest_user = suggest_reassign_userid($incidentid);
                if ($suggest_user > 0)
                {
                    reassign_incident($incidentid, $suggest_user);
                }
            }

            clear_form_data('portalnewincident');            html_redirect("index.php", TRUE, $strIncidentAdded);
        }
        else
        {
            $contact_id = intval($_SESSION['contactid']);
            $contact_name = contact_realname($_SESSION['contactid']);
            $contact_email = contact_email($_SESSION['contactid']);
            create_temp_incoming($update_id, $contact_name, $incidenttitle,
                                $contact_email, $_SESSION['contactid']);
            clear_form_data('portalnewincident');
            html_redirect("index.php", TRUE, $strRequestSent);
        }
        exit;
    }
    else
    {
        html_redirect("{$_SERVER['PHP_SELF']}?contractid={$contractid}", FALSE);
    }
}
?>