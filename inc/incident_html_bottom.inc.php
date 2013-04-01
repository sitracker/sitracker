<?php
// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

if ($CONFIG['debug'] == TRUE)
{
    echo "\n<div id='tail'><strong>DEBUG</strong><br />";
    $exec_time_end = getmicrotime();
    $exec_time = $exec_time_end - $exec_time_start;
    echo "<p>CPU Time: ".number_format($exec_time, 3)." seconds<br />";
    echo "Memory Usage: ".readable_bytes_size(memory_get_usage())."</p>";
    if (isset($dbg)) echo "<hr /><pre>".print_r($dbg, true)."</pre>";
    echo "</div>";
}

echo "<div id='incidentfooter'></div>\n";
plugin_do('page_end');
echo "</body>\n";
echo "</html>\n";
plugin_do('after_page');
?>