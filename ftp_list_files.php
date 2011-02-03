<?php
// ftp_list_files.php -
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>

// This Page Is Valid XHTML 1.0 Transitional!   1Nov05


$permission = 44; // FTP Publishing
require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');

// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

$title = $strFTPFilesDB;
include (APPLICATION_INCPATH . 'htmlheader.inc.php');

$orderby = cleanvar($_REQUEST['orderby']);

echo "<h2>".icon('attach', 32)." {$title}</h2>";

if (!empty($CONFIG['ftp_hostname']) AND !empty($CONFIG['ftp_username']))
{
    echo "<p align='center'><a href='ftp_upload_file.php'>Upload a new file</a></p>";
}

echo "<table summary='files' align='center'>";
echo "<tr>";
echo "<th>&nbsp;</th>";
echo "<th><a href='{$_SERVER['PHP_SELF']}?orderby=filename'>{$strFilename}</a></th>";
echo "<th><a href='{$_SERVER['PHP_SELF']}?orderby=size'>{$strSize}</a></th>";
echo "<th><a href='{$_SERVER['PHP_SELF']}?orderby=shortdescription'>{$strTitle}</a></th>";
echo "<th><a href='{$_SERVER['PHP_SELF']}?orderby=version'>{$strVersion}</a></th>";
echo "<th><a href='{$_SERVER['PHP_SELF']}?orderby=date'>{$strDate}</a></th>";
echo "<th><a href='{$_SERVER['PHP_SELF']}?orderby=expiry'>{$strExpiryDate}</a></th>";
echo "<th><a href='{$_SERVER['PHP_SELF']}?orderby=published'>{$strPublished}</a></th>";
echo "</tr>";

$sql = "SELECT id, filename, size, userid, shortdescription, path, downloads, filedate, fileversion, ";
$sql .="expiry, published FROM `{$dbFiles}` WHERE category = 'ftp' ";

switch ($orderby)
{
    case 'filename':
        $sql .= "ORDER by filename ";
        break;

    case 'shortdescription':
        $sql .= "ORDER by shortdescription ";
        break;

    case 'size':
        $sql .= "ORDER by size ";
        break;

    case 'version':
        $sql .= "ORDER BY fileversion ";
        break;

    case 'expiry':
        $sql .= "ORDER by expiry ";
        break;

    case 'date':
        $sql .= "ORDER BY filedate ";
        break;

    case 'published':
        $sql .= "ORDER BY published ";
        break;

    default:
        $sql .= "ORDER by filename ";
        break;
}

$result = mysql_query($sql);
if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_WARNING);

if (mysql_numrows($result) > 0)
{
    while (list($id, $filename, $size, $userid, $shortdescription, $path, $downloads, $filedate, $fileversion,
                $expiry, $published) = mysql_fetch_row($result))
    {
        $pretty_file_size = readable_file_size($size);

        if ($published == 'no') echo "<tr class='urgent'>";
        else echo "<tr>";
        echo "<td align='right'><img src=\"".getattachmenticon($filename)."\" alt=\"$filename ($pretty_file_size)\" border='0' /></td>";
        echo "<td><strong><a href=\"ftp_file_details.php?id={$id}\">$filename</a></strong></td>";
        echo "<td>$pretty_file_size</td>";
        echo "<td>$shortdescription</td>";
        echo "<td>$fileversion</td>";
        echo "<td>".ldate($CONFIG['dateformat_filedatetime'], $filedate)."</td>";
        echo "<td>";
        if ($expiry == 0)
        {
            echo 'Never';
        }
        else
        {
            echo ldate($CONFIG['dateformat_filedatetime'],$expiry);
        }
        echo "</td>";

        echo "<td>{$published}</td>";

        echo "</tr>\n";
    }
}
else
{
    echo "<tr><td colspan='8' align='center'>{$strNone}</td></tr>";
}
echo "</table>\n";
include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
?>
