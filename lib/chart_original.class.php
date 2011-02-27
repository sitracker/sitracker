<?php
// chart_original.inc.php - Functions which provide the original charting diagrams from SiT!
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Author: Ivan Lucas <ivan [at] sitracker.org

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}

class OriginalChart extends Chart{
   
    var $rgb;
    var $white;
    var $blue;
    var $midblue;
    var $darkblue;
    var $black;
    var $grey;
    var $red;
    
    var $use_ttf;
    
    var $img;
    
    function OriginalChart($width, $height)
    {
        global $CONFIG;

        parent::Chart($width, $height);
        
        $this->rgb[] = "190,190,255";
        $this->rgb[] = "205,255,255";
        $this->rgb[] = "255,255,156";
        $this->rgb[] = "156,255,156";
        $this->rgb[] = "255,205,195";
        $this->rgb[] = "255,140,255";
        $this->rgb[] = "100,100,155";
        $this->rgb[] = "98,153,90";
        $this->rgb[] = "205,210,230";
        $this->rgb[] = "192,100,100";
        $this->rgb[] = "204,204,0";
        $this->rgb[] = "255,102,102";
        $this->rgb[] = "0,204,204";
        $this->rgb[] = "0,255,0";
        $this->rgb[] = "255,168,88";
        $this->rgb[] = "128,0,128";
        $this->rgb[] = "0,153,153";
        $this->rgb[] = "255,230,204";
        $this->rgb[] = "128,170,213";
        $this->rgb[] = "75,75,75";
        // repeats...
        $this->rgb[] = "190,190,255";
        $this->rgb[] = "156,255,156";
        $this->rgb[] = "255,255,156";
        $this->rgb[] = "205,255,255";
        $this->rgb[] = "255,205,195";
        $this->rgb[] = "255,140,255";
        $this->rgb[] = "100,100,155";
        $this->rgb[] = "98,153,90";
        $this->rgb[] = "205,210,230";
        $this->rgb[] = "192,100,100";
        $this->rgb[] = "204,204,0";
        $this->rgb[] = "255,102,102";
        $this->rgb[] = "0,204,204";
        $this->rgb[] = "0,255,0";
        $this->rgb[] = "255,168,88";
        $this->rgb[] = "128,0,128";
        $this->rgb[] = "0,153,153";
        $this->rgb[] = "255,230,204";
        $this->rgb[] = "128,170,213";
        $this->rgb[] = "75,75,75";

        if (!empty($CONFIG['font_file']) AND file_exists($CONFIG['font_file'])) $this->use_ttf = TRUE;
        else $this->use_ttf = FALSE;

        if ($this->numberOfDataElements()> 8) $this->height += (($this->numberOfDataElements()- 8) * 14);
    
        $this->img = imagecreatetruecolor($this->width, $this->height);
          
        $this->white = imagecolorallocate($this->img, 255, 255, 255);
        $this->blue = imagecolorallocate($this->img, 240, 240, 255);
        $this->midblue = imagecolorallocate($this->img, 204, 204, 255);
        $this->darkblue = imagecolorallocate($this->img, 32, 56, 148);
        $this->black = imagecolorallocate($this->img, 0, 0, 0);
        $this->grey = imagecolorallocate($this->img, 224, 224, 224);
        $this->red = imagecolorallocate($this->img, 255, 0, 0);
        
        imagefill($this->img, 0, 0, $this->white);
    }
    
    function numberOfDataElements()
    {
        return count($this->data);
    }
    
    function draw_pie_chart()
    {
        global $CONFIG;
        // Set Pie Postition. CenterX,CenterY
        $cx = '120';
        $cy ='60';
    
        // Set Size-dimensions. SizeX,SizeY,SizeZ
        $sx = '200';
        $sy = '100';
        $sz = '15';
    
        // Title
        if (!empty($this->title))
        {
            $cy += 10;
            if ($this->use_ttf)
            {
                imagettftext($this->img, 10, 0, 2, 10, $this->black, $CONFIG['font_file'], $this->title);
            }
            else
            {
                imagestring($this->img, 2, 2, ($legendY - 1), $this->title, $this->black);
            }
        }

        $sumdata = array_sum($this->data);
        
        $angle_sum[-1] = 0;
    
        //convert to angles.
        for ($i = 0; $i < $this->numberOfDataElements(); $i++)
        {
            if ($sumdata > 0)
            {
                $angle[$i] = (($this->data[$i] / $sumdata) * 360);
            }
            else
            {
                $angle[$i] = 0;
            }
            $angle_sum[$i] = array_sum($angle);
        }
    
        $background = imagecolorallocate($this->img, 255, 255, 255);
        //Random colors.
    
        for ($i = 0; $i <= $this->numberOfDataElements(); $i++)
        {
            $rgbcolors = explode(',', $this->rgb[$i]);
            $colors[$i] = imagecolorallocate($this->img, $rgbcolors[0], $rgbcolors[1], $rgbcolors[2]);
            $colord[$i] = imagecolorallocate($this->img, ($rgbcolors[0]/1.5), ($rgbcolors[1]/1.5), ($rgbcolors[2]/1.5));
        }
    
        //3D effect.
        $legendY = 80 - ($this->numberOfDataElements() * 10);
    
        if ($legendY < 10) $legendY = 10;
    
        for ($z = 1; $z <= $sz; $z++)
        {
            for ($i = 0; $i < $this->numberOfDataElements(); $i++)
            {
                imagefilledarc($this->img, $cx, ($cy + $sz) - $z, $sx, $sy, $angle_sum[$i-1], $angle_sum[$i], $colord[$i], IMG_ARC_PIE);
            }
        }
    
        imagerectangle($this->img, 250, $legendY - 5, 470, $legendY + ($this->numberOfDataElements() * 15), $this->black);
    
        //Top of the pie.
        for ($i = 0; $i < $this->numberOfDataElements(); $i++)
        {
            // If its the same angle don't try and draw anything otherwise you end up with the whole pie being this colour
            if ($angle_sum[$i - 1] != $angle_sum[$i])
            {
                imagefilledarc($this->img, $cx, $cy, $sx, $sy, $angle_sum[$i-1], $angle_sum[$i], $colors[$i], IMG_ARC_PIE);
            }
    
            imagefilledrectangle($this->img, 255, ($legendY + 1), 264, ($legendY + 9), $colors[$i]);
            // Legend
            if ($this->unit == 'seconds')
            {
                $this->data[$i] = format_seconds($this->data[$i]);
            }
    
            $l = mb_substr(urldecode($this->legends[$i]), 0, 27, 'UTF-8');
            if (strlen(urldecode($this->legends[$i])) > 27) $l .= $GLOBALS['strEllipsis'];
    
            if ($this->use_ttf)
            {
                imagettftext($this->img, 8, 0, 270, ($legendY + 9), $this->black, $CONFIG['font_file'], "{$l} ({$this->data[$i]})");
            }
            else
            {
                imagestring($this->img,2, 270, ($legendY - 1), "{$l} ({$this->data[$i]})", $this->black);
            }
            // imagearc($this->img,$cx,$cy,$sx,$sy,$angle_sum[$i1] ,$angle_sum[$i], $blue);
            $legendY += 15;
        }
    }
    
    
    function draw_line_chart()
    {
        $maxdata = 0;
        $colwidth = round($width / $this->numberOfDataElements());
        $rowheight = round($height / 10);
        foreach ($this->data AS $dataval)
        {
            if ($dataval > $maxdata) $maxdata = $dataval;
        }
    
        imagerectangle($this->img, $this->width-1, $this->height-1, 0, 0, $this->black);
        for ($i = 1; $i < $this->numberOfDataElements(); $i++)
        {
            imageline($this->img, $i * $colwidth, 0, $i * $colwidth, $this->width, $this->grey);
            imageline($this->img, 2, $i * $rowheight, $this->width - 2, $i * $rowheight, $this->grey);
        }
    
        for ($i = 0; $i < $this->numberOfDataElements(); $i++)
        {
            $dataheight = ($this->height - ($this->data[$i] / $maxdata) * $this->height);
            $legendheight = $dataheight > ($this->height - 15) ? $this->height - 15 : $dataheight;
            $nextdataheight = ($this->height - ($this->data[$i + 1] / $maxdata) * $this->height);
            imageline($this->img, $i * $colwidth, $dataheight, ($i + 1) * $colwidth, $nextdataheight, $this->red);
            imagestring($this->img, 3, $i * $colwidth, $legendheight, mb_substr($legends[$i], 0, 6, 'UTF-8'), $this->darkblue);
        }

        imagestring($this->img,3, 10, 10, $this->title, $this->red);
    }
    
    
    function draw_bar_chart()
    {
        $maxdata = 0;
        $colwidth = round($this->width / $this->numberOfDataElements());
        $rowheight = round($this->height / 10);
        foreach ($this->data AS $dataval)
        {
            if ($dataval > $maxdata) $maxdata = $dataval;
        }
    
        imagerectangle($this->img, $this->width-1, $this->height-1, 0, 0, $this->black);
        for ($i = 1; $i < $this->numberOfDataElements(); $i++)
        {
            imageline($this->img, $i * $colwidth, 0, $i * $colwidth, $this->width, $this->grey);
            imageline($this->img, 2, $i * $rowheight, $this->width - 2, $i * $rowheight, $this->grey);
        }
    
        for ($i = 0; $i < $this->numberOfDataElements(); $i++)
        {
            $dataheight = ($this->height - ($this->data[$i] / $maxdata) * $this->height);
            $legendheight = $dataheight > ($this->height - 15) ? $this->height - 15 : $dataheight;
            imagefilledrectangle($this->img, $i * $colwidth, $dataheight, ($i + 1) * $colwidth, $this->height, $this->darkblue);
            imagefilledrectangle($this->img, ($i * $colwidth)+1, $dataheight + 1, (($i + 1) * $colwidth) - 3, ($this->height - 2), $this->midblue);
            imagestring($this->img, 3, ($i * $colwidth) + 4, $legendheight, mb_substr($this->legends[$i], 0, 5,'UTF-8'), $this->darkblue);
        }
        imagestring($this->img,3, 10, 10, $this->title, $this->red);
    }
    
    
    function draw_error()
    {
        imagerectangle($this->img, $this->width - 1, $this->height - 1, 1, 1, $this->red);
        imagestring($this->img, 3, 10, 10, "Invalid chart type", $this->red);
    }
}