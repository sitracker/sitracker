<?php
// strings.inc.php - Set up strings
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

//create array of strings in the system's language for updates etc
if (isset($_SESSION['syslang'])) $SYSLANG = $_SESSION['syslang'];

/**
 Hierarchical Menus

 $hmenu array containing menu options for the SiT! menu, each menu has an
 associated permission number (perm), an entry name (name), a URL (url)
 and optionally a sub-menu reference (submenu) and a enabling variable (enablevar)

 submenus are numbered with concatenated numbers in the format [menu+item_number]
 so if menu 30 has a submenu as it's fourth item, the reference for that menu
 would be 3040.

 enablevar is a key of the $CONFIG assoc. array.

 Example:

    $hmenu[1040] = array (10 => array('perm' => 0, 'name' => "Option1", 'url' => ""),
                          20 => array('perm' => 0, 'name' => "Option2", 'url' => "", enablevar => "example_config"),
                          30 => array('perm' => 0, 'name' => "Option3", 'url' => "", submenu => 104030 ),
                          40 => array('perm' => 0, 'name' => "Option4", 'url' => "", 'desc' => 'Description for reports.php')  // used for reports menus
);
*/

if (!empty($_SESSION) AND $_SESSION['auth'] == TRUE)
{
    // //need to call directly as we don't have functions yet
    if ($CONFIG['enable_inbound_mail'] == TRUE)
    {
        $sql = "SELECT COUNT(*) AS count FROM `{$dbTempIncoming}`";
        $result = mysqli_query($db, $sql);
        list($inbox_count) = mysqli_fetch_row($result);
        if ($inbox_count > 0)
        {
            $inbox_count = " <strong>(".$inbox_count.")</strong>";
        }
        else $inbox_count = '';
    }


    //
    // Top Level: Main Menu
    //
    if (!is_array(@$hmenu[0])) $hmenu[0] = array();
    $hmenu[0] = $hmenu[0] +
                    array (10 => array('perm' => PERM_NOT_REQUIRED, 'name' => $CONFIG['application_shortname'], 'url' => "{$CONFIG['application_webpath']}main.php", 'submenu' => 10),
                           20 => array('perm' => PERM_SITE_VIEW, 'name' => $strCustomers, 'url' => "{$CONFIG['application_webpath']}sites.php", 'submenu' => 20),
                           30 => array('perm' => PERM_INCIDENT_LIST, 'name' => $strSupport, 'url' => "{$CONFIG['application_webpath']}incidents.php?user=current&amp;queue=1&amp;type=support", 'submenu' => 30),
                           40 => array('perm' => PERM_TASK_VIEW, 'name' => $strTasks, 'url' => "{$CONFIG['application_webpath']}tasks.php", 'submenu' => 40, 'enablevar' => 'tasks_enabled'),
                           50 => array('perm' => PERM_KB_VIEW, 'name' => $strKnowledgeBase, 'url' => "{$CONFIG['application_webpath']}kb.php", 'submenu' => 50, 'enablevar' => 'kb_enabled'),
                           60 => array('perm' => PERM_REPORT_RUN, 'name' => $strReports, 'url' => "reports.php", 'submenu' => 60),
                           70 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strHelp, 'url' => "{$CONFIG['application_webpath']}help.php", 'submenu' => 70)
    );
    // Second Level: SiT! submenu
    if (!is_array(@$hmenu[10])) $hmenu[10] = array();
    $hmenu[10] = $hmenu[10] +
                    array (10 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strDashboard, 'url' => "{$CONFIG['application_webpath']}main.php"),
                           20 => array('perm' => PERM_SEARCH, 'name' => $strSearch, 'url' => "{$CONFIG['application_webpath']}search.php"),
                           30 => array('perm' => PERM_MYPROFILE_EDIT, 'name' => $strMyDetails, 'url' => "{$CONFIG['application_webpath']}user_profile_edit.php", 'submenu' => 1030),
                           40 => array('perm' => PERM_MYPROFILE_EDIT, 'name' => $strControlPanel, 'url' => "{$CONFIG['application_webpath']}config.php", 'submenu' => 1040),
                           50 => array('perm' => PERM_PRODUCT_VIEW, 'name' => htmlspecialchars($strProductsAndSkills), 'url' => "products.php", 'submenu' => 1050),
                           60 => array('perm' => PERM_USER_VIEW, 'name' => $strUsers, 'url' => "{$CONFIG['application_webpath']}users.php", 'submenu' => 1060),
                           70 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strLogout, 'url' => "{$CONFIG['application_webpath']}logout.php")
    );
    // Second Level: My Details submenu
    if (!is_array(@$hmenu[1030])) $hmenu[1030] = array();
    $hmenu[1030] = $hmenu[1030] +
                    array (10 => array('perm' => PERM_MYPROFILE_EDIT, 'name' => $strMyProfile, 'url' => "{$CONFIG['application_webpath']}user_profile_edit.php"),
                           20 => array('perm' => PERM_MYPROFILE_EDIT, 'name' => $strMySettings, 'url' => "{$CONFIG['application_webpath']}config.php?userid=current"),
                           30 => array('perm' => PERM_MYSKILLS_SET, 'name' => $strMySkills, 'url' => "{$CONFIG['application_webpath']}edit_user_skills.php"),
                           40 => array('perm' => PERM_MYSKILLS_SET, 'name' => $strMySubstitutes, 'url' => "{$CONFIG['application_webpath']}edit_backup_users.php"),
                           50 => array('perm' => PERM_CALENDAR_VIEW, 'name' => $strMyHolidays, 'url' => "{$CONFIG['application_webpath']}holidays.php", 'enablevar' => 'holidays_enabled'),
                           60 => array('perm' => PERM_MYPROFILE_EDIT, 'name' => $strMyDashboard, 'url' => "{$CONFIG['application_webpath']}manage_user_dashboard.php"),
                           70 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strMyNotifications, 'url' => "{$CONFIG['application_webpath']}notifications.php")
    );
    // Second Level: Control Panel submenu
    if (!is_array(@$hmenu[1040])) $hmenu[1040] = array();
    $hmenu[1040] = $hmenu[1040] +
                    array (10 => array('perm' => PERM_ADMIN, 'name' => $strConfigure, 'url' => "{$CONFIG['application_webpath']}config.php"),
                           20 => array('perm' => PERM_ADMIN, 'name' => $strUsers, 'url' => "{$CONFIG['application_webpath']}manage_users.php", 'submenu' => 104020),
                           30 => array('perm' => PERM_GLOBALSIG_EDIT, 'name' => $strGlobalSignature, 'url' => "{$CONFIG['application_webpath']}edit_global_signature.php"),
                           40 => array('perm' => PERM_ADMIN, 'name' => $strTemplates, 'url' => "{$CONFIG['application_webpath']}templates.php"),
                           50 => array('perm' => PERM_ADMIN, 'name' => $strSetPublicHolidays, 'url' => "{$CONFIG['application_webpath']}calendar.php?type=10&amp;display=year", 'enablevar' => 'holidays_enabled'),
                           70 => array('perm' => PERM_ADMIN, 'name' => $strServiceLevels, 'url' => "{$CONFIG['application_webpath']}service_levels.php"),
                           80 => array('perm' => PERM_BILLING_DURATION_EDIT, 'name' => $strBillingMatrix, 'url' => "{$CONFIG['application_webpath']}billing_matrix.php"),
                           90 => array('perm' => PERM_INCIDENT_EDIT, 'name' => $strBulkModify, 'url' => "{$CONFIG['application_webpath']}bulk_modify.php?action=external_esc"),
                           100 => array('perm' => PERM_ESCALATION_MANAGE, 'name' => $strEscalationPaths, 'url' => "{$CONFIG['application_webpath']}escalation_paths.php"),
                           110 => array('perm' => PERM_DASHLET_INSTALL, 'name' => $strManageDashboardComponents, 'url' => "{$CONFIG['application_webpath']}manage_dashboard.php"),
                           120 => array('perm' => PERM_ADMIN, 'name' => $strManagePlugins, 'url' => "{$CONFIG['application_webpath']}manage_plugins.php"),
                           130 => array('perm' => PERM_NOTICE_POST, 'name' => $strNotices, 'url' => "{$CONFIG['application_webpath']}notices.php"),
                           140 => array('perm' => PERM_ADMIN, 'name' => $strSystemActions, 'url' => "{$CONFIG['application_webpath']}system_actions.php"),
                           150 => array('perm' => PERM_ADMIN, 'name' => $strScheduler, 'url' => "{$CONFIG['application_webpath']}scheduler.php"),
                           160 => array('perm' => PERM_ADMIN, 'name' => $strJournal, 'url' => "{$CONFIG['application_webpath']}journal.php")
    );
    // Third Level: Control Panel/Manage Users submenu
    if (!is_array(@$hmenu[104020])) $hmenu[104020] = array();
    $hmenu[104020] = $hmenu[104020] +
                    array (10 => array('perm' => PERM_ADMIN, 'name' => $strManageUsers, 'url' => "{$CONFIG['application_webpath']}manage_users.php"),
                           20 => array('perm' => PERM_USER_ADD, 'name' => $strNewUser, 'url' => "{$CONFIG['application_webpath']}user_new.php?action=showform"),
                           30 => array('perm' => PERM_USER_PERMISSIONS_EDIT, 'name' => $strRolePermissions, 'url' => "{$CONFIG['application_webpath']}edit_user_permissions.php"),
                           40 => array('perm' => PERM_USER_EDIT, 'name' => $strUserGroups, 'url' => "{$CONFIG['application_webpath']}usergroups.php"),
                           50 => array('perm' => PERM_ADMIN, 'name' => $strEditHolidayEntitlement, 'url' => "{$CONFIG['application_webpath']}edit_holidays.php", 'enablevar' => 'holidays_enabled')
    );
    // Second Level: SiT/Products & Skills Submenu
    if (!is_array(@$hmenu[1050])) $hmenu[1050] = array();
    $hmenu[1050] = $hmenu[1050] +
                    array (10 => array('perm' => PERM_PRODUCT_VIEW, 'name' => $strListProducts, 'url' => "{$CONFIG['application_webpath']}products.php"),
                           20 => array('perm' => PERM_PRODUCT_VIEW, 'name' => $strListSkills, 'url' => "{$CONFIG['application_webpath']}products.php?display=skills"),
                           30 => array('perm' => PERM_SKILL_ADD, 'name' => $strListVendors, 'url' => "{$CONFIG['application_webpath']}vendors.php")
    );
    // Second Level: SiT/Users Submenu
    if (!is_array(@$hmenu[1060])) $hmenu[1060] = array();
    $hmenu[1060] = $hmenu[1060] +
                    array (10 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strViewUsers, 'url' => "{$CONFIG['application_webpath']}users.php"),
                           20 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strListUserSkills, 'url' => "{$CONFIG['application_webpath']}user_skills.php"),
                           30 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strSkillsMatrix, 'url' => "{$CONFIG['application_webpath']}skills_matrix.php"),
                           40 => array('perm' => PERM_CALENDAR_VIEW, 'name' => $strHolidayPlanner, 'url' => "{$CONFIG['application_webpath']}calendar.php?display=month", 'enablevar' => 'holidays_enabled'),
                           50 => array('perm' => PERM_HOLIDAY_APPROVE, 'name' => $strApproveHolidays, 'url' => "{$CONFIG['application_webpath']}holiday_request.php?user=all&amp;mode=approval", 'enablevar' => 'holidays_enabled')
    );


    //
    // Top Level: Customers menu
    //
    if (!is_array(@$hmenu[20])) $hmenu[20] = array();
    $hmenu[20] = $hmenu[20] +
                    array (10 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strSites, 'url' => "{$CONFIG['application_webpath']}sites.php", 'submenu' => 2010),
                           20 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strContacts, 'url' => "{$CONFIG['application_webpath']}contacts.php", 'submenu' => 2020),
                           30 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strContracts, 'url' => "{$CONFIG['application_webpath']}contracts.php", 'submenu' => 2030),
                           40 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strInventory, 'url' => "{$CONFIG['application_webpath']}inventory.php", 'enablevar' => 'inventory_enabled'),
                           50 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strFeedback, 'url' => "{$CONFIG['application_webpath']}feedback_browse.php", 'submenu' => 2050 ,'enablevar' => 'feedback_enabled')
    );
    // Second Level: Customers/Sites submenu
    if (!is_array(@$hmenu[2010])) $hmenu[2010] = array();
    $hmenu[2010] = $hmenu[2010] +
                    array (10 => array('perm' => PERM_SITE_VIEW, 'name' => $strBrowseSites, 'url' => "{$CONFIG['application_webpath']}sites.php"),
                           20 => array('perm' => PERM_SITE_ADD, 'name' => $strNewSite, 'url' => "{$CONFIG['application_webpath']}site_new.php?action=showform")
    );
    // Second Level: Customers/Contacts submenu
    if (!is_array(@$hmenu[2020])) $hmenu[2020] = array();
    $hmenu[2020] = $hmenu[2020] +
                    array (10 => array('perm' => PERM_SITE_VIEW, 'name' => $strBrowseContacts, 'url' => "{$CONFIG['application_webpath']}contacts.php"),
                           20 => array('perm' => PERM_CONTACT_ADD, 'name' => $strNewContact, 'url' => "{$CONFIG['application_webpath']}contact_new.php?action=showform")
    );
    // Second Level: Customers/Contracts submenu
    if (!is_array(@$hmenu[2030])) $hmenu[2030] = array();
    $hmenu[2030] = $hmenu[2030] +
                    array (10 => array('perm' => PERM_CONTRACT_VIEW, 'name' => $strBrowseContracts, 'url' => "{$CONFIG['application_webpath']}contracts.php"),
                           20 => array('perm' => PERM_CONTRACT_ADD, 'name' => $strNewContract, 'url' => "{$CONFIG['application_webpath']}contract_new.php?action=showform"),
                           40 => array('perm' => PERM_RESELLER_ADD, 'name' => $strNewReseller, 'url' => "{$CONFIG['application_webpath']}reseller_new.php"),
                           50 => array('perm' => PERM_SKILL_ADD, 'name' => $strSiteTypes, 'url' => "{$CONFIG['application_webpath']}site_types.php"),
                           60 => array('perm' => PERM_CONTRACT_VIEW, 'name' => $strShowRenewals, 'url' => "{$CONFIG['application_webpath']}search_renewals.php?action=showform"),
                           70 => array('perm' => PERM_CONTRACT_VIEW, 'name' => $strShowExpiredContracts, 'url' => "{$CONFIG['application_webpath']}search_expired.php?action=showform"),
                           80 => array('perm' => PERM_REPORT_RUN, 'name' => $strBilling, 'url' => "{$CONFIG['application_webpath']}billable_incidents.php")
    );
    // Second Level: Customers/Feedback  submenu
    if (!is_array(@$hmenu[2050])) $hmenu[2050] = array();
    $hmenu[2050] = $hmenu[2050] +
                    array (10 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strBrowseFeedback, 'url' => "{$CONFIG['application_webpath']}feedback_browse.php", 'enablevar' => 'feedback_enabled'),
                           20 => array('perm' => PERM_FEEDBACK_FORM_EDIT, 'name' => $strFeedbackForms, 'url' => "{$CONFIG['application_webpath']}feedback_form_list.php", 'enablevar' => 'feedback_enabled'),
                           30 => array('perm' => PERM_FEEDBACK_FORM_EDIT, 'name' => $strNewFeedbackForm, 'url' => "{$CONFIG['application_webpath']}feedback_form_edit.php?action=new", 'enablevar' => 'feedback_enabled')
    );


    //
    // Top Level: Support menu
    //
    if (!is_array(@$hmenu[30])) $hmenu[30] = array();
    $hmenu[30] = $hmenu[30] +
                    array (10 => array('perm' => PERM_UPDATE_DELETE, 'name' => $strInbox.$inbox_count, 'url' => "{$CONFIG['application_webpath']}inbox.php", 'enablevar' => 'enable_inbound_mail'),
                           20 => array('perm' => PERM_INCIDENT_ADD, 'name' => $strNewIncident, 'url' => "{$CONFIG['application_webpath']}incident_new.php"),
                           30 => array('perm' => PERM_INCIDENT_LIST, 'name' => $strMyIncidents, 'url' => "{$CONFIG['application_webpath']}incidents.php"),
                           40 => array('perm' => PERM_INCIDENT_LIST, 'name' => $strAllIncidents, 'url' => "{$CONFIG['application_webpath']}incidents.php?user=all&amp;queue=1&amp;type=support"),
                           50 => array('perm' => PERM_UPDATE_DELETE, 'name' => $strHoldingQueue, 'url' => "{$CONFIG['application_webpath']}holding_queue.php")
    );


    //
    // Top Level: Tasks menu
    //
    if (!is_array(@$hmenu[40])) $hmenu[40] = array();
    $hmenu[40] = $hmenu[40] +
                array (10 => array('perm' => PERM_TASK_EDIT, 'name' => $strNewTask, 'url' => "{$CONFIG['application_webpath']}task_new.php"),
                       20 => array('perm' => PERM_TASK_VIEW, 'name' => $strViewTasks, 'url' => "{$CONFIG['application_webpath']}tasks.php")
    );


    //
    // Top Level: Knowledge Base menu
    //
    if (!is_array(@$hmenu[50])) $hmenu[50] = array();
    $hmenu[50] = $hmenu[50] +
                array (10 => array('perm' => PERM_KB_VIEW, 'name' => $strNewKBArticle, 'url' => "{$CONFIG['application_webpath']}kb_article.php"),
                       20 => array('perm' => PERM_KB_VIEW, 'name' => $strBrowse, 'url' => "{$CONFIG['application_webpath']}kb.php")
    );


    //
    // Top Level: Reports menu
    //
    if (!is_array(@$hmenu[60])) $hmenu[60] = array();
    $hmenu[60] = $hmenu[60] +
                array (10 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strMarketingMailshot}", 'url' => "{$CONFIG['application_webpath']}report_marketing.php", 'desc' => $strReportDescMarketting),
                       20 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strCustomerExport}", 'url' => "{$CONFIG['application_webpath']}report_customers.php", 'desc' => $strReportDescCustomers),
                       30 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strQueryByExample}", 'url' => "{$CONFIG['application_webpath']}report_qbe.php", 'desc' => $strReportDescQBE),
                       40 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strIncidents}", 'url' => "", 'submenu' => 6040),
                       50 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strSiteProducts}", 'url' => "{$CONFIG['application_webpath']}report_customer_products.php", 'desc' => $strReportDescCustomerProduct),
                       60 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strSiteProductsMatrix}", 'url' => "{$CONFIG['application_webpath']}report_customer_products_matrix.php", 'desc' => $strReportDescCustomerProductsMatrix),
                       70 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strCountContractsByProduct}", 'url' => "{$CONFIG['application_webpath']}report_contracts_by_product.php", 'desc' => $strReportDescContractsByProduct),
                       80 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strSiteContracts}", 'url' => "{$CONFIG['application_webpath']}report_customer_contracts.php", 'desc' => $strReportDescCustomerContracts),
                       90 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strCustomerFeedback}", 'url' => "{$CONFIG['application_webpath']}report_feedback.php", 'enablevar' => 'feedback_enabled', 'desc' => $strReportDescFeedback),
                       100 => array('perm' => PERM_SITE_VIEW, 'name' => "{$strShowOrphanedContacts}", 'url' => "{$CONFIG['application_webpath']}report_orphans_contacts.php", 'desc' => $strReportDescOrphansContacts),
                       110 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strEngineerMonthlyActivityTotals}",'url' => "{$CONFIG['application_webpath']}report_billable_engineer_utilisation.php", 'desc' => $strReportDescBillableEngineerUtilisation
    ));
    // Second Level: Reports/Incidents submenu
    if (!is_array(@$hmenu[6040])) $hmenu[6040] = array();
    $hmenu[6040] = $hmenu[6040] +
                array (10 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strIncidentsBySite}", 'url' => "{$CONFIG['application_webpath']}report_incidents_by_site.php", 'desc' => $strReportDescIncidentsBySite),
                       20 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strIncidentsByEngineer}", 'url' => "{$CONFIG['application_webpath']}report_incidents_by_engineer.php", 'desc' => $strReportDescIncidentsByEngineer),
                       30 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strSiteIncidents}", 'url' => "{$CONFIG['application_webpath']}report_incidents_by_customer.php", 'desc' => $strReportDescIncidentsByCustomer),
                       40 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strRecentIncidents}", 'url' => "{$CONFIG['application_webpath']}report_incidents_recent.php", 'desc' => $strReportDescIncidentsRecent),
                       50 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strIncidentsLoggedOpenClosed}", 'url' => "{$CONFIG['application_webpath']}report_incidents_graph.php", 'desc' => $strReportDescIncidentsGraph),
                       60 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strAverageIncidentDuration}", 'url' => "{$CONFIG['application_webpath']}report_incidents_average_duration.php", 'desc' => $strReportDescIncidentsAverageDuration),
                       70 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strIncidentsBySkill}", 'url' => "{$CONFIG['application_webpath']}report_incidents_by_skill.php", 'desc' => $strReportDescIncidentsBySkill),
                       80 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strIncidentsByVendor}", 'url' => "{$CONFIG['application_webpath']}report_incidents_by_vendor.php", 'desc' => $strReportDescIncidentsByVendor),
                       90 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strEscalatedIncidents}",'url' => "{$CONFIG['application_webpath']}report_incidents_escalated.php", 'desc' => $strReportDescIncidentsEscalated),
                       100 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strBillableIncidents}",'url' => "{$CONFIG['application_webpath']}report_incidents_billable.php", 'desc' => $strReportDescIncidentsBillable),
                       110 => array('perm' => PERM_REPORT_RUN, 'name' => "{$strIncidentsDailySummary}",'url' => "{$CONFIG['application_webpath']}report_incidents_daily_summary.php", 'desc' => $strReportDescIncidentsDailySummary
    ));


    //
    // Top Level: Help menu
    //
    if (!is_array(@$hmenu[70])) $hmenu[70] = array();
    $hmenu[70] + $hmenu[70] =
                array (10 => array('perm' => PERM_HELP_VIEW, 'name' => "{$strHelpContents}{$strEllipsis}", 'url' => "{$CONFIG['application_webpath']}help.php"),
                       20 => array('perm' => PERM_HELP_VIEW, 'name' => "{$strGetHelpOnline}", 'url' => "http://sitracker.org/wiki/Documentation".mb_strtoupper(mb_substr($_SESSION['lang'], 0, 2))),
                       30 => array('perm' => PERM_NOT_REQUIRED, 'name' => "{$strTranslate}", 'url' => "{$CONFIG['application_webpath']}translate.php"),
                       40 => array('perm' => PERM_STATUS_VIEW, 'name' => "{$strStatus}", 'url' => "{$CONFIG['application_webpath']}status.php"),
                       50 => array('perm' => PERM_HELP_VIEW, 'name' => "{$strReportBug}", 'url' =>$CONFIG['bugtracker_url']),
                       60 => array('perm' => PERM_NOT_REQUIRED, 'name' => "{$strReleaseNotes}", 'url' => "{$CONFIG['application_webpath']}releasenotes.php"),
                       70 => array('perm' => PERM_NOT_REQUIRED, 'name' => $strHelpAbout, 'url' => "{$CONFIG['application_webpath']}about.php")
    );

    if ($_SESSION['auth'] == TRUE AND function_exists('plugin_do')) plugin_do('define_menu');

    // Sort the top level menu, so that plugin menus appear in the right place
    ksort($hmenu[0], SORT_NUMERIC);
}

// Portal menu

if (!empty($_SESSION) AND isset($_SESSION['portalauth']) AND $_SESSION['portalauth'] == TRUE)
{
    // Force KB disabled if it's globally disabled
    if (($CONFIG['kb_enabled'] != TRUE) OR ($CONFIG['portal_kb_enabled'] == 'Disabled'))
    {
        $CONFIG['portal_kb_enabled'] = FALSE;
    }

    //
    // Top Level Main Menu
    //
    if (!is_array(@$hmenu[0])) $hmenu[0] = array();
    $hmenu[0] = $hmenu[0] +
                array (10 => array ('name' => $strPortal, 'url' => 'index.php', 'submenu' => 10),
                       20 => array ('name' => $strSupport, 'url' => 'index.php', 'submenu' => 20),
                       30 => array ('name' => $strKnowledgeBase, 'url' => 'kb.php', 'submenu' => 30, 'enablevar' => 'portal_kb_enabled'),
                       40 => array ('name' => $strAdmin, 'url' => 'admin.php', 'submenu' => 40),
                       50 => array ('name' => $strHelp, 'url' => 'help.php', 'submenu' => 50)
                       );

    //
    // Top Level: Portal menu
    //
    if (!is_array(@$hmenu[10])) $hmenu[10] = array();
    $hmenu[10] + $hmenu[10] =
                array (10 => array ('name' => $strMyDetails, 'url' => 'contactdetails.php'),
                       20 => array ('name' => $strLogout, 'url' => '../logout.php'));


    //
    // Top Level: Incidents menu
    //
    if (!is_array(@$hmenu[20])) $hmenu[20] = array();
    $hmenu[20] + $hmenu[20] =
                array (10 => array ('name' => $strEntitlement, 'url' => 'entitlement.php'),
                       20 => array ('name' => $strNewIncident, 'url' => 'entitlement.php'),
                       30 => array ('name' => $strViewIncidents, 'url' => 'index.php'),
                       40 => array ('name' => $strFeedback, 'url' => 'feedback.php', 'enablevar' => 'portal_feedback_enabled')
                       );

    //
    // Top Level: KB menu
    //
    if (!is_array(@$hmenu[30])) $hmenu[30] = array();
    $hmenu[30] + $hmenu[30] =
                array (10 => array ('name' => $strViewKnowledgebaseArticles, 'url' => 'kb.php', 'enablevar' => 'kb_enabled'));

    //
    // Top Level: Admin
    //
    if (!is_array(@$hmenu[40])) $hmenu[40] = array();
    $hmenu[40] + $hmenu[40] =
                array (10 => array ('name' => $strContractDetails, 'url' => 'admin.php'),
                       20 => array ('name' => $strSiteDetails, 'url' => 'sitedetails.php'),
                       30 => array ('name' => $strNewSiteContact, 'url' => 'newcontact.php'));

    //
    // Top Level: Help
    //
    if (!is_array(@$hmenu[50])) $hmenu[50] = array();
    $hmenu[50] + $hmenu[50] =
                array (10 => array ('name' => $strHelpContents.$strEllipsis, 'url' => 'help.php'),
                       20 => array ('name' => $strGetHelpOnline, 'url' => "http://sitracker.org/wiki/Documentation".mb_strtoupper(mb_substr($_SESSION['lang'], 0, 2))),
                       30 => array ('name' => $strHelpAbout, 'url' => 'about.php')
                       );

    if ($_SESSION['auth'] == TRUE AND function_exists('plugin_do')) plugin_do('define_portal_menu');

    ksort($hmenu[0], SORT_NUMERIC);
}

//
// Non specific update types
//
$updatetypes['actionplan'] = array('icon' => 'actionplan', 'text' => sprintf($strActionPlanBy,'updateuser'));
$updatetypes['auto'] = array('icon' => 'auto', 'text' => sprintf($strUpdatedAutomaticallyBy, 'updateuser'));
$updatetypes['closing'] = array('icon' => 'close', 'text' => sprintf($strMarkedforclosureby,'updateuser'));
$updatetypes['editing'] = array('icon' => 'edit', 'text' => sprintf($strEditedBy,'updateuser'));
$updatetypes['email'] = array('icon' => 'emailout', 'text' => sprintf($strEmailsentby,'updateuser'));
$updatetypes['emailin'] = array('icon' => 'emailin', 'text' => sprintf($strEmailreceivedby,'updateuser'));
$updatetypes['emailout'] = array('icon' => 'emailout', 'text' => sprintf($strEmailsentby,'updateuser'));
$updatetypes['externalinfo'] = array('icon' => 'externalinfo', 'text' => sprintf($strExternalInfoAddedBy,'updateuser'));
$updatetypes['probdef'] = array('icon' => 'probdef', 'text' => sprintf($strProblemDefinitionby,'updateuser'));
$updatetypes['research'] = array('icon' => 'research', 'text' => sprintf($strResearchedby,'updateuser'));
$updatetypes['reassigning'] = array('icon' => 'reassign', 'text' => sprintf($strReassignedToBy,'currentowner','updateuser'));
$updatetypes['reviewmet'] = array('icon' => 'review', 'text' => sprintf($strReviewby, 'updatereview', 'updateuser')); // conditional
$updatetypes['tempassigning'] = array('icon' => 'tempassign', 'text' => sprintf($strTemporarilyAssignedto,'currentowner','updateuser'));
$updatetypes['opening'] = array('icon' => 'open', 'text' => sprintf($strOpenedby,'updateuser'));
$updatetypes['phonecallout'] = array('icon' => 'callout', 'text' => sprintf($strPhonecallmadeby,'updateuser'));
$updatetypes['phonecallin'] = array('icon' => 'callin', 'text' => sprintf($strPhonecalltakenby,'updateuser'));
$updatetypes['reopening'] = array('icon' => 'reopen', 'text' => sprintf($strReopenedby,'updateuser'));
$updatetypes['slamet'] = array('icon' => 'sla', 'text' => sprintf($strSLAby,'updatesla', 'updateuser'));
$updatetypes['solution'] = array('icon' => 'solution', 'text' => sprintf($strResolvedby, 'updateuser'));
$updatetypes['webupdate'] = array('icon' => 'webupdate', 'text' => sprintf($strWebupdate));
$updatetypes['auto_chase_phone'] = array('icon' => 'chase', 'text' => $strRemind);
$updatetypes['auto_chase_manager'] = array('icon' => 'chase', 'text' => $strRemind);
$updatetypes['auto_chase_email'] = array('icon' => 'chased', 'text' => $strReminded);
$updatetypes['auto_chased_phone'] = array('icon' => 'chased', 'text' => $strReminded);
$updatetypes['auto_chased_manager'] = array('icon' => 'chased', 'text' => $strReminded);
$updatetypes['auto_chased_managers_manager'] = array('icon' => 'chased', 'text' => $strChased);
$updatetypes['customerclosurerequest'] = array('icon' => 'close', 'text' => $strCustomerRequestedClosure);
$updatetypes['fromtask'] = array('icon' => 'timer', 'text' => sprintf($strUpdatedFromActivity, 'updateuser'));
$slatypes['opened'] = array('icon' => 'open', 'text' => $strOpened);
$slatypes['initialresponse'] = array('icon' => 'initialresponse', 'text' => $strInitialResponse);
$slatypes['probdef'] = array('icon' => 'probdef', 'text' => $strProblemDefinition);
$slatypes['actionplan'] = array('icon' => 'actionplan', 'text' => $strActionPlan);
$slatypes['solution'] = array('icon' => 'solution', 'text' => $strSolution);
$slatypes['closed'] = array('icon' => 'close', 'text' => $strClosed);


// Language codes and language name in local language
// Aphabetical by language code
// This is the list of languages that SiT recognises, to configure which of
// these languages to use, make sure you have a file in your i18n dir and
// go to the sit configuration page
$i18n_codes = array(
                    'af' => 'Afrikaans',
                    'ar' => 'العربية',
                    'bg-BG' => 'български',
                    'bn-IN' => 'বাংলা',
                    'cs-CZ' => 'Český',
                    'ca-ES' => 'Català',
                    'cy-GB' => 'Cymraeg',
                    'da-DK' => 'Dansk',
                    'de-DE' => 'Deutsch',
                    'el-GR' => 'Ελληνικά',
                    'en-GB' => 'English (British)',
                    'en-US' => 'English (US)',
                    'es-ES' => 'Español',
                    'es-CO' => 'Español (Colombia)',
                    'es-MX' => 'Español (Mexicano)',
                    'et-EE' => 'Eesti',
                    'eu-ES' => 'Euskara',
                    'fa-IR' => 'فارسی',
                    'fi-FI' => 'Suomi',
                    'fo-FO' => 'føroyskt',
                    'fr-FR' => 'Français',
                    'he-IL' => 'עִבְרִית',
                    'hr-HR' => 'Hrvatski',
                    'hu-HU' => 'Magyar',
                    'id-ID' => 'Bahasa Indonesia',
                    'is-IS' => 'Íslenska',
                    'it-IT' => 'Italiano',
                    'ja-JP' => '日本語',
                    'ka' => 'ქართული',
                    'ko-KR' => '한국어',
                    'lt-LT' => 'Lietuvių',
                    'ms-MY' => 'بهاس ملايو',
                    'nb-NO' => 'Norsk (Bokmål)',
                    'nl-NL' => 'Nederlands',
                    'nn-NO' => 'Norsk (Nynorsk)',
                    'pl-PL' => 'Polski',
                    'pt-BR' => 'Português (Brasil)',
                    'pt-PT' => 'Português',
                    'ro-RO' => 'Română',
                    'ru-RU' => 'Русский',
                    'sl-SL' => 'Slovenščina',
                    'sk-SK' => 'Slovenčina',
                    'sr-YU' => 'Српски',
                    'sv-SE' => 'Svenska',
                    'th-TH' => 'ภาษาไทย',
                    'tr_TR' => 'Türkçe',
                    'uk-UA' => 'Украї́нська мо́ва',
                    'zh-CN' => '简体中文',
                    'zh-TW' => '繁體中文',
                    );


// List of timezones, with UTC offset in minutes
// Source: http://en.wikipedia.org/wiki/List_of_time_zones (where else?)
$availabletimezones = array('-720' => 'UTC-12',
                            '-660' => 'UTC-11',
                            '-600' => 'UTC-10',
                            '-570' => 'UTC-9:30',
                            '-540' => 'UTC-9',
                            '-480' => 'UTC-8',
                            '-420' => 'UTC-7',
                            '-360' => 'UTC-6',
                            '-300' => 'UTC-5',
                            '-270' => 'UTC-4:30',
                            '-240' => 'UTC-4',
                            '-210' => 'UTC-3:30',
                            '-180' => 'UTC-3',
                            '-120' => 'UTC-2',
                            '-60' => 'UTC-1',
                            '0' => 'UTC',
                            '60' => 'UTC+1',
                            '120' => 'UTC+2',
                            '180' => 'UTC+3',
                            '210' => 'UTC+3:30',
                            '240' => 'UTC+4',
                            '300' => 'UTC+5',
                            '330' => 'UTC+5:30',
                            '345' => 'UTC+5:45',
                            '360' => 'UTC+6',
                            '390' => 'UTC+6:30',
                            '420' => 'UTC+7',
                            '480' => 'UTC+8',
                            '525' => 'UTC+8:45',
                            '540' => 'UTC+9',
                            '570' => 'UTC+9:30',
                            '600' => 'UTC+10',
                            '630' => 'UTC+10:30',
                            '660' => 'UTC+11',
                            '690' => 'UTC+11:30',
                            '720' => 'UTC+12',
                            '765' => 'UTC+12:45',
                            '780' => 'UTC+13',
                            '840' => 'UTC+14',
                           );
?>