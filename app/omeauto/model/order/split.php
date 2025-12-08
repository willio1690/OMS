<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeauto_mdl_order_split extends dbeav_model{
    protected $split_type = array(
        'storemax' => '按库存就全拆',
        // 'goodstype' => '商品类型数量拆',
        'sku' => '单商品拆',
        'skuweight' => '按商品重量拆',
        'skucategory' => '按商品品类拆',
        'skuvolume' => '按商品体积拆',
        'branchgroup' => '按仓库分组拆',
        'virtualsku' => '按虚拟商品拆,虚拟商品自动发货',
        'skuchannel' => '按京东开普勒商品渠道ID拆',
        'oid' => '按京东子订单拆',
        'orderhost' => '按达人信息拆',
    );

    protected $batchConfirmSplitType = array('storemax', 'sku', 'skuweight','skucategory', 'skuvolume', 'oid');

    public function getBatchConfirmSplitType() {
        return $this->batchConfirmSplitType;
    }

    public function getSplitType() {
        return $this->split_type;
    }

    public function modifier_split_type($col) {
        return $this->split_type[$col] ? $this->split_type[$col] : $col;
    }
}