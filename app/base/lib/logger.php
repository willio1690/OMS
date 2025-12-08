<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\UidProcessor;

class base_logger
{
    /** @var Logger $logger 日志对象 */
    private $logger = null;

    /**
     * 构造器
     * 
     * @param string $name 通道名称
     * @return void
     * */
    public function __construct($name = 'default')
    {
        // Create the logger
        $this->logger = new Logger($name);

        if (defined('MONOLOG_OPTIONS') && is_array(MONOLOG_OPTIONS) && isset(MONOLOG_OPTIONS[$name])) {
            foreach ((array) MONOLOG_OPTIONS[$name]['handlers'] as $handler => $args) {
                $handler = new $handler(...$args);

                $this->logger->pushHandler($handler);
            }
        }

        $this->logger->pushProcessor(new WebProcessor());
        $this->logger->pushProcessor(new UidProcessor());
    }

    /**
     * Adds a log record at the INFO level.
     * 
     * This method allows for compatibility with common interfaces.
     * 
     * @param  string $message The log message
     * @param  array  $context The log context
     * @return bool   Whether the record has been processed
     */
    public function info(string $message, array $context = [])
    {
        return $this->logger->info($message, $context);
    }

    /**
     * Adds a log record at the DEBUG level.
     * 
     * This method allows for compatibility with common interfaces.
     * 
     * @param  string $message The log message
     * @param  array  $context The log context
     * @return bool   Whether the record has been processed
     */
    public function debug($message, array $context = [])
    {
        return $this->logger->debug($message, $context);
    }

    /**
     * Adds a log record at the NOTICE level.
     * 
     * This method allows for compatibility with common interfaces.
     * 
     * @param  string $message The log message
     * @param  array  $context The log context
     * @return bool   Whether the record has been processed
     */
    public function notice($message, array $context = [])
    {
        return $this->logger->notice($message, $context);
    }

    /**
     * Adds a log record at the WARNING level.
     * 
     * This method allows for compatibility with common interfaces.
     * 
     * @param  string $message The log message
     * @param  array  $context The log context
     * @return bool   Whether the record has been processed
     */
    public function warning($message, array $context = [])
    {
        return $this->logger->warning($message, $context);
    }

    /**
     * Adds a log record at the ERROR level.
     * 
     * This method allows for compatibility with common interfaces.
     * 
     * @param  string $message The log message
     * @param  array  $context The log context
     * @return bool   Whether the record has been processed
     */
    public function error($message, array $context = [])
    {
        return $this->logger->error($message, $context);
    }

    /**
     * Adds a log record at the CRITICAL level.
     * 
     * This method allows for compatibility with common interfaces.
     * 
     * @param  string $message The log message
     * @param  array  $context The log context
     * @return bool   Whether the record has been processed
     */
    public function critical($message, array $context = [])
    {
        return $this->logger->critical($message, $context);
    }

    /**
     * Adds a log record at the ALERT level.
     * 
     * This method allows for compatibility with common interfaces.
     * 
     * @param  string $message The log message
     * @param  array  $context The log context
     * @return bool   Whether the record has been processed
     */
    public function alert($message, array $context = [])
    {
        return $this->logger->alert($message, $context);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     * 
     * This method allows for compatibility with common interfaces.
     * 
     * @param  string $message The log message
     * @param  array  $context The log context
     * @return bool   Whether the record has been processed
     */
    public function emergency($message, array $context = [])
    {
        return $this->logger->emergency($message, $context);
    }
}
