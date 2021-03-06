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

class lcDataStorageItem extends lcObj
{
    const DEFAULT_CHUNK_SIZE = 8192;
    protected $attributes = [];
    protected $data_location_attribute_name;
    /** @var iDataStorageItemListener */
    protected $delegate;
    private $is_receiving;
    private $total_bytes_read = 0;
    private $errors = [];
    private $fpointer;

    // factory

    public function __construct($attributes = [], $data_location_attribute_name = null)
    {
        parent::__construct();

        $this->data_location_attribute_name = $data_location_attribute_name;
        $this->attributes = is_array($attributes) ? $attributes : [];
    }

    public static function itemWithAttributes($attributes = [], $data_location_attribute_name = null)
    {
        return new lcDataStorageItem($attributes, $data_location_attribute_name);
    }

    public function __destruct()
    {
        if ($this->fpointer) {
            @fclose($this->fpointer);
        }

        $this->delegate = null;

        parent::__destruct();
    }

    public function __get($param)
    {
        return isset($this->attributes[$param]) ? $this->attributes[$param] : null;
    }

    public function __set($param, $value = null)
    {
        $this->attributes[$param] = $value;
    }

    public function getAttributes()
    {
        return (array)$this->attributes;
    }

    public function __toString()
    {
        return print_r($this->attributes, true);
    }

    public function passThroughData()
    {
        if (!$this->getDataPointer()) {
            return;
        }

        fpassthru($this->fpointer);

        $this->cleanup();
    }

    public function &getDataPointer($mode = 'rb')
    {
        if ($this->fpointer) {
            return $this->fpointer;
        }

        if (!$location = $this->getDataLocation()) {
            return $this->fpointer;
        }

        $this->fpointer = @fopen($location, $mode);

        return $this->fpointer;
    }

    public function getDataLocation()
    {
        if (!$this->data_location_attribute_name) {
            return null;
        }

        return isset($this->attributes[$this->data_location_attribute_name]) ?
            $this->attributes[$this->data_location_attribute_name] : null;
    }

    private function cleanup()
    {
        $this->is_receiving = false;
        @fclose($this->fpointer);
        $this->fpointer = null;
        $this->total_bytes_read = 0;
    }

    public function getFullData()
    {
        if (!$location = $this->getDataLocation()) {
            return null;
        }

        return @file_get_contents($location);
    }

    public function getDelegate()
    {
        return $this->delegate;
    }

    public function setDelegate(iDataStorageItemListener $delegate)
    {
        $this->delegate = $delegate;
    }

    public function isReceivingData()
    {
        return $this->is_receiving;
    }

    public function startReceivingData($chunk_size = self::DEFAULT_CHUNK_SIZE)
    {
        if (!$this->delegate) {
            return;
        }

        // try to open the pointer
        $pointer = $this->getDataPointer();

        if (!$pointer) {
            return;
        }

        $this->is_receiving = true;
        $this->errors = [];

        // inform the delegate
        $this->delegate->onBeginReceivingData($this);

        $this->total_bytes_read = 0;

        // iterate over the contents
        try {
            while (!feof($this->fpointer)) {
                $contents = fread($this->fpointer, $chunk_size);
                $this->total_bytes_read += strlen($contents);

                // inform the delegate
                $this->delegate->onReceiveData($this, $contents);

                unset($contents);
            }
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();

            // cleanup, inform the delegate
            $this->delegate->onEndReceivingData($this, $this->total_bytes_read, true, $this->errors);

            // cleanup
            $this->cleanup();

            return;
        }

        // close the socket, inform the delegate
        $this->stopReceivingData();
    }

    public function stopReceivingData()
    {
        if (!$this->is_receiving) {
            return false;
        }

        $this->delegate->onEndReceivingData($this, $this->total_bytes_read, count($this->errors) ? true : false, $this->errors);

        $this->cleanup();

        return true;
    }

    public function getLastErrors()
    {
        return $this->errors;
    }
}