<?php
// kb_article.php - Form to add a knowledgebase article
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//

// Authors: Kieran Hogg, <kieran[at]sitracker.org>
//          Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//          Tom Gerrard <tomgerrard[at]users.sourceforge.net>

require ('core.php');
$permission = PERM_KB_VIEW; // view KB
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

if (!empty($_REQUEST['id']))
{
    $mode = 'edit';
    $kbid = clean_int($_REQUEST['id']);
}

// Array of available sections, in order they are to appear
$sections = array('strSummary', 'strSymptoms', 'strCause', 'strQuestion', 'strAnswer',
                  'strSolution', 'strWorkaround', 'strStatus', 'strAdditionalInfo',
                  'strReferences');

$att_max_filesize = return_bytes($CONFIG['upload_max_filesize']);

if (isset($_POST['submit']))
{
    $kbtitle = cleanvar($_POST['title']);
    $keywords = cleanvar($_POST['keywords']);
    $distribution = clean_fixed_list($_POST['distribution'], array('public', 'private', 'restricted'));
    $sql = array();

    if (isset($_FILES['attachment']) AND ($_FILES['attachment']['name'] != ''))
    {
        // Check if we had an error whilst uploading
        if ($_FILES['attachment']['error'] != '' AND $_FILES['attachment']['error'] != UPLOAD_ERR_OK)
        {
            echo get_file_upload_error_message($_FILES['attachment']['error'], $_FILES['attachment']['name']);
        }
        else
        {
            // OK to proceed
            // Create an entry in the files table
            $sql = "INSERT INTO `{$dbFiles}` (category, filename, size, userid, usertype, filedate) ";
            $sql .= "VALUES ('public', '" . cleanvar($_FILES['attachment']['name']). "', '{$_FILES['attachment']['size']}', '{$sit[2]}', 'user', NOW())";
            mysql_query($sql);
            if (mysql_error()) trigger_error(mysql_error(),E_USER_ERROR);
            $fileid =  mysql_insert_id();

            $kb_attachment_fspath = $CONFIG['attachment_fspath'] . DIRECTORY_SEPARATOR . "kb" . DIRECTORY_SEPARATOR . $kbid . DIRECTORY_SEPARATOR;

            // make incident attachment dir if it doesn't exist
            $newfilename = $kb_attachment_fspath . $fileid . "-".$_FILES['attachment']['name'];
            $umask = umask(0000);
            $mk = TRUE;
            if (!file_exists($kb_attachment_fspath))
            {
                $mk = mkdir($kb_attachment_fspath, 0770, TRUE);
                if (!$mk)
                {
                    trigger_error('Failed creating kb attachment directory: '.$kb_attachment_fspath, E_USER_WARNING);
                }
            }
            // Move the uploaded file from the temp directory into the incidents attachment dir
            $mv = move_uploaded_file($_FILES['attachment']['tmp_name'], $newfilename);
            if (!$mv) trigger_error('!Error: Problem moving attachment from temp directory to: '.$newfilename, E_USER_WARNING);

            //create link
            $sql = "INSERT INTO `{$dbLinks}`(linktype, origcolref, linkcolref, direction, userid) ";
            $sql .= "VALUES (7, '{$kbid}', '{$fileid}', 'left', '{$sit[2]}')";
            mysql_query($sql);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        }
    }

    $_SESSION['formdata']['kb_new_article'] = cleanvar($_POST, TRUE, FALSE, FALSE);

    $errors = 0;
    if ($kbtitle == '')
    {
        $_SESSION['formerrors']['kb_new_article']['title'] = sprintf($strFieldMustNotBeBlank, $strTitle);
        $errors++;
    }
    if ($keywords == '')
    {
        $_SESSION['formerrors']['kb_new_article']['keywords'] = sprintf($strFieldMustNotBeBlank, $strKeywords);
        $errors++;
    }

    plugin_do('kb_article_submitted');

    $sql = array();
    if (empty($kbid))
    {
        // If the KB ID is blank, we assume we're creating a new article
        $author = user_realname($_SESSION['userid']);
        $pubdate = date('Y-m-d h:i:s');

        $sqlinsert = "INSERT INTO `{$dbKBArticles}` (title, keywords, distribution, author, published) ";
        $sqlinsert .= "VALUES ('{$kbtitle}', '{$keywords}', '{$distribution}', '{$author}', '{$pubdate}')";
        mysql_query($sqlinsert);
        if (mysql_error()) trigger_error("MySQL Error: ".mysql_error(), E_USER_ERROR);
        $kbid = mysql_insert_id();
    }
    else
    {
        $sql[] = "UPDATE `{$dbKBArticles}` SET title='{$kbtitle}', keywords='{$keywords}', distribution='{$distribution}' WHERE docid = '{$kbid}'";
        // Remove associated software ready for re-assocation
        $sql[] = "DELETE FROM `{$dbKBSoftware}` WHERE docid='{$kbid}'";
    }

    foreach ($sections AS $section)
    {

        $sectionvar = strtolower($section);
        $sectionvar = str_replace(" ", "", $sectionvar);
        $sectionid = clean_int($_POST["{$sectionvar}id"]);
        $content = clean_dbstring($_POST[$sectionvar], FALSE, TRUE);
        if ($sectionid > 0)
        {
            if (!empty($content))
            {
                $sql[] = "UPDATE `{$dbKBContent}` SET content='{$content}', headerstyle='h1', distribution='public' WHERE id='{$sectionid}' AND docid='{$kbid}' ";
            }
            else
            {
                $sql[] = "DELETE FROM `{$dbKBContent}` WHERE id='{$sectionid}' AND docid='{$kbid}' ";
            }
        }
        else
        {
            if (!empty($content))
            {
                $sql[] = "INSERT INTO `{$dbKBContent}` (docid, ownerid, header, headerstyle, content, distribution) VALUES ('{$kbid}', '{$sit[2]}', '{$section}', 'h1', '{$content}', 'public')";
            }
        }
    }

    // Set software / expertise
    if (is_array($_POST['expertise']))
    {
        $expertise = cleanvar(array_unique(($_POST['expertise'])));
        foreach ($expertise AS $value)
        {
            $value = clean_int($value);
            $sql[] = "INSERT INTO `{$dbKBSoftware}` (docid, softwareid) VALUES ('{$kbid}', '{$value}')";
        }
    }

    if (is_array($sql))
    {
        foreach ($sql AS $sqlquery)
        {
//             echo "<p>$sqlquery</p>";
            mysql_query($sqlquery);
            if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        }
    }
    plugin_do('kb_article_saved');
    $t = new TriggerEvent('TRIGGER_KB_CREATED', array('kbid' => $kbid, 'userid' => $sit[2]));
    html_redirect("kb_view_article.php?id={$kbid}");
    exit;
}
else
{
    //show form
    $title = $strEditKBArticle;
    require (APPLICATION_INCPATH . 'htmlheader.inc.php');

    if ($mode == 'edit')
    {
        echo "<h2>".icon('kb', 32, $strEditKBArticle)." {$strEditKBArticle}: {$kbid}</h2>";
        $sql = "SELECT * FROM `{$dbKBArticles}` WHERE docid='{$kbid}'";
        $result = mysql_query($sql);
        if (mysql_error()) trigger_error("MySQL Query Error ".mysql_error(), E_USER_ERROR);
        $kbobj = mysql_fetch_object($result);

        foreach ($sections AS $section)
        {
            $secsql = "SELECT * FROM `{$dbKBContent}` ";
            $secsql .= "WHERE docid='{$kbobj->docid}' ";
            $secsql .= "AND header='{$section}' LIMIT 1";
            if ($secresult = mysql_query($secsql))
            {
                $secobj = mysql_fetch_object($secresult);
                if (!empty($secobj->content))
                {
                    $sections[$section] = $secobj->content;
                    $sectionstore .= "<input type='hidden' name='".strtolower($section)."id' value='{$secobj->id}' />\n";
                }
            }
        }
    }
    else
    {
        echo "<h2>".icon('kb', 32, $strNewKBArticle)." {$strNewKBArticle}</h2>";
    }
    plugin_do('kb_article');

    echo "<div id='kbarticle'>";
    echo "<form enctype='multipart/form-data' action='{$_SERVER['PHP_SELF']}?id={$kbid}' method='post'>";

    echo "<h3>{$strEnvironment}</h3>";
    echo "<p style='text-align:left'>{$strSelectSkillsApplyToArticle}:</p>";
    if ($mode == 'edit')
    {
        $docsoftware = array();
        $swsql = "SELECT softwareid FROM  `{$dbKBSoftware}` WHERE docid = '{$kbobj->docid}'";
        $swresult = mysql_query($swsql);
        if (mysql_error()) trigger_error("MySQL Error: ".mysql_error(),E_USER_WARNING);
        if (mysql_num_rows($swresult) > 0)
        {
            while ($sw = mysql_fetch_object($swresult))
            {
                $docsoftware[] = $sw->softwareid;
            }
        }
    }
    $listsql = "SELECT * FROM `{$dbSoftware}` ORDER BY name";
    $listresult = mysql_query($listsql);
    if (mysql_error()) trigger_error("MySQL Error: ".mysql_error(),E_USER_WARNING);
    if (mysql_num_rows($listresult) > 0)
    {
        echo "<select name='expertise[]' multiple='multiple' size='5' style='width: 100%;'>";
        while ($software = mysql_fetch_object($listresult))
        {
            echo "<option value='{$software->id}'";
            if ($mode == 'edit' AND in_array($software->id, $docsoftware)) echo " selected='selected'";
            echo ">{$software->name}</option>\n";
        }
        echo "</select>";
    }

    echo "<h3>{$strTitle}</h3>";
    echo "<input class='required' name='title' id='title' size='50' value='{$kbobj->title}'/> ";
    echo "<span class='required'>{$strRequired}</span>";

    echo "<h3>{$strKeywords}</h3>";
    echo "<input name='keywords' id='keywords' size='60' value='{$kbobj->keywords}' />";
    echo help_link('SeparatedBySpaces');

    echo "<h3>{$strDistribution}</h3>";
    echo "<select name='distribution'> ";

    echo "<option value='public' ";
    if ($kbobj->distribution == 'public')
    {
        echo " selected='selected' ";
    }
    echo ">{$strPublic}</option>";

    echo "<option value='private' style='color: blue;'";
    if ($kbobj->distribution == 'private')
    {
        echo " selected='selected' ";
    }
    echo ">{$strPrivate}</option>";

    echo "<option value='restricted' style='color: red;";
    if ($kbobj->distribution == 'restricted')
    {
        echo " selected='selected' ";
    }
    echo "'>{$strRestricted}</option>";
    echo "</select> ";
    echo help_link('KBDistribution');

    echo "<h3><a href=\"javascript:void(0);\" onclick=\"Effect.toggle('summarysection', 'blind', { duration: 0.2 });";
    echo "togglePlusMinus('summaryspan');\">";
    echo "{$strSummary} <span id='summaryspan'>[+]</span></a></h3>";
    echo "<div id='summarysection' style='display: none;'>";
    echo bbcode_toolbar('summary');
    echo "<textarea id='summary' name='strsummary' cols='100' rows='8' ";
    echo "style='overflow: visible; white-space: nowrap;' onchange='kbSectionCollapse();'>{$sections['strSummary']}";
    echo "</textarea>";
    echo "</div>";

    echo "<h3><a href=\"javascript:void(0);\" onclick=\"Effect.toggle('symptomssection', 'blind', { duration: 0.2 });";
    echo "togglePlusMinus('symptomsspan');\">";
    echo "{$strSymptoms} <span id='symptomsspan'>[+]</span></a></h3>";
    echo "<div id='symptomssection' style='display: none;'>";
    echo bbcode_toolbar('symptoms');
    echo "<textarea id='symptoms' name='strsymptoms' cols='100' rows='8' ";
    echo "onchange='kbSectionCollapse();'>{$sections['strSymptoms']}";
    echo "</textarea>";
    echo "</div>";

    echo "<h3><a href=\"javascript:void(0);\" onclick=\"Effect.toggle('causesection', 'blind', { duration: 0.2 });";
    echo "togglePlusMinus('causespan');\">";
    echo "{$strCause} <span id='causespan'>[+]</span></a></h3>";
    echo "<div id='causesection' style='display: none;'>";
    echo bbcode_toolbar('cause');
    echo "<textarea id='cause' name='strcause' cols='100' rows='8' ";
    echo "onchange='kbSectionCollapse();'>{$sections['strCause']}";
    echo "</textarea>";
    echo "</div>";

    echo "<h3><a href=\"javascript:void(0);\" onclick=\"Effect.toggle('questionsection', 'blind', { duration: 0.2 });";
    echo "togglePlusMinus('questionspan');\">";
    echo "{$strQuestion} <span id='questionspan'>[+]</span></a></h3>";
    echo "<div id='questionsection' style='display: none;'>";
    echo bbcode_toolbar('question');
    echo "<textarea id='question' name='strquestion' cols='100' rows='8' ";
    echo "onchange='kbSectionCollapse();'>{$sections['strQuestion']}";
    echo "</textarea>";
    echo "</div>";

    echo "<h3><a href=\"javascript:void(0);\" onclick=\"Effect.toggle('answersection', 'blind', { duration: 0.2 });";
    echo "togglePlusMinus('answerspan');\">";
    echo "{$strAnswer} <span id='answerspan'>[+]</span></a></h3>";
    echo "<div id='answersection' style='display: none;'>";
    echo bbcode_toolbar('answer');
    echo "<textarea id='answer' name='stranswer' cols='100' rows='8' ";
    echo "onchange='kbSectionCollapse();'>{$sections['strAnswer']}";
    echo "</textarea>";
    echo "</div>";

    echo "<h3><a href=\"javascript:void(0);\" onclick=\"Effect.toggle('solutionsection', 'blind', { duration: 0.2 });";
    echo "togglePlusMinus('solutionspan');\">";
    echo "{$strSolution} <span id='solutionspan'>[+]</span></a></h3>";
    echo "<div id='solutionsection' style='display: none;'>";
    echo bbcode_toolbar('solution');
    echo "<textarea id='solution' name='strsolution' cols='100' rows='8' ";
    echo "onchange='kbSectionCollapse();'>{$sections['strSolution']}";
    echo "</textarea>";
    echo "</div>";

    echo "<h3><a href=\"javascript:void(0);\" onclick=\"Effect.toggle('workaroundsection', 'blind', { duration: 0.2 });";
    echo "togglePlusMinus('workaroundspan');\">";
    echo "{$strWorkaround} <span id='workaroundspan'>[+]</span></a></h3>";
    echo "<div id='workaroundsection' style='display: none;'>";
    echo bbcode_toolbar('workaround');
    echo "<textarea id='workaround' name='strworkaround' cols='100' rows='8' ";
    echo "onchange='kbSectionCollapse();'>{$sections['strWorkaround']}";
    echo "</textarea>";
    echo "</div>";

    echo "<h3><a href=\"javascript:void(0);\" onclick=\"Effect.toggle('statussection', 'blind', { duration: 0.2 });";
    echo "togglePlusMinus('statusspan');\">";
    echo "{$strStatus} <span id='statusspan'>[+]</span></a></h3>";
    echo "<div id='statussection' style='display: none;'>";
    echo bbcode_toolbar('status');
    echo "<textarea id='status' name='strstatus' cols='100' rows='8' ";
    echo "onchange='kbSectionCollapse();'>{$sections['strStatus']}";
    echo "</textarea>";
    echo "</div>";

    echo "<h3><a href=\"javascript:void(0);\" onclick=\"Effect.toggle('additionalinformationsection', 'blind', { duration: 0.2 });";
    echo "togglePlusMinus('additionalinformationspan');\">";
    echo "{$strAdditionalInfo} <span id='additionalinformationspan'>[+]</span></a></h3>";
    echo "<div id='additionalinformationsection' style='display: none;'>";
    echo bbcode_toolbar('additionalinformation');
    echo "<textarea id='additionalinformation' name='stradditionalinfo' cols='100' rows='8'  ";
    echo "onchange='kbSectionCollapse();'>{$sections['strAdditionalInfo']}";
    echo "</textarea>";
    echo "</div>";

    echo "<h3><a href=\"javascript:void(0);\" onclick=\"Effect.toggle('referencessection', 'blind', { duration: 0.2 });";
    echo "togglePlusMinus('referencesspan');\">";
    echo "{$strReferences} <span id='referencesspan'>[+]</span></a></h3>";
    echo "<div id='referencessection' style='display: none;'>";
    echo bbcode_toolbar('references');
    echo "<textarea id='references' name='strreferences' cols='100' rows='8' ";
    echo "onchange='kbSectionCollapse();'>{$sections['strReferences']}";
    echo "</textarea>";
    echo "</div>";

    echo "<h3>{$strAttachFile}</h3>";
    echo "<input type='hidden' name='MAX_FILE_SIZE' value='{$att_max_filesize}' />";
    echo "<input type='file' name='attachment' />";
    if ($mode == 'edit')
    {
        $sqlf = "SELECT f.filename, f.id, f.filedate
                FROM `{$dbFiles}` AS f
                INNER JOIN `{$dbLinks}` as l
                ON l.linkcolref = f.id
                WHERE l.linktype = 7
                AND l.origcolref = '{$kbid}'";
        $fileresult = mysql_query($sqlf);
        if (mysql_error()) trigger_error("MySQL Error: ".mysql_error(),E_USER_WARNING);
        if (mysql_num_rows($fileresult) > 0)
        {
            echo "<br /><table><th>{$strFilename}</th><th>{$strDate}</th>";
            while ($filename = mysql_fetch_object($fileresult))
            {
                echo "<tr><td><a href='download.php?id={$filename->id}&app=7&appid={$kbid}'>$filename->filename</a></td>";
                echo "<td>" . ldate($CONFIG['dateformat_filedatetime'],mysql2date($filename->filedate)) . "</td></tr>";
            }
            echo "</table>";
        }

    }

    echo "<h3>{$strDisclaimer}</h3>";
    echo $CONFIG['kb_disclaimer_html'];
    echo "<p class='formbuttons'><input name='reset' type='reset' value='{$strReset}' /> ";
    echo "<input type='submit' name='submit' value='{$strSave}' /></p>";
    echo $sectionstore;
    echo "</form></div>";
    echo "<p class='return'><a href='kb_view_article.php?id=$kbid'>{$strReturnWithoutSaving}</a></p>";
    echo "<script type='text/javascript'>\n//<![CDATA[\nkbSectionCollapse();\n//]]>\n</script>";
    include (APPLICATION_INCPATH . 'htmlfooter.inc.php');
}
?>