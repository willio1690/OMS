<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2018/4/12
 * @describe 发货处理
 */
class erpapi_shop_matrix_shunguang_request_delivery extends erpapi_shop_request_delivery
{
    /**
     * confirm
     * @param mixed $sdf sdf
     * @param mixed $queue queue
     * @return mixed 返回值
     */

    public function confirm($sdf,$queue=false)
    {
        $hadRequestOid = array();
        $requestSdf = $sdf;
        foreach($sdf['orderinfo']['order_objects'] as $value) {
            if(!in_array($value['oid'], $hadRequestOid)) {
                $hadRequestOid[] = $value['oid'];
                $requestSdf['oid'] = $value['oid'];
                $result = parent::confirm($requestSdf, $queue);
            }
        }
        return $result;
    }

    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);
        $param['oid'] = $sdf['oid'];
        $param['send_type'] = '2';
        return $param;
    }
}