<?php
// sit.js.php - JAVASCRIPT file
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// Note: This file is PHP that outputs Javascript code, this is primarily
//       to enable us to pass variables from PHP to Javascript.
//

require ('..' . DIRECTORY_SEPARATOR . 'core.php');
$permission = PERM_NOT_REQUIRED; // not required

session_name($CONFIG['session_name']);
session_start();

require (APPLICATION_LIBPATH . 'functions.inc.php');

if ($_SESSION['auth'] == TRUE)
{
    $theme = $_SESSION['userconfig']['theme'];
    $iconset = $_SESSION['userconfig']['iconset'];
}
else
{
    $theme = $CONFIG['default_interface_style'];
    $iconset = $CONFIG['default_iconset'];
}
if (empty($iconset)) $iconset = 'sit';

header('Content-type: text/javascript');

$site_icon = icon('site', 16);
$navdown_icon = icon('navdown', 16);
$navup_icon = icon('navup', 16);
$kb_icon = icon('kb', 16);
$save_icon = icon('save', 16, $strSaveDraft);
$info_icon = icon('info', 16, $strDraftLastSaved);
$icon_ldap_container = icon('ldap-directory', 16);
$icon_ldap_group = icon('ldap-group', 16);

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

var strMondayAbbr = '{$strMondayAbbr}';
var strTuesdayAbbr = '{$strTuesdayAbbr}';
var strWednesdayAbbr = '{$strWednesdayAbbr}';
var strThursdayAbbr = '{$strThursdayAbbr}';
var strFridayAbbr = '{$strFridayAbbr}';
var strSaturdayAbbr = '{$strSaturdayAbbr}';
var strSundayAbbr = '{$strSundayAbbr}';

var strActionPlan = '{$strActionPlan}';
var strProblemDefinition = '{$strProblemDefinition}'
var strResolutionReprioritisation = '{$strResolutionReprioritisation}';

var strAreYouSureUpdateLastBilled = \"{$strAreYouSureUpdateLastBilled}\";
var strCheckingDetails = \"{$strCheckingDetails}\";
var strEnterDetailsAboutIncidentToBeStoredInLog = \"{$strEnterDetailsAboutIncidentToBeStoredInLog}\";
var strFinalUpdate = \"{$strFinalUpdate}\";
var strHide = \"{$strHide}\";
var strKnowledgeBaseArticle = \"{$strKnowledgeBaseArticle}\";
var strLDAPTestFailed = \"{$strLDAPTestFailed}\";
var strLDAPUserBaseDNIncorrect = \"{$strLDAPUserBaseDNIncorrect}\";
var strLDAPAdminGroupIncorrect = \"{$strLDAPAdminGroupIncorrect}\";
var strLDAPManagerGroupIncorrect = \"{$strLDAPManagerGroupIncorrect}\";
var strLDAPUserGroupIncorrect = \"{$strLDAPUserGroupIncorrect}\";
var strLDAPCustomerGroupIncorrect = \"{$strLDAPCustomerGroupIncorrect}\";
var strLDAPTestSucessful = \"{$strLDAPTestSucessful}\";
var strLDAPTestFailed = \"{$strLDAPTestFailed}\";
var strPasswordIncorrect = \"{$strPasswordIncorrect}\";
var strReveal = \"{$strReveal}\";
var strSaved = \"{$strSaved}\";
var strSelectAFieldForTemplates = \"{$strSelectAFieldForTemplates}\";
var strSelectKBSections = \"{$strSelectKBSections}\";
var strSummaryOfProblemAndResolution = \"{$strSummaryOfProblemAndResolution}\";
var strUp = \"{$strUp}\";
var strYouMustEnterIncidentTitle = \"{$strYouMustEnterIncidentTitle}\";
var strEmailSentSuccessfullyConfirmWindowClosure = \"{$strEmailSentSuccessfullyConfirmWindowClosure}\";

/* CONSTANTS */

var LDAP_PASSWORD_INCORRECT = ".LDAP_PASSWORD_INCORRECT.";
var LDAP_BASE_INCORRECT = ".LDAP_BASE_INCORRECT.";
var LDAP_ADMIN_GROUP_INCORRECT = ".LDAP_ADMIN_GROUP_INCORRECT.";
var LDAP_MANAGER_GROUP_INCORRECT = ".LDAP_MANAGER_GROUP_INCORRECT.";
var LDAP_USER_GROUP_INCORRECT = ".LDAP_USER_GROUP_INCORRECT.";
var LDAP_CUSTOMER_GROUP_INCORRECT = ".LDAP_CUSTOMER_GROUP_INCORRECT.";
var LDAP_CORRECT = ".LDAP_CORRECT.";

/* SESSIONS */

var show_confirmation_caution = '{$_SESSION['userconfig']['show_confirmation_caution']}';
var show_confirmation_delete = '{$_SESSION['userconfig']['show_confirmation_delete']}';

/* ICONS */

var icon_site = '{$site_icon}';
var icon_navdown = '{$navdown_icon}';
var icon_kb = '{$kb_icon}';
var icon_navup = '{$navup_icon}';
var save_icon = '{$save_icon}';
var info_icon = '{$info_icon}';
var icon_ldap_group = '{$icon_ldap_group}';
var icon_ldap_container = '{$icon_ldap_container}';


/*
    Please don't add functions here, functions belong in webtrack.js
    this file is to make i18n keys available in javascript
*/
";

?>