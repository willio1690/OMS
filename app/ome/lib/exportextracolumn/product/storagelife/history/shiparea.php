<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯一码历史导出扩展字段 收货区域
 * 20180718 by wangjianjun
 */
class ome_exportextracolumn_product_storagelife_history_shiparea extends ome_exportextracolumn_abstract implements ome_exportextracolumn_interface{

    protected $__pkey = 'history_id';

    protected $__extra_column = 'column_ship_area';

    public function associatedData($ids){
        $mdl_storagelife_history = app::get('ome')->model('product_storagelife_history');
        $rs_storagelife_history = $mdl_storagelife_history->getList("*",$ids);
        foreach ($rs_storagelife_history as $var_rsh){
            if($var_rsh["bill_type"] == "1"){ //发货单
                $mdl_ome_delivery = app::get('ome')->model('delivery');
                $delivery = $mdl_ome_delivery->dump(array('delivery_bn'=>$var_rsh['bill_no']),'ship_area');
                if(!empty($delivery)){
                    $ship_area = explode(":", $delivery["consignee"]["area"]);
                    $tmp_array[$var_rsh[$this->__pkey]] = str_replace("/", "", $ship_area[1]);
                }
            }elseif($var_rsh["bill_type"] == "2"){ //退换货单
                $mdl_ome_reship = app::get('ome')->model('reship');
                $rs_reship = $mdl_ome_reship->dump(array("reship_bn"=>$var_rsh['bill_no']),'ship_area');
                if(!empty($rs_reship)){
                    $ship_area = explode(":", $rs_reship["ship_area"]);
                    $tmp_array[$var_rsh[$this->__pkey]] = str_replace("/", "", $ship_area[1]);
                }
            }
        }
        return $tmp_array;
    }

}