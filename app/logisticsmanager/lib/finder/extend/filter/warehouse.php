<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class logisticsmanager_finder_extend_filter_warehouse{
    function get_extend_colums(){
        $db['warehouse']=array (
            'columns' => array (
                'b_type' => array(
                    'type' => array(
                        1 => '仓库',
                        2 => '门店'
                    ),
                    'label' => '业务类型',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => false,
                    'default_in_list' => false,
                ),
            )
        );
        return $db;
    }
}
