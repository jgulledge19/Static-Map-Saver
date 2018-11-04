<?php
ini_set('display_errors', 1);

use Joshua19\StaticMapSaver\Application;

$bootstrap_possible_paths = [
    // if cloned from git:
    dirname(__DIR__).'/src/bootstrap.php',
    // if installed via composer:
    dirname(dirname(dirname(__DIR__))).'/joshua19/static-map-saver/src/bootstrap.php',
];
foreach ($bootstrap_possible_paths as $bootstrap_path) {
    if (file_exists($bootstrap_path)) {
        require_once $bootstrap_path;
        break;
    }
}

/** @var Application $application */
$application = new Application();
$application->loadCommands();
$application->run();