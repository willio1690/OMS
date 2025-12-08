<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 仓库预占流水记录
 */

class console_finder_extend_filter_basic_material_stock_freeze
{
    function get_extend_colums(){
        
        $branchObj = app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        
        //仓库列表
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super, false);
        $branch_rows   = $branchObj->getList('branch_id, name',array('branch_id'=>$branch_ids),0,-1);
        $branch_list = array();
        foreach($branch_rows as $branch){
            $branch_list [$branch['branch_id']] = $branch['name'];
        }
        
        //columns
        $db['basic_material_stock_freeze']=array (
                'columns' => array (
                        'branch_id' =>
                        array (
                                'type' => $branch_list,
                                'editable' => false,
                                'label' => '仓库',
                                'width' => 110,
                                'filtertype' => 'normal',
                                'filterdefault' => true,
                                'in_list' => true,
                                'panel_id' => 'stock_freeze_finder_top',
                        ),
                        'shop_id' =>
                        array (
                          'type' => 'table:shop@ome',
                          'label' => '来源店铺',
                          'width' => 75,
                          'editable' => false,
                          'in_list' => true,
                          'filtertype' => 'normal',
                          'filterdefault' => true,
                          'panel_id' => 'stock_freeze_finder_top',
                        ),
                        'obj_type' =>
                        array (
                                'type' => [1=>'订单', 2=>'仓库', 3=>'售后'],
                                'editable' => false,
                                'label' => '对象类型',
                                'width' => 70,
                                'filtertype' => 'normal',
                                'filterdefault' => true,
                                'in_list' => true,
                                'panel_id' => 'stock_freeze_finder_top',
                        ),
                        'bill_type' =>
                        array (
                                'type' => [
                                    0=>'订单冻结', 1=>'发货单', 2=>'售后换货', 3=>'采购退换', 4=>'调拨出库', 5=>'库内转储', 
                                    6=>'唯品会出库', 7=>'人工库存预占', 8=>'库存调整单', 9=>'差异单',10=>'加工单', 
                                    11 => 'VOP拣货单', 12 => '售后申请单', 13 => '订单缺货', 14 => '仓库冻结',
                                ],
                                'editable' => false,
                                'label' => '业务类型',
                                'width' => 80,
                                'filtertype' => 'normal',
                                'filterdefault' => true,
                                'in_list' => true,
                                'panel_id' => 'stock_freeze_finder_top',
                        ),
                )
        );
        
        return $db;
    }
}