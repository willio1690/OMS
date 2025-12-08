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
class erpapi_shop_matrix_alibaba_request_delivery extends erpapi_shop_request_delivery
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
        
        $items = array();
        foreach ($sdf['delivery_items'] as $item)
        {
            if($item['oid']) {
                $send_num = ($item['nums'] ? $item['nums'] : $item['number']);
                
                $items[] = array(
                        'oid' => $item['oid'],
                        'num' => (int)$send_num,
                        'weight' => 0,
                );
            }
        }
        
        $order_list = array();
        $order_list[] = array(
                'tid' => $param['tid'],
                'items' => $items,
        );
        
        $param['order_list'] = json_encode($order_list);
        $param['remarks'] = '';
        $param['ship_date'] = date('Y-m-d H:i:s', $sdf['delivery_time']);
        
        return $param;
    }
}