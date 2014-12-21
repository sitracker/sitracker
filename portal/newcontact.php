<?php
// portal/newcontact.php - Add a site contact
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author Kieran Hogg <kieran[at]sitracker.org>

require ('..' . DIRECTORY_SEPARATOR . 'core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

$accesslevel = 'admin';

include (APPLICATION_LIBPATH . 'portalauth.inc.php');

if (isset($_POST['submit']))
{
    echo process_new_contact('external');
}
else 
{
    include (APPLICATION_INCPATH . 'portalheader.inc.php');
    
    $returnpage = cleanvar($_REQUEST['return']);
    if (!empty($_REQUEST['name']))
    {
        $name = explode(' ',cleanvar(urldecode($_REQUEST['name'])), 2);
        $_SESSION['formdata']['new_contact']['forenames'] = ucfirst($name[0]);
        $_SESSION['formdata']['new_contact']['surname'] = ucfirst($name[1]);
    }
    
    echo show_form_errors('new_contact');
    clear_form_errors('new_contact');
    echo "<h2>".icon('contact', 32)." {$strNewSiteContact}</h2>";
    
    echo "<form name='contactform' action='{$_SERVER['PHP_SELF']}' ";
    echo "method='post' onsubmit=\"return confirm_action('{$strAreYouSureAdd}')\">";
    echo "<table class='maintable vertical'>";
    echo "<tr><th>{$strName}</th>\n";
    
    echo "<td>";
    echo "\n<table><tr><td align='center'>{$strTitle}<br />";
    echo "<input maxlength='50' name='courtesytitle' title=\"";
    echo "{$strCourtesyTitle}\" size='7' value='".show_form_value('new_contact', 'courtesytitle', '')."'/></td>\n";
    
    echo "<td align='center'>{$strForenames}<br />";
    echo "<input class='required' maxlength='100' name='forenames' ";
    echo "size='15' title=\"{$strForenames}\" value='".show_form_value('new_contact', 'forenames', '')."'/></td>\n";
    
    echo "<td align='center'>{$strSurname}<br />";
    echo "<input class='required' maxlength='100' name='surname' ";
    echo "size='20' title=\"{$strSurname}\" value='".show_form_value('new_contact', 'surname', '')."'/>\n";
    echo " <span class='required'>{$strRequired}</span></td></tr>\n";
    echo "</table>\n</td></tr>\n";
    
    echo "<tr><th>{$strJobTitle}</th><td><input maxlength='255'";
    echo " name='jobtitle' size='35' title=\"{$strJobTitle}\" value='".show_form_value('new_contact', 'jobtitle', '')."'/></td></tr>\n";

    echo "<tr><th>{$strDepartment}</th><td><input maxlength='255' name='department' size='35'  value='".show_form_value('new_contact', 'department', '')."' /></td></tr>\n";
    
    echo "<tr><th>{$strEmail}</th><td>";
    echo "<input class='required' maxlength='100' name='email' size='35'  value='".show_form_value('new_contact', 'email', '')."'/>\n";
    echo " <span class='required'>{$strRequired}</span> ";
    
    echo "<label>";
    echo html_checkbox('dataprotection_email', 'No');
    echo "{$strEmail} {$strDataProtection}</label>".help_link("EmailDataProtection");
    echo "</td></tr>\n";
    
    echo "<tr><th>{$strTelephone}</th><td><input maxlength='50' name='phone' size='35'  value='".show_form_value('new_contact', 'phone', '')."'/>\n";
    echo "<label>";
    echo html_checkbox('dataprotection_phone', 'No');
    echo "{$strTelephone} {$strDataProtection}</label>".help_link("TelephoneDataProtection");
    echo "</td></tr>\n";
    
    echo "<tr><th>{$strMobile}</th><td><input maxlength='100' name='mobile' size='35' value='".show_form_value('new_contact', 'mobile', '')."'/></td></tr>\n";

    echo "<tr><th>{$strFax}</th><td><input maxlength='50' name='fax' size='35' value='".show_form_value('new_contact', 'fax', '')."'/></td></tr>\n";
    
    echo "<tr><th>{$strAddress}</th><td><label>";
    echo html_checkbox('dataprotection_address', 'No');
    echo " {$strAddress} {$strDataProtection}</label>";
    echo help_link("AddressDataProtection")."</td></tr>\n";
    echo "<tr><th></th><td><label><input type='checkbox' name='usesiteaddress' value='yes' onclick=\"$('hidden').toggle();\" /> {$strSpecifyAddress}</label></td></tr>\n";
    echo "<tbody id='hidden' style='display:none'>";
    echo "<tr><th>{$strAddress1}</th>";
    echo "<td><input maxlength='255' name='address1' size='35'  value='".show_form_value('new_contact', 'address1', '')."' /></td></tr>\n";
    echo "<tr><th>{$strAddress2}</th>";
    echo "<td><input maxlength='255' name='address2' size='35'  value='".show_form_value('new_contact', 'address2', '')."' /></td></tr>\n";
    echo "<tr><th>{$strCity}</th><td><input maxlength='255' name='city' size='35'  value='".show_form_value('new_contact', 'city', '')."' /></td></tr>\n";
    echo "<tr><th>{$strCounty}</th><td><input maxlength='255' name='county' size='35'  value='".show_form_value('new_contact', 'county', '')."' /></td></tr>\n";
    echo "<tr><th>{$strCountry}</th><td>";
    if (isset($_SESSION['formdata']['new_contact']['country']))
    {
        echo country_drop_down('country', $_SESSION['formdata']['new_contact']['country']);
    }
    else
    {
        echo country_drop_down('country', $CONFIG['home_country']);
    }
    echo "</td></tr>\n";
    echo "<tr><th>{$strPostcode}</th><td><input maxlength='255' name='postcode' size='35'  value='".show_form_value('new_contact', 'postcode', '')."' /></td></tr>\n";
    echo "</tbody>";
    echo "<tr><th>{$strEmailDetails}</th>";
    // Check the box to send portal details, only if portal is enabled
    echo "<td><input type='checkbox' id='emaildetails' name='emaildetails' value='on'";
    if ($CONFIG['portal'] == TRUE) echo " checked='checked'";
    else echo " disabled='disabled'";
    echo " />";
    echo "<label for='emaildetails'>{$strEmailContactLoginDetails}</label></td></tr>";
    plugin_do('contact_new_form');
    echo "</table>\n\n";
    if (!empty($returnpage)) echo "<input type='hidden' name='return' value='{$returnpage}' />";
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input name='submit' type='submit' value=\"{$strSave}\" /></p>";
    echo "<input type='hidden' name='siteid' value='{$_SESSION['siteid']}' />";
    echo "</form>\n";
    
    //cleanup form vars
    clear_form_data('new_contact');
    
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');    
}

?>