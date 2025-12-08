<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 基础物料关联库存
 *
 * @version 1.0
 */
class wms_receipt_material
{
   /*
    * 获取库存详情
    *$param int
    *return array
    */
    function products_detail($product_id)
    {
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        $basicMaterialLib    = kernel::single('material_basic_material');
        $libBranchProductPos    = kernel::single('ome_branch_product_pos');
        $entityBranchLib    = kernel::single('ome_entity_branch_product');
        
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        
        //基础物料库存
        $pro    = $basicMaterialLib->getBasicMaterialStock($product_id);
        $pro['bn']    = $pro['material_bn'];
        $pro['name']  = $pro['material_name'];
        
        $sql = 'SELECT
        p.product_id,p.branch_id,p.arrive_store,p.store,p.store_freeze,p.safe_store,p.is_locked,
        bc.name as branch_name
        FROM sdb_ome_branch_product as p 
        LEFT JOIN sdb_ome_branch as bc ON bc.branch_id=p.branch_id 
        WHERE p.product_id='.$product_id.' AND p.branch_id in ('.implode(',',$branch_ids).')';
       
        $branch_product = kernel::database()->select($sql);

       
        foreach($branch_product as $key=>$val)
        {
            //根据仓库ID、基础物料ID获取该物料仓库级的预占
            $branch_product[$key]['store_freeze']    = $basicMStockFreezeLib->getBranchFreeze($val['product_id'], $val['branch_id']);
            
            //获取某产品已经放置货位列表
            $pos_string ='';
            $posLists = $libBranchProductPos->get_pos($val['product_id'], $val['branch_id']);
            
            if(count($posLists) > 0){
                foreach($posLists as $pos){
                    $pos_string .= $pos['store_position'].",";
                }
                $branch_product[$key]['store_position'] = substr($pos_string,0,strlen($pos_string)-1);
            }
            //总仓成本
            $entityBranchInfo = $entityBranchLib->getBranchCountCostPrice( $val['branch_id'],$val['product_id']);
            if (!kernel::single('desktop_user')->has_permission('cost_price')) {
                $branch_product[$key]['entity_unit_cost'] = '-';
            }else{
                $branch_product[$key]['entity_unit_cost'] = isset($entityBranchInfo[$val['branch_id']]) ? $entityBranchInfo[$val['branch_id']][$val['product_id']]['unit_cost'] : 0;
            }
        }

        $pro['branch_product'] = $branch_product;
        $store_total = 0;
        foreach($branch_product as $bp){
            $store_total+=$bp['store'];
        }
        
        $pro['store'] = $store_total;
        return $pro;
    }
    
    /**
    * 统计自有仓库存
    *
    * $product_id 
    */
    function countBranchProduct($product_id, $column='safe_store')
    {
        $is_super = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        $sql = 'SELECT SUM('.$column.') AS total FROM sdb_ome_branch_product WHERE product_id = '.$product_id.' AND branch_id in ('.implode(',',$branch_ids).")";

        $count = kernel::database()->selectrow($sql);

        return $count['total'];
    }
    
    /**
     * 统计自有仓库冻结库存
     * 
     * @param int $bm_id
     * @return int $store_freeze
     */
    function countBmidStoreFreeze($bm_id)
    {
        $bProductObj = app::get('ome')->model('branch_product');
        $basicMStockFreezeLib  = kernel::single('material_basic_material_stock_freeze');
        
        //管理的仓库
        $is_super   = kernel::single('desktop_user')->is_super();
        $branch_ids = kernel::single('wms_branch')->getBranchwmsByUser($is_super);
        $store_freeze = 0;
        
        //冻结库存
        $sql      = 'SELECT product_id, branch_id FROM sdb_ome_branch_product WHERE product_id='.$bm_id.' AND branch_id in ('. implode(',', $branch_ids) .')';
        $dataList = $bProductObj->db->select($sql);
        if($dataList)
        {
            foreach ($dataList as $key => $val)
            {
                //根据仓库ID、基础物料ID获取该物料仓库级的预占
                $store_freeze    += $basicMStockFreezeLib->getBranchFreeze($val['product_id'], $val['branch_id']);
            }
        }
        
        return $store_freeze;
    }
}