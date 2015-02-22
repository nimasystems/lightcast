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
 * @changed $Id: lcPHPRouting.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcPHPRouting extends lcRouting
{
	protected $request;

	public function initialize()
	{
		parent::initialize();

		$this->request = $this->event_dispatcher->provide('loader.request', $this)->getReturnValue();

		// allow others to be notified when base routes have been loaded
		$this->event_dispatcher->notify(new lcEvent('router.load_configuration', $this, array(
				'context' => $this->context
				)));

		// try to detect the parameters from request
		$this->detectParameters();
	}

	public function shutdown()
	{
		$this->request = null;

		parent::shutdown();
	}

	private function detectParameters()
	{
		$res = $this->parse($this->context['request_uri']);
		$result = null;

		if ($res && isset($res['module']) && isset($res['action']))
		{
			$result = array('params' => $res);
		}

		$this->event_dispatcher->notify(new lcEvent('router.detect_parameters', $this, $result));
	}

	// TODO: Finish this implementation
	public function getParams()
	{
		return false;
	}

	public function getParamsByCriteria($criteria)
	{
		fnothing($criteria);
		return false;
	}

	public function generate($params = array(), $absolute = false, $name = null)
	{
		fnothing($name);

		!isset($params['application']) ?
		$params['application'] = $this->getDefaultParams()->get('application') :
		null;

		!isset($params['controller']) ?
		$params['controller'] = $this->getDefaultParams()->get('controller') :
		null;

		!isset($params['action']) ?
		$params['action'] = $this->getDefaultParams()->get('action') :
		null;

		$params = http_build_query($params, null, '&');

		return $this->fixGeneratedUrl('/'.($params ? '?'.$params : ''), $absolute);
	}

	public function parse($url)
	{
		// TODO: What is $url for here?
		fnothing($url);
		
		// get the prefixes of URL matching vars
		$this->context['application_prefix'] = $this->configuration['routing.application_prefix'] ?
		$this->configuration['routing.application_prefix'] : 'application';

		$this->context['module_prefix'] = $this->configuration['routing.module_prefix'] ?
		$this->configuration['routing.module_prefix'] : 'module';

		$this->context['action_prefix'] = $this->configuration['routing.action_prefix'] ?
		$this->configuration['routing.action_prefix'] :  'action';

		$params = array();

		// not really sure about this - but we know the application name for sure (as it was booted with it)
		$params['application'] = $this->configuration->getApplicationName();

		$get_params = $this->context['get_params']->getArrayCopy();

		// set all GET params into params
		if ($get_params)
		{
			foreach($get_params as $param)
			{
				$value = $param->getValue();

				if (!is_string($value))
				{
					continue;
				}

				$params[$param->getName()] = $value;

				unset($param, $value);
			}

			unset($get_params);
		}

		// find if we have the rest in the GET vars
		if ($module = $this->context['get_params']->get($this->context['module_prefix']))
		{
			$params['module'] = $module;
		}

		if ($action = $this->context['get_params']->get($this->context['action_prefix']))
		{
			$params['action'] = $action;
		}

		return $params;
	}
}

?>