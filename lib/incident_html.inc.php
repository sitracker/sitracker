<?php
// incident_html.inc.php - functions that return HTMl elements for incidents
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


/**
 * Prints the HTML for a drop down list of incident status names (EXCLUDING 'CLOSED'),
 * with the given name and with the given id selected.
 * @author Ivan Lucas
 * @param string $name. Text to use for the HTML select name and id attributes
 * @param int $id. Status ID to preselect
 * @param bool $disabled. Disable the select box when TRUE
 * @return string. HTML.
 */
function incidentstatus_drop_down($name, $id, $disabled = FALSE)
{
    global $dbIncidentStatus;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbIncidentStatus}` WHERE id<>2 AND id<>7 AND id<>10 ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($result) < 1)
    {
        trigger_error("Zero rows returned", E_USER_WARNING);
    }

    $html = "<select id='{$name}' name='{$name}'";
    if ($disabled)
    {
        $html .= " disabled='disabled' ";
    }
    $html .= ">";

    while ($statuses = mysql_fetch_object($result))
    {
        $html .= "<option ";
        if ($statuses->id == $id)
        {
            $html .= "selected='selected' ";
        }

        $html .= "value='{$statuses->id}'";
        $html .= ">{$GLOBALS[$statuses->name]}</option>\n";
    }
    $html .= "</select>\n";
    return $html;
}


/**
 * Return HTML for a select box of closing statuses
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of Closing Status to pre-select. None selected if 0 or blank.
 * @todo Requires database i18n
 * @return string. HTML
 */
function closingstatus_drop_down($name, $id, $required = FALSE)
{
    global $dbClosingStatus;
    // extract statuses
    $sql  = "SELECT id, name FROM `{$dbClosingStatus}` ORDER BY name ASC";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);
    $html = "<select name='{$name}'";
    if ($required)
    {
        $html .= " class='required' ";
    }
    $html .= ">";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    while ($statuses = mysql_fetch_object($result))
    {
        $html .= "<option ";
        if ($statuses->id == $id)
        {
            $html .= "selected='selected' ";
        }
        $html .= "value='{$statuses->id}'>";
        if (isset($GLOBALS[$statuses->name]))
        {
            $html .= $GLOBALS[$statuses->name];
        }
        else
        {
            $html .= $statuses->name;
        }
        $html .= "</option>\n";
    }
    $html .= "</select>\n";

    return $html;
}


/**
 * Returns a string of HTML nicely formatted for the incident details page containing any additional
 * product info for the given incident.
 * @author Ivan Lucas
 * @param int $incidentid The incident ID
 * @return string HTML
 */
function incident_productinfo_html($incidentid)
{
    global $dbProductInfo, $dbIncidentProductInfo, $strNoProductInfo;

    // TODO extract appropriate product info rather than *
    $sql  = "SELECT *, TRIM(incidentproductinfo.information) AS info FROM `{$dbProductInfo}` AS p, {$dbIncidentProductInfo}` ipi ";
    $sql .= "WHERE incidentid = $incidentid AND productinfoid = p.id AND TRIM(p.information) !='' ";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) == 0)
    {
        return ('<tr><td>{$strNoProductInfo}</td><td>{$strNoProductInfo}</td></tr>');
    }
    else
    {
        // generate HTML
        while ($productinfo = mysql_fetch_object($result))
        {
            if (!empty($productinfo->info))
            {
                $html = "<tr><th>{$productinfo->moreinformation}:</th><td>";
                $html .= urlencode($productinfo->info);
                $html .= "</td></tr>\n";
            }
        }
        echo $html;
    }
}


/**
 * A drop down to select from a list of open incidents
 * optionally filtered by contactid
 * @author Ivan Lucas
 * @param string $name The name attribute for the HTML select
 * @param int $id The value to select by default (not implemented yet)
 * @param int $contactid Filter the list to show incidents from a single
 contact
 * @return string HTML
 */
function incident_drop_down($name, $id, $contactid = 0)
{
    global $dbIncidents;

    $html = '';

    $sql = "SELECT * FROM `{$dbIncidents}` WHERE status != ".STATUS_CLOSED . " ";
    if ($contactid > 0) $sql .= "AND contact = {$contactid}";
    $result = mysql_query($sql);
    if (mysql_error()) trigger_error(mysql_error(),E_USER_WARNING);

    if (mysql_num_rows($result) > 0)
    {
        $html = "<select id='{$name}' name='{$name}' {$select}>\n";
        while ($incident = mysql_fetch_object($result))
        {
            $html .= "<option value='{$incident->id}'>[{$incident->id}] - ";
            $html .= "{$incident->title}</option>";
        }

        $html .= "</select>";
    }
    else
    {
        $html = "<input type='text' name='{$name}' value='' size='10' maxlength='12' />";
    }
    return $html;
}


function parse_updatebody($updatebody, $striptags = TRUE)
{
    if (!empty($updatebody))
    {
        $updatebody = str_replace("&lt;hr&gt;", "[hr]\n", $updatebody);
        if ($striptags)
        {
            $updatebody = strip_tags($updatebody);
        }
        else
        {
            $updatebody = str_replace("<hr>", "", $updatebody);
        }
        $updatebody = nl2br($updatebody);
        $updatebody = str_replace("&amp;quot;", "&quot;", $updatebody);
        $updatebody = str_replace("&amp;gt;", "&gt;", $updatebody);
        $updatebody = str_replace("&amp;lt;", "&lt;", $updatebody);
        // Insert path to attachments
        //new style
        $updatebody = preg_replace("/\[\[att\=(.*?)\]\](.*?)\[\[\/att\]\]/","$2", $updatebody);
        //old style
        $updatebody = preg_replace("/\[\[att\]\](.*?)\[\[\/att\]\]/","$1", $updatebody);
        //remove tags that are incompatable with tool tip
        $updatebody = strip_bbcode_tooltip($updatebody);
        //then show compatable BBCode
        $updatebody = bbcode($updatebody);
        if (strlen($updatebody) > 490) $updatebody .= '...';
    }

    return $updatebody;
}


/**
 * Return HTML for a select box of priority names (with icons)
 * @author Ivan Lucas
 * @param string $name. Name attribute
 * @param int $id. ID of priority to pre-select. None selected if 0 or blank.
 * @param int $max. The maximum priority ID to list.
 * @param bool $disable. Disable the control when TRUE.
 * @return string. HTML
 */
function priority_drop_down($name, $id, $max=4, $disable = FALSE)
{
    global $CONFIG, $iconset, $strRequired;
    // INL 8Oct02 - Removed DB Query
    $html = "<select id='priority' name='$name' ";
    if ($disable)
    {
        $html .= "disabled='disabled' ";
    }
    if ($id == 0)
    {
        $html .= "class='required' ";
    }


    $html .= ">";
    if ($id == 0)
    {
        $html .= "<option selected='selected' value='0'></option>\n";
    }

    $html .= "<option style='text-indent: 14px; background-image: url({$CONFIG['application_webpath']}images/low_priority.gif); background-repeat:no-repeat;' value='1'";
    if ($id == 1)
    {
        $html .= " selected='selected'";
    }

    $html .= ">{$GLOBALS['strLow']}</option>\n";
    $html .= "<option style='text-indent: 14px; background-image: url({$CONFIG['application_webpath']}images/med_priority.gif); background-repeat:no-repeat;' value='2'";
    if ($id == 2)
    {
        $html .= " selected='selected'";
    }

    $html .= ">{$GLOBALS['strMedium']}</option>\n";
    $html .= "<option style='text-indent: 14px; background-image: url({$CONFIG['application_webpath']}images/high_priority.gif); background-repeat:no-repeat;' value='3'";
    if ($id==3)
    {
        $html .= " selected='selected'";
    }

    $html .= ">{$GLOBALS['strHigh']}</option>\n";
    if ($max >= 4)
    {
        $html .= "<option style='text-indent: 14px; background-image: url({$CONFIG['application_webpath']}images/crit_priority.gif); background-repeat:no-repeat;' value='4'";
        if ($id==4)
        {
            $html .= " selected='selected'";
        }
        $html .= ">{$GLOBALS['strCritical']}</option>\n";
    }
    $html .= "</select>\n";
    if ($id == 0)
    {
        $html .= "<span class='required'>{$strRequired}</span>";
    }

    return $html;
}


/**
 * Output html for the 'time to next action' box
 * Used in add incident and update incident
 * @param string $formid. HTML ID of the form containing the controls
 * @return $html string html to output
 * @author Kieran Hogg
 */
function show_next_action($formid, $incidentid)
{
    global $now, $strAM, $strPM;
    $html = "{$GLOBALS['strPlaceIncidentInWaitingQueue']}<br />";

    $oldtimeofnextaction = incident_timeofnextaction($incidentid);
    if ($oldtimeofnextaction < 1)
    {
        $oldtimeofnextaction = $now;
    }
    $wait_time = ($oldtimeofnextaction - $now);

    $na_days = floor($wait_time / 86400);
    $na_remainder = $wait_time % 86400;
    $na_hours = floor($na_remainder / 3600);
    $na_remainder = $wait_time % 3600;
    $na_minutes = floor($na_remainder / 60);
    if ($na_days < 0) $na_days = 0;
    if ($na_hours < 0) $na_hours = 0;
    if ($na_minutes < 0) $na_minutes = 0;

    $html .= "<label>";
    $html .= "<input checked='checked' type='radio' name='timetonextaction' ";
    $html .= "id='ttna_none' onchange=\"update_ttna();\" onclick=\"this.blur();\" ";
    //     $html .= "onclick=\"$('timetonextaction_days').value = ''; window.document.updateform.";
    //     $html .= "timetonextaction_hours.value = ''; window.document.updateform."; timetonextaction_minutes.value = '';\"
    $html .= " value='None' />{$GLOBALS['strNo']}";
    $html .= "</label><br />";

    $html .= "<label><input type='radio' name='timetonextaction' ";
    $html .= "id='ttna_time' value='time' onchange=\"update_ttna();\" onclick=\"this.blur();\" />";
    $html .= "{$GLOBALS['strForXDaysHoursMinutes']}</label><br />\n";
    $html .= "<span id='ttnacountdown'";
    if (empty($na_days) AND
        empty($na_hours) AND
        empty($na_minutes))
    {
        $html .= " style='display: none;'";
    }
    $html .= ">";
    $html .= "&nbsp;&nbsp;&nbsp;<input name='timetonextaction_days' ";
    $html .= " id='timetonextaction_days' value='{$na_days}' maxlength='3' ";
    $html .= "onclick=\"$('ttna_time').checked = true;\" ";
    $html .= "size='3' /> {$GLOBALS['strDays']}&nbsp;";
    $html .= "<input maxlength='2' name='timetonextaction_hours' ";
    $html .= "id='timetonextaction_hours' value='{$na_hours}' ";
    $html .= "onclick=\"$('ttna_time').checked = true;\" ";
    $html .= "size='3' /> {$GLOBALS['strHours']}&nbsp;";
    $html .= "<input maxlength='2' name='timetonextaction_minutes' id='";
    $html .= "timetonextaction_minutes' value='{$na_minutes}' ";
    $html .= "onclick=\"$('ttna_time').checked = true;\" ";
    $html .= "size='3' /> {$GLOBALS['strMinutes']}";
    $html .= "<br />\n</span>";

    $html .= "<label><input type='radio' name='timetonextaction' id='ttna_date' ";
    $html .= "value='date' onchange=\"update_ttna();\" onclick=\"this.blur();\" />";
    $html .= "{$GLOBALS['strUntilSpecificDateAndTime']}</label><br />\n";
    $html .= "<div id='ttnadate' style='display: none;'>";
    $html .= "<input name='date' id='timetonextaction_date' size='10' value='{$date}' ";
    $html .= "onclick=\"$('ttna_date').checked = true;\" /> ";
    $html .= date_picker("{$formid}.timetonextaction_date");

    $html .= time_picker();

    $html .= "<br />\n</div>";

    return $html;
}


/**
 * @author Ivan Lucas
 */
function getattachmenticon($filename)
{
    global $CONFIG, $iconset;
    // Maybe sometime make this use mime typesad of file extensions
    $ext = strtolower(substr($filename, (strlen($filename)-3) , 3));
    $imageurl = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/unknown.png";

    $type_image = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/file_image.png";

    $filetype[] = "gif";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "jpg";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "bmp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "png";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "pcx";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "xls";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/spreadsheet.png";
    $filetype[] = "csv";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/spreadsheet.png";
    $filetype[] = "zip";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[] = "arj";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/zip.png";
    $filetype[] = "rar";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/rar.png";
    $filetype[] = "cab";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[] = "lzh";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[] = "txt";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/txt.png";
    $filetype[] = "f90";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_f.png";
    $filetype[] = "f77";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_f.png";
    $filetype[] = "inf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png";
    $filetype[] = "ins";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png";
    $filetype[] = "adm";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png";
    $filetype[] = "f95";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_f.png";
    $filetype[] = "cpp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_cpp.png";
    $filetype[] = "for";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_f.png";
    $filetype[] = ".pl";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_pl.png";
    $filetype[] = ".py";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source_py.png";
    $filetype[] = "rtm";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/misc_doc.png";
    $filetype[] = "doc";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/wordprocessing.png";
    $filetype[] = "rtf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/wordprocessing.png";
    $filetype[] = "wri";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/wordprocessing.png";
    $filetype[] = "wri";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/wordprocessing.png";
    $filetype[] = "pdf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/pdf.png";
    $filetype[] = "htm";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[] = "tml";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[] = "wav";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/sound.png";
    $filetype[] = "mp3";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/sound.png";
    $filetype[] = "voc";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/sound.png";
    $filetype[] = "exe";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "com";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "nlm";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "evt";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/log.png";
    $filetype[] = "log";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/log.png";
    $filetype[] = "386";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "dll";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "asc";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/txt.png";
    $filetype[] = "asp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[] = "avi";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/video.png";
    $filetype[] = "bkf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tar.png";
    $filetype[] = "chm";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/man.png";
    $filetype[] = "hlp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/man.png";
    $filetype[] = "dif";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/txt.png";
    $filetype[] = "hta";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[] = "reg";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/resource.png";
    $filetype[] = "dmp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/core.png";
    $filetype[] = "ini";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png";
    $filetype[] = "jpe";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "mht";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/html.png";
    $filetype[] = "msi";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "aot";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "pgp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "dbg";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "axt";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/source.png"; // zen text
    $filetype[] = "rdp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "sig";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/document.png";
    $filetype[] = "tif";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/image.png";
    $filetype[] = "ttf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/font_ttf.png";
    $filetype[] = "for";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/font_bitmap.png";
    $filetype[] = "vbs";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[] = "vbe";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[] = "bat";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[] = "wsf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[] = "cmd";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[] = "scr";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "xml";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/xml.png";
    $filetype[] = "zap";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = ".ps";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/postscript.png";
    $filetype[] = ".rm";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/real_doc.png";
    $filetype[] = "ram";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/real_doc.png";
    $filetype[] = "vcf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/vcard.png";
    $filetype[] = "wmf";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/vectorgfx.png";
    $filetype[] = "cer";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/document.png";
    $filetype[] = "tmp";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/unknown.png";
    $filetype[] = "cap";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = "tr1";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/binary.png";
    $filetype[] = ".gz";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[] = "tar";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tar.png";
    $filetype[] = "nfo";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/info.png";
    $filetype[] = "pal";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/colorscm.png";
    $filetype[] = "iso";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/cdimage.png";
    $filetype[] = "jar";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/java_src.png";
    $filetype[] = "eml";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/message.png";
    $filetype[] = ".sh";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/shellscript.png";
    $filetype[] = "bz2";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/tgz.png";
    $filetype[] = "out";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/log.png";
    $filetype[] = "cfg";    $imgurl[] = "{$CONFIG['application_webpath']}images/icons/{$iconset}/32x32/mimetypes/log.png";

    $cnt = count($filetype);
    if ( $cnt > 0 )
    {
        $a = 0;
        $stop = FALSE;
        while ($a < $cnt && $stop == FALSE)
        {
            if ($ext == $filetype[$a])
            {
                $imageurl = $imgurl[$a];
                $stop = TRUE;
            }
            $a++;
        }
    }
    unset ($filetype);
    unset ($imgurl);
    return $imageurl;
}