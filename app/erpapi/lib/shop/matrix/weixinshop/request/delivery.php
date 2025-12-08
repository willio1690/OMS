<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Datetime: 2022/4/11
 * @Describe: 发货单处理
 */
class erpapi_shop_matrix_weixinshop_request_delivery extends erpapi_shop_request_delivery
{
    /**
     * 发货请求参数
     *
     * @return void
     * @author
     **/

    protected function get_confirm_params($sdf) {
        $param = parent::get_confirm_params($sdf);
        
        // 拆单子单回写
        if ($sdf['is_split'] == 1) {
            $param['is_split'] = $sdf['is_split'];
            $param['oids']  = implode(',',$sdf['oid_list']);
            $param['package_type'] = 'break';# 发货标志 normal 正常发货, break 拆单发货,
            $param['goods'] = json_encode($sdf['goods']);
        }

        return $param;
    }
}