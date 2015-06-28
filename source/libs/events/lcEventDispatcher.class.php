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
 * @changed $Id: lcEventDispatcher.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
/*
 * This object MUST NOT IMPLEMENT ANY VIRTUAL methods
* and it must be AS PLAIN AS POSSIBLE
* for performance reasons!!!
*/

class lcEventDispatcher extends lcSysObj implements iDebuggable
{
    protected $observers;

    protected $listeners;
    protected $listener_events;

    protected $event_listeners;

    protected $object_listeners;
    protected $object_listeners_objects;

    protected $max_filter_requests = array(
        'response.send_response' => 1,
        'request.set_context' => 1,
        'request.filter_parameters' => 1,
    );

    protected $max_filter_requests_actual = array();

    protected $filter_processors = array();
    protected $notifications = array();

    protected $total_notifications_sent;

    public function __construct()
    {
        parent::__construct();

        $this->total_notifications_sent = 0;

        $this->filter_processors = array();
        $this->notifications = array();

        $this->listeners = array();
        $this->listener_events = array();
        $this->event_listeners = array();

        $this->object_listeners = array();
        $this->object_listeners_objects = array();
    }

    public function addObserver(iEventObserver $event_observer)
    {
        $this->observers[] = $event_observer;
    }

    public function removeObserver(iEventObserver $event_observer)
    {
        if (isset($this->observers[$event_observer])) {
            unset($this->observers[$event_observer]);
        }
    }

    public function removeObservers()
    {
        $this->observers = null;
    }

    public function shutdown()
    {
        $this->removeObservers();
        $this->disconnectAllListeners();

        parent::shutdown();
    }

    public function getDebugInfo()
    {
        $debug = array(
            'notifications_sent' => $this->total_notifications_sent,
            'filter_processors' => $this->filter_processors,
            'notifications' => $this->notifications
        );

        return $debug;
    }

    public function getShortDebugInfo()
    {
        $debug = array(
            'notifications_sent' => $this->total_notifications_sent,
        );

        return $debug;
    }

    public function getFilterProcessors()
    {
        return $this->filter_processors;
    }

    public function getTotalNotificationsSent()
    {
        return $this->total_notifications_sent;
    }

    public function connect($event_name, lcObj & $listener, $callback_func)
    {
        $listeners = $this->listeners;
        $listener_events = $this->listener_events;

        $listener_index = null;

        // check if not already connected
        $listener_index = array_search($listener, $listeners);

        if (is_bool($listener_index) && !$listener_index && isset($listener_events[$event_name])) {
            return true;
        }

        if (DO_DEBUG) {
            // search for a duplicate registration
            // in general these should be ommited as there is no sense of making a second
            // registration of the same event type / object - with a different callback
            $evs = isset($this->event_listeners[$event_name]) ? $this->event_listeners[$event_name] : null;

            if ($evs) {
                foreach ($evs as $d) {
                    $listener_ = $this->listeners[$d];

                    if ($listener_ === $listener) {
                        throw new lcSystemException('Duplicate event connection detected (' .
                            'event: ' . $event_name . ', object: ' . get_class($listener_) . ')');
                    }

                    unset($d);
                }
            }

            unset($evs);
        }

        $c = (isset($listener_index) && is_numeric($listener_index)) ? $listener_index : count($listeners);

        $listeners[$c] = $listener;
        $listener_events[$c][$event_name] = $callback_func;

        $this->listeners = $listeners;
        $this->listener_events = $listener_events;

        $this->event_listeners[$event_name][] = $c;

        return true;
    }

    public function disconnectAllListeners()
    {
        $this->listeners =
        $this->event_listeners =
        $this->listener_events = null;
    }

    public function disconnectListener(lcObj & $listener)
    {
        $listeners = $this->listeners;
        $listener_events = $this->listener_events;

        if (!$listeners) {
            return false;
        }

        $listener_index = array_search($listener, $listeners);

        // listener not here
        if (!$listener_index) {
            return false;
        }

        unset($listeners[$listener_index]);
        unset($listener_events[$listener_index]);

        // remove from event listeners
        $event_listeners = $this->event_listeners;

        if ($event_listeners) {
            foreach ($event_listeners as $event_name => $listeners1) {
                $k = array_search($listener_index, $listeners1);

                if ($k) {
                    unset($listeners1[$k]);
                }

                unset($event_name, $listeners1);
            }
        }

        $this->listeners = $listeners;
        $this->listener_events = $listener_events;
        $this->event_listeners = $event_listeners;

        return true;
    }

    public function disconnect($event_name, lcObj & $listener)
    {
        $listeners = $this->listeners;
        $listener_events = $this->listener_events;

        if (!$listeners) {
            return false;
        }

        $listener_index = array_search($listener, $listeners);

        // listener not here
        if (!$listener_index) {
            return false;
        }

        if (!isset($listener_events[$listener_index][$event_name])) {
            return false;
        }

        unset($listener_events[$listener_index][$event_name]);

        // disconnect from event listeners
        $event_listeners = $this->event_listeners;

        if ($event_listeners) {
            foreach ($event_listeners as $event_name1 => $listeners1) {
                if ($event_name1 != $event_name) {
                    continue;
                }

                $k = array_search($listener_index, $listeners1);

                if ($k) {
                    unset($listeners1[$k]);
                }

                unset($event_name, $listeners1);
            }
        }

        $this->listeners = $listeners;
        $this->listener_events = $listener_events;
        $this->event_listeners = $event_listeners;

        return true;
    }

    public function notify(lcEvent $event, lcObj $invoker = null)
    {
        // notify observers
        $observers = $this->observers;

        if ($observers) {
            foreach ($observers as $observer) {
                $observer->willSendNotification($this, $event, $invoker);
                unset($observer);
            }
        }

        unset($observers);

        $listeners = $this->listeners;
        $listener_events = $this->listener_events;

        if (!$listeners || !$listener_events) {
            return false;
        }

        foreach ($listeners as $key => $listener) {
            $events = $listener_events[$key];

            if (!$events) {
                continue;
            }

            foreach ($events as $event_name => $callback_func) {
                if ($event_name != $event->event_name) {
                    continue;
                }

                $listener->$callback_func($event);

                if (DO_DEBUG) {
                    $this->notifications[] = array('event_name' => $event_name, 'subject' => get_class($event->subject));
                }

                $this->total_notifications_sent++;

                unset($event_name, $callback_func);
            }

            unset($key, $listener);
        }

        return $event;
    }

    public function filter(lcEvent $event, $value, lcObj $invoker = null)
    {
        // notify observers
        $observers = $this->observers;

        if ($observers) {
            foreach ($observers as $observer) {
                $observer->willFilterValue($this, $event, $value, $invoker);
                unset($observer);
            }
        }

        unset($observers);

        $event_listeners = $this->event_listeners;
        $listeners = isset($event_listeners[$event->event_name]) ? $event_listeners[$event->event_name] : null;
        $listeners_objects = $this->listeners;
        $listener_events = $this->listener_events;

        // initialize the return value with the input value
        $event->return_value = $value;

        if (!$listeners_objects || !$listeners || !$listener_events || !$event_listeners) {
            return $event;
        }

        $new_value = $value;

        // check if the filter event has been processed
        // more than the allowed
        if (DO_DEBUG) {
            $event_name = $event->event_name;
            $max = isset($this->max_filter_requests[$event_name]) ? (int)$this->max_filter_requests[$event_name] : null;
            $subject_name = get_class($event->subject);

            $this->filter_processors[] = array('event_name' => $event_name, 'subject' => $subject_name);

            if ($max) {
                if (!isset($this->max_filter_requests_actual[$event_name])) {
                    $this->max_filter_requests_actual[$event_name] = 1;
                }

                $actual = (int)$this->max_filter_requests_actual[$event_name];

                if ($actual > $max) {
                    $filters = $this->filter_processors;
                    $filters_out = '';

                    if (isset($filters) && is_array($filters)) {
                        foreach ($filters as $filter) {
                            $filters_out .= $filter['subject'] . ' => ' . $filter['event_name'] . "\n";

                            unset($filter);
                        }
                    }

                    throw new lcLogicException('Event dispatcher tried to send filter event: \'' . $event_name . '\' more than the allowed times (max: ' . $max . '). Here are all filter processors so far: ' . "\n\n" .
                        $filters_out
                    );
                }

                $actual += 1;

                $this->max_filter_requests_actual[$event_name] = $actual;
            }
        }

        foreach ($listener_events as $listener_index => $info) {
            $callback_func = isset($info[$event->event_name]) ? $info[$event->event_name] : null;

            if (!$callback_func) {
                continue;
            }

            $found_listener = $listeners_objects[$listener_index];

            $event->processed = false;
            $value_out = $found_listener->$callback_func($event, $new_value);

            if ($event->isProcessed() || $new_value !== $value) {
                $event->actual_processing_iterations++;

                $event->filtered_by = &$found_listener;
                $event->processed = true;
                $new_value = $value_out;
            }

            $this->total_notifications_sent++;

            if ($event->max_processing_iterations && $event->actual_processing_iterations >= $event->max_processing_iterations) {
                break;
            }

            /*if ($event->processed)
             {
            echo $event->event_name . ' - ' . get_class($found_listener) . '<br /><br />' . $value . '<br /><br /><hr /><br /><br />';
            }*/

            unset($listener_index, $info);
        }

        $event->return_value = $new_value;

        unset($listeners);

        return $event;
    }

    public function hasListeners($event_name)
    {
        return isset($this->event_listeners[$event_name]);
    }

    public function getConnectedListeners()
    {
        $event_listeners = $this->event_listeners;

        if (!$event_listeners) {
            return null;
        }

        $ret = array();

        foreach ($event_listeners as $event_type => $data) {
            $listeners = array();

            foreach ($data as $idx) {
                $listeners[] = array(
                    'listener' => get_class($this->listeners[$idx]),
                    'callback' => $this->listener_events[$idx][$event_type]);
            }

            $ret[$event_type] = $listeners;
        }

        return $ret;
    }

    public function getSentNotifications()
    {
        return $this->notifications;
    }

    public function registerProvider($object_name, lcObj &$listener, $callback_func)
    {
        $listener_name = get_class($listener);

        $this->object_listeners[$object_name][] = array($listener_name, $callback_func);
        $this->object_listeners_objects[$listener_name] = &$listener;
    }

    public function provide($object_name, lcObj &$listener, array $params = null)
    {
        // check in objects and classes
        if (isset($this->object_listeners[$object_name])) {
            $listeners = $this->object_listeners;
            $listener_name = null;

            foreach ($listeners as $object_name1 => $listeners1) {
                if ($object_name1 == $object_name) {
                    // if no specific provider has been set - use the first one
                    if (!isset($listener_name)) {
                        // return the first found object
                        $listener_name = $listeners1[0][0];
                        $callback_func = $listeners1[0][1];

                        $callee = $this->object_listeners_objects[$listener_name];
                        $event = new lcEvent($object_name, $listener, $params);

                        try {
                            $return_value = $callee->$callback_func($event);
                            $event->return_value = $return_value;
                        } catch (Exception $e) {
                            $message = 'Could not provide object: ' . $object_name . ': ' . $e;
                            $this->err($message);

                            throw $e;
                        }

                        return $event;
                    }
                }

                unset($object_name1, $listeners);
            }
        }

        // if not found we create a null event which accepts any calls
        // so callers would not raise fatal errors due to calling lcEvent methods
        $event = new lcEvent($object_name);

        return $event;
    }
}

?>