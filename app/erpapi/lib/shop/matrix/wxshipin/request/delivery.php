<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Date: 2023/4/20
 * @Describe: 发货单处理
 */
class erpapi_shop_matrix_wxshipin_request_delivery extends erpapi_shop_request_delivery
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
        
        // 拆单子单回写
//        if ($sdf['is_split'] == 1) {
            $param['goods']        = json_encode($sdf['goods']);
//        }
        
        return $param;
    }
}