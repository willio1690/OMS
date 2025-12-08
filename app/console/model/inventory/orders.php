<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯品会平台已经成交的销售单列表model类
 * API文档：https://vop.vip.com/home#/api/method/detail/vipapis.inventory.InventoryService-1.0.0/getInventoryOccupiedOrders
 *
 * @author wangbiao@shopex.cn
 * @version 2025.03.27
 */
class console_mdl_inventory_orders extends dbeav_model
{
    //hold单状态列表
    static public $hold_status = array(
        '0' =>  array('stat'=>'0', 'name'=>'正常hold单'),
        '1' =>  array('stat'=>'1', 'name'=>'hold单时间16天'),
    );
    
    /**
     * hold单状态列表
     * 
     * @return array
     */

    public function getHoldStatus()
    {
        $tempList = self::$hold_status;
        
        //format
        $typeList = [];
        foreach ($tempList as $type_id => $typeVal)
        {
            $typeList[$type_id] = $typeVal['name'];
        }
        
        return $typeList;
    }
}