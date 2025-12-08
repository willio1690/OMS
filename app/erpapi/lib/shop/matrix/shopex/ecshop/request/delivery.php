<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_shopex_ecshop_request_delivery extends erpapi_shop_matrix_shopex_request_delivery{

    /**
     * 更新物流公司(ecshop无需请求)
     *
     * @return void
     * @author
     **/
    public function logistics_update($sdf){}
    /**
     * 获取发货接口(默认线下发货)
     *
     * @return void
     * @author
     **/
    protected function get_delivery_apiname($sdf){
        return SHOP_LOGISTICS_OFFLINE_SEND;
    }
    /**
     * 添加发货单
     *
     * @return void
     * @author
     **/
    public function add($sdf){
        
    }
    /**
     * 添加发货单参数
     *
     * @return void
     * @author
     **/
    protected function get_confirm_params($sdf){
        $param = parent::get_confirm_params($sdf);
        $param["logistics_no"] = $sdf["logi_no"]; //物流单号
        $param["company_name"] = $sdf["logi_name"]; //物流公司名称

        // 不支持SKU拆单，全量回写
        // if ($sdf['split_model'] == '2') {
        //     $delivery_items = array();
        //     foreach ($sdf['orderinfo']['order_objects'] as $object) {
        //         if ($object['obj_type'] == 'pkg') {
        //             $delivery_items[] = array(
        //                     'name'   => $object['name'],
        //                     'bn'     => $object['bn'],
        //                     'number' => $object['quantity'],
        //             );
        //         } else {
        //             foreach ($object['order_items'] as $item) {
        //                 $delivery_items[] = array(
        //                         'name'   => $item['name'],
        //                         'bn'     => $item['bn'],
        //                         'number' => $item['nums'],
        //                 );
        //             }
        //         }
        //     }
        
        //     $param['shipping_items'] = json_encode($delivery_items);
        // }
        
        return $param;
    }
}