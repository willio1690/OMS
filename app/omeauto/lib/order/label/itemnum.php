<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单基础物料购买总数量
 */
class omeauto_order_label_itemnum extends omeauto_order_label_abstract implements omeauto_order_label_interface
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
            $error_msg = '没有设置基础物料种类数';
            return false;
        }
        
        //基础物料购买数量
        $itemnum = 0;
        foreach($orderInfo['order_objects'] as $objects)
        {
            foreach($objects['order_items'] as $value)
            {
                if($value['delete'] == 'true'){
                    continue;
                }
                
                $itemnum += $value['quantity']; //$value['nums']
            }
        }
        
        //check
        $isResult = false;
        switch($this->content['type'])
        {
            case 1:
                //小于指定商品数
                $isResult = ($itemnum < $this->content['max'] ? true : false);
                if(!$isResult){
                    $error_msg = '订单总购买数量'. $itemnum .',未小于指定商品数'. $this->content['max'];
                }
                
                break;
            case 2:
                //大于等于指定商品数
                $isResult = ($itemnum >= $this->content['min'] ? true : false);
                if(!$isResult){
                    $error_msg = '订单总购买数量'. $itemnum .',未大于等于指定商品数'. $this->content['min'];
                }
                
                break;
            case 3:
                //位于两个商品数之间
                if($itemnum >= $this->content['min'] && $itemnum < $this->content['max']){
                    $isResult = true;
                }
                
                if(!$isResult){
                    $error_msg = '订单总购买数量'. $itemnum .',不是位于两个商品数之间('. $this->content['min'] .','. $this->content['max'] .')';
                }
                
                break;
            case 4:
                //等于指定商品数
                $isResult = ($itemnum == $this->content['min'] ? true : false);
                if(!$isResult){
                    $error_msg = '订单总购买数量'. $itemnum .',不是等于指定商品数'. $this->content['min'];
                }
                
                break;
        }
        
        return $isResult;
    }
}