<?php
// incident_types.php - Page to list/add/edit incident types
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2016 The Support Incident Tracker Project
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Paul Heaney <paul[at]sitracker.org>
//

require ('core.php');

$permission = PERM_SITE_TYPES;

require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');
require (APPLICATION_LIBPATH . 'sitform.inc.php');

$title = $strLicenseTypes;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$mode = clean_fixed_list($_REQUEST['mode'], array('','new','edit'));

if (empty($mode))
{
    echo "<h2>".icon('edit', 32)." {$strLicenseTypes}</h2>";
    plugin_do('licence_types');

    $sql = "SELECT * FROM `{$dbLicenceTypes}` ORDER BY name";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    if (mysqli_num_rows($result) > 0)
    {
        echo "<table class='maintable'>";
        echo "<tr><th>{$strLicenseType}</th><th>{$strActions}</th></tr>";
        $shade = 'shade1';
        while ($obj = mysqli_fetch_object($result))
        {
            echo "<tr class='{$shade}'><td>{$obj->name}</td>";
            echo "<td><a href='{$_SERVER['PHP_SELF']}?mode=edit&amp;id={$obj->id}'>{$strEdit}</a></td></tr>";

            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        echo "</table>";
    }
    else
    {
        user_alert($strNoRecords, E_USER_NOTICE);
    }
    echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?mode=new'>{$strNewLicenseType}</a></p>";
}
elseif ($mode == 'new')
{
    $form = new Form("licencetypes", $strNew, $dbLicenceTypes, "insert", $strLicenseType);
    $form->setReturnURLFailure($_SERVER['PHP_SELF']);
    $form->setReturnURLSuccess($_SERVER['PHP_SELF']);
    $c1 = new Cell();
    $c1->setIsHeader(TRUE);
    $label = new Label($strLicenseType);
    $c1->addComponent($label);
    $c2 = new Cell();
    $sle = new SingleLineEntry("name", 30, "name", "", true);
    $sle->setLabel($label);
    $c2->addComponent($sle);

    $r = new Row();
    $r->addComponent($c1);
    $r->addComponent($c2);
    $form->addRow($r);
    $hr = new HiddenRow();
    $hr->addComponent(new HiddenEntry("mode", "", "new"));
    $form->addRow($hr);

    $form->run();
}
elseif ($mode == 'edit')
{
    $id = clean_int($_REQUEST['id']);
    $sql = "SELECT name FROM `{$dbLicenceTypes}` WHERE id = {$id}";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    if (mysqli_num_rows($result) > 0)
    {
        list($name) = mysqli_fetch_row($result);
    }
    $form = new Form("licencetypes", $strSave, $dbLicenceTypes, "update", $strEditLicenseType);
    $form->setReturnURLFailure($_SERVER['PHP_SELF']);
    $form->setReturnURLSuccess($_SERVER['PHP_SELF']);
    $c1 = new Cell();
    $c1->setIsHeader(TRUE);
    $label = new Label($strLicenseType);
    $c1->addComponent($label);
    $c2 = new Cell();
    $sle = new SingleLineEntry("name", 30, "name", $name, true);
    $sle->setLabel($label);
    $c2->addComponent($sle);

    $r = new Row();
    $r->addComponent($c1);
    $r->addComponent($c2);
    $form->addRow($r);
    $hr = new HiddenRow();
    $hr->addComponent(new HiddenEntry("mode", "", "edit"));
    $hr->addComponent(new HiddenEntry("id", "", $id));
    $form->addRow($hr);
    $form->setKey("id", $id);

    $form->run();
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>