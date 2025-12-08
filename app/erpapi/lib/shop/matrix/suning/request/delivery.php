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
class erpapi_shop_matrix_suning_request_delivery extends erpapi_shop_request_delivery
{
    /**
     * 发货请求参数
     *
     * @return void
     * @author 
     **/

    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);

        $item_list = array();
        foreach ($sdf['orderinfo']['order_objects'] as $object) {

            if ($object['shop_goods_id'] && $object['shop_goods_id'] > 0) $item_list[] = $object['shop_goods_id'];
        }

        $param['item_list'] = json_encode($item_list);
        
        return $param;
    }
}