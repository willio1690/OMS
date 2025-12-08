<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯一码导入队列执行
 * by wangjianjun 20171117
 */
class wms_product_serial_import{
    
    function run(&$cursor_id,$params){
        if(empty($params["sdfdata"])){
            return false;   
        }

        $mdl_ome_ps = app::get('wms')->model('product_serial');
        $operationLogObj = app::get('ome')->model('operation_log');
        foreach($params["sdfdata"] as $var_sdf){
            $insert_arr = array_merge(array("create_time"=>time()),$var_sdf);
            $mdl_ome_ps->insert($insert_arr);
            //write log import serial
            $operationLogObj->write_log('product_serial_import@wms',$insert_arr['serial_id'],'唯一码导入');
        }
        return false;
    }
    
}