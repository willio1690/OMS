<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Datetime: 2022/4/24
 * @Describe: 外部优仓商品请求路由
 */
class dchain_event_trigger_dchain_data_product_router
{
    private $__shop_id;

    /**
     * 设置_shop_id
     * @param mixed $shop_id ID
     * @return mixed 返回操作结果
     */

    public function set_shop_id($shop_id)
    {
        $this->__shop_id = $shop_id;

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
        $platform = kernel::single('dchain_event_trigger_dchain_data_product_common');

        if ($this->__shop_id) {
            $shop = $this->get_shop();

            try {
                $node_type = $shop['node_type'];

                $platform = kernel::single('ome_event_trigger_dchain_data_product_'.$node_type);
            } catch (Exception $e) {}    
        }

        return call_user_func_array(array($platform,$method), $args);
    }

    private function get_shop()
    {
        static $shops;

        if ($shops[$this->__shop_id]) return $shops[$this->__shop_id];

        $shops[$this->__shop_id] = app::get('ome')->model('shop')->dump($this->__shop_id);

        return $shops[$this->__shop_id];
    }
}