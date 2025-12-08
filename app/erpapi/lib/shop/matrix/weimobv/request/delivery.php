<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: ghc
 * Date: 18/11/20
 * Time: 上午11:32
 */
class erpapi_shop_matrix_weimobv_request_delivery extends erpapi_shop_request_delivery
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
            $goods = array();
            foreach($sdf['delivery_items'] as $key => $object){
                $obj = array();
                $obj['item_id'] = $object['oid'];
                $obj['sku_id'] = $object['shop_goods_id'];
                $obj['sku_num'] = $object['number'];
                $goods[] = $obj;
            }
            $param['is_split'] = 1;
            $param['goods'] = json_encode($goods);
        }
        return $param;
    }


}