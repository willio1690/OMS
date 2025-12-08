<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2015-11-17
 * @describe 特殊订单条件
 */
class brush_mdl_farm extends dbeav_model{

    /**
     * modifier_shop_ids
     * @param mixed $col col
     * @return mixed 返回值
     */

    public function modifier_shop_ids($col){
        $arrShopName = app::get('ome')->model('shop')->getList('name', array('shop_id|in' => explode(',', $col)));
        $strShopName = '';
        foreach($arrShopName as $val){
            $strShopName .= $val['name'] . ',';
        }
        return trim($strShopName, ',');
    }

    /**
     * modifier_mark_type
     * @param mixed $col col
     * @return mixed 返回值
     */
    public function modifier_mark_type($col){
        $arrMarkType = kernel::single('ome_order_func')->order_mark_type();
        return $col ? "<img src='{$arrMarkType[$col]}' width='20'height='20'>" : '';
    }
}
?>