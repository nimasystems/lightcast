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
 * @changed $Id: lcComponent.class.php 1535 2014-06-05 17:11:56Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1535 $
 *
 */
abstract class lcComponent extends lcBaseController
{
    /**
     * @var lcController
     */
    protected $controller;

    public function getDefaultViewInstance()
    {
        $view = new lcRawContentView();
        return $view;
    }

    public function getProfilingData()
    {
        // TODO: Complete this
        return null;
    }

    public function execute()
    {
        $rendered_contents = $this->render();

        if (!$rendered_contents) {
            return false;
        }

        $content = $rendered_contents['content'];
        return $content;
    }

    public function shutdown()
    {
        $this->controller = null;

        parent::shutdown();
    }

    public function getController()
    {
        return $this->controller;
    }

    public function setController(lcBaseController $controller)
    {
        $this->controller = $controller;
    }

    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return '';
        }
    }

    protected function renderControllerView(lcBaseController $controller, lcView $view)
    {
        if (!$controller || !$view) {
            return null;
        }

        $content_type = null;
        $output = null;

        try {
            // set the filtering chain
            $view->setViewFilterChain($this->view_filter_chain);

            // set the decorator
            if ($controller instanceof iViewDecorator && $view instanceof iDecoratingView) {
                $view->setViewDecorator($controller);
            }

            // get the view's output
            $this->event_dispatcher->notify(new lcEvent('view.will_render', $this, array(
                'view' => $view,
                'context_name' => $controller->getContextName(),
                'context_type' => $controller->getContextType(),
                'translation_context_name' => $controller->getTranslationContextName(),
                'translation_context_type' => $controller->getTranslationContextType()
            )));

            // render the view
            $output = $view->render();

            // send view render event
            $event = $this->event_dispatcher->filter(new lcEvent('view.render', $this, array(
                'view' => $view,
                'context_type' => $controller->getContextType(),
                'context_name' => $controller->getContextName(),
                'translation_context_name' => $controller->getTranslationContextName(),
                'translation_context_type' => $controller->getTranslationContextType()
            )), $output);

            $output = $event->getReturnValue();

            $content_type = $view->getContentType();
        } catch (Exception $e) {
            throw new lcViewRenderException('Could not render view: ' . $e->getMessage(), $e->getCode(), $e);
        }

        $ret = array(
            'content_type' => $content_type,
            'content' => $output
        );

        return $ret;
    }
}
