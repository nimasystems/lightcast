<?php

class lcAutoloadCacheTool extends lcObj
{
    const NOINDEX_SUFFIX = '.no_index';

    private $class_dirs;
    private $class_file_endings;
    private $follow_symlinks;
    private $ignore_hidden_files;

    private $write_base_path;

    private $found_classes;
    private $cache_filename;
    private $cache_var_name;
    private $cache_version_var_name;
    private $cache_version;

    public function __construct(array $class_dirs, $cache_filename, $class_cache_var_name, $class_cache_version_var_name)
    {
        parent::__construct();

        if (!$class_dirs || !$cache_filename || !$class_cache_var_name || !$class_cache_version_var_name) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $this->write_base_path = true;
        $this->class_dirs = $class_dirs;
        $this->cache_filename = $cache_filename;
        $this->cache_var_name = $class_cache_var_name;
        $this->cache_version_var_name = $class_cache_version_var_name;
        $this->class_file_endings = ['.php'];
        $this->follow_symlinks = false;
        $this->ignore_hidden_files = true;
    }

    public function getWriteBasePath()
    {
        return $this->write_base_path;
    }

    public function setWriteBasePath($write_base_path = true)
    {
        $this->write_base_path = $write_base_path;
    }

    public function getClassDirs()
    {
        return $this->class_dirs;
    }

    public function getClassFileEndings()
    {
        return $this->class_file_endings;
    }

    public function setClassFileEndings(array $class_file_endings)
    {
        $this->class_file_endings = $class_file_endings;
    }

    public function getCacheVersionVarName()
    {
        return $this->getCacheVarName();
    }

    public function getCacheVarName()
    {
        return $this->cache_var_name;
    }

    public function getFollowSymlinks()
    {
        return $this->follow_symlinks;
    }

    public function setFollowSymlinks($follow_symlinks = false)
    {
        $this->follow_symlinks = $follow_symlinks;
    }

    public function getIgnoreHiddenFiles()
    {
        return $this->ignore_hidden_files;
    }

    public function setIgnoreHiddenFiles($ignore_hidden_files = true)
    {
        $this->ignore_hidden_files = $ignore_hidden_files;
    }

    public function getCacheFilename()
    {
        return $this->cache_filename;
    }

    public function setCacheFilename($cache_filename)
    {
        $this->cache_filename = $cache_filename;
    }

    public function getCacheVersion()
    {
        return $this->cache_version;
    }

    public function setCacheVersion($version)
    {
        $this->cache_version = $version;
    }

    public function getFoundClasses()
    {
        return $this->found_classes;
    }

    public function createCache()
    {
        // make some basic checks
        if (!$this->cache_filename || !$this->class_dirs) {
            throw new lcSystemException('Cache filename missing / Class dirs not set');
        }

        // parse the dirs
        $this->found_classes = $this->parse();

        // create and store the cache
        $this->storeCache();
    }

    public function parse()
    {
        $class_dirs = $this->class_dirs;

        assert($class_dirs);

        $found_classes_all = [];

        foreach ($class_dirs as $dir) {
            $found_classes = [];

            try {
                $this->parseDir($dir, $dir, $found_classes);
            } catch (Exception $e) {
                assert(false);
                continue;
            }

            if (is_array($found_classes)) {
                $found_classes_all[$dir] = $found_classes;
            }

            unset($dir, $found_classes);
        }

        if (!count($found_classes_all)) {
            $found_classes_all = null;
        }

        return $found_classes_all;
    }

    protected function parseDir($initial_directory_path, $directory_path, array & $found_classes)
    {
        assert(isset($directory_path));
        $directory_path = (string)$directory_path;

        if ($directory_path{strlen($directory_path) - 1} != DS) {
            $directory_path .= DS;
        }

        if (is_dir($directory_path)) {
            if ($dh = opendir($directory_path)) {
                while (($file = readdir($dh)) !== false) {
                    $file_path = $directory_path . $file;

                    if (!$this->ignore_hidden_files || $file{0} != '.') {
                        switch (filetype($file_path)) {
                            case 'dir':

                                if ($file != "." && $file != "..") {
                                    if (file_exists($file_path . DS . self::NOINDEX_SUFFIX)) {
                                        //then we should not index
                                        continue;
                                    }

                                    /* parse on recursively */
                                    $this->parseDir($initial_directory_path, $file_path, $found_classes);
                                }

                                break;
                            case 'link':

                                if ($this->follow_symlinks) {
                                    /* follow link, parse on recursively */
                                    $this->parseDir($initial_directory_path, $file_path, $found_classes);
                                }

                                break;
                            case 'file':
                                /* a non-empty endings array implies an ending check
                                 * TODO: Write a more sophisticated suffix check. */
                                if (!sizeof($this->class_file_endings) || in_array(substr($file, strrpos($file, '.')), $this->class_file_endings)) {
                                    $size = filesize($file_path);

                                    if ($size && $php_file = fopen($file_path, "r")) {
                                        if ($buf = fread($php_file, $size)) {
                                            $result = [];

                                            if (preg_match_all('%(interface|class)\s+(\w+)\s+(extends\s+(\w+)\s+)?(implements\s+\w+\s*(,\s*\w+\s*)*)?{%', $buf, $result)) {
                                                foreach ($result[2] as $class_name) {
                                                    $file_path_clean = str_replace($initial_directory_path, '', $file_path);

                                                    $found_classes[$class_name] = $file_path_clean;

                                                    unset($class_name, $file_path_clean);
                                                }
                                            }

                                            unset($result);
                                        }

                                        unset($buf);
                                    }

                                    unset($size);
                                }

                                break;
                        }
                    }

                    unset($file_path);
                    unset($file);
                }

                return true;
            }
        }

        return false;
    }

    protected function storeCache()
    {
        $full_path = $this->cache_filename;
        $d = dirname($full_path);

        // try to create the cache dir
        lcDirs::mkdirRecursive($d);

        // check if writable
        if (!is_writable($d)) {
            throw new lcIOException('Directory is not writable: ' . $d);
        }

        // prepare the new format
        $found_classes = $this->found_classes;
        $class_array_data = [];

        if ($found_classes && is_array($found_classes)) {
            foreach ($found_classes as $path => $classes) {
                foreach ($classes as $class_name => $filename) {
                    $filename = ($filename{0} == '/') ? substr($filename, 1, strlen($filename)) : $filename;
                    $class_array_data[] = '\'' . $class_name . '\' => \'' . (($this->write_base_path ? $path . DS : null) . $filename) . '\'';
                    unset($class_name, $filename);
                }
                unset($path, $classes);
            }
        }

        $class_array_data = implode(",", $class_array_data);

        // prepare and write the data
        $ver = LC_VER;
        $generate_time = date('Y-m-d H:i:s');
        $cache_var_name = $this->cache_var_name;
        $cache_version = (int)$this->cache_version;
        $cache_version_suffix = $this->cache_version_var_name;

        $php_data = <<<EOT
<?php
// Lightcast $ver autoload class cache
// Generated at: $generate_time
\${$cache_version_suffix} = $cache_version;
\${$cache_var_name} = array($class_array_data);
EOT;
        lcFiles::putFile($full_path, $php_data);
    }
}