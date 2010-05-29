<?php
// download.php - Pass a file to the browser for download
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas, <ivanlucas[at]users.sourceforge.net


$permission = 0; // no permission required

require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$inlinefiles = array('jpg','jpeg','png','gif','txt','htm','html');

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

// External variables
$id = cleanvar(intval($_GET['id']));

$sql = "SELECT *, u.id AS updateid, f.id AS fileid
        FROM `{$dbFiles}` AS f, `{$dbLinks}` AS l, `{$dbUpdates}` AS u
        WHERE l.linktype='5'
        AND l.origcolref=u.id
        AND l.linkcolref='{$id}'
        AND l.direction='left'
        AND l.linkcolref=f.id
        ORDER BY f.filedate DESC";
$result = mysql_query($sql);
$fileobj = mysql_fetch_object($result);
$incidentid = cleanvar(intval($fileobj->incidentid));
$updateid = cleanvar(intval($fileobj->updateid));
$filename = cleanvar($fileobj->filename);
$visibility = $fileobj->category;
$fileid = $fileobj->fileid;

$access = FALSE;
if ($visibility == 'public' AND (isset($sit[2]) OR isset($_SESSION['contactid'])))
{
    $access = TRUE;
}
elseif ($visibility != 'public' AND isset($sit[2]))
{
    $access = TRUE;
}
else
{
    $access = FALSE;
}

if (($incidentid == 0) OR (empty($incidentid)))
{
    $file_fspath = "{$CONFIG['attachment_fspath']}updates{$fsdelim}{$fileid}";
    $file_fspath2 = "{$CONFIG['attachment_fspath']}updates{$fsdelim}{$fileid}-{$filename}";
    $old_style = "{$CONFIG['attachment_fspath']}updates{$fsdelim}{$filename}";
}
else
{
    $file_fspath = "{$CONFIG['attachment_fspath']}{$incidentid}{$fsdelim}{$fileid}-{$filename}";
    $file_fspath2 = "{$CONFIG['attachment_fspath']}{$incidentid}{$fsdelim}{$fileid}";
    $old_style = "{$CONFIG['attachment_fspath']}{$incidentid}{$fsdelim}u{$updateid}{$fsdelim}{$filename}";
}

if ((!file_exists($file_fspath)) AND (!file_exists($file_fspath2)) AND (!file_exists($old_style)))
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
    // header('HTTP/1.1 404 Not Found');
    // header('Status: 404 Not Found',1,404);
    echo "<h3>404 File Not Found</h3>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    if ($CONFIG['debug'] === TRUE)
    {
        echo "<p>Path: {$file_fspath}<br />Old style path: {$old_style}</p>";
    }
    exit;
}
elseif ($access == TRUE)
{
    if (file_exists($file_fspath))
    {  
        //do nothing
    }
    elseif (file_exists($file_fspath2))
    {
        $file_fspath = $file_fspath2;
    }
    elseif (file_exists($old_style))
    {
        $file_fspath = $old_style;
    }

    if (file_exists($file_fspath))
    {
        $file_size = filesize($file_fspath);
        $fp = fopen($file_fspath, 'r');
        if ($fp && ($file_size !=-1))
        {
            $ext = substr($filename, strrpos($filename, '.') + 1);
            if (in_array($ext, $inlinefiles)) $inline = TRUE;
            else $inline = FALSE;
            if ($inline) header("Content-Type: ".mime_type($file_fspath));
            else header("Content-Type: application/octet-stream");
            header("Content-Length: {$file_size}");
            if ($inline) header("Content-Disposition: inline; filename=\"{$filename}\"");
            else header("Content-Disposition: attachment; filename=\"{$filename}\"");
            header("Content-Transfer-Encoding: binary");
            if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE") AND $_SERVER['HTTPS'])
            {
                header('Cache-Control: private');
                header('Pragma: private');
            }

            $buffer = '';
            while (!feof($fp))
            {
                $buffer = fread($fp, 1024*1024);
                print $buffer;
            }
            fclose($fp);
            exit;
        }
        else
        {
            // Access Denied
            include (APPLICATION_INCPATH . 'htmlheader.inc.php');
            // header('HTTP/1.1 403 Forbidden');
            // header('Status: 403 Forbidden',1,403);
            echo "<h3>403 Forbidden</h3>";
            include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
            exit;

        }
    }
}
else
{
    include (APPLICATION_INCPATH . 'htmlheader.inc.php');
	// header('HTTP/1.1 404 Not Found');
    // header('Status: 404 Not Found',1,404);
    echo "<h3>404 File Not Found</h3>";
    echo "Please report this message to support";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
    if ($CONFIG['debug'] == TRUE)
    {
        echo "<p>Path: {$file_fspath}<br />Old style path: {$old_style}</p>";
    }
    exit;
}
?>