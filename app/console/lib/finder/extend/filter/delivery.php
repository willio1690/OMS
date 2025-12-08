<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_extend_filter_delivery{
    function get_extend_colums()
    {
        //WMS异常错误码
        $abnormalObj = app::get('wmsmgr')->model('abnormal_code');
        $tempList = $abnormalObj->getList('abnormal_id,abnormal_code,abnormal_name', array('abnormal_type'=>'delivery'), 0, 100);
        $error_codes = array();
        if($tempList){
            foreach ($tempList as $key => $val)
            {
                $abnormal_code = $val['abnormal_code'];
                $error_codes[$abnormal_code] = $abnormal_code.'('.$val['abnormal_name'].')';
            }
        }
        unset($tempList);
        
        $db['delivery']=array (
            'columns' => array(
                'delivery_bn' => array (
                    'type' => 'varchar(32)',
                    'label' => '发货单号',
                    'editable' => false,
                    'width' =>140,
                    'searchtype' => 'nequal',
                    'filtertype' => 'textarea',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'order_bn' => array (
                    'type' => 'varchar(30)',
                    'label' => '订单号',
                    'width' => 130,
                    'filtertype' => 'textarea',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'outer_dlybn' => array (
                    'type' => 'varchar(30)',
                    'label' => '第三方单号',
                    'width' => 130,
                    'filtertype' => 'textarea',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'sync_code' => array (
                    'type' => $error_codes,
                    'label' => '同步WMS错误码',
                    'editable' => false,
                    'filtertype' => 'yes',
                    'filterdefault' => true,
                    'in_list' => false,
                    'default_in_list' => false,
                ),
                'material_bn' => array (
                    'type' => 'varchar(30)',
                    'label' => '基础物料编码',
                    'width' => 130,
                    'filtertype' => 'textarea',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'order_label' => array (
                    'type' => 'table:order_labels@omeauto',
                    'label' => '标记',
                    'width' => 120,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
            )
        );
        
        return $db;
    }
}
