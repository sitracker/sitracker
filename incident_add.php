<?php
// incident_add.php - Multi-page form for Adding Incidents
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>, Tom Gerrard
// 7Oct02 INL  Added support for maintenanceid to be put into incidents table

$permission = 5;
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strAddIncident;

function to_row($contactrow)
{
    global $now, $updateid, $CONFIG, $dbg;

    $str = '';
    if (($contactrow['expirydate'] < $now
        OR $contactrow['term'] == 'yes')
        AND $contactrow['expirydate'] != -1)
    {
        $class = 'expired';
    }
    else
    {
        $class = "shade2";
    }

    $incidents_remaining = $contactrow['incident_quantity'] - $contactrow['incidents_used'];

    $str = "<tr class='$class'>";
    if ($contactrow['expirydate'] < $now AND $contactrow['expirydate'] != '-1')
    {
        $str .=  "<td>{$GLOBALS['strExpired']}</td>";
    }
    elseif ($contactrow['term'] == 'yes')
    {
        $str .=  "<td>{$GLOBALS['strTerminated']}</td>";
    }
    elseif ($contactrow['incident_quantity'] >= 1 AND $contactrow['incidents_used'] >= $contactrow['incident_quantity'])
    {
        $str .= "<td class='expired'>{$GLOBALS['strZeroRemaining']} ({$contactrow['incidents_used']}/{$contactrow['incident_quantity']} {$strUsed})</td>";
    }
    else
    {
        $str .=  "<td><a href=\"{$_SERVER['PHP_SELF']}?action=incidentform&amp;type=support&amp;";
        $str .= "contactid=".$contactrow['contactid']."&amp;maintid=".$contactrow['maintid'];
        $str .= "&amp;producttext=".urlencode($contactrow['productname'])."&amp;productid=";
        $str .= $contactrow['productid']."&amp;updateid=$updateid&amp;siteid=".$contactrow['siteid'];
        $str .= "&amp;win={$win}\"";
        if ($_SESSION['userconfig']['show_confirmation_caution'] == 'TRUE')
        {
            $str .= " onclick=\"return confirm_support();\"";
        }
        $str .= ">{$GLOBALS['strAddIncident']}</a> ";
        if ($contactrow['incident_quantity'] == 0)
        {
            $str .=  "({$GLOBALS['strUnlimited']})";
        }
        else
        {
            $str .= "(".sprintf($GLOBALS['strRemaining'], $incidents_remaining).")";
        }
    }
    $str .=  "</td>";
    $str .=  '<td>'.$contactrow['forenames'].' '.$contactrow['surname'].'</td>';
    $str .=  '<td>'.$contactrow['name'].'</td>';
    $str .=  '<td>'.$contactrow['productname'].'</td>';
    $str .=  '<td>'.servicelevel_id2tag($contactrow['servicelevelid']).'</td>';
    if ($contactrow['expirydate'] == '-1')
    {
        $str .= "<td>{$GLOBALS['strUnlimited']}</td>";
    }
    else
    {
        $str .=  '<td>'.ldate($CONFIG['dateformat_date'], $contactrow['expirydate']).'</td>';
    }
    $str .=  "</tr>\n";
    return $str;
}

// External variables
$action = $_REQUEST['action'];
$context = cleanvar($_REQUEST['context']);
$updateid = cleanvar($_REQUEST['updateid']);
$incomingid = cleanvar($_REQUEST['incomingid']);
$query = cleanvar($_REQUEST['query']);
$siteid = cleanvar($_REQUEST['siteid']);
$contactid = cleanvar($_REQUEST['contactid']);
$search_string = cleanvar($_REQUEST['search_string']);
$from = cleanvar($_REQUEST['from']);
$type = cleanvar($_REQUEST['type']);
$maintid = cleanvar($_REQUEST['maintid']);
$productid = cleanvar($_REQUEST['productid']);
$producttext = cleanvar($_REQUEST['producttext']);
$win = cleanvar($_REQUEST['win']);

if (!empty($incomingid) AND empty($updateid)) $updateid = db_read_column('updateid', $dbTempIncoming, $incomingid);

if (empty($action) OR $action == 'showform')
{
    $pagescripts = array('AutoComplete.js');
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>".icon('add', 32)." {$strAddIncident} - {$strFindContact}</h2>";
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
        echo autocomplete('search_string', 'autocomplete_sitecontact');
        echo "<input type='hidden' name='win' value='{$win}' />";
        echo "<input name='submit' type='submit' value='{$strFindContact}' />";
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

    $search_string = mysql_real_escape_string(urldecode($_REQUEST['search_string']));
    // check for blank or very short search field - otherwise this would find too many results
    if (empty($contactid) && strlen($search_string) < 2)
    {
        header("Location: {$_SERVER['PHP_SELF']}");
        exit;
    }
    // Filter by contact
    $contactsql .= "AND (c.surname LIKE '%$search_string%' OR c.forenames LIKE '%$search_string%' ";
    $contactsql .= "OR SOUNDEX('$search_string') = SOUNDEX(CONCAT_WS(' ', c.forenames, c.surname)) ";
    $contactsql .= "OR SOUNDEX('$search_string') = SOUNDEX(CONCAT_WS(', ', c.surname, c.forenames)) ";
    $contactsql .= "OR s.name LIKE '%$search_string%') ";

    $sql  = "SELECT p.name AS productname, p.id AS productid, c.surname AS surname, ";
    $sql .= "m.id AS maintid, m.incident_quantity, m.incidents_used, m.expirydate, m.term, s.name AS name, ";
    $sql .= "c.id AS contactid, s.id AS siteid, c.forenames, m.servicelevelid ";
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
        $sql .= "AND c.id = '$contactid' ";
    }

    $sql .= "UNION SELECT p.name AS productname, p.id AS productid, c.surname AS surname, ";
    $sql .= "m.id AS maintid, m.incident_quantity, m.incidents_used, m.expirydate, m.term, s.name AS name, ";
    $sql .= "c.id AS contactid, s.id AS siteid, c.forenames, m.servicelevelid ";
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
        $sql .= "AND c.id = '$contactid' ";
    }
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) > 0)
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        ?>
        <script type="text/javascript">
        function confirm_support()
        {
            return window.confirm("<?php echo $strContractAreYouSure; ?>");
        }

        function confirm_free()
        {
            return window.confirm("<?php echo $strSiteAreYouSure; ?>");
        }
        </script>
        <?php
        echo "<h2>".icon('add', 32)." {$strAddIncident} - {$strSelect} {$strContract} / {$strContact}</h2>";
        echo "<h3>".icon('contract', 32)." {$strContracts}</h3>";
        echo "<p align='center'>".sprintf($strListShowsContracts, $strAddIncident).".</p>";

        $str_prefered = '';
        $str_alternative = '';

        $headers = "<tr><th>&nbsp;</th><th>{$strName}</th><th>{$strSite}</th>";
        $headers .= "<th>{$strContract}</th><th>{$strServiceLevel}</th>";
        $headers .= "<th>{$strExpiryDate}</th></tr>";

        while ($contactrow = mysql_fetch_array($result))
        {
            if (empty($CONFIG['preferred_maintenance']) OR
                (is_array($CONFIG['preferred_maintenance']) AND
                    in_array(servicelevel_id2tag($contactrow['servicelevelid']),
                                             $CONFIG['preferred_maintenance'])))
            {
                $str_prefered .= to_row($contactrow);
            }
            else
            {
                $str_alternative .= to_row($contactrow);
            }
        }

        if (!empty($str_prefered))
        {
            if (!empty($str_alternative))
            {
                echo "<h3>{$strPreferred}</h3>";
            }
            echo "<table align='center'>";
            echo $headers;
            echo $str_prefered;
            echo "</table>\n";
        }

        // NOTE: these BOTH need to be shown as you might wish to log against an alternative contract

        if (!empty($str_alternative))
        {
            if (!empty($str_prefered)) echo "<h3>{$strAlternative}</h3>";
            echo "<table align='center'>";
            echo $headers;
            echo $str_alternative;
            echo "</table>\n";
        }

        if (empty($str_prefered) AND empty($str_alternative))
        {
            echo "<p class='error'>{$strNothingToDisplay}</p>";
        }

        // Select the contact from the list of contacts as well
        $sql = "SELECT *, c.id AS contactid FROM `{$dbContacts}` AS c, `{$dbSites}` AS s WHERE c.siteid = s.id ";
        if (empty($contactid))
        {
            $sql .= "AND (surname LIKE '%$search_string%' OR forenames LIKE '%$search_string%' OR s.name LIKE '%$search_string%' ";
            $sql .= "OR CONCAT_WS(' ', forenames, surname) LIKE '$search_string') ";
        }
        else $sql .= "AND c.id = '$contactid' ";

        $sql .= "ORDER by c.surname, c.forenames ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($result) > 0)
        {
            $html = "<h3>".icon('contact', 32, $strContact)." ";
            $html .= "{$strContacts}</h3>\n";
            $html .=  "<p align='center'>{$strListShowsContacts}.</p>";
            $html .=  "<table align='center'>";
            $html .=  "<tr>";
            $html .=  "<th>&nbsp;</th>";
            $html .=  "<th>{$strName}</th>";
            $html .=  "<th>{$strSite}</th>";
            $html .=  "</tr>\n";

            $customermatches = 0;
            while ($contactrow = mysql_fetch_array($result))
            {
                $html .=  "<tr class='shade2'>";
                $site_incident_pool = db_read_column('freesupport', $dbSites,
                                                     $contactrow['siteid']);
                if ($site_incident_pool > 0)
                {
                    $html .=  "<td><a href=\"{$_SERVER['PHP_SELF']}?action=";
                    $html .= "incidentform&amp;type=free&amp;contactid=";
                    $html .= $contactrow['contactid']."&amp;updateid=$updateid";
                    $html .= "&amp;win={$win}\"";
                    if ($_SESSION['userconfig']['show_confirmation_caution'] == 'TRUE')
                    {
                        $html .= " onclick=\"return confirm_free();\"";
                    }
                    $html .= ">";
                    $html .=  "{$strAddSiteSupportIncident}</a> (";
                    $html .= sprintf($strRemaining,$site_incident_pool).")</td>";
                    $customermatches++;
                }
                else
                {
                    $html .=  "<td class='expired'>{$strZeroRemaining}</td>";
                }
                $html .=  '<td>'.$contactrow['forenames'].' '.$contactrow['surname'].'</td>';
                $html .=  '<td>'.site_name($contactrow['siteid']).'</td>';
                $html .=  "</tr>\n";
            }
            $html .=  "</table>\n";
            $html .= "<p align='center'><a href='contact_add.php?name=".urlencode($search_string)."&amp;return=addincident'>{$strAddContact}</a></p>";

            if ($customermatches > 0)
            {
                echo $html;
            }
            unset($html, $customermatches);
        }
        else
        {
            echo "<h3>".sprintf($strNoResultsFor, $strContacts)."</h3>";
            echo "<p align='center'><a href=\"contact_add.php?name=".urlencode($search_string)."&amp;return=addincident\">{$strAddContact}</a></p>";
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
        echo "<h2>".sprintf($strSorryNoRecordsMatchingX, $match)."</h2>\n";
        echo "<p align='center'><a href=\"incident_add.php?updateid=$updateid&amp;win={$win}\">{$strSearchAgain}</a></p>";
        // Select the contact from the list of contacts as well
        $sql = "SELECT *, c.id AS contactid FROM `{$dbContacts}` AS c, `{$dbSites}` AS s WHERE c.siteid = s.id ";
        if (empty($contactid))
        {
            $sql .= "AND (surname LIKE '%$search_string%' OR forenames LIKE '%$search_string%' OR s.name LIKE '%$search_string%' ";
            $sql .= "OR CONCAT_WS(' ', forenames, surname) = '$search_string' )";
        }
        else $sql .= "AND c.id = '$contactid' ";
        $sql .= "ORDER by c.surname, c.forenames ";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

        if (mysql_num_rows($result)>0)
        {
            $html = "<h3>{$strCustomers}</h3>\n";
            $html .= "<p align='center'>{$strThisListShowsCustomers}</p>";
            $html .= "<table align='center'>";
            $html .= "<tr>";
            $html .= "<th>&nbsp;</th>";
            $html .= "<th>{$strName}</th>";
            $html .= "<th>{$strSite}</th>";
            $html .= "</tr>\n";

            $customermatches = 0;
            while ($contactrow = mysql_fetch_array($result))
            {
                $html .= "<tr class='shade2'>";
                $site_incident_pool = db_read_column('freesupport', $dbSites, $contactrow['siteid']);
                if ($site_incident_pool > 0)
                {
                    $html .= "<td><a href=\"{$_SERVER['PHP_SELF']}?action=incidentform&amp;type=free&amp;contactid=".$contactrow['contactid']."&amp;updateid=$updateid&amp;win={$win}\" onclick=\"return confirm_free();\">";
                    $html .= "{$strAddSiteSupportIncident}</a> ({$site_incident_pool})</td>";
                    $customermatches++;
                }
                else
                {
                    $html .= "<td class='expired'>{$strZeroRemaining}</td>";
                }
                $html .= '<td>'.$contactrow['forenames'].' '.$contactrow['surname'].'</td>';
                $html .= '<td>'.site_name($contactrow['siteid']).'</td>';
                $html .= "</tr>\n";
            }
            $html .= "</table>\n";

            if ($customermatches > 0)
            {
                echo $html;
            }

            echo "<p align='center'><a href='contact_add.php?name=".urlencode($search_string)."&amp;return=addincident'>{$strAddContact}</a></p>\n";
        }
        else
        {
            echo "<h3>".sprintf($strNoResultsFor, $strContacts)."</h3>";
            echo "<p align='center'><a href=\"contact_add.php?name=".urlencode($search_string)."&amp;return=addincident\">{$strAddContact}</a></p>\n";
        }

        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    }
}
elseif ($action=='incidentform')
{
    // Display form to get details of the actual incident
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    echo "<h2>".icon('add', 32)." {$strAddIncident} - {$strDetails}</h2>";
    ?>
    <script type="text/javascript">
    function validateForm(form)
    {
        if (form.incidenttitle.value == '')
        {
            alert(strYouMustEnterIncidentTitle);
            form.incidenttitle.focus( );
            return false;
        }
    }
    </script>
    <?php
    echo "<form action='{$_SERVER['PHP_SELF']}?action=assign'";
    echo " method='post' id='supportdetails' name='supportdetails' onsubmit=\"return validateForm(this)\">";
    echo "<input type='hidden' name='type' value=\"{$type}\" />";
    echo "<input type='hidden' name='contactid' value=\"{$contactid}\" />";
    echo "<input type='hidden' name='productid' value=\"{$productid}\" />";
    echo "<input type='hidden' name='maintid' value=\"{$maintid}\" />";
    echo "<input type='hidden' name='siteid' value=\"{$siteid}\" />";

    if (!empty($updateid))
    {
        echo "<input type='hidden' name='updateid' value='$updateid' />";
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

    if (empty($updateid))
    {
        echo "<tr><td><label for='incidenttitle'>{$strIncidentTitle}</label><br />";
        echo "<input class='required' maxlength='200' id='incidenttitle' ";
        echo "name='incidenttitle' size='50' type='text' />";
        echo " <span class='required'>{$strRequired}</span></td>\n";
        echo "<td>";
        if ($type == 'free')
        {
            echo "<label>{$strServiceLevel}".serviceleveltag_drop_down('servicelevel',$CONFIG['default_service_level'], TRUE)."</label><br />";
            echo "<label>{$strSkill}: ".skill_drop_down('software', 0)."</label>";
        }
        else
        {
            echo "<label for='software'>{$strSkill}</label><br />".softwareproduct_drop_down('software', 1, $productid);
        }
        echo " <label>{$strVersion}: <input maxlength='50' name='productversion' size='8' type='text' /></label> \n";
        echo " <label>{$strServicePacksApplied}: <input maxlength='100' name='productservicepacks' size='8' type='text' /></label>\n";
        echo "</td></tr>";

        // Inventory
        $items_array[0] = '';
        $sql = "SELECT * FROM `{$dbInventory}` ";
        $sql .= "WHERE contactid='{$contactid}' ";
        $result = mysql_query($sql);
        $contact_inv_count = mysql_num_rows($result);
        while ($items = mysql_fetch_object($result))
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
            echo array_drop_down($items_array, 'inventory', '', '', TRUE)."</td><td></td></tr>";
        }

        // Insert pre-defined per-product questions from the database, these should be required fields
        // These 'productinfo' questions don't have a GUI as of 27Oct05
        $sql = "SELECT * FROM `{$dbProductInfo}` WHERE productid='{$productid}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        $numquestions = mysql_num_rows($result);
        $cellcount = 1;
        while ($productinforow = mysql_fetch_array($result))
        {
            if ($cellcount & 1) echo "<tr>";
            echo "<td>";
            echo "<label>{$productinforow['information']}";
            if ($productinforow['moreinformation'] != '')
            {
                echo " (<em>{$productinforow['moreinformation']}</em>)";
            }
            echo "<br />\n";
            echo "<input class='required' maxlength='50' ";
            echo "name='pinfo{$productinforow['id']}' size='70' type='text' />";
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
        echo "<textarea id='probdesc' name='probdesc' rows='2' cols='60' onfocus='new ResizeableTextarea(this);'></textarea></td>\n";
        echo "<td><label for='probreproduction'>{$strProblemReproduction}</label>".help_link('ProblemReproductionEngineer')."<br />";
        echo "<textarea id='probreproduction' name='probreproduction' rows='2' cols='60' onfocus='new ResizeableTextarea(this);'></textarea></td></tr>\n";
        echo "<tr><td><label for='workarounds'>{$strWorkAroundsAttempted}</label>".help_link('WorkAroundsAttemptedEngineer')."<br />";
        echo "<textarea id='workarounds' name='workarounds' rows='2' cols='60' onfocus='new ResizeableTextarea(this);'></textarea></td>\n";
        echo "<td><label for ='custimpact'>{$strCustomerImpact}</label>".help_link('CustomerImpactEngineer')."<br />";
        echo "<textarea id='custimpact' name='custimpact' rows='2' cols='60' onfocus='new ResizeableTextarea(this);'></textarea></td></tr>\n";
    }
    else
    {
        $sql = "SELECT bodytext FROM `{$dbUpdates}` WHERE id={$updateid}";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $updaterow = mysql_fetch_array($result);
        $mailed_body_text = $updaterow['bodytext'];

        $sql="SELECT subject FROM `{$dbTempIncoming}` WHERE updateid={$updateid}";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        $updaterow = mysql_fetch_array($result);
        $mailed_subject = $updaterow['subject'];

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
    echo "<label><input name='cust_vis' type='checkbox' checked='checked' /> {$strVisibleToCustomer}</label>";
    echo help_link('VisibleToCustomer')."<br />";
    echo "<label><input name='send_email' type='checkbox' checked='checked' /> ";
    echo "{$strSendOpeningEmailDesc}</label><br />";
    echo "<strong>{$strPriority}</strong><br />".priority_drop_down("priority", 1, 4, FALSE)." </td></tr>";
    echo "</table>\n";
    echo "<input type='hidden' name='win' value='{$win}' />";
    echo "<p align='center'><input name='submit' type='submit' value='{$strAddIncident}' /></p>";
    echo "</form>\n";

    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == 'assign')
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    if ($type == "support" OR $type == "free")
    {
        $html .= "<h2>{$strAddIncident} - {$strAssign}</h2>";

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
        $priority = cleanvar($_REQUEST['priority']);
        $software = cleanvar($_REQUEST['software']);
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
            $errors = 1;
            $error_string .= $strYouMustSelectAcontact;
        }

        // check for blank title
        if ($incidenttitle == '')
        {
            $incidenttitle = $strUntitled;
        }

        // check for blank priority
        if ($priority == 0)
        {
            $priority = 1;
        }

        // check for blank type
        if ($type == '')
        {
            $errors = 1;
            $error_string .= $strIncidentTypeWasBlank;
        }

        if ($type == 'free' AND $servicelevel == '' )
        {
            $errors++;
            $error_string .= $strYouMustSelectAserviceLevel;
        }

        if ($errors == 0)
        {
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
                $servicelevel = servicelevel_id2tag(maintenance_servicelevel($maintid));
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
                $result = mysql_query($sql);
                if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                $obj = mysql_fetch_object($result);
                if ($obj->contact_source == 'ldap')
                {
                	//function authenticateLDAP($username, $password, $id = 0, $user=TRUE, $populateOnly=FALSE, $searchOnEmail=FALSE)
                    authenticateLDAP($obj->username, '', $contactid, false, true, false);
                }
            }

            // Check the service level priorities, look for the highest possible and reduce the chosen priority if needed
            $sql = "SELECT priority FROM `{$dbServiceLevels}` WHERE tag='{$servicelevel}' ORDER BY priority DESC LIMIT 1";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            list($highestpriority) = mysql_fetch_row($result);
            if ($priority > $highestpriority)
            {
                $prioritychangedmessage = " (".sprintf($strReducedPrioritySLA, priority_name($priority)).")";
                $priority = $highestpriority;
            }

            $sql  = "INSERT INTO `{$dbIncidents}` (title, owner, contact, priority, servicelevel, status, type, maintenanceid, ";
            $sql .= "product, softwareid, productversion, productservicepacks, opened, lastupdated, timeofnextaction) ";
            $sql .= "VALUES ('{$incidenttitle}', '{$sit[2]}', '{$contactid}', '{$priority}', '{$servicelevel}', '1}', 'Support}', '{$maintid}', ";
            $sql .= "'{$productid}', '{$software}', '{$productversion}', '{$productservicepacks}', '{$now}', '{$now}', '{$timeofnextaction}')";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            $incidentid = mysql_insert_id();
            $_SESSION['incidentid'] = $incidentid;

            // Save productinfo if there is some
            $sql = "SELECT * FROM `{$dbProductInfo}` WHERE productid='{$productid}'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($result) > 0)
            {
                while ($productinforow = mysql_fetch_object($result))
                {
                    $var = "pinfo{$productinforow->id}";
                    $pinfo = cleanvar($_POST[$var]);
                    $pisql = "INSERT INTO `{$dbIncidentProductInfo}` (incidentid, productinfoid, information) ";
                    $pisql .= "VALUES ('{$incidentid}', '{$productinforow->id}', '{$pinfo}')";
                    mysql_query($pisql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
                }
            }

            $updatetext = "{$_SESSION['syslang']['strPriority']}: [b]" . priority_name($priority, TRUE) . "[/b]";
            if (!empty($prioritychangedmessage)) $updatetext .= $prioritychangedmessage;
            $updatetext .= "\n\n" . $bodytext;
            if ($probdesc != '') $updatetext .= "<b>{$_SESSION['syslang']['strProblemDescription']}</b>\n" . $probdesc . "\n\n";
            if ($workarounds != '') $updatetext .= "<b>{$_SESSION['syslang']['strWorkAroundsAttempted']}</b>\n" . $workarounds . "\n\n";
            if ($probreproduction != '') $updatetext .= "<b>{$_SESSION['syslang']['strProblemReproduction']}</b>\n" . $probreproduction . "\n\n";
            if ($custimpact != '') $updatetext .= "<b>{$_SESSION['syslang']['strCustomerImpact']}</b>\n" . $custimpact . "\n\n";
            if ($other != '') $updatetext .= "<b>{$_SESSION['syslang']['strDetails']}</b>\n" . $other . "\n";
            if ($cust_vis == "on") $customervisibility = 'show';
            else $customervisibility = 'hide';

            if (!empty($updateid))
            {
                // Assign existing update to new incident if we have one
                $sql = "UPDATE `{$dbUpdates}` SET incidentid='{$incidentid}', userid='{$sit[2]}' WHERE id='{$updateid}'";

                $result = mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

                $old_path = $CONFIG['attachment_fspath']. 'updates' . $fsdelim;
                $new_path = $CONFIG['attachment_fspath'] . $incidentid . $fsdelim;

                //move attachments from updates to incident
                $sql = "SELECT linkcolref, filename FROM `{$dbLinks}` AS l, ";
                $sql .= "`{$dbFiles}` as f ";
                $sql .= "WHERE l.origcolref = '{$updateid}' ";
                $sql .= "AND l.linktype = 5 ";
                $sql .= "AND l.linkcolref = f.id";
                $result = mysql_query($sql);
                if ($result)
                {
                    if (!file_exists($new_path))
                    {
                        $umask = umask(0000);
                        @mkdir($new_path, 0770);
                        umask($umask);
                    }

                    while ($row = mysql_fetch_object($result))
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
                $sql = "DELETE FROM `{$dbTempIncoming}` WHERE updateid='$updateid'";
                mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            }
            else
            {
                // Create a new update from details entered
                $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, bodytext, timestamp, currentowner, ";
                $sql .= "currentstatus, customervisibility, nextaction) ";
                $sql .= "VALUES ('{$incidentid}', '{$sit[2]}', 'opening', '{$updatetext}', '{$now}', '{$sit[2]}', ";
                $sql .= "'1', '{$customervisibility}', '{$nextaction}')";
                $result = mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            }

            // get the service level
            // find out when the initial response should be according to the service level
            if (empty($servicelevel) OR $servicelevel==0)
            {
                // FIXME: for now we use id but in future use tag, once maintenance uses tag
                $servicelevel = maintenance_servicelevel($maintid);
                $sql = "SELECT * FROM `{$dbServiceLevels}` WHERE id='$servicelevel' AND priority='$priority' ";
            }
            else
            {
                $sql = "SELECT * FROM `{$dbServiceLevels}` WHERE tag='$servicelevel' AND priority='$priority' ";
            }

            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            $level = mysql_fetch_object($result);

            $targetval = $level->initial_response_mins * 60;
            $initialresponse = $now + $targetval;

            // Insert the first SLA update, this indicates the start of an incident
            // This insert could possibly be merged with another of the 'updates' records, but for now we keep it seperate for clarity
            $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, sla, bodytext) ";
            $sql .= "VALUES ('{$incidentid}', '{$sit[2]}', 'slamet', '{$now}', '{$sit[2]}', '1', 'show', 'opened','{$_SESSION['syslang']['strIncidentIsOpen']}.')";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            // Insert the first Review update, this indicates the review period of an incident has started
            // This insert could possibly be merged with another of the 'updates' records, but for now we keep it seperate for clarity
            $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, customervisibility, sla, bodytext) ";
            $sql .= "VALUES ('{$incidentid}', '{$sit[2]}', 'reviewmet', '{$now}', '{$sit[2]}', '1', 'hide', 'opened','')";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            if (!empty($inventory) AND $inventory != 0)
            {
                $sql = "INSERT INTO `{$dbLinks}`(linktype, origcolref, linkcolref, direction, userid) ";
                $sql .= "VALUES(7, '{$incidentid}', '{$inventory}', 'left', '{$sit[2]}')";
                mysql_query($sql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
            }

            plugin_do('incident_created');

            // Decrement free support, where appropriate
            if ($type == 'free')
            {
                decrement_free_incidents(contact_siteid($contactid));
                plugin_do('incident_created_site');
            }
            else
            {
                // decrement contract incident by incrementing the number of incidents used
                increment_incidents_used($maintid);
                plugin_do('incident_created_contract');
            }

            $html .= "<h3>{$strIncident}: $incidentid</h3>";
            $html .=  "<p align='center'>";
            $html .= sprintf($strIncidentLoggedEngineer, $incidentid);
            $html .= "</p>\n";

            $suggested_user = suggest_reassign_userid($incidentid);
            $trigger = new TriggerEvent('TRIGGER_INCIDENT_CREATED', array('incidentid' => $incidentid, 'sendemail' => $send_email));

            if ($CONFIG['auto_assign_incidents'])
            {
                html_redirect("incident_add.php?action=reassign&userid={$suggested_user}&incidentid={$incidentid}");
                exit;
            }
            else
            {
                echo $html;
            }

            // List Engineers
            // We need a user type 'engineer' so we don't just list everybody
            // Status zero means account disabled
            $sql = "SELECT * FROM `{$dbUsers}` WHERE status!=0 ORDER BY realname";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            echo "<h3>{$strUsers}</h3>
            <table align='center'>
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
            echo "<th align='center'>".priority_icon(4)."</th>";
            echo "<th align='center'>".priority_icon(3)."</th>";
            echo "<th align='center'>".priority_icon(2)."</th>";
            echo "<th align='center'>".priority_icon(1)."</th>";

            echo "<th></th>";
            echo "</tr>";

            $shade = 'shade2';
            while ($userrow = mysql_fetch_array($result))
            {
                if ($userrow['id'] == $suggested_user) $shade = 'idle';
                echo "<tr class='$shade'>";
                // display reassign link only if person is accepting or if the current user has 'reassign when not accepting' permission
                if ($userrow['accepting'] == 'Yes')
                {
                    echo "<td align='right'><a href=\"{$_SERVER['PHP_SELF']}?action=reassign&amp;userid={$userrow['id']}&amp;incidentid={$incidentid}&amp;nextaction=".urlencode($nextaction)."&amp;win={$win}\" ";
                    // if ($priority >= 3) echo " onclick=\"alertform.submit();\"";
                    echo ">{$strAssignTo}</a></td>";
                }
                elseif (user_permission($sit[2],40) OR $userrow['id'] == $sit[2])
                {
                    echo "<td align='right'><a href=\"{$_SERVER['PHP_SELF']}?action=reassign&amp;userid={$userrow['id']}&amp;incidentid={$incidentid}&amp;nextaction=".urlencode($nextaction)."&amp;win={$win}\" ";
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
                $ssql .= "WHERE userid='{$userrow['id']}' AND softwareid='{$software}' ";
                $sresult = mysql_query($ssql);
                if (mysql_num_rows($sresult) >= 1)
                {
                    echo "<strong>{$userrow['realname']}</strong>";
                }
                else
                {
                    echo $userrow['realname'];
                }

                echo "</td>";
                echo "<td>".$userrow['phone']."</td>";
                echo "<td>".user_online_icon($userrow['id'])." ".userstatus_name($userrow['status'])."</td>";
                echo "<td>".$userrow['message']."</td>";
                echo "<td align='center'>";

                $incpriority = user_incidents($userrow['id']);
                $countincidents = ($incpriority['1']+$incpriority['2']+$incpriority['3']+$incpriority['4']);

                if ($countincidents >= 1) $countactive = user_activeincidents($userrow['id']);
                else $countactive = 0;

                $countdiff = $countincidents-$countactive;

                echo "$countactive / {$countdiff}</td>";
                echo "<td align='center'>".$incpriority['4']."</td>";
                echo "<td align='center'>".$incpriority['3']."</td>";
                echo "<td align='center'>".$incpriority['2']."</td>";
                echo "<td align='center'>".$incpriority['1']."</td>";

                echo "<td align='center'>";
                echo $userrow['accepting'] == 'Yes' ? $strYes : "<span class='error'>{$strNo}</span>";
                echo "</td>";
                echo "</tr>\n";
                if ($shade == 'shade2') $shade = 'shade1';
                else $shade = 'shade2';
            }
            echo "</table>";
            echo "<p align='center'>{$strUsersBoldSkills}.</p>";
        }
        else
        {
            trigger_error('User input error: '. $error_string, E_USER_ERROR);
        }
    }
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
elseif ($action == 'reassign')
{
    // External variables
    $incidentid = cleanvar($_REQUEST['incidentid']);
    $uid = cleanvar($_REQUEST['userid']);
    $nextaction = cleanvar($_REQUST['nextaction']);

    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    echo "<h2>{$strIncidentAdded} - {$strSummary}</h2>";
    echo "<p align='center'>";
    $incidentnum = "<a href=\"javascript:incident_details_window('$incidentid','incident{$incidentid}');\">{$strIncident} {$incidentid}</a>";
    $queuename = "<strong style='color: red'>{$strActionNeeded}</strong>";
    $name = user_realname($uid);
    printf($strHasBeenAutoMovedToX, $incidentnum, $name, $queuename);
    echo help_link('AutoAssignIncidents')."</p><br /><br />";
    $userphone = user_phone($userid);
    if ($userphone!='') echo "<p align='center'>{$strTelephone}: {$userphone}</p>";
    $sql = "UPDATE `{$dbIncidents}` SET owner='$uid', lastupdated='$now' WHERE id='$incidentid'";
    mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

    $t = new TriggerEvent('TRIGGER_INCIDENT_ASSIGNED', array('userid' => $uid, 'incidentid' => $incidentid));

    // add update
    $sql  = "INSERT INTO `{$dbUpdates}` (incidentid, userid, type, timestamp, currentowner, currentstatus, nextaction) ";
    $sql .= "VALUES ('{$incidentid}', '{$sit[2]}', 'reassigning', '{$now}', '{$uid}', '1', '{$nextaction}')";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>
