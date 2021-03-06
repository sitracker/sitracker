<?php
// site_types.php - Page to list/add/edit site types
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
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

$title = $strSiteTypes;

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$mode = clean_fixed_list($_REQUEST['mode'], array('','new','edit'));

if (empty($mode))
{
    echo "<h2>".icon('edit', 32)." {$strSiteTypes}</h2>";
    plugin_do('site_types');

    $sql = "SELECT * FROM `{$dbSiteTypes}` ORDER BY typename";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    if (mysqli_num_rows($result) > 0)
    {
        echo "<table class='maintable'>";
        echo "<tr><th>{$strSiteType}</th><th>{$strActions}</th></tr>";
        $shade = 'shade1';
        while ($obj = mysqli_fetch_object($result))
        {
            echo "<tr class='{$shade}'><td>{$obj->typename}</td>";
            echo "<td><a href='{$_SERVER['PHP_SELF']}?mode=edit&amp;typeid={$obj->typeid}'>{$strEdit}</a></td></tr>";

            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        echo "</table>";
    }
    else
    {
        user_alert($strNoRecords, E_USER_NOTICE);
    }
    echo "<p align='center'><a href='{$_SERVER['PHP_SELF']}?mode=new'>{$strNewSiteType}</a></p>";
}
elseif ($mode == 'new')
{
    $form = new Form("sitetypes", $strNew, $dbSiteTypes, "insert", $strNewSiteType);
    $form->setReturnURLFailure($_SERVER['PHP_SELF']);
    $form->setReturnURLSuccess($_SERVER['PHP_SELF']);
    $c1 = new Cell();
    $c1->setIsHeader(TRUE);
    $label = new Label($strSiteType);
    $c1->addComponent($label);
    $c2 = new Cell();
    $sle = new SingleLineEntry("typename", 30, "typename", "", true);
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
    $typeid = clean_int($_REQUEST['typeid']);
    $sql = "SELECT typename FROM `{$dbSiteTypes}` WHERE typeid = {$typeid}";
    $result = mysqli_query($db, $sql);
    if (mysqli_error($db)) trigger_error(mysqli_error($db), E_USER_WARNING);
    if (mysqli_num_rows($result) > 0)
    {
        list($typename) = mysqli_fetch_row($result);
    }
    $form = new Form("sitetypes", $strSave, $dbSiteTypes, "update", $strEditSiteType);
    $form->setReturnURLFailure($_SERVER['PHP_SELF']);
    $form->setReturnURLSuccess($_SERVER['PHP_SELF']);
    $c1 = new Cell();
    $c1->setIsHeader(TRUE);
    $label = new Label($strSiteType);
    $c1->addComponent($label);
    $c2 = new Cell();
    $sle = new SingleLineEntry("typename", 30, "typename", $typename, true);
    $sle->setLabel($label);
    $c2->addComponent($sle);

    $r = new Row();
    $r->addComponent($c1);
    $r->addComponent($c2);
    $form->addRow($r);
    $hr = new HiddenRow();
    $hr->addComponent(new HiddenEntry("mode", "", "edit"));
    $hr->addComponent(new HiddenEntry("typeid", "", $typeid));
    $form->addRow($hr);
    $form->setKey("typeid", $typeid);

    $form->run();
}

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');

?>