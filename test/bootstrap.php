<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2026 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Minimal stream wrapper to test protocol-based access to templates.
 */
class TestStream
{
    public $context;
    private $filehandle;

    /**
     * Always returns false.
     *
     * @param string $path
     * @param int    $flags
     *
     * @return array
     */
    public function url_stat($path, $flags)
    {
        return false;
    }

    /**
     * Open the file.
     *
     * @param string $path
     * @param string $mode
     *
     * @return bool
     */
    public function stream_open($path, $mode)
    {
        $path = preg_replace('-^test://-', '', $path);
        $this->filehandle = fopen($path, $mode);

        return $this->filehandle !== false;
    }

    /**
     * @return array
     */
    public function stream_stat()
    {
        return [];
    }

    /**
     * @param int $count
     *
     * @return string
     */
    public function stream_read($count)
    {
        return fgets($this->filehandle, $count);
    }

    /**
     * @return bool
     */
    public function stream_eof()
    {
        return feof($this->filehandle);
    }

    /**
     * @return bool
     */
    public function stream_close()
    {
        return fclose($this->filehandle);
    }
}

if (!stream_wrapper_register('test', 'TestStream')) {
    exit('Failed to register protocol');
}
