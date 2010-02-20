<?php
// sit.js.php - JAVASCRIPT file
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// Note: This file is PHP that outputs Javascript code, this is primarily
//       to enable us to pass variables from PHP to Javascript.
//

$permission = 0; // not required
require ('..' . DIRECTORY_SEPARATOR . 'core.php');

session_name($CONFIG['session_name']);
session_start();

require (APPLICATION_LIBPATH . 'functions.inc.php');

header('Content-type: text/javascript');

echo "
var application_webpath = '{$CONFIG['application_webpath']}';
var strJanAbbr = '{$strJanAbbr}';
var strFebAbbr = '{$strFebAbbr}';
var strMarAbbr = '{$strMarAbbr}';
var strAprAbbr = '{$strAprAbbr}';
var strMayAbbr = '{$strMayAbbr}';
var strJunAbbr = '{$strJunAbbr}';
var strJulAbbr = '{$strJulAbbr}';
var strAugAbbr = '{$strAugAbbr}';
var strSepAbbr = '{$strSepAbbr}';
var strOctAbbr = '{$strOctAbbr}';
var strNovAbbr = '{$strNovAbbr}';
var strDecAbbr = '{$strDecAbbr}';

var strAreYouSureUpdateLastBilled = \"{$strAreYouSureUpdateLastBilled}\";
var strYouMustEnterIncidentTitle = \"{$strYouMustEnterIncidentTitle}\";
var strKnowledgeBaseArticle = \"{$strKnowledgeBaseArticle}\";
var strSelectKBSections = \"{$strSelectKBSections}\";
var strFinalUpdate = \"{$strFinalUpdate}\";
var strEnterDetailsAboutIncidentToBeStoredInLog = \"{$strEnterDetailsAboutIncidentToBeStoredInLog}\";
var strSummaryOfProblemAndResolution = \"{$strSummaryOfProblemAndResolution}\";

/* Please don't add functions here, these functions below need moving to webtrack.js
   this file is to make i18n keys available in javascript
*/



/**
  * Display/Hide contents of a password field
  * (converts from a password to text field and back)
  * @author Ivan Lucas
  * @param string elem. The ID of the password input HTML element
**/
function password_reveal(elem)
{
    var elemlink = 'link' + elem;
    if ($(elem).type == 'password')
    {
        $(elem).type = 'text';
        $(elemlink).innerHTML = '{$strHide}';
    }
    else
    {
        $(elem).type = 'password';
        $(elemlink).innerHTML = '{$strReveal}';
    }
}



/**
  * Check the LDAP details entered and display the results
  * @author Paul heaney
  * @param string statusfield element ID of the DIV that will contain the status text
*/
function checkLDAPDetails(statusfield)
{
    $(statusfield).innerHTML = \"<strong>{$strCheckingDetails}</strong>\";

    var server = $('ldap_host').value;
    var port = $('ldap_port').value;
    var protocol = $('ldap_protocol').options[$('ldap_protocol').selectedIndex].value;
    var security = $('ldap_security').options[$('ldap_security').selectedIndex].value;
    var user = $('ldap_bind_user').value;
    var password = $('cfgldap_bind_pass').value;

    // Auto save
    var xmlhttp=false;

    if (!xmlhttp && typeof XMLHttpRequest!='undefined')
    {
        try
        {
            xmlhttp = new XMLHttpRequest();
        }
        catch (e)
        {
            xmlhttp=false;
        }
    }
    if (!xmlhttp && window.createRequest)
    {
        try
        {
            xmlhttp = window.createRequest();
        }
        catch (e)
        {
            xmlhttp=false;
        }
    }

    var url =  \"ajaxdata.php\";
    var params = \"action=checkldap&ldap_host=\"+server+\"&ldap_port=\"+port+\"&ldap_protocol=\"+protocol+\"&ldap_security=\"+security+\"&ldap_bind_user=\"+escape(user)+\"&ldap_bind_pass=\"+escape(password);
    xmlhttp.open(\"POST\", url, true)
    xmlhttp.setRequestHeader(\"Content-type\", \"application/x-www-form-urlencoded\");
    xmlhttp.setRequestHeader(\"Content-length\", params.length);
    xmlhttp.setRequestHeader(\"Connection\", \"close\");


    xmlhttp.onreadystatechange=function()
    {
        if (xmlhttp.readyState==4)
        {
            if (xmlhttp.responseText != '')
            {
                if (xmlhttp.responseText == 1)
                {
                    $(statusfield).innerHTML = \"<strong>{$strLDAPTestSucessful}</strong>\";
                }
                else
                {
                    $(statusfield).innerHTML = \"<strong>{$strLDAPTestFailed}</strong>\";
                }
            }
        }
    }
    xmlhttp.send(params);
}

";




?>
