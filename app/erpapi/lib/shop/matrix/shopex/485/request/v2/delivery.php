<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单处理
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_shop_matrix_shopex_485_request_v2_delivery extends erpapi_shop_matrix_shopex_request_delivery
{
    /**
     * 添加发货单参数
     *
     * @return void
     * @author 
     **/

    protected function get_add_params($sdf)
    {
        $param = parent::get_add_params($sdf);

        $delivery_items = array();
        foreach ($sdf['orderinfo']['order_objects'] as $object) {
            if($object['shop_goods_id'] && $object['shop_goods_id'] > 0){
                $delivery_items[] = array(
                    'name'   => $object['name'],
                    'bn'     => $object['bn'],
                    'number' => $object['quantity'],
                );
            }


        }

        $param['shipping_items'] = json_encode($delivery_items);

        return $param;
    }
}