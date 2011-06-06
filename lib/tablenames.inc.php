<?php
// tablenames.inc.php - Defines soft database table names
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
//  Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


// Database Table Names
$dbBillingMatrix = "{$CONFIG['db_tableprefix']}billingmatrix";
$dbBillingPeriods = "{$CONFIG['db_tableprefix']}billing_periods";
$dbClosingStatus = "{$CONFIG['db_tableprefix']}closingstatus";
$dbConfig = "{$CONFIG['db_tableprefix']}config";
$dbContactConfig = "{$CONFIG['db_tableprefix']}contactconfig";
$dbContacts = "{$CONFIG['db_tableprefix']}contacts";
$dbDashboard = "{$CONFIG['db_tableprefix']}dashboard";
$dbDashboardRSS = "{$CONFIG['db_tableprefix']}dashboard_rss";
$dbDashboardWatchIncidents = "{$CONFIG['db_tableprefix']}dashboard_watch_incidents";
$dbDrafts = "{$CONFIG['db_tableprefix']}drafts";
$dbEmailSig = "{$CONFIG['db_tableprefix']}emailsig";
$dbEmailTemplates = "{$CONFIG['db_tableprefix']}emailtemplates";
$dbEscalationPaths = "{$CONFIG['db_tableprefix']}escalationpaths";
$dbFeedbackForms = "{$CONFIG['db_tableprefix']}feedbackforms";
$dbFeedbackQuestions = "{$CONFIG['db_tableprefix']}feedbackquestions";
$dbFeedbackReport = "{$CONFIG['db_tableprefix']}feedbackreport";
$dbFeedbackRespondents = "{$CONFIG['db_tableprefix']}feedbackrespondents";
$dbFeedbackResults = "{$CONFIG['db_tableprefix']}feedbackresults";
$dbFiles = "{$CONFIG['db_tableprefix']}files";
$dbGroups = "{$CONFIG['db_tableprefix']}groups";
$dbHolidays = "{$CONFIG['db_tableprefix']}holidays";
$dbIncidentPools = "{$CONFIG['db_tableprefix']}incidentpools";
$dbIncidentProductInfo = "{$CONFIG['db_tableprefix']}incidentproductinfo";
$dbIncidents = "{$CONFIG['db_tableprefix']}incidents";
$dbIncidentStatus = "{$CONFIG['db_tableprefix']}incidentstatus";
$dbInventory = "{$CONFIG['db_tableprefix']}inventory";
$dbJournal = "{$CONFIG['db_tableprefix']}journal";
$dbKBArticles = "{$CONFIG['db_tableprefix']}kbarticles";
$dbKBContent = "{$CONFIG['db_tableprefix']}kbcontent";
$dbKBSoftware = "{$CONFIG['db_tableprefix']}kbsoftware";
$dbLicenceTypes = "{$CONFIG['db_tableprefix']}licencetypes";
$dbLinks = "{$CONFIG['db_tableprefix']}links";
$dbLinkTypes = "{$CONFIG['db_tableprefix']}linktypes";
$dbMaintenance = "{$CONFIG['db_tableprefix']}maintenance";
$dbNotes = "{$CONFIG['db_tableprefix']}notes";
$dbNotices = "{$CONFIG['db_tableprefix']}notices";
$dbNoticeTemplates = "{$CONFIG['db_tableprefix']}noticetemplates";
$dbPermissions = "{$CONFIG['db_tableprefix']}permissions";
$dbPermissionCategories = "{$CONFIG['db_tableprefix']}permissioncategories";
$dbPriority = "{$CONFIG['db_tableprefix']}priority";
$dbProductInfo = "{$CONFIG['db_tableprefix']}productinfo";
$dbProducts = "{$CONFIG['db_tableprefix']}products";
$dbRelatedIncidents = "{$CONFIG['db_tableprefix']}relatedincidents";
$dbResellers = "{$CONFIG['db_tableprefix']}resellers";
$dbRolePermissions = "{$CONFIG['db_tableprefix']}rolepermissions";
$dbRoles = "{$CONFIG['db_tableprefix']}roles";
$dbScheduler = "{$CONFIG['db_tableprefix']}scheduler";
$dbService = "{$CONFIG['db_tableprefix']}service";
$dbServiceLevels = "{$CONFIG['db_tableprefix']}servicelevels";
$dbSetTags = "{$CONFIG['db_tableprefix']}set_tags";
$dbSiteConfig = "{$CONFIG['db_tableprefix']}siteconfig";
$dbSiteContacts = "{$CONFIG['db_tableprefix']}sitecontacts";
$dbSites = "{$CONFIG['db_tableprefix']}sites";
$dbSiteTypes = "{$CONFIG['db_tableprefix']}sitetypes";
$dbSoftware = "{$CONFIG['db_tableprefix']}software";
$dbSoftwareProducts = "{$CONFIG['db_tableprefix']}softwareproducts";
$dbSupportContacts = "{$CONFIG['db_tableprefix']}supportcontacts";
$dbSystem = "{$CONFIG['db_tableprefix']}system";
$dbTags = "{$CONFIG['db_tableprefix']}tags";
$dbTasks = "{$CONFIG['db_tableprefix']}tasks";
$dbTempAssigns = "{$CONFIG['db_tableprefix']}tempassigns";
$dbTempIncoming = "{$CONFIG['db_tableprefix']}tempincoming";
$dbTransactions = "{$CONFIG['db_tableprefix']}transactions";
$dbTriggers = "{$CONFIG['db_tableprefix']}triggers";
$dbUpdates = "{$CONFIG['db_tableprefix']}updates";
$dbUserConfig = "{$CONFIG['db_tableprefix']}userconfig";
$dbUserGroups = "{$CONFIG['db_tableprefix']}usergroups";
$dbUserPermissions = "{$CONFIG['db_tableprefix']}userpermissions";
$dbUsers = "{$CONFIG['db_tableprefix']}users";
$dbUserSoftware = "{$CONFIG['db_tableprefix']}usersoftware";
$dbUserStatus = "{$CONFIG['db_tableprefix']}userstatus";
$dbVendors = "{$CONFIG['db_tableprefix']}vendors";

?>
