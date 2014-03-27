<?php
// file.inc.php - functions relating to files
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2014 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


if (!function_exists('list_dir'))
{
    /**
     * returns an array contains all files in a directory and optionally recurses subdirectories
     */
    function list_dir($dirname, $recursive = 1)
    {
        if ($dirname[mb_strlen($dirname)-1] != DIRECTORY_SEPARATOR)
        {
            $dirname .= DIRECTORY_SEPARATOR;
        }

        $handle = opendir($dirname);
        if ($handle == FALSE)
        {
            trigger_error("Error in list_dir() Problem attempting to open directory: {$dirname}", E_USER_WARNING);
        }

        $result_array = array();

        while ($file = readdir($handle))
        {
            if ($file == '.' || $file == '..')
            {
                continue;
            }

            if (is_dir($dirname.$file) && $recursive)
            {
                $x = list_dir($dirname . $file . DIRECTORY_SEPARATOR);
                $result_array = array_merge($result_array, $x);
            }
            else
            {
                $result_array[] = $dirname . $file;
            }
        }
        closedir($handle);

        if (sizeof($result_array))
        {
            natsort($result_array);

            if ($_SESSION['update_order'] == "desc")
            {
                $result_array = array_reverse($result_array);
            }
        }
        return $result_array;
    }
}


/**
 * recursive copy from one directory to another
 */
function rec_copy($from_path, $to_path)
{
    if ($from_path == '') trigger_error('Cannot move file', 'from_path not set', E_USER_WARNING);
    if ($to_path == '') trigger_error('Cannot move file', 'to_path not set', E_USER_WARNING);

    $mk = mkdir($to_path, 0700);
    if (!$mk) trigger_error('Failed creating directory: {$to_path}',E_USER_WARNING);
    $this_path = getcwd();
    if (is_dir($from_path))
    {
        chdir($from_path);
        $handle = opendir('.');
        while (($file = readdir($handle)) !== false)
        {
            if (($file != ".") && ($file != ".."))
            {
                if (is_dir($file))
                {
                    rec_copy ($from_path.$file."/",
                    $to_path.$file."/");
                    chdir($from_path);
                }

                if (is_file($file))
                {
                    if (!(mb_substr(rtrim($file),mb_strlen(rtrim($file))-8, 4) == 'mail'
                        || mb_substr(rtrim($file),mb_strlen(rtrim($file))-10, 5) == 'part1'
                        || mb_substr(rtrim($file),mb_strlen(rtrim($file))-8, 4) == '.vcf'))
                    {
                        copy($from_path.$file, $to_path.$file);
                    }
                }
            }
        }
        closedir($handle);
    }
}


function file_permissions_info($perms)
{
    if (($perms & 0xC000) == 0xC000) $info = 's';
    elseif (($perms & 0xA000) == 0xA000) $info = 'l';
    elseif (($perms & 0x8000) == 0x8000) $info = '-';
    elseif (($perms & 0x6000) == 0x6000) $info = 'b';
    elseif (($perms & 0x4000) == 0x4000) $info = 'd';
    elseif (($perms & 0x2000) == 0x2000) $info = 'c';
    elseif (($perms & 0x1000) == 0x1000) $info = 'p';
    else $info = 'u';

    // Owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
            (($perms & 0x0800) ? 's' : 'x' ) :
            (($perms & 0x0800) ? 'S' : '-'));

    // Group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
            (($perms & 0x0400) ? 's' : 'x' ) :
            (($perms & 0x0400) ? 'S' : '-'));

    // World
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
            (($perms & 0x0200) ? 't' : 'x' ) :
            (($perms & 0x0200) ? 'T' : '-'));

    return $info;
}


/**
 * Function to produce a user readable memory/file size i.e 2048 bytes 1KB etc
 * @author Paul Heaney
 * @param int $size - size in bytes
 * @return String size in readable format (e.g. 4.5 Mbytes)
 */
function readable_bytes_size($size)
{
    global $strBytes, $strKBytes, $strMBytes, $strGBytes, $strTBytes;
    $j = 0;

    $ext = array($strBytes, $strKBytes, $strMBytes, $strGBytes, $strTBytes);
    while ($size >= pow(1024, $j))
    {
        ++$j;
    }
    $readable_size = round($size / pow(1024,$j-1) * 100) / 100 . ' ' . $ext[$j-1];

    return $readable_size;
}


/**
 * Function to produce a user readable file size i.e 2048 bytes 1KB etc
 * @author Paul Heaney
 * @param filesize - filesize in bytes
 * @return String filesize in readable format
 * @deprecated DEPRECATED Function renamed to readable_bytes_size() for v3.90, INL 21 June 2011
 */
function readable_file_size($filesize)
{
    return readable_bytes_size($filesize);
}


/**
 * Uploads a file
 * @author Kieran Hogg
 * @param mixed $file file to upload
 * @param int $incidentd
 * @return string path of file
 * @todo Use within sit
 */
function upload_file($file, $incidentid, $type='public')
{
    global $CONFIG, $now;
    $att_max_filesize = return_bytes($CONFIG['upload_max_filesize']);
    $incident_attachment_fspath = $CONFIG['attachment_fspath'] . $incidentid;
    if ($file['name'] != '')
    {
        // make incident attachment dir if it doesn't exist
        $umask = umask(0000);
        if (!file_exists("{$CONFIG['attachment_fspath']}{$incidentid}"))
        {
            $mk = @mkdir("{$CONFIG['attachment_fspath']}{$incidentid}", 0770);
            if (!$mk) trigger_error("Failed creating incident attachment directory.", E_USER_WARNING);
        }
        $mk = @mkdir("{$CONFIG['attachment_fspath']}{$incidentid}" . DIRECTORY_SEPARATOR . "{$now}", 0770);
        if (!$mk) trigger_error("Failed creating incident attachment (timestamp) directory.", E_USER_WARNING);
        umask($umask);
        $returnpath = $incidentid . DIRECTORY_SEPARATOR . $now . DIRECTORY_SEPARATOR . $file['name'];
        $filepath = $incident_attachment_fspath . DIRECTORY_SEPARATOR . $now . DIRECTORY_SEPARATOR;
        $newfilename = $filepath . clean_fspath($file['name']);

        // Move the uploaded file from the temp directory into the incidents attachment dir
        $mv = @move_uploaded_file($file['tmp_name'], $newfilename);
        if (!$mv) trigger_error('!Error: Problem moving attachment from temp directory.', E_USER_WARNING);

        // Check file size before attaching
        if ($file['size'] > $att_max_filesize)
        {
            trigger_error("User Error: Attachment too large or file upload error.", E_USER_WARNING);
            // throwing an error isn't the nicest thing to do for the user but there seems to be no guaranteed
            // way of checking file sizes at the client end before the attachment is uploaded. - INL
            return FALSE;
        }
        else
        {
            if (!empty($sit[2]))
            {
                $usertype = 'user';
                $userid = $sit[2];
            }
            else
            {
                $usertype = 'contact';
                $userid = $_SESSION['contactid'];
            }
            $sql = "INSERT INFO `{$GLOBALS['dbFiles']}`
                    (category, filename, size, userid, usertype, path, filedate, refid)
                    VALUES
                    ('{$type}', '{$file['name']}', '{$file['size']}', '{$userid}', '{$usertype}', '{$filepath}', '{$now}', '{$incidentid}')";
            $result = mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);

            return $returnpath;
        }
    }
}


// Converts a PHP.INI integer into a byte value
function return_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val{mb_strlen($val) - 1});
    switch ($last)
    {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}


function mime_type($file)
{
    if (function_exists("mime_content_type"))
    {
        return mime_content_type($file);
    }
    elseif (DIRECTORY_SEPARATOR == '/')
    {
        //This only works on *nix, but better than failing
        $file = escapeshellarg($file);
        $mime = shell_exec("file -bi " . $file);
        return $mime;
    }
    else
    {
        return 'application/octet-stream';
    }
}


/**
 * Function that reads the contents(First 2048 bits) of a .txt file in /help/ and Outputs it.
 * @author Nico du Toit
 * @param $filename The name of the file (without the .txt extension)
 * @return Outputs the text file contents to HTML if it exists, or an error message
 * if the file does not exist.
*/
function file_get_help_textfile($filename)
{
    $helpfile = APPLICATION_HELPPATH . "{$_SESSION['lang']}". DIRECTORY_SEPARATOR . "{$filename}.txt";
    // Default back to english if language helpfile isn't found
    if (!file_exists($helpfile)) $helpfile = APPLICATION_HELPPATH . "en-GB/{$filename}.txt";
    if (file_exists($helpfile))
    {
        $fp = fopen(clean_fspath($helpfile), 'r', TRUE);
        $helptext = fread($fp, 2048);
        fclose($fp);
        echo nl2br($helptext);
    }
    else
    {
        echo "Error: Missing helpfile '{$_SESSION['lang']}/{$filename}.txt'";
    }
}
?>