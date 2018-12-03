<?php

class lcValidatorFailure implements Serializable, JsonSerializable
{
    protected $name;
    protected $validator;
    protected $message;
    protected $extra_data;

    public function __construct($name, $failure_message, lcValidator $validator = null, array $extra_data = null)
    {
        $this->name = $name;
        $this->message = $failure_message;
        $this->validator = $validator;
        $this->extra_data = $extra_data;
    }

    public function getExtraData()
    {
        return $this->extra_data;
    }

    public function setExtraData(array $data = null)
    {
        $this->extra_data = $data;
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMessage()
    {
        return $this->message;
    }

    #pragma mark - Serializable

    public function serialize()
    {
        return serialize([
            'name' => $this->name,
            'message' => $this->message,
            'extra_data' => $this->extra_data
        ]);
    }

    public function unserialize($serialized)
    {
        $serialized = unserialize($serialized);
        $this->message = isset($serialized['message']) ? $serialized['message'] : null;
        $this->name = isset($serialized['name']) ? $serialized['name'] : null;
        $this->extra_data = isset($serialized['extra_data']) ? $serialized['extra_data'] : null;
    }

    #pragma mark - JsonSerializable

    public function jsonSerialize()
    {
        return json_encode([
            'name' => $this->name,
            'message' => $this->message,
            'extra_data' => $this->extra_data
        ]);
    }
}
