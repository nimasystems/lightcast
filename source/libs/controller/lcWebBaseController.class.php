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
 * @changed $Id: lcWebBaseController.class.php 1552 2014-08-01 07:13:50Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1552 $
 */
abstract class lcWebBaseController extends lcController
{
    protected function renderRaw($content, $content_type = 'text/html', $decorated = false)
    {
        if (!$decorated) {
            // unset the decorator first
            $this->unsetDecorator();
        }

        $view = new lcRawContentView();
        $view->setController($this);
        $view->setConfiguration($this->configuration);
        $view->setEventDispatcher($this->event_dispatcher);
        $view->setContent($content);
        $view->setContentType($content_type);
        $view->initialize();

        // unset the previous view
        $this->unsetView();

        // assign the new view
        $this->view = $view;
    }

    protected function renderHttpError($error_code = 500, $reason_string = null)
    {
        $response = $this->response;
        $response->setStatusCode($error_code, $reason_string);
        $response->setContent($reason_string);
        $response->send();
    }

    protected function render404($message = 'Page not found')
    {
        $this->renderHttpError(404, $message);
    }

    protected function reloadPage()
    {
        if (!$this->request) {
            throw new lcNotAvailableException('Request not available');
        }

        $request_uri = $this->request->getRequestUri();

        if (!$request_uri) {
            return;
        }

        $this->redirect($request_uri);
    }

    protected function redirectIfNot($url, $condition, $http_code = 302)
    {
        if (!$condition) {
            $this->redirect($url, $http_code);
        }
    }

    protected function redirectIf($url, $condition, $http_code = 302)
    {
        if ($condition) {
            $this->redirect($url, $http_code);
        }
    }

    protected function redirect($url, $http_code = 302)
    {
        if (!$url) {
            throw new lcInvalidArgumentException('Invalid URL');
        }

        if (!$this->response) {
            throw new lcNotAvailableException('Response not available');
        }

        $res = array(
            'http_code' => $http_code,
            'allow_redirect' => true,
            'controller_name' => $this->getControllerName(),
            'action_name' => $this->getActionName(),
        );

        // notify about this redirect and allow others to rewrite it

        $event = new lcEvent('controller.redirect', $this, $res);
        $evn = $this->event_dispatcher->filter($event, $url);

        unset($event);

        if ($evn->isProcessed()) {
            $v = $evn->getReturnValue();
            $allow_redirect = $v && ((isset($v['allow_redirect']) && (bool)$v['allow_redirect']) || !isset($v['allow_redirect']));

            if (!$allow_redirect) {
                return false;
            }

            $http_code = isset($v['http_code']) ? (string)$v['http_code'] : $http_code;
            $url = isset($v['url']) ? (string)$v['url'] : $url;
        }

        $this->response->redirect($url, $http_code);
    }
}