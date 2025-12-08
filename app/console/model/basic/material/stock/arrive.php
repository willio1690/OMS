<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 仓库在途流水记录
 */
class console_mdl_basic_material_stock_arrive extends dbeav_model{
    
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false)
    {
        if($real){
            $table_name = 'sdb_material_basic_material_stock_arrive';
        }else{
            $table_name = 'basic_material_stock_arrive';
        }
        
        return $table_name;
    }
    
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema()
    {
        return app::get('material')->model('basic_material_stock_arrive')->get_schema();
    }
    
    function _filter($filter,$tableAlias=null,$baseWhere=null){
        if($filter['obj_bn']) {
            $bill = app::get('purchase')->model('po')->db_dump(['po_bn'=>$filter['obj_bn']],'po_id');
            if($bill) {
                $baseWhere[] = ' obj_type="purchase" and obj_id="'.$bill['po_id'].'"';
            } else {
                $bill = app::get('taoguaniostockorder')->model('iso')->db_dump(['iso_bn'=>$filter['obj_bn']],'iso_id');
                if($bill) {
                    $baseWhere[] = ' obj_type="iostockorder" and obj_id="'.$bill['iso_id'].'"';
                } else {
                    $baseWhere[] = 'obj_id = -1';
                }
            }
            unset($filter['obj_bn']);
        }
        if($filter['product_bn']) {
            $material = app::get('material')->model('basic_material')->db_dump(['material_bn'=>$filter['product_bn']], 'bm_id');
            $baseWhere[] = 'bm_id='.intval($material['bm_id']);
            unset($filter['product_bn']);
        }
        return parent::_filter($filter,$tableAlias,$baseWhere);
    }

    function searchOptions(){
        $columns = parent::searchOptions();
        $columns['obj_bn'] = '对象单号';
        $columns['product_bn'] = '基础物料编码';
        return $columns;
    }

    /**
     * modifier_obj_id
     * @param mixed $col col
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_obj_id($col,$list,$row) {
        if($row['obj_type'] == 'purchase') {
            $bill = app::get('purchase')->model('po')->db_dump(['po_id'=>$col],'po_bn as obj_bn');
        } else {
            $bill = app::get('taoguaniostockorder')->model('iso')->db_dump(['iso_id'=>$col],'iso_bn as obj_bn');
        }
        return $bill['obj_bn'] ? : $col;
    }

    /**
     * modifier_obj_type
     * @param mixed $col col
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_obj_type($col,$list,$row) {
        return $col == 'purchase' ? '采购单' : '入库单';
    }
}