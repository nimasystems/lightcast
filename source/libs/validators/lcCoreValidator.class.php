<?php

abstract class lcCoreValidator extends lcObj implements iI18nProvider
{
    /** @var lcI18n */
    protected $i18n;

    protected $t_context_type;
    protected $t_context_name;

    protected $options;
    protected $default_error_message;

    private $initialized;

    abstract public function getDefaultOptions();

    abstract protected function validateOptions();

    abstract protected function skipNullValues();

    abstract protected function doValidate($value = null);

    public static function validateValue($validator_name, $value, array $options = null, lcCoreValidator &$validator = null)
    {
        $validator = self::getValidator($validator_name, false);
        return $validator ? $validator->setOptions($options)->validate($value) : false;
    }

    /**
     * @param $validator_name
     * @param bool $throws
     * @return lcCoreValidator
     * @throws lcNotAvailableException
     * @throws lcSystemException
     */
    public static function getValidator($validator_name, $throws = true)
    {
        if (!$validator_name) {
            return null;
        }

        $class = 'lc' . lcInflector::camelize($validator_name, false) . 'CoreValidator';

        if (!class_exists($class)) {
            if ($throws) {
                throw new lcNotAvailableException(sprintf('Validator %s not available', $validator_name));
            } else {
                return null;
            }
        }

        $validator = new $class();

        // check it
        if (!($validator instanceof lcCoreValidator)) {
            if ($throws) {
                throw new lcSystemException(sprintf('Validator %s is not valid', $validator_name));
            } else {
                return null;
            }
        }

        return $validator;
    }

    public static function validateAlphaNumericValue($string, $allowWhiteSpace = false)
    {
        return self::validateValue('string', $string, array('alpha_numeric' => true, 'allow_whitespace' => $allowWhiteSpace));
    }

    public static function validateAlpha($string)
    {
        return self::validateValue('string', $string, array('alpha_numeric' => true));
    }

    public static function validateCleanNumeric($string)
    {
        return preg_replace('/[a-zA-Z \+\\\\\/]*/', '', $string);
    }


    public static function valideteCleanAlphaNumeric($string)
    {
        return (bool)preg_replace('/[a-zA-Z0-9\+\\\\\/]*/', '', $string);
    }

    public function initialize()
    {
        // for subclassers
        $this->initialized = true;
        return $this;
    }

    public function setI18n(lcI18n $i18n)
    {
        $this->i18n = $i18n;
    }

    public function setOptions(array $options = null)
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getValue()
    {
        return (isset($this->options['value']) ? $this->options['value'] : null);
    }

    public function setDefaultErrorMessage($error_message)
    {
        $this->default_error_message = $error_message;
        return $this;
    }

    public function getDefaultErrorMessage()
    {
        return $this->default_error_message;
    }

    public function getIsNegative()
    {
        return (isset($this->options['negative']) ? (bool)$this->options['negative'] : false);
    }

    public function validate($value = null)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if (!$value && $this->skipNullValues()) {
            return true;
        }

        // set default options if none available
        $this->options = $this->options ?: $this->getDefaultOptions();

        // verify options
        $validated_options = $this->validateOptions();

        if (!$validated_options) {
            throw new lcConfigException($this->translate('Incorrectly configured validator options'));
        }

        // validate
        $validated = $this->doValidate($value);

        // negate if necessary
        $validated = $this->getIsNegative() ? !$validated : $validated;

        return $validated;
    }

    #pragma mark - iI18nProvider interface

    public function translate($string)
    {
        return $this->translateInContext($this->t_context_type, $this->t_context_name, $string);
    }

    public function translateInContext($context_type, $context_name, $string, $translation_domain = null)
    {
        return ($this->i18n ? $this->i18n->translateInContext($context_type, $context_name, $string, $translation_domain) : $string);
    }

    public function setTranslationContext($context_type, $context_name = null)
    {
        $this->t_context_type = $context_type;
        $this->t_context_name = $context_name;
    }

    public function getTranslationContextType()
    {
        return $this->t_context_type;
    }

    public function getTranslationContextName()
    {
        return $this->t_context_name;
    }
}
