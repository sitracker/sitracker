-- create_sitdb_39x.sql 
-- 
-- Define database schema for SiT! database of version 3.9x
-- with constraints for documentation purposes
-- 
-- SiT (Support Incident Tracker) - Support call tracking system
-- Copyright (C) 2011 The Support Incident Tracker Project
-- 
-- This software may be used and distributed according to the terms
-- of the GNU General Public License, incorporated herein by reference.
--
-- Author: Gabriele Pohl <contact[at]dipohl.de>
--  

set sql_mode='STRICT_ALL_TABLES';
set storage_engine=innodb;

# TABLE userstatus
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `userstatus` (
  `id` int(11) NOT NULL,
  `name` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Status, i.e. Absent Sick, Holiday';

# TABLE roles
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `roles` (
`id` INT( 5 ) NOT NULL AUTO_INCREMENT ,
`rolename` VARCHAR( 255 ) NOT NULL ,
`description` text NULL,
PRIMARY KEY ( `id` )
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Roles, i.e. categories of user permissions';

# TABLE permissioncategories
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `permissioncategories` (
`id` INT( 5 ) NOT NULL AUTO_INCREMENT ,
`category` VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( `id` )
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Categories for user permissions';

# TABLE permissions
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(5) NOT NULL auto_increment,
  `categoryid` int(5) NOT NULL,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`categoryid`) REFERENCES `permissioncategories`(`id`),  
  KEY `categoryid` (`categoryid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'List of user permissions, these match constants in the code';

# TABLE rolepermissions
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `rolepermissions` (
  `roleid` int( 5 ) NOT NULL default '0',
  `permissionid` int( 5 ) NOT NULL default '0',
  `granted` enum( 'true', 'false' ) NOT NULL default 'false',
  PRIMARY KEY ( `roleid` , `permissionid` ),
  FOREIGN KEY (`roleid`) REFERENCES `roles`(`id`),
  FOREIGN KEY (`permissionid`) REFERENCES `permissions`(`id`)  
) DEFAULT CHARACTER SET = utf8
COMMENT = 'List of permissions per role';

# TABLE groups
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `imageurl` varchar(255) NOT NULL default '',
  `created` DATETIME NULL,
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`)
-- circular reference: Table USERS refers to groupid
-- therefore we have to add constraint later..
--  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
--  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`)  
) DEFAULT CHARACTER SET = utf8
COMMENT='List of user groups';


# TABLE users
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `users` (
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
  `skype` varchar(70) NOT NULL default '',
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
  FOREIGN KEY (`roleid`) REFERENCES `roles`(`id`),
  FOREIGN KEY (`groupid`) REFERENCES `groups`(`id`),      
  KEY `username` (`username`),
  KEY `accepting` (`accepting`),
  KEY `status` (`status`),
  KEY `groupid` (`groupid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'SiT Users (Engineers)';

# TABLE sitetypes
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `sitetypes` (
  `typeid` int(5) NOT NULL auto_increment,
  `typename` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`typeid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Site Types';

# TABLE sites
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `sites` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `department` varchar(255) DEFAULT NULL,
  `address1` varchar(255) NOT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `county` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `postcode` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `fax` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `websiteurl` varchar(255) DEFAULT NULL,
  `notes` blob,
  `typeid` int(5) NOT NULL default '1',
  `freesupport` int(5) NOT NULL default '0',
  `licenserx` int(5) NOT NULL default '0',
  `ftnpassword` varchar(40) NOT NULL default '',
  `owner` smallint(6) NOT NULL default '0',
  `active` enum('true','false') NOT NULL default 'true',
  PRIMARY KEY  (`id`),
  FOREIGN  KEY (`typeid`) REFERENCES `sitetypes`(`typeid`),
  FOREIGN KEY (`owner`) REFERENCES `users`(`id`),   
  KEY `typeid` (`typeid`),
  KEY `owner` (`owner`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Sites';

# TABLE resellers
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `resellers` (
  `id` tinyint(4) NOT NULL auto_increment,
  `name` varchar(100) default NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Resellers for products';

# TABLE contacts
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notify_contactid` int(11) NOT NULL DEFAULT '0',
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `forenames` varchar(100) NOT NULL DEFAULT '',
  `surname` varchar(100) NOT NULL DEFAULT '',
  `jobtitle` varchar(255) NOT NULL DEFAULT '',
  `courtesytitle` varchar(50) NOT NULL DEFAULT '',
  `siteid` int(11) NOT NULL DEFAULT '0',
  `email` varchar(100) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `address1` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `county` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `postcode` varchar(255) DEFAULT NULL,
  `dataprotection_email` enum('No','Yes') DEFAULT 'No',
  `dataprotection_phone` enum('No','Yes') DEFAULT 'No',
  `dataprotection_address` enum('No','Yes') DEFAULT 'No',
  `timestamp_added` int(11) DEFAULT NULL,
  `timestamp_modified` int(11) DEFAULT NULL,
  `notes` blob,
  `active` enum('true','false') NOT NULL DEFAULT 'true',
  `created` datetime DEFAULT NULL,
  `createdby` smallint(6) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `modifiedby` smallint(6) DEFAULT NULL,
  `contact_source` varchar(32) NOT NULL DEFAULT 'sit',
  PRIMARY KEY (`id`),
  FOREIGN  KEY (`siteid`) REFERENCES `sites`(`id`),
  FOREIGN KEY (`notify_contactid`) REFERENCES `contacts`(`id`),  
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`),  
  KEY `siteid` (`siteid`),
  KEY `username` (`username`),
  KEY `forenames` (`forenames`),
  KEY `surname` (`surname`),
  KEY `notify_contactid` (`notify_contactid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Contacts';

# TABLE incidentstatus
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `incidentstatus` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  `ext_name` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Incident statuses, these match constants in the SiT code';

# TABLE licencetypes
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `licencetypes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) default NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Contract licence types';

# TABLE vendors
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `vendors` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'List of Vendors';

# TABLE products
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL auto_increment,
  `vendorid` int(5) NOT NULL default '0',
  `name` varchar(50) default NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`vendorid`) REFERENCES `vendors`(`id`),  
  KEY `vendorid` (`vendorid`),
  KEY `name` (`name`)
) DEFAULT CHARACTER SET = utf8
COMMENT='Current List of Products';

# TABLE productinfo
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `productinfo` (
  `id` int(11) NOT NULL auto_increment,
  `productid` int(11) default NULL,
  `information` text,
  `moreinformation` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`productid`) REFERENCES `products`(`id`)  
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Product info questions';

# TABLE maintenance
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `maintenance` (
  `id` int(11) NOT NULL auto_increment,
  `site` int(11) default NULL,
  `product` int(11) default NULL,
  `reseller` tinyint(4) default NULL,
  `expirydate` int(11) default NULL,
  `licence_quantity` int(11) default NULL,
  `licence_type` int(11) default NULL,
  `incident_quantity` int(5) NOT NULL default '0',
  `incidents_used` int(5) NOT NULL default '0',
  `notes` text,
  `admincontact` int(11) default NULL,
  `productonly` enum('yes','no') NOT NULL default 'no',
  `term` enum('no','yes') default 'no',
  `servicelevel` varchar(32) NOT NULL default '',
  `incidentpoolid` int(11) NOT NULL default '0',
  `supportedcontacts` INT( 255 ) NOT NULL DEFAULT '0',
  `allcontactssupported` ENUM( 'no', 'yes' ) NOT NULL DEFAULT 'no',
  `var_incident_visible_contacts` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no',
  `var_incident_visible_all` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no',
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`product`) REFERENCES `products`(`id`),
  FOREIGN KEY (`reseller`) REFERENCES `resellers`(`id`),
  FOREIGN KEY (`licence_type`) REFERENCES `licencetypes`(`id`),
  KEY `site` (`site`),
  KEY `productonly` (`productonly`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Contracts';

# TABLE incidents
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `incidents` (
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
  `servicelevel` varchar(32) default NULL,
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
  `created` DATETIME NULL,
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`owner`) REFERENCES `users`(`id`),
  FOREIGN KEY (`towner`) REFERENCES `users`(`id`),
  FOREIGN  KEY (`maintenanceid`) REFERENCES `maintenance`(`id`), 
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`),  
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
  KEY `servicelevel` (`servicelevel`),
  KEY `lastupdated` (`lastupdated`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Core data of incidents';

# TABLE incidentproductinfo
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `incidentproductinfo` (
  `id` int(11) NOT NULL auto_increment,
  `incidentid` int(11) default NULL,
  `productinfoid` int(11) default NULL,
  `information` text,
  `created` DATETIME NULL,
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`incidentid`) REFERENCES `incidents`(`id`),  
  FOREIGN KEY (`productinfoid`) REFERENCES `productinfo`(`id`),  
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`)  
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Responses to product info questions per incident';

# TABLE tempassigns
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `tempassigns` (
  `incidentid` int(5) NOT NULL default '0',
  `originalowner` smallint(6) NOT NULL default '0',
  `userstatus` int(11) NOT NULL default '1',
  `assigned` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`incidentid`,`originalowner`),
  FOREIGN KEY (`incidentid`) REFERENCES `incidents`(`id`),  
  FOREIGN KEY (`originalowner`) REFERENCES `users`(`id`),
  FOREIGN KEY (`userstatus`) REFERENCES `userstatus`(`id`),  
  KEY `assigned` (`assigned`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Incidents thats been temporary assigned to another engineer';

# TABLE updates
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `updates` (
  `id` int(11) NOT NULL auto_increment,
  `incidentid` int(11) default NULL,
  `userid` smallint(6) default NULL,
  `type` enum('default','editing','opening','email','reassigning','closing','reopening','auto','phonecallout','phonecallin','research','webupdate','emailout','emailin','externalinfo','probdef','solution','actionplan','slamet','reviewmet','tempassigning', 'auto_chase_email', 'auto_chase_phone', 'auto_chase_manager','auto_chased_phone','auto_chased_manager','auto_chase_managers_manager', 'customerclosurerequest', 'fromtask') default 'default',
  `currentowner` smallint(6) NOT NULL default '0',
  `currentstatus` smallint(6) NOT NULL default '0',
  `bodytext` text,
  `timestamp` int(11) default NULL,
  `nextaction` varchar(50) NOT NULL default '',
  `customervisibility` enum('show','hide','unset') default 'unset',
  `sla` enum('opened','initialresponse','probdef','actionplan','solution','closed') default NULL,
  `duration` int(11) default NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`incidentid`) REFERENCES `incidents`(`id`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`),
  FOREIGN KEY (`currentowner`) REFERENCES `users`(`id`),   
  KEY `currentowner` (`currentowner`,`currentstatus`),
  KEY `incidentid` (`incidentid`),
  KEY `timestamp` (`timestamp`),
  KEY `type` (`type`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Incident updates';

# TABLE system
# NOTE system must be the first table created.

CREATE TABLE IF NOT EXISTS `system` (
  `id` int(1) NOT NULL default '0',
  `version` float(3,2) NOT NULL default '0.00',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'used to hold schema version number';

# TABLE billingmatrix
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `billingmatrix` (
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
) DEFAULT CHARACTER SET = utf8
COMMENT = 'billing multipliers';

# TABLE billing_periods
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `billing_periods` (
  `engineerperiod` INT NOT NULL COMMENT 'In minutes',
  `customerperiod` INT NOT NULL COMMENT 'In minutes',
  `priority` INT( 4 ) NOT NULL,
  `tag` VARCHAR( 10 ) NOT NULL,
  `created` DATETIME NULL,
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  `limit` float NOT NULL default 0,
  PRIMARY KEY ( `tag`,`priority` ),
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`)  
) DEFAULT CHARACTER SET = utf8
COMMENT = 'missing a comment here';

# TABLE closingstatus
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `closingstatus` (
 `id` int(11) NOT NULL auto_increment,
 `name` varchar(50) default NULL,
 PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'extra statuses for closed incidents';

# TABLE config
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `config` (
  `config` varchar(255) NOT NULL,
  `value` text,
  PRIMARY KEY  (`config`)
) DEFAULT CHARACTER SET = utf8
COMMENT='SiT system configuration';

# TABLE dashboard
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `dashboard` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `version` mediumint(9) NOT NULL default '1',
  `enabled` enum('true','false') NOT NULL default 'false',
  PRIMARY KEY  (`id`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Dashboard components (dashlets)';

# TABLE emailtemplates
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `emailtemplates` (
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
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`) 
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Templates for outbound incident emails';

# TABLE escalationpaths
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `escalationpaths` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `track_url` varchar(255) default NULL,
  `home_url` varchar(255) NOT NULL default '',
  `url_title` varchar(255) default NULL,
  `email_domain` varchar(255) default NULL,
  `createdby` smallint(6)  NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6)  NULL ,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`)  
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Escalation paths';

# TABLE feedbackforms
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `feedbackforms` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `introduction` text NOT NULL,
  `thanks` text NOT NULL,
  `description` text NOT NULL,
  `multi` enum('yes','no') NOT NULL default 'no',
  `created` DATETIME NULL,
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`),  
  KEY `multi` (`multi`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Feedback form summary, meta';

# TABLE feedbackquestions
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `feedbackquestions` (
  `id` int(5) NOT NULL auto_increment,
  `formid` int(5) NOT NULL default '0',
  `question` varchar(255) NOT NULL default '',
  `questiontext` text NOT NULL,
  `sectiontext` text NOT NULL,
  `taborder` int(5) NOT NULL default '0',
  `type` varchar(255) NOT NULL default 'text',
  `required` enum('true','false') NOT NULL default 'false',
  `options` text NOT NULL,
  `created` DATETIME NULL,
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`formid`) REFERENCES `feedbackforms`(`id`),
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`),  
  KEY `taborder` (`taborder`),
  KEY `type` (`type`),
  KEY `formid` (`formid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Feedback questions for feedback forms';

# TABLE feedbackrespondents
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `feedbackrespondents` (
  `id` int(5) NOT NULL auto_increment,
  `formid` int(5) NOT NULL default '0',
  `contactid` int(11) NOT NULL default '0',
  `incidentid` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `completed` enum('yes','no') NOT NULL default 'no',
  `created` timestamp NOT NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`formid`) REFERENCES `feedbackforms`(`id`),    
  FOREIGN KEY (`contactid`) REFERENCES `contacts`(`id`),    
  FOREIGN KEY (`incidentid`) REFERENCES `incidents`(`id`),    
  KEY `responseref` (`incidentid`),
  KEY `formid` (`formid`),
  KEY `contactid` (`contactid`),
  KEY `completed` (`completed`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'List of responses to feedback forms';

# TABLE feedbackresults
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `feedbackresults` (
  `id` int(5) NOT NULL auto_increment,
  `respondentid` int(5) NOT NULL default '0',
  `questionid` int(5) NOT NULL default '0',
  `result` varchar(255) NOT NULL default '',
  `resulttext` text,
  `created` DATETIME NULL,
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`respondentid`) REFERENCES `feedbackrespondents`(`id`),  
  FOREIGN KEY (`questionid`) REFERENCES `feedbackquestions`(`id`),  
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`),  
  KEY `questionid` (`questionid`),
  KEY `respondentid` (`respondentid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Answers/responses for feedback questions/feedback forms';

# TABLE incidentpools
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `incidentpools` (
  `id` int(11) NOT NULL auto_increment,
  `maintenanceid` int(11) NOT NULL default '0',
  `siteid` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL default '',
  `incidentsremaining` int(5) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  FOREIGN  KEY (`maintenanceid`) REFERENCES `maintenance`(`id`),
  FOREIGN  KEY (`siteid`) REFERENCES `sites`(`id`),
  KEY `maintenanceid` (`maintenanceid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Incident pools for contracts';

# TABLE software
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `software` (
  `id` int(5) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `vendorid` INT( 5 ) NOT NULL default '0',
  `software` int(5) NOT NULL default '0',
  `lifetime_start` date default NULL,
  `lifetime_end` date default NULL,
  PRIMARY KEY  (`id`)
) AUTO_INCREMENT=1 DEFAULT CHARACTER SET = utf8
COMMENT='Individual skills (fka software) as they are supported.';

# TABLE softwareproducts
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `softwareproducts` (
  `productid` int(5) NOT NULL default '0',
  `softwareid` int(5) NOT NULL default '0',
  PRIMARY KEY  (`productid`,`softwareid`),
  FOREIGN KEY (`productid`) REFERENCES `products`(`id`), 
  FOREIGN KEY (`softwareid`) REFERENCES `software`(`id`) 
) DEFAULT CHARACTER SET = utf8
COMMENT='Table to link products with skills (fka software)';

# TABLE kbarticles
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `kbarticles` (
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
  FOREIGN KEY (`reviewer`) REFERENCES `users`(`id`),  
  KEY `distribution` (`distribution`),
  KEY `title` (`title`)
) DEFAULT CHARACTER SET = utf8
COMMENT='Knowledge base articles, summary/meta';

# TABLE kbsoftware
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `kbsoftware` (
  `docid` int(5) NOT NULL default '0',
  `softwareid` int(5) NOT NULL default '0',
  PRIMARY KEY  (`docid`,`softwareid`),
  FOREIGN KEY (`docid`) REFERENCES `kbarticles`(`docid`),
  FOREIGN KEY (`softwareid`) REFERENCES `software`(`id`)   
) DEFAULT CHARACTER SET = utf8
COMMENT='Links kb articles with skills (fka software)';

# TABLE linktypes
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `linktypes` (
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
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Soft link definitions';

# TABLE service
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `service` (
  `serviceid` int(11) NOT NULL auto_increment,
  `contractid` int(11) NOT NULL,
  `startdate` date NOT NULL,
  `enddate` date NOT NULL,
  `lastbilled` datetime NOT NULL,
  `creditamount` float NOT NULL default '0',
  `balance` float NOT NULL default '0',
  `unitrate` float NOT NULL default '0',
  `incidentrate` float NOT NULL default '0',
  `billingmatrix` varchar(32) NOT NULL,
  `priority` smallint(6) NOT NULL default '0',
  `cust_ref` VARCHAR( 255 ) NULL,
  `cust_ref_date` DATE NULL,
  `title` VARCHAR( 255 ) NULL,
  `notes` TEXT NOT NULL,
  `foc` enum('yes','no') NOT NULL default 'no' COMMENT 'Free of charge (customer not charged)',
  PRIMARY KEY  (`serviceid`),
  FOREIGN  KEY (`contractid`) REFERENCES `maintenance`(`id`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'List Billing service periods (per contract)';


# TABLE noticetemplates
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `noticetemplates` (
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
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Templates for user notices';

# TABLE priority
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `priority` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) AUTO_INCREMENT=5 DEFAULT CHARACTER SET = utf8
COMMENT='Incident/Task priorities, these match constants in the code';

# TABLE scheduler
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `scheduler` (
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
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Actions for the scheduler';

# TABLE servicelevels
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `servicelevels` (
  `tag` varchar(32) NOT NULL default '',
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
  KEY `review_days` (`review_days`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Service levels (SLA)';

# TABLE tags
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `tags` (
  `tagid` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`tagid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Available tags';

# TABLE tempincoming
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `tempincoming` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `arrived` datetime NOT NULL,
  `updateid` int(11) NOT NULL DEFAULT '0',
  `path` varchar(255) NOT NULL DEFAULT '',
  `incidentid` int(11) NOT NULL DEFAULT '0',
  `from` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `emailfrom` varchar(255) DEFAULT NULL,
  `locked` smallint(6) DEFAULT NULL,
  `lockeduntil` datetime DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `reason_user` int(11) NOT NULL,
  `reason_time` datetime NOT NULL,
  `reason_id` tinyint(1) DEFAULT '1',
  `incident_id` int(11) DEFAULT NULL,
  `contactid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`contactid`) REFERENCES `contacts`(`id`),      
  KEY `updateid` (`updateid`)
) DEFAULT CHARACTER SET = utf8
COMMENT='Temporary store for incoming attachment paths';

# TABLE tasks
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `tasks` (
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
  `lastupdated` timestamp NOT NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`owner`) REFERENCES `users`(`id`),   
  KEY `owner` (`owner`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Tasks';

# TABLE supportcontacts
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `supportcontacts` (
  `maintenanceid` int(11) default NULL,
  `contactid` int(11) default NULL,
  PRIMARY KEY ( `maintenanceid` , `contactid` ),
  FOREIGN  KEY (`maintenanceid`) REFERENCES `maintenance`(`id`), 
  FOREIGN KEY (`contactid`) REFERENCES `contacts`(`id`)    
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Named contacts for contracts';

# TABLE inventory
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `inventory` (
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
  FOREIGN  KEY (`siteid`) REFERENCES `sites`(`id`),
  FOREIGN KEY (`contactid`) REFERENCES `contacts`(`id`),
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`),  
  KEY `siteid` (`siteid`,`contactid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Inventory';

# TABLE sitecontacts
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `sitecontacts` (
  `siteid` int(11) NOT NULL default '0',
  `contactid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`siteid`,`contactid`),
  FOREIGN KEY (`contactid`) REFERENCES `contacts`(`id`)    
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Links contacts to sites';

# TABLE feedbackreport
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `feedbackreport` (
  `id` int(5) NOT NULL default '0',
  `formid` int(5) NOT NULL default '0',
  `respondent` int(11) NOT NULL default '0',
  `responseref` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `completed` enum('yes','no') NOT NULL default 'no',
  `created` timestamp NOT NULL,
  `incidentid` int(5) NOT NULL default '0',
  `contactid` int(5) NOT NULL default '0',
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`formid`) REFERENCES `feedbackforms`(`id`),
  FOREIGN KEY (`respondent`) REFERENCES `feedbackrespondents`(`id`),  
  FOREIGN KEY (`incidentid`) REFERENCES `incidents`(`id`),  
  FOREIGN KEY (`contactid`) REFERENCES `contacts`(`id`),
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`),  
  KEY `responseref` (`responseref`),
  KEY `formid` (`formid`),
  KEY `respondant` (`respondent`),
  KEY `completed` (`completed`),
  KEY `incidentid` (`incidentid`),
  KEY `contactid` (`contactid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Feedback assigned/closed attached to contact';

# TABLE contactconfig
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `contactconfig` (
  `contactid` int(11) NOT NULL default '0',
  `config` varchar(255) NOT NULL,
  `value` text,
  PRIMARY KEY  (`contactid`,`config`),
  FOREIGN KEY (`contactid`) REFERENCES `contacts`(`id`),
  KEY `contactid` (`contactid`)
) DEFAULT CHARACTER SET = utf8
COMMENT='Contact configuration';

# TABLE siteconfig
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `siteconfig` (
  `siteid` int(11) NOT NULL default '0',
  `config` varchar(255) NOT NULL,
  `value` text,
  PRIMARY KEY  (`siteid`,`config`),
  FOREIGN  KEY (`siteid`) REFERENCES `sites`(`id`),
  KEY siteid (`siteid`)
) DEFAULT CHARACTER SET = utf8
COMMENT='Site configuration';

# TABLE relatedincidents
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `relatedincidents` (
  `id` INT( 5 ) NOT NULL AUTO_INCREMENT ,
  `incidentid` INT( 5 ) NOT NULL ,
  `relation` ENUM( 'child', 'sibling' ) DEFAULT 'child' NOT NULL ,
  `relatedid` INT( 5 ) NOT NULL ,
  `owner` smallint(6) NOT NULL default '0',
  PRIMARY KEY ( `id` ),
  FOREIGN KEY (`incidentid`) REFERENCES `incidents`(`id`),  
  FOREIGN KEY (`owner`) REFERENCES `users`(`id`), 
  INDEX ( `incidentid` , `relatedid` )
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Links incidents with other incidents';

# TABLE kbcontent
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `kbcontent` (
  `docid` int(5) NOT NULL default '0',
  `id` int(7) NOT NULL auto_increment,
  `ownerid` smallint(6) NOT NULL default '0',
  `headerstyle` char(2) NOT NULL default 'h1',
  `header` varchar(255) NOT NULL default '',
  `contenttype` int(5) NOT NULL default '1',
  `content` mediumtext NOT NULL,
  `distribution` enum('public','private','restricted') NOT NULL default 'private',
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`ownerid`) REFERENCES `users`(`id`),   
  KEY `distribution` (`distribution`),
  KEY `ownerid` (`ownerid`),
  KEY `docid` (`docid`)
--  FULLTEXT KEY `c_index` (`content`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Knowledge base section content for knowledge base articles';

# TABLE set_tags
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `set_tags` (
  `id` INT NOT NULL ,
  `type` MEDIUMINT NOT NULL ,
  `tagid` INT NOT NULL ,
  PRIMARY KEY ( `id` , `type` , `tagid` ),
  FOREIGN  KEY (`tagid`) REFERENCES `tags`(`tagid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Tags that have been set for incidents, sites, tasks etc.';

# TABLE holidays
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `holidays` (
  `id` int(11) NOT NULL auto_increment,
  `userid` smallint(6) NOT NULL default '0',
  `type` int(11) NOT NULL default '1',
  `length` enum('am','pm','day') NOT NULL default 'day',
  `approved` tinyint(1) NOT NULL default '0',
  `approvedby` smallint(6) NOT NULL default '0',
  `date` DATE NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`), 
  FOREIGN KEY (`approvedby`) REFERENCES `users`(`id`),  
  KEY `userid` (`userid`),
  KEY `type` (`type`),
  KEY `approved` (`approved`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'List of holidays any user requested';

# TABLE notices
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `notices` (
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
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`)
) AUTO_INCREMENT=1 DEFAULT CHARACTER SET = utf8
COMMENT = 'User Notices';

# TABLE notes
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `notes` (
  `id` int(11) NOT NULL auto_increment,
  `userid` smallint(6) NOT NULL default '0',
  `timestamp` timestamp NOT NULL,
  `bodytext` text NOT NULL,
  `link` int(11) NOT NULL default '0',
  `refid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`),    
  KEY `refid` (`refid`),
  KEY `userid` (`userid`),
  KEY `link` (`link`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Notes';

# TABLE links
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `links` (
  `linktype` int(11) NOT NULL default '0',
  `origcolref` int(11) NOT NULL default '0',
  `linkcolref` int(11) NOT NULL default '0',
  `direction` enum('left','right','bi') NOT NULL default 'left',
  `userid` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`linktype`,`origcolref`,`linkcolref`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`),       
  KEY `userid` (`userid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Soft links';

# TABLE journal
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `journal` (
  `id` int(11) NOT NULL auto_increment,
  `userid` smallint(6) NOT NULL default '0',
  `timestamp` timestamp NOT NULL,
  `event` varchar(40) NOT NULL default '',
  `bodytext` text NOT NULL,
  `journaltype` int(11) NOT NULL default '0',
  `refid` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`),    
  KEY `refid` (`refid`),
  KEY `userid` (`userid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'SiT Journal - events logged';

# TABLE files
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `files` (
  `id` int(11) NOT NULL auto_increment,
  `category` enum('public','private','protected','ftp') NOT NULL default 'public',
  `filename` varchar(255) NULL default '',
  `size` bigint(11) NOT NULL default '0',
  `userid` smallint(6) NOT NULL default '0',
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
  `created` DATETIME NULL,
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`),
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`),  
  KEY `userid` (`userid`),
  KEY `category` (`category`),
  KEY `filename` (`filename`),
  KEY `published` (`published`),
  KEY `webcategory` (`webcategory`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Meta data for uploaded files';

# TABLE drafts
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `drafts` (
  `id` int(11) NOT NULL auto_increment,
  `userid` smallint(6) NOT NULL,
  `incidentid` int(11) NOT NULL,
  `type` enum('update','email') NOT NULL,
  `content` text NOT NULL,
  `meta` text NOT NULL,
  `lastupdate` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`),
  FOREIGN KEY (`incidentid`) REFERENCES `incidents`(`id`),    
  KEY `incidentid` (`incidentid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Incident update drafts';

# TABLE transactions
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `transactions` (
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
  PRIMARY KEY ( `transactionid` ),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'missing a comment here';

# TABLE triggers
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `triggers` (
  `id` int(11) NOT NULL auto_increment,
  `triggerid` varchar(50) NOT NULL,
  `userid` smallint(6) NOT NULL,
  `action` varchar(255) default NULL,
  `template` varchar(255) default NULL,
  `parameters` varchar(255) default NULL,
  `checks` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`),  
  KEY `triggerid` (`triggerid`),
  KEY `userid` (`userid`)
) DEFAULT CHARACTER SET = utf8
COMMENT = 'System/User triggers';

# TABLE usergroups
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `usergroups` (
  `userid` smallint(6) NOT NULL default '0',
  `groupid` int(5) NOT NULL default '0',
  PRIMARY KEY  (`userid`,`groupid`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`),
  FOREIGN KEY (`groupid`) REFERENCES `groups`(`id`)      
) DEFAULT CHARACTER SET = utf8
COMMENT='Links users with groups';

# TABLE userpermissions
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `userpermissions` (
  `userid` smallint(6) NOT NULL default '0',
  `permissionid` int(5) NOT NULL default '0',
  `granted` enum('true','false') NOT NULL default 'false',
  PRIMARY KEY  (`userid`,`permissionid`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`),
  FOREIGN KEY (`permissionid`) REFERENCES `permissions`(`id`)      
) DEFAULT CHARACTER SET = utf8
COMMENT = 'Role permissions';

# TABLE emailsig
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `emailsig` (
  `id` int(11) NOT NULL auto_increment,
  `signature` text NOT NULL,
  `created` DATETIME NULL,
  `createdby` smallint(6) NULL ,
  `modified` DATETIME NULL ,
  `modifiedby` smallint(6) NULL ,
  PRIMARY KEY  (`id`),
  FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`)  
)  DEFAULT CHARACTER SET = utf8
COMMENT='Global Email Signature';

# TABLE userconfig
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `userconfig` (
  `userid` smallint(6) NOT NULL default '0',
  `config` varchar(255) NOT NULL,
  `value` text,
  PRIMARY KEY  (`userid`,`config`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`),  
  KEY `userid` (`userid`)
) DEFAULT CHARACTER SET = utf8
COMMENT='User configuration';

# TABLE usersoftware
# NOTE ..?..

CREATE TABLE IF NOT EXISTS `usersoftware` (
  `userid` smallint(6) NOT NULL default '0',
  `softwareid` int(5) NOT NULL default '0',
  `backupid` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`userid`,`softwareid`),
  FOREIGN KEY (`userid`) REFERENCES `users`(`id`),  
  FOREIGN KEY (`softwareid`) REFERENCES `software`(`id`),  
  KEY `backupid` (`backupid`)
) DEFAULT CHARACTER SET = utf8
COMMENT='List of skills (fka software) users do support.';

# Add constraints that could not be added before
ALTER TABLE groups
add FOREIGN KEY (`createdby`) REFERENCES `users`(`id`),  
add FOREIGN KEY (`modifiedby`) REFERENCES `users`(`id`); 