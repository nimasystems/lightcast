<?php

use LightnCandy\LightnCandy;

/**
 * Class lcHandlebarsView
 * https://github.com/zordius/lightncandy
 */
class lcHandlebarsView extends lcHTMLView implements ArrayAccess
{
    /**
     * @var string
     */
    protected $template_ext = '.handlebars';

    /**
     * @var string
     */
    protected $template_filename;

    /**
     * @var int
     */
    protected $handlebar_flags = LightnCandy::FLAG_HANDLEBARS |
    LightnCandy::FLAG_ERROR_LOG |
    LightnCandy::FLAG_ERROR_EXCEPTION;

    /**
     * @var array
     */
    protected $handlebar_helpers;

    /**
     * @var
     */
    protected $template_hash;

    /**
     * @var Callable
     */
    protected $partial_resolver;

    /**
     * @var bool
     */
    protected $use_cache;

    /**
     * @var array
     */
    protected $data;

    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    public function __set($name, $value = null)
    {
        return $this->data[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function initialize()
    {
        parent::initialize();

        $this->setDefaults();
    }

    protected function setDefaults()
    {
        $this->handlebar_helpers = self::getDefaultHelpers($this);
        $this->partial_resolver = self::getDefaultPartialResolver($this);
    }

    protected static function getDefaultPartialResolver(lcHandlebarsView $ctx)
    {
        return function ($cx, $name) use ($ctx) {
            $bd = dirname($ctx->template_filename);
            $fn = $bd . DS . $name . '.handlebars';

            if (file_exists($fn)) {
                return file_get_contents($fn);
            }
            return "[partial (file:$name.handlebars) not found]";
        };
    }

    protected static function getDefaultHelpers(lcHandlebarsView $ctx)
    {
        return [
            'ifEqual' => function ($data, $template_var, $ctx) {
                if ($data === $template_var) {
                    return $ctx['fn']();
                } else {
                    return $ctx['inverse']();
                }
            },
            'generateUrl' => function ($route_name, $params, $ctx) {
                if ($route_name) {
                    $app = lcApp::getInstance();

                    /** @var lcPatternRouting $router */
                    $router = $app->getRouter();
                    $np = [];

                    if ($params) {
                        $pp = [];
                        $_this = $ctx['_this'];

                        parse_str($params, $pp);

                        if ($pp) {
                            foreach ($pp as $k => $v) {
                                if (strpos($v, ':') !== false) {
                                    $kd = explode(':', $v);

                                    if (count($kd) >= 2) {
                                        $modifier = $kd[1];
                                        $modified_val = $kd[0];

                                        if ($modifier == 'urlkey') {
                                            $modified_val = isset($_this[$kd[0]]) ? $_this[$kd[0]] : null;
                                            $modified_val = lcStrings::url_slug($modified_val);
                                        }

                                        $np[$k] = $modified_val;
                                    }
                                } else {
                                    $np[$k] = isset($_this[$v]) ? $_this[$v] : null;
                                }

                                unset($k, $v);
                            }
                        }

                        $np = array_filter($np);
                    }

                    return $router->generate($np, false, $route_name, false);
                }
            },
            'uri' => function ($url, $options, $ctx) {
                return $url;
            },
        ];
    }

    /**
     * @return string
     */
    public function getTemplateFilename()
    {
        return $this->template_filename;
    }

    /**
     * @param string $template_filename
     * @return lcHandlebarsView
     */
    public function setTemplateFilename($template_filename)
    {
        $this->template_filename = $template_filename;
        $this->template_hash = $this->getTemplateHash($template_filename);
        return $this;
    }

    /**
     * @param bool $use_cache
     * @return lcHandlebarsView
     */
    public function setUseCache($use_cache)
    {
        $this->use_cache = $use_cache;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUseCache()
    {
        return $this->use_cache;
    }

    /**
     * @param array $data
     * @return lcHandlebarsView
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function set($name, $value = null)
    {
        if (!$value) {
            unset($this->data[$value]);
        } else {
            $this->data[$name] = $value;
        }
    }

    public function get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * @param int $handlebar_flags
     * @return lcHandlebarsView
     */
    public function setHandlebarFlags($handlebar_flags)
    {
        $this->handlebar_flags = $handlebar_flags;
        return $this;
    }

    /**
     * @return int
     */
    public function getHandlebarFlags()
    {
        return $this->handlebar_flags;
    }

    /**
     * @param array $handlebar_helpers
     * @return lcHandlebarsView
     */
    public function setHandlebarHelpers($handlebar_helpers)
    {
        $this->handlebar_helpers = $handlebar_helpers;
        return $this;
    }

    /**
     * @return array
     */
    public function getHandlebarHelpers()
    {
        return $this->handlebar_helpers;
    }

    protected function renderHandlebars()
    {
        $template_data = null;
        $ret = null;

        $cache = $this->getController()->getCache();

        if ($this->use_cache) {
            $template_data = $cache->get($this->template_hash);
        }

        if (!$template_data) {
            $template = @file_get_contents($this->template_filename);

            if ($template) {
                $template_data = LightnCandy::compile($template, $this->getHandlebarCompileOptions());

                if ($template_data && $this->use_cache && $cache) {
                    $cache->set($this->template_hash, $template_data);
                }
            }
        }

        if ($template_data) {
            $renderer = $this->getHandlebarRenderer($template_data);

            if ($renderer) {
                /** @var Closure $renderer */
                $ret = $renderer($this->data);
            }
        }

        return $ret;
    }

    /**
     * @param string $phpcode
     * @return false|Closure
     */
    protected function getHandlebarRenderer($phpcode)
    {
        return LightnCandy::prepare($phpcode);
    }

    protected function getHandlebarCompileOptions()
    {
        return [
            'flags' => $this->handlebar_flags,
            'helpers' => $this->handlebar_helpers,
            'partialresolver' => $this->partial_resolver,
        ];
    }

    protected function getViewContent()
    {
        $controller = $this->getController();

        if (!$controller) {
            throw new lcNotAvailableException($this->t('Controller not set'));
        }

        $action_name = $controller->getActionName();
        $template_filename = $controller->getControllerDirectory() . DS . 'templates' . DS . $action_name . $this->template_ext;
        $this->setTemplateFilename($template_filename);

        return $this->renderHandlebars();
    }

    protected function getTemplateHash($filename)
    {
        return 'handlebars_ctrl_view_' . sha1($filename);
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
