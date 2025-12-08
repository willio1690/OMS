<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class tgstockcost_schema_cost
{
  function get_schema()
  {
      $db['branch_product']=array (
        'columns' =>
        array (
    			'id' => 
    				array (
    				'type' => 'number',
    				'required' => true,
    				'pkey' => true,
    				'extra' => 'auto_increment',
    				'editable' => false,
    				'in_list'=>false,
    				'default_in_list'=>false,
    				'label'=>'ID',
    			),
          'p.bn' =>
          array (
            'label' => '基础物料编码',
            'editable' => false,
            'in_list' => true,
            'default_in_list'=>true,
      			#'searchtype' => 'has',
      			'filterdefault' => true,
            'filtertype' => 'normal',
            'order' => 4,
            'orderby'=>false,
            'filterdefault' => true,
            'panel_id' => 'costselect_finder_top',
          ),
          'product_name' =>
          array (
            'label' => '基础物料名称',
            'editable' => false,
            'in_list' => true,
            'default_in_list'=>true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 5,
            'orderby'=>false,
            'panel_id' => 'costselect_finder_top',
          ),

          'bp.store' =>
          array (
            'label' => '库存数',
            'editable' => false,
            'in_list' => true,
            'default_in_list'=>true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 7,
            'orderby'=>false,
          ),
          'unit_cost' =>
          array (
            'label' => '单位平均成本',
      		  'type'=>'money',
            'editable' => false,
            'in_list' => true,
            'default_in_list'=>true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'order' => 8,
            'orderby'=>false,
          ),
          'inventory_cost' =>
          array (
            'label' => '商品成本',
            'editable' => false,
            'in_list' => true,
            'default_in_list'=>true,
            'filtertype' => 'normal',
            'filterdefault' => true,
            'orderby'=>false,
            'order' => 9,
      			'type'=>'money',
          ),
          'brand' => array (
              'type' => 'table:brand@ome',
              'pkey' => true,
              'label' => '品牌',
              'width' => 110,
              'editable' => false,
              'in_list' => true,
              'default_in_list' => true,
              'filtertype' => 'normal',
              'filterdefault' => true,              
              'order' => 2,
              'orderby'=>false,
              'realtype' => 'varchar(200)',
              'panel_id' => 'costselect_finder_top',
          ),
          /***
           * 库存成本中删除销售物料编码
           * 
          'goods_bn' => array (
              'type' => 'varchar(50)',
              'required' => true,
              'default' => 0,
              'label' => '商品编号',
              'width' => 120,
              #'searchtype' => 'has',
              'editable' => true,
              'filtertype' => 'normal',
              'filterdefault' => 'true',
              'in_list' => true,
              'default_in_list' => true,
              'order' => 3,
              'orderby'=>false,
              'realtype' => 'varchar(50)',
              'panel_id' => 'costselect_finder_top',
          ),
          **/
          'goods_specinfo' => array (
              'type' => 'table:goods_type@ome',
              'pkey' => true,
              'label' => '商品规格',
              'width' => 110,
              'editable' => false,
              'in_list' => true,
              'default_in_list' => true,
              'order' => 6, 
              'orderby'=>false,
              'realtype' => 'varchar(200)',
          ),
          'type_id' => array (
              'type' => 'table:goods_type@ome',
              'pkey' => true,
              'label' => '商品类型',
              'width' => 110,
              'editable' => false,
              'in_list' => true,
              'default_in_list' => true,
              'filtertype' => 'normal',
              'filterdefault' => true,
              'order' => 1,
              'orderby'=>false,
              'realtype' => 'varchar(200)',
              'panel_id' => 'costselect_finder_top',
          ),
          'branch_id' => array (
              'type' => 'table:branch@ome',
              'pkey' => true,
              'label' => '仓库',
              'width' => 110,
              'editable' => false,
              'in_list' => true,
              'default_in_list' => true,
              'filtertype' => 'normal',
              'filterdefault' => true,
              'order' => 10,
              'orderby'=>false,
              'panel_id' => 'costselect_finder_top',
              #'realtype' => 'varchar(200)',
          ),
        'total_unit_cost' =>
            array (
                'label' => '总仓单位平均成本',
                'type'=>'money',
                'editable' => false,
                'in_list' => false,
                'default_in_list'=>false,
                'filtertype' => 'normal',
                'filterdefault' => true,
                'order' => 8,
                'orderby'=>false,
            ),
        ),
       // 'engine' => 'innodb',
        //'version' => '$Rev:  $',
      );
      return $db['branch_product'];
  }
}