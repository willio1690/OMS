<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

//已弃用 全渠道无需iframe绑定奇门
class tbo2o_rpc_response_channel extends ome_rpc_response{
    /**
     * qimen_callback
     * @param mixed $result result
     * @return mixed 返回值
     */
    public function qimen_callback($result){
        $nodes = $_POST;
        $status = $nodes['status'];
        $node_id = $nodes['node_id'];
        $node_type = $nodes['node_type'];
        if($node_type != "qimen"){
            //非淘宝全渠道奇门绑定
            die(1);
        }
        $mdlTbo2oShop = app::get('tbo2o')->model('shop');
        
        $tbo2o_shop = kernel::single('tbo2o_common')->getTbo2oShopInfo();
        
        if(!empty($tbo2o_shop)){
            $filter_arr = array("id"=>$tbo2o_shop["id"]);
        }
        if ($status == 'bind'){
            //绑定
            $sql_arr = array("qimen_node_id" => $node_id);
            if($filter_arr){
                //有配置信息 更新记录
                $mdlTbo2oShop->update($sql_arr,$filter_arr);
            }else{
                //无配置信息  新建记录
                $mdlTbo2oShop->insert($sql_arr);
            }
        }elseif ($status == 'unbind' && $tbo2o_shop["qimen_node_id"] == $node_id){
            //解绑
            $sql_arr = array("qimen_node_id" => "");
            if($filter_arr){
                //有配置信息 更新记录
                $mdlTbo2oShop->update($sql_arr,$filter_arr);
            }
        }
        die('1');
    }
}
