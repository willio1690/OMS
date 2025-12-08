<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ome_rpc_response_version_2_payment extends ome_rpc_response_version_base_payment
{
       
    /**
     * 添加支付单
     * @access public
     * @param array $payment_sdf 付款单标准结构数据
     * @return array('payment_id'=>'付款单主键ID')
     */
    public function add($payment_sdf){
        return array('rsp'=>'success','data'=>array('tid'=>$payment_sdf['order_bn']));
    }
    
    /**
     * 更新付款单状态
     * @access public
     * @param array $status_sdf 付款单状态标准结构数据
     */
    public function status_update($status_sdf){
        return array('rsp'=>'success','data'=>array('tid'=>$payment_sdf['order_bn']));
    }
    
}