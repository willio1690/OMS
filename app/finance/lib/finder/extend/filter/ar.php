<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_finder_extend_filter_ar{
    function get_extend_colums(){
        $db['ar']=array (
            'columns' => array (
                'channel_id' => array (
                    'type' => kernel::single('finance_ar')->get_name_by_shop(),
                    'default' => '0',
                    'label' => '渠道名称',
                    'width' => 65,
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
                'status' => array (
                    'type' => kernel::single('finance_ar')->get_name_by_status('',1),
                    'default' => '0',
                    'required' => true,
                    'label' => '核销状态',
                    'width' => 75,
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => false,
                ),
                'type' => array (
                    'type' => kernel::single('finance_ar')->get_name_by_type('',1),
                    'default' => '0',
                    'label' => '业务类型',
                    'comment' => '业务类型',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => false,
                ),
                // 'charge_status' => array (
                //     'type' => kernel::single('finance_ar')->get_name_by_charge_status('',1),
                //     'default' => '0',
                //     'label' => '记账状态',
                //     'width' => 65,
                //     'editable' => false,
                //     'filtertype' => 'normal',
                //     'filterdefault' => true,
                //     'in_list' => true,
                //     'default_in_list' => false,
                // ),
                // 'monthly_status' => array (
                //     'type' => kernel::single('finance_ar')->get_name_by_monthly_status('',1),
                //     'default' => '0',
                //     'label' => '月结状态',
                //     'width' => 65,
                //     'editable' => false,
                //     'filtertype' => 'normal',
                //     'filterdefault' => true,
                //     'in_list' => true,
                //     'default_in_list' => false,
                // ),
                'ar_type' => array (
                    'type' => array('0'=>'应收单','1'=>'应退单'),
                    'default' => '0',
                    'label' => '单据类型',
                    'comment' => '单据类型',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => false,
                ),
                'monthly_date' => array (
                    'type' => 'varchar(32)',
                    'label'=>'账期名称',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
            )
        );
        return $db;
    }
}

