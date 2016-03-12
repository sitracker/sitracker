<?php
// report_contracts_by_skill.php - Lists contracts which include a particular skill
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2016 The Support Incident Tracker Project
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Report Type: Maintenance

// Author: Paul Heaney <paul[at]sitracker.org>

require ('core.php');
$permission = PERM_REPORT_RUN; // Run Reports
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strContractsBySkill;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

echo "<h2>".icon('reports', 32)." {$title}</h2>";

$submit = cleanvar($_REQUEST['submit']);

if (empty($submit))
{
    echo "<form name='skillform' id='skillform' action='{$_SERVER['PHP_SELF']}' method='post' >";
    echo "<table class='maintable'>";
    echo "<tr><th>{$strContainsAnySkills}</th></tr>";
    echo "<tr><td>";
    echo skill_multi_select("skills");
    echo "</td></tr>";
    echo "<tr><td><input type='checkbox' name='showexpired' id='showexpired' value='yes' />{$strShowExpiredContracts}</td></tr>";
    
    echo "</table>";
    
    echo "<p class='formbuttons'>";
    echo "<input name='reset' type='reset' value='{$strReset}' />";
    echo "<input name='submit' type='submit' value='{$strSearch}' /></p>";
    
    echo "</form>";
}
else
{
    $skills = clean_int($_REQUEST['skills']);
    $skillsList = implode(", ", $skills);
    $showexpired = clean_fixed_list($_REQUEST['showexpired'], array('', 'yes'));
    
    if (is_array($skills))
    {
        $sql = "SELECT id, name FROM `{$dbSoftware}` WHERE id IN ({$skillsList}) ";
        $result = mysqli_query($db, $sql);
        if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
        
        if (mysqli_num_rows($result) > 0)
        {
            echo "<p align='center'><strong>{$strSkills}:</strong> ";
            while ($obj = mysqli_fetch_object($result))
            {
                echo "{$obj->name}, ";
            }
            echo "</p>";
            
            
            $sql = "SELECT m.id AS mid, s.id AS sid, p.name AS productname, s.name AS sitename, m.expirydate ";
            $sql .= "FROM `{$dbSoftwareProducts}` AS sp,  `{$dbMaintenance}` AS m, `{$dbProducts}` AS p, `{$dbSites}` AS s ";
            $sql .= "WHERE sp.productid = m.product AND m.product = p.id AND sp.productid = p.id AND m.site = s.id ";
            $sql .= "AND sp.softwareid IN ({$skillsList}) ";
            if ($showexpired != 'yes')
            {
                $sql .= "AND m.expirydate > '{$now}'";
            }

            
            $result = mysqli_query($db, $sql);
            if (mysqli_error($db)) trigger_error("MySQL Query Error ".mysqli_error($db), E_USER_WARNING);
            
            if (mysqli_num_rows($result) > 0)
            {
                echo "<table class='maintable'>";
                echo "<tr><th>{$strSite}</th><th>{$strContract}</th><th>{$strExpiryDate}</th></tr>";
                
                while ($obj = mysqli_fetch_object($result))
                {
                    $class = '';
                    if ($obj->expirydate < $now) $class = "class='expired'";
                    echo "<tr {$class}>";
                    echo "<td><a href='site_details.php?id={$obj->sid}'>{$obj->sitename}</a></td><td><a href='contract_details.php?id={$obj->mid}'>{$obj->productname}</a></td>";
                    echo "<td>".ldate($CONFIG['dateformat_date'], $obj->expirydate)."</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            }
            else
            {
                echo user_alert($strNoRecords, E_USER_NOTICE);
            }
        }
        else
        {
            echo user_alert($strNoRecords, E_USER_NOTICE);
        }
    }
    else
    {
        echo user_alert($strNoSkillsDefined, E_USER_NOTICE);
    }
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');