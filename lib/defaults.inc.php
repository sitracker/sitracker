<?php
// defaults.inc.php - Provide configuration defaults
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
//  Author: Ivan Lucas
//  Notes: These variables are overwridden by config.inc.php and/or sit.conf

###########################################################
####                                                   ####
####  IMPORTANT:                                       ####
####                                                   ####
####    Don't modify this file to configure your       ####
####    SiT installation, instead edit the             ####
####    config.inc.php file.                           ####
####                                                   ####
####    If you don't have a config.inc.php file        ####
####    you can use the config.inc-dist.php file       ####
####    as a template.                                 ####
####                                                   ####
###########################################################

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


$CONFIG['application_name'] = 'SiT! Support Incident Tracker';
$CONFIG['application_shortname'] = 'SiT!';

$CONFIG['application_webpath'] = '/';

// The URI prefix to use when referring to this application (in emails etc.)
$CONFIG['application_uriprefix'] = '';

$CONFIG['db_hostname'] = 'localhost';
$CONFIG['db_username'] = '';
$CONFIG['db_password'] = '';
// the name of the database to use
$CONFIG['db_database'] = 'sit';

// Prefix database tables with the a string (e.g. 'sit_', use this if the database you are using is shared with other applications
$CONFIG['db_tableprefix'] = '';

$CONFIG['home_country'] = 'UNITED KINGDOM';

$CONFIG['support_email'] = 'support@localhost';
// DEPRECATED support_manager_email is obsolete as of v3.45, use support_manager instead
$CONFIG['support_manager_email'] = 'support_manager@localhost';
// The user ID of the person who is in charge of your support service
$CONFIG['support_manager'] = 1;

// Incident number style
// 1 = incremental, 2 = YYMMDD + incremental incidents per day
$CONFIG['incident_number_type'] = 1;

$CONFIG['enable_outbound_mail'] = TRUE;

// These are the settings for the account to download incoming mail from, settings POP/IMAP or MTA (for piping message in)
$CONFIG['enable_inbound_mail'] = 'disabled';
$CONFIG['email_username'] = '';
$CONFIG['email_password'] = '';
$CONFIG['email_address'] = '';
$CONFIG['email_server'] = '';
//'imap' or 'pop'
$CONFIG['email_servertype'] = '';
// e.g. Gmail needs '/ssl', secure Groupwise needs /novalidate-cert etc.
// see http://uk2.php.net/imap_open for examples
$CONFIG['email_options'] = '';
$CONFIG['email_port'] = '';

$CONFIG['bugtracker_url'] = 'http://sitracker.org/wiki/Bugs';

// See http://www.php.net/manual/en/function.date.php for help with date formats
$CONFIG['dateformat_datetime'] = 'jS M Y @ g:ia';
$CONFIG['dateformat_filedatetime'] = 'd/m/Y H:i';
$CONFIG['dateformat_shortdate'] = 'd/m/y';
$CONFIG['dateformat_shorttime'] = 'H:i';
$CONFIG['dateformat_date'] = 'jS M Y';
$CONFIG['dateformat_time'] = 'g:ia';
$CONFIG['dateformat_longdate'] = 'l jS F Y';

// Array containing working days (0=Sun, 1=Mon ... 6=Sat)
$CONFIG['working_days'] = array(1,2,3,4,5);
// Times of the start and end of the working day (in seconds)
$CONFIG['start_working_day'] = (9 * 3600);
$CONFIG['end_working_day'] = (17 * 3600);

$CONFIG['attachment_fspath'] = "";
$CONFIG['attachment_webpath'] = "attachments/";

$CONFIG['upload_max_filesize'] = get_cfg_var('upload_max_filesize');
// Convert a PHP.INI integer value into a byte value

// The icon set that new users should use
$CONFIG['default_iconset'] = 'sit';

// The interface style that new users should use (user default style)
$CONFIG['default_interface_style'] = 'kriplyana';

// Knowledgebase ID prefix, inserted before the ID to give it uniqueness
$CONFIG['kb_id_prefix'] = 'KB';
// Knowledgebase disclaimer, displayed at the bottom of every article
$CONFIG['kb_disclaimer_html']  = '<strong>THE INFORMATION IN THIS DOCUMENT IS PROVIDED ON AN AS-IS BASIS WITHOUT WARRANTY OF ANY KIND.</strong> ';
$CONFIG['kb_disclaimer_html'] .= 'PROVIDER SPECIFICALLY DISCLAIMS ANY OTHER WARRANTY, EXPRESS OR IMPLIED, INCLUDING ANY WARRANTY OF MERCHANTABILITY ';
$CONFIG['kb_disclaimer_html'] .= 'OR FITNESS FOR A PARTICULAR PURPOSE. IN NO EVENT SHALL PROVIDER BE LIABLE FOR ANY CONSEQUENTIAL, INDIRECT, SPECIAL ';
$CONFIG['kb_disclaimer_html'] .= 'OR INCIDENTAL DAMAGES, EVEN IF PROVIDER HAS BEEN ADVISED BY USER OF THE POSSIBILITY OF SUCH POTENTIAL LOSS OR DAMAGE. ';
$CONFIG['kb_disclaimer_html'] .= 'USER AGREES TO HOLD PROVIDER HARMLESS FROM AND AGAINST ANY AND ALL CLAIMS, LOSSES, LIABILITIES AND EXPENSES.';

// The service level to use in case the contact does not specify (text not the tag)
$CONFIG['default_service_level'] = 'standard';
// The number of days to elapse before we are prompted to contact the customer (usually overridden by SLA)
$CONFIG['regular_contact_days'] = 7;

// Number of free support incidents that can be logged to a site
$CONFIG['free_support_limit'] = 2;

// Comma seperated list specifying the numbers of incidents to assign to contracts
$CONFIG['incident_pools'] = '1,2,3,4,5,10,20,25,50,100,150,200,250,500,1000';

// Incident feedback form (the id number of the feedback form to use or empty to disable sending feedback forms out)
$CONFIG['feedback_form'] = '';

// If you set 'trusted_server' to TRUE, passwords can no longer be changed from the users profile
// another mechanism for authentication
$CONFIG['trusted_server'] = FALSE;

// Lock records for (number of seconds)
$CONFIG['record_lock_delay'] = 1800;  // 30 minutes

// maximum no. of incoming emails per incident before a mail-loop is detected
$CONFIG['max_incoming_email_perday'] = 15;

// String to look for in email message subject to determine a message is spam
$CONFIG['spam_email_subject'] = 'SPAMASSASSIN';

$CONFIG['feedback_max_score'] = 9;

// Paths to various required files
$CONFIG['licensefile']= 'doc/LICENSE';
$CONFIG['changelogfile']= 'doc/Changelog';
$CONFIG['creditsfile']= 'doc/CREDITS';

// The session name for use in cookies and URL's, Must contain alphanumeric characters only
$CONFIG['session_name'] = 'SiTsessionID';


// Notice Threshold, flag items as 'notice' when they are this percentage complete.
$CONFIG['notice_threshold'] = 85;

// Urgent Threshold, flag items as 'urgent' when they are this percentage complete.
$CONFIG['urgent_threshold'] = 90;

// Urgent Threshold, flag items as 'critical' when they are this percentage complete.
$CONFIG['critical_threshold'] = 95;

// Force critical priority incidents to flag as critical
// When set all critical priority incidents will be forcibly marked as if past the critical threshold.
$CONFIG['force_critical_flag'] = FALSE;

// Run in demo mode, some features are disabled or replaced with mock-ups
$CONFIG['demo'] = FALSE;

// Output extra debug information, some as HTML comments and some in the page footer
$CONFIG['debug'] = FALSE;

// Enable user portal
$CONFIG['portal'] = TRUE;

// Journal Logging Level
//      0 = No logging
//      1 = Minimal Logging
//      2 = Normal Logging
//      3 = Full Logging
//      4 = Maximum/Debug Logging
$CONFIG['journal_loglevel'] = 3;

// How long should we keep journal entries, entries older than this will be purged (deleted)
$CONFIG['journal_purge_after'] = 60 * 60 * 24 * 180;  // 180 Days

// When left blank this defaults to $CONFIG['application_webpath'], setting that here will take the value of the default
$CONFIG['logout_url'] = '';

$CONFIG['error_logfile'] = '';

// Filename to log authentication failures
$CONFIG['access_logfile'] = '';

// The plugins configuration is an array
//$CONFIG['plugins'] = array();
$CONFIG['plugins'] = array();

$CONFIG['no_feedback_contracts'] = array();

$CONFIG['preferred_maintenance'] = array();

// Use an icon for specified tags, format: array('tag' => 'icon', 'tag2' => 'icon2')";
$CONFIG['tag_icons'] = array ('redflag' => 'redflag', 'yellowflag' => 'yellowflag', 'blueflag' => 'blueflag', 'cyanflag' => 'cyanflag', 'greenflag' => 'greenflag', 'whiteflag' => 'whiteflag', 'blackflag' => 'blackflag');

// Default Internationalisation tag (rfc4646/rfc4647/ISO 639 code), note the corresponding i18n file must exist in includes/i18n before you can use it
$CONFIG['default_i18n'] = 'en-GB';

// How should the language be selected prior to login, via a dropdown or a list?
$CONFIG['i18n_selection'] = 'dropdown';

$CONFIG['timezone'] = 'Europe/London';

// Incidents closed more than this number of days ago aren't show in the incident queue, -1 means disabled
$CONFIG['hide_closed_incidents_older_than'] = 90;

// Following is still BETA
$CONFIG['auto_chase'] = FALSE;
$CONFIG['chase_email_minutes'] = 0; // number of minutes incident has been 'awaiting customer action' before sending a chasing email, 0 is disabled
$CONFIG['chase_phone_minutes'] = 0; // number of minutes incident has been 'awaiting customer action' before putting in the 'chase by phone queue', 0 is disabled
$CONFIG['chase_manager_minutes'] = 0; // number of minutes incident has been 'awaiting customer action' before putting in the 'chase manager queue', 0 is disabled
$CONFIG['chase_managers_manager_minutes'] = 0; // number of minutes incident has been 'awaiting customer action' before putting in the 'chase managers_manager queue', 0 is disabled
$CONFIG['chase_email_template'] = ''; // The template to use to send chase email
$CONFIG['dont_chase_maintids'] = array(1 => 1); // maintence IDs not to chase

//Enable/Disable sections
$CONFIG['kb_enabled'] = TRUE;
$CONFIG['portal_kb_enabled'] = 'Public';
$CONFIG['tasks_enabled'] = TRUE;
$CONFIG['calendar_enabled'] = TRUE;
$CONFIG['holidays_enabled'] = TRUE;
$CONFIG['feedback_enabled'] = TRUE;
$CONFIG['portal_feedback_enabled'] = TRUE;

$CONFIG['portal_site_incidents'] = TRUE; //users in the portal can view site incidents based on the contract options
$CONFIG['portal_usernames_can_be_changed'] = TRUE; //portal usernames can be changed by the users
$CONFIG['portal_iconset'] = 'kriplyana';

// The interface style to use for the portal
$CONFIG['portal_interface_style'] = 'kriplyana';

// incidents are automatically assigned based on a lottery weighted towards who
// are less busy, assumes everyone set to accepting is an engineer and willing to take incidents
$CONFIG['auto_assign_incidents'] = TRUE;

// Default role for new users, where 1 is admin, 2 is manager and 3 is user
$CONFIG['default_roleid'] = 3;

// Default gravatar, can be 'wavatar', 'identicon', 'monsterid' a URL to an image, or blank for a blue G
// see www.gravatar.com to learn about gravatars
$CONFIG['default_gravatar'] = 'identicon';

// A URL linking to a web mapping service, use the variable {address} to pass the address to the mapping service. e.g. http://www.google.com/maps?q={address}
$CONFIG['map_url'] = 'http://www.google.com/maps?q={address}';

// Default holiday entitlement for new users and new holiday periods (in days)
$CONFIG['default_entitlement'] = 21;

// Default for whom the billing reports should be mailed to, multiple address can be seperared by commas
$CONFIG['billing_reports_email'] = 'admin@localhost';

// Allow incidents to be approved against overdrawn services
$CONFIG['billing_allow_incident_approval_against_overdrawn_service'] = TRUE;

// Multipliers to be used on billing matrix to allow more units tobe consumed at certain periods of time
$CONFIG['billing_matrix_multipliers'] = array(0.5, 1, 1.5, 2, 2.5, 3);

// Default billing multiplier to use
$CONFIG['billing_default_multiplier'] = 1;

$CONFIG['inventory_types']['cisco vpn'] = 'Cisco VPN';
$CONFIG['inventory_types']['go_to_my_pc'] = 'Go to my PC';
$CONFIG['inventory_types']['nortel vpn'] = 'Nortel VPN';
$CONFIG['inventory_types']['pc_anywhere'] = 'PC Anywhere';
$CONFIG['inventory_types']['rdp_tunneled_ssh'] = 'RDP tunneled through SSH';
$CONFIG['inventory_types']['rdp'] = 'RDP';
$CONFIG['inventory_types']['reverse_vnc'] = 'Reverse VNC';
$CONFIG['inventory_types']['server'] = 'Server';
$CONFIG['inventory_types']['software'] = 'Software';
$CONFIG['inventory_types']['ssh_port_tunneling'] = 'SSH (port tunneled)';
$CONFIG['inventory_types']['ssh'] = 'SSH';
$CONFIG['inventory_types']['ssl_vpn'] = 'SSL VPN';
$CONFIG['inventory_types']['vnc'] = 'VNC';
$CONFIG['inventory_types']['webex'] = 'Webex';
$CONFIG['inventory_types']['workstation'] = 'Workstation/PC';

// Whether it should be possible to adjust a duration of individual activities
$CONFIG['allow_duration_adjustment'] = FALSE;

// LDAP Config

// Set to TRUE for LDAP authentication, or FALSE for standard database authentication
$CONFIG['use_ldap'] = FALSE;

$CONFIG['ldap_host'] = "";
$CONFIG['ldap_port'] = '389';

// Ldap Protocol version to use
$CONFIG['ldap_protocol'] = 3;

// The credentials for binding to the ldap host
$CONFIG['ldap_bind_user'] = "";
$CONFIG['ldap_bind_pass'] = "";

// SSL, TLS or NONE
$CONFIG['ldap_security'] = 'NONE';

// The LDAP Base DN for user lookups
$CONFIG['ldap_user_base'] = "ou=Users,dc=example,dc=com";

// Default user values
// LDAP user status (1 = In Office)
$CONFIG['ldap_default_user_status'] = 1;

// LDAP group for SIT users
$CONFIG['ldap_user_group'] = "cn=situsers,ou=Groups,dc=example,dc=com";

// LDAP group for SIT admins
$CONFIG['ldap_admin_group'] = "cn=sitadmins,ou=Groups,dc=example,dc=com";

// LDAP group for SIT managers
$CONFIG['ldap_manager_group'] = "cn=sitmanagers,ou=Groups,dc=example,dc=com";

// Customer Group and default role
$CONFIG['ldap_customer_group'] = "cn=sitcustomers,ou=Groups,dc=example,dc=com";

// Default Customer values
// 1 is the example site in the default install
$CONFIG['ldap_default_customer_siteid'] = 1;

// This attempts to create the customer record automatically using LDAP
// when creating an incident from an email in the holding queue.
$CONFIG['ldap_autocreate_customer'] = TRUE;

// Whether to cache users passwords from LDAP
$CONFIG['ldap_cache_passwords'] = FALSE;

// Whether to allow authentication against stored password if unable to
// connect to LDAP server
$CONFIG['ldap_allow_cached_password'] = FALSE;

// If true, portal users create incidents, if FALSE, they just create emails
$CONFIG['portal_creates_incidents'] = TRUE;

$CONFIG['holiday_allow_overbooking'] = FALSE;

$CONFIG['soap_enabled'] = FALSE;
$CONFIG['soap_portal_enabled'] = FALSE;

$CONFIG['inventory_enabled'] = TRUE;

$CONFIG['currency_symbol'] = '&curren;';

$CONFIG['display_minute_interval'] = 15;

$CONFIG['available_charts'] = array('OriginalChart');

$CONFIG['default_chart'] = 'OriginalChart';

// Associative array of user config variables and their settings
$CONFIG['user_config_defaults'] = array('show_emoticons' => TRUE, 'incident_refresh' => 60, 'incident_log_order' => 'desc', 'show_table_legends' => TRUE);

// Associative array of contact config variables and their settings
$CONFIG['contact_config_defaults'] = array('feedback_enabled' => 'yes');

// Associative array of site config variables and their settings
$CONFIG['site_config_defaults'] = array('feedback_enabled' => 'yes');


// Allow outbound email
$CONFIG['enable_outbound_email'] = TRUE;

// Change the newline character if outbound emails have line breaks in the wrong places.(CRLF or LF)
$CONFIG['outbound_email_newline'] = 'LF';

// Change the newline character if outbound emails attachments have line breaks in the wrong places.(CRLF or LF)
$CONFIG['outbound_emailattachment_newline'] = 'CRLF';

// The string used to prefix the incident number to make the incident reference custamisable
$CONFIG['incident_reference_prefix'] = '';

// The character(s) to appear before the incident number on an outgoing email e.g. in [123456] this is [
$CONFIG['incident_id_email_opening_tag'] = '[';

// The character(s) to appear after the incident number on an outgoing email e.g. in [123456] this is ]
$CONFIG['incident_id_email_closing_tag'] = ']';

// How many address components are required before the map link appears on site details 
$CONFIG['address_components_to_map'] = 3;
?>