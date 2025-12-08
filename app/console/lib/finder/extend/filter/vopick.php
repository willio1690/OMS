<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_extend_filter_vopick{
    function get_extend_colums(){
        $db['pick_bills']=array (
            'columns' => array (
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

