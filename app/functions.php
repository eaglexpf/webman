<?php

if (!function_exists('envF')) {
    /**
     * @param $key
     * @param null $default
     * @return array|bool|false|mixed|string
     */
    function envF($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        if (($valueLength = strlen($value)) > 1 && $value[0] === '"' && $value[$valueLength - 1] === '"') {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (!function_exists('make')) {
    function make(string $name, array $parameters = []) {
        $container = \support\bootstrap\Container::instance();
        if (!$container->has($name)) {
            return \support\bootstrap\Container::make($name, $parameters);
        }
        return $container->get($name);
    }
}

if (!function_exists('event')) {
    function event(object $object) {
        make(\app\listener\EventDispatcher::class)->dispatch($object);
    }
}

if (!function_exists('logger')) {
    function logger($channel = 'default') {
        return \support\bootstrap\Log::channel($channel);
    }
}
