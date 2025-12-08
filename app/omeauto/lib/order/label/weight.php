<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 基础物料总重量总重量
 */
class omeauto_order_label_weight extends omeauto_order_label_abstract implements omeauto_order_label_interface
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
        
        //订单商品总重量
        $weight = app::get('ome')->model('orders')->getOrderWeight($orderInfo['order_id']);
        
        //check
        $isResult = false;
        switch($this->content['type']) {
            case 1:
                //小于指定总重量
                $isResult = ($weight < $this->content['max'] ? true : false);
                if(!$isResult){
                    $error_msg = '订单总重量'. $weight .',未小于总重量'. $this->content['max'];
                }
                
                break;
            case 2:
                //大于等于指定总重量
                $isResult = ($weight >= $this->content['min'] ? true : false);
                if(!$isResult){
                    $error_msg = '订单总重量'. $weight .',未大于等于总重量'. $this->content['min'];
                }
                
                break;
            case 3:
                //位于两个总重量之间
                if($weight >= $this->content['min'] && $weight < $this->content['max']){
                    $isResult = true;
                }
                
                if(!$isResult){
                    $error_msg = '订单总重量'. $weight .',未位于两个总重量之间('. $this->content['min'] .','. $this->content['max'] .')';
                }
                
                break;
            case 4:
                //等于指定总重量
                $isResult = ($weight == $this->content['min'] ? true : false);
                if(!$isResult){
                    $error_msg = '订单总重量'. $weight .',等于指定总重量'. $this->content['min'];
                }
                
                break;
        }
        
        return $isResult;
    }
}