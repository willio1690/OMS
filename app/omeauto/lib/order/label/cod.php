<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 付款方式
 */
class omeauto_order_label_cod extends omeauto_order_label_abstract implements omeauto_order_label_interface
{
    /**
     * 检查订单数据是否符合要求
     *
     * @param array $orderInfo
     * @param string $error_msg
     * @return bool
     */
    public function vaild($orderInfo, &$error_msg=null)
    {
        if(empty($this->content)){
            $error_msg = '没有设置付款方式规则';
            return false;
        }
        
        //检查付款方式
        if ($orderInfo['shipping']['is_cod'] != $this->content) {
            if($this->content == 'true'){
                $error_msg = '订单支付方式不是货到付款';
            }else{
                $error_msg = '订单支付方式不是款到发货';
            }
            
            return false;
        }
        
        return true;
    }

}