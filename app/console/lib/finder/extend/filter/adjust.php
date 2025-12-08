<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_extend_filter_adjust
{
    function get_extend_colums()
    {
        $db['adjust'] = array(
            'columns' => array(
                'material_bn' => array(
                    'type'            => 'varchar(30)',
                    'label'           => '基础物料编码',
                    'width'           => 130,
                    'filtertype'      => 'textarea',
                    'filterdefault'   => true,
                    'editable'        => false,
                    'in_list'         => true,
                    'default_in_list' => true,
                ),
            )
        );
        return $db;
    }
}