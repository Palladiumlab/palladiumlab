<?php


namespace Palladiumlab\Traits;


use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

trait LoggerTrait
{
    use \Psr\Log\LoggerTrait;

    protected $notificationsLevels = [
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::DEBUG,
        LogLevel::ALERT,
    ];

    /** @var LoggerInterface */
    protected $logger;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    public function log($level, $message, array $context = array())
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}