<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 人工库存预占流水记录
 */
class console_mdl_basic_material_stock_artificial_freeze extends material_mdl_basic_material_stock_artificial_freeze{
    
    //是否有导出配置
    var $has_export_cnf = true;
    var $export_name = '人工预占列表';
    
    //确定表名获取表数据
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false){
        if($real){
            $table_name = 'sdb_material_basic_material_stock_artificial_freeze';
        }else{
            $table_name = 'basic_material_stock_artificial_freeze';
        }
        return $table_name;
    }
    
    //确定表结构
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        return app::get('material')->model('basic_material_stock_artificial_freeze')->get_schema();
    }
    
    function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = array();
        //基础物料编码
        if(isset($filter['basic_material_bn']) && $filter['basic_material_bn']){
            $mdl_basic_material = app::get('material')->model('basic_material');
            $rs_basic_material = $mdl_basic_material->dump(array("material_bn"=>trim($filter['basic_material_bn'])));
            if(!empty($rs_basic_material)){
                $where[] = "bm_id=".$rs_basic_material["bm_id"];
            }else{
                $where[] = "bm_id=-1";
            }
            unset($filter['basic_material_bn']);
        }
        if(isset($filter["basic_material_bn"]) && !$filter["basic_material_bn"]){
            unset($filter["basic_material_bn"]);
        }
        if(isset($filter["group_id"]) && !$filter["group_id"]){
            unset($filter["group_id"]);
        }
        if(isset($filter["branch_id"]) && !$filter["branch_id"]){
            unset($filter["branch_id"]);
        }
        $sWhere = parent::_filter($filter,$tableAlias,$baseWhere);
        if(!empty($where)){
            $sWhere .= " AND ".implode(" and ",$where);
        }
        return $sWhere;
    }
    
    //导出 字段配置 移除不需要的字段
    /**
     * disabled_export_cols
     * @param mixed $cols cols
     * @return mixed 返回值
     */
    public function disabled_export_cols(&$cols){
        unset($cols['column_edit']);
    }
    
}