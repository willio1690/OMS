<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author k 2017/10/23
 * @describe 发货处理
 */
class erpapi_shop_matrix_yunji_request_delivery extends erpapi_shop_request_delivery
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
        $bn_list=array();
        if($sdf['is_split'] == 1&&$sdf['split_model']==1) {
            $param['is_split']  = $sdf['is_split'];
            foreach ($sdf['delivery_items'] as $arr){
                $bn_list[] = $arr['bn'];
            }
        }else{
            foreach ($sdf['order_items'] as $arr){
                $bn_list[] = $arr['bn'];
            }
        }
        $param['bn_list'] = json_encode($bn_list);
        return $param;
    }
}