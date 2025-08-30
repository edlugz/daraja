<?php

declare(strict_types=1);

namespace EdLugz\Daraja\Logging;

use Exception;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

class Log
{
    /**
     * All the available debug levels.
     *
     * @var array
     */
    protected static array $levels = [
        'DEBUG'     => Level::Debug,
        'INFO'      => Level::Info,
        'NOTICE'    => Level::Notice,
        'WARNING'   => Level::Warning,
        'ERROR'     => Level::Error,
        'CRITICAL'  => Level::Critical,
        'ALERT'     => Level::Alert,
        'EMERGENCY' => Level::Emergency,
    ];

    /**
     * Set up the logging requirements for the Guzzle package.
     *
     * @param $options
     *
     * @throws Exception
     *
     * @return array
     */
    public static function enable($options) : array
    {
        if (!config('daraja.logs.enabled', true)) {
            return is_array($options) ? $options : [];
        }

        $level = self::getLogLevel();

        $dir = storage_path('logs/daraja');

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handler = new Logger('Daraja', [
            new RotatingFileHandler($dir . '/daraja.log', 30, $level),
        ]);

        $stack = $options['handler'] ?? null;

        if (! $stack instanceof HandlerStack) {
            $stack = is_callable($stack)
                ? HandlerStack::create($stack)   // user-provided handler callable
                : HandlerStack::create();        // default handler
        }

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
     * @return Level
     *
     */
    protected static function getLogLevel(): Level
    {
        $raw = (string) config('daraja.logs.level', 'DEBUG');

        $level = strtoupper($raw);

        return self::$levels[$level] ?? Level::Debug;
    }
}
