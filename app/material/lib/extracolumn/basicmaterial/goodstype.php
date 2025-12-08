<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 销售物料字段包装单位
 * @author xiayuanjun@shopex.cn
 * @version 1.0
 */
class material_extracolumn_basicmaterial_goodstype extends desktop_extracolumn_abstract implements desktop_extracolumn_interface{

    protected $__pkey = 'bm_id';
    protected $__extra_column = 'column_goods_type';

    /**
     *
     * 获取基础物料字段售价
     * @param $ids
     * @return array $tmp_array关联数据数组
     */
    public function associatedData($ids){
        //print_r($ids);
        $extObj = app::get('material')->model('basic_material_ext');
        $materialLists = $extObj->getList('cat_id,bm_id',array('bm_id' => $ids));
        $type_ids=array_column($materialLists,'cat_id');
        //根据发货单ids获取相应的信息
        $extObj = app::get('ome')->model('goods_type');
        $lists = $extObj->getList('name,type_id',array('type_id' => $type_ids));
        $tmp_goods_array= array();
        foreach($lists as $k=>$row){
            $tmp_goods_array[$row['type_id']] = $row['name'];
        }
        $tmp_array=array();
        //print_r($type_ids);
        foreach($materialLists as $key=>$val){
            $tmp_array[$val['bm_id']]=$tmp_goods_array[$val['cat_id']];
        }
        return $tmp_array;
    }

}
