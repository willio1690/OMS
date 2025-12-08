<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class crm_mdl_gift_rule extends dbeav_model{
    
    /**
     * table_name
     * @param mixed $real real
     * @return mixed 返回值
     */
    public function table_name($real = false){
        if($real){
           $table_name = 'sdb_ome_gift_rule';
        }else{
           $table_name = 'gift_rule';
        }
        return $table_name;
    }

    /**
     * 获取_schema
     * @return mixed 返回结果
     */
    public function get_schema(){
        return app::get('ome')->model('gift_rule')->get_schema();
    }

    /**
     * modifier_shop_ids
     * @param mixed $col col
     * @param mixed $list list
     * @return mixed 返回值
     */
    public function modifier_shop_ids($col, $list) {
        if($col == '_ALL_') {
            return '所有店铺';
        }
        $col = explode(',', $col);
        static $shopName = array();
        if(!$shopName) {
            $shopId = array();
            foreach ($list as $val) {
                $shopList = explode(',', $val['shop_ids']);
                foreach ($shopList as $k => $v) {
                    $shopId[$v] = $v;
                }
            }
            $shop = app::get('ome')->model('shop')->getList('shop_id, name',array('shop_id'=>$shopId));
            foreach ($shop as $v) {
                $shopName[$v['shop_id']] = $v['name'];
            }
        }
        $str = '';
        foreach ($col as $k => $v) {
            $str .= $shopName[$v] . ';';
        }
        return $str;
    }

    function _filter($filter,$tableAlias=null,$baseWhere=array ())
    {
        if ($filter['shop_id']) {
            $baseWhere[] = ' (shop_ids="_ALL_" OR FIND_IN_SET("'.$filter['shop_id'].'",shop_ids)) ';
            unset($filter['shop_id']);
        }

        if (isset($filter['ac_status'])) {
            
            switch ($filter['ac_status']) {
                case '0':
                    $baseWhere[] = ' start_time > '.time();
                    break;
                case '1':
                    $baseWhere[] = ' start_time <= '.time().' AND end_time > '.time();
                    break;
                case '2':
                    $baseWhere[] = ' end_time <= '.time();
                    break;
            }
            unset($filter['ac_status']);
        }
        if(!isset($filter['disable'])) {
            $filter['disable'] = 'false';
        }
        return parent::_filter($filter, $tableAlias, $baseWhere);
    }
}