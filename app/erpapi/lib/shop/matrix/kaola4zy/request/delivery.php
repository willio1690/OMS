<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_kaola4zy_request_delivery extends erpapi_shop_request_delivery
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

        $product_list = array();
        $weights = array();
        foreach($sdf['delivery_items'] as $k => $item){
            $num = intval($item['nums']);
            $product_list[] = array(
                'qty'       => $num,
                'sku_id'    => $item['shop_product_id'] ,
            );
            $weights[] = floatval($item['weight']);
        }
        $param['order_status'] = 300;// 50 - 打单（打印分拣单）100 - 分拣（分拣员拣货）200 - 打包（按照用户订单打包）300 - 发货（包裹交付物流商揽收）
        $param['notify_type'] = 31;// 20 - 用户下单 21 - 取消用户下单、31 - 用户订单出库回调70 - 查询物流轨迹 、117-供应商全量库存同步、118-初始化商品映射关系
        $param['transport_order_id_list'] = json_encode(array($sdf['logi_no']));
        $param['order_items'] = json_encode($product_list);
        $param['domestic_weights'] = json_encode($weights);
        return $param;
    }
}