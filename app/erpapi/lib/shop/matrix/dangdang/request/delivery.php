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
class erpapi_shop_matrix_dangdang_request_delivery extends erpapi_shop_request_delivery
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

        // 货号对应平台商品ID
        $item_list = array();
        foreach ((array) $sdf['delivery_items'] as $item) {
            if ($item['shop_goods_id']) {
                $item_list[] = array(
                    'oid'          => $sdf['orderinfo']['order_bn'],
                    'itemId'       => $item['shop_goods_id'],
                    'num'          => $item['number'],
                    'promotion_id' => $item['promotion_id'],
                );
            }
        }

        $param['item_list'] = json_encode($item_list);
        $param['logisticstel'] = '13612345678';
        $param['ship_date']       = date('Y-m-d H:i:s',$sdf['delivery_time']);

        return $param;
    }
}