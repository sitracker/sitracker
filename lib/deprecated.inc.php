<?php
// deprecated.inc.php - deprecated functions that will be removed in a subsequent release
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


/**
 * @author Kieran Hogg
 * @param string $name. name of the html entity
 * @param string $time. the time to set it to, format 12:34
 * @return string. HTML
 * @TODO perhaps merge with the new time display function?
 * @deprecated Replaced by time_picker, scheduled for removal in 4.0
 */
function time_dropdown($name, $time='')
{
    if ($time)
    {
        $time = explode(':', $time);
    }

    $html = "<select name='$name'>\n";
    $html .= "<option></option>";
    for ($hours = 0; $hours < 24; $hours++)
    {
        for ($mins = 0; $mins < 60; $mins+=15)
        {
            $hours = str_pad($hours, 2, "0", STR_PAD_LEFT);
            $mins = str_pad($mins, 2, "0", STR_PAD_RIGHT);

            if ($time AND $time[0] == $hours AND $time[1] == $mins)
            {
                $html .= "<option selected='selected' value='$hours:$mins'>$hours:$mins</option>";
            }
            else
            {
                if ($time AND $time[0] == $hours AND $time[1] < $mins AND $time[1] > ($mins - 15))
                {
                    $html .= "<option selected='selected' value='$time[0]:$time[1]'>$time[0]:$time[1]</option>\n";
                }
                else
                {
                    $html .= "<option value='$hours:$mins'>$hours:$mins</option>\n";
                }
            }
        }
    }
    $html .= "</select>";
    return $html;
}


/**
 * Returns the HTML for a drop down list of supported products for the given contact and with the
 * given name and with the given product selected
 * @author Ivan Lucas
 * @todo FIXME this should use the contract and not the contact
 * @note This is no longer called, suggest we remove
 * @deprecated No longer used and as the above FIXME suggests serves no purpose
 */
function supported_product_drop_down($name, $contactid, $productid)
{
    global $CONFIG, $dbSupportContacts, $dbMaintenance, $dbProducts, $strXIncidentsLeft;

    $sql = "SELECT *, p.id AS productid, p.name AS productname FROM `{$dbSupportContacts}` AS sc, `{$dbMaintenance}` AS m, `{$dbProducts}` AS p ";
    $sql .= "WHERE sc.maintenanceid = m.id AND m.product = p.id ";
    $sql .= "AND sc.contactid='$contactid'";

    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if ($CONFIG['debug']) $html .= "<!-- Original product {$productid}-->";
    $html .= "<select name=\"$name\">\n";
    if ($productid == 0)
    {
        $html .= "<option selected='selected' value='0'>No Contract - Not Product Related</option>\n";
    }

    if ($productid == -1)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($products = mysql_fetch_objecy($result))
    {
        $remainingstring = sprintf($strXIncidentsLeft, incidents_remaining($products->incidentpoolid));
        $html .= "<option ";
        if ($productid == $products->productid)
        {
            $html .= "selected='selected' ";
        }
        $html .= "value='{$products->productid}'>";
        $html .= get_sla_name($products->servicelevel)." ".$products->productname.", Exp:".date($CONFIG['dateformat_shortdate'], $products->expirydate).", $remainingstring";
        $html .= "</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}