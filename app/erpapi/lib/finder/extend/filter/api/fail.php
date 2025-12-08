<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_finder_extend_filter_api_fail{
    function get_extend_colums(){

        $type_list = array(
            'delivery'=>'发货单',
            'purchase'=>'采购单',
            'allcoate'=>'调拨出入库单',
            'defective'=>'残损入库单',
            'adjustment'=>'调帐入库单',
            'exchange'=>'换货入库单',
            'other'=>'其他入库单',
            'purchase_return'=>'采购退货单',
            'defective'=>'残损出库单',
            'adjustment'=>'调帐出库单',
            'reship'=>'退货单',
            'jddelivery'=>'京东发货回传',
            'deliveryship'=>'发货单发货回传',
            'deliveryBack'=>'运单号回传',
        );
        $db['api_fail']=array (
            'columns' => array (
                'obj_type' => array(
                    'type' => $type_list,
                    'filtertype' => 'normal',
                    'label' => '单据类型',
                    'in_list' => true,
                    'default_in_list' => true,
                    'filterdefault' => true,
                    'panel_id' => 'apifail_finder_top',
                ),

            )
        );
        
        return $db;
    }
}
