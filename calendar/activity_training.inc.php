<?php

// activity_training.inc.php - Training
//
// Author: Tom Gerrard <tom.gerrard[at]salfordsoftware.co.uk>
//
// Included by timesheet.inc.php

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

$activity_types['Training'] = "";

echo "<script type='text/javascript'>
    
    function activityTraining(level)
    {
        $('newactivityalias').value = 'Training';               
    }
    
    activityTypes['Training'] = activityTraining;

</script>
";

?>



