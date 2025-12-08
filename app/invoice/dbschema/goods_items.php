<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

#商品与服务税收分类编码
$db['goods_items']=array(
    'columns' =>
        array(
            'id' => array(
                'type' => 'int(10)',
                'pkey' => true,
                'extra' => 'auto_increment',
                'required' => true,
            ),
            'channel_type' =>
                array (
                  'type' => 'varchar(32)',
                  'default' => 'bw',
                  'required' => true,
                  'default' => 'taobao',
                  'comment' => '渠道类型',
            ),
            'tax_code' =>
            array (
                'type' => 'varchar(255)',
                'comment' => '发票分类合并编码',
                'editable' => false,
            ),
            'tax_rate' => array(
                'type' => 'tinyint(2)',
                'default' => '0',
                'label' => '税率',
            ),
            'name' =>
            array (
                'type' => 'varchar(200)',
                'required' => true,
                'default' => '',
                'comment' => '商品和服务名称',
            ),
            'zzstsgl' => array(
                'type' => 'varchar(255)',
                'comment' => '增值税特殊管理',#如果yhzcbs为1时，此项必填，具体信息取百望《商品和服务税收分类与编码》.xls中的增值税特殊管理列
            ),            
            'create_time' => array(
                'type' => 'time',
                'default' => '0',
                'comment' => '创建时间',
            ),
            'update_time' => array(
                'type' => 'time',
                'default' => '0',
                'comment' => '更新时间',
            ),
    ),
    'comment' => '商品与服务税收分类编码',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);
