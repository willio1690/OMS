<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * [活动订单]订单标签规则抽象类
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class omeauto_order_label_sku extends omeauto_order_label_abstract implements omeauto_order_label_interface
{
    /**
     * 设置已经创建好的配置内容
     *
     */
    public function setRole($params)
    {
        $this->content = $params;
        
        if (!empty($this->content['sku'])) {
            $this->content['sku'] = explode(',', $this->content['sku']);
            foreach ($this->content['sku'] as $key => $sku) {
                $this->content['sku'][$key] = strtolower(trim($sku)); //转换为小写
            }
        }
    }
    
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
            $error_msg = '没有配置活动订单规则';
            return false;
        }
        
        $now_time = time();
        $this->content['start'] = intval($this->content['start']);
        $this->content['end'] = intval($this->content['end']);
        
        //生效时间
        if($this->content['start']){
            if($now_time < $this->content['start']){
                $error_msg = '不符合开始时间';
                return false;
            }
        }
        
        if($this->content['end']){
            if($now_time > $this->content['end']){
                $error_msg = '不符合结束时间';
                return false;
            }
        }
        
        //检查订单销售物料
        $isResult = true;
        foreach ($orderInfo['order_objects'] as $key => $object)
        {
            $object['bn'] = strtolower($object['bn']); //转换为小写
            
            if($this->content['sku_range'] == 'true'){
                //匹配订单所有销售物料
                if(!in_array($object['bn'], $this->content['sku'])){
                    $error_msg = '货号'. $object['bn'] .'不匹配';
                    return false;
                }
            }else{
                //匹配订单一个销售物料
                if(in_array($object['bn'], $this->content['sku'])) {
                    return true;
                }
                
                $isResult = false;
            }
        }
        
        //error
        if(!$isResult){
            $error_msg = '没有一个销售物料匹配到';
        }
        
        return $isResult;
    }
}