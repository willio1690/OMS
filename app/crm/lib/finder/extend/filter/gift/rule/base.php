<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class crm_finder_extend_filter_gift_rule_base
{
    function get_extend_colums()
    {
        $db['gift_rule_base'] = array (
            'columns' => array (
                'sales_material_bn' => array(
                    'type' => 'varchar(32)',
                    'label' => '销售物料编码',
                    'in_list' => true,
                    'default_in_list' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'order' => 50,
                ),
            )
        );
        
        return $db;
    }
}
