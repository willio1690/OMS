<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 预约退款
 * 2018.9.27 by wangjianjun
 */
class erpapi_shop_response_params_bookingrefund extends erpapi_shop_response_params_abstract {
    
    /**
     * ordermsg
     * @return mixed 返回值
     */

    public function ordermsg(){
//        $arr = array(
//            'order_bn' => array(
//                'required' => 'true',
//                'errmsg' => '发订单号不能为空'
//            )
//        );
        return array();
    }
    
    /**
     * ordercancle
     * @return mixed 返回值
     */
    public function ordercancle(){
        return array();
    }
}