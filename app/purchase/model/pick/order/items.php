<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * JIT订单明细model类
 * API文档：https://vop.vip.com/home#/api/method/detail/com.vip.vis.order.jit.service.order.JitOrderVopService-1.0.0/getJitOrderDetail
 *
 * @author wangbiao@shopex.cn
 * @version 2025.03.27
 */
class purchase_mdl_pick_order_items extends dbeav_model
{
    //订单状态列表
    static public $order_stat = array(
        '10' =>  array('stat'=>'10', 'name'=>'未拣货'),
        '13' =>  array('stat'=>'13', 'name'=>'商品缺货'),
        '20' =>  array('stat'=>'20', 'name'=>'拣货中'),
        '26' =>  array('stat'=>'26', 'name'=>'供应商已发货'),
        '27' =>  array('stat'=>'27', 'name'=>'仓库已收货'),
        '97' =>  array('stat'=>'97', 'name'=>'订单已取消'),
    );
    
    /**
     * 获取订单状态列表
     *
     * @return array
     */
    public function getOrderStats()
    {
        $tempList = self::$order_stat;
        
        //format
        $typeList = [];
        foreach ($tempList as $type_id => $typeVal)
        {
            $typeList[$type_id] = $typeVal['name'];
        }
        
        return $typeList;
    }
}
