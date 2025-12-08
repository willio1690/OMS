<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: gehuachun
 * Date: 2018-12-10
 * Time: 15:51
 */
class erpapi_shop_matrix_yutang_request_delivery extends erpapi_shop_request_delivery
{
    /**
     * 发货请求参数
     *
     * @return void
     * @author
     **/

    public function get_confirm_params($sdf)
    {
        $param = parent::get_confirm_params($sdf);
        //订单需要拆单
        if($sdf['is_split']==1 && !empty($sdf['oid_list'])){
            $param['is_split'] = 1;
        }
        $goods = array();
        foreach($sdf['delivery_items'] as $key => $object){
            $obj = array();
            $obj['oid'] = $object['oid'];
            $goods[] = $obj;
        }
        $param['details'] = json_encode($goods);
        return $param;
    }


}