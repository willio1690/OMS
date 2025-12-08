<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 发货单处理
 */
class erpapi_shop_matrix_meituan4medicine_request_delivery extends erpapi_shop_request_delivery
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
        
        // 拆单回写
        if ($sdf['is_split'] && $sdf['orderinfo']['ship_status'] == '1') {
            $param                 = array();
            $param['package_type'] = 'break';
            $param['tid']          = $sdf['orderinfo']['order_bn'];
            $packages              = array();
            $num = 0;
            foreach ($sdf['delivery_items'] as $key => $value) {
                if ($num >= 5) {
                    continue;
                }
                $packages[] = [
                    'logistics_no' => $value['logi_no'],
                    'company_code' => $value['logi_type'],
                    'company_name' => $value['logi_name'],
                ];
                $num++;
            }
            $param['packages'] = json_encode($packages);
        }
        //如果快递公司是顺丰必须传递手机号
        $param['recipient_phone'] = $sdf['consignee']['mobile'];
        return $param;
    }
}