<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 路由
 */
class invoice_event_trigger_data_router
{
    private $__shop_id;
    
    public function set_shop_id($shop_id)
    {
        $this->__shop_id = $shop_id;
    
        return $this;
    }
    
    public function __call($method,$args)
    {
       $platform = kernel::single('invoice_event_trigger_data_common');
       
        if ($this->__shop_id) {
            $obj_channel = app::get('invoice')->model('channel');
            
            //获取店铺所属的电子发票渠道
            $rs = $obj_channel->get_channel_info($this->__shop_id);
            if($rs['channel_type']){
                $channelType = $rs['channel_type'];
                
                try {
                    $platform = kernel::single('invoice_event_trigger_data_'.$channelType);
                } catch (Exception $e) {}
            }
        }
        
        return call_user_func_array(array($platform, $method), $args);
    }  
}