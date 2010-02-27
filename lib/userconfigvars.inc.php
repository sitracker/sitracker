<?php
// userconfigvars.inc.php - List of user configuration variables
//                      and functions to manage them
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Author: Ivan Lucas, <ivanlucas[at]users.sourceforge.net

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

$CFGTAB['application'] = array('displayprefs', 'localeprefs');
$TABI18n['application'] = $strApplication;

$CFGCAT['displayprefs'] = array('theme','iconset',
                               'incident_refresh',
                               'incident_log_order',
                               'updates_per_page',
                               'show_emoticons'
                               );

$CFGCAT['localeprefs'] = array('language','utc_offset');


// i18n keys for categories
$CATI18N['displayprefs'] = $strTheme;
$CATI18N['localeprefs'] = $strLocale;

// Descriptions of all the config variables
// each config var may have these elements:
//      title   - A title/short description of the configuration variable
//      help    - A line of instructions/help to assist the user configuring
//      helplink - A help context label for help/en-GB/help.txt type help
//      type - A datatype, see cfgVarInput() for list
//      unit - A unit string to print after the input
//      options - A pipe seperated list of optios for a 'select' type


$CFGVAR['iconset']['title'] = 'Icon set';

$CFGVAR['language']['title'] = $strLanguage;
$CFGVAR['language']['type'] = 'languageselect';

$CFGVAR['incident_log_order']['title'] = $strIncidentLogOrder;
$CFGVAR['incident_log_order']['type'] = 'select';
$CFGVAR['incident_log_order']['options'] = 'asc|desc';

$CFGVAR['incident_refresh']['title'] = $strIncidentRefresh;
$CFGVAR['incident_refresh']['type'] = 'number';
$CFGVAR['incident_refresh']['unit'] = $strSeconds;

$CFGVAR['theme']['title'] = $strInterfaceStyle;
$CFGVAR['theme']['type'] = 'interfacestyleselect';

$CFGVAR['show_emoticons']['title'] = $strShowEmoticons;
$CFGVAR['show_emoticons']['type'] = 'select';

$CFGVAR['updates_per_page']['title'] = $strIncidentUpdatesPerPage;
$CFGVAR['updates_per_page']['type'] = 'number';

$CFGVAR['utc_offset']['title'] = $strUTCOffset;
$CFGVAR['utc_offset']['type'] = 'timezoneselect';


if (function_exists('plugin_do'))
{
    // Plugin_do won't always be available in this file, because we use this
    // file for setup as well, no plugins before sit is installed.
    plugin_do('usercfgvar');
}
?>