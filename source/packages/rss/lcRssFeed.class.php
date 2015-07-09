<?php
/*
 * Lightcast - A PHP MVC Framework
* Copyright (C) 2005 Nimasystems Ltd
*
* This program is NOT free software; you cannot redistribute and/or modify
* it's sources under any circumstances without the explicit knowledge and
* agreement of the rightful owner of the software - Nimasystems Ltd.
*
* This program is distributed WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
* PURPOSE.  See the LICENSE.txt file for more information.
*
* You should have received a copy of LICENSE.txt file along with this
* program; if not, write to:
* NIMASYSTEMS LTD
* Plovdiv, Bulgaria
* ZIP Code: 4000
* Address: 95 "Kapitan Raycho" Str.
* E-Mail: info@nimasystems.com
*/

/**
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcRssFeed.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
class lcRssFeed extends lcObj
{
    const RSS_MIMETYPE = 'application/rss+xml';
    const RSS_TIME_FORMAT = 'D, d M Y H:i:s O';

    /** @var lcRssItem[] */
    private $items = array(); //of RSSItem

    private $language = 'en';
    private $encoding = 'utf-8';
    private $builddate = 0; //unixtime
    private $feed_title;
    private $feed_link;
    private $feed_descr;
    private $managingEditor;
    private $webMaster;
    private $feedUrl;

    public function __construct($feed_title, $feed_link, $builddate = 0,
                                $language = 'en', $feed_descr = null)
    {
        parent::__construct();

        $this->items = array();

        $this->setFeedTitle($feed_title);
        $this->setFeedLink($feed_link);
        $this->setBuildDate($builddate);
        $this->setLanguage($language);
        $this->setFeedDescription($feed_descr);
    }

    public function setFeedTitle($title)
    {
        $this->feed_title = $title;
    }

    public function setFeedLink($link)
    {
        $this->feed_link = $link;
    }

    public function setBuildDate($bdate)
    {
        if ($bdate < 1) {
            $bdate = time();
        }

        $this->builddate = (int)$bdate;
    }

    public function setLanguage($lang = 'en')
    {
        $this->language = $lang;
    }

    public function setEncoding($encoding = 'utf-8')
    {
        $this->encoding = $encoding;
    }

    public function setFeedDescription($descr)
    {
        $this->feed_descr = $descr;
    }

    public function setManagingEditor($value)
    {
        $this->managingEditor = $value;
    }

    public function setWebmaster($value)
    {
        $this->webMaster = $value;
    }

    public function setFeedURL($value)
    {
        $this->feedUrl = $value;
    }

    public function getFeedTitle()
    {
        return $this->feed_title;
    }

    public function getFeedLink()
    {
        return $this->feed_link;
    }

    public function getBuildDate($format = self::RSS_TIME_FORMAT)
    {
        if ($format) {
            return date($format, $this->builddate);
        } else {
            return (int)$this->builddate;
        }
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function getFeedDescription()
    {
        return $this->feed_descr;
    }

    public function addItem(lcRssItem $item)
    {
        $max = count($this->items);
        $this->items[$max] = $item;
        return $max;
    }

    public function generateFeed()
    {
        if (!$this->feed_title || !$this->feed_link || !$this->language || !$this->encoding || $this->builddate < 1) {
            return false;
        }

        $rss = '';
        $rss .= "<?xml version=\"1.0\" encoding=\"{$this->encoding}\" ?>\n";
        $rss .= "<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n";
        $rss .= "	<channel>\n";
        $rss .= "	<atom:link href=\"{$this->feedUrl}\" rel=\"self\" type=\"application/rss+xml\" />\n";
        $rss .= "		<title>{$this->feed_title}</title>\n";
        $rss .= "		<link>{$this->feed_link}</link>\n";
        $rss .= "		<language>{$this->language}</language>\n";
        $rss .= "		<description>{$this->feed_descr}</description>\n";
        $rss .= "		<docs>http://feedvalidator.org/docs/rss2.html</docs>\n";
        $rss .= "		<generator>Nimasystems RSS2 Feed Generator - http://www.nimasystems.com</generator>\n";
        $rss .= "		<managingEditor>{$this->managingEditor}</managingEditor>\n";
        $rss .= "		<webMaster>{$this->webMaster}</webMaster>\n";
        $rss .= "		<copyright>Copyright 2006, Nimasystems Ltd, RSS2 Feed Generator: Nimasystems</copyright>\n";
        $rss .= "		<lastBuildDate>" . $this->GetBuildDate(self::RSS_TIME_FORMAT) . "</lastBuildDate>\n";

        foreach ($this->items as $item) {
            if (!$item->getTitle() || !$item->getLink()) {
                continue;
            }

            $title = $item->getTitle();

            if ($enc = $item->getEnclosure()) {
                $enc = '<enclosure url="' . $enc['url'] . '"' .
                    ($enc['length'] ? ' length="' . $enc['length'] . '"' : null) .
                    ($enc['type'] ? ' type="' . $enc['type'] . '"' : null) .
                    ' />';
            }

            $rss .= "		<item>\n";
            $rss .= "			<title>" . $title . "</title>\n";
            $rss .= "			<link>" . $item->getLink() . "</link>\n";
            $rss .= "			<guid isPermaLink=\"" . ($item->isGuidPermanent() ? 'true' : 'false') . "\">" . '1234567890' . ($item->getGuid()) . "</guid>\n";
            $rss .= "			<description>" . $item->getDescription() . "</description>\n";
            $rss .= "			<pubDate>" . $item->getPublishDate() . "</pubDate>\n";
            $rss .= $enc ? "			" . $enc . "\n" : null;
            $rss .= "		</item>\n";

            unset($title, $enc, $item);
        }

        $rss .= "	</channel>\n";
        $rss .= "</rss>";

        return $rss;
    }
}