<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 平台一件代发订单Hold单规则
 *
 * @author wangbiao@shopex.cn
 * @version 2024.07.22
 */
class dealer_platform_order_Hold extends dealer_abstract
{
    /**
     * 处理
     * @param mixed $plat_order_id ID
     * @return mixed 返回值
     */

    public function process($plat_order_id)
    {
        $logMdl = app::get('ome')->model('operation_log');
        
        //获取Hold规则
        $rules = $this->getHoldRules();
        if(empty($rules)){
            $error_msg = '没有一件代发店铺Hold规则';
            return $this->error($error_msg);
        }
        
        //生成订单结构
        $group = $this->_instanceItemObject($plat_order_id);
        if(!$group) {
            $error_msg = '平台订单信息不存在';
            return $this->error($error_msg);
        }
        
        //rules
        $HoldRule = $this->getValidHoldRule($rules, $group);
        if(empty($HoldRule)){
            $error_msg = '没有有效的Hold规则';
            return $this->error($error_msg);
        }
        
        $HoldTime = time() + ($HoldRule['hours'] * 3600);
        
        //logs
        $logMsg = 'Hold单成功,Hold单规则名称:'. $HoldRule['name']. '(order_id：'.$HoldRule['tid'].'),Hold单时限：'. date('Y-m-d H:i:s', $HoldTime);
        $logMdl->write_log('order_modify@dealer', $plat_order_id, $logMsg);
        
        return $this->succ('成功获取到Hold规则', $HoldRule);
    }
    
    /**
     * 获取一件代发店铺Hold规则列表
     * 
     * @return array|void
     */
    public function getHoldRules()
    {
        //Hold规则列表
        $filters = kernel::single('omeauto_auto_type')->getAutoHoldTypes();
        if(empty($filters)){
            return false;
        }
        
        $HoldList = array();
        foreach ($filters as $key => $filterInfo)
        {
            $isFlag = false;
            
            if(empty($filterInfo['config'])){
                continue;
            }
            
            $configList = unserialize($filterInfo['config']);
            if(empty($configList)){
                continue;
            }
            
            foreach ($configList as $configKey => $configVal)
            {
                $configVal = json_decode($configVal, true);
                
                $configList[$configKey] = $configVal;
                
                //check判断店铺发货模式是否为：一件代发店铺
                if($configVal['role'] == 'shopmode' && $configVal['content']['type'] == 'shop_yjdf'){
                    $isFlag = true;
                }
            }
            
            //check必须要设置Hold单时限/小时
            if(empty($filterInfo['hours'])){
                continue;
            }
            
            if($filterInfo['hours'] <= 0){
                continue;
            }
            
            if($isFlag){
                $filterInfo['config'] = $configList;
                
                $HoldList[$key] = $filterInfo;
            }
        }
        
        return $HoldList;
    }
    
    /**
     * 生成订单结构
     * 
     * @param Array $plat_order_id
     * @retun void
     */
    private function _instanceItemObject($plat_order_id)
    {
        $orderLib = kernel::single('dealer_platform_orders');
        
        //order
        $filter = array('plat_order_id'=>$plat_order_id);
        $orderInfo = $orderLib->getOrderDetail($filter);
        if(empty($orderInfo)){
            return false;
        }
        
        //order_id
        $orderInfo['order_id'] = $orderInfo['plat_order_id'];
        
        //format
        $orderInfo['objects'] = $orderInfo['order_objects'];
        foreach ($orderInfo['objects'] as $objKey => $objVal)
        {
            $orderInfo['objects'][$objKey]['items'] = $orderInfo['objects'][$objKey]['order_items'];
            
            unset($orderInfo['objects'][$objKey]['order_items']);
        }
        
        //setOrders
        $orders = array($orderInfo);
        
        return new dealer_platform_order_item($orders);
    }
    
    /**
     * 获取有效的Hold规则
     * 
     * @param $rules
     * @param $group
     * @return void
     */
    public function getValidHoldRule($rules, $group=null)
    {
        foreach ($rules as $ruleKey => $ruleVal)
        {
            foreach ($ruleVal['config'] as $roleKey => $role)
            {
                $className = sprintf('omeauto_auto_type_%s', $role['role']);
                $filter = new $className();
                $filter->setRole($role['content']);
                
                //valid
                if ($filter->vaild($group)) {
                    unset($filter);
                    
                    return $ruleVal;
                }else{
                    unset($filter);
                }
            }
        }
        
        return array();
    }
}
