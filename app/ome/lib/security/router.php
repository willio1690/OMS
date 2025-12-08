<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_security_router
{
    private $__shop_type = null;

    /**
     * __construct
     * @param mixed $shop_type shop_type
     * @return mixed 返回值
     */
    public function __construct($shop_type)
    {
        $this->__shop_type = is_object($shop_type) ? '' : $shop_type;

        $this->__shop_type = str_replace('.','',$this->__shop_type);
        
        //平台兼容
        if($this->__shop_type == 'jd') {
            $this->__shop_type = '360buy';
        }elseif($this->__shop_type == 'tmall'){
            $this->__shop_type = 'taobao';
        }
    }

    /**
     * __call
     * @param mixed $method method
     * @param mixed $arguments arguments
     * @return mixed 返回值
     */
    public function __call($method,$arguments)
    {
        try {
            $object = kernel::single('ome_security_'.$this->__shop_type);

            return call_user_func_array(array($object,$method), $arguments);
        } catch (Exception $e) {
            $object = kernel::single('ome_security_hash');
            if (method_exists($object, $method)) {
                return call_user_func_array(array($object,$method), $arguments);
            }
        }

        return null;
    }

}