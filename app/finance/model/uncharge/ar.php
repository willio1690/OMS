<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_mdl_uncharge_ar extends dbeav_model{

    var $defaultOrder = array('ar_id DESC');
    public $filter_use_like = true;

    public function table_name($real=false){
        $tableName = 'ar';
        return $real ? kernel::database()->prefix.'finance_'.$tableName : $tableName;

    }

    // public function searchOptions(){
    //     return array();
    // }


    function modifier_type($row){
        return kernel::single('finance_ar')->get_name_by_type($row);
    }

    //重载记账状态，展示
    function modifier_charge_status($row){
        return kernel::single('finance_ar')->get_name_by_charge_status($row);
    }

    //重载核销状态，展示
    function modifier_status($row){
        return kernel::single('finance_ar')->get_name_by_status($row);
    }

    //重载核销状态，展示
    function modifier_monthly_status($row){
        return kernel::single('finance_ar')->get_name_by_monthly_status($row);
    }

    //重载单据类型
    function modifier_ar_type($row){
        return kernel::single('finance_ar')->get_name_by_ar_type($row);
    }




}
