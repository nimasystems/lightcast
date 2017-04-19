<?php

use LightnCandy\LightnCandy;

/**
 * Class lcHandlebarsView
 * https://github.com/zordius/lightncandy
 */
class lcHandlebarsView extends lcHTMLView
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
    protected $handlebar_flags = LightnCandy::FLAG_ERROR_LOG | LightnCandy::FLAG_STANDALONEPHP;

    /**
     * @var array
     */
    protected $handlebar_helpers;

    /**
     * @var
     */
    protected $template_hash;

    /**
     * @var bool
     */
    protected $use_cache;

    /**
     * @var array
     */
    protected $data;

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
        return array(
            'flags' => $this->handlebar_flags,
            'helpers' => $this->handlebar_helpers
        );
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
}
