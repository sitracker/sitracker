<?php
// contacts.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_CONTACT_VIEW; // View Contacts
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strBrowseContacts;

// External variables
$search_string = cleanvar($_REQUEST['search_string']);
$submit_value = cleanvar($_REQUEST['submit']);
$displayinactive = cleanvar($_REQUEST['displayinactive']);
if (empty($displayinactive)) $displayinactive = "false";

if ($search_string == '')
{
    if (!empty($i18nAlphabet))
    {
        $search_string = mb_substr($i18nAlphabet, 0 , 1);
    }
    else
    {
        $search_string = '*';
    }
}

if ($submit_value == 'go')
{
    // build SQL
    $sql  = "SELECT * FROM `{$dbContacts}` ";
    $search_string_len = mb_strlen(utf8_decode($search_string));
    if ($search_string != '*')
    {
        $sql .= "WHERE ";
        if ($search_string_len <= 6) $sql .= "id=('{$search_string}') OR ";
        if ($search_string_len <= 2)
        {
            $sql .= "SUBSTRING(surname,1,{$search_string_len})=('$search_string') ";
        }
        else
        {
            $sql .= "surname LIKE '%$search_string%' OR forenames LIKE '%{$search_string}%' OR ";
            $sql .= "CONCAT(forenames,' ',surname) LIKE '%{$search_string}%'";
        }
    }
    $sql .= " ORDER BY surname ASC, forenames ASC";

    // execute query
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) == 1)
    {
        //go straight to the contact
        $obj = mysql_fetch_object($result);
        $url = "contact_details.php?id={$obj->id}";
        header("Location: {$url}");
    }
}
$pagescripts = array('AutoComplete.js');
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('contact', 32)." ";
echo "{$title}</h2>";
plugin_do('contacts');
echo "<table summary='alphamenu' class='maintable'>";
echo "<tr>";
echo "<td align='center'>";
echo "<form action='{$_SERVER['PHP_SELF']}' method='get'>";
echo "<p>{$strBrowseContacts}: ";
echo "<input type='text' id='search_string' style='width: 300px;' name='search_string' />";
echo autocomplete('search_string', 'contact');
echo "<input name='submit' type='submit' value=\"{$strGo}\" /></p>";
echo "</form>\n";
if ($displayinactive == "true")
{
    echo "<a href='".$_SERVER['PHP_SELF']."?displayinactive=false";
    if (!empty($search_string)) echo "&amp;search_string={$search_string}";
    echo "'>{$strShowActiveOnly}</a>";
    $inactivestring="displayinactive=true";
}
else
{
    echo "<a href='".$_SERVER['PHP_SELF']."?displayinactive=true";
    if (!empty($search_string)) echo "&amp;search_string={$search_string}";
    echo "'>{$strShowAll}</a>";
    $inactivestring="displayinactive=false";
}
echo "</td></tr><tr><td class='alphamenu'>";

echo "<a href='contact_new.php'>{$strNew}</a>";
echo alpha_index("{$_SERVER['PHP_SELF']}?search_string=", $inactivestring);
echo "</td></tr></table>";

if (empty($search_string))
{
    echo user_alert(sprintf($strFieldMustNotBeBlank, "'{$strSearch}'"), E_USER_ERROR);
}
else
{
    // perform search
    // check input
    if ($search_string == '')
    {
        $errors = 1;
        echo user_alert(sprintf($strFieldMustNotBeBlank, "'{$strSearch}'"), E_USER_ERROR);
    }
    // search for criteria
    if ($errors == 0)
    {
        if ($submit_value != 'go')
        {
            // Don't  need to do this again, already done above, us the results of that
            // build SQL
            $sql  = "SELECT c.* FROM `{$dbContacts}` AS c, `{$dbSites}` AS s ";
            $sql .= "WHERE c.siteid = s.id ";
            $search_string_len=mb_strlen(utf8_decode($search_string));
            if ($search_string != '*')
            {
                $sql .= " AND (";
                if ($search_string_len <= 6) $sql .= "c.id=('{$search_string}') OR ";

                if ($search_string_len <= 2)
                {
                    // $sql .= "SUBSTRING(c.surname,1,$search_string_len)=('$search_string') ";
                    $sql .= "c.surname LIKE '{$search_string}%' ";
                }
                else
                {
                    $sql .= "c.surname LIKE '%$search_string%' OR c.forenames LIKE '%{$search_string}%' OR ";
                    $sql .= "CONCAT(c.forenames,' ',c.surname) LIKE '%{$search_string}%'";
                }
                $sql .= " ) ";
            }

            if ($displayinactive == "false")
            {
                $sql .= " AND c.active = 'true' AND s.active = 'true'";
            }
            $sql .= " ORDER BY surname ASC";
            $result = mysql_query($sql);
            debug_log($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        }

        if ($search_string == '*')
        {
            $search_term = $strAll;
        }
        else
        {
            $search_term = $search_string;
        }

        if (mysql_num_rows($result) == 0)
        {
            if (empty($search_string)) echo user_alert($strNoRecords, E_USER_NOTICE);
            else user_alert(sprintf($strSorryNoRecordsMatchingX, "<em>{$search_term}</em>", E_USER_NOTICE));
        }
        else
        {

            echo "<p align='center'>".sprintf($strDisplayingXcontactMatchingY, mysql_num_rows($result), "<em>{$search_term}</em>")."</p>";

            echo "<table class='maintable'>
            <tr>
            <th>{$strName}</th>
            <th>{$strSite}</th>
            <th>{$strEmail}</th>
            <th>{$strTelephone}</th>
            <th>{$strFax}</th>
            <th>{$strAction}</th>
            </tr>";
            $shade = 'shade1';
            while ($results = mysql_fetch_object($result))
            {
                if ($results->active == 'false') $shade = 'expired';

                echo "<tr class='{$shade}'>";
                echo "<td><a href='contact_details.php?id={$results->id}'>{$results->surname}, {$results->forenames}</a></td>";
                echo "<td><a href='site_details.php?id={$results->siteid}'>".site_name($results->siteid)."</a></td>";
                echo "<td>{$results->email}</td>";
                echo "<td>";
                if ($results->phone == '')  echo "<em>{$strNone}</em>";
                else echo $results->phone;
                echo "</td>";
                echo "<td>";
                if ($results->fax == '') echo "<em>{$strNone}</em>";
                else echo $results->fax;
                echo "</td>";
                echo "<td>";
                $operations = array();
                $operations[$strNewIncident] = "incident_new.php?action=findcontact&amp;contactid={$results->id}";
                $operations[$strEditContact] = "contact_edit.php?action=edit&amp;contact={$results->id}";
                echo html_action_links($operations);
                echo "</td></tr>";

                // invert shade
                if ($shade == 'shade1') $shade = 'shade2';
                else $shade = 'shade1';
            }
            echo "</table>";
        }
    }
}
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>