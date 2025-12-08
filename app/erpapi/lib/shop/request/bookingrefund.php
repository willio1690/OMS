<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 预约退款
 * 20180927 by wangjianjun
 */
class erpapi_shop_request_bookingrefund extends erpapi_shop_request_abstract{
    
    /**
     * orderMsgUpdate
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function orderMsgUpdate($sdf){
        $order = $sdf['order'];
        $params = array(
            'success'=> $order['pause_status'],
            'tid'=> $order['order_bn'],
            'sub_order_ids'=> $sdf['request_params']['oid_list'],
        );
        if($order["ship_status"] == "1"){ //已发货
            $params["error_code"] = "1001"; //1001代表“已发货拦截失败”
        }
        $title = '订单预约退款回传淘宝店,订单号'.$params['tid'];
        $rsp = $this->__caller->call(SHOP_RDC_ORDERMSG_UPDATE, $params, array(), $title, 10, $params['tid']);
        return $rsp;
    }
    
}
