<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_mdl_inventory extends dbeav_model{
    
    var $has_many = array(
        'inventory_items' => 'inventory_items',
    );
    
    function gen_id(){
        return 'P'.date("ymdHis").rand(0,9).rand(0,9);
    }
    
    
    function do_save($applySdf, $branch_bn){
        if (!$applySdf || !$branch_bn) return false;
        $oInvApply  = app::get('console')->model("inventory_apply");
        $inventoryObj = kernel::single('console_receipt_inventory');
        //先生成盘点，再生成出入库
        
        if(!$inventoryObj->finish_inventory($applySdf['inventory_apply_bn'],$branch_bn,1,$applySdf['inventory_apply_items'])) return false;
        
        //确认盘点申请
        $rs = $oInvApply->update(array('status'=>'confirmed','process_date'=>time()), array('inventory_apply_id'=>$applySdf['inventory_apply_id']));
        if (!$rs) return false;
        return true;
    }
    

    /**
     * modifier_out_id
     * @param mixed $col col
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_out_id($col,$list,$row){
        $branch_relationObj = app::get('wmsmgr')->model('branch_relation');
        $branch_relation    = $branch_relationObj->dump(array('wms_branch_bn'=>$col,'wms_id'=>$row['wms_id']));
        $branch_bn = $branch_relation['sys_branch_bn'] ? : $col;
        $branchObj = kernel::single('console_iostockdata');
        $branch_info = $branchObj->getBranchBybn($branch_bn);
        return $branch['name'];
        
    }

    /**
     * modifier_branch_bn
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_branch_bn($row){
        $branchObj = kernel::single('console_iostockdata');
        $branch = $branchObj->getBranchBybn($row);
        return $branch['name'];
    }
}
