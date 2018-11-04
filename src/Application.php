<?php

namespace Joshua19\StaticMapSaver;

use Joshua19\StaticMapSaver\Command\AirTable;
use Joshua19\StaticMapSaver\Command\StaticMapSaver;

class Application extends \Symfony\Component\Console\Application
{
    protected static $name = 'Static Map Saver';

    protected static $version = '0.1';

    public function __construct()
    {
        parent::__construct(static::$name, static::$version);
    }

    /**
     *
     */
    public function loadCommands()
    {
        $this->add((new AirTable()));
        $this->add((new StaticMapSaver()));
    }

    /**
     * @return bool|string
     */
    protected function getArt()
    {
        if (file_exists(static::$logo)) {
            return file_get_contents(static::$logo);
        }

        return '';
    }
}