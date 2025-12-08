<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_finder_map_goods {

    public $addon_cols = 'shop_id,bind,shop_type';

    public $column_edit = "操作";
    public $column_edit_width = "170";
    public $column_edit_order = 10;
    /**
     * column_edit
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_edit($row){

    }

    public $column_bind = '销售物料类型';
    public $column_bind_order = 70;
    public $column_bind_width = "70";
    /**
     * column_bind
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function column_bind($row){
        if($row[$this->col_prefix.'bind'] == '1'){
            return '组合';
        }elseif ($row[$this->col_prefix.'bind'] == '2'){
            return '多选一';
        }else{
            return '普通';
        }
    }

    public $column_shop_name ='店铺名';
    public $column_shop_name_order = 20;
    public $column_shop_name_width = "150";
    /**
     * column_shop_name
     * @param mixed $row row
     * @param mixed $list list
     * @return mixed 返回值
     */
    public function column_shop_name($row, $list){
        $shop = $this->__getShop($row, $list);
        return $shop['name'];
    }

    public $column_shop_type ='店铺类型';
    public $column_shop_type_order = 30;
    public $column_shop_type_width = "100";
    /**
     * column_shop_type
     * @param mixed $row row
     * @param mixed $list list
     * @return mixed 返回值
     */
    public function column_shop_type($row, $list){
        $shop = $this->__getShop($row, $list);
        return ome_shop_type::shop_name($shop['shop_type']);
    }

    private function __getShop($row, $list) {
        static $shop = array();
        if(empty($shop)) {
            $shopId = array();
            foreach($list as $val) {
                $shopId[$val[$this->col_prefix . 'shop_id']] = $val[$this->col_prefix . 'shop_id'];
            }
            $shopData = app::get('ome')->model('shop')->getList('*', array('shop_id' => $shopId));
            foreach($shopData as $value) {
                $shop[$value['shop_id']] = $value;
            }
        }
        return $shop[$row[$this->col_prefix . 'shop_id']] ? $shop[$row[$this->col_prefix . 'shop_id']] : array();
    }
}
