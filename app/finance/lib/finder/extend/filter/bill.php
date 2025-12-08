<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_finder_extend_filter_bill{
    function get_extend_colums(){
        $db['bill']=array (
            'columns' => array (

                'channel_id' => array (
                    'type' => kernel::single('finance_bill')->get_name_by_shop(),
                    'default' => '0',
                    'label' => '渠道名称',
                    'width' => 65,
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'status' => array (
                    'type' => kernel::single('finance_bill')->get_name_by_status('',1),
                    'default' => '0',
                    'required' => true,
                    'label' => '核销状态',
                    'width' => 75,
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                // 'charge_status' => array (
                //     'type' => kernel::single('finance_bill')->get_name_by_charge_status('',1),
                //     'default' => '0',
                //     'label' => '记账状态',
                //     'width' => 65,
                //     'editable' => false,
                //     'filtertype' => 'normal',
                //     'filterdefault' => true,
                // ),
                // 'monthly_status' => array (
                //     'type' => kernel::single('finance_bill')->get_name_by_monthly_status('',1),
                //     'default' => '0',
                //     'label' => '月结状态',
                //     'width' => 65,
                //     'editable' => false,
                //     'filtertype' => 'normal',
                //     'filterdefault' => true,
                // ),
                'bill_type' => array (
                    'type' => array('0'=>'实收单','1'=>'实退单'),
                    'default' => '0',
                    'label' => '单据类型',
                    'comment' => '单据类型',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => false,
                ),
            )
        );
        if($_GET['app'] == 'finance' && $_GET['ctl']=='bill' && $_GET['act'] == 'index'){
            unset($db['bill']['columns']['status']);
        }
        return $db;
    }
}

