<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 唯一码历史导出扩展字段 电话
 * 20180718 by wangjianjun
 */
class ome_exportextracolumn_product_storagelife_history_shiptel extends ome_exportextracolumn_abstract implements ome_exportextracolumn_interface{

    protected $__pkey = 'history_id';

    protected $__extra_column = 'column_ship_tel';

    public function associatedData($ids){
        $mdl_storagelife_history = app::get('ome')->model('product_storagelife_history');
        $rs_storagelife_history = $mdl_storagelife_history->getList("*",$ids);
        foreach ($rs_storagelife_history as $var_rsh){
            if($var_rsh["bill_type"] == "1"){ //发货单
                $mdl_ome_delivery = app::get('ome')->model('delivery');
                $delivery = $mdl_ome_delivery->dump(array('delivery_bn'=>$var_rsh['bill_no']),'ship_tel');
                if(!empty($delivery)){
                    $tmp_array[$var_rsh[$this->__pkey]] = $delivery["consignee"]["telephone"];
                }
            }elseif($var_rsh["bill_type"] == "2"){ //退换货单
                $mdl_ome_reship = app::get('ome')->model('reship');
                $rs_reship = $mdl_ome_reship->dump(array("reship_bn"=>$var_rsh['bill_no']),'ship_tel');
                if(!empty($rs_reship)){
                    $tmp_array[$var_rsh[$this->__pkey]] = $rs_reship["ship_tel"];
                }
            }
        }
        return $tmp_array;
    }

}