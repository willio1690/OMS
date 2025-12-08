<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * Created by PhpStorm.
 * User: qiudi
 * Date: 18/10/12
 * Time: 上午11:32
 */
class erpapi_shop_matrix_aikucun_request_delivery extends erpapi_shop_request_delivery
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

        // $skuSize = 0;
        $product_list = array();
        foreach($sdf['delivery_items'] as $k => $item){
            // $skuSize += intval($item['nums']);

            // $num     = intval($item['nums']);
            // $realnum = intval($item['sendnum']);
            // $lacknum = $num - $realnum;
            if ($item['shop_goods_id'] && $item['shop_goods_id']!='-1'){
                $product_list[] = array(
                    'bn'        => $item['bn'],
                    'barcode'   => $item['shop_goods_id'] ? $item['shop_goods_id'] : '',
                    'pinpai'    => $item['brand_name'] ? $item['brand_name'] : '',
                    'num'       => $item['number'],
                    'realnum'   => $item['number'],
                    'lacknum'   => 0,
                );
            }
            
        }

        $param['sku_size']     = count($product_list);
        $param['product_info'] = json_encode($product_list);
        $param['activity_id']  = $sdf['orderinfo']['tostr'];

        $param['status'] = $sdf['orderinfo']['ship_status'] == '1' ? 1 : 2;
        $param['version'] = '2.0';
        $param['is_split'] = isset($sdf['is_split']) ? $sdf['is_split'] : 0;
        if ($sdf['is_split']) {
            $param['goods_no_list'] = json_encode($sdf['oid_list']);
        }

        if ($sdf['sku_order_list']) {
            $param['sku_order_list'] = json_encode($sdf['sku_order_list']);
        }

        return $param;
    }


}