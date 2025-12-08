<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_finder_ome_extend_filter_cod{
    function get_extend_colums(){
        //物流公司列表
        $logi_ids = array();
        $dlyCorpObj = app::get('ome')->model('dly_corp');
        $dlyCorps = $dlyCorpObj->getList('corp_id,name');
        foreach($dlyCorps as $dlyCorp){
            $logi_ids[$dlyCorp['corp_id']] = $dlyCorp['name'];
        }
        //扩展高级筛选项
        $db['ome_cod']=array (
            'columns' => array (
                'order_bn' => array (
                    'type' => 'varchar(30)',
                    'label' => '订单号',
                    'width' => 130,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'logi_id' => array (
                    'type' => $logi_ids,
                    'comment' => '物流公司ID',
                    'editable' => false,
                    'label' => '物流公司',
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'panel_id' => 'delivery_finder_top',
                ),
            )
        );
        return $db;
    }
}