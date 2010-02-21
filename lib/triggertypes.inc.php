<?php
// triggertypes.inc.php - Create the trigger definitions
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Kieran Hogg <kieran[at]sitracker.org>

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


// Define a list of available triggers, trigger() will need to be called in the appropriate
// place in the code for each of these
//
// id - trigger name
// description - when the trigger is fired
// required - parameters the triggers needs to fire, 'provides' these to templates
// params - Rules the trigger can check, mimics 'subscription'-type events
// type - Trigger type (eg. incident, contact etc)

$triggerarray['TRIGGER_CONTACT_RESET_PASSWORD'] =
array('name' => $strContactResetPassword,
      'description' => $strTriggerContactResetPasswordDesc,
      'required' => array('contactid', 'passwordreseturl'),
      'type' => 'system'
      );

$triggerarray['TRIGGER_HOLIDAY_REQUESTED'] =
array('name' => $strHolidayRequested,
      'description' => $strTriggerHolidayRequestedDesc,
      'required' => array('userid', 'approvaluseremail', 'listofholidays'),
      'permission' => 'user_permission($_SESSION[\'userid\'], 50);'
      );

$triggerarray['TRIGGER_INCIDENT_ASSIGNED'] =
array('name' => $strIncidentAssigned,
      'description' => $strTriggerNewIncidentAssignedDesc,
      'required' => array('incidentid', 'userid'),
      'params' => array('userid', 'userstatus'),
      );

$triggerarray['TRIGGER_INCIDENT_CLOSED'] =
array('name' => $strIncidentClosed,
      'description' => $strTriggerIncidentClosedDesc,
      'required' => array('incidentid', 'userid', 'notifyexternal', 'notifycontact','awaitingclosure'),
      'params' => array('userid', 'externalid', 'externalengineer', 'notifyexternal', 'notifycontact','awaitingclosure')
      );

$triggerarray['TRIGGER_INCIDENT_CREATED'] =
array('name' => $strIncidentCreated,
      'description' => $strTriggerNewIncidentCreatedDesc,
      'required' => array('incidentid'),
      'params' => array('contactid', 'siteid', 'priority', 'contractid', 'slaid', 'sitesalespersonid', 'sendemail')
      );

$triggerarray['TRIGGER_INCIDENT_NEARING_SLA'] =
array('name' => $strIncidentNearingSLA,
      'description' => $strTriggerIncidentNearingSLADesc,
      'required' => array('incidentid', 'nextslatime', 'nextsla'),
      'params' => array('ownerid', 'townerid'),
      );

$triggerarray['TRIGGER_INCIDENT_REVIEW_DUE'] =
array('name' => $strIncidentReviewDue,
      'description' => $strTriggerIncidentReviewDueDesc,
      'required' => array('incidentid', 'time'),
      'params' => array('incidentid'),
      );

$triggerarray['TRIGGER_INCIDENT_UPDATED_EXTERNAL'] =
array('name' => $strIncidentUpdatedExternally,
      'description' => $strIncidentUpdatedExternallyDesc,
      'required' => array('incidentid'),
      'params' => array('incidentid')
      );

$triggerarray['TRIGGER_INCIDENT_UPDATED_INTERNAL'] =
array('name' => $strIncidentUpdatedInternally,
      'description' => $strIncidentUpdatedInternallyDesc,
      'required' => array('incidentid', 'userid'),
      'params' => array('incidentid', 'userid')
      );

$triggerarray['TRIGGER_KB_CREATED'] =
array('name' => $strKnowledgeBaseArticleCreated,
      'description' => $strTriggerKBArticleCreatedDesc,
      'required' => array('kbid', 'userid'),
      'params' => array('userid'),
      );

$triggerarray['TRIGGER_LANGUAGE_DIFFERS'] =
array('name' => $strCurrentLanguageDiffers,
      'description' => $strTriggerLanguageDiffersDesc,
      'required' => array('currentlang', 'profilelang'),
      'params' => array(),
     );

$triggerarray['TRIGGER_NEW_CONTACT'] =
array('name' => $strNewContact,
      'description' => $strTriggerNewContactDesc,
      'required' => array('contactid', 'prepassword', 'userid'),
      'params' => array('siteid')
      );

$triggerarray['TRIGGER_NEW_CONTRACT'] =
array('name' => $strNewContract,
      'description' => $strTriggerNewContractDesc,
      'required' => array('contractid'),
      'params' => array('productid', 'slaid')
      );

$triggerarray['TRIGGER_NEW_HELD_EMAIL'] =
array('name' => $strNewHeldEmail,
      'description' => $strTriggerNewHeldEmailDesc,
      'required' => array('holdingemailid'),
      'params' => array(),
      );

$triggerarray['TRIGGER_NEW_SITE'] =
array('name' => $strNewSite,
      'description' => $strTriggerNewSiteDesc,
      'required' => array('siteid')
      );

$triggerarray['TRIGGER_NEW_USER'] =
array('name' => $strNewUser,
      'description' => $strTriggerNewUserDesc,
      'required' => array('userid')
      );

$triggerarray['TRIGGER_SCHEDULER_TASK_FAILED'] =
array('name' => $strSchedulerActionFailed,
      'description' => $strTriggerSchedulerTaskFailedDesc,
      'required' => array('schedulertask'));

$triggerarray['TRIGGER_SIT_UPGRADED'] =
array('name' => $strSitUpgraded,
      'description' => $strTriggerSitUpgradedDesc,
      'required' => array('applicationversion'),
      'params' => array(),
      );

$triggerarray['TRIGGER_TASK_DUE'] =
array('name' => $strTaskDue,
      'description' => $strTaskDueDesc,
      'required' => array('taskid'),
      'params' => array('userid')
      );

$triggerarray['TRIGGER_USER_CHANGED_STATUS'] =
array('name' => $strUserChangedStatus,
      'description' => $strTriggerUserChangedStatusDesc,
      'required' => array('userid'),
      'params' => array('userid', 'userstatus', 'useraccepting'),
      );

$triggerarray['TRIGGER_USER_RESET_PASSWORD'] =
array('name' => $strUserResetPassword,
      'description' => $strTriggerUserResetPasswordDesc,
      'required' => array('userid', 'passwordreseturl'),
      'type' => 'system'
      );

$triggerarray['TRIGGER_WAITING_HELD_EMAIL'] =
array('name' => $strWaitingHeldEmail,
      'description' => $strTriggerNewHeldEmailMinsDesc,
      'required' => array('holdingmins'),
      'params' => array('holdingmins'),
      );


$triggerarray['TRIGGER_SERVICE_LIMIT'] =
array('name' => $strBillableIncidentApproved,
      'description' => $strBillableIncidentApprovedDesc,
      'required' => array('contractid', 'serviceremaining'),
      'params' => array('contractid', 'serviceremaining'),
      );

plugin_do('trigger_types');

$pairingarray = array('TRIGGER_CONTACT_RESET_PASSWORD' => 'EMAIL_CONTACT_RESET_PASSWORD');

/**
    * Template variables (Alphabetical order)
    * description - Friendly label
    * replacement - Quoted PHP code to be run to perform the template var replacement
    * requires -Optional field. single string or array. Specifies the 'required' params from the trigger that is needed for this replacement
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
      'replacement' => '$paramarray[\'approvaluseremail\'];',
      'requires' => 'approvaluseremail'
      );

$ttvararray['{awaitingclosure}'] =
array('description' => $strAwaitingClosureVar,
      'replacement' => '$paramarray[\'awaitingclosure\'];',
      'requires' => 'incidentid',
      'show' => FALSE
      );

$ttvararray['{contactid}'][] =
array('description' => $strContactID,
      'requires' => 'incidentid',
      'replacement' => 'incident_contact($paramarray[\'incidentid\']);',
      'show' => FALSE
      );

$ttvararray['{contactemail}'][] =
array('description' => $strIncidentsContactEmail,
      'requires' => 'contactid',
      'replacement' => 'contact_email($paramarray[\'contactid\']);',
      'action' => 'ACTION_EMAIL'
      );

$ttvararray['{contactemail}'][] =
array('description' => $strIncidentsContactEmail,
      'requires' => 'incidentid',
      'replacement' => 'contact_email(incident_contact($paramarray[\'incidentid\']));',
      'action' => 'ACTION_EMAIL'
      );

$ttvararray['{contactfirstname}'][] =
array('description' => $strContactsForename,
      'requires' => 'contactid',
      'replacement' => 'strtok(contact_realname($paramarray[\'contactid\'])," ");'
      );

$ttvararray['{contactfirstname}'][] =
array('description' => $strContactsForename,
      'requires' => 'incidentid',
      'replacement' => 'strtok(contact_realname(incident_contact($paramarray[\'incidentid\']))," ");'
      );

$ttvararray['{contactid}'][] =
array('description' => $strContactID,
      'requires' => 'contactid',
      'replacement' => '$paramarray[\'contactid\'];',
      'show' => FALSE
      );

$ttvararray['{contactname}'][] =
array('description' => $strContactsName,
      'requires' => 'contactid',
      'replacement' => 'contact_realname($paramarray[\'contactid\']);'
      );

$ttvararray['{contactname}'][] =
array('description' => $strContactsName,
      'requires' => 'incidentid',
      'replacement' => 'contact_realname(incident_contact($paramarray[\'incidentid\']));'
      );

$ttvararray['{contactnotify}'] =
array('description' => $strNotifyContactEmail,
      'requires' => 'contactid',
      'replacement' => 'contact_notify_email($paramarray[\'contactid\']);'
      );

$ttvararray['{contactphone}'][] =
array('description' => $strContactsPhone,
      'requires' => 'contactid',
      'replacement' => 'contact_phone($paramarray[\'contactid\']);'
      );

$ttvararray['{contactphone}'][] =
array('description' => $strContactsPhone,
      'requires' => 'incidentid',
      'replacement' => 'contact_phone(incident_contact($paramarray[\'incidentid\']));'
      );

$ttvararray['{contactusername}'] =
array('description' => $strContactsUsername,
      'requires' => 'contactid',
      'replacement' => 'contact_username($paramarray[\'contactid\']);'
      );

$ttvararray['{contractid}'][] =
array('description' => $strContractID,
      'requires' => 'contractid',
      'replacement' => '$paramarray[\'contractid\'];',
      'show' => FALSE
      );

$ttvararray['{contractid}'][] =
array('description' => $strContractID,
      'requires' => 'contractid',
      'replacement' => 'incident_owner($paramarray[\'incidentid\']);',
      'show' => FALSE
      );

$ttvararray['{contractproduct}'] =
array('description' => $strContractProduct,
      'replacement' => 'contract_product($paramarray[\'contractid\']);',
      'requires' => 'contractid'
      );

$ttvararray['{contractsla}'] =
array('description' => $strContractsSLA,
      'replacement' => 'maintenance_servicelevel($paramarray[\'contractid\']);',
      'requires' => 'contractid'
      );

$ttvararray['{currentlang}'] =
array('description' => $strCurrentLanguage,
      'replacement' => '$paramarray[\'currentlang\'];',
      'requires' => 'currentlang'
      );

$ttvararray['{feedbackurl}'] =
array('description' => $strFeedbackURL,
      'requires' => 'incidentid',
      'replacement' => 'application_url().\'feedback.php?ax=\'.urlencode(trim(base64_encode(gzcompress(str_rot13(urlencode($CONFIG[\'feedback_form\']).\'&&\'.urlencode(incident_owner($paramarray[\'incidentid\'])).\'&&\'.urlencode($paramarray[\'incidentid\']))))));'
      );

$ttvararray['{globalsignature}'] =
array('description' => $strGlobalSignature,
      'replacement' => 'global_signature();'
      );

// $ttvararray['{holdingemailid}'] =
// array('description' => 'ID of the new email in the holding queue',
//       'replacement' => '$paramarray[\'holdingemailid\'];',
//       'requires' => 'holdingemailid',
//       'show' => FALSE
//       );

$ttvararray['{holdingmins}'] =
array('description' => $strHoldingQueueMinutes,
      'replacement' => '$paramarray[\'holdingmins\'];',
      'requires' => 'holdingmins'
      );

$ttvararray['{incidentccemail}'] =
array('description' => $strIncidentCCList,
      'requires' => 'incidentid',
      'replacement' => 'incident_ccemail($paramarray[\'incidentid\']);'
      );

$ttvararray['{incidentexternalemail}'] =
array('description' => $strExternalEngineerEmail,
      'requires' => 'incidentid',
      'replacement' => 'incident_externalemail($paramarray[incidentid]);'
      );

$ttvararray['{incidentexternalengineer}'] =
array('description' => $strExternalEngineer,
      'requires' => 'incidentid',
      'replacement' => 'incident_externalengineer($paramarray[incidentid]);'
      );

$ttvararray['{incidentexternalengineerfirstname}'] =
array('description' => $strExternalEngineersFirstName,
      'requires' => 'incidentid',
      'replacement' => 'strtok(incident_externalengineer($paramarray[\'incidentid\']), " ");'
      );

$ttvararray['{incidentexternalid}'] =
array('description' => $strExternalID,
      'requires' => 'incidentid',
      'replacement' => 'incident_externalid($paramarray[\'incidentid\']);'
      );

$ttvararray['{incidentid}'] =
array('description' => $strIncidentID,
      'requires' => 'incidentid',
      'replacement' => '$paramarray[\'incidentid\'];'
      );

$ttvararray['{incidentowner}'] =
array('description' => $strIncidentOwnersFullName,
      'requires' => 'incidentid',
      'replacement' => 'user_realname(incident_owner($paramarray[incidentid]));'
      );

$ttvararray['{incidentowneremail}'] =
array('description' => $strIncidentOwnersEmail,
      'requires' => 'incidentid',
      'replacement' => 'user_email(incident_owner($paramarray[incidentid]));'
      );

$ttvararray['{incidentpriority}'] =
array('description' => $strIncidentPriority,
      'requires' => 'incidentid',
      'replacement' => 'priority_name(incident_priority($paramarray[incidentid]));'
      );

$ttvararray['{incidentsoftware}'] =
array('description' => $strSkillAssignedToIncident,
      'requires' => 'incidentid',
      'replacement' => 'software_name(db_read_column(\'softwareid\', $GLOBALS[\'dbIncidents\'], $paramarray[\'incidentid\']));'
      );

$ttvararray['{incidenttitle}'] =
array('description' => $strIncidentTitle,
      'requires' => 'incidentid',
      'replacement' => 'incident_title($paramarray[incidentid]);'
      );

$ttvararray['{kbid}'] =
array('description' => $strKBID,
      'requires' => 'kbid',
      'replacement' => '$paramarray[\'kbid\'];'
      );

$ttvararray['{kbprefix}'] =
array('description' => $CONFIG['kb_id_prefix'],
      'requires' => array(),
      'replacement' => '$CONFIG[\'kb_id_prefix\'];'
     );

$ttvararray['{kbtitle}'] =
array('description' => $strKnowledgeBase,
      'requires' => 'kbid',
      'replacement' => 'kb_name($paramarray[\'kbid\']);'
      );

$ttvararray['{listofholidays}'] =
array('description' => $strListOfHolidays,
      'replacement' => '$paramarray[\'listofholidays\'];',
      'requires' => 'listofholidays'
      );

$ttvararray['{nextslatime}'] =
array('description' => $strTimeToNextAction,
      'replacement' => 'format_date_friendly($paramarray[\'nextslatime\']);',
      'requires' => 'nextslatime'
      );

$ttvararray['{nextsla}'] =
array('description' => $strNextSLATarget,
      'replacement' => '$paramarray[\'nextsla\'];',
      'requires' => 'nextsla'
      );

$ttvararray['{notifycontact}'] =
array('description' => $strNotifyContactOnClose,
      'replacement' => '$paramarray[\'notifycontact\'];',
      'requires' => 'incidentid',
      'show' => FALSE
      );

$ttvararray['{notifyexternal}'] =
array('description' => $strNotifyExternalEngineerOnClose,
      'replacement' => '$paramarray[\'notifyexternal\'];',
      'requires' => 'incidentid',
      'show' => FALSE
      );

$ttvararray['{ownerid}'] =
array('description' => $strIncidentOwnerID,
      'replacement' => 'incident_owner($paramarray[\'incidentid\']);',
      'requires' => 'incidentid',
      'show' => FALSE
      );

$ttvararray['{passwordreseturl}'] =
array('description' => $strPasswordResetURL,
      'replacement' => '$paramarray[\'passwordreseturl\'];',
      'requires' => 'passwordreseturl',
      'type' => 'system'
      );

$ttvararray['{prepassword}'] =
array('description' => $strContactsPassword,
      'replacement' => '$paramarray[\'prepassword\'];',
      'requires' => 'prepassword',
      'type' => 'system'
      );

$ttvararray['{profilelang}'] =
array('description' => $strProfileLanguage,
      'replacement' => '$paramarray[\'profilelang\'];',
      'requires' => 'profilelang'
      );

$ttvararray['{salesperson}'] =
array('description' => $strSalesperson,
      'requires' => 'siteid',
      'replacement' => 'user_realname(db_read_column(\'owner\', $GLOBALS[\'dbSites\'], $paramarray[\'siteid\']));'
      );

$ttvararray['{salespersonemail}'][] =
array('description' => $strSalespersonAssignedToContactsSiteEmail,
      'requires' => 'siteid',
      'replacement' => 'user_email(db_read_column(\'owner\', $GLOBALS[\'dbSites\'], $paramarray[\'siteid\']));'
      );

$ttvararray['{salespersonemail}'][] =
array('description' => $strSalespersonAssignedToContactsSiteEmail,
      'requires' => 'contractid',
      'replacement' => 'user_email(db_read_column(\'owner\', $GLOBALS[\'dbSites\'], maintenance_siteid($paramarray[\'contractid\'])));'
      );

$ttvararray['{schedulertask}'] =
array('description' => $strScheduledTask,
      'replacement' => '$paramarray[\'schedulertask\'];'
     );

$ttvararray['{sendemail}'] =
array('description' => $strSendOpeningEmailDesc,
      'replacement' => '$paramarray[\'sendemail\'];',
      'show' => FALSE
     );

$ttvararray['{sendfeedback}'] = 
array('description' => $strSendFeedbackDesc,
      'replacement' => '$paramarray[\'sendfeedback\']',
      'show' => FALSE);

$ttvararray['{serviceremaining}'] =
array('description' => $strServiceBalanceInfo,
      'requires' => 'contractid',
      'replacement' => 'get_service_percentage($paramarray[\'contractid\']);',
      'show' => FALSE
     );

$ttvararray['{serviceremainingstring}'] =
array('description' => $strServiceBalanceString,
      'requires' => 'contractid',
      'replacement' => '(get_service_percentage($paramarray[\'contractid\']) * 100)."%";',
     );

$ttvararray['{signature}'] =
array('description' => $strCurrentUsersSignature,
      'replacement' => 'user_signature($_SESSION[\'userid\']);'
      );

$ttvararray['{siteid}'] =
array('description' => $strSiteName,
      'requires' => 'siteid',
      'replacement' => '$paramarray[\'siteid\'];',
      'show' => FALSE
      );

$ttvararray['{sitename}'][] =
array('description' => $strSiteName,
      'requires' => 'incidentid',
      'replacement' => 'contact_site(incident_contact($paramarray[\'incidentid\']));'
      );

$ttvararray['{sitename}'][] =
array('description' => $strSiteName,
      'requires' => 'contactid',
      'replacement' => 'contact_site($paramarray[\'contactid\']);'
      );

$ttvararray['{sitename}'][] =
array('description' => $strSiteName,
      'requires' => 'contractid',
      'replacement' => 'contract_site($paramarray[\'contractid\']);'
      );

$ttvararray['{sitename}'][] =
array('description' => $strSiteName,
      'requires' => 'siteid',
      'replacement' => 'site_name($paramarray[\'siteid\']);'
      );

$ttvararray['{sitesalespersonid}'] =
array('description' => 'The ID of the site\'s salesperson',
      'replacement' => 'site_salespersonid($paramarray[\'siteid\']);',
      'requires' => 'siteid',
      'show' => FALSE
      );

$ttvararray['{sitesalesperson}'] =
array('description' => $strSalespersonSite,
      'replacement' => 'site_salesperson($paramarray[\'siteid\']);',
      'requires' => 'siteid'
      );

$ttvararray['{slaid}'] =
array('description' => 'ID of the SLA',
      'replacement' => 'contract_slaid($paramarray[\'contractid\']);',
      'requires' => 'contractid',
      'show' => FALSE
      );

$ttvararray['{slatag}'] =
array('description' => $strSLA,
      'replacement' => 'servicelevel_id2tag(contract_slaid($paramarray[\'contractid\']));',
      'requires' => 'contractid'
      );

$ttvararray['{supportemail}'] =
array('description' => $strSupportEmailAddress,
      'replacement' => '$CONFIG[\'support_email\'];'
      );

$ttvararray['{supportmanageremail}'] =
array('description' => $strSupportManagersEmailAddress,
      'replacement' => 'user_email($CONFIG[\'support_manager\']);'
      );

$ttvararray['{taskid}'] =
array('description' => 'ID of the task',
      'replacement' => '$paramarray[\'taskid\']',
      'show' => FALSE
     );

$ttvararray['{todaysdate}'] =
array('description' => $strCurrentDate,
      'replacement' => 'ldate("jS F Y");'
      );

$ttvararray['{townerid}'] =
array('description' => 'Incident temp owner ID',
      'replacement' => 'incident_towner($paramarray[\'incidentid\']);',
      'requires' => 'incidentid',
      'show' => FALSE
      );

$ttvararray['{triggersfooter}'] =
array('description' => $strTriggersFooter,
      'replacement' => '$SYSLANG[\'strTriggerFooter\'];',
      'requires' => ''
     );

$ttvararray['{triggeruseremail}'] =
array('description' => $strTriggerUserEmail,
      'replacement' => 'user_email($paramarray[\'triggeruserid\']);'
      );

$ttvararray['{updateid}'] =
array('description' => 'The ID of the update',
      'replacement' => 'incoming_email_update_id($paramarray[\'holdingemailid\']);',
      'requires' => 'holdingemailid',
      'show' => FALSE
      );

$ttvararray['{useraccepting}'] =
array('description' => 'Whether the user is accepting or not',
      'replacement' => 'user_accepting_status($paramarray[\'userid\']);',
      'requires' => 'userid',
      'show' => FALSE
      );

$ttvararray['{useremail}'] =
array('description' => $strCurrentUserEmailAddress,
      'replacement' => 'user_email($paramarray[\'userid\']);'
      );

$ttvararray['{userid}'][] =
array('description' => 'UserID the trigger passes',
      'replacement' => '$paramarray[\'userid\'];',
      'show' => FALSE
      );

$ttvararray['{userid}'][] =
array('description' => 'Owner of a task',
      'replacement' => 'task_owner($paramarray[\'taskid\']);',
      'requires' => 'taskid',
      'show' => FALSE
      );

$ttvararray['{userrealname}'] =
array('description' => $strFullNameCurrentUser,
      'replacement' => 'user_realname($GLOBALS[\'sit\'][2]);'
      );

$ttvararray['{userstatus}'] =
array('description' => $strUserStatus,
      'replacement' => 'user_status_name($paramarray[\'userid\']);',
      'requires' => 'userid'
      );

plugin_do('trigger_variables');
?>
