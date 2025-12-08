<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class purchase_finder_extend_filter_returned_purchase{
    function get_extend_colums(){
        $db['returned_purchase']=array (
            'columns' => array (
                'bn' => array (
                    'type' => 'varchar(30)',
                    'label' => '货号',
                    'width' => 85,
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

