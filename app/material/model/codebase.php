<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 码库模型层
 *
 * @author kamisama.xia@gmail.com 
 * @version 0.1
 */

class material_mdl_codebase extends dbeav_model{

    /**
     * 码库类型字段格式化
     * @param string $row 物料类型字段
     * @return string
     */
    function modifier_type($row){
        $materialConfLib = kernel::single('material_codebase');
        $codebaseList = $materialConfLib->getCodeList();
        if($codebaseList){
            foreach($codebaseList as $code){
                if($code['type'] == $row){
                    return $code['name'];
                }
            }
        }

        return "-";
    }

    /**
     * 码库列表项扩展字段
     */
    function extra_cols(){
        return array(
            'column_material_name' => array('label'=>'基础物料名称','width'=>'260','func_suffix'=>'material_name'),
            'column_material_bn' => array('label'=>'基础物料编码','width'=>'120','func_suffix'=>'material_bn'),
        );
    }

    /**
     * 物料名称扩展字段格式化
     */
    function extra_material_name($rows){
        return kernel::single('material_extracolumn_codebase_materialname')->process($rows);
    }

    /**
     * 物料编码扩展字段格式化
     */
    function extra_material_bn($rows){
        return kernel::single('material_extracolumn_codebase_materialbn')->process($rows);
    }

}
