<?php
// MIME class for sending email
// This script was on the Internet as MIME.def and MIME.class and is assumed to be in the public domain
// Modified for SiT!

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}




/**
 *  Constants used inside the MIME sending class. This file is included
 *  by the class module and these constants an be freely
 *  used in scripts using MIME_mail class
*/
define ('BASE64', 'base64');
define ('BIT7', '7bit');
define ('QP', 'quoted-printable');
define ('NOSUBJECT', '(No Subject)');
define ('WARNING', 'This is a MIME encoded message');
define ('OCTET', 'application/octet-stream');
define ('TEXT', 'text/plain');
define ('HTML', 'text/html');
define ('JPEG', 'image/jpg');
define ('GIF', 'image/gif');
define ('CRLF', "\n");   // was \r\n
//define ('CHARSET', 'us-ascii');
define ('CHARSET', 'UTF-8');
define ('INLINE', 'inline');
define ('ATTACH', 'attachment');
define ('BODY', CRLF.'BODY'.CRLF);


/**
 * From MIME.class
 * MIME Class:
 * @note Allows creation of e-mail messages via the MIME Standard.
 * @note The class supports multiple attachments and presenting
 * @note an e-mail in HTML.
*/
class MIME_mail
{
    //public:
    var $to;
    var $from;
    var $subject;
    var $body;
    var $headers = '';
    var $errstr= '';

    // these are the names of the encoding functions, user
    // provide the names of user-defined functions

    var $base64_func= '';     # if !specified use PHP's base64
    var $qp_func = '';        # None at this time

    // If do not have a local mailer..use this array to pass info of an SMTP object
    // e.g. $mime_mail->mailer = array('name' => 'smtp', method => 'smtp_send()');
    // 'name' is the name of the object less the $ and 'method' can have parameters
    // specific to itself.  If you are using MIME_mail object's to, from, etc.
    // remember to send parameters a literal strings referring 'this' object!!!!
    // If in doubt, you are probably better off subclassing this class...
    var $mailer = ''; # Set this to the name of a valid mail object

    //private:
    var $mimeparts = array();

    // Constructor.
    function MIME_mail($from= '', $to= '', $subject= '', $body= '', $headers = '')
    {
        $this->to = $to;
        $this->from = $from;
        // $this->subject = $subject;
        $this->subject = encode_email_subject($subject, 'UTF-8');
        $this->body = $body;
        if (is_array($headers))
        {
            if (sizeof($headers)>1)
                $headers=join(CRLF, $headers);
            else
                $headers=$headers[0];
        }
        if ($from)
        {
            $headers = preg_replace("!(from:\ ?.+?[\r\n]?\b)!i", '', $headers);
        }
        $this->headers = chop($headers);
        $this->mimeparts[] = '' ;   //Bump up location 0;
        $this->errstr = '';
        return;
    }


    /**
    * Attach a 'file' to e-mail message
    * Pass a file name to attach.
    * This function returns a success/failure code/key of current
    * attachment in array (+1). Read attach() below.
    */
    function fattach($path, $description = '', $contenttype = OCTET, $encoding = BASE64, $disp = '')
    {
        $this->errstr = '';
        if (!file_exists($path))
        {
            $this->errstr = "File does not exist";
            return 0;
        }
        // Read in file
        $fp = fopen($path, "rb");   # (b)inary for Win compatability
        if (!$fp)
        {
            $this->errstr = "fopen() failed";
            return 0;   //failed
        }
        $contenttype .= ";\r\n\tname=".basename($path);
        $data = fread($fp, filesize($path));
        return $this->attach($data,
                $description,
                $contenttype,
                $encoding,
                $disp);
    }


    /**
    * Attach data provided by user (rather than a file)
    * Useful when you want to MIME encode user input
    * like HTML.
    * @param string $data
    * @param string $description
    * @param string $contenttype
    * @param string $encoding
    * @param string $disp Content Disposition
    * @note This function returns key at which the requested
    * data is attached. IT IS CURRENT KEY VALUE + 1!!
    * Construct the body with MIME parts
    */
    function attach($data, $description = '', $contenttype = OCTET, $encoding = BASE64, $disp = '')
    {
        $this->errstr = '';
        if (empty($data))
        {
            $this->errstr = "No data to be attached";
            return 0;
        }
        if (trim($contenttype) == '') $contenttype = OCTET ;
        if (trim($encoding) == '') $encoding = BASE64;
        if ($encoding == BIT7) $emsg = $data;
        elseif ($encoding == QP)
        {
            if (function_exists('quoted_printable_encode'))
            {
                // PHP 5.3.0 function quoted_printable_encode()
                $emsg = quoted_printable_encode($data);
            }
            else
            {
                //$emsg = $$this->qp_func($data);
                $emsg = imap_8bit($data);
//                 $emsg = chunk_split($emsg); // commented out by INL 5Aug09, not sure if it's needed, imap_8bit is supposed to split lines
            }
        }
        elseif ($encoding == BASE64)
        {
            if (!$this->base64_func)    # Check if there is user-defined function
                $emsg = base64_encode($data);
            else
                $emsg = $$this->base64_func($data);
                $emsg = chunk_split($emsg);
        }
        //Check if content-type is text/plain and if charset is not specified append default CHARSET
        if (preg_match("/^text\/plain/i", $contenttype) && !preg_match("/;charset=/i", $contenttype))
        {
            $contenttype .= ";".CRLF."\tcharset=".CHARSET ;
        }
        $msg = sprintf("Content-Type: %sContent-Transfer-Encoding: %s%s%s%s",
        $contenttype.CRLF,
        $encoding.CRLF,
        ((($description) && (BODY != $description)) ? "Content-Description: $description".CRLF:""),
        ($disp ? "Content-Disposition: $disp".CRLF:""),
        CRLF.$emsg.CRLF);
        BODY == $description ? $this->mimeparts[0] = $msg : $this->mimeparts[] = $msg;
        return sizeof($this->mimeparts);
    }


    /**
    * private:
    * Construct mail message header from info already given.
    * This is a very important function.  It shows how exactly
    * the MIME message is constructed.
    */
    function build_message()
    {
        $this->errstr = '';
        $msg = '';
        $boundary = 'SITMIME200908'.chr(rand(65, 91)).md5(uniqid(rand()));    # Boundary marker
        $nparts = sizeof($this->mimeparts);

        if (is_array($this->mimeparts) && ($nparts > 1))
        {
            // Case 1: Attachment list is there.  Therefore MIME Message header must have multipart/mixed
            debug_log("case1, $nparts");
            $c_ver = "MIME-Version: 1.0".CRLF;
            $c_type = 'Content-Type: multipart/mixed;'.CRLF."\tboundary=\"$boundary\"".CRLF;
            //INL   $c_enc = "Content-Transfer-Encoding: ".BIT7.CRLF;
            $c_enc = '';
            $c_desc = $c_desc?"Content-Description: $c_desc".CRLF:"";
            $warning = CRLF.CRLF.WARNING.CRLF.CRLF ;

            // Since we are here, it means we do have attachments => body must become an attachment too.
            if (!empty($this->body))
            {
                $this->attach($this->body, BODY, TEXT, BIT7);
            }

            // Now create the MIME parts of the email!
            for ($i=0 ; $i < $nparts; $i++)
            {
                if (!empty($this->mimeparts[$i]))
                {
                    $msg .= CRLF.'--'.$boundary.CRLF.$this->mimeparts[$i].CRLF;
                }
            }
            $msg .= '--'.$boundary.'--'.CRLF;
            $msg = $c_ver.$c_type.$c_enc.$c_desc.$warning.$msg;
            debug_log("Message: $msg");
        }
        else
        {
            // Case 2: No attachments list
            debug_log('case2');
            if (!empty($this->body)) $msg .= $this->body.CRLF.CRLF;
        }

        return $msg;
    }

    /**
    * public:
    * Now Generate the entire Mail Message, header and body et al.
    */
    function gen_email($force=false)
    {
        $this->errstr = '';
        if (!empty($this->email) && !$force) return $this->email ;  // saves processing
        $email = '';
        if (empty($this->subject)) $this->subject = NOSUBJECT;
        if (!empty($this->from)) $email .= 'From: '.$this->from.CRLF;
        if (!empty($this->headers)) $email .= $this->headers.CRLF;
        $email .= $this->build_message();
        $this->email = $email;
        return $this->email;
    }

    /**
    * public:
    * Printable form
    */
    function print_mail($force=false)
    {
        $this->errstr = '';
        $email =  $this->gen_email($force);
        if (!empty($this->to)) $email = 'To: '.$this->to.CRLF.$email;
        if (!empty($this->subject)) $email = 'Subject: '.$this->subject.CRLF.$email;
        print $email;
    }


    /**
    * public:
    * Send mail via local mailer
    */
    function send_mail($force=false)
    {
        $this->errstr = '';
        $email = $this->gen_email($force);
        if (empty($this->to)) {
            $this->errstr = "To Address not specified";
            return 0;
        }
        if (is_array($this->mailer) && (1 == sizeof($this->mailer)) ) {
            $mail_obj = $this->mailer['name'];
            $mail_method = $this->mailer['method'];
            if (empty($mail_obj)) {
                $this->errstr = "Invalid object name passed to send_mail()";
                return 0;
            }
            global $mail_obj;
            eval("$ret = \$$mail_obj".'->'."$mail_method;");
            return $ret;
        }
        $splitmsg = explode(CRLF.CRLF, $email, 2);
        return mail($this->to, $this->subject, $splitmsg[1], $splitmsg[0]);
    }
}


?>