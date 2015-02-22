<?php
/*
 * Lightcast - A PHP MVC Framework Copyright (C) 2005 Nimasystems Ltd This program is NOT free
 * software; you cannot redistribute and/or modify it's sources under any circumstances without the
 * explicit knowledge and agreement of the rightful owner of the software - Nimasystems Ltd. This
 * program is distributed WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the LICENSE.txt file for more information. You should
 * have received a copy of LICENSE.txt file along with this program; if not, write to: NIMASYSTEMS
 * LTD Plovdiv, Bulgaria ZIP Code: 4000 Address: 95 "Kapitan Raycho" Str. E-Mail:
 * info@nimasystems.com
 */

/**
 * File Description
 *
 * @package File Category
 * @subpackage File Subcategory
 *             @changed $Id: lcHtmlComponent.class.php 1535 2014-06-05 17:11:56Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1535 $
 *         
 */
abstract class lcHtmlComponent extends lcComponent {

	public function initialize() {
		parent::initialize();
		
		if (!$this->view) {
			// init with default view
			$template_filename = $this->getControllerDirectory() . DS . 'templates' . DS . $this->getControllerName() . '.htm';
			
			$view = new lcHTMLTemplateView();
			$view->setController($this);
			$view->setEventDispatcher($this->event_dispatcher);
			$view->setConfiguration($this->configuration);
			$view->setTemplateFilename($template_filename);
			$view->initialize();
			
			$this->setView($view);
		}
	}

	public function shutdown() {
		// shutdown the view
		if ($this->view) {
			$this->view->shutdown();
			$this->view = null;
		}
		
		$this->controller = null;
		
		parent::shutdown();
	}
}

?>