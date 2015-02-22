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
 * @changed $Id: lcRssItem.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
*/


class lcRssItem extends lcObj
{
	private $title;
	private $link;
	private $descr;
	private $permlink = false;
	private $guid;
	private $publishdate = 0; //unix timestamp
	private $enclosure;

	public function __construct($title, $link, $publishdate=0, $guid = null,
			$permlink = false,$descr = null)
	{
		parent::__construct();

		$this->setTitle($title);
		$this->setLink($link);
		$this->setPublishDate($publishdate);
		$this->setGuid($guid,$permlink);
		$this->setDescription($descr);
	}

	public function setTitle($title)
	{
		$this->title = $title;
	}

	public function setEnclosure($url, $length = null, $type = null)
	{
		$this->enclosure = array(
				'url' => $url,
				'length' => $length,
				'type' => $type
		);
	}

	public function getEnclosure()
	{
		return $this->enclosure;
	}

	public function setLink($link)
	{
		$this->link = $link;
	}

	public function setDescription($descr)
	{
		$this->descr = $descr;
	}

	public function setGUID($guid, $permlink = false)
	{
		$this->guid = $guid;
		$this->permlink = $permlink;
	}

	public function setPublishDate($pdate)
	{
		$this->publishdate = $pdate;
	}

	public function getTitle()
	{
		return $this->fFixTags($this->title);
	}

	public function getLink()
	{
		return $this->link;
	}

	private function fFixTags($content)
	{
		$content = str_replace('<','&lt;',$content);
		$content = str_replace('>','&gt;',$content);
		return $content;
	}

	public function getDescription()
	{
		return $this->fFixTags($this->descr);
	}

	public function getGuid()
	{
		if (strlen($this->guid) > 0)
		{
			return $this->guid;
		}
		else
		{
			return false;
		}
	}

	public function isGuidPermanent()
	{
		return $this->permlink;
	}

	public function getPublishDate()
	{
		return $this->publishdate;
	}
}

?>