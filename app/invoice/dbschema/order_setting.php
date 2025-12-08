<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['order_setting']=array(
    'columns' =>
        array(
            'sid' => array(
                'type' => 'int unsigned',
                'required' => true,
                'pkey' => true,
                'editable' => false,
                'label' => '发票配置编号',
                'extra' => 'auto_increment',
            ),
           'title' => array(
                'type' => 'number',
                'label' => '发票内容',
            ),          
            'payee_name' => array(
                'type' => 'varchar(100)',
                'label' => '开票方名称',
                'in_list' => true,
                'default_in_list' => true,
                'width' => 120,
            ),
            'payee_operator' => array(
                'type' => 'varchar(50)',
                'label' => '开票人',
                'in_list' => true,
            ),
            'payee_receiver' => array(
                'type' => 'varchar(50)',
                'label' => '收款人',
                'in_list' => true,
            ),
            'payee_checker' => array(
                'type' => 'varchar(50)',
                'label' => '复核人',
                'in_list' => true,
            ),
            'tax_rate' => array(
                'type' => 'tinyint(2)',
                'default' => '0',
                'label' => '税率',
                'in_list' => true,
                'default_in_list' => true,
                'width' => 60,
            ),
            'tax_no' => array(
                'type' => 'varchar(32)',
                'label' => '开票方税号',
                'in_list' => true,
                'default_in_list' => true,
                'width' => 150,
            ),
            'bank' => array(
                'type' => 'varchar(32)',
                'label' => '开票方开户银行',
            ),
            'bank_no' => array(
                'type' => 'char(32)',
                'label' => '开票方银行账号',
            ),
            'telphone' => array(
                'type' => 'char(32)',
                'label' => '开票方电话',
                'in_list' => true,
                'default_in_list' => true,
                'width' => 60,
            ),
            'hsbz' => array(
                'type' => 'char(10)',
                'default' => '1',
                'label' => '含税标志',#0:不含税  1:含税
            ),
            'yhzcbs' => array(
                'type' => 'char(10)',
                'default' => '0',
                'label' => '是否使用优惠政策',#0:未使用，1:使用
            ),
            'zzstsgl' => array(
                'type' => 'varchar(255)',
                'label' => '增值税特殊管理',#如果yhzcbs为1时，此项必填，具体信息取百望《商品和服务税收分类与编码》.xls中的增值税特殊管理列
            ),
            'lslbs' => array(
                'type' => 'char(10)',
                'label' => '零税率标识',#1 出口免税和其他免税优惠政策;2 不征增值税;3 普通零税率
            ),
            'kpddm' => array(
                'type' => 'varchar(100)',
                'label' => '开票点编码',
            ),
            'kpddm' => array(
                'type' => 'varchar(100)',
                'label' => '开票点编码',
            ),
            'eqpttype' => array(
                'type' => 'char(32)',
                'label' => '设备类型',#设备类型 0税控服务，1税控盘
            ),
            'skpdata' => array(
                'type' => 'longtext',
                'label' => '税控盘信息',#这里面包含税控盘编号、税控盘口令、税务数字证书密码
            ),
            'address' => array(
                'type' => 'varchar(255)',
                'label' => '开票方地址',
                'in_list' => true,
                'default_in_list' => true,
                'width' => 60,
            ),
            'provider_appkey' => array(
                'type' => 'varchar(100)',
                'label' => '开票服务商的APPKEY',
                //'in_list' => true,
                //'default_in_list' => true,
                //'width' => 60,
            ),
            'dateline' => array(
                'type' => 'time',
                'label' => '添加时间',
                'width' => 130,
                'in_list' => true,
                'default_in_list' => true,
                'filtertype' => 'time',
                'filterdefault' => true,
            ),
            'shop_id' => array(
                'type' => 'table:shop@ome',
                'default' => 0,
                'required' => true,
                'editable' => false,
                'label' => '电子发票渠道店铺ID',
            ),
          'shopids' => array(
            'type' => 'text',
            'default' => 0,
            'editable' => false,
            'label' => '应用到的店铺ID',
          ),
            'billing_shop_node_id' => array(
                'type' => 'varchar(32)',
                'editable' => false,
                'label' => '电子发票开票服务店铺节点号',
            ),
            'einvoice_operating_conditions' => array(
                'type' => array(
                    1=>'已发货',
                    2=>'已付款',
                    3=>'已完成',
                ),
                'default' => 1,
                'editable' => false,
                'label' => '电子发票操作条件',
            ),
          'channel_id' =>array (
              'type' => 'table:channel@invoice',
              'label' => '开票渠道',
              'width' => 75,
              'editable' => false,
              'in_list' => true,
              'filtertype' => 'normal',
              'filterdefault' => true,
          ),
          'mode' => array(
            'type' => array(
              0=>'纸质发票',
              1=>'电子发票',
            ),
            'default' => '0',
            'required' => true,
            'label' => '类型',
            'in_list' => true,
            'default_in_list' => true,
            'order'=>3,
            'width' => 70,
          ),
    ),
    'index' =>
    array (
        
    ),
    'comment' => '订单发票配置表',
    'engine' => 'innodb',
    'version' => '$Rev:  $',
);