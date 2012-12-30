<?php
// triggers.inc.php - Trigger definitions and helper functions
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

/**
 * Trigger action definitions
 * These are the avilable actions to be taken if a trigger is fired
 * To extend these, implement a plugin which attaches to the trigger_actions hook
 */

$actionarray['ACTION_NOTICE'] =
array('name' => $strNotice,
      'description' => $strCreateANotice
      );

$actionarray['ACTION_EMAIL'] =
array('name' => $strEmail,
      'description' => $strSendAnEmail
      );

$actionarray['ACTION_CREATE_INCIDENT'] =
array('name' => $strNewIncident,
      'description' => $strCreateAnIncident,
      'requires' => array('updateid'),
      'permission' => array(),
      'type' => 'system'
      );

$actionarray['ACTION_JOURNAL'] =
array('name' => 'Journal',
      'description' => $strLogTriggerInJournal,
      'type' => 'system'
      );

plugin_do('trigger_actions');

/**
 * Trigger type definitions
 * These are the avilable triggers that can be fired
 * To extend these, implement a plugin which attaches to the trigger_types hook
 *
 * array definitions:
 * name - trigger name, can be anything descriptive, not seen by the end-user
 * description - when the trigger is fired, shown to the end-user
 * required - variables that are needed and can be used for templates
 * params - Rules the trigger can check, mimics 'subscription'-type events
 * type - Trigger type (eg. incident, contact etc)
 */

$trigger_types['TRIGGER_CONTACT_RESET_PASSWORD'] =
array('name' => $strContactResetPassword,
      'description' => $strTriggerContactResetPasswordDesc,
      'required' => array('contactid', 'passwordreseturl'),
      'type' => 'system'
      );

$trigger_types['TRIGGER_HOLIDAY_REQUESTED'] =
array('name' => $strHolidayRequested,
      'description' => $strTriggerHolidayRequestedDesc,
      'required' => array('userid', 'approvaluseremail', 'listofholidays'),
      'permission' => 'user_permission($_SESSION[\'userid\'], PERM_HOLIDAY_APPROVE);',
      'type' => 'system'
      );

$trigger_types['TRIGGER_INCIDENT_ASSIGNED'] =
array('name' => $strIncidentAssigned,
      'description' => $strTriggerNewIncidentAssignedDesc,
      'required' => array('incidentid', 'userid'),
      'object' => 'incident',
      'params' => array('ownerid', 'userstatus', 'incidentassigner')
      );

$trigger_types['TRIGGER_INCIDENT_CLOSED'] =
array('name' => $strIncidentClosed,
      'description' => $strTriggerIncidentClosedDesc,
      'required' => array('incidentid', 'userid', 'notifyexternal', 'notifycontact','awaitingclosure'),
      'params' => array('userid', 'externalid', 'externalengineer', 'notifyexternal', 'notifycontact','awaitingclosure')
      );

$trigger_types['TRIGGER_INCIDENT_CREATED'] =
array('name' => $strIncidentCreated,
      'description' => $strTriggerNewIncidentCreatedDesc,
      'required' => array('incidentid'),
      'params' => array('contactid', 'siteid', 'incidentpriorityid', 'contractid', 'slatag', 'salespersonid', 'sendemail')
      );

$trigger_types['TRIGGER_INCIDENT_NEARING_SLA'] =
array('name' => $strIncidentNearingSLA,
      'description' => $strTriggerIncidentNearingSLADesc,
      'required' => array('incidentid', 'nextslatime', 'nextsla'),
      'params' => array('ownerid', 'townerid'),
      );

$trigger_types['TRIGGER_INCIDENT_REVIEW_DUE'] =
array('name' => $strIncidentReviewDue,
      'description' => $strTriggerIncidentReviewDueDesc,
      'required' => array('incidentid', 'time'),
      'params' => array('incidentid'),
      );

$trigger_types['TRIGGER_INCIDENT_UPDATED_EXTERNAL'] =
array('name' => $strIncidentUpdatedExternally,
      'description' => $strIncidentUpdatedExternallyDesc,
      'required' => array('incidentid'),
      'params' => array('incidentid')
      );

$trigger_types['TRIGGER_INCIDENT_UPDATED_INTERNAL'] =
array('name' => $strIncidentUpdatedInternally,
      'description' => $strIncidentUpdatedInternallyDesc,
      'required' => array('incidentid', 'userid'),
      'params' => array('incidentid', 'userid')
      );

$trigger_types['TRIGGER_KB_CREATED'] =
array('name' => $strKnowledgeBaseArticleCreated,
      'description' => $strTriggerKBArticleCreatedDesc,
      'required' => array('kbid', 'userid'),
      'params' => array('userid'),
      );

$trigger_types['TRIGGER_LANGUAGE_DIFFERS'] =
array('name' => $strCurrentLanguageDiffers,
      'description' => $strTriggerLanguageDiffersDesc,
      'required' => array('currentlang', 'profilelang'),
      'params' => array()
    );

$trigger_types['TRIGGER_PORTAL_INCIDENT_CREATED'] =
array('name' => $strPortalIncidentCreated,
      'description' => $strTriggerPortalIncidentCreated,
      'required' => array('incidentid'),
      'params' => array('incidentid', 'contactid', 'siteid', 'incidentpriorityid', 'contractid', 'slatag', 'sitesalespersonid')
    );

$trigger_types['TRIGGER_PORTAL_INCIDENT_REQUESTCLOSURE'] =
array('name' => $strPortalIncidentRequestClosed,
      'description' => $strTriggerPortalIncidentRequestClosed,
      'required' => array('incidentid'),
      'params' => array('userid')
    );

$trigger_types['TRIGGER_NEW_CONTACT'] =
array('name' => $strNewContact,
      'description' => $strTriggerNewContactDesc,
      'required' => array('contactid', 'prepassword', 'userid'),
      'params' => array('siteid')
      );

$trigger_types['TRIGGER_NEW_CONTRACT'] =
array('name' => $strNewContract,
      'description' => $strTriggerNewContractDesc,
      'required' => array('contractid'),
      'params' => array('productid', 'slatag')
      );

$trigger_types['TRIGGER_NEW_HELD_EMAIL'] =
array('name' => $strNewHeldEmail,
      'description' => $strTriggerNewHeldEmailDesc,
      'required' => array('holdingemailid'),
      'params' => array('subject', 'contactid', 'siteid'),
      );

$trigger_types['TRIGGER_NEW_SITE'] =
array('name' => $strNewSite,
      'description' => $strTriggerNewSiteDesc,
      'required' => array('siteid')
      );

$trigger_types['TRIGGER_NEW_USER'] =
array('name' => $strNewUser,
      'description' => $strTriggerNewUserDesc,
      'required' => array('userid')
      );

$trigger_types['TRIGGER_SCHEDULER_TASK_FAILED'] =
array('name' => $strSchedulerActionFailed,
      'description' => $strTriggerSchedulerTaskFailedDesc,
      'required' => array('schedulertask'),
      'params' => array('schedulertask'),
      'perm' => 22
      );

$trigger_types['TRIGGER_SIT_UPGRADED'] =
array('name' => $strSitUpgraded,
      'description' => $strTriggerSitUpgradedDesc,
      'required' => array('applicationversion'),
      'params' => array(),
      );

$trigger_types['TRIGGER_TASK_DUE'] =
array('name' => $strTaskDue,
      'description' => $strTaskDueDesc,
      'required' => array('taskid'),
      'params' => array('userid')
      );

$trigger_types['TRIGGER_USER_CHANGED_STATUS'] =
array('name' => $strUserChangedStatus,
      'description' => $strTriggerUserChangedStatusDesc,
      'required' => array('userid'),
      'params' => array('userid', 'userstatus', 'useraccepting'),
      );

$trigger_types['TRIGGER_USER_RESET_PASSWORD'] =
array('name' => $strUserResetPassword,
      'description' => $strTriggerUserResetPasswordDesc,
      'required' => array('userid', 'passwordreseturl'),
      'type' => 'system'
      );

$trigger_types['TRIGGER_WAITING_HELD_EMAIL'] =
array('name' => $strWaitingHeldEmail,
      'description' => $strTriggerNewHeldEmailMinsDesc,
      'required' => array('holdingmins', 'notifymins'),
      'params' => array('notifymins'),
      );


$trigger_types['TRIGGER_SERVICE_LIMIT'] =
array('name' => $strBillableIncidentApproved,
      'description' => $strBillableIncidentApprovedDesc,
      'required' => array('contractid', 'serviceremaining'),
      'params' => array('contractid', 'serviceremaining'),
      );

plugin_do('trigger_types');

// The pairing allows us to define which templates go with which triggers
$email_pair = array('TRIGGER_CONTACT_RESET_PASSWORD' => 'EMAIL_CONTACT_RESET_PASSWORD',
                    'TRIGGER_HOLIDAY_REQUESTED' => 'EMAIL_HOLIDAYS_REQUESTED',
                    'TRIGGER_INCIDENT_ASSIGNED' => 'EMAIL_INCIDENT_REASSIGNED_USER_NOTIFY',
                    'TRIGGER_INCIDENT_CLOSED' => 'EMAIL_INCIDENT_CLOSED_USER',
                    'TRIGGER_INCIDENT_CREATED' => 'EMAIL_INCIDENT_CREATED_USER',
                    'TRIGGER_INCIDENT_NEARING_SLA' => 'EMAIL_INCIDENT_NEARING_SLA',
                    'TRIGGER_INCIDENT_REVIEW_DUE' => 'EMAIL_INCIDENT_REVIEW_DUE',
                    'TRIGGER_INCIDENT_UPDATED_EXTERNAL' => 'EMAIL_INCIDENT_UPDATED_CUSTOMER',
                    'TRIGGER_INCIDENT_UPDATED_INTERNAL' => 'blank',
                    'TRIGGER_KB_CREATED' => 'EMAIL_KB_ARTICLE_CREATED',
                    'TRIGGER_LANGUAGE_DIFFERS' => 'blank',
                    'TRIGGER_NEW_CONTACT' => 'EMAIL_CONTACT_CREATED',
                    'TRIGGER_NEW_CONTRACT' => 'EMAIL_CONTRACT_ADDED',
                    'TRIGGER_NEW_HELD_EMAIL' => 'EMAIL_HELD_EMAIL_RECEIVED',
                    'TRIGGER_NEW_SITE' => 'EMAIL_SITE_CREATED',
                    'TRIGGER_NEW_USER' => 'EMAIL_USER_CREATED',
                    'TRIGGER_PORTAL_INCIDENT_REQUESTCLOSURE' => 'EMAIL_REQUEST_CLOSURE',
                    'TRIGGER_SCHEDULER_TASK_FAILED' => 'blank',
                    'TRIGGER_SIT_UPGRADED' => 'EMAIL_SIT_UPGRADED',
                    'TRIGGER_TASK_DUE' => 'blank',
                    'TRIGGER_USER_CHANGED_STATUS' => 'blank',
                    'TRIGGER_USER_RESET_PASSWORD' => 'EMAIL_USER_RESET_PASSWORD',
                    'TRIGGER_WAITING_HELD_EMAIL' => 'EMAIL_HELD_EMAIL_MINS',
                    'TRIGGER_SERVICE_LIMIT' => 'EMAIL_SERVICE_LEVEL');

$notice_pair = array('TRIGGER_INCIDENT_ASSIGNED' => 'NOTICE_INCIDENT_ASSIGNED',
                    'TRIGGER_INCIDENT_CLOSED' => 'NOTICE_INCIDENT_CLOSED',
                    'TRIGGER_INCIDENT_CREATED' => 'NOTICE_INCIDENT_CREATED',
                    'TRIGGER_INCIDENT_NEARING_SLA' => 'NOTICE_INCIDENT_NEARING_SLA',
                    'TRIGGER_INCIDENT_REVIEW_DUE' => 'NOTICE_INCIDENT_REVIEW_DUE',
                    'TRIGGER_INCIDENT_UPDATED_EXTERNAL' => 'blank',
                    'TRIGGER_INCIDENT_UPDATED_INTERNAL' => 'blank',
                    'TRIGGER_KB_CREATED' => 'NOTICE_KB_CREATED',
                    'TRIGGER_LANGUAGE_DIFFERS' => 'NOTICE_LANGUAGE_DIFFERS',
                    'TRIGGER_NEW_CONTACT' => 'NOTICE_NEW_CONTACT',
                    'TRIGGER_NEW_CONTRACT' => 'NOTICE_NEW_CONTRACT',
                    'TRIGGER_NEW_HELD_EMAIL' => 'NOTICE_NEW_HELD_EMAIL',
                    'TRIGGER_NEW_SITE' => 'NOTICE_NEW_SITE',
                    'TRIGGER_NEW_USER' => 'NOTICE_NEW_USER',
                    'TRIGGER_PORTAL_INCIDENT_REQUESTCLOSURE' => 'NOTICE_REQUEST_CLOSURE',
                    'TRIGGER_SCHEDULER_TASK_FAILED' => 'NOTICE_SCHEDULER_TASK_FAILED',
                    'TRIGGER_SIT_UPGRADED' => 'NOTICE_SIT_UPGRADED',
                    'TRIGGER_TASK_DUE' => 'NOTICE_TASK_DUE',
                    'TRIGGER_USER_CHANGED_STATUS' => 'NOTICE_USER_CHANGED_STATUS',
                    'TRIGGER_WAITING_HELD_EMAIL' => 'NOTICE_MINS_HELD_EMAIL',
                    'TRIGGER_SERVICE_LIMIT' => 'blank');


/**
 * Template variables (Alphabetical order)
 * description - Friendly label
 * replacement - Quoted PHP code to be run to perform the template var replacement
 * requires -Optional field. single string or array. Specifies the required params from the trigger that is needed for this replacement
 * action - Optional field, when set the var will only be available for that action
 * type - Optional field, defines where a variable can be used, system, incident or user
 */

$ttvararray['{applicationname}'] =
array('description' => $CONFIG['application_name'],
      'replacement' => '$CONFIG[\'application_name\'];'
      );

$ttvararray['{applicationpath}'] =
array('description' => $strSystemPath,
      'replacement' => '$CONFIG[\'application_webpath\'];'
      );

$ttvararray['{applicationshortname}'] =
array('description' => $CONFIG['application_shortname'],
      'replacement' => '$CONFIG[\'application_shortname\'];'
      );

$ttvararray['{applicationurl}'] =
array('description' => $strSystemUrl,
      'replacement' => 'application_url();'
      );

$ttvararray['{applicationversion}'] =
array('description' => $application_version_string,
      'replacement' => 'application_version_string();'
      );

$ttvararray['{approvaluseremail}'] =
array('description' => $strHolidayApproverEmail,
      'replacement' => '$param_array[\'approvaluseremail\'];',
      'requires' => 'approvaluseremail'
      );

$ttvararray['{awaitingclosure}'] =
array('description' => $strAwaitingClosureVar,
      'replacement' => '$param_array[\'awaitingclosure\'];',
      'requires' => 'incidentid',
      'show' => FALSE
      );

$ttvararray['{contactid}'] =
array('description' => $strContact,
      'requires' => 'incidentid',
      'replacement' => 'incident_contact($param_array[\'incidentid\']);',
      'checkreplace' => 'contact_drop_down',
      'show' => FALSE
      );

$ttvararray['{contactemail}'][] =
array('description' => $strIncidentsContactEmail,
      'requires' => 'contactid',
      'replacement' => 'contact_email($param_array[\'contactid\']);',
      'action' => 'ACTION_EMAIL'
      );

$ttvararray['{contactemail}'][] =
array('description' => $strIncidentsContactEmail,
      'requires' => 'incidentid',
      'replacement' => 'contact_email(incident_contact($param_array[\'incidentid\']));',
      'action' => 'ACTION_EMAIL'
      );

$ttvararray['{contactfirstname}'][] =
array('description' => $strContactsForename,
      'requires' => 'contactid',
      'replacement' => 'strtok(contact_realname($param_array[\'contactid\'])," ");'
      );

$ttvararray['{contactfirstname}'][] =
array('description' => $strContactsForename,
      'requires' => 'incidentid',
      'replacement' => 'strtok(contact_realname(incident_contact($param_array[\'incidentid\']))," ");'
      );

$ttvararray['{contactid}'][] =
array('description' => $strContact,
      'requires' => 'contactid',
      'replacement' => '$param_array[\'contactid\'];',
      'show' => FALSE
      );

$ttvararray['{contactid}'][] =
array('description' => $strContact,
      'requires' => 'incidentid',
      'replacement' => 'incident_contact($param_array[\'incidentid\']);',
      'show' => FALSE
      );

$ttvararray['{contactname}'][] =
array('description' => $strContactsName,
      'requires' => 'contactid',
      'replacement' => 'contact_realname($param_array[\'contactid\']);'
      );

$ttvararray['{contactname}'][] =
array('description' => $strContactsName,
      'requires' => 'incidentid',
      'replacement' => 'contact_realname(incident_contact($param_array[\'incidentid\']));'
      );

$ttvararray['{contactnotify}'] =
array('description' => $strNotifyContactEmail,
      'requires' => 'contactid',
      'replacement' => 'contact_notify_email($param_array[\'contactid\']);'
      );

$ttvararray['{contactphone}'][] =
array('description' => $strContactsPhone,
      'requires' => 'contactid',
      'replacement' => 'contact_phone($param_array[\'contactid\']);'
      );

$ttvararray['{contactphone}'][] =
array('description' => $strContactsPhone,
      'requires' => 'incidentid',
      'replacement' => 'contact_phone(incident_contact($param_array[\'incidentid\']));'
      );

$ttvararray['{contactusername}'] =
array('description' => $strContactsUsername,
      'requires' => 'contactid',
      'replacement' => 'contact_username($param_array[\'contactid\']);'
      );

$ttvararray['{contractid}'][] =
array('description' => $strContract,
      'requires' => 'contractid',
      'replacement' => '$param_array[\'contractid\'];',
      'checkreplace' => 'maintenance_drop_down',
      'show' => FALSE
      );

$ttvararray['{contractid}'][] =
array('description' => $strContract,
      'requires' => 'contractid',
      'replacement' => 'incident_owner($param_array[\'incidentid\']);',
      'checkreplace' => 'maintenance_drop_down',
      'show' => FALSE
      );

$ttvararray['{contractproduct}'] =
array('description' => $strContractProduct,
      'replacement' => 'contract_product($param_array[\'contractid\']);',
      'requires' => 'contractid'
      );

$ttvararray['{contractsla}'] =
array('description' => $strContractsSLA,
      'replacement' => 'maintenance_servicelevel_tag($param_array[\'contractid\']);',
      'requires' => 'contractid'
      );

$ttvararray['{currentlang}'] =
array('description' => $strCurrentLanguage,
      'replacement' => '$param_array[\'currentlang\'];',
      'requires' => 'currentlang'
      );

$ttvararray['{emaildetails}'] =
array('show' => FALSE,
      'replacement' => '$param_array[\'emaildetails\'];',
      );

$ttvararray['{feedbackurl}'] =
array('description' => $strFeedbackURL,
      'requires' => 'incidentid',
      'replacement' => 'application_url().\'feedback.php?ax=\'. feedback_hash($CONFIG[\'feedback_form\'], incident_contact($param_array[\'incidentid\']), $param_array[\'incidentid\'], contact_email(incident_contact($param_array[\'incidentid\'])));'
      );

$ttvararray['{feedbackoptout}'] =
array('description' => $strFeedbackOptOutURL,
      'requires' => 'incidentid',
      'replacement' => 'application_url().\'feedback.php?ou=\'. feedback_opt_out_hash(incident_contact($param_array[\'incidentid\']), contact_email(incident_contact($param_array[\'incidentid\'])));'
      );


$ttvararray['{formattedtime}'][] =
array('description' => 'Outputs a formatted time, e.g. 2 minutes, 1 hour etc.',
      'replacement' => 'format_seconds($param_array[holdingmins] * 60);',
      'requires' => 'holdingmins'
      );

$ttvararray['{globalsignature}'] =
array('description' => $strGlobalSignature,
      'replacement' => 'global_signature();'
      );

// $ttvararray['{holdingemailid}'] =
// array('description' => 'ID of the new email in the holding queue',
//       'replacement' => '$param_array[\'holdingemailid\'];',
//       'requires' => 'holdingemailid',
//       'show' => FALSE
//       );

$ttvararray['{holdingmins}'] =
array('description' => $strHoldingQueueMinutes,
      'replacement' => '$param_array[\'holdingmins\'];',
      'requires' => 'holdingmins'
      );

$ttvararray['{incidentassigner}'] =
array('description' => $strIncidentAssigner,
      'replacement' => '$param_array[\'incidentassigner\'];',
      'checkreplace' => 'user_drop_down'
      );

$ttvararray['{incidentccemail}'] =
array('description' => $strIncidentCCList,
      'requires' => 'incidentid',
      'replacement' => 'incident_ccemail($param_array[\'incidentid\']);'
      );

$ttvararray['{incidentexternalemail}'] =
array('description' => $strExternalEngineerEmail,
      'requires' => 'incidentid',
      'replacement' => 'incident_externalemail($param_array[incidentid]);'
      );

$ttvararray['{incidentexternalengineer}'] =
array('description' => $strExternalEngineer,
      'requires' => 'incidentid',
      'replacement' => 'incident_externalengineer($param_array[incidentid]);'
      );

$ttvararray['{incidentexternalengineerfirstname}'] =
array('description' => $strExternalEngineersFirstName,
      'requires' => 'incidentid',
      'replacement' => 'strtok(incident_externalengineer($param_array[\'incidentid\']), " ");'
      );

$ttvararray['{incidentexternalid}'] =
array('description' => $strExternalID,
      'requires' => 'incidentid',
      'replacement' => 'incident_externalid($param_array[\'incidentid\']);'
      );

$ttvararray['{incidentcustomerid}'] =
array('description' => $strCustomerReference,
      'requires' => 'incidentid',
      'replacement' => 'incident_customerid($param_array[\'incidentid\']);'
);

$ttvararray['{incidentid}'] =
array('description' => $strIncident,
      'requires' => 'incidentid',
      'replacement' => 'get_userfacing_incident_id_email($param_array[\'incidentid\']);',
      'checkreplace' => 'incident_drop_down'
      );

$ttvararray['{incidentowner}'] =
array('description' => $strIncidentOwnersFullName,
      'requires' => 'incidentid',
      'replacement' => 'user_realname(incident_owner($param_array[incidentid]));'
      );

$ttvararray['{incidentowneremail}'] =
array('description' => $strIncidentOwnersEmail,
      'requires' => 'incidentid',
      'replacement' => 'user_email(incident_owner($param_array[incidentid]));'
      );

$ttvararray['{incidentpriority}'] =
array('description' => $strIncidentPriority,
      'requires' => 'incidentid',
      'replacement' => 'priority_name(incident_priority($param_array[incidentid]));',
      );

$ttvararray['{incidentpriorityid}'] =
array('description' => $strIncidentPriority,
      'requires' => 'incidentid',
      'replacement' => 'incident_priority($param_array[incidentid]);',
      'show' => FALSE,
      'checkreplace' => 'freeform'
      );

$ttvararray['{incidentsoftware}'] =
array('description' => $strSkillAssignedToIncident,
      'requires' => 'incidentid',
      'replacement' => 'software_name(db_read_column(\'softwareid\', $GLOBALS[\'dbIncidents\'], $param_array[\'incidentid\']));'
      );

$ttvararray['{incidenttitle}'] =
array('description' => $strIncidentTitle,
      'requires' => 'incidentid',
      'replacement' => 'incident_title($param_array[incidentid]);'
      );

$ttvararray['{kbid}'] =
array('description' => $strKBArticle,
      'requires' => 'kbid',
      'replacement' => '$param_array[\'kbid\'];'
      );

$ttvararray['{kbprefix}'] =
array('description' => $CONFIG['kb_id_prefix'],
      'requires' => array(),
      'replacement' => '$CONFIG[\'kb_id_prefix\'];'
    );

$ttvararray['{kbtitle}'] =
array('description' => $strKnowledgeBase,
      'requires' => 'kbid',
      'replacement' => 'kb_name($param_array[\'kbid\']);'
      );

$ttvararray['{listofholidays}'] =
array('description' => $strListOfHolidays,
      'replacement' => '$param_array[\'listofholidays\'];',
      'requires' => 'listofholidays'
      );

$ttvararray['{nextslatime}'] =
array('description' => $strTimeToNextAction,
      'replacement' => 'format_date_friendly($param_array[\'nextslatime\']);',
      'requires' => 'nextslatime'
      );

$ttvararray['{nextsla}'] =
array('description' => $strNextSLATarget,
      'replacement' => '$param_array[\'nextsla\'];',
      'requires' => 'nextsla'
      );

$ttvararray['{notifycontact}'] =
array('description' => $strNotifyContactOnClose,
      'replacement' => '$param_array[\'notifycontact\'];',
      'requires' => 'incidentid',
      'show' => FALSE
      );

$ttvararray['{notifyexternal}'] =
array('description' => $strNotifyExternalEngineerOnClose,
      'replacement' => '$param_array[\'notifyexternal\'];',
      'requires' => 'incidentid',
      'show' => FALSE
      );

$ttvararray['{notifymins}'] =
array('description' => $strNotifyMinutes,
      'replacement' => '$param_array[\'notifymins\'];',
      'requires' => 'notifymins',
      'show' => TRUE
      );

$ttvararray['{ownerid}'] =
array('description' => $strIncidentOwner,
      'replacement' => 'incident_owner($param_array[\'incidentid\']);',
      'requires' => 'incidentid',
      'checkreplace' => 'user_drop_down',
      'show' => FALSE
      );

$ttvararray['{passwordreseturl}'] =
array('description' => $strPasswordResetURL,
      'replacement' => '$param_array[\'passwordreseturl\'];',
      'requires' => 'passwordreseturl',
      'type' => 'system'
      );

$ttvararray['{prepassword}'] =
array('description' => $strContactsPassword,
      'replacement' => '$param_array[\'prepassword\'];',
      'requires' => 'prepassword',
      'type' => 'system'
      );

$ttvararray['{profilelang}'] =
array('description' => $strProfileLanguage,
      'replacement' => '$param_array[\'profilelang\'];',
      'requires' => 'profilelang'
      );

$ttvararray['{salesperson}'] =
array('description' => $strSalesperson,
      'requires' => 'siteid',
      'replacement' => 'user_realname(db_read_column(\'owner\', $GLOBALS[\'dbSites\'], $param_array[\'siteid\']));'
      );

$ttvararray['{salespersonid}'] =
array('description' => $strSalesperson,
      'requires' => 'siteid',
      'show' => FALSE,
      'replacement' => 'db_read_column(\'owner\', $GLOBALS[\'dbSites\'], incident_site($param_array[\'incidentid\']));',
      'checkreplace' => 'user_drop_down'
      );

$ttvararray['{salespersonemail}'][] =
array('description' => $strSalespersonAssignedToContactsSiteEmail,
      'requires' => 'siteid',
      'replacement' => 'user_email(db_read_column(\'owner\', $GLOBALS[\'dbSites\'], $param_array[\'siteid\']));'
      );

$ttvararray['{salespersonemail}'][] =
array('description' => $strSalespersonAssignedToContactsSiteEmail,
      'requires' => 'contractid',
      'replacement' => 'user_email(db_read_column(\'owner\', $GLOBALS[\'dbSites\'], maintenance_siteid($param_array[\'contractid\'])));'
      );

$ttvararray['{schedulertask}'] =
array('description' => $strScheduledTask,
      'replacement' => '$param_array[\'schedulertask\'];'
    );

$ttvararray['{sendemail}'] =
array('description' => $strSendOpeningEmailDesc,
      'replacement' => '$param_array[\'sendemail\'];',
      'show' => FALSE
    );

$ttvararray['{sendfeedback}'] =
array('description' => $strEmailSendFeedbackDesc,
      'replacement' => '$param_array[\'sendfeedback\']',
      'show' => FALSE);

$ttvararray['{serviceremaining}'] =
array('description' => $strServiceBalanceInfo,
      'requires' => 'contractid',
      'replacement' => 'get_service_percentage($param_array[\'contractid\']);',
      'show' => FALSE
    );

$ttvararray['{serviceremainingstring}'] =
array('description' => $strServiceBalanceString,
      'requires' => 'contractid',
      'replacement' => '(get_service_percentage($param_array[\'contractid\']) * 100)."%";',
    );

$ttvararray['{signature}'] =
array('description' => $strCurrentUsersSignature,
      'replacement' => 'user_signature($_SESSION[\'userid\']);'
      );

$ttvararray['{siteid}'][] =
array('description' => $strSite,
      'requires' => 'siteid',
      'replacement' => '$param_array[\'siteid\'];',
      'checkreplace' => 'site_drop_down',
      'show' => FALSE
      );

$ttvararray['{siteid}'][] =
array('description' => $strSite,
      'requires' => 'incidentid',
      'replacement' => 'contact_site(incident_contact($param_array[\'incidentid\']));',
      'checkreplace' => 'site_drop_down',
      'show' => FALSE
      );

$ttvararray['{sitename}'][] =
array('description' => $strSiteName,
      'requires' => 'incidentid',
      'replacement' => 'contact_site(incident_contact($param_array[\'incidentid\']));'
      );

$ttvararray['{sitename}'][] =
array('description' => $strSiteName,
      'requires' => 'contactid',
      'replacement' => 'contact_site($param_array[\'contactid\']);'
      );

$ttvararray['{sitename}'][] =
array('description' => $strSiteName,
      'requires' => 'contractid',
      'replacement' => 'contract_site($param_array[\'contractid\']);'
      );

$ttvararray['{sitename}'][] =
array('description' => $strSiteName,
      'requires' => 'siteid',
      'replacement' => 'site_name($param_array[\'siteid\']);'
      );

$ttvararray['{sitesalespersonid}'] =
array('description' => $strSalesperson,
      'replacement' => 'site_salespersonid($param_array[\'siteid\']);',
      'requires' => 'siteid',
      'show' => FALSE
      );

$ttvararray['{sitesalesperson}'] =
array('description' => $strSalespersonSite,
      'replacement' => 'site_salesperson($param_array[\'siteid\']);',
      'requires' => 'siteid'
      );

$ttvararray['{slaactionplan}'] =
array('description' => $strActionPlanSLA,
      'replacement' => 'incident_sla($param_array[\'incidentid\'], \'action_plan\');',
      'requires' => 'incidentid'
      );

$ttvararray['{slainitialresponse}'] =
array('description' => $strInitialResponseSLA,
      'replacement' => 'incident_sla($param_array[\'incidentid\'], \'initial_response\');',
      'requires' => 'incidentid'
      );

$ttvararray['{slaproblemdefinition}'] =
array('description' => $strProblemDefinitionSLA,
      'replacement' => 'incident_sla($param_array[\'incidentid\'], \'prob_determ\');',
      'requires' => 'incidentid'
      );

$ttvararray['{slatag}'] =
array('description' => $strSLA,
      'replacement' => 'maintenance_servicelevel_tag($param_array[\'contractid\']);',
      'requires' => 'contractid'
      );

$ttvararray['{slaresolutionreprioritisation}'] =
array('description' => $strResolutionReprioritisationSLA,
      'replacement' => 'incident_sla($param_array[\'incidentid\'], \'resolution\');',
      'requires' => 'incidentid'
      );

if ($CONFIG['support_email_tags'] === TRUE)
{
    $ttvararray['{supportemail}'] =
    array('description' => $strSupportEmailAddress,
        'replacement' => 'tag_email_address($CONFIG[\'support_email\'], $param_array[\'incidentid\']);',
        'requires' => 'incidentid'
        );
}
else
{
    $ttvararray['{supportemail}'] =
    array('description' => $strSupportEmailAddress,
        'replacement' => '$CONFIG[\'support_email\'];'
        );
}


$ttvararray['{supportmanageremail}'] =
array('description' => $strSupportManagersEmailAddress,
      'replacement' => 'user_email($CONFIG[\'support_manager\']);'
      );

$ttvararray['{taskid}'] =
array('description' => $strTask,
      'replacement' => '$param_array[\'taskid\']',
      'show' => FALSE
    );

$ttvararray['{todaysdate}'] =
array('description' => $strCurrentDate,
      'replacement' => 'ldate("jS F Y");'
      );

$ttvararray['{townerid}'] =
array('description' => $strTemporaryOwner,
      'replacement' => 'incident_towner($param_array[\'incidentid\']);',
      'requires' => 'incidentid',
      'checkreplace' => 'user_drop_down',
      'show' => FALSE
      );

$ttvararray['{triggersfooter}'] =
array('description' => $strTriggersFooter,
      'replacement' => '$SYSLANG[\'strTriggerFooter\'];',
      'requires' => ''
    );

$ttvararray['{triggeruseremail}'] =
array('description' => $strTriggerUserEmail,
      'replacement' => 'user_email($param_array[\'triggeruserid\']);'
      );

$ttvararray['{updateid}'] =
array('description' => 'The ID of the update',
      'replacement' => 'incoming_email_update_id($param_array[\'holdingemailid\']);',
      'requires' => 'holdingemailid',
      'show' => FALSE
      );

$ttvararray['{updatetext}'] =
array('description' => 'By default, the text of the last update to an incident, the numupdates parameter can be set to an integer or -1 for all updates',
      'replacement' => 'readable_last_updates($param_array[\'incidentid\'], $param_array[\'numupdates\']);',
      'requires' => 'incidentid',
      );


$ttvararray['{useraccepting}'] =
array('description' => $strAcceptingIncidents,
      'replacement' => 'user_accepting_status($param_array[\'userid\']);',
      'requires' => 'userid',
      'show' => FALSE
      );

$ttvararray['{useremail}'] =
array('description' => $strCurrentUserEmailAddress,
      'replacement' => 'user_email($param_array[\'userid\']);'
      );

$ttvararray['{userid}'][] =
array('description' => $strUser,
      'replacement' => '$param_array[\'userid\'];',
      'checkreplace' => 'user_drop_down',
      'show' => FALSE
      );

$ttvararray['{userid}'][] =
array('replacement' => 'task_owner($param_array[\'taskid\']);',
      'requires' => 'taskid',
      'show' => FALSE
      );

$ttvararray['{userrealname}'] =
array('description' => $strFullNameCurrentUser,
      'replacement' => 'user_realname($GLOBALS[\'sit\'][2]);'
      );

$ttvararray['{userstatus}'] =
array('description' => $strUserStatus,
      'replacement' => 'user_status_name($param_array[\'userid\']);',
      'requires' => 'userid',
      'checkreplace' => 'userstatus_drop_down'
      );

plugin_do('trigger_variables');


/**
 * Displays a <select> with the list of email templates
 * @author Kieran Hogg, Ivan Lucas
 * @param $name string. The name for the select
 * @param $triggertype string (optional). The type of trigger (incident, system...)
 * @param $selected string (optional). The name of the selected item
 * @return string. HTML snippet
 */
function email_templates($name, $triggertype = 'system', $selected = '')
{
    global $dbEmailTemplates, $dbTriggers, $strPersonalTemplates, $strNoResults;
    $html .= "<select id='{$name}' name='{$name}'>";

    foreach (array($triggertype, 'usertemplate') as $type)
    {
        if ($type == 'usertemplate')
        {
            $html .= "<option disabled='disabled'></option><option disabled='disabled'>=== {$strPersonalTemplates} ===</option>";
        }

        $sql = "SELECT id, name, description FROM `{$dbEmailTemplates}` ";
        $sql .= "WHERE type='{$type}' ORDER BY name";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) > 0)
        {
            while ($template = mysql_fetch_object($result))
            {
                //$name = strpos()
                //$name = str_replace("_", " ", $name);
                $name = strtolower($name);
                $html .= "<option id='{$template->name}' value='{$template->name}'>{$GLOBALS[$template->description]} ({$template->name})</option>\n";
                //$html .= "<option disabled='disabled' style='color: #333; text-indent: 10px;' value='{$template->name}'>".$GLOBALS[$template->description]."</option>\n";

            }
        }
        else
        {
            $html .= "<option disabled='disabled'>{$strNoResults}</option>";
        }

    }
    $html .= "</select>\n";
    return $html;
}


/**
 * Displays a <select> with the list of notice templates
 * @author Kieran Hogg
 * @param string $name. The name for the select element
 * @param string $selected (optional). The name of the selected item
 * @returns string HTML
 */
function notice_templates($name, $selected = '')
{
    global $dbNoticeTemplates, $strPersonalTemplates;
    $html .= "<select id='{$name}' name='{$name}'>";
    $sql = "SELECT id, name, description, type FROM `{$dbNoticeTemplates}` ORDER BY type,name ASC";
    $query = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    while ($template = mysql_fetch_object($query))
    {
        $user_header = false;
        if (!$user_header AND $template->type == USER_DEFINED_NOTICE_TYPE)
        {
            $user_header = true;
            $html .= "<option></option><option>=== {$strPersonalTemplates} ===</option>";
        }
        $html .= "<option id='{$template->name}' value='{$template->name}'>{$GLOBALS[$template->description]} ({$template->name})</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * Actually do the replacement, used so we can define variables more than once
 * @author Kieran Hogg
 * @param string $trigger_type
 * @param array &$ttvar the array of the variable to replace
 * @param string &$identifier the {variable} name
 * @param array $param_array
 * @param array &$required  optional array of required vars to pass, used if
 * we're not dealing with a trigger
 * @return mixed array if replacement found, NULL if not
 */
function replace_vars($trigger_type, &$ttvar, &$identifier, $param_array, $required = '')
{
    global $trigger_types, $ttvararray, $CONFIG;

    $usetvar = FALSE;

    //if we don't have any requires, we can already use this var
    if (empty($ttvar['requires']))
    {
        $usetvar = TRUE;
    }
    else
    {
        //otherwise we need to check all the requires
        if (!is_array($ttvar['requires']))
        {
            $ttvar['requires'] = array($ttvar['requires']);
        }
        //compare the trigger 'provides' with the var 'requires'
        foreach ($ttvar['requires'] as $needle)
        {
            if (is_array($required))
            {
                if (in_array($needle, $required))
                {
                    $usetvar = TRUE;
                }
            }
            else
            {
                if (in_array($needle, $trigger_types[$trigger_type]['required']))
                {
                    $usetvar = TRUE;
                }
            }
        }
    }

    //if we're able to use this variable
    if ($usetvar)
    {
        // debug_log("Using $identifier");
        $trigger_regex = "/{$identifier}/s";
        if (!empty($ttvar['replacement']))
        {
            $eresult = eval("\$res = {$ttvar[replacement]};return TRUE;");
            if (!$eresult)
            {
                trigger_error("Error in variable replacement for
                        <strong>{$identifier}</strong>, check that
                        this variable is available for the template
                        that uses it.", E_USER_WARNING);

                debug_log("replacement: {$ttvar[replacement]}", TRUE);
            }
        }

        $trigger_replace = $res;
        unset($res);
        return array('trigger_replace' => $trigger_replace,
                'trigger_regex' => $trigger_regex);
    }
}


/**
 * Replaces template variables with their values
 * @author Ivan Lucas
 * @param string $string. The string containing the variables
 * @param string $param_array An array containing values to be substituted
 * @return string The string with variables replaced
 */
function replace_specials($string, $param_array)
{
    global $CONFIG, $dbg, $dbIncidents, $ttvararray;

    //manual variables
    $required = array('incidentid');

    //this loops through each variable and creates an array of useable variables' regexs
    foreach ($ttvararray AS $identifier => $ttvar)
    {
        $multiple = FALSE;
        foreach ($ttvar AS $key => $value)
        {
            //this checks if it's a multiply-defined variable
            if (is_numeric($key))
            {
                $trigger_replaces = replace_vars($triggerid, $ttvar[$key], $identifier, $param_array, $required);

                if (!empty($trigger_replaces))
                {
                    $trigger_regex[] = $trigger_replaces['trigger_regex'];
                    $trigger_replace[] = $trigger_replaces['trigger_replace'];
                }
                $multiple = TRUE;
            }
        }
        if ($multiple == FALSE)
        {
            $trigger_replaces = replace_vars($triggerid, $ttvar, $identifier, $param_array, $required);
            if (!empty($trigger_replaces))
            {
                $trigger_regex[] = $trigger_replaces['trigger_regex'];
                $trigger_replace[] = $trigger_replaces['trigger_replace'];
            }
        }
    }

    return preg_replace($trigger_regex, $trigger_replace, $string);
}


/**
 * Replaces template variables with their values
 * @author Kieran Hogg, Ivan Lucas
 * @param string $trigger_type
 * @param $string_array The string containing the variables
 * @param $param_array
 * @return string The string with variables replaced
 */
function trigger_replace_specials($trigger_type, $string_array, $param_array)
{
    global $CONFIG, $application_version, $application_version_string, $dbg;
    global $dbIncidents;
    global $trigger_types, $ttvararray;

    //this loops through each variable and creates an array of useable varaibles' regexs
    foreach ($ttvararray AS $identifier => $ttvar)
    {
        $multiple = FALSE;
        foreach ($ttvar AS $key => $value)
        {
            //this checks if it's a multiply-defined variable
            if (is_numeric($key))
            {
                $trigger_replaces = replace_vars($trigger_type, $ttvar[$key], $identifier, $param_array);
                if (!empty($trigger_replaces))
                {
                    $trigger_regex[] = $trigger_replaces['trigger_regex'];
                    $trigger_replace[] = $trigger_replaces ['trigger_replace'];
                }
                $multiple = TRUE;
            }
        }
        if ($multiple == FALSE)
        {
            $trigger_replaces = replace_vars($trigger_type, $ttvar, $identifier, $param_array);

            if (!empty($trigger_replaces))
            {
                $trigger_regex[] = $trigger_replaces['trigger_regex'];
                $trigger_replace[] = $trigger_replaces['trigger_replace'];
            }
        }
    }
    $string = preg_replace($trigger_regex, $trigger_replace, $string_array);
    return $string;
}


/**
 * Formats a human readable description of a trigger
 * @author Ivan Lucas
 * @param $triggervar array. An individual trigger array
 * @return HTML
 */
// function trigger_description($triggervar)
// {
//     global $CONFIG, $iconset;
//     $html = ''.icon('trigger', 16)." ";
//     $html .= "<strong>";
//     if (!empty($triggervar['name'])) $html .= "{$triggervar['name']}";
//     else $html .= "{$GLOBALS['strUnknown']}";
//     $html .= "</strong><br />\n";
//     if (isset($triggervar['description']))
//     {
//         $html .= $triggervar['description'];
//     }
//     else
//     {
//         $html .=  $triggervar['description'];
//     }
//     return $html;
// }


/**
 * Return as associative array with a trigger's properties
 * @author Kieran Hogg
 * @param object $trigger a Trigger object
 * @return array
 */
function trigger_to_array($trigger)
{
    $array['trigger_type'] = $trigger->getTrigger_type();
    $array['param_array'] = $trigger->getParam_array();
    $array['user_id'] = $trigger->getUser_id();
    $array['template'] = $trigger->getTemplate();
    $array['action'] = $trigger->getAction();
    $array['checks'] = $trigger->getChecks();
    $array['parameters'] = $trigger->getParameters();

    return $array;
}


/**
 * Return HTML for a table listing triggers
 * @author Kieran Hogg
 * @param int $user_id
 * @param int $trigger_id (optional)
 * @return string HTML
 */
function triggers_to_html($user_id, $trigger_id = '')
{
    global $dbTriggers, $sit, $trigger_types, $strTrigger, $strActions;

    $user_id = cleanvar($user_id);
    if ($user_id == '') $user_id = $sit[2];
    $trigger_id = cleanvar($trigger_id);

    $html = "<table id='trigger_list'>";
    $html .= "<tr><th>{$strTrigger}</th><th>{$strActions}</th></tr>";
    $i = 0;
    foreach ($trigger_types AS $trigger => $description)
    {
        $trigger_html = trigger_to_html($trigger, $user_id);
        if (!empty($trigger_html))
        {
            $shade = ($i % 2) + 1;
            $html .= "<tr class='shade{$shade}'><td>".icon('trigger', 16);
            $html .= " ".$description['description']."</td><td><div class='triggeraction'>";
            $html .= $trigger_html;
            $html .= "</div></td></tr>";
            $i++;
        }
    }
    $html .= "</table>";
    return $html;
}


/**
 * Return HTML to describe a trigger
 * @author Kieran Hogg
 * @param int $trigger_id - Trigger ID
 * @param int $user_id
 * @return string HTML
 */
function trigger_to_html($trigger_id, $user_id)
{
    global $dbTriggers;
    $html = '';
    $sql = "SELECT id FROM `{$dbTriggers}` ";
    $sql .= "WHERE userid = '{$user_id}' ";
    $sql .= "AND triggerid = '{$trigger_id}'";
    $result = mysql_query($sql);
    if (mysql_error())
    {
        trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        trigger_error("Problem getting trigger details for {$trigger_id}");
        return FALSE;
    }
    if (mysql_num_rows($result) >= 0)
    {
        while ($row = mysql_fetch_object($result))
        {
            $t = Trigger::fromID($row->id);
            $html .= trigger_action_to_html($t, $user_id);
        }
    }

    return $html;
}


/**
 * Return HTML to describe a trigger action
 * @author Kieran Hogg
 * @param object $trigger - Trigger Object
 * @param int $user - Optional User to redirect to page
 * @return string HTML
 */
function trigger_action_to_html($trigger, $user_id)
{
    global $trigger_types, $actionarray, $strChecks, $strParameters, $strMore, $strLess, $strEllipsis;
    $t_array = trigger_to_array($trigger);
    switch ($t_array['action'])
    {
        case 'ACTION_JOURNAL':
            $action = icon('configure', 16)." ";
            $action .= $GLOBALS['strLogTriggerInJournal'];
            break;

        case 'ACTION_NOTICE':
            $action = icon('info', 16)." ";
            $action .= $GLOBALS['strCreateANotice'];
            break;

        case 'ACTION_EMAIL':
            $action = icon('email', 16). " ";
            $action .= $GLOBALS['strSendAnEmail'];
            break;

        default:
            $action = $GLOBALS['strUnknown'];
            plugin_do('trigger_action_html');
        break;
    }

    $html .= $action;

    if (!empty($t_array['template']))
    {
        $html .= " <a href='templates.php?id={$t_array['template']}'>";
        $html .= "{$t_array['template']}</a> ";
        $desc = template_description($t_array['template'], $t_array['action']);
        if ($desc != '')
        {
            $html .= "<small>({$desc})</small><br />";
        }
    }

    if ($t_array['checks'] != '' OR $t_array['parameters'] != '')
    {
        $html .= "<span id='more_checks{$trigger->id}'>";
        $html .= "<a href='javascript:void(0)' ";
        $html .= "onclick=\"javascript:$('checksandparams{$trigger->id}').show(); $('less_checks{$trigger->id}').show(); $('more_checks{$trigger->id}').hide()\">";
        $html .= icon('auto', 16) ." {$strMore}{$strEllipsis}</a></span> ";

        $html .= "<span id='less_checks{$trigger->id}' style='display:none'>";
        $html .= "<a href='javascript:void(0)' ";
        $html .= "onclick=\"javascript:$('checksandparams{$trigger->id}').hide(); $('less_checks{$trigger->id}').hide(); $('more_checks{$trigger->id}').show()\">";
        $html .= icon('auto', 16) ." {$strLess}{$strEllipsis}</a></span> ";

        $html .= "<span id='checksandparams{$trigger->id}' style='display:none'>";
        if ($t_array['checks'] != '')
        {
            $html .= "<strong>{$strChecks}</strong>: ";
            //FIXME 4.0
            //$html .= checks_to_html($t_array['checks'])." ".help_link('trigger_checks')." ";
            $html .= $t_array['checks']." ".help_link('trigger_checks')." ";

        }
        if ($t_array['parameters'] != '')
        {
            // FIXME i18n
            $html .= "<strong>Parameters</strong>: {$t_array['parameters']} ".help_link('trigger_parameters')." ";
        }
        $html .= "</span>";
    }

    $html .=  "<div class='triggeractions'>";
    $operations = array();
    // FIXME 3.90, add edit back in
    // $operations[$GLOBALS['strEdit']] = "action_details.php?id={$trigger->id}";
    if ($user_id == 0)
    {
        $userurl = "&amp;user=admin";
    }
    else
    {
        $userurl = "";
    }

    $operations[$GLOBALS['strDelete']] = "action_details.php?action=delete&amp;id={$trigger->id}$userurl";
    $html .= html_action_links($operations);
    $html .= "</div><br />";
    return $html;
}


/**
 * Return HTML the description of a given template
 * @author Kieran Hogg
 * @param string $name - Template name
 * @param $type - Template type
 * @return string HTML
 */
function template_description($name, $type)
{
    global $dbEmailTemplates, $dbNoticeTemplates;
    $name = cleanvar($name);
    if ($type == 'ACTION_NOTICE')
    {
        $tbl = $dbNoticeTemplates;
    }
    elseif ($type == 'ACTION_EMAIL')
    {
        $tbl = $dbEmailTemplates;
    }
    $sql = "SELECT description FROM `{$tbl}` WHERE name = '{$name}'";
    $result = mysql_query($sql);
    list($desc) = mysql_fetch_row($result);
    (substr_compare($desc, "str", 1, 3)) ? $desc = $GLOBALS[$desc] : $desc;
    if ($desc == '') $desc = FALSE;
    return $desc;
}


/**
 * Provides a drop down list of matching functions
 * @param string $id the HTML ID attribute to give the <select>
 * @param string $name the name attribute to give the <select>
 */
function check_match_drop_down($id = '')
{
    $html = "<select id='{$id}' name='{$id}'>";
    $html .= "<option value='is'>{$GLOBALS['strIs']}</option>";
    $html .= "<option value='is not'>{$GLOBALS['strIsNot']}</option>";
    $html .= "<option value='contains'>{$GLOBALS['strContains']}</option>";
    $html .= "<option value='does not contain'>{$GLOBALS['strDoesNotContain']}</option>";
    $html .= "</select>";

    return $html;
}


/**
 * Creates a trigger check string from an array of HTML elements
 * @param array $param the parameter names
 * @param array $value the values of the parameters
 * @param array $join the 'is', 'is not' selection
 * @param array $enabled the status of the checkbox
 * @param array $conditions whether to use 'all' or 'any' of the conditions
 */
function create_check_string($param, $value, $join, $enabled, $conditions)
{
    $param_count = sizeof($param);

    for ($i = 0; $i < $param_count; $i++)
    {
        if ($enabled[$i] == 'on')
        {
            $checks[$i] = "{".$param[$i]."}";
            if ($join[$i] == 'is') $checks[$i] .= "==";
            elseif ($join[$i] == 'is not') $checks[$i] .= "!=";
            elseif ($join[$i] == 'contains') trigger_error("Contains not yet supported");
            elseif ($join[$i] == 'does not contain') trigger_error("Contains not yet supported");
            $checks[$i] .= $value[$i];
        }
    }

    $check_count = sizeof($checks);
    if ($check_count > 0)
    {
        foreach ($checks as $key => $value)
        {
            $final_check .= $checks[$key];
            if ($check_count != 1)
            {
                if ($conditions == 'all')
                {
                    $final_check .= " AND ";
                }
                else
                {
                    $final_check .= " OR ";
                }
            }
            $check_count --;
        }
    }

    return $final_check;
}


/**
 * Returns HTML human readable listing of trigger checks (rules)
 * @author Kieran Hogg
 * @param string $checks
 * @returns string HTML
 * @todo FIXME 4.0
 */
function checks_to_html($checks)
{
    $checks = trim($checks);
    if ($checks != '')
    {
        if (strpos($checks, 'AND') !== FALSE)
        {
            $checks = explode('AND', $checks);
        }
        elseif (strpos($checks, 'OR') !== FALSE)
        {
            $checks = explode('OR', $checks);
        }
        else
        {
            $checks[0] = $checks;
        }
        $html = "";
        foreach ($checks as $check)
        {
            $original_check = $check;
            if (strpos($check, '==') !== FALSE)
            {
                $check = explode('==', $check);
                $check[0] = trim($check[0]);
                $check[1] = trim($check[1]);
            }
            elseif (strpos($check, '!=') !== FALSE)
            {
                $check = explode('!=', $check);
                $check[0] = trim($check[0]);
                $check[1] = trim($check[1]);
            }
            else
            {
                trigger_error('not yet supported', E_USER_ERROR);
                $html .= $original_check;
            }

            if ($ttvararray[$check[0]]['checkreplace'] != '')
            {
                $html .= $ttvararray[$check[0]]['checkreplace']();
            }
            else
            {
                $html .= $original_check;
            }
        }
    }
    return $html;
}

/**
 * @author Kieran Hogg
 * @todo FIXME This is unused and does nothing. was it supposed to do something? INL 24 June 2011
 */
function freeform($name)
{
    $html = "<input name='name' />";
    return $html;
}

/**
 * @deprecated DEPRECATED trigger() function, use the TriggerEvent class instead
 * @TODO remove after 4.0
 */
function trigger($trigger_id, $param_array)
{
    trigger_error("trigger() is deprecated, please use the TriggerEvent class instead", E_USER_DEPRECATED);
    new TriggerEvent($trigger_id, $param_array);
}
?>