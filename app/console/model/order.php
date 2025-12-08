<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯品会JIT采购单mdl类
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: vopurchase.php 2017-03-06 13:00
 */
class console_mdl_order extends dbeav_model{
    var $defaultOrder = array('po_id',' DESC');
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false)
    {
        if($real){
           $table_name = 'sdb_purchase_order';
        }else{
           $table_name = 'order';
        }
        
        return $table_name;
    }
    
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        return app::get('purchase')->model('order')->get_schema();
    }

    /**
     * modifier_co_mode
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_co_mode($row){
        if($row == 'jit'){
            return '普通';
        }elseif($row == 'jit_4a'){
            return '分销';
        }else{
            return $row ? $row : '-';
        }
    }
}
?>
