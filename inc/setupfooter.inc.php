<?php
// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

echo "<div style='margin-top: 50px;'>";
echo "<hr style='width: 50%; margin-left: 0px;'/>";
echo "<p><a href='http://sitracker.org/'>{$CONFIG['application_name']}</a> Setup | <a href='http://sitracker.org/wiki/Installation'>Installation Help</a></p>";
echo "<p></p>";
echo "</div>";
echo "\n</body>\n</html>";

?>