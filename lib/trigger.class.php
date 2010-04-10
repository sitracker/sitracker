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

/**
 * Receives the Trigger Event and finds the appropriate trigger to fire
 */
class TriggerEvent {
    function TriggerEvent($trigger_type, $param_array = '')
    {
        global $sit, $CONFIG, $dbg, $dbTriggers, $trigger_types, $dbTriggers;
        
        $trigger_type = cleanvar($trigger_type);
        // Check that this is a defined trigger
        if (!array_key_exists($trigger_type, $trigger_types))
        {
            trigger_error("Trigger '{$this->trigger_type}' not defined", E_USER_WARNING);
            return;
        }

        //find relevant triggers
        $sql = "SELECT * FROM `{$dbTriggers}` ";
        $sql .= "WHERE triggerid='{$trigger_type}'";
        $result = mysql_query($sql);
        if (mysql_error())
        {
            trigger_error("MySQL Query Error " . 
                          mysql_error(), E_USER_WARNING);
            return FALSE;
        }

        while ($trigger = mysql_fetch_object($result))
        {
            $trigger = new Trigger($trigger_type, $param_array, 
                                   $trigger->userid, $trigger->template, 
                                   $trigger->action, $trigger->checks, 
                                   $trigger->parameters);
            $rtn = $trigger->fire();
        }

        // we always return TRUE as all triggers might not match
        return TRUE;
    }
}


class Trigger extends SitEntity {
    function retrieveDetails(){}
    
    function add()
    {
        global $dbTriggers;
        $exists = check_exists($this->trigger_type, $this->param_array, 
                               $this->userid, $this->template, $this->action, 
                               $this->checks, $this->parameters);

        if (!$exists)
        {
            $sql = "INSERT INTO `{$dbTriggers}` (triggerid, userid, action, ";
            $sql .= "template, parameters, checks) ";
            $sql .= "VALUES ('{$this->trigger_type}', '{$this->user_id}', ";
            $sql .= "'{$this->action}', '{$this->template}', ";
            $sql .= "'{$this->parameters}', '{$this->checks}')";
            mysql_query($sql);
            if (mysql_error()) 
            {
                $this->error_text .= trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                return FALSE;
            }
            else return TRUE;
        }
        else
        {
            return FALSE;
        }
        
    }
    function edit()
    {
        global $dbTriggers;
        if ($this->trigger_id !== -1)
        {
            $sql = "UPDATE `{$dbTriggers}` ";
            $sql .= "SET triggerid = '$this->trigger_type', ";
            $sql .= "userid = '{$this->user_id}', ";
            $sql .= "action = '{$this->action}', ";
            $sql .= "template = '{$this->template}' ";
            $sql .= "parameters = '{$this->parameters}' ";
            $sql .= "checks = '{$this->checks}' ";
            $sql .= "WHERE id = {$this->trigger_id}";
            mysql_query($sql);
            if (mysql_error()) 
            {
                $this->error_text .= trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);
                return FALSE;
            }
            else return TRUE;
        }
        else
        {
            $this->error_text .= "Error: Not a valid trigger ID provided\n";
            return FALSE;
        }
    }
    function getSOAPArray(){}
    
    /**
     * ID of the trigger's entry in the database
     * @var int
     */
    private $trigger_id;

    /**
     * Name of the trigger type
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

    /**
     * The user the trigger applies to, ID of 0 means this is a system action
     * @var integer
     */
    private $user_id;

    /**
     * The template the trigger uses
     * @var string
     */
    private $template;

    /**
     * The action the trigger uses, this is arbitrary as actions can be 
     * provided by plugins etc
     * @var string
     */
    private $action;

    /**
     * The checks to validate before firing the trigger, e.g. {userid} == 1
     * @var string
     */
    private $checks;

    /**
     * Extra parameters to add onto the provided variables, e.g. numupdates = 2
     * @var string
     */
    private $parameters;
    
    /**
     * If the trigger fails, put the errors here
     */
    private $error_text;

    /**
     * Constructs a new Trigger object
     */
    function Trigger($trigger_type, $param_array, $user_id, $template, 
                     $action, $checks, $parameters, $trigger_id = -1)
    {
        $this->trigger_type = cleanvar($trigger_type);
        $this->param_array = cleanvar($param_array);
        $this->user_id = cleanvar($user_id);
        $this->template = cleanvar($template);
        $this->action = cleanvar($action);
        $this->checks = cleanvar($checks);
        $this->parameters = cleanvar($parameters);
        $this->trigger_id = cleanvar($trigger_id);
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

        //see if we have any checks first
        if (!empty($this->checks))
        {
            $checks = trigger_replace_specials($this->trigger_type, $this->checks, $this->param_array);
            $eresult = eval("\$value = $checks;return TRUE;");

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
                return;
            }                           
        }

        // if we have any stored parameters from the trigger, append to
        // the dynamic ones
        if (!empty($this->parameters))
        {   
            $resultparams = explode(",", $this->parameters);
            foreach ($resultparams as $assigns)
            {
                $values = explode(" = ", $assigns);
                $this->param_array[$values[0]] = $values[1];
                $dbg .= "\$this->param_array[{$values[0]}] = {$values[1]}";
            }
            debug_log("Trigger parameters:\n.$dbg", TRUE);
        }

        debug_log("trigger_action({$this->trigger_type}, {$this->action}, 
                    {$this->param_array}) called", TRUE);

        $return = $this->trigger_action($this->action,
                                        $this->template);
        
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

        //if we have an incidentid, get it to pass to trigger_replace_specials($this->trigger_type, )
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
        $this->param_array['triggeruserid'] = $this->user_id;

        $from = $this->trigger_replace_specials($this->trigger_type, $template->fromfield, $this->param_array);
        $toemail = $this->trigger_replace_specials($this->trigger_type, $template->tofield, $this->param_array);
        $replytoemail = $this->trigger_replace_specials($this->trigger_type, $template->replytofield, $this->param_array);
        $ccemail = $this->trigger_replace_specials($this->trigger_type, $template->ccfield, $this->param_array);
        $bccemail = $this->trigger_replace_specials($this->trigger_type, $template->bccfield, $this->param_array);
        $subject = cleanvar($this->trigger_replace_specials($this->trigger_type, $template->subjectfield, $this->param_array));
        $body .= $this->trigger_replace_specials($this->trigger_type, $template->body, $this->param_array);
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

            $notice_text = mysql_real_escape_string(trigger_replace_specials($this->trigger_type, $notice_text, $this->param_array));
            $noticelinktext = cleanvar(trigger_replace_specials($this->trigger_type, $noticelinktext, $this->param_array));
            $noticelink = cleanvar(trigger_replace_specials($this->trigger_type, $notice->link, $this->param_array));
            $refid = cleanvar(trigger_replace_specials($this->trigger_type, $notice->refid, $this->param_array));
            $durability = $notice->durability;
            debug_log("notice: $notice_text", TRUE);
            
            /** Not sure this makes sense KH 10/04/10
            if ($user_id == 0 AND $this->param_array['userid'] > 0)
            {
                $user_id = $this->param_array['userid'];
            } */

            $sql = "INSERT INTO `{$dbNotices}` (userid, type, text, linktext,";
            $sql .= " link, durability, referenceid, timestamp) ";
            $sql .= "VALUES ('{$this->user_id}', '{$notice->type}', '{$notice_text}',";
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
    private function check_exists($action, $templateid, $rules, $parameters)
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
