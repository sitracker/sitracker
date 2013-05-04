<?php
// products.php - List products
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
$permission = PERM_PRODUCT_VIEW; // View Products and Software
require (APPLICATION_LIBPATH.'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH.'auth.inc.php');

$title = $strListProducts;

// External Variables
$productid = clean_int($_REQUEST['productid']);
$display = clean_fixed_list($_REQUEST['display'], array('','skills','software'));

include (APPLICATION_INCPATH . 'htmlheader.inc.php');

if (empty($productid) AND $display!='skills')
{
    $sql = "SELECT * FROM `{$dbVendors}` ORDER BY name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

    echo "<h2>".icon('product', 32, $strProducts)." {$strProducts}</h2>";
    plugin_do('products');
    if (mysql_num_rows($result) >= 1)
    {
        while ($vendor = mysql_fetch_object($result))
        {
            echo "<h3>{$strVendor}: {$vendor->name}</h3>";
            $psql = "SELECT * FROM `{$dbProducts}` WHERE vendorid='{$vendor->id}' ORDER BY name";
            $presult = mysql_query($psql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($presult) >= 1)
            {
                echo "<table summary='{$strListProducts}' align='center' width='95%'>";
                echo "<tr><th width='20%'>{$strProduct}</th><th width='52%'>{$strDescription}</th><th width='10%'>{$strLinkedSkills}</th>";
                echo "<th width='10%'>{$strActiveContracts}</th><th width='8%'>{$strActions}</th></tr>\n";
                $shade = 'shade1';
                while ($product = mysql_fetch_object($presult))
                {
                    // Count linked skills
                    $ssql = "SELECT COUNT(softwareid) FROM `{$dbSoftwareProducts}` WHERE productid={$product->id}";
                    $sresult = mysql_query($ssql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                    list($countlinked) = mysql_fetch_row($sresult);

                    // Count contracts
                    $ssql = "SELECT COUNT(id) FROM `{$dbMaintenance}` WHERE product='{$product->id}' AND term!='yes' AND (expirydate > '{$now}' OR expirydate = '-1')";
                    $sresult = mysql_query($ssql);
                    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                    list($countcontracts) = mysql_fetch_row($sresult);

                    if ($countlinked < 1) $shade = 'urgent';
                    if ($countcontracts < 1) $shade = 'inactive';
                    if ($product->active == 'false') $shade = 'expired';
                    
                    echo "<tr class='{$shade}'><td><a href='{$_SERVER['PHP_SELF']}?productid={$product->id}' name='{$product->id}'>{$product->name}</a></td>";
                    echo "<td>{$product->description}</td>";
                    echo "<td align='right'>{$countlinked}</td>";
                    echo "<td align='right'>";
                    if ($countcontracts > 0)
                    {
                        echo "<a href='contracts.php?search_string=&amp;productid={$product->id}&amp;activeonly=yes'>{$countcontracts}</a>";
                    }
                    else
                    {
                        echo $countcontracts;
                    }
                    echo "</td>";
                    echo "<td>";
                    $operations = array();
                    $operations[$strEdit] = "edit_product.php?id={$product->id}";
                    $operations[$strDelete] = "product_delete.php?id={$product->id}";
                    echo html_action_links($operations);
                    echo "</td></tr>\n";
                    if ($shade == 'shade1') $shade = 'shade2';
                    else $shade = 'shade1';
                }
                echo "</table>\n";
            }
            else
            {
                echo user_alert($strNoProductsForThisVendor, E_USER_NOTICE);
            }
        }
    }
    else
    {
        echo "<p class='error'>{$strNoVendorsDefined}</p>";
    }


    $sql = "SELECT s.* FROM `{$dbSoftware}` AS s LEFT JOIN `{$dbSoftwareProducts}` AS sp ON s.id = sp.softwareid WHERE sp.softwareid IS NULL";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) >= 1)
    {
        echo "<h2>".icon('skill', 32)." Skills not linked</h2>";
        echo "<p align='center'>These skills are not linked to any product</p>";
        echo "<table summary='' align='center' width='55%'>";
        echo "<tr><th>{$strSkill}</th><th>{$strLifetime}</th>";
        echo "<th>{$strEngineers}</th><th>{$strIncidents}</th><th>{$strActions}</th></tr>";
        while ($software = mysql_fetch_object($result))
        {
            $ssql = "SELECT COUNT(userid) FROM `{$dbUserSoftware}` AS us, `{$dbUsers}` AS u WHERE us.userid = u.id AND u.status!=0 AND us.softwareid = '{$software->id}'";
            $sresult = mysql_query($ssql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            list($countengineers) = mysql_fetch_row($sresult);

            $ssql = "SELECT COUNT(id) FROM `{$dbIncidents}` WHERE softwareid='{$software->id}'";
            $sresult = mysql_query($ssql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            list($countincidents) = mysql_fetch_row($sresult);

            echo "<tr class='{$shade}'><td>".icon('skill', 16)." ";
            echo "{$software->name}</td>";
            echo "<td>";
            if ($software->lifetime_start > 1)
            {
                echo ldate($CONFIG['dateformat_shortdate'],mysql2date($software->lifetime_start))." {$strTo} ";
            }
            else
            {
                echo "&#8734;";
            }

            if ($software->lifetime_end > 1)
            {
                echo ldate($CONFIG['dateformat_shortdate'], mysql2date($software->lifetime_end));
            }
            elseif ($software->lifetime_start > 1)
            {
                echo "&#8734;";
            }

            echo "</td>";
            echo "<td>{$countengineers}</td>";
            echo "<td>{$countincidents}</td>";
            echo "<td>";
            $operations = array();
            $operations[$strLink] = "product_skill_new.php?softwareid={$software->id}";
            $operations[$strEdit] = "edit_skill.php?id={$software->id}";
            $operations[$strDelete] = "edit_skill.php?id={$software->id}&amp;action=delete";
            echo html_action_links($operations);
            echo "</td>";
            echo "</tr>\n";
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        echo "</table>";
    }
}
elseif (empty($productid) AND ($display == 'skills' OR $display == 'software'))
{
    echo "<h2>".icon('skill', 32)." {$strSkills}</h2>";
    $sql = "SELECT s.*, v.name AS vendorname FROM `{$dbSoftware}` AS s LEFT JOIN `{$dbVendors}` AS v ON s.vendorid = v.id ORDER BY name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($result) >= 1)
    {
        echo "<table class='maintable'>";
        echo "<tr><th>{$strSkill}</th><th>{$strVendor}</th>";
        echo "<th>{$strLifetime}</th><th>{$strLinkedToNumProducts}</th>";
        echo "<th>{$strEngineers}</th><th>{$strIncidents}</th><th>{$strActions}</th></tr>";
        $shade = 'shade1';
        while ($software = mysql_fetch_object($result))
        {
            $ssql = "SELECT COUNT(userid) FROM `{$dbUserSoftware}` AS us, `{$dbUsers}` AS u WHERE us.userid = u.id AND u.status!=0 AND us.softwareid='{$software->id}'";
            $sresult = mysql_query($ssql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            list($countengineers) = mysql_fetch_row($sresult);

            // Count linked products
            $ssql = "SELECT COUNT(productid) FROM `{$dbSoftwareProducts}` WHERE softwareid={$software->id}";
            $sresult = mysql_query($ssql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            list($countlinked)=mysql_fetch_row($sresult);

            $ssql = "SELECT COUNT(id) FROM `{$dbIncidents}` WHERE softwareid='{$software->id}'";
            $sresult = mysql_query($ssql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            list($countincidents) = mysql_fetch_row($sresult);

            $lifetime_start = mysql2date($software->lifetime_start);
            $lifetime_end = mysql2date($software->lifetime_end);

            if ($countengineers < 1) $shade = "notice";
            if ($countlinked < 1) $shade = "urgent";
            if ($lifetime_start > $now OR ($lifetime_end > 1 AND $lifetime_end < $now))
            {
                $shade = 'expired';
            }

            echo "<tr class='{$shade}'>";
            echo "<td>{$software->name}</td>";
            echo "<td>{$software->vendorname}</td>";
            echo "<td>";
            if ($software->lifetime_start > 1)
            {
                echo ldate($CONFIG['dateformat_shortdate'],$lifetime_start)." {$strTo} ";
            }
            else
            {
                echo "&#8734;";
            }

            if ($software->lifetime_end > 1)
            {
                echo ldate($CONFIG['dateformat_shortdate'],$lifetime_end);
            }
            elseif ($software->lifetime_start >1)
            {
                echo "&#8734;";
            }

            echo "</td>";
            echo "<td>{$countlinked}</td>";
            echo "<td>{$countengineers}</td>";
            echo "<td>{$countincidents}</td>";
            echo "<td>";
            $operations = array();
            $operations[$strLink] = array('url' => "product_skill_new.php?softwareid={$software->id}", 'perm' => PERM_PRODUCT_ADD);
            $operations[$strEdit] = array('url' => "edit_skill.php?id={$software->id}", 'perm' => PERM_SKILL_ADD);
            $operations[$strDelete] = array('url' => "edit_skill.php?id={$software->id}&amp;action=delete", 'perm' => PERM_SKILL_ADD);
            echo html_action_links($operations);
            echo "</td>";
            echo "</tr>\n";
            if ($shade == 'shade1') $shade = 'shade2';
            else $shade = 'shade1';
        }
        echo "</table>";

        // Legend
        if ($_SESSION['userconfig']['show_table_legends'] == 'TRUE')
        {
            echo "<br />";
            echo "<table class='legend'><tr>";
            echo "<td class='shade1'>{$strOK}</td>";
            echo "<td class='expired'>{$strEndOfLife}</td>";
            echo "<td class='notice'>{$strNoEngineers}</td>";
            echo "<td class='urgent'>{$strUnused}</td>";
            echo "</tr></table>";
        }
    }
    else echo user_alert($GLOBALS['strNothingToDisplay'], E_USER_NOTICE);

}
else
{
    $psql = "SELECT * FROM `{$dbProducts}` WHERE id='{$productid}' LIMIT 1";
    $presult = mysql_query($psql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    if (mysql_num_rows($presult) >= 1)
    {
        while ($product = mysql_fetch_object($presult))
        {
            echo "<h2>".icon('product', 32)." ".sprintf($strProductX, $product->name)."</h2>";
            echo "<p align='center'><a href='edit_product.php?id={$product->id}'>Edit</a> ";
            echo "| <a href='product_delete.php?id={$product->id}'>{$strDelete}</a></p>";
            $tags = list_tags($product->id, TAG_PRODUCT, TRUE);

            if (!empty($tags)) echo "<div id='producttags'>{$tags}</div><br />\n";
            echo "<table class='maintable'>";

            if (!empty($product->description)) echo "<tr class='shade1'><td colspan='0'>".nl2br($product->description)."</td></tr>";

            $swsql = "SELECT * FROM `{$dbSoftwareProducts}` AS sp, `{$dbSoftware}` AS s ";
            $swsql .= "WHERE sp.softwareid=s.id AND productid='{$product->id}' ORDER BY name";
            $swresult=mysql_query($swsql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

            if (mysql_num_rows($swresult) > 0)
            {
                echo "<tr><th>{$strSkill}</th><th>{$strLifetime}</th>";
                echo "<th>{$strEngineers}</th><th>{$strIncidents}</th>";
                echo "<th>{$strActions}</th></tr>";
                $shade = 'shade2';
                while ($software = mysql_fetch_object($swresult))
                {
                    $ssql = "SELECT COUNT(userid) FROM `{$dbUserSoftware}` AS us, `{$dbUsers}` AS u WHERE us.userid = u.id AND u.status!=0 AND us.softwareid='{$software->id}'";
                    $sresult = mysql_query($ssql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                    list($countengineers) = mysql_fetch_row($sresult);

                    $ssql = "SELECT COUNT(id) FROM `{$dbIncidents}` WHERE softwareid='{$software->id}'";
                    $sresult = mysql_query($ssql);
                    if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
                    list($countincidents) = mysql_fetch_row($sresult);

                    echo "<tr class='{$shade}'><td>".icon('skill', 16)." ";
                    echo "{$software->name}</td>";
                    echo "<td>";
                    if ($software->lifetime_start > 1)
                    {
                        echo ldate($CONFIG['dateformat_shortdate'], mysql2date($software->lifetime_start))." {$strTo} ";
                    }
                    else
                    {
                        echo "&#8734;";
                    }

                    if ($software->lifetime_end > 1)
                    {
                        echo ldate($CONFIG['dateformat_shortdate'], mysql2date($software->lifetime_end));
                    }
                    elseif ($software->lifetime_start > 1)
                    {
                        echo "&#8734;";
                    }
                    echo "</td>";
                    echo "<td>{$countengineers}</td>";
                    echo "<td>{$countincidents}</td>";
                    echo "<td><a href='delete_product_skill.php?productid={$product->id}&amp;softwareid={$software->softwareid}'>{$strUnlink}</a> ";
                    echo "| <a href='edit_skill.php?id={$software->softwareid}'>{$strEdit}</a> ";
                    echo "| <a href='edit_skill.php?id={$software->softwareid}&amp;action=delete'>{$strDelete}</a>";
                    echo "</td>";
                    echo "</tr>\n";
                    if ($shade == 'shade1') $shade = 'shade2';
                    else $shade = 'shade1';
                }
            }
            else
            {
                echo "<tr><td>&nbsp;</td><td><em>{$strNoSkillsLinkedToProduct}</em></td><td>&nbsp;</td></tr>\n";
            }
            echo "</table>\n";
            echo "<p align='center'><a href='product_skill_new.php?productid={$product->id}'>".sprintf($strLinkSkillToX, $product->name)."</a></p>\n";

            $sql = "SELECT * FROM `{$dbProductInfo}` WHERE productid='{$product->id}'";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($result) > 0)
            {
                echo "<h3>{$strProductQuestions}</h3>";
                echo "<table class='maintable'>";
                echo "<tr><th>{$strQuestion}</th><th>{$strAdditionalInfo}</th></tr>";
                $shade = 'shade1';
                while ($productinfoobj = mysql_fetch_object($result))
                {
                    echo "<tr class='{$shade}'><td>{$productinfoobj->information}</td>";
                    echo "<td>{$productinfoobj->moreinformation}</td></tr>\n";
                    if ($shade == 'shade1') $shade = 'shade2';
                    else $shade = 'shade1';
                }
                echo "</table>";
            }
            echo "<p align='center'><a href='product_info_new.php?product={$product->id}'>{$strNewProductQuestion}</a></p>";

            $sql = "SELECT * FROM `{$dbMaintenance}` WHERE product='{$product->id}' ORDER BY id DESC";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($result) >= 1)
            {
                echo "<h3>{$strRelatedContracts}</h3>";
                echo "<table class='maintable'>";
                echo "<tr><th>{$strContract}</th><th>{$strSite}</th></tr>";
                $shade = 'shade1';
                while ($contract = mysql_fetch_object($result))
                {
                    if ($contract->term == 'yes' OR ($contract->expirydate < $now AND $contract->expirydate > -1))
                    {
                        $shade = "expired";
                    }

                    echo "<tr class='{$shade}'>";
                    echo "<td>".icon('contract', 16)." ";
                    echo "<a href='contract_details.php?id={$contract->id}'>".sprintf($strContractNum, $contract->id)."</a></td>";
                    echo "<td>".site_name($contract->site)."</td>";
                    echo "</tr>\n";
                    if ($shade == 'shade1') $shade = 'shade2';
                    else $shade = 'shade1';
                }
                echo "</table>\n";
            }

            $sql = "SELECT * FROM `{$dbIncidents}` WHERE product={$product->id} ORDER BY id DESC";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(), E_USER_WARNING);
            if (mysql_num_rows($result) >= 1)
            {
                echo "<h3>{$strRelatedIncidents}</h3>";
                echo "<table class='maintable'>";
                echo "<tr><th>{$strIncident}</th><th>{$strContact}</th><th>{$strSite}</th><th>{$strTitle}</th></tr>";
                $shade = 'shade1';
                while ($incident = mysql_fetch_object($result))
                {
                    echo "<tr class='{$shade}'>";
                    echo "<td>".html_incident_popup_link($incident->id, sprintf($strIncidentNum, $incident->id))."</td>";
                    echo "<td>".contact_realname($incident->contact)."</td><td>".contact_site($incident->contact)."</td>";
                    echo "<td>{$incident->title}</td>";
                    echo "</tr>\n";
                    if ($shade == 'shade1') $shade = 'shade2';
                    else $shade = 'shade1';
                }
                echo "</table>\n";

            }
        }
    }
    else
    {
        echo "<p class='error'>{$strNoMatchingProduct}</p>";
    }

    echo "<p class='return'><a href='{$_SERVER['PHP_SELF']}#{$productid}'>{$strBackToList}</a></p>";
}

echo "<p align='center'>";
$operations = array();
$operations[$strNewVendor] = 'vendor_new.php';
$operations[$strNewProduct] = 'product_new.php';
$operations[$strNewSkill] = 'skill_new.php';


if ($display == 'skills' OR $display == 'software')
{
    $operations[$strListProducts] = 'products.php';
}
else
{
    $operations[$strListSkills] = 'products.php?display=skills';
}
$operations[$strNewProductQuestion] = "product_info_new.php?product={$productid}";
echo html_action_links($operations);
echo "</p>";

include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>