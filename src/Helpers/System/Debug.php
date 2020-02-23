<?php


namespace Palladiumlab\Helpers\System;


use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryUsageProcessor;

class Debug
{
    public static function getLogger()
    {
        return new Logger(
            'Debug',
            [
                new RotatingFileHandler(
                    bitrix_server()->getDocumentRoot() . '/logs/debug/debug.log',
                    10,
                    Logger::DEBUG
                ),
            ],
            [new MemoryUsageProcessor()]
        );
    }
}
