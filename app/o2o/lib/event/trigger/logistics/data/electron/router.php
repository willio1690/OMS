<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_event_trigger_logistics_data_electron_router {
    public $channel;

    /**
     * 设置Channel
     * @param mixed $channel channel
     * @return mixed 返回操作结果
     */
    public function setChannel($channel) {
        $this->channel = $channel;
        return $this;
    }

    /**
     * __call
     * @param mixed $method method
     * @param mixed $args args
     * @return mixed 返回值
     */
    public function __call($method,$args)
    {
        $platform = kernel::single('o2o_event_trigger_logistics_data_electron_common');
        if ($this->channel) {
            $channelType = $this->channel['channel_type'];
            try {
                if(class_exists('o2o_event_trigger_logistics_data_electron_'.$channelType)) {
                    $platform = kernel::single('o2o_event_trigger_logistics_data_electron_'.$channelType);
                }
            } catch (Exception $e) {}
        }
        $platform->setChannel($this->channel);
        return call_user_func_array(array($platform,$method), $args);
    }
}