<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯品会JIT出库单mdl类
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: vopurchase.php 2017-03-06 13:00
 */
class wms_mdl_pick_stockout_bills extends dbeav_model{
    var $defaultOrder = array('stockout_id',' DESC');
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false)
    {
        if($real){
           $table_name = 'sdb_purchase_pick_stockout_bills';
        }else{
           $table_name = 'pick_stockout_bills';
        }
        
        return $table_name;
    }
    
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        return app::get('purchase')->model('pick_stockout_bills')->get_schema();
    }
}
?>
