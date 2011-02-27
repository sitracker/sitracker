<?php
// chart_pchart.inc.php - Functions which provide pChart integration for SiT!
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

include(APPLICATION_LIBPATH ."pChart/class/pData.class.php");
include(APPLICATION_LIBPATH ."pChart/class/pDraw.class.php");
include(APPLICATION_LIBPATH ."pChart/class/pPie.class.php");
include(APPLICATION_LIBPATH ."pChart/class/pImage.class.php");

class OriginalChart extends Chart{
    
    function draw_pie_chart()
    {
        $pData = new pData();
        $pData->addPoints($this->data);

        $pData->addPoints($this->legends, 'Labels');
        $pData->setAbscissa("Labels");
        
        $pImage = new pImage($this->width, $this->height, $pData, TRUE);
        
 		/* Set the default font properties */ 
        $pImage->setFontProperties(array("FontName"=>APPLICATION_LIBPATH ."pChart/fonts/Forgotte.ttf","FontSize"=>10,"R"=>80,"G"=>80,"B"=>80)); 
        
        $pPie = new pPie($pImage, $pData);
        
        $pImage->setShadow(TRUE, array("X"=>3,"Y"=>3,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));
        $pPie->draw3DPie(200, 90, array("Radius"=>100, "DataGapAngle"=>10, "DataGapRadius"=>6," Border"=>TRUE, "DrawLabels" => TRUE));

        $pImage->setFontProperties(array("FontName"=>APPLICATION_LIBPATH ."pChart/fonts/Silkscreen.ttf","FontSize"=>6,"R"=>0,"G"=>0,"B"=>0));
        $pPie->drawPieLegend(1,1,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL)); 
        
        $pImage->autoOutput('chart.png');
    }
    
    function draw_line_chart()
    {
        debug_log('Function not implemented pchart.draw_line_chart');
    }
    
    function draw_bar_chart()
    {
        debug_log('Function not implemented pchart.draw_bar_chart');
    }
    
    function draw_error()
    {
        debug_log('Function not implemented pchart.draw_error');
    }
}