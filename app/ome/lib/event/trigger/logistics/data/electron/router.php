<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_trigger_logistics_data_electron_router {
    public $channel;

    public function setChannel($channel) {
        $this->channel = $channel;
        return $this;
    }

    public function __call($method,$args)
    {
        $platform = kernel::single('ome_event_trigger_logistics_data_electron_common');
        if ($this->channel) {
            $channelType = $this->channel['channel_type'];
            try {
                if(class_exists('ome_event_trigger_logistics_data_electron_'.$channelType)) {
                    $platform = kernel::single('ome_event_trigger_logistics_data_electron_'.$channelType);
                }
            } catch (Exception $e) {}
        }
        $platform->setChannel($this->channel);
        return call_user_func_array(array($platform,$method), $args);
    }
}