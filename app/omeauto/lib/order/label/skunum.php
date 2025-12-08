<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 订单基础物料种类数
 */
class omeauto_order_label_skunum extends omeauto_order_label_abstract implements omeauto_order_label_interface
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
            $error_msg = '没有设置基础物料总数量规则';
            return false;
        }
        
        //基础物料种类数量
        $product_ids = array();
        foreach($orderInfo['order_objects'] as $objects)
        {
            foreach($objects['order_items'] as $value)
            {
                $product_id = $value['product_id'];
                
                if($value['delete'] == 'true'){
                    continue;
                }
                
                $product_ids[$product_id] = $product_id;
            }
        }
        
        //种类数量
        $skunum = count($product_ids);
        
        //check
        $isResult = false;
        switch($this->content['type'])
        {
            case 1:
                //小于指定种类数
                $isResult = ($skunum < $this->content['max'] ? true : false);
                if(!$isResult){
                    $error_msg = '订单SKU种类数量'. $skunum .',未小于指定种类数'. $this->content['max'];
                }
                
                break;
            case 2:
                //大于等于指定种类数
                $isResult = ($skunum >= $this->content['min'] ? true : false);
                if(!$isResult){
                    $error_msg = '订单SKU种类数量'. $skunum .',未大于等于指定种类数'. $this->content['min'];
                }
                
                break;
            case 3:
                //位于两个种类数之间
                if($skunum >= $this->content['min'] && $skunum < $this->content['max']){
                    $isResult = true;
                }
                
                if(!$isResult){
                    $error_msg = '订单SKU种类数量'. $skunum .',没有位于两个种类数之间('. $this->content['min'] .','. $this->content['max'] .')';
                }
                
                break;
            case 4:
                //等于指定种类数
                $isResult = ($skunum == $this->content['min'] ? true : false);
                if(!$isResult){
                    $error_msg = '订单SKU种类数量'. $skunum .',没有等于指定种类数'. $this->content['min'];
                }
                
                break;
        }
        
        return $isResult;
    }
}