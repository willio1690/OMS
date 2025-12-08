<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 门店发货单矩阵对接阿里全渠道通路接口请求类
 *
 * @author xiayuanjun@shopex.cn
 * @version 0.1
 *
 */
class erpapi_store_matrix_taobao_request_delivery extends erpapi_store_request_delivery
{
    /**
     * 发货单创建
     * ISV同步派单结果到星盘
     * @param array $sdf 请求参数
     */
    public function delivery_create($sdf){
        $param = array(
            "tid" => $sdf["order_bn"],
            "status" => "Allocated",
            "message" => "",
            "report_timestamp" => time(),
            "trace_id " => "",
        );
        //获取门店信息
        $mdlO2oStore = app::get('o2o')->model('store');
        $rs_store = $mdlO2oStore->dump(array("branch_id"=>$sdf["branch_id"]),"store_id,name");
        //获取全渠道门店id
        $mdlTbo2oStore = app::get('tbo2o')->model('store');
        $rs_tbo2o_store = $mdlTbo2oStore->dump(array("store_id"=>$rs_store["store_id"]),"outer_store_id");
        //获取子订单信息
        $mdlOmeOrders = app::get('ome')->model('orders');
        $mdlOmeOrderObjects = app::get('ome')->model('order_objects');
        $rs_orders = $mdlOmeOrders->dump(array("order_bn"=>$sdf["order_bn"],"shop_id"=>$sdf["shop_id"]),"order_id");
        $rs_order_objects = $mdlOmeOrderObjects->getList("oid",array("order_id"=>$rs_orders["order_id"]));
        $sub_order_list = array();
        foreach ($rs_order_objects as $var_o_o){
            $temp_arr = array(
                "code" => 0,
                "message" => "",
                "sub_oid" => $var_o_o["oid"],
                "store_id" => $rs_tbo2o_store["outer_store_id"],
                "store_type" => "Store",
                "store_name" => $rs_store["name"],
                "status" => "Allocated",
                "tid" => $sdf["order_bn"],
//                 "attributes" => "",
            );
            $sub_order_list[] = $temp_arr;
        }
        $param["sub_order_list"] = json_encode($sub_order_list);
        $params = array();
        $params['data'] = json_encode($param);
        $title = "阿里全渠道ISV同步派单结果到星盘";
        return $this->__caller->call(OMNIORDER_ALLOCATEDINFO_SYNC,$params,null,$title,10,$param["tid"]);
    }
    
}