<?php
// mailbox.class.php - Incoming POP/IMAP mailbox class
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2013 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


class Mailbox
{
    var $username;
    var $password;
    var $server;
    var $email;
    var $mailbox;
    var $servertype;
    //Append
    function Mailbox($username, $password, $email, $server =
                          'localhost', $servertype = 'pop', $port = '',
                          $options = '')
    {
        global $CONFIG;
        if (!empty($CONFIG['email_incoming_folder']))
        {
            $folder = $CONFIG['email_incoming_folder'];
        }
        else
        {
            $folder = 'INBOX';
        }
        if ($servertype == 'imap')
        {
            if (empty($port))
            {
                $port = '143';
            }
            $connectionString = "{{$server}:{$port}/imap{$options}".
                                 "/user={$username}}$folder";
        }
        else
        {
            if (empty($port))
            {
                $port = '110';
            }
            $connectionString = "{{$server}:{$port}/pop3{$options}".
                                "/user={$username}}$folder";
        }
        $this->username = $username;
        $this->password = $password;
        $this->server = $connectionString;
        $this->email = $email;
        $this->servertype = $servertype;
    }

    function connect()
    {
        $this->mailbox = imap_open($this->server, $this->username,
                                   $this->password, CL_EXPUNGE);
        if ($this->mailbox)
        {
            return TRUE;
        }
        else
        {
            debug_log(imap_last_error());
            return FALSE;
        }
    }

    function getNumUnreadEmails()
    {
        $headers = imap_headers($this->mailbox);
        return count($headers);
    }

    function messageBody($id)
    {
        global $CONFIG;
        if ($CONFIG['debug']) debug_log("Retrieving message {$id} from server\n");
        if (imap_body($this->mailbox, $id))
        {
            return imap_body($this->mailbox, $id);
        }
        else
        {
            debug_log("Died on message {$id} with: ".imap_last_error());
        }
    }

    function getMessageHeader($id)
    {
        return imap_fetchheader($this->mailbox, $id);
    }

    function deleteEmail($id)
    {
        imap_delete($this->mailbox, $id) OR debug_log(imap_last_error());
    }

    function iso8859Decode($text)
    {
        return imap_utf7_encode($text);
    }

    function archiveEmail($id)
    {
        global $CONFIG;
        if ($CONFIG['debug']) debug_log("Moving mail to {$CONFIG['email_archive_folder']} folder");
        return imap_mail_move($this->mailbox, $id, $CONFIG['email_archive_folder']) OR debug_log(imap_last_error());
    }
}
?>