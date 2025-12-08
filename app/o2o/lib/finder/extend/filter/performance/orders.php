<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_finder_extend_filter_performance_orders{

     function get_extend_colums(){
        $storeObj = app::get('o2o')->model('store');

        $branch_arr   = $storeObj->getList('branch_id, name',array('b_type'=>'2'), 0, -1);
        $branch_list = array();
        foreach($branch_arr as $branch){
            $branch_list[$branch['branch_id']] = $branch['name'];
        }

        $dlyCorpObj    = app::get('ome')->model('dly_corp');
        $corp_arr      = $dlyCorpObj->getList('corp_id, name', array('d_type'=>'2'), 0, -1);
        foreach($corp_arr as $corp){
            $corp_list[$corp['corp_id']] = $corp['name'];
        }

        $db['performance_orders']=array (
            'columns' => array (
               'branch_id' => array (
                    'type' => $branch_list,
                    'editable' => false,
                    'label' => '分派门店',
                    'width' => 110,
                   'filtertype' => 'normal',
                    'filterdefault' => true,
                    'in_list' => true,
                    'panel_id' => 'performance_orders_finder_top',
                ),
                'logi_id' => array(
                  'type' => $corp_list,
                  'comment' => '履约方式',
                  'editable' => false,
                  'label' => '履约方式',
                  'filtertype' => 'normal',
                  'filterdefault' => true,
                  'panel_id' => 'performance_orders_finder_top',
                ),
            )
        );
        return $db;
     }

}
