<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class finance_finder_extend_filter_monthly_report_items{
    function get_extend_colums(){
        $db['monthly_report_items']=array (
            'columns' => array (
                'gap' => [
                    'type' => 'money',
                    'label' => 'GAP',
                    'in_list' => true,
                    'default_in_list' => true,
                    'order' => 110,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ],
            )
        );
        return $db;
    }
}

