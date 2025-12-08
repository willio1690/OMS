<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_trigger_exrecommend_data_recommend_router
{
    private $channel_type;
    
    public function setChannel($channel_type) {
        $this->channel_type = $channel_type;
        return $this;
    }
    
    public function __call($method,$args)
    {
       $platform = kernel::single('ome_event_trigger_exrecommend_data_recommend_common');
        if ($this->channel_type) {
            $channelType = $this->channel_type;
            try {
                $platform = kernel::single('ome_event_trigger_exrecommend_data_recommend_'.$channelType);
            } catch (Exception $e) {}
        }
        return call_user_func_array(array($platform,$method), $args);
    }  
}