<?php
// forgotpwd.php - Forgotten password page
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//
// Authors: Paul Heaney <paulheaney[at]users.sourceforge.net>
//          Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//          Kieran Hogg <kieran[at]sitracker.org>


$permission = PERM_NOT_REQUIRED; // not required
require ('core.php');

session_name($CONFIG['session_name']);
session_start();
require (APPLICATION_LIBPATH . 'strings.inc.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
require (APPLICATION_LIBPATH . 'trigger.class.php');
$title = $strForgottenDetails;

// External variables
$email = clean_dbstring($_REQUEST['emailaddress']);
$username = clean_dbstring($_REQUEST['username']);
$userid = clean_int($_REQUEST['userid']);
$contactid = clean_int($_REQUEST['contactid']);

if (!empty($userid))
{
    $mode = 'user';
}
elseif (!empty($contactid))
{
    $mode = 'contact';
}
$userhash = cleanvar($_REQUEST['hash']);

switch ($_REQUEST['action'])
{
    case 'forgotpwd':
    case 'sendpwd':
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        // First look to see if this is a SiT user
        if (empty($email) AND !empty($userid))
        {
            $sql = "SELECT id, username, password FROM `{$dbUsers}` WHERE id = '{$userid}' LIMIT 1";
        }
        else
        {
            $sql = "SELECT id, username, password FROM `{$dbUsers}` WHERE email = '{$email}' LIMIT 1";
        }
        $userresult = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        $usercount = mysql_num_rows($userresult);
        $userdetails = mysql_fetch_object($userresult);
        if ($usercount == 1)
        {
            $hash = md5($userdetails->username.'.'.$userdetails->password);
            $reseturl = "{$CONFIG['application_uriprefix']}{$CONFIG['application_webpath']}forgotpwd.php?action=confirmreset&userid={$userdetails->id}&hash={$hash}";
            $t = new TriggerEvent('TRIGGER_USER_RESET_PASSWORD', array('userid' => $userdetails->id, 'passwordreseturl' => $reseturl));
            echo "<h3 align='center'>{$strInformationSent}</h3>";
            echo "<p align='center'>{$strInformationSentRegardingSettingPassword}</p>";
            if ($_REQUEST['action'] == 'forgotpwd')
            {
                echo "<p align='center'><a href='index.php'>{$strBackToLoginPage}</a></p>";
            }
            else
            {
                echo "<p align='center'><a href='{$_SERVER['HTTP_REFERER']}'>{$strReturnToPreviousPage}</a></p>";
            }

        }
        else
        {
            // This is a SiT contact, not a user
            if (empty($email) AND !empty($contactid))
            {
               $sql = "SELECT id, username, password, email FROM `{$dbContacts}` WHERE id = '{$contactid}' LIMIT 1";
            }
            else
            {
                $sql = "SELECT id, username, password, email FROM `{$dbContacts}` WHERE email = '{$email}' LIMIT 1";
            }
            $contactresult = mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

            $contactcount = mysql_num_rows($contactresult);
            if ($contactcount == 1)
            {
                $row = mysql_fetch_object($contactresult);
                $hash = md5($row->username.'.'.$row->password);
                $reseturl = "{$CONFIG['application_uriprefix']}{$CONFIG['application_webpath']}forgotpwd.php?action=confirmreset&contactid={$row->id}&hash={$hash}";
                $t = new TriggerEvent('TRIGGER_CONTACT_RESET_PASSWORD', array('contactid' => $row->id, 'passwordreseturl' => $reseturl));
                echo "<h3>{$strInformationSent}</h3>";
                echo "<div align='center'>";
                echo "<p>{$strInformationSentRegardingSettingPassword}</p>";
                if (empty($email) AND !empty($contactid))
                {
                   echo "<p><a href='contact_details.php?id={$contactid}'>{$strContactDetails}</a></p>";
                }
                else
                {
                    echo "<p align='center'><a href='index.php'>{$strBackToLoginPage}</a></p>";
                }
                echo "</div>";
            }
            else
            {
                echo "<h3>{$strInvalidEmailAddress}</h3>";
                echo "<p align='center'>".sprintf($strForFurtherAssistance, $CONFIG['support_email'])."</p>";
                echo "<p align='center'><a href='index.php'>{$strBackToLoginPage}</a></p>";
                echo "</div>";
            }
        }
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
        break;

    case 'confirmreset':
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        if ($mode == 'user')
        {
            $sql = "SELECT id, username, password FROM `{$dbUsers}` WHERE id = '{$userid}' LIMIT 1";
        }
        elseif ($mode == 'contact')
        {
            $sql = "SELECT id, username, password FROM `{$dbContacts}` WHERE id = '{$contactid}' LIMIT 1";
        }
        $userresult = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        $usercount = mysql_num_rows($userresult);
        if ($usercount > 0)
        {
            $userdetails = mysql_fetch_object($userresult);
            $hash = md5($userdetails->username.'.'.$userdetails->password);

            if ($hash == $userhash)
            {
                echo "<h2>{$strResetPassword}</h2>";
                echo "<p align='center'>{$strPleaseConfirmUsername}</p>";
                echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";

                echo "<table class='vertical'>";
                echo "<tr><th>{$strUsername}</th>";
                echo "<td><input name='username' size='30' type='text' /></td></tr>";
                echo "</table>";
                echo "<p><input type='submit' value='{$strContinue}' /></p>";

                if ($mode == 'user')
                {
                    echo "<input type='hidden' name='userid' value='{$userid}' />";
                }
                elseif ($mode == 'contact')
                {
                    echo "<input type='hidden' name='contactid' value='{$contactid}' />";
                }
                echo "<input type='hidden' name='hash' value='{$userhash}' />";
                echo "<input type='hidden' name='action' value='resetpasswordform' />";
                echo "</form>";
            }
            else
            {
                echo "<h3>{$strError}</h3>";
                echo "<p align='center'>{$strDidYouPasteFullURL}</p>";
                echo "<p align='center'><a href='index.php'>{$strBackToLoginPage}</a></p>";
            }
        }
        else
        {
            echo "<h3>{$strError}</h3>";
            echo "<p align='center'>{$strDidYouPasteFullURL}</p>";
            echo "<p align='center'><a href='index.php'>{$strBackToLoginPage}</a></p>";
        }
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    break;

    case 'resetpasswordform':
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        if ($mode == 'user')
        {
            $sql = "SELECT id, username, password FROM `{$dbUsers}` WHERE id = '{$userid}' LIMIT 1";
        }
        elseif ($mode == 'contact')
        {
            $sql = "SELECT id, username, password FROM `{$dbContacts}` WHERE id = '{$contactid}' LIMIT 1";
        }

        $userresult = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        $usercount = mysql_num_rows($userresult);
        if ($usercount > 0)
        {
            $userdetails = mysql_fetch_object($userresult);
            $hash = md5($userdetails->username.'.'.$userdetails->password);
            if ($hash == $userhash AND $username == $userdetails->username)
            {
                $newhash = md5($userdetails->username.'.ok.'.$userdetails->password);
                echo "<h2>{$strSetPassword}</h2>";
                echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";
                echo "<table class='maintable vertical'>";
                echo "<tr class='password'><th>{$strNewPassword}:</th>";
                echo "<td><input maxlength='50' name='newpassword1' size='30' type='password' />";
                echo "</td></tr>";
                echo "<tr class='password'><th>{$strConfirmNewPassword}:</th>";
                echo "<td><input maxlength='50' name='newpassword2' size='30' type='password' />";
                echo "</td></tr>";
                echo "</table>";
                if ($mode == 'user')
                {
                    echo "<input type='hidden' name='userid' value='{$userid}' />";
                }
                elseif ($mode == 'contact')
                {
                    echo "<input type='hidden' name='contactid' value='{$contactid}' />";
                }
                echo "<input type='hidden' name='hash' value='{$newhash}' />";
                echo "<input type='hidden' name='action' value='savepassword' />";
                echo "<p><input type='submit' value='{$strSetPassword}' />";
                echo "</form>";
                echo "<p align='center'><a href='index.php'>{$strBackToLoginPage}</a></p>";
            }
            else
            {
                echo "<h3>{$strError}</h3>";
                echo "<p align='center'> align='center'{$strHaveForgottenUsername}</p>";
                echo "<p align='center'><a href='index.php'>{$strBackToLoginPage}</a></p>";
            }
        }
        else
        {
            echo "<h3>{$strError}</h3>";
            echo "<p align='center'>{$strInvalidUserID}</p>";
            echo "<p align='center'><a href='index.php'>{$strBackToLoginPage}</a></p>";
        }
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    break;

    case 'savepassword':
        $newpassword1 = cleanvar($_REQUEST['newpassword1']);
        $newpassword2 = cleanvar($_REQUEST['newpassword2']);
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        if ($mode == 'user')
        {
            $sql = "SELECT id, username, password FROM `{$dbUsers}` WHERE id = '{$userid}' LIMIT 1";
        }
        elseif ($mode == 'contact')
        {
            $sql = "SELECT id, username, password FROM `{$dbContacts}` WHERE id = '{$contactid}' LIMIT 1";
        }

        $userresult = mysql_query($sql);
        if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
        $usercount = mysql_num_rows($userresult);
        if ($usercount > 0)
        {
            $userdetails = mysql_fetch_object($userresult);
            $newhash = md5($userdetails->username.'.ok.'.$userdetails->password);
            if ($newhash == $userhash)
            {
                if ($newpassword1 == $newpassword2)
                {
                    if ($mode == 'user')
                    {
                        $usql = "UPDATE `{$dbUsers}` SET password=MD5('{$newpassword1}') WHERE id={$userid} LIMIT 1";
                    }
                    elseif ($mode == 'contact')
                    {
                        $usql = "UPDATE `{$dbContacts}` SET password=MD5('{$newpassword1}') WHERE id={$contactid} LIMIT 1";
                    }
                    mysql_query($usql);
                    echo "<h3>{$strPasswordReset}</h3>";
                    echo "<p align='center'>{$strPasswordHasBeenReset}</p>";
                    echo "<p align='center'><a href='index.php'>{$strBackToLoginPage}</a></p>";
                }
                else
                {
                    echo "<h3>{$strError}</h3>";
                    echo "<p align='center'>{$strPasswordsDoNotMatch}</p>";
                    echo "<p align='center'><a href='index.php'>{$strBackToLoginPage}</a></p>";
                }
            }
            else
            {
                echo "<h3>{$strError}</h3>";
                echo "<p align='center'>{$strInvalidDetails}</p>";
                echo "<p align='center'><a href='index.php'>{$strBackToLoginPage}</a></p>";
            }
        }
        else
        {
            echo "<h3>{$strError}</h3>";
            echo "<p align='center'>{$strInvalidUserID}</p>";
            echo "<p align='center'><a href='index.php'>{$strBackToLoginPage}</a></p>";
        }
        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    break;

    case 'form':
    default:
        include (APPLICATION_INCPATH . 'htmlheader.inc.php');
        echo "<h2>{$title}</h2>";
        echo "<form action='{$_SERVER['PHP_SELF']}' method='post'>";

        echo "<table class='vertical'>";
        echo "<tr><th>{$strEmailAddress}</th><td><input name='emailaddress' size='30' type='text' /></td></tr>";
        echo "</table>";
        echo "<p><input type='submit' value='{$strContinue}' /></p>";
        echo "<input type='hidden' name='action' value='forgotpwd' />";
        echo "</form>";

        include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    break;
}

?>
