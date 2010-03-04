<?php
// triggers.inc.php - Trigger definitions and helper functions
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// FIXME Added by ivan because triggers.class.php was never included and that file
// Included other vital files such as incident.inc.php - INL 29Feb08
// Caution: This include here might not be the right place. Kieran can you
// check.
//include('trigger.class.php');

$actionarray['ACTION_NONE'] =
array('name' => $strNone,
      'description' => $strDoNothing,
      );

$actionarray['ACTION_NOTICE'] =
array('name' => $strNotice,
      'description' => $strCreateANotice
      );

$actionarray['ACTION_EMAIL'] =
array('name' => $strEmail,
      'description' => $strSendAnEmail
      );

$actionarray['ACTION_CREATE_INCIDENT'] =
array('name' => $strAddIncident,
      'description' => $strCreateAnIncident,
      'requires' => array('updateid'),
      'permission' => array(),
      'type' => 'system'
      );

$actionarray['ACTION_JOURNAL'] =
array('name' => 'Journal',
      'description' => $strLogTriggerInJournal
      );

plugin_do('trigger_actions');

// Define a list of available triggers, trigger() will need to be called in the appropriate
// place in the code for each of these
//
// id - trigger name
// description - when the trigger is fired
// required - parameters the triggers needs to fire, 'provides' these to templates
// params - Rules the trigger can check, mimics 'subscription'-type events
// type - Trigger type (eg. incident, contact etc)

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
      'permission' => 'user_permission($_SESSION[\'userid\'], 50);'
      );

$trigger_types['TRIGGER_INCIDENT_ASSIGNED'] =
array('name' => $strIncidentAssigned,
      'description' => $strTriggerNewIncidentAssignedDesc,
      'required' => array('incidentid', 'userid'),
      'params' => array('userid', 'userstatus'),
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
      'params' => array('contactid', 'siteid', 'priority', 'contractid', 'slaid', 'sitesalespersonid', 'sendemail')
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
      'params' => array(),
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
      'params' => array('productid', 'slaid')
      );

$trigger_types['TRIGGER_NEW_HELD_EMAIL'] =
array('name' => $strNewHeldEmail,
      'description' => $strTriggerNewHeldEmailDesc,
      'required' => array('holdingemailid'),
      'params' => array(),
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
      'required' => array('schedulertask'));

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
      'required' => array('holdingmins'),
      'params' => array('holdingmins'),
      );


$trigger_types['TRIGGER_SERVICE_LIMIT'] =
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
array('description' => $strEmailSendFeedbackDesc,
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

/**
    * Displays a <select> with the list of email templates
    * @author Kieran Hogg, Ivan Lucas
    * @param $triggertype string. The type of trigger (incident, system...)
    * @param $name string. The name for the select
    * @param $selected string. The name of the selected item
    * @returns string. HTML snippet
*/
function email_templates($name, $triggertype='system', $selected = '')
{
    global $dbEmailTemplates, $dbTriggers;;
    $html .= "<select id='{$name}' name='{$name}'>";
    $sql = "SELECT id, name, description FROM `{$dbEmailTemplates}` ";
    $sql .= "WHERE type='{$triggertype}' ORDER BY name";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    while ($template = mysql_fetch_object($result))
    {
    //$name = strpos()
    //$name = str_replace("_", " ", $name);
    $name = strtolower($name);
    $html .= "<option id='{$template->name}' value='{$template->name}'>{$template->name}</option>\n";
    $html .= "<option disabled='disabled' style='color: #333; text-indent: 10px;' value='{$template->name}'>".$GLOBALS[$template->description]."</option>\n";

    }
    $html .= "</select>\n";
    return $html;
}


/**
    * Displays a <select> with the list of notice templates
    * @author Kieran Hogg
    * @param $name string. The name for the select
    * @param $selected string. The name of the selected item
*/
function notice_templates($name, $selected = '')
{
    global $dbNoticeTemplates;
    $html .= "<select id='{$name}' name='{$name}'>";
    $sql = "SELECT id, name, description FROM `{$dbNoticeTemplates}` ORDER BY name ASC";
    $query = mysql_query($sql);
    if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
    while ($template = mysql_fetch_object($query))
    {
    $html .= "<option id='{$template->name}' value='{$template->name}'>{$template->name}</option>\n";
    $html .= "<option disabled='disabled' style='color: #333; text-indent: 10px;' value='{$template->name}'>".$GLOBALS[$template->description]."</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}

/**
    * Actually do the replacement, used so we can define variables more than once
    * @author Kieran Hogg
    * @param array &$ttvar the array of the variable to replace
    * @param string &$identifier the {variable} name
    * @param array &$required  optional array of required vars to pass, used if
    * we're not dealing with a trigger
    * @return mixed array if replacement found, NULL if not
*/
function replace_vars($trigger_type, &$ttvar, &$identifier, $required = '')
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
	//debug_log("Using $identifier");
	$trigger_regex = "/{$identifier}/s";
	if (!empty($ttvar['replacement']))
	{
	    $eresult = @eval("\$res = {$ttvar[replacement]};return TRUE;");
	    if (!$eresult)
	    {
		trigger_error("Error in variable replacement for 
			      <strong>{$identifier}</strong>, check that 
			      this variable is available for the template 
			      that uses it.", E_USER_WARNING);

		debug_log("Replacement: {$ttvar[replacement]}", TRUE);
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
    * @param string $paramarray An array containing values to be substituted
    * @return string The string with variables replaced
*/
function replace_specials($string, $paramarray)
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
	$trigger_replaces = replace_vars($ttvar[$key], $triggerid, $identifier, $paramarray, $required);
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
	$trigger_replaces = replace_vars($ttvar, $triggerid, $identifier, $paramarray, $required);
	if (!empty($trigger_replaces))
	{
	$trigger_regex[] = $trigger_replaces['trigger_regex'];
	$trigger_replace[] = $trigger_replaces['trigger_replace'];
	}
    }
    }
    return  preg_replace($trigger_regex, $trigger_replace, $string);
}


/**
    * Formats a human readable description of a trigger
    * @author Ivan Lucas
    * @param $triggervar array. An individual trigger array
    * @returns HTML
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


function trigger_types()
{
    return $trigger_types;
}

?>
