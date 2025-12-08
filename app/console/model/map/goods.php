<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_mdl_map_goods extends dbeav_model{

    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real = false){
        if($real){
           $table_name = 'sdb_inventorydepth_shop_skus';
        }else{
           $table_name = 'shop_skus';
        }
        return $table_name;
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        $schema = app::get('inventorydepth')->model('shop_skus')->get_schema();
        if($schema['columns']['shop_id']) {
            $schema['columns']['shop_id']['label'] = '店铺名';
            $schema['columns']['shop_id']['filterdefault'] = true;
            $schema['columns']['shop_id']['filtertype'] = 'normal';
            $schema['columns']['shop_id']['searchtype'] = 'has';
        }
        return $schema;
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null) {
        $shopFilter = array('node_type' => 'taobao');
        if($filter['shop_id']) {
            $shopFilter['name|has'] = $filter['shop_id'];
        }
        $shop = app::get('ome')->model('shop')->getList('shop_id', $shopFilter);
        $filter['shop_id'] = array('-1');
        foreach($shop as $val) {
            $filter['shop_id'][] = $val['shop_id'];
        }
        return parent::_filter($filter,$tableAlias=null,$baseWhere=null);
    }
}
