<?php

namespace EdLugz\Daraja\Logging;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Level;

class Log
{
    /**
     * All the available debug levels.
     *
     * @var array
     */
    protected static $levels = [
        'DEBUG' => Level::Debug,
        'INFO' => Level::Info,
        'NOTICE' => Level::Notice,
        'WARNING' => Level::Warning,
        'ERROR' => Level::Error,
        'CRITICAL' => Level::Critical,
        'ALERT' => Level::Alert,
        'EMERGENCY' => Level::Emergency,
    ];

    /**
     * Set up the logging requirements for the Guzzle package.
     *
     * @param $options
     * @return int
     * @throws \Exception
     */
    public static function enable($options)
    {
        $level = self::getLogLevel();

        $handler = new Logger(
            'Daraja',
            [
                new RotatingFileHandler(storage_path('logs/daraja.log'), 30, $level),
            ]

        );

        $stack = HandlerStack::create();
        $stack->push(
            Middleware::log(
                $handler,
                new MessageFormatter('{method} {uri} HTTP/{version} {req_body} RESPONSE: {code} - {res_body}')
            )
        );

        $options['handler'] = $stack;

        return $options;
    }

    /**
     * Determine the log level specified in the configurations.
     *
     * @return mixed
     * @throws \Exception
     */
    protected static function getLogLevel()
    {
        $level = strtoupper(config('daraja.logs.level'));

        if (array_key_exists($level, self::$levels)) {
            return self::$levels[$level];
        }

        throw new \Exception('Debug level not recognized');
    }
}