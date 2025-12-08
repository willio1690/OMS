<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

define("POS_LOGISTICS_ONLINE_SEND",'orderDeliveryUpdate');
class erpapi_shop_matrix_pos_request_delivery extends erpapi_shop_request_delivery
{
   
   

   protected function get_confirm_params($sdf)
    {

      
        $param = array(
         
            //'api'                   => 'orderDeliveryUpdate',
            'orderNo'               => $sdf['orderinfo']['order_bn'], // 订单号
            'deliveryCompanyCode'   => $sdf['logi_type'], // 物流编号
            'deliveryCompanyName'   => $sdf['logi_name'], // 物流公司
            'deliveryCode'          => $sdf['logi_no'], // 运单号
        );
        

        $orderItems = [];
        $sdf['delivery_items'] = array_values($sdf['delivery_items']);
        foreach($sdf['delivery_items'] as $k=>$v){
            $orderItems[] = array(

                'itemSeqNo' =>  $k+1,
                'skuCode'   =>  $v['bn'],
                'quantity'  =>  $v['number'],
            );
        }
        $param['orderItems'] = $orderItems;
        return $param;
    }


     protected function get_delivery_apiname($sdf)
    {
        return 'orderDeliveryUpdate';
    }

    
}