<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_reship_import{
    
    function run(&$cursor_id, $params, &$error_msg=null)
    {
        if(empty($params["sdfdata"])){
            return false;
        }
        
        $Oreship = app::get('ome')->model('reship');
        $mdl_ome_orders = app::get('ome')->model('orders');
        $rchangeObj = kernel::single('ome_return_rchange');
        $is_auto_approve = app::get('ome')->getConf('return.auto_approve');
        
        foreach($params["sdfdata"] as $var_sdf)
        {
            //检查退换货单是否已经存在
            if($var_sdf['reship_bn']){
                $reshipInfo = $Oreship->dump(array('reship_bn'=>$var_sdf['reship_bn']), 'reship_id');
                if($reshipInfo){
                    $error_msg = '退换货单号：'. $var_sdf['reship_bn'] .'已经存在,不能重复导入';
                    return false;
                }
            }
            
            //检查换出商品是否有错误提示信息
            if(isset($var_sdf['import_error_msg']) && $var_sdf['import_error_msg']){
                $error_msg = '退换货单号：'. $var_sdf['reship_bn'] .'错误，'. $var_sdf['import_error_msg'];
                return false;
            }
            
            //create
            $msg = '';
            $reship_bn = $Oreship->create_treship($var_sdf, $msg);
            if($reship_bn){
                $rs_current_order = $mdl_ome_orders->dump($var_sdf["order_id"],"pay_status");
                if($rs_current_order["pay_status"] == "5"){ //全额退款订单
                    $var_sdf["pay_status"] = "5";
                }
                
                $rchangeObj->update_diff_amount($var_sdf,$reship_bn);
                $reship = $Oreship->getList('reship_id',array('reship_bn'=>$reship_bn),0,1);
                if($is_auto_approve == 'on'){
                    $reshipLib = kernel::single('ome_reship');
                    $reshipLib->batch_reship_queue($reship[0]['reship_id']);
                }
            }
        }
        
        return false;
    }
    
}