<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class archive_service_objtype_pko {
    /*
     * 处理object类型数据
     *
     * @param array $obj object数据
     * 
     * @return array $items 订单详情
     */

    public function process($obj){
        if ($obj['obj_type'] == 'pko'){

            $order = app::get('archive')->model('orders')->dump($obj['order_id'],'order_bn');
            
            $items['order_bn']  = $order['order_bn'];
            $items['bn']        = $obj['bn'];
            $items['name']      = $obj['name'];
            $items['unit']      = $obj['unit']?$obj['unit']:'-';
            $items['spec_info'] = $obj['spec_info']?$obj['spec_info']:'-';
            $items['nums']      = $obj['quantity'];
            $items['price']     = $obj['price'];
            $items['sale_price'] = $obj['sale_price'];
            
            return array($items);
        }
        return array();
    }
}