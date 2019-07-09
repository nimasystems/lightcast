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

abstract class lcWebBaseController extends lcController
{
    /**
     * @var lcPatternRouting
     */
    protected $routing;

    /** @var lcWebRequest */
    protected $request;

    /** @var lcWebResponse */
    protected $response;

    private $last_redirect_url;

    public function generateUrl(array $params = null, $route = null, $absolute_url = false)
    {
        $router = $this->routing;

        if (!$router) {
            throw new lcNotAvailableException('Router not available');
        }

        $url = $router->generate($params, $absolute_url, $route);
        return $url;
    }

    public function render404($message = 'Page not found')
    {
        $this->renderHttpError(404, $message);
    }

    public function renderHttpError($error_code = 500, $reason_string = null)
    {
        $response = $this->response;
        $response->setStatusCode($error_code, $reason_string);
        $response->setContent($reason_string);
        $response->send();
    }

    public function reloadPage()
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

    public function redirect($url, $http_code = 302)
    {
        if (!$url) {
            throw new lcInvalidArgumentException('Invalid URL');
        }

        if (!$this->response) {
            throw new lcNotAvailableException('Response not available');
        }

        $this->last_redirect_url = $url;

        $res = [
            'http_code' => $http_code,
            'allow_redirect' => true,
            'controller_name' => $this->getControllerName(),
            'action_name' => $this->getActionName(),
        ];

        // notify about this redirect and allow others to rewrite it

        $event = new lcEvent('controller.redirect', $this, $res);
        $evn = $this->event_dispatcher->filter($event, $url);

        unset($event);

        if ($evn->isProcessed()) {
            $v = $evn->getReturnValue();
            $allow_redirect = $v && ((isset($v['allow_redirect']) && (bool)$v['allow_redirect']) || !isset($v['allow_redirect']));

            if (!$allow_redirect) {
                return;
            }

            $http_code = isset($v['http_code']) ? (string)$v['http_code'] : $http_code;
            $url = isset($v['url']) ? (string)$v['url'] : $url;
        }

        $this->last_redirect_url = $url;
        $this->response->redirect($url, $http_code);
    }

    public function permanentRedirect($url)
    {
        $this->redirect($url, 301);
    }

    public function getLastRedirectUrl()
    {
        return $this->last_redirect_url;
    }

    public function redirectIfNot($url, $condition, $http_code = 302)
    {
        if (!$condition) {
            $this->redirect($url, $http_code);
        }
    }

    public function redirectIf($url, $condition, $http_code = 302)
    {
        if ($condition) {
            $this->redirect($url, $http_code);
        }
    }

    protected function renderJson($content)
    {
        $this->renderRaw(lcVm::json_encode($content, true), 'application/json');
    }

    protected function renderRaw($content, $content_type = 'text/html', $decorated = false)
    {
        if (!$decorated) {
            // unset the decorator first
            $this->unsetDecoratorView();
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

    protected function validateRequestAndThrow()
    {
        $args = func_get_args();

        if ($args) {
            foreach ($args as $arg) {
                if (($arg == self::VPOST && !$this->request->isPost()) ||
                    ($arg == self::VPUT && !$this->request->isPut()) ||
                    ($arg == self::VGET && !$this->request->isGet()) ||
                    ($arg == self::VDELETE && !$this->request->isDelete()) ||
                    ($arg == self::VAJAX && !$this->request->isAjax()) ||
                    !$arg
                ) {
                    throw new lcInvalidRequestException($this->t('Invalid Request'));
                } else if (($arg == self::VAUTH && !$this->user->isAuthenticated()) ||
                    ($arg == self::VNAUTH && $this->user->isAuthenticated())) {
                    throw new lcAuthException($this->t('Authentication is not valid'));
                }
            }
        }
    }
}