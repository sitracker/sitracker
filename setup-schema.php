<?php
// setup-schema.php - Defines database schema for use in setup.php
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>


// Important: When making changes to the schema you must add SQL to make the alterations
// to existing databases in $upgrade_schema[] at the bottom of the file
// *AND* you must also change $schema[] for new installations (at the top of the file)

// TODO we need to clean this schema up to make it confirmed compatible with mysql4

//the list of default triggers so we can drop all and recreate when we need to update the built-in ones
$default_triggers = "
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_CREATED', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_CREATED', 0, 'ACTION_EMAIL', 'EMAIL_INCIDENT_LOGGED_CONTACT', '', '{sendemail} == 1');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_ASSIGNED', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_NEARING_SLA', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_REVIEW_DUE', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_KB_CREATED', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_NEW_HELD_EMAIL', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_USER_CHANGED_STATUS', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_SIT_UPGRADED', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_LANGUAGE_DIFFERS', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_CONTACT_RESET_PASSWORD', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_USER_RESET_PASSWORD', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_NEW_CONTACT', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_CLOSED', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_CLOSED', 0, 'ACTION_EMAIL', 'EMAIL_INCIDENT_CLOSED_CONTACT', '', '( {notifycontact} == 1 ) AND ( {awaitingclosure} == 0 )');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_CONTACT_ADDED', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_CONTACT_ADDED', 0, 'ACTION_EMAIL', 'EMAIL_NEW_CONTACT_DETAILS', '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_NEW_CONTRACT', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_NEW_USER', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_NEW_SITE', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_HOLIDAY_REQUESTED', 0, 'ACTION_JOURNAL', 0, '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_ASSIGNED', 1, 'ACTION_NOTICE', 'NOTICE_INCIDENT_ASSIGNED', '', '{userid} == 1');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_SIT_UPGRADED', 1, 'ACTION_NOTICE', 'NOTICE_SIT_UPGRADED', '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_CLOSED', 1, 'ACTION_NOTICE', 'NOTICE_INCIDENT_CLOSED', '', '{userid} != 1');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_NEARING_SLA', 1, 'ACTION_NOTICE', 'NOTICE_INCIDENT_NEARING_SLA', '', '{ownerid} == 1 OR {townerid} == 1');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_LANGUAGE_DIFFERS', 1, 'ACTION_NOTICE', 'NOTICE_LANGUAGE_DIFFERS', '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_NEW_CONTACT', 0, 'ACTION_EMAIL', 'EMAIL_NEW_CONTACT_DETAILS', '', '{emaildetails} == 1');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_USER_RESET_PASSWORD', 0, 'ACTION_EMAIL', 'EMAIL_USER_RESET_PASSWORD', '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_CONTACT_RESET_PASSWORD', 0, 'ACTION_EMAIL', 'EMAIL_CONTACT_RESET_PASSWORD', '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_HOLIDAY_REQUESTED', 0, 'ACTION_EMAIL', 'EMAIL_HOLIDAYS_REQUESTED', '', '');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_CLOSED', 0, 'ACTION_EMAIL', 'EMAIL_INCIDENT_CLOSURE', '', '( {notifycontact} == 1 ) AND ( {awaitingclosure} == 1 )');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_CLOSED', 0, 'ACTION_EMAIL', 'EMAIL_EXTERNAL_INCIDENT_CLOSURE', '', '{notifyexternal} == 1');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_SERVICE_LIMIT' , 0, 'ACTION_EMAIL', 'EMAIL_SERVICE_LEVEL', '', '{serviceremaining} <= 0.2');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_SCHEDULER_TASK_FAILED', 1, 'ACTION_NOTICE', 'NOTICE_SCHEDULER_TASK_FAILED', '', '{schedulertask} == \'CheckIncomingMail\'');
INSERT INTO `{$dbTriggers}` (`triggerid`, `userid`, `action`, `template`, `parameters`, `checks`) VALUES('TRIGGER_INCIDENT_CLOSED', 0, 'ACTION_EMAIL', 'EMAIL_SEND_FEEDBACK', '', '{sendfeedback} == 1');
";

$schema = "
CREATE TABLE IF NOT EXISTS `{$dbSystem}` (
  `id` int(1) NOT NULL default '0',
  `version` float(3,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM DEFAULT CHARACTER SET = utf8;

-- NOTE system must be the first table created.

CREATE TABLE IF NOT EXISTS `{$dbBillingMatrix}` (
  `tag` varchar(32) NOT NULL,
  `hour` smallint(6) NOT NULL,
  `mon` float NOT NULL,
  `tue` float NOT NULL,
  `wed` float NOT NULL,
  `thu` float NOT NULL,
  `fri` float NOT NULL,
  `sat` float NOT NULL,
  `sun` float NOT NULL,
  `holiday` float NOT NULL,
  PRIMARY KEY  (`tag`,`hour`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbBillingMatrix}` (`id`, `hour`, `mon`, `tue`, `wed`, `thu`, `fri`, `sat`, `sun`, `holiday`) VALUES
('Default', 0, 2, 2, 2, 2, 2, 2, 2, 2),
('Default', 1, 2, 2, 2, 2, 2, 2, 2, 2),
('Default', 2, 2, 2, 2, 2, 2, 2, 2, 2),
('Default', 6, 2, 2, 2, 2, 2, 2, 2, 2),
('Default', 3, 2, 2, 2, 2, 2, 2, 2, 2),
('Default', 4, 2, 2, 2, 2, 2, 2, 2, 2),
('Default', 5, 2, 2, 2, 2, 2, 2, 2, 2),
('Default', 7, 2, 2, 2, 2, 2, 2, 2, 2),
('Default', 8, 1, 1, 1, 1, 1, 1.5, 2, 2),
('Default', 9, 1, 1, 1, 1, 1, 1.5, 2, 2),
('Default', 10, 1, 1, 1, 1, 1, 1.5, 2, 2),
('Default', 11, 1, 1, 1, 1, 1, 1.5, 2, 2),
('Default', 12, 1, 1, 1, 1, 1, 1.5, 2, 2),
('Default', 13, 1, 1, 1, 1, 1, 1.5, 2, 2),
('Default', 14, 1, 1, 1, 1, 1, 1.5, 2, 2),
('Default', 15, 1, 1, 1, 1, 1, 1.5, 2, 2),
('Default', 16, 1, 1, 1, 1, 1, 1.5, 2, 2),
('Default', 17, 1, 1, 1, 1, 1, 1.5, 2, 2),
('Default', 18, 1.5, 1.5, 1.5, 1.5, 1.5, 2, 2, 2),
('Default', 19, 1.5, 1.5, 1.5, 1.5, 1.5, 2, 2, 2),
('Default', 20, 1.5, 1.5, 1.5, 1.5, 1.5, 2, 2, 2),
('Default', 21, 1.5, 1.5, 1.5, 1.5, 1.5, 2, 2, 2),
('Default', 22, 2, 2, 2, 2, 2, 2, 2, 2),
('Default', 23, 2, 2, 2, 2, 2, 2, 2, 2);


CREATE TABLE `{$dbBillingPeriods}` (
`servicelevelid` INT( 5 ) NOT NULL ,
`engineerperiod` INT NOT NULL COMMENT 'In minutes',
`customerperiod` INT NOT NULL COMMENT 'In minutes',
`priority` INT( 4 ) NOT NULL,
`tag` VARCHAR( 10 ) NOT NULL,
`createdby` smallint(6) NULL ,
`modified` DATETIME NULL ,
`modifiedby` smallint(6) NULL ,
`limit` float NOT NULL default 0,
PRIMARY KEY ( `servicelevelid`,`priority` )
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbClosingStatus}` (
 `id` int(11) NOT NULL auto_increment,
 `name` varchar(50) default NULL,
 PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbClosingStatus}` VALUES (1, 'strSentInformation');
INSERT INTO `{$dbClosingStatus}` VALUES (2, 'strSolvedProblem');
INSERT INTO `{$dbClosingStatus}` VALUES (3, 'strReportedBug');
INSERT INTO `{$dbClosingStatus}` VALUES (4, 'strActionTaken');
INSERT INTO `{$dbClosingStatus}` VALUES (5, 'strDuplicate');
INSERT INTO `{$dbClosingStatus}` VALUES (6, 'strNoLongerRelevant');
INSERT INTO `{$dbClosingStatus}` VALUES (7, 'strUnsupported');
INSERT INTO `{$dbClosingStatus}` VALUES (8, 'strSupportExpired');
INSERT INTO `{$dbClosingStatus}` VALUES (9, 'strUnsolved');
INSERT INTO `{$dbClosingStatus}` VALUES (10, 'strEscalated');


CREATE TABLE IF NOT EXISTS `{$dbConfig}` (
  `config` varchar(255) NOT NULL,
  `value` text,
  PRIMARY KEY  (`config`)
) TYPE=MyISAM COMMENT='SiT configuration' DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbContacts}` (
`id` int(11) NOT NULL auto_increment,
  `notify_contactid` int(11) NOT NULL default '0',
  `username` varchar(50) default NULL,
  `password` varchar(50) default NULL,
  `forenames` varchar(100) NOT NULL default '',
  `surname` varchar(100) NOT NULL default '',
  `jobtitle` varchar(255) NOT NULL default '',
  `courtesytitle` varchar(50) NOT NULL default '',
  `siteid` int(11) NOT NULL default '0',
  `email` varchar(100) default NULL,
  `phone` varchar(50) default NULL,
  `mobile` varchar(50) NOT NULL default '',
  `fax` varchar(50) default NULL,
  `department` varchar(255) default NULL,
  `address1` varchar(255) default NULL,
  `address2` varchar(255) NOT NULL default '',
  `city` varchar(255) NOT NULL default '',
  `county` varchar(255) NOT NULL default '',
  `country` varchar(255) NOT NULL default '',
  `postcode` varchar(255) NOT NULL default '',
  `dataprotection_email` enum('No','Yes') default 'No',
  `dataprotection_phone` enum('No','Yes') default 'No',
  `dataprotection_address` enum('No','Yes') default 'No',
  `timestamp_added` int(11) default NULL,
  `timestamp_modified` int(11) default NULL,
  `notes` blob NOT NULL,
  `active` enum('true','false') NOT NULL default 'true',
  `created` datetime default NULL,
  `createdby` smallint(6) default NULL,
  `modified` datetime default NULL,
  `modifiedby` smallint(6) default NULL,
  `contact_source` varchar(32) NOT NULL default 'sit',
  PRIMARY KEY  (`id`),
  KEY `siteid` (`siteid`),
  KEY `username` (`username`),
  KEY `forenames` (`forenames`),
  KEY `surname` (`surname`),
  KEY `notify_contactid` (`notify_contactid`)
) TYPE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbDashboard}` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `version` mediumint(9) NOT NULL default '1',
  `enabled` enum('true','false') NOT NULL default 'false',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbDashboard}` (`id`, `name`, `enabled`) VALUES (1, 'random_tip', 'true'),
(2, 'statistics', 'true'),
(3, 'tasks', 'true'),
(4, 'user_incidents', 'true'),
(5, 'incoming', 'true'),
(6, 'rss', 'true'),
(7, 'watch_incidents', 'true'),
(8, 'holidays', 'true'),
(9, 'tags', 'true');


CREATE TABLE `{$dbDrafts}` (
  `id` int(11) NOT NULL auto_increment,
  `userid` smallint(6) NOT NULL,
  `incidentid` int(11) NOT NULL,
  `type` enum('update','email') NOT NULL,
  `content` text NOT NULL,
  `meta` text NOT NULL,
  `lastupdate` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbEmailSig}` (
  `id` int(11) NOT NULL auto_increment,
  `signature` text NOT NULL,
  `createdby` INT NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` INT NULL ,
  PRIMARY KEY  (`id`)
)  ENGINE=MyISAM COMMENT='Global Email Signature' DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbEmailSig}` (`id`, `signature`) VALUES (1, '--\r\n... Powered by Open Source Software: Support Incident Tracker (SiT!) is available free from http://sitracker.org/');


CREATE TABLE IF NOT EXISTS `{$dbEmailTemplates}` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `type` enum('usertemplate','system','contact','site','incident','kb','user') NOT NULL default 'user' COMMENT 'usertemplate is personal template owned by a user, user is a template relating to a user',
  `description` text NOT NULL,
  `tofield` varchar(100) default NULL,
  `fromfield` varchar(100) default NULL,
  `replytofield` varchar(100) default NULL,
  `ccfield` varchar(100) default NULL,
  `bccfield` varchar(100) default NULL,
  `subjectfield` varchar(255) default NULL,
  `body` text,
  `customervisibility` enum('show','hide') NOT NULL default 'show',
  `storeinlog` enum('No','Yes') NOT NULL default 'Yes',
  `created` datetime default NULL,
  `createdby` smallint(6) default NULL,
  `modified` datetime default NULL,
  `modifiedby` smallint(6) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('Support Email', 'incident', 'strSupportEmailDesc', '{contactemail}', '{supportemail}', '{supportemail}', '', '{triggeruseremail}', '[{incidentid}] - {incidenttitle}', 'Hi {contactfirstname},\r\n\r\n{signature}\r\n{globalsignature}', 'show', 'Yes', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_INCIDENT_CLOSURE', 'system', 'strEmailIncidentClosureDesc', '{contactemail}', '{supportemail}', '{supportemail}', '', '{triggeruseremail}', 'Closure Notification: [{incidentid}] - {incidenttitle}', '{contactfirstname},\r\n\r\nIncident {incidentid} has been marked for closure. If you still have outstanding issues relating to this incident then please reply with details, otherwise it will be closed in the next seven days.\r\n\r\n{signature}\r\n{globalsignature}', 'show', 'Yes', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_INCIDENT_LOGGED_CONTACT', 'system', 'strEmailIncidentLoggedContactDesc', '{contactemail}', '{supportemail}', '{supportemail}', '', '{triggeruseremail}', '[{incidentid}] - {incidenttitle}', 'Thank you for contacting us. The incident {incidentid} has been generated and your details stored in our tracking system. \r\n\r\nYou will be receiving a response from one of our product specialists as soon as possible. When referring to this incident please remember to quote incident {incidentid} in all communications. \r\n\r\nFor all email communications please title your email as [{incidentid}] - {incidenttitle}\r\n\r\n{globalsignature}\r\n', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_INCIDENT_OUT_OF_SLA', 'user', 'strEmailIncidentOutOfSlaDesc', '{supportmanager}', '{supportemail}', '{supportemail}', '{triggeruseremail}', '', '{applicationshortname}: Incident {incidentid} now outside SLA', 'This is an automatic notification that this incident has gone outside its SLA.  The SLA target nextsla expired {nextslatime} minutes ago.\r\n\r\nIncident: [{incidentid}] - {incidenttitle}\r\nOwner: {incidentowner}\r\nPriority: {incidentpriority}\r\nExternal Id: {incidentexternalid}\r\nExternal Engineer: {incidentexternalengineer}\r\nSite: {sitename}\r\nContact: {contactname}\r\n\r\nRegards\r\n{applicationname}\r\n\r\n\r\n---\r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_INCIDENT_OUT_OF_REVIEW', 'user', 'strEmailIncidentOutOfReviewWDesc', '{supportmanager}', '{supportemail}', '{supportemail}', '{triggeruseremail}', '', '{applicationshortname} Review: Incident {incidentid} due for review soon', 'This is an automatic notification that this incident [{incidentid}] will soon be due for review.\r\n\r\nIncident: [{incidentid}] - {incidenttitle}\r\nEngineer: {incidentowner}\r\nPriority: {incidentpriority}\r\nExternal Id: {incidentexternalid}\r\nExternal Engineer: {incidentexternalengineer}\r\nSite: {sitename}\r\nContact: {contactname}\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_INCIDENT_CREATED_USER', 'user', 'strEmailIncidentCreatedUserDesc', '{triggeruseremail}', '{supportemail}', '{supportemail}', '', '', '[{incidentid}] - {incidenttitle}', 'Hi,\r\n\r\nIncident [{incidentid}] {incidenttitle} has been logged.\r\n\r\nThe details of this incident are:\r\n\r\nPriority: {incidentpriority}\r\nContact: {contactname}\r\nSite: {sitename}\r\n\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_INCIDENT_REASSIGNED_USER_NOTIFY', 'user', 'strEmailIncidentReassignedUserNotifyDesc', '{triggeruseremail}', '{supportemail}', '{supportemail}', '', '', '{incidentpriority} priority call ([{incidentid}] - {incidenttitle}) has been reassigned to you', 'Hi,\r\n\r\nIncident [{incidentid}] entitled {incidenttitle} has been reassigned to you.\r\n\r\nThe details of this incident are:\r\n\r\nPriority: {incidentpriority}\r\nContact: {contactname}\r\nSite: {sitename}\r\n\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_INCIDENT_NEARING_SLA', 'user', 'strEmailIncidentNearingSlaDesc', '{supportmanageremail}', '{supportemail}', '{supportemail}', '{triggeruseremail}', '', '{applicationshortname} SLA: Incident {incidentid} about to breach SLA', 'This is an automatic notification that this incident is about to breach its SLA.  The SLA target {nextsla} will expire in {nextslatime} minutes.\r\n\r\nIncident: [{incidentid}] - {incidenttitle}\r\nOwner: {incidentowner}\r\nPriority: {incidentpriority}\r\nExternal Id: {incidentexternalid}\r\nExternal Engineer: {incidentexternalengineer}\r\nSite: {sitename}\r\nContact: {contactname}\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_CONTACT_RESET_PASSWORD', 'system', 'strEmailContactResetPasswordDesc', '{contactemail}', '{supportemail}', '{supportemail}', '', '', '{applicationshortname} - password reset', 'Hi {contactfirstname},\r\n\r\nThis is a email to reset your contact portal password for {applicationname}. If you did not request this, please ignore this email.\r\n\r\nTo complete your password reset please visit the following url:\r\n\r\n{passwordreseturl}\r\n\r\n\r\n{globalsignature}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_USER_RESET_PASSWORD', 'system', 'strEmailUserResetPasswordDesc', '{useremail}', '{supportemail}', '{supportemail}', '', '', '{applicationshortname} - password reset', 'Hi,\r\n\r\nThis is a email to reset your user account password for {applicationname}. If you did not request this, please ignore this email.\r\n\r\nTo complete your password reset please visit the following url:\r\n\r\n{passwordreseturl}\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_NEW_CONTACT_DETAILS', 'system', 'strEmailNewContactDetailsDesc', '{contactemail}', '{supportemail}', '', '', '', '{applicationshortname} - portal details', 'Hello {contactfirstname},\r\nYou have just been added as a contact on {applicationname} ({applicationurl}).\r\n\r\nThese details allow you to the login to the portal, where you can create, update and close your incidents, as well as view your sites\' incidents.\r\n\r\nYour details are as follows:\r\n\r\nusername: {contactusername}\r\npassword: {prepassword}\r\nPlease note, this password cannot be recovered, only reset. You may change it in the portal.\r\n\r\n{globalsignature}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_INCIDENT_REVIEW_DUE', 'system', 'strEmailIncidentReviewDueDesc', '{supportmanageremail}', '{supportemail}', '{supportemail}', NULL, NULL, '{applicationshortname}: review due', 'Hi,\r\n\r\nThe review for incident {incidentid} - {incidenttitle} is now due for review.\r\n\r\nYou can view the incident at {applicationurl}incident_details.php?id={incidentid}\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_KB_ARTICLE_CREATED', 'user', 'strEmailKbArticleCreatedDesc', '{triggeruseremail}', '{supportemail}', '{supportemail}', NULL, NULL, '{applicationshortname}: {kbid} KB article created', 'Hi,\r\n\r\nKB article {kbprefix}{kbid} - {kbtitle} has been created by {userrealname}. You can view it at {applicationurl}kb_article.php?id={kbid} : \r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_HELD_EMAIL_RECEIVED', 'user', 'strEmailHeldEmailReceivedDesc', '{triggeruseremail}', '{supportemail}', '{supportemail}', NULL, NULL, 'New held email', 'Hi,\r\n\r\nThere\'s a new email in the holding queue. You can view it at: {applicationurl}holding_queue.php\r\n\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_HELD_EMAIL_MINS', 'user', 'strEmailHeldEmailMinsDesc', '{triggeruseremail}', '{supportemail}', '{supportemail}', NULL, NULL, 'New held email', 'Hi,\r\n\r\nThere\'s been an email in the holding queue for {holdingemailmins}. You can view it at {applicationurl}holding_queue.php\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_USER_CHANGED_STATUS', 'user', 'strEmailUserChangedStatusDesc', '{triggeruseremail}', '{supportemail}', '{supportemail}', NULL, NULL, '{watcheduserrealname} has changed status', 'Hi,\r\n\r\n{userrealname} has set their status to {userstatus} and is {useraccepting} incidents.\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_SIT_UPGRADED', 'user', 'strEmailSitUpgradedDesc', '{triggeruseremail}', '{supportemail}', '{supportemail}', NULL, NULL, '{applicationshortname} upgraded', 'Hi,\r\n\r\n{applicationshortname} has been upgraded to {applicationversion}. You can view the changelog at {applicationurl}releasenotes.php?v={applicationversion}\r\n\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_CONTACT_CREATED', 'system', 'strEmailContactCreatedDesc', '{triggeruseremail}', '{supportemail}', '{supportemail}', NULL, NULL, 'New contact added', 'Hi,\r\n\r\n{contactname} has been added as a contact to {sitename} by {userealname}.\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_INCIDENT_CLOSED_CONTACT', 'system', 'strEmailIncidentClosedContactDesc', '{contactemail}', '{supportemail}', '{supportemail}', NULL, NULL, '[{incidentid}] - {incidenttitle} - Closed', 'Hi {contactfirstname},\r\n\r\nIncident {incidentid} has now been closed. \r\n\r\n\r\n{globalsignature}', 'show', 'Yes', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_INCIDENT_CLOSED_USER', 'user', 'strEmailIncidentClosedUserDesc', '{triggeruseremail}', '{supportemail}', '{supportemail}', NULL, NULL, '[{incidentid}] - {incidenttitle} - Closed', 'Hi,\r\n\r\nIncident {incidentid} has now been closed.\r\n\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'show', 'Yes', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_CONTRACT_ADDED', 'user', 'strEmailContractAddedDesc', '{triggeruseremail}', '{supportemail}', '{supportemail}', NULL, NULL, 'New contract added to {sitename}', 'Hi,\r\n\r\nA new {contractproduct} contract ID{contractid} has been added to {sitename} by {userealname}. You can view it at {applicationurl}contract_details.php?id={contractid}\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_USER_CREATED', 'user', 'strEmailUserCreatedDesc', '{triggeruseremail}', '{supportemail}', '{supportemail}', NULL, NULL, 'New user {userrealname} added', 'Hi,\r\n\r\n{userrealname} has just been added as a new user to the {usergroup} group.\r\n\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_SITE_CREATED', 'user', 'strEmailSiteCreatedDesc', '{triggeruseremail}', '{supportemail}', '{supportemail}', NULL, NULL, 'New site {sitename} added', 'Hi,\r\n\r\n{sitename} has just been added by {userrealname}. The admin contact is {admincontact}.\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_EXTERNAL_INCIDENT_CLOSURE', 'system', 'strEmailExternalIncidentClosureDesc', '{incidentexternalemail}', '{supportemail}', '{supportemail}', NULL, NULL, 'Service Request #{incidentexternalid}  - {incidenttitle} CLOSED - [{incidentid}]', 'Hi {incidentexternalengineerfirstname},\r\n\r\nThis is an automatic email generated from {applicationname}, our call tracking system.\r\n\r\nIncident {incidentexternalid} has been closed.\r\n\r\nMany thanks for your help.\r\n\r\n{signature}\r\n{globalsignature}', 'show', 'Yes', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_HOLIDAYS_REQUESTED', 'system', 'strEmailHolidaysRequestedDesc', '{approvaluseremail}', '{supportemail}', '{supportemail}', NULL, NULL, '{applicationshortname}: Holiday approval request', 'Hi,\r\n\r\n{userrealname} has requested that you approve the following holidays:\r\n\r\n{listofholidays}\r\n\r\nPlease point your browser to {applicationurl}holiday_request.php?user={userid}&mode=approval to approve or decline these requests.\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_SERVICE_LEVEL', 'system', 'strEmailServiceLevelDesc', '{salespersonemail}', '{supportemail}', '{supportemail}', NULL, NULL, '{sitename}\'s service credit low', 'Hi, {sitename}''s total service credit is now standing at {serviceremainingstring}.\r\n\r\nRegards\r\n{applicationname}\r\n\r\n-- \r\n{todaysdate} - {applicationshortname} {applicationversion}\r\n{globalsignature}\r\n{triggersfooter}', 'show', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_INCIDENT_UPDATED_CUSTOMER', 'system', 'strEmailIncidentUpdatedCustomerDesc', '{contactemail}', '{supportemail}', '{supportemail}', '', '', '{applicationshortname} [{incidentid}] - {incidenttitle} updated', 'Hi {contactfirstname},\r\n\r\nYour incident [{incidentid}] - {incidentid} has been updated, please log into the portal to view the update and respond.\r\n \r\nDO NOT respond to this e-mail directly, use the portal for your responses.\r\n\r\nLog into the portal at: {applicationurl}, where you can also reset your details if you do not know them.\r\n\r\nRegards,\r\n{signature}\r\n\r\n{globalsignature}', 'hide', 'No', NULL, NULL, NULL, NULL);
INSERT INTO `{$dbEmailTemplates}` (`name`, `type`, `description`, `tofield`, `fromfield`, `replytofield`, `ccfield`, `bccfield`, `subjectfield`, `body`, `customervisibility`, `storeinlog`, `created`, `createdby`, `modified`, `modifiedby`) VALUES('EMAIL_SEND_FEEDBACK', 'system', 'strEmailSendFeedbackDesc', '{contactemail}', '{supportemail}', '{supportemail}', '', '', '{applicationshortname} [{incidentid}] - {incidenttitle}: feedback requested', 'Hi {contactfirstname},\r\n\r\nWe would very much value your feedback relating to Incident #{incidentid} - {incidenttitle}.\r\n \r\nDO NOT respond to this e-mail directly, use the portal for your responses.\r\n\r\nPlease visit the following URL to complete our short questionnaire.\r\n\r\n{feedbackurl}\r\n\r\nRegards,\r\n{signature}\r\n\r\n{globalsignature}', 'hide', 'No', NULL, NULL, NULL, NULL);


CREATE TABLE `{$dbEscalationPaths}` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `track_url` varchar(255) default NULL,
  `home_url` varchar(255) NOT NULL default '',
  `url_title` varchar(255) default NULL,
  `email_domain` varchar(255) default NULL,
  `createdby` INT NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` INT NULL ,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbFeedbackForms}` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `introduction` text NOT NULL,
  `thanks` text NOT NULL,
  `description` text NOT NULL,
  `multi` enum('yes','no') NOT NULL default 'no',
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  KEY `multi` (`multi`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbFeedbackQuestions}` (
  `id` int(5) NOT NULL auto_increment,
  `formid` int(5) NOT NULL default '0',
  `question` varchar(255) NOT NULL default '',
  `questiontext` text NOT NULL,
  `sectiontext` text NOT NULL,
  `taborder` int(5) NOT NULL default '0',
  `type` varchar(255) NOT NULL default 'text',
  `required` enum('true','false') NOT NULL default 'false',
  `options` text NOT NULL,
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  KEY `taborder` (`taborder`),
  KEY `type` (`type`),
  KEY `formid` (`formid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbFeedbackReport}` (
  `id` int(5) NOT NULL default '0',
  `formid` int(5) NOT NULL default '0',
  `respondent` int(11) NOT NULL default '0',
  `responseref` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `completed` enum('yes','no') NOT NULL default 'no',
  `created` timestamp(14) NOT NULL,
  `incidentid` int(5) NOT NULL default '0',
  `contactid` int(5) NOT NULL default '0',
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  KEY `responseref` (`responseref`),
  KEY `formid` (`formid`),
  KEY `respondant` (`respondent`),
  KEY `completed` (`completed`),
  KEY `incidentid` (`incidentid`),
  KEY `contactid` (`contactid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbFeedbackRespondents}` (
  `id` int(5) NOT NULL auto_increment,
  `formid` int(5) NOT NULL default '0',
  `contactid` int(11) NOT NULL default '0',
  `incidentid` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `completed` enum('yes','no') NOT NULL default 'no',
  `created` timestamp(14) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `responseref` (`incidentid`),
  KEY `formid` (`formid`),
  KEY `contactid` (`contactid`),
  KEY `completed` (`completed`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

CREATE TABLE `{$dbFeedbackResults}` (
  `id` int(5) NOT NULL auto_increment,
  `respondentid` int(5) NOT NULL default '0',
  `questionid` int(5) NOT NULL default '0',
  `result` varchar(255) NOT NULL default '',
  `resulttext` text,
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  KEY `questionid` (`questionid`),
  KEY `respondentid` (`respondentid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbFiles}` (
  `id` int(11) NOT NULL auto_increment,
  `category` enum('public','private','protected','ftp') NOT NULL default 'public',
  `filename` varchar(255) NULL default '',
  `size` bigint(11) NOT NULL default '0',
  `userid` int(11) NOT NULL default '0',
  `usertype` ENUM( 'user', 'contact' ) NOT NULL DEFAULT 'user',
  `shortdescription` varchar(255) NULL default '',
  `longdescription` TEXT NULL,
  `webcategory` varchar(255) NULL default '',
  `path` varchar(255) NULL default '',
  `downloads` int(11) NOT NULL default '0',
  `filedate` DATETIME NOT NULL,
  `expiry` DATETIME NULL,
  `fileversion` varchar(50) NULL default '',
  `published` enum('yes','no') NOT NULL default 'no',
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`),
  KEY `category` (`category`),
  KEY `filename` (`filename`),
  KEY `published` (`published`),
  KEY `webcategory` (`webcategory`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbGroups}` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `imageurl` varchar(255) NOT NULL default '',
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM COMMENT='List of user groups' DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbHolidays}` (
  `id` int(11) NOT NULL auto_increment,
  `userid` smallint(6) NOT NULL default '0',
  `type` int(11) NOT NULL default '1',
  `length` enum('am','pm','day') NOT NULL default 'day',
  `approved` tinyint(1) NOT NULL default '0',
  `approvedby` smallint(6) NOT NULL default '0',
  `date` DATE NULL,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`),
  KEY `type` (`type`),
  KEY `approved` (`approved`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbIncidentPools}` (
  `id` int(11) NOT NULL auto_increment,
  `maintenanceid` int(11) NOT NULL default '0',
  `siteid` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `incidentsremaining` int(5) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `maintenanceid` (`maintenanceid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbIncidentProductInfo}` (
  `id` int(11) NOT NULL auto_increment,
  `incidentid` int(11) default NULL,
  `productinfoid` int(11) default NULL,
  `information` text,
   `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbIncidents}` (
  `id` int(11) NOT NULL auto_increment,
  `escalationpath` int(11) default NULL,
  `externalid` varchar(50) default NULL,
  `externalengineer` varchar(80) NOT NULL default '',
  `externalemail` varchar(255) NOT NULL default '',
  `ccemail` varchar(255) default NULL,
  `title` varchar(150) default NULL,
  `owner` smallint(6) default NULL,
  `towner` smallint(6) NOT NULL default '0',
  `contact` int(11) default '0',
  `priority` tinyint(4) default NULL,
  `servicelevel` varchar(10) default NULL,
  `status` tinyint(4) default NULL,
  `type` enum('Support','Sales','Other','Free') default 'Support',
  `maintenanceid` int(11) NOT NULL default '0',
  `product` int(11) default NULL,
  `softwareid` int(5) NOT NULL default '0',
  `productversion` varchar(50) default NULL,
  `productservicepacks` varchar(100) default NULL,
  `opened` int(11) default NULL,
  `lastupdated` int(11) default NULL,
  `timeofnextaction` int(11) default '0',
  `closed` int(11) default '0',
  `closingstatus` tinyint(4) default NULL,
  `slaemail` tinyint(1) NOT NULL default '0',
  `slanotice` tinyint(1) NOT NULL default '0',
  `locked` tinyint(4) NOT NULL default '0',
  `locktime` int(11) NOT NULL default '0',
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  KEY `type` (`type`),
  KEY `owner` (`owner`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `timeofnextaction` (`timeofnextaction`),
  KEY `maintenanceid` (`maintenanceid`),
  KEY `softwareid` (`softwareid`),
  KEY `contact` (`contact`),
  KEY `title` (`title`),
  KEY `opened` (`opened`),
  KEY `closed` (`closed`),
  KEY `servicelevel` (`servicelevel`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbIncidentStatus}` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  `ext_name` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


INSERT INTO `{$dbIncidentStatus}` VALUES (1, 'strActive', 'strActive');
INSERT INTO `{$dbIncidentStatus}` VALUES (2, 'strClosed', 'strClosed');
INSERT INTO `{$dbIncidentStatus}` VALUES (3, 'strResearchNeeded', 'strResearching');
INSERT INTO `{$dbIncidentStatus}` VALUES (4, 'strCalledAndLeftMessage', 'strCalledAndLeftMessage');
INSERT INTO `{$dbIncidentStatus}` VALUES (5, 'strAwaitingColleagueResponse', 'strInternalEscalation');
INSERT INTO `{$dbIncidentStatus}` VALUES (6, 'strAwaitingSupportResponse', 'strExternalEscalation');
INSERT INTO `{$dbIncidentStatus}` VALUES (7, 'strAwaitingClosure', 'strAwaitingClosure');
INSERT INTO `{$dbIncidentStatus}` VALUES (8, 'strAwaitingCustomerAction', 'strCustomerHasAction');
INSERT INTO `{$dbIncidentStatus}` VALUES (9, 'strUnsupported', 'strUnsupported');
INSERT INTO `{$dbIncidentStatus}` VALUES (10, 'strActiveUnassigned', 'strActive');


CREATE TABLE `{$dbInventory}` (
  `id` int(11) NOT NULL auto_increment,
  `identifier` varchar(255) default NULL,
  `name` varchar(255) NOT NULL,
  `siteid` int(11) NOT NULL,
  `contactid` int(11) NOT NULL,
  `address` varchar(255) NOT NULL,
  `url` varchar(255) default NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notes` text,
  `createdby` smallint(6) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  `modifiedby` smallint(6) NOT NULL,
  `active` tinyint(1) NOT NULL default '1',
  `privacy` enum('none','adminonly','private') NOT NULL default 'none',
  PRIMARY KEY  (`id`),
  KEY `siteid` (`siteid`,`contactid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbJournal}` (
  `id` int(11) NOT NULL auto_increment,
  `userid` smallint(6) NOT NULL default '0',
  `timestamp` timestamp(14) NOT NULL,
  `event` varchar(40) NOT NULL default '',
  `bodytext` text NOT NULL,
  `journaltype` int(11) NOT NULL default '0',
  `refid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `refid` (`refid`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbKBArticles}` (
  `docid` int(5) NOT NULL auto_increment,
  `doctype` int(5) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `distribution` ENUM( 'public', 'private', 'restricted' ) NOT NULL DEFAULT 'public'
  COMMENT 'public appears in the portal, private is info never to be released to the public,
  restricted is info that is sensitive but could be mentioned if asked, for example' ,
  `published` datetime NOT NULL default '0000-00-00 00:00:00',
  `author` varchar(255) NOT NULL default '',
  `reviewed` datetime NOT NULL default '0000-00-00 00:00:00',
  `reviewer` smallint(6) NOT NULL default '0',
  `keywords` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`docid`),
  KEY `distribution` (`distribution`),
  KEY `title` (`title`)
) ENGINE=MyISAM COMMENT='Knowledge base articles' DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbKBContent}` (
  `docid` int(5) NOT NULL default '0',
  `id` int(7) NOT NULL auto_increment,
  `ownerid` smallint(6) NOT NULL default '0',
  `headerstyle` char(2) NOT NULL default 'h1',
  `header` varchar(255) NOT NULL default '',
  `contenttype` int(5) NOT NULL default '1',
  `content` mediumtext NOT NULL,
  `distribution` enum('public','private','restricted') NOT NULL default 'private',
  PRIMARY KEY  (`id`),
  KEY `distribution` (`distribution`),
  KEY `ownerid` (`ownerid`),
  KEY `docid` (`docid`),
  FULLTEXT KEY `c_index` (`content`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbKBSoftware}` (
  `docid` int(5) NOT NULL default '0',
  `softwareid` int(5) NOT NULL default '0',
  PRIMARY KEY  (`docid`,`softwareid`)
) ENGINE=MyISAM COMMENT='Links kb articles with software' DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbLicenceTypes}` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbLicenceTypes}` VALUES (1, 'Per User');
INSERT INTO `{$dbLicenceTypes}` VALUES (2, 'Per Workstation');
INSERT INTO `{$dbLicenceTypes}` VALUES (3, 'Per Server');
INSERT INTO `{$dbLicenceTypes}` VALUES (4, 'Site');
INSERT INTO `{$dbLicenceTypes}` VALUES (5, 'Evaluation');


CREATE TABLE `{$dbLinks}` (
     `linktype` int(11) NOT NULL default '0',
     `origcolref` int(11) NOT NULL default '0',
     `linkcolref` int(11) NOT NULL default '0',
     `direction` enum('left','right','bi') NOT NULL default 'left',
     `userid` smallint(6) NOT NULL default '0',
     PRIMARY KEY  (`linktype`,`origcolref`,`linkcolref`),
     KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbLinkTypes}` (
     `id` int(11) NOT NULL auto_increment,
     `name` varchar(255) NOT NULL default '',
     `lrname` varchar(255) NOT NULL default '',
     `rlname` varchar(255) NOT NULL default '',
     `origtab` varchar(255) NOT NULL default '',
     `origcol` varchar(255) NOT NULL default '',
     `linktab` varchar(255) NOT NULL default '',
     `linkcol` varchar(255) NOT NULL default 'id',
     `selectionsql` varchar(255) NOT NULL default '',
     `filtersql` varchar(255) NOT NULL default '',
     `viewurl` varchar(255) NOT NULL default '',
     PRIMARY KEY  (`id`),
     KEY `origtab` (`origtab`),
     KEY `linktab` (`linktab`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbLinkTypes}`
VALUES (1,'Task','Subtask','Parent Task','tasks','id','tasks','id','name','','view_task.php?id=%id%'),
(2,'Contact','Contact','Contact Task','tasks','id','contacts','id','CONCAT(forenames, \" \", surname)','','contact_details.php?id=%id%'),
(3,'Site','Site','Site Task','tasks','id','sites','id','name','','site_details.php?id=%id%'),
(4,'Incident','Incident','Task','incidents','id','tasks','id','title','','incident_details.php?id=%id%'),
(5,'Attachments', 'Update', 'File', 'updates', 'id', 'files', 'id', 'filename', '', 'incident_details.php?updateid=%id%&tab=files'),
(6, 'Incident', 'Transaction', 'Incidents', 'transactions', 'transactionid', 'incidents', 'id', '', '', '');


CREATE TABLE `{$dbMaintenance}` (
  `id` int(11) NOT NULL auto_increment,
  `site` int(11) default NULL,
  `product` int(11) default NULL,
  `reseller` int(11) default NULL,
  `expirydate` int(11) default NULL,
  `licence_quantity` int(11) default NULL,
  `licence_type` int(11) default NULL,
  `incident_quantity` int(5) NOT NULL default '0',
  `incidents_used` int(5) NOT NULL default '0',
  `notes` text,
  `admincontact` int(11) default NULL,
  `productonly` enum('yes','no') NOT NULL default 'no',
  `term` enum('no','yes') default 'no',
  `servicelevelid` int(11) NOT NULL default '1',
  `incidentpoolid` int(11) NOT NULL default '0',
  `supportedcontacts` INT( 255 ) NOT NULL DEFAULT '0',
  `allcontactssupported` ENUM( 'no', 'yes' ) NOT NULL DEFAULT 'no',
  `var_incident_visible_contacts` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no',
  `var_incident_visible_all` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no',
  PRIMARY KEY  (`id`),
  KEY `site` (`site`),
  KEY `productonly` (`productonly`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

CREATE TABLE `{$dbNotes}` (
  `id` int(11) NOT NULL auto_increment,
  `userid` smallint(6) NOT NULL default '0',
  `timestamp` timestamp(14) NOT NULL,
  `bodytext` text NOT NULL,
  `link` int(11) NOT NULL default '0',
  `refid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `refid` (`refid`),
  KEY `userid` (`userid`),
  KEY `link` (`link`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbNotices}` (
  `id` int(11) NOT NULL auto_increment,
  `userid` smallint(6) NOT NULL,
  `template` varchar(255) NULL,
  `type` tinyint(4) NOT NULL,
  `text` tinytext NOT NULL,
  `linktext` varchar(50) default NULL,
  `link` varchar(100) NOT NULL,
  `referenceid` int(11) default NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `durability` enum('sticky','session') NOT NULL default 'sticky',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARACTER SET = utf8;


CREATE TABLE IF NOT EXISTS `{$dbNoticeTemplates}` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `description` varchar(255) NOT NULL,
  `text` tinytext NOT NULL,
  `linktext` varchar(50) default NULL,
  `link` varchar(100) default NULL,
  `durability` enum('sticky','session') NOT NULL default 'sticky',
  `refid` varchar(255) NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_INCIDENT_CREATED', 3, 'strNoticeIncidentCreatedDesc', 'strNoticeIncidentCreated', 'strViewIncident', 'javascript:incident_details_window({incidentid})', 'sticky', '{incidentid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_INCIDENT_ASSIGNED', 3, 'strNoticeIncidentAssignedDesc', 'strNoticeIncidentAssigned', 'strViewIncident', 'javascript:incident_details_window({incidentid})', 'sticky', '{incidentid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_INCIDENT_NEARING_SLA', 3, 'strNoticeIncidentNearingSLADesc', 'strNoticeIncidentNearingSLA', 'strViewIncident', 'javascript:incident_details_window({incidentid})', 'sticky','{incidentid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_LANGUAGE_DIFFERS', 3, 'strNoticeLanguageDiffersDesc', 'strNoticeLanguageDiffers', 'strKeepCurrentLanguage', '{applicationurl}user_profile_edit.php?mode=savesessionlang', 'session', '{currentlang}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_NEW_CONTACT', 3, 'strNoticeNewContactDesc', 'strNoticeNewContact', 'strViewContact', '{applicationurl}contact_details.php?id={contactid}', 'sticky','{contactid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_INCIDENT_REVIEW_DUE', 3, 'strNoticeIncidentReviewDueDesc', 'strNoticeIncidentReviewDue', 'strViewIncident', 'javascript:incident_details_window({incidentid})', 'sticky', '{incidentid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_KB_CREATED', 3, 'strNoticeKBCreatedDesc', 'strNoticeKBCreated', 'strViewArticle', '{applicationurl}kb_view_article.php?id={kbid}', 'sticky', '{kbid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_NEW_HELD_EMAIL', 3, 'strNoticeNewHeldEmailDesc', 'strNoticeNewHeldEmail', 'strViewHoldingQueue', '{applicationurl}holding_queue.php', 'sticky', '{holdingemailid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_MINS_HELD_EMAIL', 3, 'strNoticeMinsHeldEmailDesc', 'strNoticeMinsHeldEmail', 'strViewHoldingQueue', '{applicationurl}holding_queue.php', 'sticky', '{holdingemailid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_SIT_UPGRADED', 3, 'strNoticeSitUpgradedDesc', 'strNoticeSitUpgraded', 'strWhatsNew', '{applicationurl}releasenotes.php?v={applicationversion}', 'sticky', '{applicationversion}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_USER_CHANGED_STATUS', 3, 'strNoticeUserChangedStatusDesc', 'strNoticeUserChangedStatus', NULL, '', 'sticky', '{userid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_NEW_USER', 3, 'strNoticeNewUserDesc', 'strNoticeNewUser', NULL, NULL, 'sticky', '{userid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_INCIDENT_CLOSED', 3, 'strNoticeIncidentClosedDesc', 'strNoticeIncidentClosed', NULL, NULL, 'sticky', '{incidentid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_NEW_CONTRACT', 3, 'strNoticeNewContractDesc', 'strNoticeNewContract', 'strViewContract', '{applicationurl}contract_details.php?id={contractid}', 'sticky', '{contractid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_NEW_SITE', 3, 'strNoticeNewSiteDesc', 'strNoticeNewSite', 'strViewSite', '{applicationurl}site_details.php?id={siteid}', 'sticky', '{siteid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_TASK_DUE', 3, 'strNoticeTaskDueDesc', 'strNoticeTaskDue', 'strViewTask', '{applicationurl}view_task.php?id={taskid}', 'sticky', '{taskid}');
INSERT INTO `{$dbNoticeTemplates}` (`name`, `type`, `description`, `text`, `linktext`, `link`, `durability`, `refid`) VALUES('NOTICE_SCHEDULER_TASK_FAILED', 3, 'strNoticeSchedulerTaskFailedDesc', 'strNoticeSchedulerTaskFailed', '', '', 'sticky', '');


CREATE TABLE IF NOT EXISTS `{$dbPermissions}` (
  `id` int(5) NOT NULL auto_increment,
  `categoryid` int(5) NOT NULL,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `categoryid` (`categoryid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


INSERT INTO `{$dbPermissions}` VALUES(1, 2, 'Add new contacts');
INSERT INTO `{$dbPermissions}` VALUES(2, 2, 'Add new sites');
INSERT INTO `{$dbPermissions}` VALUES(3, 2, 'Edit existing site details');
INSERT INTO `{$dbPermissions}` VALUES(4, 7, 'Edit your profile');
INSERT INTO `{$dbPermissions}` VALUES(5, 1, 'Add Incidents');
INSERT INTO `{$dbPermissions}` VALUES(6, 1, 'View Incidents');
INSERT INTO `{$dbPermissions}` VALUES(7, 1, 'Edit Incidents');
INSERT INTO `{$dbPermissions}` VALUES(8, 1, 'Update Incidents');
INSERT INTO `{$dbPermissions}` VALUES(9, 7, 'Edit User Permissions');
INSERT INTO `{$dbPermissions}` VALUES(10, 2, 'Edit contacts');
INSERT INTO `{$dbPermissions}` VALUES(11, 2, 'View Sites');
INSERT INTO `{$dbPermissions}` VALUES(12, 2, 'View Contacts');
INSERT INTO `{$dbPermissions}` VALUES(13, 1, 'Reassign Incidents');
INSERT INTO `{$dbPermissions}` VALUES(14, 11, 'View Users');
INSERT INTO `{$dbPermissions}` VALUES(15, 3, 'Add Supported Products');
INSERT INTO `{$dbPermissions}` VALUES(16, 7, 'Add Templates');
INSERT INTO `{$dbPermissions}` VALUES(17, 7, 'Edit Templates');
INSERT INTO `{$dbPermissions}` VALUES(18, 1, 'Close Incidents');
INSERT INTO `{$dbPermissions}` VALUES(19, 3, 'View Maintenance Contracts');
INSERT INTO `{$dbPermissions}` VALUES(20, 7, 'Add Users');
INSERT INTO `{$dbPermissions}` VALUES(21, 3, 'Edit Maintenance Contracts');
INSERT INTO `{$dbPermissions}` VALUES(22, 7, 'Administrate');
INSERT INTO `{$dbPermissions}` VALUES(23, 7, 'Edit User');
INSERT INTO `{$dbPermissions}` VALUES(24, 3, 'Add Product');
INSERT INTO `{$dbPermissions}` VALUES(25, 3, 'Add Product Information');
INSERT INTO `{$dbPermissions}` VALUES(26, 11, 'Get Help');
INSERT INTO `{$dbPermissions}` VALUES(27, 10, 'View Your Calendar');
INSERT INTO `{$dbPermissions}` VALUES(28, 3, 'View Products and Software');
INSERT INTO `{$dbPermissions}` VALUES(29, 3, 'Edit Products');
INSERT INTO `{$dbPermissions}` VALUES(30, 3, 'View Supported Products');
INSERT INTO `{$dbPermissions}` VALUES(32, 3, 'Edit Supported Products');
INSERT INTO `{$dbPermissions}` VALUES(33, 11, 'Send Emails');
INSERT INTO `{$dbPermissions}` VALUES(34, 1, 'Reopen Incidents');
INSERT INTO `{$dbPermissions}` VALUES(35, 11, 'Set your status');
INSERT INTO `{$dbPermissions}` VALUES(36, 2, 'Set contact flags');
INSERT INTO `{$dbPermissions}` VALUES(37, 9, 'Run Reports');
INSERT INTO `{$dbPermissions}` VALUES(38, 1, 'View Sales Incidents');
INSERT INTO `{$dbPermissions}` VALUES(39, 3, 'Add Maintenance Contract');
INSERT INTO `{$dbPermissions}` VALUES(40, 1, 'Reassign Incident when user not accepting');
INSERT INTO `{$dbPermissions}` VALUES(41, 11, 'View Status');
INSERT INTO `{$dbPermissions}` VALUES(42, 1, 'Review/Delete Incident updates');
INSERT INTO `{$dbPermissions}` VALUES(43, 7, 'Edit Global Signature');
INSERT INTO `{$dbPermissions}` VALUES(44, 11, 'Publish files to FTP site');
INSERT INTO `{$dbPermissions}` VALUES(45, 11, 'View Mailing List Subscriptions');
INSERT INTO `{$dbPermissions}` VALUES(46, 11, 'Edit Mailing List Subscriptions');
INSERT INTO `{$dbPermissions}` VALUES(47, 11, 'Administrate Mailing Lists');
INSERT INTO `{$dbPermissions}` VALUES(48, 7, 'Add Feedback Forms');
INSERT INTO `{$dbPermissions}` VALUES(49, 7, 'Edit Feedback Forms');
INSERT INTO `{$dbPermissions}` VALUES(50, 10, 'Approve Holidays');
INSERT INTO `{$dbPermissions}` VALUES(51, 1, 'View Feedback');
INSERT INTO `{$dbPermissions}` VALUES(52, 1, 'View Hidden Updates');
INSERT INTO `{$dbPermissions}` VALUES(53, 7, 'Edit Service Levels');
INSERT INTO `{$dbPermissions}` VALUES(54, 5, 'View KB Articles');
INSERT INTO `{$dbPermissions}` VALUES(55, 2, 'Delete Sites/Contacts');
INSERT INTO `{$dbPermissions}` VALUES(56, 3, 'Add Software');
INSERT INTO `{$dbPermissions}` VALUES(57, 7, 'Disable User Accounts');
INSERT INTO `{$dbPermissions}` VALUES(58, 7, 'Edit your Software Skills');
INSERT INTO `{$dbPermissions}` VALUES(59, 7, 'Manage users software skills');
INSERT INTO `{$dbPermissions}` VALUES(60, 11, 'Perform Searches');
INSERT INTO `{$dbPermissions}` VALUES(61, 1, 'View Incident Details');
INSERT INTO `{$dbPermissions}` VALUES(62, 1, 'View Incident Attachments');
INSERT INTO `{$dbPermissions}` VALUES(63, 3, 'Add Reseller');
INSERT INTO `{$dbPermissions}` VALUES(64, 7, 'Manage Escalation Paths');
INSERT INTO `{$dbPermissions}` VALUES(65, 3, 'Delete Products');
INSERT INTO `{$dbPermissions}` VALUES(66, 7, 'Install Dashboard Components');
INSERT INTO `{$dbPermissions}` VALUES(67, 9, 'Run Management Reports');
INSERT INTO `{$dbPermissions}` VALUES(68, 10, 'Manage Holidays');
INSERT INTO `{$dbPermissions}` VALUES(69, 4, 'View your Tasks');
INSERT INTO `{$dbPermissions}` VALUES(70, 4, 'Create/Edit your Tasks');
INSERT INTO `{$dbPermissions}` VALUES(71, 7, 'Manage your Triggers');
INSERT INTO `{$dbPermissions}` VALUES(72, 7, 'Manage System Triggers');
INSERT INTO `{$dbPermissions}` VALUES(73, 8, 'Approve Billable Incidents');
INSERT INTO `{$dbPermissions}` VALUES(74, 8, 'Set duration without activity (for billable incidents)');
INSERT INTO `{$dbPermissions}` VALUES(75, 8, 'Set negative time for duration on incidents (for billable incidents - refunds)');
INSERT INTO `{$dbPermissions}` VALUES(76, 8, 'View Transactions');
INSERT INTO `{$dbPermissions}` VALUES(77, 8, 'View Billing Information');
INSERT INTO `{$dbPermissions}` VALUES(78, 11, 'Post System Notices');
INSERT INTO `{$dbPermissions}` VALUES(79, 8, 'Edit Service Balances');
INSERT INTO `{$dbPermissions}` VALUES(80, 8, 'Edit Service Details');
INSERT INTO `{$dbPermissions}` VALUES(81, 8, 'Adjust durations on activities');


CREATE TABLE `{$dbPermissionCategories}` (
`id` INT( 5 ) NOT NULL AUTO_INCREMENT ,
`category` VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( `id` )
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


INSERT INTO `{$dbPermissionCategories}` (`id`, `category`) VALUES(1, 'strSupport');
INSERT INTO `{$dbPermissionCategories}` (`id`, `category`) VALUES(2, 'strCustomers');
INSERT INTO `{$dbPermissionCategories}` (`id`, `category`) VALUES(3, 'strContracts');
INSERT INTO `{$dbPermissionCategories}` (`id`, `category`) VALUES(4, 'strTasks');
INSERT INTO `{$dbPermissionCategories}` (`id`, `category`) VALUES(5, 'strKBabbr');
INSERT INTO `{$dbPermissionCategories}` (`id`, `category`) VALUES(6, 'strPortal');
INSERT INTO `{$dbPermissionCategories}` (`id`, `category`) VALUES(7, 'strConfiguration');
INSERT INTO `{$dbPermissionCategories}` (`id`, `category`) VALUES(8, 'strBilling');
INSERT INTO `{$dbPermissionCategories}` (`id`, `category`) VALUES(9, 'strReports');
INSERT INTO `{$dbPermissionCategories}` (`id`, `category`) VALUES(10, 'strHolidays');
INSERT INTO `{$dbPermissionCategories}` (`id`, `category`) VALUES(11, 'strOther');


CREATE TABLE `{$dbPriority}` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM COMMENT='Used in incidents.php' AUTO_INCREMENT=5 DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbPriority}` VALUES (1, 'Low');
INSERT INTO `{$dbPriority}` VALUES (2, 'Medium');
INSERT INTO `{$dbPriority}` VALUES (3, 'High');
INSERT INTO `{$dbPriority}` VALUES (4, 'Critical');


CREATE TABLE `{$dbProductInfo}` (
  `id` int(11) NOT NULL auto_increment,
  `productid` int(11) default NULL,
  `information` text,
  `moreinformation` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbProducts}` (
  `id` int(11) NOT NULL auto_increment,
  `vendorid` int(5) NOT NULL default '0',
  `name` varchar(50) default NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `vendorid` (`vendorid`),
  KEY `name` (`name`)
) ENGINE=MyISAM COMMENT='Current List of Products' DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbRelatedIncidents}` (
`id` INT( 5 ) NOT NULL AUTO_INCREMENT ,
`incidentid` INT( 5 ) NOT NULL ,
`relation` ENUM( 'child', 'sibling' ) DEFAULT 'child' NOT NULL ,
`relatedid` INT( 5 ) NOT NULL ,
`owner` smallint(6) NOT NULL default '0',
PRIMARY KEY ( `id` ) ,
INDEX ( `incidentid` , `relatedid` )
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbResellers}` (
  `id` tinyint(4) NOT NULL auto_increment,
  `name` varchar(100) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbResellers}` VALUES (1,'Us (No Reseller)');


CREATE TABLE `{$dbRoles}` (
`id` INT( 5 ) NOT NULL AUTO_INCREMENT ,
`rolename` VARCHAR( 255 ) NOT NULL ,
`description` text NULL,
PRIMARY KEY ( `id` )
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbRoles}` ( `id` , `rolename` ) VALUES ('1', 'Administrator');
INSERT INTO `{$dbRoles}` ( `id` , `rolename` ) VALUES ('2', 'Manager');
INSERT INTO `{$dbRoles}` ( `id` , `rolename` ) VALUES ('3', 'User');


CREATE TABLE `{$dbRolePermissions}` (
`roleid` tinyint( 4 ) NOT NULL default '0',
`permissionid` int( 5 ) NOT NULL default '0',
`granted` enum( 'true', 'false' ) NOT NULL default 'false',
PRIMARY KEY ( `roleid` , `permissionid` )
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 1, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 2, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 3, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 4, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 5, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 6, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 7, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 8, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 9, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 10, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 11, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 12, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 13, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 14, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 15, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 16, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 17, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 18, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 19, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 20, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 21, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 22, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 23, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 24, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 25, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 26, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 27, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 28, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 29, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 30, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 32, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 33, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 34, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 35, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 36, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 37, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 38, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 39, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 40, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 41, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 42, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 43, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 44, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 45, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 46, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 47, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 48, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 49, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 50, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 51, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 52, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 53, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 54, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 55, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 56, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 57, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 58, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 59, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 60, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 61, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 62, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 63, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 64, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 65, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 66, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 67, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 68, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 69, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 70, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 71, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 72, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 73, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 74, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 75, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 76, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 77, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 78, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 79, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 80, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (1, 81, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 1, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 2, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 3, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 4, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 5, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 6, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 7, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 8, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 10, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 11, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 12, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 13, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 14, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 15, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 16, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 17, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 18, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 19, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 21, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 24, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 25, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 26, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 27, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 28, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 29, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 30, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 32, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 33, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 34, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 35, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 36, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 37, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 38, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 39, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 40, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 41, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 42, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 43, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 44, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 45, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 46, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 47, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 48, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 49, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 50, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 51, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 52, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 53, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 54, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 55, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 56, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 58, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 59, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 60, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 61, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 62, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 67, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 69, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 70, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 71, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 73, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 76, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (2, 77, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 1, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 2, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 3, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 4, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 5, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 6, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 7, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 8, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 10, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 11, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 12, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 13, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 14, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 18, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 19, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 26, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 27, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 28, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 30, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 33, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 34, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 35, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 36, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 37, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 38, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 41, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 44, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 52, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 54, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 58, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 60, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 61, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 62, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 69, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 70, 'true');
INSERT INTO `{$dbRolePermissions}` (`roleid`, `permissionid`, `granted`) VALUES (3, 71, 'true');


CREATE TABLE `{$dbScheduler}` (
  `id` int(11) NOT NULL auto_increment,
  `action` varchar(50) NOT NULL,
  `params` varchar(255) NOT NULL,
  `paramslabel` varchar(255) default NULL,
  `description` tinytext NOT NULL,
  `status` enum('enabled','disabled') NOT NULL default 'enabled',
  `start` datetime NOT NULL,
  `end` datetime NOT NULL,
  `type` enum('interval','date') NOT NULL default 'interval',
  `interval` int(11) NOT NULL,
  `date_type` enum('month','year') NOT NULL COMMENT 'For type date the type',
  `date_offset` int(11) NOT NULL default '0' COMMENT 'off set into the period',
  `date_time` time NULL COMMENT 'Time to perform action',
  `laststarted` datetime NULL,
  `lastran` datetime NULL,
  `success` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `job` (`action`)
) ENGINE=MyISAM  DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbScheduler}` (`action`, `params`, `paramslabel`, `description`, `status`, `start`, `end`, `interval`, `lastran`, `success`) VALUES ('CloseIncidents', '554400', 'closure_delay', 'Close incidents that have been marked for closure for longer than the <var>closure_delay</var> parameter (which is in seconds)', 'enabled', '2008-01-01 00:00:00', '0000-00-00 00:00:00', 60, '0000-00-00 00:00:00', 1);
INSERT INTO `{$dbScheduler}` (`action`, `params`, `paramslabel`, `description`, `status`, `start`, `end`, `interval`, `lastran`, `success`) VALUES ('SetUserStatus', '', NULL, '(EXPERIMENTAL) This will set users status                         based on data from their holiday calendar.                        e.g. Out of Office/Away sick.', 'enabled', '2008-01-01 00:00:00', '0000-00-00 00:00:00', 60, '0000-00-00 00:00:00', 1);
INSERT INTO `{$dbScheduler}` (`action`, `params`, `paramslabel`, `description`, `status`, `start`, `end`, `interval`, `lastran`, `success`) VALUES ('PurgeJournal', '', NULL, 'Delete old journal entries according to the config setting <var>\$CONFIG[''journal_purge_after'']</var>', 'enabled', '2008-01-01 00:00:00', '0000-00-00 00:00:00', 300, '0000-00-00 00:00:00', 1);
INSERT INTO `{$dbScheduler}` (`action`, `params`, `paramslabel`, `description`, `status`, `start`, `end`, `interval`, `lastran`, `success`) VALUES ('TimeCalc', '', NULL, 'Calculate SLA Target Times and trigger                        OUT_OF_SLA and OUT_OF_REVIEW system email templates where appropriate.', 'enabled', '2008-01-01 00:00:00', '0000-00-00 00:00:00', 60, '0000-00-00 00:00:00', 1);
INSERT INTO `{$dbScheduler}` (`action`, `params`, `paramslabel`, `description`, `status`, `start`, `end`, `interval`, `lastran`, `success`) VALUES ('ChaseCustomers', '', NULL, 'Chase customers', 'disabled', '2008-01-01 00:00:00', '0000-00-00 00:00:00', 3600, '0000-00-00 00:00:00', 1);
INSERT INTO `{$dbScheduler}` (`action`, `params`, `paramslabel`, `description`, `status`, `start`, `end`, `interval`, `lastran`, `success`) VALUES ('CheckWaitingEmail', '', NULL, 'Checks the holding queue for emails and fires the TRIGGER_WAITING_HELD_EMAIL trigger when it finds some.', 'enabled', '2008-01-01 00:00:00', '0000-00-00 00:00:00', 60, '0000-00-00 00:00:00', 1);
INSERT INTO `{$dbScheduler}` (`action`, `params`, `paramslabel`, `description`, `status`, `start`, `end`, `interval`, `lastran`, `success`) VALUES ('PurgeExpiredFTPItems', '', NULL, 'purges files which have expired from the FTP site when run.', 'enabled', '2008-01-01 00:00:00', '0000-00-00 00:00:00', 216000, '0000-00-00 00:00:00', 1);
INSERT INTO `{$dbScheduler}` (`action`, `params`, `paramslabel`, `description`, `status`, `start`, `end`, `interval`, `lastran`, `success`) VALUES ('CheckIncomingMail', '', NULL, 'Check incoming support mailbox.', 'enabled', '2008-01-01 00:00:00', '0000-00-00 00:00:00', 60, '0000-00-00 00:00:00', 1);
INSERT INTO `{$dbScheduler}` (`action`, `params`, `paramslabel`, `description`, `status`, `start`, `end`, `interval`, `lastran`, `success`) VALUES ('CheckTasksDue', '', NULL, 'Checks for due tasks.', 'enabled', '2008-01-01 00:00:00', '0000-00-00 00:00:00', 3600, '0000-00-00 00:00:00', 1);
INSERT INTO `{$dbScheduler}` (`action`, `params`, `paramslabel`, `description`, `status`, `start`, `end`, `type`, `interval`, `date_type`, `date_offset`, `date_time`, `laststarted`, `lastran`, `success`) VALUES ('ldapSync', '', NULL, 'Sync users and customers from LDAP', 'enabled', '2008-01-01 00:00:00', '0000-00-00 00:00:00', 'interval', 60, 'month', 0, '00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1);

CREATE TABLE IF NOT EXISTS `{$dbService}` (
  `serviceid` int(11) NOT NULL auto_increment,
  `contractid` int(11) NOT NULL,
  `startdate` date NOT NULL,
  `enddate` date NOT NULL,
  `lastbilled` datetime NOT NULL,
  `creditamount` float NOT NULL default '0',
  `balance` float NOT NULL default '0',
  `unitrate` float NOT NULL default '0',
  `incidentrate` float NOT NULL default '0',
  `dailyrate` float NOT NULL default '0',
  `billingmatrix` varchar(32) NOT NULL,
  `priority` smallint(6) NOT NULL default '0',
  `cust_ref` VARCHAR( 255 ) NULL,
  `cust_ref_date` DATE NULL,
  `title` VARCHAR( 255 ) NULL,
  `notes` TEXT NOT NULL,
  `foc` enum('yes','no') NOT NULL default 'no' COMMENT 'Free of charge (customer not charged)',
    PRIMARY KEY  (`serviceid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbServiceLevels}` (
  `id` int(5) NOT NULL default '0',
  `tag` varchar(10) NOT NULL default '',
  `priority` int(5) NOT NULL default '0',
  `initial_response_mins` int(11) NOT NULL default '0',
  `prob_determ_mins` int(11) NOT NULL default '0',
  `action_plan_mins` int(11) NOT NULL default '0',
  `resolution_days` float(5,2) NOT NULL default '0.00',
  `contact_days` int(11) NOT NULL default '0',
  `review_days` int(11) NOT NULL default '365',
  `timed` enum('yes','no') NOT NULL default 'no',
  `allow_reopen` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'yes' COMMENT 'Allow incidents to be reopened?',
  PRIMARY KEY  (`tag`,`priority`),
  KEY `id` (`id`),
  KEY `review_days` (`review_days`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbServiceLevels}` VALUES (0, 'standard', 1, 320, 380, 960, 14.00, 28, 90, 'no', 'yes');
INSERT INTO `{$dbServiceLevels}` VALUES (0, 'standard', 2, 240, 320, 960, 10.00, 20, 90, 'no', 'yes');
INSERT INTO `{$dbServiceLevels}` VALUES (0, 'standard', 3, 120, 180, 480, 7.00, 14, 90, 'no', 'yes');
INSERT INTO `{$dbServiceLevels}` VALUES (0, 'standard', 4, 60, 120, 240, 3.00, 6, 90, 'no', 'yes');


CREATE TABLE `{$dbSetTags}` (
`id` INT NOT NULL ,
`type` MEDIUMINT NOT NULL ,
`tagid` INT NOT NULL ,
PRIMARY KEY ( `id` , `type` , `tagid` )
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbSiteContacts}` (
  `siteid` int(11) NOT NULL default '0',
  `contactid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`siteid`,`contactid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbSites}` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `department` varchar(255) NOT NULL default '',
  `address1` varchar(255) NOT NULL default '',
  `address2` varchar(255) NOT NULL default '',
  `city` varchar(255) NOT NULL default '',
  `county` varchar(255) NOT NULL default '',
  `country` varchar(255) NOT NULL default '',
  `postcode` varchar(255) NOT NULL default '',
  `telephone` varchar(255) NOT NULL default '',
  `fax` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `websiteurl` varchar(255) default NULL,
  `notes` blob NOT NULL,
  `typeid` int(5) NOT NULL default '1',
  `freesupport` int(5) NOT NULL default '0',
  `licenserx` int(5) NOT NULL default '0',
  `ftnpassword` varchar(40) NOT NULL default '',
  `owner` smallint(6) NOT NULL default '0',
  `active` enum('true','false') NOT NULL default 'true',
  PRIMARY KEY  (`id`),
  KEY `typeid` (`typeid`),
  KEY `owner` (`owner`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbSiteTypes}` (
  `typeid` int(5) NOT NULL auto_increment,
  `typename` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`typeid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbSiteTypes}` VALUES (1, 'Unclassified');
INSERT INTO `{$dbSiteTypes}` VALUES (2, 'Commercial');
INSERT INTO `{$dbSiteTypes}` VALUES (3, 'Academic');


CREATE TABLE `{$dbSoftware}` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `vendorid` INT( 5 ) NOT NULL default '0',
  `software` int(5) NOT NULL default '0',
  `lifetime_start` date default NULL,
  `lifetime_end` date default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM COMMENT='Individual software products as they are supported' AUTO_INCREMENT=1 DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbSoftware}` (`id`, `name`, `lifetime_start`, `lifetime_end`) VALUES (1, 'Example Software', NULL, NULL);


CREATE TABLE `{$dbSoftwareProducts}` (
  `productid` int(5) NOT NULL default '0',
  `softwareid` int(5) NOT NULL default '0',
  PRIMARY KEY  (`productid`,`softwareid`)
) ENGINE=MyISAM COMMENT='Table to link products with software' DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbSoftwareProducts}` VALUES (1,1);


CREATE TABLE `{$dbSupportContacts}` (
  `maintenanceid` int(11) default NULL,
  `contactid` int(11) default NULL,
  PRIMARY KEY ( `maintenanceid` , `contactid` )
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbSupportContacts}` VALUES (1,1);


CREATE TABLE `{$dbTags}` (
  `tagid` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`tagid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbTasks}` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `description` text NOT NULL,
  `priority` tinyint(4) default NULL,
  `owner` smallint(6) NOT NULL default '0',
  `duedate` datetime default NULL,
  `startdate` datetime default NULL,
  `enddate` datetime default NULL,
  `completion` tinyint(4) default NULL,
  `value` float(6,2) default NULL,
  `distribution` enum('public','private', 'incident', 'event') NOT NULL default 'public',
  `created` datetime NOT NULL default '0000-00-00 00:00:00',
  `lastupdated` timestamp(14) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `owner` (`owner`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbTempAssigns}` (
  `incidentid` int(5) NOT NULL default '0',
  `originalowner` smallint(6) NOT NULL default '0',
  `userstatus` tinyint(4) NOT NULL default '1',
  `assigned` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`incidentid`,`originalowner`),
  KEY `assigned` (`assigned`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbTempIncoming}` (
  `id` int(11) NOT NULL auto_increment,
  `updateid` int(11) NOT NULL default '0',
  `path` varchar(255) NOT NULL default '',
  `incidentid` int(11) NOT NULL default '0',
  `from` varchar(255) default NULL,
  `subject` varchar(255) default NULL,
  `emailfrom` varchar(255) default NULL,
  `locked` smallint(6) default NULL,
  `lockeduntil` datetime default NULL,
  `reason` varchar(255) default NULL,
  `reason_user` int(11) NOT NULL,
  `reason_time` datetime NOT NULL,
  `reason_id` tinyint(1) default 1,
  `incident_id` int(11) default NULL,
  `contactid` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `updateid` (`updateid`)
) ENGINE=MyISAM COMMENT='Temporary store for incoming attachment paths' DEFAULT CHARACTER SET = utf8;


 CREATE TABLE `{$dbTransactions}` (
`transactionid` INT NOT NULL AUTO_INCREMENT ,
`serviceid` INT NOT NULL ,
`totalunits` INT NOT NULL,
`totalbillableunits` INT NOT NULL,
`totalrefunds` INT NOT NULL,
`amount` FLOAT NOT NULL ,
`description` VARCHAR( 255 ) NOT NULL ,
`userid` smallint(6) NOT NULL ,
`dateupdated` DATETIME NOT NULL ,
`transactionstatus` smallint(6) NOT NULL default '5',
PRIMARY KEY ( `transactionid` )
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE IF NOT EXISTS `{$dbTriggers}` (
  `id` int(11) NOT NULL auto_increment,
  `triggerid` varchar(50) NOT NULL,
  `userid` smallint(6) NOT NULL,
  `action` varchar(255) default NULL,
  `template` varchar(255) default NULL,
  `parameters` varchar(255) default NULL,
  `checks` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `triggerid` (`triggerid`),
  KEY `userid` (`userid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

{$default_triggers}

CREATE TABLE `{$dbUpdates}` (
  `id` int(11) NOT NULL auto_increment,
  `incidentid` int(11) default NULL,
  `userid` smallint(6) default NULL,
  `type` enum('default','editing','opening','email','reassigning','closing','reopening','auto','phonecallout','phonecallin','research','webupdate','emailout','emailin','externalinfo','probdef','solution','actionplan','slamet','reviewmet','tempassigning', 'auto_chase_email', 'auto_chase_phone', 'auto_chase_manager','auto_chased_phone','auto_chased_manager','auto_chase_managers_manager', 'customerclosurerequest', 'fromtask') default 'default',
  `currentowner` tinyint(4) NOT NULL default '0',
  `currentstatus` smallint(6) NOT NULL default '0',
  `bodytext` text,
  `timestamp` int(11) default NULL,
  `nextaction` varchar(50) NOT NULL default '',
  `customervisibility` enum('show','hide','unset') default 'unset',
  `sla` enum('opened','initialresponse','probdef','actionplan','solution','closed') default NULL,
  `duration` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `currentowner` (`currentowner`,`currentstatus`),
  KEY `incidentid` (`incidentid`),
  KEY `timestamp` (`timestamp`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbUserGroups}` (
  `userid` smallint(6) NOT NULL default '0',
  `groupid` int(5) NOT NULL default '0',
  PRIMARY KEY  (`userid`,`groupid`)
) ENGINE=MyISAM COMMENT='Links users with groups' DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbUserPermissions}` (
  `userid` smallint(6) NOT NULL default '0',
  `permissionid` int(5) NOT NULL default '0',
  `granted` enum('true','false') NOT NULL default 'false',
  PRIMARY KEY  (`userid`,`permissionid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbUserPermissions}` VALUES (1, 1, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 2, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 3, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 4, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 5, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 6, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 7, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 8, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 9, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 10, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 11, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 12, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 13, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 14, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 15, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 16, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 17, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 18, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 19, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 20, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 21, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 22, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 23, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 24, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 25, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 26, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 27, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 28, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 29, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 30, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 31, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 32, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 33, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 34, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 35, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 36, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 37, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 38, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 39, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 40, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 41, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 42, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 43, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 44, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 45, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 46, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 47, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 48, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 49, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 50, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 51, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 52, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 53, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 54, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 55, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 56, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 57, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 58, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 59, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 60, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 61, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 62, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 63, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 64, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 65, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 66, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 67, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 68, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 69, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 70, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 71, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 72, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 73, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 74, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 75, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 76, 'true');
INSERT INTO `{$dbUserPermissions}` VALUES (1, 77, 'true');


CREATE TABLE `{$dbUsers}` (
  `id` smallint(6) NOT NULL auto_increment,
  `username` varchar(50) default NULL,
  `password` varchar(50) default NULL,
  `realname` varchar(50) default NULL,
  `roleid` int(5) NOT NULL default '3',
  `groupid` int(5) default NULL,
  `title` varchar(50) default NULL,
  `signature` text,
  `email` varchar(50) default NULL,
  `icq` varchar(15) NOT NULL default '',
  `aim` varchar(25) NOT NULL default '',
  `msn` varchar(70) NOT NULL default '',
  `phone` varchar(50) default NULL,
  `mobile` varchar(50) NOT NULL default '',
  `fax` varchar(50) default NULL,
  `status` tinyint(4) default NULL,
  `message` varchar(150) default NULL,
  `accepting` enum('No','Yes') default 'Yes',
  `user_startdate` DATE NULL,
  `var_incident_refresh` int(11) default '60',
  `var_update_order` enum('desc','asc') default 'desc',
  `var_num_updates_view` int(11) NOT NULL default '15',
  `var_style` int(11) default '1',
  `var_hideautoupdates` enum('true','false') NOT NULL default 'false',
  `var_hideheader` enum('true','false') NOT NULL default 'false',
  `var_monitor` enum('true','false') NOT NULL default 'true',
  `var_i18n` varchar(5) NOT NULL default 'en-GB',
  `var_utc_offset` int(11) NOT NULL default '0' COMMENT 'Offset from UTC (timezone)',
  `var_emoticons` enum('true','false') NOT NULL default 'false',
  `listadmin` tinytext,
  `holiday_entitlement` float NOT NULL default '0',
  `holiday_resetdate` DATE NULL,
  `qualifications` tinytext,
  `dashboard` varchar(255) NOT NULL default '0-3,1-1,1-2,2-4',
  `lastseen` DATETIME NOT NULL,
  `user_source` varchar(32) NOT NULL default 'sit',
  PRIMARY KEY  (`id`),
  KEY `username` (`username`),
  KEY `accepting` (`accepting`),
  KEY `status` (`status`),
  KEY `groupid` (`groupid`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


CREATE TABLE IF NOT EXISTS `{$dbUserConfig}` (
  `userid` smallint(6) NOT NULL default '0',
  `config` varchar(255) NOT NULL,
  `value` text,
  PRIMARY KEY  (`userid`,`config`),
  KEY `userid` (`userid`)
) TYPE=MyISAM COMMENT='User configuration' DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbUserSoftware}` (
  `userid` smallint(6) NOT NULL default '0',
  `softwareid` int(5) NOT NULL default '0',
  `backupid` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`userid`,`softwareid`),
  KEY `backupid` (`backupid`)
) ENGINE=MyISAM COMMENT='Defines which software users have expertise with' DEFAULT CHARACTER SET = utf8;


CREATE TABLE `{$dbUserStatus}` (
  `id` int(11) NOT NULL,
  `name` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;


INSERT INTO `{$dbUserStatus}` VALUES (0, 'strAccountDisabled');
INSERT INTO `{$dbUserStatus}` VALUES (1, 'strInOffice');
INSERT INTO `{$dbUserStatus}` VALUES (2, 'strNotInOffice');
INSERT INTO `{$dbUserStatus}` VALUES (3, 'strInMeeting');
INSERT INTO `{$dbUserStatus}` VALUES (4, 'strAtLunch');
INSERT INTO `{$dbUserStatus}` VALUES (5, 'strOnHoliday');
INSERT INTO `{$dbUserStatus}` VALUES (6, 'strWorkingFromHome');
INSERT INTO `{$dbUserStatus}` VALUES (7, 'strOnTrainingCourse');
INSERT INTO `{$dbUserStatus}` VALUES (8, 'strAbsentSick');
INSERT INTO `{$dbUserStatus}` VALUES (9, 'strWorkingAway');


CREATE TABLE `{$dbVendors}` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET = utf8;

INSERT INTO `{$dbVendors}` VALUES (1,'Default');
";

// ********************************************************************
// Sample Data
$sampledata_sql = "
INSERT INTO `{$dbSites}` (`id`, `name`, `department`, `address1`, `address2`, `city`, `county`,
`country`, `postcode`, `telephone`, `fax`, `email`, `notes`, `typeid`, `freesupport`, `licenserx`,
 `owner`) VALUES (1, 'ACME Widgets Co.', 'Manufacturing Dept.', '21 Any Street', '',
'Anytown', 'Anyshire', 'UNITED KINGDOM', 'AN1 0TH', '0555 555555', '0444 444444', 'acme@example.com',
'Example site', 1, 0, 0, 0);

INSERT INTO `{$dbContacts}` (`id`, `notify_contactid`, `username`, `password`, `forenames`, `surname`, `jobtitle`, `courtesytitle`, `siteid`, `email`, `phone`, `mobile`, `fax`, `department`, `address1`, `address2`, `city`, `county`, `country`, `postcode`, `dataprotection_email`, `dataprotection_phone`, `dataprotection_address`, `timestamp_added`, `timestamp_modified`, `notes`) VALUES
(1, '0', 'Acme1', MD5(RAND()), 'John', 'Acme', 'Chairman', 'Mr', 1, 'acme@example.com', '0666 222111', '', '', '', '', '', '', '', '', '', 'Yes', 'Yes', 'Yes', 1132930556, 1187360933, '');

INSERT INTO `{$dbProducts}` VALUES (1,1,'Example Product','This is an example product.');

INSERT INTO `{$dbResellers}` VALUES (2,'Example Reseller');

-- FIXME - decide what the last two fields should be by default
INSERT INTO `{$dbMaintenance}` (id, site, product, reseller, expirydate, licence_quantity, licence_type, incident_quantity, incidents_used, notes, admincontact, productonly, term, servicelevelid, incidentpoolid) VALUES (1,1,1,2,1428192000,1,4,0,0,'This is an example contract.',1,'no','no',0,0);

";

// Upgrading from versions prior to 3.90 won't be possible via setup.php
$upgrade_schema[390] = "ALTER TABLE `{$dbBillingMatrix}` CHANGE `id` `tag` VARCHAR( 32 ) NOT NULL ;
ALTER TABLE `{$dbService}` CHANGE `billingmatrix` `billingmatrix` VARCHAR( 32 ) NOT NULL ;
UPDATE `{$dbBillingMatrix}` SET tag = 'Default' WHERE tag = 1;
UPDATE `{$dbService}` SET billingmatrix = 'Default' WHERE billingmatrix = 1;


UPDATE `{$dbPermissions}` SET name = 'strAddNewSiteContact' WHERE id = 1;
UPDATE `{$dbPermissions}` SET name = 'strAddNewSites' WHERE id = 2;
UPDATE `{$dbPermissions}` SET name = 'strEditExistingSiteDetails' WHERE id = 3;
UPDATE `{$dbPermissions}` SET name = 'strEditProfile' WHERE id = 4;
UPDATE `{$dbPermissions}` SET name = 'strAddIncidents' WHERE id = 5;
UPDATE `{$dbPermissions}` SET name = 'strViewIncidents' WHERE id = 6;
UPDATE `{$dbPermissions}` SET name = 'strEditIncidents' WHERE id = 7;
UPDATE `{$dbPermissions}` SET name = 'strUpdateIncidents' WHERE id = 8;
UPDATE `{$dbPermissions}` SET name = 'strEditUserPermissions' WHERE id = 9;
UPDATE `{$dbPermissions}` SET name = 'strEditContacts' WHERE id = 10;
UPDATE `{$dbPermissions}` SET name = 'strViewSites' WHERE id = 11;
UPDATE `{$dbPermissions}` SET name = 'strViewContacts' WHERE id = 12;
UPDATE `{$dbPermissions}` SET name = 'strReassignIncidents' WHERE id = 13;
UPDATE `{$dbPermissions}` SET name = 'strViewUsers' WHERE id = 14;
UPDATE `{$dbPermissions}` SET name = 'strAddSupportedProducts' WHERE id = 15;
UPDATE `{$dbPermissions}` SET name = 'strAddTemplates' WHERE id = 16;
UPDATE `{$dbPermissions}` SET name = 'strEditTemplates' WHERE id = 17;
UPDATE `{$dbPermissions}` SET name = 'strCloseIncidents' WHERE id = 18;
UPDATE `{$dbPermissions}` SET name = 'strViewContracts' WHERE id = 19;
UPDATE `{$dbPermissions}` SET name = 'strAddUsers' WHERE id = 20;
UPDATE `{$dbPermissions}` SET name = 'strEditContracts' WHERE id = 21;
UPDATE `{$dbPermissions}` SET name = 'strAdministrate' WHERE id = 22;
UPDATE `{$dbPermissions}` SET name = 'strEditUser' WHERE id = 23;
UPDATE `{$dbPermissions}` SET name = 'strAddProduct' WHERE id = 24;
UPDATE `{$dbPermissions}` SET name = 'strAddProductInformation' WHERE id = 25;
UPDATE `{$dbPermissions}` SET name = 'strGetHelp' WHERE id = 26;
UPDATE `{$dbPermissions}` SET name = 'strViewYourCalendar' WHERE id = 27;
UPDATE `{$dbPermissions}` SET name = 'strViewProductsAndSoftware' WHERE id = 28;
UPDATE `{$dbPermissions}` SET name = 'strEditProducts' WHERE id = 29;
UPDATE `{$dbPermissions}` SET name = 'strViewSupportedProducts' WHERE id = 30;
-- UPDATE `{$dbPermissions}` SET name = '' WHERE id = 31;   -- There is no 31
UPDATE `{$dbPermissions}` SET name = 'strEditSupportedProducts' WHERE id = 32;
UPDATE `{$dbPermissions}` SET name = 'strSendEmails' WHERE id = 33;
UPDATE `{$dbPermissions}` SET name = 'strReopenIncidents' WHERE id = 34;
UPDATE `{$dbPermissions}` SET name = 'strSetYourStatus' WHERE id = 35;
UPDATE `{$dbPermissions}` SET name = 'strSetContactFlags' WHERE id = 36;
UPDATE `{$dbPermissions}` SET name = 'strRunReports' WHERE id = 37;
UPDATE `{$dbPermissions}` SET name = 'strViewSalesIncidents' WHERE id = 38;
UPDATE `{$dbPermissions}` SET name = 'strAddContract' WHERE id = 39;
UPDATE `{$dbPermissions}` SET name = 'strReassignIncidentsWhenUserNotAccepting' WHERE id = 40;
UPDATE `{$dbPermissions}` SET name = 'strViewStatus' WHERE id = 41;
UPDATE `{$dbPermissions}` SET name = 'strReviewDeleteIncidentUpdates' WHERE id = 42;
UPDATE `{$dbPermissions}` SET name = 'strEditGlobalSignature' WHERE id = 43;
UPDATE `{$dbPermissions}` SET name = 'strPublishFielsToFTPSite' WHERE id = 44;
-- UPDATE `{$dbPermissions}` SET name = '' WHERE id = 45;  -- View Mailing list subscriptions - we no longer do
-- UPDATE `{$dbPermissions}` SET name = '' WHERE id = 46;  -- Edit Mailing list subscriptions - we no longer do
-- UPDATE `{$dbPermissions}` SET name = '' WHERE id = 47;  -- Administrator mailing lists - we no longer do
UPDATE `{$dbPermissions}` SET name = 'strAddFeedbackForms' WHERE id = 48;
UPDATE `{$dbPermissions}` SET name = 'strEditFeedbackForms' WHERE id = 49;
UPDATE `{$dbPermissions}` SET name = 'strApproveHolidays' WHERE id = 50;
UPDATE `{$dbPermissions}` SET name = 'strViewFeedback' WHERE id = 51;
UPDATE `{$dbPermissions}` SET name = 'strViewHiddenUpdates' WHERE id = 52;
UPDATE `{$dbPermissions}` SET name = 'strEditServiceLevels' WHERE id = 53;
UPDATE `{$dbPermissions}` SET name = 'strViewKnowledgebaseArticles' WHERE id = 54;
UPDATE `{$dbPermissions}` SET name = 'strDeleteSitesContacts' WHERE id = 55;
UPDATE `{$dbPermissions}` SET name = 'strAddSoftware' WHERE id = 56;
UPDATE `{$dbPermissions}` SET name = 'strDisableUserAccounts' WHERE id = 57;
UPDATE `{$dbPermissions}` SET name = 'strEditYourSkills' WHERE id = 58;
UPDATE `{$dbPermissions}` SET name = 'strManageUsersSkills' WHERE id = 59;
UPDATE `{$dbPermissions}` SET name = 'strPerformSearches' WHERE id = 60;
UPDATE `{$dbPermissions}` SET name = 'strViewIncidentDetails' WHERE id = 61;
UPDATE `{$dbPermissions}` SET name = 'strViewIncidentAttachments' WHERE id = 62;
UPDATE `{$dbPermissions}` SET name = 'strAddReseller' WHERE id = 63;
UPDATE `{$dbPermissions}` SET name = 'strManageEscalationPaths' WHERE id = 64;
UPDATE `{$dbPermissions}` SET name = 'strDeleteProducts' WHERE id = 65;
UPDATE `{$dbPermissions}` SET name = 'strInstallDashboardComponents' WHERE id = 66;
UPDATE `{$dbPermissions}` SET name = 'strRunManagementReports' WHERE id = 67;
UPDATE `{$dbPermissions}` SET name = 'strManageHolidays' WHERE id = 68;
UPDATE `{$dbPermissions}` SET name = 'strViewYourTasks' WHERE id = 69;
UPDATE `{$dbPermissions}` SET name = 'strCreateEditYourTasks' WHERE id = 70;
UPDATE `{$dbPermissions}` SET name = 'strManageYourTriggers' WHERE id = 71;
UPDATE `{$dbPermissions}` SET name = 'strManageSystemTriggers' WHERE id = 72;
UPDATE `{$dbPermissions}` SET name = 'strApproveBillableIncidents' WHERE id = 73;
UPDATE `{$dbPermissions}` SET name = 'strSetDurationWithoutActivity' WHERE id = 74;
UPDATE `{$dbPermissions}` SET name = 'strSetNegativeTimeForDurationOnIncidents' WHERE id = 75;
UPDATE `{$dbPermissions}` SET name = 'strViewTransactions' WHERE id = 76;
UPDATE `{$dbPermissions}` SET name = 'strViewBillingInformation' WHERE id = 77;
UPDATE `{$dbPermissions}` SET name = 'strPostSystemNotices' WHERE id = 78;
UPDATE `{$dbPermissions}` SET name = 'strEditServiceBalances' WHERE id = 79;
UPDATE `{$dbPermissions}` SET name = 'strEditServiceDetails' WHERE id = 80;
UPDATE `{$dbPermissions}` SET name = 'strAdjustActivityDuration' WHERE id = 81;




";

// ********************************************************************


// Important: When making changes to the schema you must add SQL to make the alterations
// to existing databases in $upgrade_schema[] *AND* you must also change $schema[] for
// new installations (above the line of stars).
?>
