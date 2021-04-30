#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Autoloader;
use Workerman\Worker;
use Webman\Config;
use Dotenv\Dotenv;
use support\bootstrap\Container;

if (method_exists('Dotenv\Dotenv', 'createUnsafeImmutable')) {
    Dotenv::createUnsafeImmutable(base_path())->load();
} else {
    Dotenv::createMutable(base_path())->load();
}

Config::reload(config_path(), ['route', 'container']);
if ($timezone = config('app.default_timezone')) {
    date_default_timezone_set($timezone);
}
foreach (config('autoload.files', []) as $file) {
    include_once $file;
}
if (method_exists('Dotenv\Dotenv', 'createUnsafeMutable')) {
    Dotenv::createUnsafeMutable(base_path())->load();
} else {
    Dotenv::createMutable(base_path())->load();
}

Autoloader::setRootPath(base_path());
foreach (config('bootstrap', []) as $class_name) {
    /** @var \Webman\Bootstrap $class_name */
    $class_name::start(new Worker());
}

$container = Container::instance();

use Symfony\Component\Console\Application;

$application = $container->get(Application::class);

// ... register commands
$kernel = $container->get(\app\console\Kernel::class);
foreach ($kernel->commands as $command) {
    $application->add(make($command));
}

$application->run();