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
 * @author fire
 * @version $Id: Z
 */
class erpapi_shop_matrix_alibaba4ascp_request_delivery extends erpapi_shop_request_delivery
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

        $param['mail_no']   = $param['logistics_no'];//运单号
        $param['source_id'] = $param['tid'];//订单id
        $items = array();
        foreach ($sdf['delivery_items'] as $item)
        {
            if($item['oid']) {
                $items[] = $item['oid'];
            }
        }
        $order_list          = array($param['tid']);
        $param['order_list'] = json_encode($items);
        $param['ship_date']  = date('Y-m-d H:i:s', $sdf['delivery_time']);
        
        return $param;
    }
}