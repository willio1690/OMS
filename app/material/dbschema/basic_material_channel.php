<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 平台物料映射渠道
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

$db['basic_material_channel'] = array(
    'columns' =>
        array(
            'id'          =>
                array(
                    'type'     => 'int unsigned',
                    'required' => true,
                    'pkey'     => true,
                    'editable' => false,
                    'extra'    => 'auto_increment',
                ),
            'bm_id'       =>
                array(
                    'type'     => 'int',
                    'required' => true,
                    'label'    => 'ID',
                    'width'    => 110,
                    'hidden'   => true,
                    'editable' => false,
                ),
            'material_bn' =>
                array(
                    'type'            => 'varchar(200)',
                    'label'           => '物料编码',
                    'width'           => 120,
                    'editable'        => false,
                    'in_list'         => true,
                    'default_in_list' => true,
                    'required'        => true,
                    'searchtype'      => 'nequal',
                    'filtertype'      => 'normal',
                    'filterdefault'   => true,
                ),
            'outer_product_id' =>
                array(
                    'type'            => 'varchar(200)',
                    'label'           => '外部编码',
                    'width'           => 120,
                    'editable'        => false,
                    'in_list'         => true,
                    'default_in_list' => true,
                    'required'        => true,
                    'searchtype'      => 'nequal',
                    'filtertype'      => 'normal',
                    'filterdefault'   => true,
                ),
            'channel_id'   =>
                array(
                    'type'     => 'varchar(20)',
                    'required' => true,
                    'label'    => '渠道id',
                    'width'    => 110,
                    'hidden'   => true,
                    'editable' => false,
                ),
            'channel_name' =>
                array(
                    'type'            => 'varchar(200)',
                    'label'           => '渠道名称',
                    'width'           => 120,
                    'default'           => '',
                    'editable'        => false,
                    'in_list'         => true,
                    'default_in_list' => true,
                    'required'        => true,
                    'searchtype'      => 'nequal',
                    'filtertype'      => 'normal',
                    'filterdefault'   => true,
                ),
            'approve_status'  =>
                array(
                    'type'     => array(
                        0 => '下架',
                        1 => '上架',
                    ),
                    'label'    => '上下架状态',
                    'in_list'  => true,
                    'editable' => false,
                    'default'  => 1,//上下架状态(1: 上架 0:下架)
                ),
            'create_time' => array(
                'type' => 'time',
                'label' => '创建时间',
                'in_list' => true,
                'default' => 0,
            ),
            'last_modify' => array(
                'type' => 'last_modify',
                'label' => '最后更新时间',
                'in_list' => true,
                'order' => 11,
            ),
            'op_id'         => array(
                'type'       => 'table:account@pam',
                'label'      => '操作员',
                'width'      => 110,
                'editable'   => false,
                'filtertype' => 'normal',
                'in_list'    => true,
            ),
            'op_name'       => array(
                'type'     => 'varchar(30)',
                'editable' => false,
            ),
            'is_error' =>
                array(
                    'type'     => array(
                        0 => '正常',
                        1 => '异常',
                    ),
                    'label'    => '是否异常',
                    'in_list'  => false,
                    'editable' => false,
                    'default'  => '0',//上下架状态(1: 上架 0:下架)
                ),
            'price' =>
                array (
                    'type' => 'money',
                    'default' => '0.000',
                    'label' => '售价',
                    'width' => 75,
                ),
        ),
    
    'comment' => '基础物料wms渠道映射表',
    'index'   =>
        array(
            'ind_bm_id'       =>
                array(
                    'columns' =>
                        array(
                            0 => 'bm_id',
                        ),
                ),
            'ind_material_bn' =>
                array(
                    'columns' =>
                        array(
                            0 => 'material_bn',
                        ),
                ),
            'ind_product_channel' => array(
                'columns' => array(
                    0 => 'outer_product_id',
                    1 => 'channel_id',
                ),
            ),
        ),
    'engine'  => 'innodb',
    'version' => '$Rev:  $',
);
