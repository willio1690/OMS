<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Handler\AbstractProcessingHandler;

class base_logger_handler_apidb extends AbstractProcessingHandler
{
    private $apiMdl;

    public function __construct(int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        $this->apiMdl = app::get('ome')->model('api_log');

        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $data = [
            'log_id' =>  uniqid('', true),
            'original_bn' => $record->context['original_bn'],
            'task_name' => $record->message,
            'status' => $record->context['status'] ?: null,
            'worker' => $record->context['worker'] ?: null,
            'params' => $record->context['params'] ? json_encode($record->context['params']) : null,
            'addon' => $record->extra ? json_encode($record->extra) : null,
            'msg' => $record->context['msg'],
            'log_type' => $record->channel,
            'api_type' => $record->context['api_type'],
            'error_lv' => $record->level->getName(),
            'msg_id' => $record->context['msg_id'],
            'unique' => $record->extra['uid'],
            'createtime' => $record->datetime->getTimestamp(),
            'spendtime' => $record->context['original_bn'],
        ];

        $this->apiMdl->insert($data);
    }
}