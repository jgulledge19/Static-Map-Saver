<?php

namespace Joshua19\StaticMapSaver\Helpers;

class SimpleCache
{
    protected $directory = __DIR__;

    /**
     * SimpleCache constructor.
     */
    public function __construct()
    {
        $this->directory = dirname(__DIR__).'/cache/';

        if (!file_exists(rtrim($this->directory, '/'))) {
            mkdir(rtrim($this->directory, '/'), '0777', true);
        }
    }

    /**
     * @param string $key
     * @return bool|mixed
     */
    public function get(string $key)
    {
        $path = $this->getFullKeyPath($key);
        $data = false;

        if (file_exists($path)) {
            $data = include $path;
        }

        return $data;
    }

    /**
     * @param string $key
     * @param array $data
     */
    public function set(string $key, $data=[])
    {
        $content = '<?php '.PHP_EOL.
            'return ' . var_export($data, true) . ';';

        file_put_contents($this->getFullKeyPath($key), $content);
    }

    /**
     * @param string $key
     */
    public function remove(string $key)
    {
        if (!empty($key)) {
            $path = $this->getFullKeyPath($key);
            if (file_exists($path)) {
                unlink($path);
            }

        }
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getFullKeyPath($key)
    {
        return rtrim($this->directory, '/') . DIRECTORY_SEPARATOR .
            preg_replace('/[^A-Za-z0-9\_\-]/', '', str_replace(['/', ' '], '_', $key)) .
            '.php';
    }

}