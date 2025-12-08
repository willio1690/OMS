<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


$setting = array(
'site.decimal_digit.count'=>array('type'=>SET_T_ENUM,'default'=>2,'desc'=>'金额运算精度保留位数','options'=>array(0=>'整数取整',1=>'取整到1位小数',2=>'取整到2位小数',3=>'取整到3位小数')),
'site.decimal_type.count'=>array('type'=>SET_T_ENUM,'default'=>1,'desc'=>'金额运算精度取整方式','options'=>array('1'=>'四舍五入','2'=>'向上取整','3'=>'向下取整')),
'site.decimal_digit.display'=>array('type'=>SET_T_ENUM,'default'=>2,'desc'=>'金额显示保留位数','options'=>array(0=>'整数取整',1=>'取整到1位小数',2=>'取整到2位小数',3=>'取整到3位小数')),//WZP
'site.decimal_type.display'=>array('type'=>SET_T_ENUM,'default'=>1,'desc'=>'金额显示取整方式','options'=>array('1'=>'四舍五入','2'=>'向上取整','3'=>'向下取整')),
'system.area_depth'=>array('type'=>SET_T_INT,'default'=>'3','desc'=>'地区级数'),
);
