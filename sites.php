<?php
// browse_sites.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

$permission = 11; // View Sites
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$pagescripts = array('AutoComplete.js');

$title = $strBrowseSites;

$search_string = cleanvar($_REQUEST['search_string']);
$owner = clean_int($_REQUEST['owner']);
$submit_value = cleanvar($_REQUEST['submit']);
$displayinactive = cleanvar($_REQUEST['displayinactive']);
if (empty($displayinactive) OR $_SESSION['userconfig']['show_inactive_data'] != 'TRUE')
{
    $displayinactive = "false";
}

if ($submit_value == "go")
{
    // build SQL
    $sql  = "SELECT id, name, department FROM `{$dbSites}` ";
    if (!empty($owner))
    {
        $sql .= "WHERE owner = '{$owner}' ";
    }
    elseif ($search_string != '*')
    {
        $sql .= "WHERE ";
        if (mb_strlen(utf8_decode($search_string)) == 1)
        {
            if ($search_string == '0')
            {
                $sql .= "(SUBSTRING(name,1,1)=('0')
                                OR SUBSTRING(name,1,1)=('1')
                                OR SUBSTRING(name,1,1)=('2')
                                OR SUBSTRING(name,1,1)=('3')
                                OR SUBSTRING(name,1,1)=('4')
                                OR SUBSTRING(name,1,1)=('5')
                                OR SUBSTRING(name,1,1)=('6')
                                OR SUBSTRING(name,1,1)=('7')
                                OR SUBSTRING(name,1,1)=('8')
                                OR SUBSTRING(name,1,1)=('9'))";
            }
            else
            {
                // $sql .= "SUBSTRING(name,1,1)=('$search_string') ";
                $sql .= "name LIKE '{$search_string}%' ";
            }
        }
        else
        {
            $sql .= "name LIKE '%$search_string%' ";
        }
    }
    if (!$displayinactive) $sql .= "AND active = 'true'";
    $sql .= " ORDER BY name ASC";

    // execute query
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    if (mysql_num_rows($result) == 1)
    {
            //go straight to the site
            $obj = mysql_fetch_object($result);
            $url = "site_details.php?id={$obj->id}";
            header("Location: {$url}");
    }
}

include (APPLICATION_INCPATH . 'htmlheader.inc.php');
if ($search_string == '') $search_string='a';
echo "<h2>".icon('site', 32)." ";
echo "{$title}</h2>";

echo "<table summary='alphamenu' align='center'>";
echo "<tr>";
echo "<td align='center'>";
echo "<form action='{$_SERVER['PHP_SELF']}' method='get'>";

echo "<p>{$strBrowseSites}: ";
echo "<input type='text' id='search_string' style='width: 300px;' name='search_string' />";
echo autocomplete('search_string', 'sites');
echo "<input name='submit' type='submit' value='{$strGo}' /></p>";
echo "</form>\n";
if ($_SESSION['userconfig']['show_inactive_data'] == 'TRUE')
{
    if ($displayinactive == "true")
    {
        echo "<a href='".$_SERVER['PHP_SELF']."?displayinactive=false";
        if (!empty($search_string)) echo "&amp;search_string={$search_string}&amp;owner={$owner}";
        echo "'>{$strShowActiveOnly}</a>";
        $inactivestring="displayinactive=true";
    }
    else
    {
        echo "<a href='".$_SERVER['PHP_SELF']."?displayinactive=true";
        if (!empty($search_string)) echo "&amp;search_string={$search_string}&amp;owner={$owner}";
        echo "'>{$strShowAll}</a>";
        $inactivestring="displayinactive=false";
    }
}
echo "</td></tr>";

echo "<tr><td class='alphamenu'>";
echo "<a href='site_new.php'>{$strNewSite}</a>";
echo alpha_index("{$_SERVER['PHP_SELF']}?search_string=", $displayinactive);
if (!empty($i18nAlphbet))
{
    echo "<a href='{$_SERVER['PHP_SELF']}?search_string=*&amp;{$inactivestring}'>{$strAll}</a>\n";
}
$sitesql = "SELECT COUNT(id) FROM `{$dbSites}` WHERE owner='{$sit[2]}'";
$siteresult = mysql_query($sitesql);
if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
list($ownedsites) = mysql_fetch_row($siteresult);
if ($ownedsites > 0) echo " | <a href='sites.php?owner={$sit[2]}' title='Sites'>{$strMine}</a> ";

echo "</td></tr></table>";

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
        $sql  = "SELECT id, name, department, active FROM `{$dbSites}` ";

        if (!empty($owner))
        {
            $sql .= "WHERE owner = '{$owner}' ";
        }
        elseif ($search_string != '*')
        {
            $sql .= "WHERE ";
            if (mb_strlen(utf8_decode($search_string)) == 1)
            {
                if ($search_string == '0')
                {
                    $sql .= "(SUBSTRING(name,1,1)=('0')
                                    OR SUBSTRING(name,1,1)=('1')
                                    OR SUBSTRING(name,1,1)=('2')
                                    OR SUBSTRING(name,1,1)=('3')
                                    OR SUBSTRING(name,1,1)=('4')
                                    OR SUBSTRING(name,1,1)=('5')
                                    OR SUBSTRING(name,1,1)=('6')
                                    OR SUBSTRING(name,1,1)=('7')
                                    OR SUBSTRING(name,1,1)=('8')
                                    OR SUBSTRING(name,1,1)=('9'))";
                }
                else
                {
                    //$sql .= "SUBSTRING(name,1,1)=('$search_string') ";
                    $sql .= "name LIKE '{$search_string}%' ";
                }
            }
            else
            {
                $sql .= "name LIKE '%{$search_string}%' ";
            }
        }
        if ($displayinactive == "false")
        {
            if ($search_string == '*') $sql .= " WHERE ";
            else $sql .= " AND ";
            $sql .= " active = 'true'";
        }
        $sql .= " ORDER BY name ASC";

        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    }

    if (mysql_num_rows($result) == 0)
    {
        echo "<p align='center'>{$strSorryNoSearchResults}</p>";
    }
    else
    {
        $countsites = mysql_num_rows($result);

        echo "<p align='center'>{$strDisplaying} {$countsites} ";

        if ($countsites == 1)
        {
            echo "{$strSite}";
        }
        else
        {
            echo "{$strSites}";
        }
        if ($owner > 0)
        {
            echo " {$strOwnedBy} <strong>".user_realname($owner)."</strong>";

        }
        echo "</p>";
        echo "<table align='center'>";
        echo "<tr>";
        echo "<th>{$strID}</th>";
        echo "<th>{$strSiteName}</th>";
        echo "<th>{$strDepartment}</th>";
        echo "<th>{$strActions}</th>";
        echo "</tr>";
        $shade = 'shade1';
        while ($results = mysql_fetch_object($result))
        {
            if ($results->active == 'false') $shade = 'expired';
            echo "<tr class='{$shade}'>";
            echo "<td align='center'>{$results->id}</td>";
            echo "<td><a href='site_details.php?id={$results->id}&amp;action=show'>{$results->name}</a></td>";
            echo "<td>".nl2br($results->department)."</td>";
            echo "<td><a href='site_edit.php?action=edit&amp;site={$results->id}'>{$strEdit}</a></td>";
            echo "</tr>";

            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        echo "</table>\n";
    }
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>