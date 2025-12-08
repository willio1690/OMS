<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 发货单处理
 */
class erpapi_shop_matrix_xhs_request_delivery extends erpapi_shop_request_delivery
{
    protected function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);
        
        // 拆单子单回写
        if ($sdf['is_split'] == 1) {
            $param['is_split'] = $sdf['is_split'];
            $param['itemIdList'] = implode(',', $sdf['oid_list']);
        }
        
        return $param;
    }
}