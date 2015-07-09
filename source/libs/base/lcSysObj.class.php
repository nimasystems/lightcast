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
 * @changed $Id: lcSysObj.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
abstract class lcSysObj extends lcObj implements iLoggable, iI18nProvider
{
    const CONTEXT_PROJECT = 1;
    const CONTEXT_APP = 2;
    const CONTEXT_PLUGIN = 3;
    const CONTEXT_FRAMEWORK = 4;

    protected $translation_context_type;
    protected $translation_context_name;

    /**
     * @var lcPlugin
     */
    protected $parent_plugin;

    /** @var  lcPluginManager */
    protected $plugin_manager;

    protected $context_type;
    protected $context_name;

    /**
     * @var lcLogger
     */
    protected $logger;

    /**
     * @var lcI18n
     */
    protected $i18n;
    /**
     * @var lcClassAutoloader
     */
    protected $class_autoloader;
    /**
     * @var lcEventDispatcher
     */
    protected $event_dispatcher;
    /**
     * @var lcConfiguration
     */
    protected $configuration;
    private $has_initialized = false;

    public function __construct()
    {
        parent::__construct();

        // set defaults
        $this->context_type = self::CONTEXT_FRAMEWORK;
        $this->translation_context_type = self::CONTEXT_FRAMEWORK;
    }

    public static function getContextTypeAsConst($context_type_str)
    {
        $context_type_str = (string)$context_type_str;
        $context_type = null;

        switch ($context_type_str) {
            case 'app': {
                $context_type = self::CONTEXT_APP;
                break;
            }
            case 'framework': {
                $context_type = self::CONTEXT_FRAMEWORK;
                break;
            }
            case 'plugin': {
                $context_type = self::CONTEXT_PLUGIN;
                break;
            }
            case 'project': {
                $context_type = self::CONTEXT_PROJECT;
                break;
            }
        }

        return $context_type;
    }

    public static function getContextTypeAsString($context_type)
    {
        $context_type = (int)$context_type;
        $str = null;

        switch ($context_type) {
            case self::CONTEXT_APP: {
                $str = 'app';
                break;
            }
            case self::CONTEXT_FRAMEWORK: {
                $str = 'framework';
                break;
            }
            case self::CONTEXT_PLUGIN: {
                $str = 'plugin';
                break;
            }
            case self::CONTEXT_PROJECT: {
                $str = 'project';
                break;
            }
        }

        return $str;
    }

    public function initialize()
    {
        // the method is called after all system objects have been loaded into ram and configuration has been read
        $this->has_initialized = true;
    }

    public function shutdown()
    {
        // remove all events from event dispatcher
        $this->removeObservers();

        $this->event_dispatcher =
        $this->configuration =
        $this->class_autoloader =
        $this->parent_plugin =
            null;

        $this->has_initialized = false;
    }

    public function removeObservers()
    {
        if ($this->event_dispatcher) {
            $this->event_dispatcher->disconnectListener($this);
        }
    }

    public function setPluginManager(lcPluginManager $plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    public function _onI18nStartup(lcEvent $event)
    {
        $this->i18n = $event->subject;
    }

    public function _onLoggerStartup(lcEvent $event)
    {
        $this->logger = $event->subject;
    }

    public function getHasInitialized()
    {
        return $this->has_initialized;
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function setConfiguration(lcConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getEventDispatcher()
    {
        return $this->event_dispatcher;
    }

    public function setEventDispatcher(lcEventDispatcher $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
    }

    #pragma mark - Context / Plugin containment

    public function getClassAutoloader()
    {
        return $this->class_autoloader;
    }

    public function setClassAutoloader(lcClassAutoloader $class_autoloader)
    {
        $this->class_autoloader = $class_autoloader;
    }

    public function getContextName()
    {
        return $this->context_name;
    }

    public function setContextName($context_name)
    {
        $this->context_name = $context_name;
    }

    public function getContextType()
    {
        return $this->context_type;
    }

    public function setContextType($context_type)
    {
        $this->context_type = $context_type;
    }

    public function getParentPlugin()
    {
        return $this->parent_plugin;
    }

    public function setParentPlugin(lcPlugin $parent_plugin = null)
    {
        $this->parent_plugin = $parent_plugin;

        if ($parent_plugin) {
            $this->setContextName($parent_plugin->getPluginName());
            $this->setContextType(lcSysObj::CONTEXT_PLUGIN);
            $this->setTranslationContext(lcI18n::CONTEXT_PLUGIN, $parent_plugin->getPluginName());
        }
    }

    public function setTranslationContext($context_type, $context_name = null)
    {
        $this->translation_context_type = $context_type;
        $this->translation_context_name = $context_name;
    }

    public function getMyPlugin()
    {
        return $this->parent_plugin;
    }

    public function isContainedWithinPlugin()
    {
        return ($this->parent_plugin != null);
    }

    #pragma mark - i18n

    public function getContainerPluginName()
    {
        $ret = $this->parent_plugin ? $this->parent_plugin->getPluginName() : null;
        return $ret;
    }

    public function getI18n()
    {
        return $this->i18n;
    }

    public function setI18n(lcI18n $i18n = null)
    {
        $this->i18n = $i18n;
    }

    /**
     * @param $text string
     * @return string
     */
    public function t($text)
    {
        return $this->translate($text);
    }

    public function translate($text)
    {
        return $this->translateInContext($this->translation_context_type, $this->translation_context_name, $text);
    }

    public function translateInContext($context_type, $context_name, $string, $translation_domain = null)
    {
        $i18n = $this->i18n;

        if (!$i18n) {
            return $string;
        }

        return $i18n->translateInContext($context_type, $context_name, $string, $translation_domain);
    }

    public function getTranslationContextName()
    {
        return $this->translation_context_name;
    }

    public function getTranslationContextType()
    {
        return $this->translation_context_type;
    }

    #pragma mark - Logging

    public function getLogger()
    {
        return $this->logger;
    }

    public function setLogger(lcLogger $logger = null)
    {
        $this->logger = $logger;
    }

    public function emerg($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_EMERG, $channel);
    }

    public function log($message_code, $severity = null, $channel = null)
    {
        if ($this->logger) {
            $this->logger->log($message_code, $severity, $channel);
        }
    }

    public function alert($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_ALERT, $channel);
    }

    public function crit($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_CRIT, $channel);
    }

    /*
     * LC 1.4 Compatibility method
    */

    public function err($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_ERR, $channel);
    }

    public function warn($message_code, $channel = null)
    {
        $this->warning($message_code, $channel);
    }

    public function warning($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_WARNING, $channel);
    }

    public function notice($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_NOTICE, $channel);
    }

    public function info($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_INFO, $channel);
    }

    public function debug($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_DEBUG, $channel);
    }

    public function logExtended($message, $severity = null, $filename = null, $ignore_severity_check = false, $cleartext = false, $channel = null)
    {
        if ($this->logger) {
            $this->logger->logExtended($message, $severity, $filename, $ignore_severity_check, $cleartext, $channel);
        }
    }
}