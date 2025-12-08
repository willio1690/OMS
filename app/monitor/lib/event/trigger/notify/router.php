<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class monitor_event_trigger_notify_router
{
    private $__send_type;
    
    public function set_send_type($send_type)
    {
        $this->__send_type = $send_type;
        
        return $this;
    }
    
    public function __call($method, $args)
    {
        $platform = kernel::single('monitor_event_trigger_notify_common');

        try {
            $className = sprintf('monitor_event_trigger_notify_%s',$this->__send_type);
    
            if (class_exists($className)) {
                $platform = kernel::single($className);
            }
            
        } catch (Exception $e) {
        
        }
        
        return call_user_func_array(array($platform, $method), $args);
    }
}
