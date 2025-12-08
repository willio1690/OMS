<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 来源店铺
 */
class omeauto_order_label_shop extends omeauto_order_label_abstract implements omeauto_order_label_interface
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
        
        //店铺ID
        $shop_id = $orderInfo['shop_id'];
        
        //检查来源店铺
        if (!in_array($shop_id, $this->content)) {
            $error_msg = '订单所属店铺不在配置中';
            return false;
        }
        
        return true;
    }
}