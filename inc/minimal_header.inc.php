<?php
// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

session_name($CONFIG['session_name']);
session_start();
echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\"  xml:lang=\"{$_SESSION['lang']}\" lang=\"{$_SESSION['lang']}\">\n<head><title>";
if (!empty($incidentid)) echo "{$incidentid} - ";
if (isset($title))
{
    echo $title;
}
else
{
    echo $CONFIG['application_shortname'];
}

echo "</title>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset={$i18ncharset}\" />\n";
echo "<meta name=\"GENERATOR\" content=\"{$CONFIG['application_name']} {$application_version_string}\" />\n";
echo "<style type='text/css'>@import url('{$CONFIG['application_webpath']}styles/sitbase.css');</style>\n";

if ($_SESSION['auth'] == TRUE)
{
    $theme = $_SESSION['userconfig']['theme'];
    $iconset = $_SESSION['userconfig']['iconset'];
}
else
{
    $theme = $CONFIG['default_interface_style'];
    $iconset = $CONFIG['default_iconset'];
}
if (empty($iconset)) $iconset = 'sit';
echo "<link rel='stylesheet' href='{$CONFIG['application_webpath']}styles/{$theme}/{$theme}.css' />\n";

echo "<script src='{$CONFIG['application_webpath']}scripts/prototype/prototype.js' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/sit.js.php' type='text/javascript'></script>\n";
echo "<script src='{$CONFIG['application_webpath']}scripts/webtrack.js' type='text/javascript'></script>\n";

echo "</head>";
echo "<body onload=\"self.focus()\">";

?>