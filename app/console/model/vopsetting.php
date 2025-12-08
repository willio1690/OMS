<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 唯品会JIT配置mdl类
 *
 * @access public
 * @author wangbiao<wangbiao@shopex.cn>
 * @version $Id: vopsetting.php
 */
class console_mdl_vopsetting extends dbeav_model{
    var $defaultOrder = array('create_time',' DESC');
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */

    public function table_name($real = false)
    {
        if($real){
           $table_name = 'sdb_purchase_setting';
        }else{
           $table_name = 'setting';
        }
        
        return $table_name;
    }
    
    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        return app::get('purchase')->model('setting')->get_schema();
    }
}
?>
