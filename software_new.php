<?php
// software_new.php - Form for adding software
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


$permission = 56; // Add Skills

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strNewSkill;

// External variables
$submit = $_REQUEST['submit'];

if (empty($submit))
{
    // Show add product form
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');

    $_SESSION['formerrors']['new_software'] = NULL;
    echo "<h2>".icon('skill', 32)." ";
    echo "{$strNewSkill}</h2>";
    echo "<form name='addsoftware' action='{$_SERVER['PHP_SELF']}' method='post' onsubmit='return confirm_action(\"{$strAreYouSureAdd}\");'>";
    echo "<table class='vertical'>";
    echo "<tr><th>{$strVendor}</th><td>";
    if ($_SESSION['formdata']['new_software']['vendor'] != '')
    {
        echo vendor_drop_down('vendor',$_SESSION['formdata']['new_software']['vendor'])."</td></tr>\n";
    }
    else
    {
        echo vendor_drop_down('vendor',$software->vendorid)."</td></tr>\n";
    }
    echo "<tr><th>{$strSkill}</th><td><input maxlength='50' name='name' size='30' class='required' /> <span class='required'>{$strRequired}</span></td></tr>\n";
    echo "<tr><th>{$strLifetime}</th><td>";
    echo "{$strFrom} <input type='text' name='lifetime_start' id='lifetime_start' size='10' ";
    if ($_SESSION['formdata']['new_software']['lifetime_start'] != '')
    {
        echo "value='{$_SESSION['formdata']['new_software']['lifetime_start']}'";
    }
    echo " /> ";
    echo date_picker('addsoftware.lifetime_start');
    echo " {$strTo} ";
    echo "<input type='text' name='lifetime_end' id='lifetime_end' size='10'";
    if ($_SESSION['formdata']['new_software']['lifetime_end'] != '')
    {
        echo "value='{$_SESSION['formdata']['new_software']['lifetime_end']}'";
    }
    echo "/> ";
    echo date_picker('addsoftware.lifetime_end');
    echo "</td></tr>\n";
    echo "<tr><th>{$strTags}</th>";
    echo "<td><textarea rows='2' cols='30' name='tags'></textarea></td></tr>\n";
    echo "</table>";
    echo "<p align='center'><input name='submit' type='submit' value='{$strNewSkill}' /></p>";
    echo "<p class='warning'>{$strAvoidDupes}</p>";
    echo "</form>\n";
    echo "<p align='center'><a href='products.php'>{$strReturnWithoutSaving}</a></p>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

    $_SESSION['formdata']['new_software'] = NULL;
}
else
{
    $name = cleanvar($_REQUEST['name']);
    $tags = cleanvar($_REQUEST['tags']);
    $vendor = clean_int($_REQUEST['vendor']);
    if (!empty($_REQUEST['lifetime_start']))
    {
        $lifetime_start = date('Y-m-d',strtotime($_REQUEST['lifetime_start']));
    }
    else
    {
        $lifetime_start = '';
    }

    if (!empty($_REQUEST['lifetime_end']))
    {
        $lifetime_end = date('Y-m-d',strtotime($_REQUEST['lifetime_end']));
    }
    else
    {
        $lifetime_end = '';
    }

    $_SESSION['formdata']['new_software'] = cleanvar($_REQUEST, TRUE, FALSE, FALSE);

    $errors = 0;

    if ($name == '')
    {
        $errors++;
        $_SESSION['formerrors']['new_software']['name'] = user_alert(sprintf($strFieldMustNotBeBlank, "'{$strName}'"), E_USER_ERROR);
    }
    // Check this is not a duplicate
    $sql = "SELECT id FROM `{$dbSoftware}` WHERE LCASE(name)=LCASE('{$name}') LIMIT 1";
    $result = mysql_query($sql);
    if (mysql_num_rows($result) >= 1)
    {
        $errors++;
        $_SESSION['formerrors']['new_software']['duplicate'] .= $strARecordAlreadyExistsWithTheSameName;
    }

    // add product if no errors
    if ($errors == 0)
    {
        $sql = "INSERT INTO `{$dbSoftware}` (name, vendorid, lifetime_start, lifetime_end) VALUES ('{$name}','{$vendor}','{$lifetime_start}','{$lifetime_end}')";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

        if (!$result)
        {
            echo "<p class='error'>{$strAdditionFail}</p>";
        }
        else
        {
            $id = mysql_insert_id();
            replace_tags(TAG_SKILL, $id, $tags);

            journal(CFG_LOGGING_DEBUG, 'Skill Added', "Skill {$id} was added", CFG_JOURNAL_DEBUG, $id);
            html_redirect("products.php");
            //clear form data
            $_SESSION['formdata']['new_software'] = NULL;
        }
    }
    else
    {
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        html_redirect($_SERVER['PHP_SELF'], FALSE);
    }
}
?>