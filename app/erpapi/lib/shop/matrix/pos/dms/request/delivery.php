<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_pos_dms_request_delivery extends erpapi_shop_matrix_pos_request_delivery
{
    protected function get_confirm_params($sdf)
    {
        $param = array(
            'tid'         => $sdf['orderinfo']['order_bn'], // 订单号
            'logi_code'   => $sdf['logi_type'], // 物流编号
            'logi_name'   => $sdf['logi_name'], // 物流公司
            'logi_no'     => $sdf['logi_no'], // 运单号
            // 'store_code'  => '',
            't_confirm'   => date('Y-m-d H:i:s', $sdf['delivery_time']),
            'delivery_bn' => $sdf['delivery_bn'],
            'method'      => 'b2c.delivery.update',
        );

        $items = [];
        foreach ($sdf['delivery_items'] as $k => $v) {
            $items[] = array(
                'product_bn'   => $v['bn'],
                'product_name' => $v['name'],
                'number'       => $v['number'],
            );
        }
        $param['items'] = json_encode($items);

        return $param;
    }
}
