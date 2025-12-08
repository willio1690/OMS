<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_finder_extend_filter_return_product{
    function get_extend_colums(){
        $obj_problem = app::get('ome')->model('return_product_problem');
        $all_problem = $obj_problem->getList('*',array());
        
        $arr_problem = array();
        foreach($all_problem as $v){
            $_key = $v['problem_id'];
            $arr_problem[$_key] = $v['problem_name'];
        }
        
        //平台售后状态
        $reshipLib = kernel::single('ome_reship');
        $platformStatus = $reshipLib->get_platform_status();
        
        //dbschema
        $db['return_product']=array (
            'columns' => array (
                'order_bn' => array (
                    'type' => 'varchar(30)',
                    'label' => '订单号',
                    'width' => 130,
                    'filtertype' => 'textarea',
                    'searchtype' => 'nequal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'problem_id' => array(
                        'type' => $arr_problem,
                        'filtertype' => 'normal',
                        'required' => true,
                        'label' => '售后类型',
                        'editable' => false,
                        'in_list' => true,
                        'default_in_list' => true,
                        'default' => 0,
                        'filterdefault' => true,
                ),
                'shop_id' => array(
                    'type' => 'table:shop@ome',
                    'filtertype' => 'normal',
                    'label' => '店铺',
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'filterdefault' => true,
                ),
                'platform_status' => array(
                    'type' => $platformStatus,
                    'editable' => false,
                    'label' => '平台售后状态',
                    'default' => '',
                    'in_list'  => true,
                    'default_in_list' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                ),
            )
        );
        return $db;
    }
}
