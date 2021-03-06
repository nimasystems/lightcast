<?php

class PidSingleton
{

    public $already_running;
    protected $filename;

    public function __construct($directory)
    {
        $this->filename = $directory . DS . basename($_SERVER['PHP_SELF']) . '.pid';

        if (is_writable($this->filename) || is_writable($directory)) {
            if (file_exists($this->filename)) {
                $pid = (int)trim(file_get_contents($this->filename));

                /** @noinspection PhpComposerExtensionStubsInspection */
                if (posix_kill($pid, 0)) {
                    $this->already_running = true;
                }
            }
        } else {
            die("Cannot write to pid file '$this->filename'. Program execution halted.\n");
        }

        if (!$this->already_running) {
            $pid = getmypid();
            file_put_contents($this->filename, $pid);
        }
    }

    public function __destruct()
    {
        if (!$this->already_running && file_exists($this->filename) && is_writeable($this->filename)) {
            unlink($this->filename);
        }
    }

    public function isAlreadyRunning()
    {
        return $this->already_running;
    }

    public function getPid()
    {
        $pid = false;

        if (file_exists($this->filename)) {
            $pid = (int)trim(file_get_contents($this->filename));
        }

        return $pid;
    }
}