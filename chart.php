<?php
// chart.php - Outputs a chart in png format using the GD library
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.
//


require ('core.php');
require (APPLICATION_LIBPATH . 'functions.inc.php');
// This page requires authentication
require (APPLICATION_LIBPATH . 'auth.inc.php');

if (!extension_loaded('gd')) trigger_error("{$CONFIG['application_name']} requires the gd module", E_USER_ERROR);

// External variables
$type = $_REQUEST['type'];
$data = explode('|',cleanvar($_REQUEST['data']));
$legends = explode('|', cleanvar($_REQUEST['legends'], TRUE, FALSE, FALSE));
$title = urldecode(cleanvar($_REQUEST['title']));
$unit = cleanvar($_REQUEST['unit']);

require (APPLICATION_LIBPATH . 'chart_original.class.php');

$chart = new OriginalChart(500, 150);
$chart->setTitle($title);
$chart->setData($data);
$chart->setLegends($legends);
$chart->setUnit($unit);

switch ($type)
{
    case 'pie':
        $chart->draw_pie_chart();
        break;
    case 'line':
        $chart->draw_line_chart();
        break;
    case 'bar':
        $chart->draw_bar_chart();
       break;
    default:
        $chart->draw_error();
}

?>