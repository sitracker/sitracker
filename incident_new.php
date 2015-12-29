<?php
// incident_new.php - Multi-page form for Adding Incidents
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>, Tom Gerrard
// 7Oct02 INL  Added support for maintenanceid to be put into incidents table

require ('core.php');
$permission = PERM_INCIDENT_ADD;
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNewIncident;

function to_row($contact)
{
    global $now, $updateid, $CONFIG, $dbg;

    $str = '';
    if (($contact->expirydate < $now
        OR $contact->term == 'yes')
        AND $contact->expirydate != -1)
    {
        $class = 'expired';
    }
    else
    {
        $class = "shade2";
    }

    $incidents_remaining = $contact->incident_quantity - $contact->incidents_used;

    $str = "<tr class='{$class}'>";
    if ($contact->expirydate < $now AND $contact->expirydate != '-1')
    {
        $str .=  "<td>{$GLOBALS['strExpired']}</td>";
    }
    elseif ($contact->term == 'yes')
    {
        $str .=  "<td>{$GLOBALS['strTerminated']}</td>";
    }
    elseif ($contact->incident_quantity >= 1 AND $contact->incidents_used >= $contact->incident_quantity)
    {
        $str .= "<td class='expired'>{$GLOBALS['strZeroRemaining']} ({$contact->incidents_used}/{$contact->incident_quantity} {$strUsed})</td>";
    }
    else
    {
        $str .=  "<td><a href=\"{$_SERVER['PHP_SELF']}?action=incidentform&amp;type=support&amp;";
        $str .= "contactid={$contact->contactid}&amp;maintid={$contact->maintid}";
        $str .= "&amp;producttext=".urlencode($contact->productname)."&amp;productid=";
        $str .= "{$contact->productid}&amp;updateid={$updateid}&amp;siteid={$contact->siteid}";
        $str .= "&amp;win={$win}\"";
        if ($_SESSION['userconfig']['show_confirmation_caution'] == 'TRUE')
        {
            $str .= " onclick=\"return confirm_action('{$GLOBALS['strContractAreYouSure']}');\"";
        }
        $str .= ">{$GLOBALS['strNewIncident']}</a> ";
        if ($contact->incident_quantity == 0)
        {
            $str .=  "({$GLOBALS['strUnlimited']})";
        }
        else
        {
            $str .= "(".sprintf($GLOBALS['strRemaining'], $incidents_remaining).")";
        }
    }
    $str .=  "</td>";
    $str .=  "<td>{$contact->forenames} {$contact->surname}</td>";
    $str .=  "<td>{$contact->name}</td>";
    $str .=  "<td>{$contact->productname}</td>";
    $str .=  "<td>{$contact->servicelevel}</td>";
    if ($contact->expirydate == '-1')
    {
        $str .= "<td>{$GLOBALS['strUnlimited']}</td>";
    }
    else
    {
        $str .=  '<td>'.ldate($CONFIG['dateformat_date'], $contact->expirydate).'</td>';
    }
    $str .=  "</tr>\n";
    return $str;
}

// External variables
$action = clean_fixed_list(@$_REQUEST['action'], array('showform','findcontact','incidentform','assign','reassign'));
$context = cleanvar(@$_REQUEST['context']);
$updateid = clean_int(@$_REQUEST['updateid']);
$incomingid = clean_int(@$_REQUEST['incomingid']);
$query = cleanvar(@$_REQUEST['query']);
$siteid = clean_int(@$_REQUEST['siteid']);
$contactid = clean_int(@$_REQUEST['contactid']);
$search_string = cleanvar(@$_REQUEST['search_string']);
$from = cleanvar(@$_REQUEST['from']);
$type = cleanvar(@$_REQUEST['type']);
$maintid = clean_int(@$_REQUEST['maintid']);
$productid = clean_int(@$_REQUEST['productid']);
$producttext = cleanvar(@$_REQUEST['producttext']);
$win = cleanvar(@$_REQUEST['win']);

if (!empty($incomingid) AND empty($updateid))
{
    $updateid = db_read_column('updateid', $dbTempIncoming, $incomingid);
}

if (empty($action) OR $action == 'showform')
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('new', 32)." {$strNewIncident} - {$strFindContact}</h2>";
    if (empty($siteid))
    {
        echo "<form action='{$_SERVER['PHP_SELF']}?action=findcontact' method='post'>";
        echo "<input type='hidden' name='context' value='{$context}' />";
        echo "<input type='hidden' name='updateid' value='{$updateid}' />";
        echo "<table class='vertical'>";
        echo "<tr><th><label for='search_string'>";
        echo icon('contact', 16);
        echo " {$strContact}</label></th><td>\n";
        echo "<input type='text' name='search_string' id='search_string' size='30' value='{$query}' />\n";
        echo "<input name='submit' type='submit' value='{$strFindContact}' />";
        echo "<div id='search_string_choices' class='autocomplete'></div>";
        echo autocomplete('search_string', 'autocomplete_sitecontact', 'search_string_choices');
        echo "<input type='hidden' name='win' value='{$win}' />";

        echo "</td></tr>";
        echo "</table>";
        echo "<p align='center'><a href='contacts.php'>{$strBrowseContacts}</a>...</p>";
        echo "<input name='siteid' type='hidden' value='$siteid' />";
        echo "</form>\n";
    }
    else
    {
        echo "<p align='center'>{$strContact} {$contactid}</p>";

    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == 'findcontact')
{
    //  Search for the contact specified in the maintenance contracts and display a list of choices
    // This Page Is Valid XHTML 1.0 Transitional! 27Oct05

    // Are we using LDAP?
    if ( $CONFIG["use_ldap"] )
    {
        // Do we want to autocreate the customer from LDAP?
        if ( $CONFIG["ldap_autocreate_customer"] )
        {
            // Import the user from LDAP
            ldapImportCustomerFromEmail($from);
        }
    }

    // check for blank or very short search field - otherwise this would find too many results
    if (empty($contactid) && mb_strlen($search_string) < 2)
    {
        header("Location: {$_SERVER['PHP_SELF']}");
        exit;
    }
    // Filter by contact
    $contactsql = "AND (c.surname LIKE '%{$search_string}%' OR c.forenames LIKE '%{$search_string}%' ";
    // Use SOUNDEX if the system is set to use English (See Mantis 879)
    if (strtolower(substr($CONFIG['default_i18n'], 0 ,2)) == 'en')
    {
        $contactsql .= "OR SOUNDEX('{$search_string}') = SOUNDEX(CONCAT_WS(' ', c.forenames, c.surname)) ";
        $contactsql .= "OR SOUNDEX('{$search_string}') = SOUNDEX(CONCAT_WS(', ', c.surname, c.forenames)) ";
    }
    $contactsql .= "OR s.name LIKE '%{$search_string}%') ";

    $sql  = "SELECT p.name AS productname, p.id AS productid, c.surname AS surname, ";
    $sql .= "m.id AS maintid, m.incident_quantity, m.incidents_used, m.expirydate, m.term, s.name AS name, ";
    $sql .= "c.id AS contactid, s.id AS siteid, c.forenames, m.servicelevel ";
    $sql .= "FROM `{$dbSupportContacts}` AS sc, `{$dbContacts}` AS c, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p, `{$dbSites}` AS s ";
    $sql .= "WHERE m.product = p.id ";
    $sql .= "AND m.site = s.id ";
    $sql .= "AND sc.contactid = c.id ";
    $sql .= "AND sc.maintenanceid = m.id ";
    if (empty($contactid))
    {
        $sql .= $contactsql;
    }
    else
    {
        $sql .= "AND c.id = '{$contactid}' ";
    }
    if (!empty($CONFIG['hide_contracts_older_than_when_opening_incident']) AND $CONFIG['hide_contracts_older_than_when_opening_incident'] != -1)
    {
        $sql .= "AND FROM_UNIXTIME(m.expirydate) >= DATE_SUB(CURDATE(), INTERVAL {$CONFIG['hide_contracts_older_than_when_opening_incident']} DAY) ";
    }

    $sql .= "UNION SELECT p.name AS productname, p.id AS productid, c.surname AS surname, ";
    $sql .= "m.id AS maintid, m.incident_quantity, m.incidents_used, m.expirydate, m.term, s.name AS name, ";
    $sql .= "c.id AS contactid, s.id AS siteid, c.forenames, m.servicelevel ";
    $sql .= "FROM `{$dbContacts}` AS c, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p, `{$dbSites}` AS s ";
    $sql .= "WHERE m.product = p.id ";
    $sql .= "AND m.site = s.id ";
    $sql .= "AND m.site = c.siteid ";
    $sql .= "AND m.allcontactssupported='yes' ";
    if (empty($contactid))
    {
        $sql .= $contactsql;
    }
    else
    {
        $sql .= "AND c.id = '{$contactid}' ";
    }
    if (!empty($CONFIG['hide_contracts_older_than_when_opening_incident']) AND $CONFIG['hide_contracts_older_than_when_opening_incident'] != -1)
    {
        $sql .= "AND FROM_UNIXTIME(m.expirydate) >= DATE_SUB(CURDATE(), INTERVAL {$CONFIG['hide_contracts_older_than_when_opening_incident']} DAY) ";
    }

    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
    if (mysqli_num_rows($result) > 0)
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');

        echo "<h2>".icon('new', 32)." {$strNewIncident} - {$strSelect} {$strContract} / {$strContact}</h2>";
        echo "<h3>".icon('contract', 32)." {$strContracts}</h3>";
        echo "<p align='center'>".sprintf($strListShowsContracts, $strNewIncident).".</p>";

        $str_prefered = '';
        $str_alternative = '';

        $headers = "<tr><th>&nbsp;</th><th>{$strName}</th><th>{$strSite}</th>";
        $headers .= "<th>{$strContract}</th><th>{$strServiceLevel}</th>";
        $headers .= "<th>{$strExpiryDate}</th></tr>";

        while ($contactobj = mysqli_fetch_object($result))
        {
            if (empty($CONFIG['preferred_maintenance']) OR
                (is_array($CONFIG['preferred_maintenance']) AND
                    in_array($contactobj->servicelevel, $CONFIG['preferred_maintenance'])))
            {
                $str_prefered .= to_row($contactobj);
            }
            else
            {
                $str_alternative .= to_row($contactobj);
            }
        }

        if (!empty($str_prefered))
        {
            if (!empty($str_alternative))
            {
                echo "<h3>{$strPreferred}</h3>";
            }
            echo "<table class='maintable'>";
            echo $headers;
            echo $str_prefered;
            echo "</table>\n";
        }

        // NOTE: these BOTH need to be shown as you might wish to log against an alternative contract

        if (!empty($str_alternative))
        {
            if (!empty($str_prefered)) echo "<h3>{$strAlternative}</h3>";
            echo "<table class='maintable'>";
            echo $headers;
            echo $str_alternative;
            echo "</table>\n";
        }

        if (empty($str_prefered) AND empty($str_alternative))
        {
            echo user_alert($strNothingToDisplay, E_USER_NOTICE);
        }

        // Select the contact from the list of contacts as well
        $sql = "SELECT *, c.id AS contactid FROM `{$dbContacts}` AS c, `{$dbSites}` AS s WHERE c.siteid = s.id ";
        if (empty($contactid))
        {
            $sql .= "AND (surname LIKE '%{$search_string}%' OR forenames LIKE '%{$search_string}%' OR s.name LIKE '%{$search_string}%' ";
            $sql .= "OR CONCAT_WS(' ', forenames, surname) LIKE '{$search_string}') ";
        }
        else $sql .= "AND c.id = '{$contactid}' ";

        $sql .= "ORDER by c.surname, c.forenames ";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

        if (mysqli_num_rows($result) > 0)
        {
            $html = "<h3>".icon('contact', 32, $strContact)." ";
            $html .= "{$strContacts}</h3>\n";
            $html .=  "<p align='center'>{$strListShowsContacts}.</p>";
            $html .=  "<table class='maintable'>";
            $html .=  "<tr>";
            $html .=  "<th>&nbsp;</th>";
            $html .=  "<th>{$strName}</th>";
            $html .=  "<th>{$strSite}</th>";
            $html .=  "</tr>\n";

            $customermatches = 0;
            while ($contactobj = mysqli_fetch_object($result))
            {
                $html .=  "<tr class='shade2'>";
                $site_incident_pool = db_read_column('freesupport', $dbSites, $contactobj->siteid);
                if ($site_incident_pool > 0)
                {
                    $html .=  "<td><a href=\"{$_SERVER['PHP_SELF']}?action=";
                    $html .= "incidentform&amp;type=free&amp;contactid=";
                    $html .= $contactobj->contactid."&amp;updateid={$updateid}";
                    $html .= "&amp;win={$win}\"";
                    if ($_SESSION['userconfig']['show_confirmation_caution'] == 'TRUE')
                    {
                        $html .= " onclick=\"return confirm_action('{$strSiteAreYouSure}');\"";
                    }
                    $html .= ">";
                    $html .=  "{$strNewSiteSupportIncident}</a> (";
                    $html .= sprintf($strRemaining,$site_incident_pool).")</td>";
                    $customermatches++;
                }
                else
                {
                    $html .=  "<td class='expired'>{$strZeroRemaining}</td>";
                }
                $html .= "<td>{$contactobj->forenames} {$contactobj->surname}</td>";
                $html .= '<td>'.site_name($contactobj->siteid).'</td>';
                $html .= "</tr>\n";
            }
            $html .=  "</table>\n";
            $html .= "<p align='center'><a href='contact_new.php?name=" . urlencode(htmlspecialchars($search_string, ENT_QUOTES, $i18ncharset)) . "&amp;return=addincident'>{$strNewContact}</a></p>";

            if ($customermatches > 0)
            {
                echo $html;
            }
            unset($html, $customermatches);
        }
        else
        {
            echo "<h3>".sprintf($strNoResultsFor, $strContacts)."</h3>";
            echo "<p align='center'><a href=\"contact_new.php?name=" . urlencode(htmlspecialchars($search_string, ENT_QUOTES, $i18ncharset)) . "&amp;return=addincident\">{$strNewContact}</a></p>";
        }
        echo "<p align='center'><a href=\"{$_SERVER['PHP_SELF']}?updateid={$updateid}&amp;win={$win}\">{$strSearchAgain}</a></p>";
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
    else
    {
        // This Page Is Valid XHTML 1.0 Transitional! 27Oct05
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        if (!empty($search_string)) $match = "'$search_string'";
        if (!empty($contactid)) $match = "{$strContact} {$strID} {$contactid}";
        echo "<h3>".sprintf($strSorryNoRecordsMatchingX, $match)."</h3>\n";
        if ($contactid > 0)
        {
            echo "<p align='center'><a href='contract_new_contact.php?contactid={$contactid}&amp;context=contact'>{$strAssociateContactWithContract}</a></p>";
        }
        echo "<p align='center'><a href=\"incident_new.php?updateid=$updateid&amp;win={$win}\">{$strSearchAgain}</a></p>";
        
        if (!empty($incomingid))
        {
            
            $tsql = "SELECT `from` FROM `{$dbTempIncoming}` WHERE id = {$incomingid}";
            $tresult = mysqli_query($db, $tsql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
            list($email) = mysqli_fetch_row($tresult);
        }
        
        // Select the contact from the list of contacts as well
        $sql = "SELECT *, c.id AS contactid FROM `{$dbContacts}` AS c, `{$dbSites}` AS s WHERE c.siteid = s.id ";
        if (empty($contactid))
        {
            $sql .= "AND (surname LIKE '%{$search_string}%' OR forenames LIKE '%{$search_string}%' OR s.name LIKE '%{$search_string}%' ";
            $sql .= "OR CONCAT_WS(' ', forenames, surname) = '{$search_string}' )";
        }
        else $sql .= "AND c.id = '{$contactid}' ";
        $sql .= "ORDER by c.surname, c.forenames ";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);

        if (mysqli_num_rows($result) > 0)
        {
            $html = "<h3>{$strCustomers}</h3>\n";
            $html .= "<p align='center'>{$strThisListShowsCustomers}</p>";
            $html .= "<table class='maintable'>";
            $html .= "<tr>";
            $html .= "<th>&nbsp;</th>";
            $html .= "<th>{$strName}</th>";
            $html .= "<th>{$strSite}</th>";
            $html .= "</tr>\n";

            $customermatches = 0;
            while ($contactobj = mysqli_fetch_object($result))
            {
                $html .= "<tr class='shade2'>";
                $site_incident_pool = db_read_column('freesupport', $dbSites, $contactobj->siteid);
                if ($site_incident_pool > 0)
                {
                    $html .= "<td><a href=\"{$_SERVER['PHP_SELF']}?action=incidentform&amp;type=free&amp;contactid={$contactobj->contactid}&amp;updateid={$updateid}&amp;win={$win}\" onclick=\"return confirm_free();\">";
                    $html .= "{$strNewSiteSupportIncident}</a> ({$site_incident_pool})</td>";
                    $customermatches++;
                }
                else
                {
                    $html .= "<td class='expired'>{$strZeroRemaining}</td>";
                }
                $html .= "<td>{$contactobj->forenames} {$contactobj->surname}</td>";
                $html .= '<td>'.site_name($contactobj->siteid).'</td>';
                $html .= "</tr>\n";
            }
            $html .= "</table>\n";

            if ($customermatches > 0)
            {
                echo $html;
            }

            echo "<p align='center'><a href='contact_new.php?name=".urlencode($search_string)."&amp;email=".urlencode($email)."&amp;return=addincident'>{$strNewContact}</a></p>\n";
        }
        else
        {
            echo "<h3>".sprintf($strNoResultsFor, $strContacts)."</h3>";
            echo "<p align='center'><a href=\"contact_new.php?name=".urlencode($search_string)."&amp;email=".urlencode($email)."&amp;return=addincident\">{$strNewContact}</a></p>\n";
        }

        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
}
elseif ($action == 'incidentform')
{
    // Display form to get details of the actual incident
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('new', 32)." {$strNewIncident} - {$strDetails}</h2>";

    echo show_form_errors('newincident');
    clear_form_errors('newincident');
    
    plugin_do('incident_new');

    $slatag = contract_slatag($maintid);
    $maxprority = servicelevel_maxpriority($slatag);

    echo "<form action='{$_SERVER['PHP_SELF']}?action=assign'";
    echo " method='post' id='supportdetails' name='supportdetails' onsubmit=\"return validate_field('incidenttitle', '{$strYouMustEnterIncidentTitle}')\">";
    echo "<input type='hidden' name='type' value=\"{$type}\" />";
    echo "<input type='hidden' name='contactid' value=\"{$contactid}\" />";
    echo "<input type='hidden' name='productid' value=\"{$productid}\" />";
    echo "<input type='hidden' name='maintid' value=\"{$maintid}\" />";
    echo "<input type='hidden' name='siteid' value=\"{$siteid}\" />";

    if (!empty($updateid))
    {
        echo "<input type='hidden' name='updateid' value='{$updateid}' />";
    }

    echo "<table class='vertical' width='90%'>";
    echo "<tr><td>";
    $contactemail = contact_email($contactid);
    echo "<a href=\"mailto:{$contactemail}\">".icon('contact', 16, '', $contactemail)."</a>";
    echo " <strong>".contact_realname($contactid)."</strong> <span style='font-size:80%;'>(<a href='contact_edit.php?action=edit&amp;";
    echo "contact={$contactid}'>{$strEdit}</a>)</span>, ";
    echo contact_site($contactid) . " ";
    echo "{$strTel}: ".contact_phone($contactid);
    echo "</td>";

    echo "<td>";
    echo icon('contract', 16) . " <strong>{$strContract} {$maintid}</strong>: ";
    echo strip_tags($producttext);
    echo "</td></tr>";

    echo "<tr>";
    echo "<td><label for='customerid'>{$strCustomerReference}: </label><input maxlength='50' name='customerid' id='customerid' value='".show_form_value('newincident', 'customerid', '')."'/></td>";
    echo "</tr>";

    if (empty($updateid))
    {
        echo "<tr><td><label for='incidenttitle'>{$strIncidentTitle}</label><br />";
        echo "<input class='required' maxlength='200' id='incidenttitle' ";
        echo "name='incidenttitle' size='50' type='text' value='".show_form_value('newincident', 'incidenttitle', '')."' />";
        echo " <span class='required'>{$strRequired}</span></td>\n";
        echo "<td>";
        if ($type == 'free')
        {
            echo "<label>{$strServiceLevel}".serviceleveltag_drop_down('servicelevel', $CONFIG['default_service_level'], TRUE)."</label><br />";
            echo "<label>{$strSkill}: ".skill_drop_down('software', 0)."</label>";
        }
        else
        {
            echo "<label for='software'>{$strSkill}</label><br />".softwareproduct_drop_down('software', 0, $productid);
        }
        echo " <label>{$strVersion}: <input maxlength='50' name='productversion' size='8' type='text' value='".show_form_value('newincident', 'productversion', '')."' /></label> \n";
        echo " <label>{$strServicePacksApplied}: <input maxlength='100' name='productservicepacks' size='8' type='text' value='".show_form_value('newincident', 'productservicepacks', '')."' /></label>\n";
        echo "</td></tr>";

        // Inventory
        $items_array[0] = '';
        $sql = "SELECT * FROM `{$dbInventory}` ";
        $sql .= "WHERE contactid='{$contactid}' ";
        $result = mysqli_query($db, $sql);
        $contact_inv_count = mysqli_num_rows($result);
        while ($items = mysqli_fetch_object($result))
        {
            $var = $items->name;
            if (!empty($items->identifier))
            {
                $var .= " ({$items->identifier})";
            }
            elseif (!empty($items->address))
            {
                $var .= " ({$items->address})";
            }
            $items_array[$items->id] = $var;
        }

        // Don't show inventory section if non available for contact
        if ($contact_inv_count > 0)
        {
            echo "<tr><td><label for='inventory'>{$strInventoryItems}:</label><br />";
            echo array_drop_down($items_array, 'inventory', show_form_value('newincident', 'inventory', ''), TRUE)."</td><td></td></tr>";
        }

        // Insert pre-defined per-product questions from the database, these should be required fields
        // These 'productinfo' questions don't have a GUI as of 27Oct05
        $sql = "SELECT * FROM `{$dbProductInfo}` WHERE productid='{$productid}'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
        $numquestions = mysqli_num_rows($result);
        $cellcount = 1;
        while ($productinfoobj = mysqli_fetch_object($result))
        {
            if ($cellcount & 1) echo "<tr>";
            echo "<td>";
            echo "<label>{$productinfoobj->information}";
            if ($productinfoobj->moreinformation != '')
            {
                echo " (<em>{$productinfoobj->moreinformation}</em>)";
            }
            echo "<br />\n";
            echo "<input class='required' maxlength='50' ";
            echo "name='pinfo{$productinfoobj->id}' size='70' type='text' value='".show_form_value('newincident', "pinfo{$productinfoobj->id}", '')."'/>";
            echo " <span class='required'>{$strRequired}</span>";
            echo "</label>";
            echo "</td>";
            $cellcount++;
            if ($cellcount > $numquestions AND $numquestions & 1)
            {
                echo "<td></td></tr>";
            }
            elseif ($cellcount & 1)  echo "</tr>";
        }
        echo "<tr><td><label for='probdesc'>{$strProblemDescription}</label>".help_link('ProblemDescriptionEngineer')."<br />";
        echo "<textarea id='probdesc' name='probdesc' rows='2' cols='60' onfocus='new ResizeableTextarea(this);'>".show_form_value('newincident', 'probdesc', '')."</textarea></td>\n";
        echo "<td><label for='probreproduction'>{$strProblemReproduction}</label>".help_link('ProblemReproductionEngineer')."<br />";
        echo "<textarea id='probreproduction' name='probreproduction' rows='2' cols='60' onfocus='new ResizeableTextarea(this);'>".show_form_value('newincident', 'probreproduction', '')."</textarea></td></tr>\n";
        echo "<tr><td><label for='workarounds'>{$strWorkAroundsAttempted}</label>".help_link('WorkAroundsAttemptedEngineer')."<br />";
        echo "<textarea id='workarounds' name='workarounds' rows='2' cols='60' onfocus='new ResizeableTextarea(this);'>".show_form_value('newincident', 'workarounds', '')."</textarea></td>\n";
        echo "<td><label for ='custimpact'>{$strCustomerImpact}</label>".help_link('CustomerImpactEngineer')."<br />";
        echo "<textarea id='custimpact' name='custimpact' rows='2' cols='60' onfocus='new ResizeableTextarea(this);'>".show_form_value('newincident', 'custimpact', '')."</textarea></td></tr>\n";
    }
    else
    {
        $sql = "SELECT bodytext FROM `{$dbUpdates}` WHERE id={$updateid}";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        list($mailed_body_text) = mysqli_fetch_assoc($result);

        $sql = "SELECT subject FROM `{$dbTempIncoming}` WHERE updateid={$updateid}";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        list($mailed_subject) = mysqli_fetch_assoc($result);

        echo "<tr><td><label for='incidenttitle'>{$strIncidentTitle}</label><br />";
        echo "<input class='required' maxlength='200' id='incidenttitle' ";
        echo "name='incidenttitle' size='50' type='text' value=\"".htmlspecialchars($mailed_subject, ENT_QUOTES)."\" />";
        echo " <span class='required'>{$strRequired}</span></td>\n";
        echo "<td>";
        if ($type == 'free')
        {
            echo "<strong>{$strSiteSupport}</strong>: <br />";
            echo "{$strServiceLevel} ".serviceleveltag_drop_down('servicelevel',$CONFIG['default_service_level'], TRUE);
            echo " {$strSkill} ".skill_drop_down('software', 0);
        }
        else
        {
            echo "<label for='software'>{$strSkill}</label><br />".softwareproduct_drop_down('software', 1, $productid);
        }
        echo "</td></tr>";

        echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
        echo "<tr><th>{$strProblemDescription}<br /><span style='font-weight: normal'>{$strReceivedByEmail}</span></th>";
        echo "<td>".parse_updatebody($mailed_body_text)."</td></tr>\n";
        echo "<tr><td class='shade1' colspan='2'>&nbsp;</td></tr>\n";
    }

    echo "<tr><td><strong>{$strNextAction}</strong><br />";
    echo show_next_action('supportdetails');
    echo "</td>";
    echo "<td colspan='2'>";
    echo "<strong>{$strVisibleToCustomer}</strong><br />\n";
    
    $cust_vis_checked = $send_email_checked = '';
    if ($_SESSION['formdata']['newincident']['cust_vis'] == 'on') $cust_vis_checked = "checked='checked'"; 
    if ($_SESSION['formdata']['newincident']['send_email'] == 'on') $send_email_checked = "checked='checked'";
    
    echo "<label><input name='cust_vis' type='checkbox' {$cust_vis_checked} /> {$strVisibleToCustomer}</label>";
    echo help_link('VisibleToCustomer')."<br />";
    echo "<label><input name='send_email' type='checkbox' {$send_email_checked} /> ";
    echo "{$strSendOpeningEmailDesc}</label><br />";
    echo "<strong>{$strPriority}</strong><br />".priority_drop_down("priority", show_form_value('newincident', 'priority', PRIORITY_LOW), $maxprority, FALSE)." </td></tr>";
    plugin_do('incident_new_form');
    echo "</table>\n";
    echo "<input type='hidden' name='win' value='{$win}' />";
    echo "<p align='center'><input name='submit' type='submit' value='{$strNewIncident}' /></p>";
    echo "</form>\n";

    clear_form_data('newincident');

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == 'assign')
{
    // Assign SUPPORT incident
    // The incident will be added to the database assigned to the current user, and then a list of engineers
    // is displayed so that the incident can be redirected
    
    // External vars
    $servicelevel = cleanvar($_REQUEST['servicelevel']);
    $type = cleanvar($_REQUEST['type']);
    $incidenttitle = cleanvar($_REQUEST['incidenttitle']);
    $probdesc = cleanvar($_REQUEST['probdesc']);
    $workarounds = cleanvar($_REQUEST['workarounds']);
    $probreproduction = cleanvar($_REQUEST['probreproduction']);
    $custimpact = cleanvar($_REQUEST['custimpact']);
    $other = cleanvar($_REQUEST['other']);
    $priority = clean_int($_REQUEST['priority']);
    $software = clean_int($_REQUEST['software']);
    $productversion = cleanvar($_REQUEST['productversion']);
    $productservicepacks = cleanvar($_REQUEST['productservicepacks']);
    $bodytext = cleanvar($_REQUEST['bodytext']);
    $cust_vis = cleanvar($_REQUEST['cust_vis']);
    $send_email = cleanvar($_REQUEST['send_email']);
    $inventory = cleanvar($_REQUEST['inventory']);
    
    $timetonextaction = cleanvar($_POST['timetonextaction']);
    $date = cleanvar($_POST['date']);
    $time_picker_hour = cleanvar($_REQUEST['time_picker_hour']);
    $time_picker_minute = cleanvar($_REQUEST['time_picker_minute']);
    $timetonextaction_days = cleanvar($_POST['timetonextaction_days']);
    $timetonextaction_hours = cleanvar($_POST['timetonextaction_hours']);
    $timetonextaction_minutes = cleanvar($_POST['timetonextaction_minutes']);
    
    $customerid = cleanvar($_POST['customerid']);
    
    $_SESSION['formdata']['newincident'] = cleanvar($_POST, TRUE, FALSE, FALSE);
    
    if ($send_email == 'on')
    {
        $send_email = 1;
    }
    else
    {
        $send_email = 0;
    }
    
    // check form input
    $errors = 0;
    // check for blank contact
    if ($contactid == 0)
    {
        $_SESSION['formerrors']['newincident']['account'] = $strYouMustSelectAcontact;
        $errors++;
    }
    
    // check for blank title
    if ($incidenttitle == '')
    {
        $incidenttitle = $strUntitled;
    }
    
    if ($software < 1)
    {
        $_SESSION['formerrors']['newincident']['skill'] = sprintf($strFieldMustNotBeBlank, $strSkill);
        $errors++;
    }
    
    // check for blank priority
    if ($priority == 0)
    {
        $priority = PRIORITY_LOW;
    }
    
    if ($type == 'free' AND $servicelevel == '' )
    {
        $_SESSION['formerrors']['newincident']['servicelevel'] = $strYouMustSelectAserviceLevel;
        $errors++;
    }
        
    if (!in_array($type, array('support', 'free')))
    {
        $_SESSION['formerrors']['newincident']['type'] = $strIncidentTypeWasBlank; // TODO Not quite right but near enought, the type was one we don't recognise 
        $errors++;
    }
    
    if ($errors > 0)
    {
        html_redirect("{$_SERVER['PHP_SELF']}?action=incidentform&type={$type}&contactid={$contactid}&maintid={$maintid}&producttext={$producttext}&productid={$productid}&updateid={$updateid}&siteid={$siteid}&win={$win}", FALSE);
    }
    else
    {
        plugin_do('incident_new_submitted');

        // add incident (assigned to current user)

        // Calculate the time to next action
        switch ($timetonextaction)
        {
            case 'none':
                $timeofnextaction = 0;
                break;
            case 'time':
                $timeofnextaction = calculate_time_of_next_action($timetonextaction_days, $timetonextaction_hours, $timetonextaction_minutes);
                break;
            case 'date':
                $date = explode("-", $date);
                $timeofnextaction = mktime($time_picker_hour, $time_picker_minute, 0, $date[1], $date[2], $date[0]);
                if ($timeofnextaction < 0) $timeofnextaction = 0;
                break;
            default:
                $timeofnextaction = 0;
                break;
        }

        if ($timeofnextaction > 0)
        {
            $timetext = "Next Action Time: ";
            $timetext .= date("D jS M Y @ g:i A", $timeofnextaction);
            $timetext .= "</b>\n\n";
            $bodytext = $timetext.$bodytext;
        }

        // Set the service level the contract
        if ($servicelevel == '')
        {
            $servicelevel = maintenance_servicelevel_tag($maintid);
        }

        // Use default service level if we didn't find one above
        if ($servicelevel == '')
        {
            $servicelevel = $CONFIG['default_service_level'];
        }

        if ($CONFIG['use_ldap'])
        {
            // Attempt to update contact
            $sql = "SELECT username, contact_source FROM `{$GLOBALS['dbContacts']}` WHERE id = {$contactid}";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
            $obj = mysqli_fetch_object($result);
            if ($obj->contact_source == 'ldap')
            {
                authenticateLDAP($obj->username, '', $contactid, false, true, false);
            }
        }

        // Check the service level priorities, look for the highest possible and reduce the chosen priority if needed
        $sql = "SELECT priority FROM `{$dbServiceLevels}` WHERE tag='{$servicelevel}' ORDER BY priority DESC LIMIT 1";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
        list($highestpriority) = mysqli_fetch_row($result);
        if ($priority > $highestpriority)
        {
            $prioritychangedmessage = " (".sprintf($strReducedPrioritySLA, priority_name($priority)).")";
            $priority = $highestpriority;
        }

        $sql  = "INSERT INTO `{$dbIncidents}` (title, owner, contact, priority, servicelevel, status, type, maintenanceid, ";
        $sql .= "product, softwareid, productversion, productservicepacks, opened, lastupdated, timeofnextaction, customerid) ";
        $sql .= "VALUES ('{$incidenttitle}', '{$sit[2]}', '{$contactid}', '{$priority}', '{$servicelevel}', '1}', 'Support', '{$maintid}', ";
        $sql .= "'{$productid}', '{$software}', '{$productversion}', '{$productservicepacks}', '{$now}', '{$now}', '{$timeofnextaction}', '{$customerid}')";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        $incidentid = mysqli_insert_id($db);
        $_SESSION['incidentid'] = intval($incidentid);

        // Save productinfo if there is some
        $sql = "SELECT * FROM `{$dbProductInfo}` WHERE productid='{$productid}'";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        if (mysqli_num_rows($result) > 0)
        {
            while ($productinforow = mysqli_fetch_object($result))
            {
                $var = "pinfo{$productinforow->id}";
                $pinfo = cleanvar($_POST[$var]);
                $pinfo = clean_dbstring($_POST[$var]);
                $pisql = "INSERT INTO `{$dbIncidentProductInfo}` (incidentid, productinfoid, information) ";
                $pisql .= "VALUES ('{$incidentid}', '{$productinforow->id}', '{$pinfo}')";
                mysqli_query($db, $pisql);
                if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
            }
        }

        $updatetext = clean_lang_dbstring($_SESSION['syslang']['strPriority']) . ": [b]" . priority_name($priority, TRUE) . "[/b]";
        if (!empty($prioritychangedmessage)) $updatetext .= $prioritychangedmessage;
        $updatetext .= "\n\n" . $bodytext;
        if ($probdesc != '') $updatetext .= "<b>" . clean_lang_dbstring($_SESSION['syslang']['strProblemDescription']) . "</b>\n" . $probdesc . "\n\n";
        if ($workarounds != '') $updatetext .= "<b>" . clean_lang_dbstring($_SESSION['syslang']['strWorkAroundsAttempted']) . "</b>\n" . $workarounds . "\n\n";
        if ($probreproduction != '') $updatetext .= "<b>" . clean_lang_dbstring($_SESSION['syslang']['strProblemReproduction']) . "</b>\n" . $probreproduction . "\n\n";
        if ($custimpact != '') $updatetext .= "<b>" . clean_lang_dbstring($_SESSION['syslang']['strCustomerImpact']) . "</b>\n" . $custimpact . "\n\n";
        if ($other != '') $updatetext .= "<b>" . clean_lang_dbstring($_SESSION['syslang']['strDetails']) . "</b>\n" . $other . "\n";
        if ($cust_vis == "on") $customervisibility = 'show';
        else $customervisibility = 'hide';

        if (!empty($updateid))
        {
            // Assign existing update to new incident if we have one
            $sql = "UPDATE `{$dbUpdates}` SET incidentid='{$incidentid}', userid='{$sit[2]}', sla='opened' WHERE id='{$updateid}'";

            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

            $old_path = $CONFIG['attachment_fspath']. 'updates' . DIRECTORY_SEPARATOR;
            $new_path = $CONFIG['attachment_fspath'] . $incidentid . DIRECTORY_SEPARATOR;

            //move attachments from updates to incident
            $sql = "SELECT linkcolref, filename FROM `{$dbLinks}` AS l, ";
            $sql .= "`{$dbFiles}` AS f ";
            $sql .= "WHERE l.origcolref = '{$updateid}' ";
            $sql .= "AND l.linktype = 5 ";
            $sql .= "AND l.linkcolref = f.id";
            $result = mysqli_query($db, $sql);
            if ($result)
            {
                if (!file_exists($new_path))
                {
                    $umask = umask(0000);
                    @mkdir($new_path, 0770);
                    umask($umask);
                }

                while ($row = mysqli_fetch_object($result))
                {
                    $filename = $row->linkcolref . "-" . $row->filename;
                    $old_file = $old_path . $row->linkcolref;
                    if (file_exists($old_file))
                    {
                        $rename = rename($old_file, $new_path . $filename);
                        if (!$rename)
                        {
                            trigger_error("Couldn't move file: {$file}", E_USER_WARNING);
                            $moved_attachments = FALSE;
                        }
                    }
                }
            }
            //remove from tempincoming to prevent build up
            $sql = "DELETE FROM `{$dbTempIncoming}` WHERE updateid='{$updateid}'";
            mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
        }
        else
        {
            // Create a new update from details entered
            $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, bodytext, timestamp, currentowner, ";
            $sql .= "currentstatus, customervisibility, nextaction, sla) ";
            $sql .= "VALUES ('{$incidentid}', '{$sit[2]}', 'opening', '{$updatetext}', '{$now}', '{$sit[2]}', ";
            $sql .= "'1', '{$customervisibility}', '{$nextaction}', 'opened')";
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
        }

        $sql = "SELECT * FROM `{$dbServiceLevels}` WHERE tag='{$servicelevel}' AND priority='{$priority}' ";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        $level = mysqli_fetch_object($result);

        $targetval = $level->initial_response_mins * 60;
        $initialresponse = $now + $targetval;

        // Insert the first Review update, this indicates the review period of an incident has started
        // This insert could possibly be merged with another of the 'updates' records, but for now we keep it seperate for clarity
        $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, bodytext) ";
        $sql .= "VALUES ('{$incidentid}', '{$sit[2]}', 'reviewmet', '{$now}', '{$sit[2]}', '1', 'hide', '')";
        mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

        if (!empty($inventory) AND $inventory != 0)
        {
            $sql = "INSERT INTO `{$dbLinks}`(linktype, origcolref, linkcolref, direction, userid) ";
            $sql .= "VALUES(7, '{$incidentid}', '{$inventory}', 'left', '{$sit[2]}')";
            mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);
        }

        plugin_do('incident_new_saved');

        // Decrement free support, where appropriate
        if ($type == 'free')
        {
            decrement_free_incidents(contact_siteid($contactid));
        }
        else
        {
            // decrement contract incident by incrementing the number of incidents used
            increment_incidents_used($maintid);
        }

        $billableincident = get_billable_object_from_incident_id($incidentid);
        if ($billableincident)
        {
            $toReturn = $billableincident->open_incident($incidentid);
        }

        $suggested_user = suggest_reassign_userid($incidentid);
        $trigger = new TriggerEvent('TRIGGER_INCIDENT_CREATED', array('incidentid' => $incidentid, 'sendemail' => $send_email));

        if ($CONFIG['auto_assign_incidents'])
        {
            clear_form_data('newincident');
            html_redirect("incident_new.php?action=reassign&userid={$suggested_user}&incidentid={$incidentid}");
            exit;
        }
        else
        {
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            
            echo "<h2>{$strNewIncident} - {$strAssign}</h2>";
            echo "<h3>{$strIncident}: {$incidentid}</h3>";
            echo "<p align='center'>";
            echo sprintf($strIncidentLoggedEngineer, $incidentid);
            echo "</p>\n";
                
        }

        // List Engineers
        // We need a user type 'engineer' so we don't just list everybody
        // Status zero means account disabled
        $sql = "SELECT * FROM `{$dbUsers}` WHERE status != " . USERSTATUS_ACCOUNT_DISABLED . " ORDER BY realname";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        echo "<h3>{$strUsers}</h3>
        <table class='maintable'>
        <tr>
            <th>&nbsp;</th>
            <th>{$strName}</th>
            <th>{$strTelephone}</th>
            <th>{$strStatus}</th>
            <th>{$strMessage}</th>
            <th colspan='5'>{$strIncidentsinQueue}</th>
            <th>{$strAccepting}</th>
        </tr>";
        echo "<tr>
        <th colspan='5'></th>
        <th align='center'>{$strActionNeeded} / {$strOther}</th>";
        echo "<th align='center'>".priority_icon(PRIORITY_CRITICAL)."</th>";
        echo "<th align='center'>".priority_icon(PRIORITY_HIGH)."</th>";
        echo "<th align='center'>".priority_icon(PRIORITY_MEDIUM)."</th>";
        echo "<th align='center'>".priority_icon(PRIORITY_LOW)."</th>";

        echo "<th></th>";
        echo "</tr>";

        $shade = 'shade2';
        while ($userobj = mysqli_fetch_object($result))
        {
            if ($userobj->id == $suggested_user) $shade = 'idle';
            echo "<tr class='{$shade}'>";
            // display reassign link only if person is accepting or if the current user has 'reassign when not accepting' permission
            if ($userobj->accepting == 'Yes')
            {
                echo "<td align='right'><a href=\"{$_SERVER['PHP_SELF']}?action=reassign&amp;userid={$userobj->id}&amp;incidentid={$incidentid}&amp;nextaction=".urlencode($nextaction)."&amp;win={$win}\" ";
                // if ($priority >= 3) echo " onclick=\"alertform.submit();\"";
                echo ">{$strAssignTo}</a></td>";
            }
            elseif (user_permission($sit[2], PERM_INCIDENT_FORCE_ASSIGN) OR $userobj->id == $sit[2])
            {
                echo "<td align='right'><a href=\"{$_SERVER['PHP_SELF']}?action=reassign&amp;userid={$userobj->id}&amp;incidentid={$incidentid}&amp;nextaction=".urlencode($nextaction)."&amp;win={$win}\" ";
                // if ($priority >= 3) echo " onclick=\"alertform.submit();\"";
                echo ">{$strForceTo}</a></td>";
            }
            else
            {
                echo "<td class='expired'>&nbsp;</td>";
            }
            echo "<td>";

            // Have a look if this user has skills with this software
            $ssql = "SELECT softwareid FROM `{$dbUserSoftware}` ";
            $ssql .= "WHERE userid='{$userobj->id}' AND softwareid='{$software}' ";
            $sresult = mysqli_query($db, $ssql);
            if (mysqli_num_rows($sresult) >= 1)
            {
                echo "<strong>{$userobj->realname}</strong>";
            }
            else
            {
                echo $userobj->realname;
            }

            echo "</td>";
            echo "<td>{$userobj->phone}</td>";
            echo "<td>".user_online_icon($userobj->id)." ".userstatus_name($userobj->status)."</td>";
            echo "<td>{$userobj->message}</td>";
            echo "<td align='center'>";

            $incpriority = user_incidents($userobj->id);
            $countincidents = ($incpriority['1'] + $incpriority['2'] + $incpriority['3'] + $incpriority['4']);

            if ($countincidents >= 1) $countactive = user_activeincidents($userobj->id);
            else $countactive = 0;

            $countdiff = $countincidents - $countactive;

            echo "{$countactive} / {$countdiff}</td>";
            echo "<td align='center'>{$incpriority['4']}</td>";
            echo "<td align='center'>{$incpriority['3']}</td>";
            echo "<td align='center'>{$incpriority['2']}</td>";
            echo "<td align='center'>{$incpriority['1']}</td>";

            echo "<td align='center'>";
            if ($userobj->accepting == 'Yes') echo $strYes;
            else echo "<span class='error'>{$strNo}</span>";
            echo "</td>";
            echo "</tr>\n";
            if ($shade == 'shade2') $shade = 'shade1';
            else $shade = 'shade2';
        }
        echo "</table>";
        echo "<p align='center'>{$strUsersBoldSkills}.</p>";
        
        clear_form_data('newincident');
        
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
}
elseif ($action == 'reassign')
{
    // External variables
    $incidentid = clean_int($_REQUEST['incidentid']);
    $uid = clean_int($_REQUEST['userid']);
    $nextaction = clean_dbstring($_REQUST['nextaction']);

    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>{$strIncidentAdded} - {$strSummary}</h2>";
    echo "<p align='center'>";
    $incidentnum = html_incident_popup_link($incidentid, sprintf($strIncidentNum, $incidentid));
    $queuename = "<strong style='color: red'>{$strActionNeeded}</strong>";
    $name = user_realname($uid);
    printf($strHasBeenAutoMovedToX, $incidentnum, $name, $queuename);
    echo help_link('AutoAssignIncidents')."</p><br /><br />";
    $userphone = user_phone($uid);
    if ($userphone != '')
    {
        echo "<h3>{$name} {$strContactDetails}</h3>";
        echo "<p align='center'>{$strTelephone}: {$userphone}</p>";
    }
    $sql = "UPDATE `{$dbIncidents}` SET owner='{$uid}', lastupdated='{$now}' WHERE id='{$incidentid}'";
    mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

    $t = new TriggerEvent('TRIGGER_INCIDENT_ASSIGNED', array('userid' => $uid, 'incidentid' => $incidentid));

    // add update
    $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, nextaction) ";
    $sql .= "VALUES ('{$incidentid}', '{$sit[2]}', 'reassigning', '{$now}', '{$uid}', '1', '{$nextaction}')";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_ERROR);

    clear_form_data('newincident');
    
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}

?>