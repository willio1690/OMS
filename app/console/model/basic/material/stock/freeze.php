<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 仓库预占流水记录
 */
class console_mdl_basic_material_stock_freeze extends material_mdl_basic_material_stock_freeze{
    
    var $defaultOrder = array('last_modified DESC, bmsf_id DESC');
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false)
    {
        if($real){
            $table_name = 'sdb_material_basic_material_stock_freeze';
        }else{
            $table_name = 'basic_material_stock_freeze';
        }
        
        return $table_name;
    }
    
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema()
    {
        return app::get('material')->model('basic_material_stock_freeze')->get_schema();
    }
    
    /**
     * 预占流水类型
     */
    public function get_type($type_id=0)
    {
        $typeList  = array();
        $typeList[1]  = array(0=>'订单冻结',13=>'订单缺货',14=>'仓库冻结');
        $typeList[2]  = array(1=>'发货单', 2=>'售后换货', 3=>'采购退换', 4=>'调拨出库', 5=>'库内转储', 6=>'唯品会出库', 7=>'人工库存预占', 8=>'库存调整单', 9=>'差异单',10=>'加工单', 11 => 'VOP拣货单', 12 => '售后申请单', 13 => '订单缺货', 14 => '仓库冻结');
        $typeList[3]  = array(1=>'发货单', 2=>'售后换货', 3=>'采购退换', 4=>'调拨出库', 5=>'库内转储', 6=>'唯品会出库', 7=>'人工库存预占', 8=>'库存调整单', 9=>'差异单',10=>'加工单', 11 => 'VOP拣货单', 12 => '售后申请单', 13 => '订单缺货', 14 => '仓库冻结');
        
        //唯品会销售订单
        $typeList[15]  = array(0=>'创建订单');
        
        if($type_id)
        {
            $typeList    = $typeList[$type_id];
        }
        
        return $typeList;
    }
}
