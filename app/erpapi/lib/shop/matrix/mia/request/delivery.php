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
class erpapi_shop_matrix_mia_request_delivery extends erpapi_shop_request_delivery
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

        $str_item_id = '';
        foreach ($sdf['orderinfo']['order_objects'] as $object) {
            if($object['shop_goods_id']) $str_item_id .= $object['shop_goods_id'].',';
        }

        $param['item_id'] = trim($str_item_id,',');
        return $param;
    }
}