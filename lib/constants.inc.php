<?php
// constants.inc.php - Constants
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas, <ivan[at]sitracker[dot]org>


// IMPORTANT: No functions or classes here, just defining constants,
// this file is loaded early in the bootstrap and used by setup


/**
 * Begin constant definitions
 **/

// Journal Logging
define ('CFG_LOGGING_OFF',0);        // 0 = No logging
define ('CFG_LOGGING_MIN',1);        // 1 = Minimal Logging
define ('CFG_LOGGING_NORMAL',2);     // 2 = Normal Logging
define ('CFG_LOGGING_FULL',3);       // 3 = Full Logging
define ('CFG_LOGGING_MAX',4);        // 4 = Maximum/Debug Logging

define ('CFG_JOURNAL_DEBUG', 0);     // 0 = for internal debugging use
define ('CFG_JOURNAL_LOGIN', 1);     // 1 = Logon/Logoff
define ('CFG_JOURNAL_SUPPORT', 2);   // 2 = Support Incidents
define ('CFG_JOURNAL_SALES', 3);     // 3 = Sales Incidents (Legacy, unused)
define ('CFG_JOURNAL_SITES', 4);     // 4 = Sites
define ('CFG_JOURNAL_CONTACTS', 5);  // 5 = Contacts
define ('CFG_JOURNAL_ADMIN', 6);     // 6 = Admin
define ('CFG_JOURNAL_USER', 7);       // 7 = User Management
define ('CFG_JOURNAL_MAINTENANCE', 8);  // 8 = Maintenance Contracts
define ('CFG_JOURNAL_PRODUCTS', 9);
define ('CFG_JOURNAL_OTHER', 10);
define ('CFG_JOURNAL_TRIGGERS', 11);
define ('CFG_JOURNAL_KB', 12);       // Knowledge Base
define ('CFG_JOURNAL_TASKS', 13);

define ('TAG_CONTACT', 1);
define ('TAG_INCIDENT', 2);
define ('TAG_SITE', 3);
define ('TAG_TASK', 4);
define ('TAG_PRODUCT', 5);
define ('TAG_SKILL', 6);
define ('TAG_KB_ARTICLE', 7);
define ('TAG_REPORT', 8);

define ('NOTE_TASK', 10);

// Holidays
define ('HOL_NORMAL', 0);  // Normal day, work hours
define ('HOL_HOLIDAY', 1); // Holiday/Leave
define ('HOL_SICKNESS', 2);
define ('HOL_WORKING_AWAY', 3);
define ('HOL_TRAINING', 4);
define ('HOL_FREE', 5); // Compassionate/Maternity/Paterity/etc/free
// The holiday archiving assumes standard holidays are < 10
define ('HOL_PUBLIC', 10);  // Public Holiday (eg. Bank Holiday)
define ('HOL_APPROVAL_NONE', 0); // Not granted or denied
define ('HOL_APPROVAL_GRANTED', 1);
define ('HOL_APPROVAL_DENIED', 2);
// TODO define the other approval (archive) states here, 10, 11 etc.
define ('HOL_APPROVAL_NONE_ARCHIVED', 10);
define ('HOL_APPROVAL_GRANTED_ARCHIVED', 11);
define ('HOL_APPROVAL_DENIED_ARCHIVED', 12);

//default notice types
define ('NORMAL_NOTICE_TYPE', 0);
define ('WARNING_NOTICE_TYPE', 1);
define ('CRITICAL_NOTICE_TYPE', 2);
define ('TRIGGER_NOTICE_TYPE', 3);
define ('USER_DEFINED_NOTICE_TYPE', 4);

// Incident statuses
define ("STATUS_ACTIVE",1);
define ("STATUS_CLOSED",2);
define ("STATUS_RESEARCH",3);
define ("STATUS_LEFTMESSAGE",4);
define ("STATUS_COLLEAGUE",5);
define ("STATUS_SUPPORT",6);
define ("STATUS_CLOSING",7);
define ("STATUS_CUSTOMER",8);
define ("STATUS_UNSUPPORTED",9);
define ("STATUS_UNASSIGNED",10);

// User statuses
define ('USERSTATUS_ACCOUNT_DISABLED', 0);
define ('USERSTATUS_IN_OFFICE', 1);
define ('USERSTATUS_NOT_IN_OFFICE', 2);
define ('USERSTATUS_IN_MEETING', 3);
define ('USERSTATUS_AT_LUNCH', 4);
define ('USERSTATUS_ON_HOLIDAY', 5);
define ('USERSTATUS_WORKING_FROM_HOME', 6);
define ('USERSTATUS_ON_TRAINING_COURSE', 7);
define ('USERSTATUS_ABSENT_SICK', 8);
define ('USERSTATUS_WORKING_AWAY', 9);

// BILLING
define ('NO_BILLABLE_CONTRACT', 0);
define ('CONTACT_HAS_BILLABLE_CONTRACT', 1);
define ('SITE_HAS_BILLABLE_CONTRACT', 2);

// For tempincoming
define ("REASON_POSSIBLE_NEW_INCIDENT", 1);
define ("REASON_INCIDENT_CLOSED", 2);

// Licence
define ("LICENCE_PER_USER", 1);
define ("LICENCE_PER_WORKSTATION", 2);
define ("LICENCE_PER_SERVER", 3);
define ("LICENCE_SITE", 4);
define ("LICENCE_EVALUATION", 5);

// Install Settings
define ("MIN_PHP_VERSION", 5.1);
define ("MIN_MYSQL_VERSION", 4.1);
define ('INSTALL_INFO', 0);
define ('INSTALL_OK', 1);
define ('INSTALL_WARN', 2);
define ('INSTALL_FATAL', 3);

// Queue
define ('QUEUE_ACTION_NEEDED', 1);
define ('QUEUE_WAITING', 2);
define ('QUEUE_ALL_OPEN', 3);
define ('QUEUE_ALL_CLOSED', 4);

// Namespaces
define ('NAMESPACE_SIT', 1);
define ('NAMESPACE_INCIDENT', 2);
define ('NAMESPACE_USER', 3);
define ('NAMESPACE_CONTACT', 4);
define ('NAMESPACE_SITE', 5);

// PRIORITIES
define ('PRIORITY_LOW', 1);
define ('PRIORITY_MEDIUM', 2);
define ('PRIORITY_HIGH', 3);
define ('PRIORITY_CRITICAL', 4);

// Permissions constants (These numbers CANNOT and MUST NOT be changed since
// earlier releases of SiT! are using these numbers in permission configurations.
// (Number, not constant name stored in the SiT! database). - INL 2011-06-22
// If you change the number you'll grant or deny user permissions on a system
// you can't control.
define ('PERM_NOT_REQUIRED', 0); // No permission required, allow all access
define ('PERM_CONTACT_ADD', 1);
define ('PERM_SITE_ADD', 2);
define ('PERM_SITE_EDIT', 3);
define ('PERM_MYPROFILE_EDIT', 4);
define ('PERM_INCIDENT_ADD', 5);
define ('PERM_INCIDENT_LIST', 6);
define ('PERM_INCIDENT_EDIT', 7);
define ('PERM_UPDATE_ADD', 8);
define ('PERM_USER_PERMISSIONS_EDIT', 9);
define ('PERM_CONTACT_EDIT', 10);
define ('PERM_SITE_VIEW', 11);
define ('PERM_CONTACT_VIEW', 12);
define ('PERM_INCIDENT_REASSIGN', 13);
define ('PERM_USER_VIEW', 14);
define ('PERM_SUPPORTED_PRODUCT_ADD', 15);  // used??
define ('PERM_TEMPLATE_ADD', 16);
define ('PERM_TEMPLATE_EDIT', 17);
define ('PERM_INCIDENT_CLOSE', 18);
define ('PERM_CONTRACT_VIEW', 19);
define ('PERM_USER_ADD', 20);
define ('PERM_CONTRACT_EDIT', 21);
define ('PERM_ADMIN', 22);
define ('PERM_USER_EDIT', 23);
define ('PERM_PRODUCT_ADD', 24);
define ('PERM_PRODUCTINFO_ADD', 25);
define ('PERM_HELP_VIEW', 26);
define ('PERM_CALENDAR_VIEW', 27);
define ('PERM_PRODUCT_VIEW', 28); // Also software/skills
define ('PERM_PRODUCT_EDIT', 29);
define ('PERM_SUPPORTED_PRODUCT_VIEW', 30);
// perm 31 unused/reserved
define ('PERM_SUPPORTED_PRODUCT_EDIT', 32);
define ('PERM_EMAIL_SEND', 33);
define ('PERM_INCIDENT_REOPEN', 34);
define ('PERM_MYSTATUS_SET', 35);
define ('PERM_CONTACT_FLAGS_SET', 36); // is this used?
define ('PERM_REPORT_RUN', 37);
define ('PERM_INCIDENT_SALES_EDIT', 38); // legacy sales incidents, is this used?
define ('PERM_CONTRACT_ADD', 39);
define ('PERM_INCIDENT_FORCE_ASSIGN', 40);
define ('PERM_STATUS_VIEW', 41); // view system status
define ('PERM_UPDATE_DELETE', 42); // Review/Delete Incident updates
define ('PERM_GLOBALSIG_EDIT', 43);
// perm 44 unused define ('PERM_FILE_PUBLISH', 44); // Publish files to FTP site
// perm 45 unused (View Mailing List Subscriptions)
// perm 46 unused (Edit Mailing List Subscriptions)
// perm 47 unused (Administrate Mailing Lists)
define ('PERM_FEEDBACK_FORM_ADD', 48);
define ('PERM_FEEDBACK_FORM_EDIT', 49);
define ('PERM_HOLIDAY_APPROVE', 50);
define ('PERM_FEEDBACK_VIEW', 51);
define ('PERM_UPDATE_VIEW_HIDDEN', 52);
define ('PERM_SLA_EDIT', 53);
define ('PERM_KB_VIEW', 54);
define ('PERM_SITE_DELETE', 55); // Also contacts delete at the moment (2011-06-22)
define ('PERM_SKILL_ADD', 56); // Was, add software
define ('PERM_USER_DISABLE', 57);
define ('PERM_MYSKILLS_SET', 58);
define ('PERM_USER_SKILLS_SET', 59); // Set other users skills
define ('PERM_SEARCH', 60);
define ('PERM_INCIDENT_VIEW', 61);
define ('PERM_INCIDENT_VIEW_ATTACHMENT', 62);
define ('PERM_RESELLER_ADD', 63);
define ('PERM_ESCALATION_MANAGE', 64);
define ('PERM_PRODUCT_DELETE', 65);
define ('PERM_DASHLET_INSTALL', 66);
define ('PERM_MANAGEMENT_REPORT_RUN', 67);
define ('PERM_HOLIDAY_MANAGE', 68);
define ('PERM_TASK_VIEW', 69);
define ('PERM_TASK_EDIT', 70);
define ('PERM_MYTRIGGERS_MANAGE', 71);
define ('PERM_TRIGGERS_MANAGE', 72); // system triggers
define ('PERM_INCIDENT_BILLING_APPROVE', 73);
define ('PERM_BILLING_DURATION_SET', 74); // Set duration without activity (for billable incidents)
define ('PERM_BILLING_DURATION_NEGATIVE', 75); // Set negative time for duration on incidents (for billable incidents - refunds
define ('PERM_BILLING_TRANSACTION_VIEW', 76);
define ('PERM_BILLING_VIEW', 77);
define ('PERM_NOTICE_POST', 78);
define ('PERM_SERVICE_BALANCE_EDIT', 79); // Edit service balances
define ('PERM_SERVICE_EDIT', 80);
define ('PERM_BILLING_DURATION_EDIT', 81); //Adjust durations on activities
define ('PERM_VIEW_SLA', 82); // View SLAs
define ('PERM_USER_DELETE', 83); // View SLAs
define ('PERM_SITE_TYPES', 84); // edit Site Types
define ('PERM_UNLINK_SKILLS_PRODUCTS', 85); // edit Site Types
// Any new permissions please use a number higher than 99, INL 2011-06-22
?>
