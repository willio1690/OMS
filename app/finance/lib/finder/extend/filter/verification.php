<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_finder_extend_filter_verification{
    function get_extend_colums(){
        $db['verification']=array (
            'columns' => array (
                'type' => array (
                    'type' => kernel::single('finance_verification')->get_name_by_type('',1),
                    'default' => '0',
                    'required' => true,
                    'label' => '核销类型',
                    'width' => 75,
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'default_in_list' => false,
                ),
            )
        );
        return $db;
    }
}