<?php

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Http\StatusCode;
use Slim\Views\PhpRenderer;
use SlimSession\Helper;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        'logger' => function (ContainerInterface $c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Logger($settings['name']);
            $logger->pushProcessor(new UidProcessor());
            $logger->pushHandler(new StreamHandler($settings['path'], $settings['level']));
            return $logger;
        },
        'renderer' => function (ContainerInterface $c) {
            $settings = $c->get('settings')['renderer'];
            return new PhpRenderer($settings['template_path']);
        },

        'dbh' => function (ContainerInterface $c) {
            $settings = $c->get('settings')['database'];

            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s', $settings['host'], $settings['port'], $settings['dbname']);
            $options = [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            ];
            $pdo = new PDO($dsn, $settings['username'], $settings['password'], $options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            return $pdo;
        },
        'session' => function ($c) {
            return new Helper;
        },
        'errorHandler' => function (ContainerInterface $c) {
            return function (ServerRequestInterface $request, Slim\Psr7\Response $response, Exception $exception) use ($c) {
                /** @var LoggerInterface $logger */
                $logger = $c['logger'];
                $logger->critical($exception->getMessage(), ['exception' => (string)$exception]);

                $error = [
                    'message' => 'Error',
                ];
                $error['exception'] = [];

                do {
                    $error['exception'][] = [
                        'type' => get_class($exception),
                        'code' => $exception->getCode(),
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => explode("\n", $exception->getTraceAsString()),
                    ];
                } while ($exception = $exception->getPrevious());

                return $response->withJson(
                    $error,
                    500,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                );
            };
        },

        'phpErrorHandler' => function (ContainerInterface $c) {
            return function (ServerRequestInterface $request, Slim\Http\Response $response, Throwable $exception) use ($c) {
                /** @var LoggerInterface $logger */
                $logger = $c['logger'];
                $logger->critical($exception->getMessage(), ['exception' => (string)$exception]);

                $error = [
                    'message' => 'Error',
                ];

                $error['exception'] = [];

                do {
                    $error['exception'][] = [
                        'type' => get_class($exception),
                        'code' => $exception->getCode(),
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => explode("\n", $exception->getTraceAsString()),
                    ];
                } while ($exception = $exception->getPrevious());


                return $response->withJson(
                    $error,
                    500,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                );
            };
        },
    ]);

    return $containerBuilder;
};
