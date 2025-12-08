<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

$db['tbgift_product']=array (
  'columns' =>
  array (
  'product_id'=>
  array(
  'type'=>'mediumint(8)',
  ),
    'bn' =>
    array (
      'type' => 'varchar(200)',

    ),
    'name' =>
    array (
      'type' => 'varchar(200)',
      'required' => true,
      'default' => '',
      ),
  'goods_id'=>
  array(
  'type'=>'table:tbgift_goods',
  ),
  ),
  'comment' => '捆绑商品',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
  );
?>
