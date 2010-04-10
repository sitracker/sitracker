<?php
// triggers.class.php - A representation of a trigger
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.


// This lib is currently included at the end of auth.inc.php

//include_once (APPLICATION_LIBPATH . 'incident.inc.php');
include_once (APPLICATION_LIBPATH . 'billing.inc.php');
include_once (APPLICATION_LIBPATH . 'mime.inc.php');
include_once (APPLICATION_LIBPATH . 'triggers.inc.php');

class Trigger extends SitEntity {
    function retrieveDetails(){}
    function add(){}
    function edit(){}
    function getSOAPArray(){}
      
    /**
     * ID of the trigger type
     *
     * This is the type of trigger, e.g. TRIGGER_ADD_INCIDENT and is used to
     * find which users/system actions are assigned to that particuar trigger
     * @var string 
     */
    private $trigger_type;
    
    /**
     * The array of parameters
     *
     * Extended and optional trigger variables are passed to the trigger in
     * this array
     * @var array
     */
    private $param_array;
    private $user_id;

    /**
     * Constructs a new Trigger object
     */
    function Trigger($trigger_type, $param_array = '')
    {
        $this->trigger_type = cleanvar($trigger_type);
        $this->paramarray = cleanvar($param_array);
        debug_log("Trigger {$trigger_type} created. Options:\n" . 
            print_r($param_array, TRUE));
    }

    /**
    * "Fires" the current trigger object, this means it has occurred
    * @author Kieran Hogg
    * @param $trigger_type string The name of the trigger to fire
    * @param $param_array array Extra parameters to pass the trigger
    * @return bool TRUE if the trigger created successfully, FALSE if not
    */
    function fire()
    {
        global $sit, $CONFIG, $dbg, $dbTriggers, $trigger_types;
        global $dbTriggers;

        // Check that this is a defined trigger
        if (!array_key_exists($this->trigger_type, $trigger_types))
        {
            trigger_error("Trigger '{$this->trigger_type}' not defined", E_USER_WARNING);
            return;
        }
        plugin_do($this->trigger_type);

        if (isset($param_array['user']))
        {
            $user_id = $this->param_array[$key];
        }

        //find relevant triggers
        $sql = "SELECT * FROM `{$dbTriggers}` ";
        $sql .= "WHERE triggerid='{$this->trigger_type}'";
        if ($user_id != '')
        {
            $sql .= " AND userid={$user_id}";
        }
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error("MySQL Query Error " . 
                          mysql_error(), E_USER_WARNING);
        }

        while ($triggerobj = mysql_fetch_object($result))
        {
            //see if we have any checks first
            if (!empty($triggerobj->checks))
            {
                // commented out 09/09/09 as I'm 99% this code is bollocks
                //if (!trigger_checks($triggerobj->checks))
                //{
                    $checks = $this->trigger_replace_specials($triggerobj->checks);
		    //print_r("'".$triggerobj->checks."'" . "<br />" . $checks);
                    $eresult = @eval("\$value = $checks;return TRUE;");
                    if (!$eresult)
                    {
                        trigger_error("Error in trigger rule for 
                                      {$this->trigger_type}, check your 
                                      <a href='triggers.php'>trigger rules</a>", 
                                      E_USER_WARNING);
                    }
                    
                    // if we fail, we jump to the next trigger
                    if ($value === FALSE)
                    {
                        continue;
                    }
                //}
            }

            // if we have any stored parameters from the trigger, append to
            // the dynamic ones
            if (!empty($triggerobj->parameters))
            {
                $resultparams = explode(",", $triggerobj->parameters);
                foreach ($resultparams as $assigns)
                {
                    $values = explode("=", $assigns);
                    $this->param_array[$values[0]] = $values[1];
                    $dbg .= "\$this->param_array[{$values[0]}] = {$values[1]}";
                }
                debug_log("Trigger parameters:\n.$dbg", TRUE);
            }

            debug_log("trigger_action({$triggerobj->userid}, 
                      {$this->trigger_type}, {$triggerobj->action}, 
                      {$this->param_array}) called", TRUE);

            $return = $this->trigger_action($triggerobj->action,
                                            $triggerobj->template);
        }
        return $return;
    }


    /**
        * Do the specific action for the specific user for a trigger
        * @author Kieran Hogg
        * @param $action string The type of action to perform
        * @param $template
        * @return boolean. TRUE if the user has the permission, otherwise FALSE
    */
    private function trigger_action($action, $template)
    {
        global $CONFIG, $dbg, $dbTriggers;

        switch ($action)
        {
            case "ACTION_EMAIL":
                debug_log("send_trigger_email($template) called", TRUE);
                $rtnvalue = $this->send_trigger_email($sit[2], $template);
                break;

            case "ACTION_NOTICE":
                debug_log("create_trigger_notice($template) called", TRUE);
                $rtnvalue = $this->create_trigger_notice($template);
                break;

            case "ACTION_CREATE_INCIDENT":
                debug_log("creating incident with holdingemailid: 
                    {$this->param_array['holdingemailid']}", TRUE);
                $rtnvalue = $this->create_incident_from_incoming(
                    $this->param_array['holdingemailid']);
                break;

            case "ACTION_JOURNAL":
                if (is_array($this->param_array))
                {
                    foreach (array_keys($this->param_array) AS $param)
                    {
                        $jtext .= "$param: {$this->param_array[$param]}; ";
                    }
                }
                else
                {
                    $jtext = '';
                }

                $rtnvalue = journal(CFG_LOGGING_NORMAL, $this->trigger_type, 
                                    "Trigger Fired ({$jtext})", 
                                    CFG_JOURNAL_TRIGGERS, $this->user_id);
                break;

            case "ACTION_NONE":
            //fallthrough
            default:
                break;
        }

        return $rtnvalue;
    }


    /**
        * Replaces template variables with their values
        * @author Kieran Hogg, Ivan Lucas
        * @param $string_array string The string containing the variables
        * @return string The string with variables replaced
    */
    private function trigger_replace_specials($string_array)
    {
        global $CONFIG, $application_version, $application_version_string, $dbg;
        global $dbIncidents;
        global $trigger_types, $ttvararray;

        debug_log("notice string before: $string_array", TRUE);
        //this loops through each variable and creates an array of useable varaibles' regexs
        foreach ($ttvararray AS $identifier => $ttvar)
        {
            $multiple = FALSE;
            foreach ($ttvar AS $key => $value)
            {
                //this checks if it's a multiply-defined variable
                if (is_numeric($key))
                {
                    $trigger_replaces = replace_vars($this->trigger_type, $ttvar[$key], $identifier);
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
                $trigger_replaces = replace_vars($this->trigger_type, $ttvar, $identifier);
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
        * Sends an email for a trigger
        * @author Kieran Hogg
        * @param $user_id integer. The user to send the email to
        * @param $template string. The name of the email template to use
        * trigger
    */
    private function send_trigger_email($user_id, $template)
    {
        global $CONFIG, $dbg, $dbEmailTemplates;
        if ($CONFIG['debug'])
        {
            $dbg .= "TRIGGER: send_trigger_email({$user_id},{$trigger_type}, {$this->param_array})\n";
        }
        // $trigger_types[$this->trigger_type]['type'])

        //if we have an incidentid, get it to pass to trigger_replace_specials()
        if (!empty($this->param_array['incidentid']))
        {
            $incidentid = $this->param_array['incidentid'];
        }

        $sql = "SELECT * FROM `{$dbEmailTemplates}` WHERE name='{$template}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        if ($result)
        {
            $template = mysql_fetch_object($result);
        }

        //add this in manually, this is who we're sending the email to
        $this->param_array['triggeruserid'] = $user_id;

        $from = $this->trigger_replace_specials($template->fromfield);
        $toemail = $this->trigger_replace_specials($template->tofield);
        $replytoemail = $this->trigger_replace_specials($template->replytofield);
        $ccemail = $this->trigger_replace_specials($template->ccfield);
        $bccemail = $this->trigger_replace_specials($template->bccfield);
        $subject = cleanvar($this->trigger_replace_specials($template->subjectfield));
        $body .= $this->trigger_replace_specials($template->body);
        if (!empty($from) AND !empty($toemail) AND !empty($subject) AND !empty($body))
        {
            $mailok = send_email($toemail, $from, $subject, $body, $replytoemail, $ccemail, $bccemail);
            if ($mailok == FALSE)
            {
                trigger_error('Internal error sending email from trigger: '. $mailerror.' send_email() failed', E_USER_ERROR);
                $return = FALSE;
            }
            else
            {
                $return = TRUE;
            }
        }
        else
        {
            $return = FALSE;
        }
        return $return;
    }


    /**
        * Creates a trigger notice
        * @author Kieran Hogg
        * @param $template string. The name of the email template to use
    */
    private function create_trigger_notice($template)
    {
        global $CONFIG, $dbg, $dbNotices, $dbNoticeTemplates;

        $sql = "SELECT * FROM `{$dbNoticeTemplates}` WHERE name='{$template}'";
        $query = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        if ($query)
        {
            $notice = mysql_fetch_object($query);

            if (substr($notice->text, 0, 3) == 'str')
            {
                $notice_text = $GLOBALS[$notice->text];
            }
            else
            {
                $notice_text = $notice->text;
            }

            if (substr($notice->linktext, 0, 3) == 'str')
            {
                $noticelinktext = $GLOBALS[$notice->linktext];
            }
            else
            {
                $noticelinktext = $notice->linktext;
            }

            $notice_text = mysql_real_escape_string($this->trigger_replace_specials($notice_text));
            $noticelinktext = cleanvar($this->trigger_replace_specials($noticelinktext));
            $noticelink = cleanvar($this->trigger_replace_specials($notice->link));
            $refid = cleanvar($this->trigger_replace_specials($notice->refid));
            $durability = $notice->durability;
            debug_log("notice: $notice_text", TRUE);;

            if ($user_id == 0 AND $this->param_array['userid'] > 0)
            {
                $user_id = $this->param_array['userid'];
            }

            $sql = "INSERT INTO `{$dbNotices}` (userid, type, text, linktext,";
            $sql .= " link, durability, referenceid, timestamp) ";
            $sql .= "VALUES ({$user_id}, '{$notice->type}', '{$notice_text}',";
            $sql .= " '{$noticelinktext}', '{$noticelink}', '{$durability}', ";
            $sql .= "'{$refid}', NOW())";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            $return = TRUE;
        }
        else
        {
            trigger_error("No such trigger type", E_USER_WARNING);
            $return = FALSE;
        }

        return $return;
    }


    /**
        * Checks array of parameters against list of parameters
        * @author Kieran Hogg
        * @param $check_strings string. The list of required parameters
        * @param $this->param_array array. The array to compare the strings to
        * @returns TRUE if the string parameter is in the array, FALSE if not
    */
//     private function trigger_checks($check_strings)
//     {
//         global $dbSites, $dbIncidents, $dbContacts;
//         $passed = FALSE;
// 
//         $checks = explode(",", $check_strings);
//         foreach ($checks as $check)
//         {
//             $values = explode("=", $check);
//             switch ($values[0])
//             {
//                 case 'siteid':
//                     $sql = "SELECT s.id AS siteid ";
//                     $sql .= "FROM `{$dbSites}` AS s, `{$dbIncidents}` AS i, `{$dbContacts}` ";
//                     $sql .= "WHERE i.id={$this->param_array[incidentid]} ";
//                     $sql .= "AND i.contact=c.id ";
//                     $sql .= "AND s.id=c.siteid";
//                     $query = mysql_query($sql);
//                     if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
//                     if ($query)
//                     {
//                         $result = mysql_fetch_object($query);
//                         $siteid = $result->siteid;
//                         if ($siteid == $values[1])
//                         {
//                             $passed = TRUE;
//                         }
//                     }
//                 break;
// 
//                 case 'contactid':
//                     $sql = "SELECT c.id AS contactid ";
//                     $sql .= "FROM `{$dbIncidents}` AS i, `{$dbContacts}` AS c ";
//                     $sql .= "WHERE i.id={$this->param_array[incidentid]} ";
//                     $sql .= "AND i.contact=c.id ";
//                     $query = mysql_query($sql);
//                     if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
//                     if ($query)
//                     {
//                         $result = mysql_fetch_object($query);
//                         $contactid = $result->contactid;
//                         if ($contactid == $values[1])
//                         {
//                             $passed = TRUE;
//                         }
//                     }
//                 break;
// 
//                 case 'userid':
//                     $sql = "SELECT i.owner AS userid ";
//                     $sql .= "FROM `{$dbIncidents}` AS i ";
//                     $sql .= "WHERE i.id='{$this->param_array[incidentid]}' ";
//                     $query = mysql_query($sql);
//                     if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
//                     if ($query)
//                     {
//                         $result = mysql_fetch_object($query);
//                         $user_id = $result->userid;
//                         if ($user_id == $values[1])
//                         {
//                             $passed = TRUE;
//                         }
//                     }
//                 break;
// 
//                 case 'sla':
//                     $sql = "SELECT i.servicelevel AS sla ";
//                     $sql .= "FROM `{$dbIncidents}` AS i ";
//                     $sql .= "WHERE i.id={$this->param_array[incidentid]} ";
//                     $query = mysql_query($sql);
//                     if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
//                     if ($query)
//                     {
//                         $result = mysql_fetch_object($query);
//                         $sla = $result->sla;
//                         if ($sla == $values[1])
//                         {
//                             $passed = TRUE;
//                         }
//                     }
//                 break;
// 
//                 default:
//                     //blank
//                 break;
//             }
//         }
//         return $passed;
//     }


    /**
        * Revokes any triggers of that type/reference
        * @author Kieran Hogg
        * @param $reference_id integer Reference of the notice
    */
    //TODO should this be limited to one delete, is there ever more than one?
    private function revoke($reference_id = 0)
    {
        global $GLOBALS;
        //find all triggers of this type and user
        $sql = "SELECT * FROM `{$GLOBALS['dbTriggers']}` ";
        $sql .= "WHERE triggerid = '{$this->trigger_type}' ";
        $sql .= "AND userid = {$this->user_id} ";
        $sql .= "AND action='ACTION_NOTICE' AND template != 0";
        $result = mysql_query($sql);
        if ($result AND mysql_num_rows($result) > 0)
        {
            while ($triggerobj = mysql_fetch_object($result))
            {
                $templatesql = "DELETE FROM {$GLOBALS['dbNotices']} ";
                $templatesql .= "WHERE template = {$triggerobj->template} ";
                $templatesql .= "AND userid = {$user_id} ";

                if ($referenceid != 0)
                {
                    $templatesql .= "AND referenceid = {$referenceid}";
                }
                $result = mysql_query($templatesql);
                if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
            }
        }
    }


    /**
    * Checks is a specified trigger already exists
    * @author Kieran Hogg
    * @param $action enum 'ACTION_NONE', 'ACTION_JOURNAL', 'ACTION_EMAIL', 'ACTION_NOTICE', 'ACTION_CREATE_INCIDENT'
    * @param $templateid int ID of the template
    * @param $rules string The trigger rules
    * @param $parameters string The trigger parameters
    */
    private function check_trigger_exists($action, $templateid, $rules, $parameters)
    {
        global $dbTriggers;
        $rtn = FALSE;

        $sql = "SELECT * FROM `{$dbTriggers}` ";
        $sql .= "WHERE triggerid = '{$this->trigger_type}' AND userid = '{$this->user_id}' AND action = '{$action}'";
        $sql .= "AND template = '{$templateid}' AND parameters = '{$parameters}' ";
        $sql .= "AND checks = '{$rules}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
        if (mysql_num_rows($result) != 0)
        {
            $rtn = TRUE;
        }

        return $rtn;
    }
}
?>
