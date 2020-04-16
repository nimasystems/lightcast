<?php

abstract class lcBasePropelObject extends BaseObject
{
    /**
     * @var lcEventDispatcher
     */
    protected $event_dispatcher;

    /**
     * @var lcApplicationConfiguration
     */
    protected $application_configuration;

    abstract public function getPrimaryKey();

    public function __construct()
    {
        parent::__construct();
        $this->application_configuration = $GLOBALS['configuration'];
        $this->event_dispatcher = $this->application_configuration->getEventDispatcher();
    }

    public function __call($name, $params)
    {
        if (preg_match('/set(\w+)/', $name, $matches)) {
            $virtualColumn = $matches[1];
            $value = isset($params[0]) ? $params[0] : true;

            return $this->setVirtualColumn($virtualColumn, $value);
        } else {
            return parent::__call($name, $params);
        }
    }

    public function __get($name)
    {
        $m = 'get' . (ctype_upper($name[0]) ? $name : lcInflector::camelize($name));

        if (method_exists($this, $m)) {
            return $this->$m();
        }
    }

    public function __set($name, $value)
    {
        $m = 'set' . (ctype_upper($name[0]) ? $name : lcInflector::camelize($name));

        if (method_exists($this, $m)) {
            $this->$m($value);
        }
    }

    public function __isset($name)
    {
        $m = 'get' . (ctype_upper($name[0]) ? $name : lcInflector::camelize($name));
        return method_exists($this, $m);
    }

    public function setEventDispatcher(lcEventDispatcher $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
    }

    public function setApplicationConfiguration(lcApplicationConfiguration $configuration)
    {
        $this->application_configuration = $configuration;
    }

    public function postSave(PropelPDO $con = null)
    {
        parent::postSave($con);

        if ($this->event_dispatcher) {
            $this->event_dispatcher->notify(new lcEvent('data_model.after_save', $this));
        }
    }

    public function preInsert(PropelPDO $con = null)
    {
        if ($this->event_dispatcher) {
            $this->event_dispatcher->notify(new lcEvent('data_model.before_create', $this));
        }

        return parent::preInsert($con);
    }

    public function postInsert(PropelPDO $con = null)
    {
        parent::postInsert($con);

        if ($this->event_dispatcher) {
            $this->event_dispatcher->notify(new lcEvent('data_model.after_create', $this));
        }
    }

    public function preUpdate(PropelPDO $con = null)
    {
        if ($this->event_dispatcher) {
            $this->event_dispatcher->notify(new lcEvent('data_model.before_update', $this));
        }

        return parent::preUpdate($con);
    }

    public function postUpdate(PropelPDO $con = null)
    {
        parent::postUpdate($con);

        if ($this->event_dispatcher) {
            $this->event_dispatcher->notify(new lcEvent('data_model.after_update', $this));
        }
    }

    public function preDelete(PropelPDO $con = null)
    {
        if ($this->event_dispatcher) {
            $this->event_dispatcher->notify(new lcEvent('data_model.before_delete', $this));
        }

        return parent::preDelete($con);
    }

    public function postDelete(PropelPDO $con = null)
    {
        parent::postDelete($con);

        if ($this->event_dispatcher) {
            $this->event_dispatcher->notify(new lcEvent('data_model.after_delete', $this));
        }
    }

    public function getFormattedTitle()
    {
        return '#' . $this->getPrimaryKey();
    }

    protected function translate($t)
    {
        return $this->getPeer()->getTableMap()->translate($t);
    }

    protected function t($t)
    {
        return $this->translate($t);
    }

    public function getVirtualColumn($name)
    {
        // no exceptions at this point
        // overriden for this purpose
        return (isset($this->virtualColumns[$name]) ? $this->virtualColumns[$name] : null);
    }

    protected function logError($msg)
    {
        return $this->log($msg, Propel::LOG_ERR);
    }

    protected function logInfo($msg)
    {
        return $this->log($msg, Propel::LOG_INFO);
    }

    protected function logWarn($msg)
    {
        return $this->log($msg, Propel::LOG_WARNING);
    }

    protected function logDebug($msg)
    {
        return $this->log($msg, Propel::LOG_DEBUG);
    }
}