<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单类型
 */
class omeauto_order_label_ordertype extends omeauto_order_label_abstract implements omeauto_order_label_interface
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
            $error_msg = '没有设置收货地区规则';
            return false;
        }
        
        //检查订单类型
        $order_type = trim($orderInfo['order_type']);
        if (!in_array($order_type, $this->content['order_type'])) {
            $error_msg = '订单类型'. $order_type .',不在配置类型中';
            return false;
        }
        
        return true;
    }

}