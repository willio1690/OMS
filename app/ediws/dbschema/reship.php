<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['reship'] = array(
    'columns' => array(
        'reship_id'                => array(
            'type'     => 'int unsigned',
            'required' => true,
            'pkey'     => true,
            'extra'    => 'auto_increment',
            'label'    => '主键id',
            'editable' => false,
        ),
        'reship_bn' =>array(
            'label'           => '退货单号',
            'type'            => 'varchar(100)',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'shop_id'           => array(
            'type'            => 'table:shop@ome',
            'label'           => '店铺',
            'in_list'         => true,
            'default_in_list' => true,
            'editable'        => false,
            'order'           => 20,
        ),
        'purchasechannelid'        => array(
            'type'            => 'varchar(100)',
            'label'           => '采购渠道ID',
            'in_list'         => true,
            'default_in_list' => true,
            'editable'        => false,
            'order'           => 30,
        ),
        'outchannelname'=>array(
            'label'           => '采购渠道名称',
            'type'            => 'varchar(100)',
             'in_list'         => true,
            'default_in_list' => true,
        ),
        'createtime'=>array(
            'label'           => '制单时间',
            'type'            => 'time',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'wareaddr'=>array(
            'type'            => 'varchar(255)',
            'label'           => '收货地址',
            'in_list'         => true,
            'default_in_list' => true,
            'editable'        => false,

        ),
        'source'=>array(
            'type'            => 'varchar(10)',
            'label'           => '退货来源',
            'in_list'         => true,
            'default_in_list' => true,
        ),
        'sourcename'=>array(
             'label'           => '退货来源名称',
             'type'            => 'varchar(50)',
             'in_list'         => true,
            'default_in_list' => true,
            'filtertype' => 'yes',
            'filterdefault' => true,
            'searchtype' => 'nequal',
        ),
       
        'addtime'          => array(
            'type'            => 'time',
            'label'           => '创建时间',
            'in_list'         => true,
            'default_in_list' => true,
            'editable'        => false,
            'order'           => 90,
           
        ),   
        'sync_status' => array(
            'type' => array(
                '0' => '未推送',
                '1' => '推送中',
                '2' => '已推送',
                '3' => '推送失败',
                '4' =>'无需推送',
            ),

            'default' => '0',
            'label' => '同步状态',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),

        'sync_msg' => array(
            'type' => 'text',

            'label' => '同步返回原因',
            'in_list'         => true,
            'default_in_list' => true,
            'editable' => false,
        ),
        'extends' => array(
            'type' => 'text',

            'label' => '行扩展字段',

            'editable' => false,
        ),
    ),
    'index'   => array(
        'ind_reship_bn' => array('columns' => array('reship_bn'), 'prefix' => 'unique'),
        
    ),
    'comment' => '主库退货单',
    'engine'  => 'innodb',
    'version' => '$Rev: 40654 $',
);
