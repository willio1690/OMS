<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class wms_mdl_product_serial extends dbeav_model{

    //是否有导出配置
    var $has_export_cnf = true;
    var $export_name = '唯一码列表';
    
    //删除不需要导出的字段
    /**
     * disabled_export_cols
     * @param mixed $cols cols
     * @return mixed 返回值
     */
    public function disabled_export_cols(&$cols){
        unset($cols['column_edit']);
    }
    
    /**
     * 唯一码是否可用
     * @param  String  $serial_number 
     * @param  String  $bn 
     * @param  String  $branch_id 
     * @return Boolean
     */
    public function checkSerial($serial_number, $bn, $branch_id){
    	if (!$serial_number || !$bn || !$branch_id) {
    		return false;
    	}
        $filter['serial_number'] = $serial_number;
        $filter['bn'] = $bn;
        $filter['branch_id'] = $branch_id;
        $serialData = $this->dump($filter);
        if($serialData['serial_id'] > 0 && $serialData['status'] == 0){
        	return true;
        }else{
        	return false;
        }
    }
}