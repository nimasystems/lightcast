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
 * @changed $Id: lcRawContentView.class.php 1455 2013-10-25 20:29:31Z mkovachev $
* @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcRawContentView extends lcView implements iDebuggable
{
	const DEFAULT_CONTENT_TYPE = 'text/html';
	
	protected $content;
	protected $content_type = self::DEFAULT_CONTENT_TYPE;

	public function shutdown()
	{
		$this->content = null;

		parent::shutdown();
	}
	
	public function getSupportedContentTypes()
	{
		// any type
		return null;
	}
	
	protected function getViewContent()
	{
		assert(($this->content && (is_null($this->content) || is_string($this->content))) || !$this->content);
		return $this->content;
	}

	public function getDebugInfo()
	{
		$debug_parent = (array)parent::getDebugInfo();

		$debug = array(
				'content_type' => $this->content_type,
				'content_length' => strlen((string)$this->content)
		);

		$debug = array_merge($debug_parent, $debug);

		return $debug;
	}

	public function getShortDebugInfo()
	{
		return false;
	}
	
	public function setContent($content)
	{
		$this->content = $content;
	}
}

?>