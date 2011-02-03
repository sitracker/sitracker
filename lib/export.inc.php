<?php
// export.inc.php - functions relating to exporting data in various formats
//                  e.g. RSS/vcard/XML etc.
//
// SiT (Support Incident Tracker) - Support call tracking system
// Copyright (C) 2010-2011 The Support Incident Tracker Project
// Copyright (C) 2000-2009 Salford Software Ltd. and Contributors
//
// This software may be used and distributed according to the terms
// of the GNU General Public License, incorporated herein by reference.

// Author: Ivan Lucas <ivanlucas[at]users.sourceforge.net>
//            Paul Heaney <paul[at]sitracker.org>

// Prevent script from being run directly (ie. it must always be included
if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME']))
{
    exit;
}


/**
 * This class represents an RSS/Atom feed, containing the necessary header details and feed items
 * @author Ivan Lucas, Paul heaney
 */
class Feed
{
    var $title = '';
    var $feedurl = '';
    var $description = '';
    var $pubdate = '';

    var $items = array();

    /**
     * Generates and displays the RSS feed as well as setting the header type
     * @author Paul Heaney
     */
    function generate_feed()
    {
    	header("Content-Type: application/xml");
        echo $this->generate_feed_xml();
    } 


    /**
     * Generates the RSS XML
     * @author Paul Heaney
     * @return string The XML string for the feed
     */
    function generate_feed_xml()
    {
	    global $CONFIG, $application_version_string;

        if (!empty($_SESSION['lang'])) $lang = $_SESSION['lang'];
        else $lang = $CONFIG['default_i18n'];
    
        $xml = "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">";
        $xml .= "<channel><title>{$this->title}</title>\n";
        $xml .= "<link>".application_url()."</link>\n";
        $xml .= "<atom:link href=\"{$this->feedurl}\" rel=\"self\" type=\"application/rss+xml\" />\n";
        $xml .= "<description>{$this->description}</description>\n";
        $xml .= "<language>{$lang}</language>\n";
        $xml .= "<pubDate>".date('r', $this->pubdate)."</pubDate>\n";
        $xml .= "<lastBuildDate>".date('r', $this->pubdate)."</lastBuildDate>\n";
        $xml .= "<docs>http://blogs.law.harvard.edu/tech/rss</docs>";
        $xml .= "<generator>{$CONFIG['application_name']} {$application_version_string}</generator>\n";
        $xml .= "<webMaster>".user_email($CONFIG['support_manager'])." (Support Manager)</webMaster>\n";
    
    
        if (is_array($this->items))
        {
            foreach ($this->items AS $item)
            {
                $xml .= $item->generateItem();
            }
        }

        $xml .= "</channel></rss>\n";
        return $xml;
    }
}


/**
 * This represents a item on an RSS/Atom feed
 * @author Paul Heaney
 */
class FeedItem
{
    var $title = '';
    var $author = '';
    var $link = '';
    var $description = '';
    var $pubdate = ''; // Unix Timestamp
    var $guid = '';


    /**
 * Generates the XML for this particular item
 * @author Paul Heaney
 * @return string The  XML for this item
 */
    function generateItem()
    {
        if ($this->pubdate == 0) $this->pubdate = $now;
        $itemxml .= "<item>\n";
        $itemxml .= "<title>{$this->title}</title>\n";
        $itemxml .= "<author>{$this->author}</author>\n";
        $itemxml .= "<link>{$this->link}</link>\n";
        $itemxml .= "<description>{$this->description}</description>\n";
        $itemxml .= "<pubDate>".date('r',$this->pubdate)."</pubDate>\n";
        $itemxml .= "<guid isPermaLink=\"false\">{$this->guid}</guid>\n";
        $itemxml .= "</item>\n";

        return $itemxml;
    }
}

?>