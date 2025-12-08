<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2022/2/22 10:14:59
 * @describe: 门店库存回写
 * ============================
 */
class inventorydepth_offline_queue {

    public function store_update($products, $shop, $options = []) {
        if($options['need_write']) {
            //kernel::single('inventorydepth_stock_pkg')->writeMemory($products); // 门店不需要捆绑商品
            kernel::single('inventorydepth_stock_products')->writeMemory($products);
        }
        // 关联门店
        $bra = $this->getOfflineId($shop['shop_id']);
        if (!$bra) return [false, '缺少门店'];
        if($options['branch_id']) {
            if(!in_array($options['branch_id'], $bra)) {
                return [false, '该门店仓与店铺不存在绑定关系'];
            }
            $bra = [$options['branch_id']];
        }

        // 店铺未开启回写
        $request = kernel::single('inventorydepth_shop')->getStockConf($shop['shop_id']);
        if($request != 'true') return [false, '店铺未开启回写'];
        $stocks = array();
        foreach($products as $product){
            foreach ($bra as $bid) {
                $st = kernel::single('inventorydepth_offline_stock')->getStock($product['sales_material_bn'],$bid,$shop['shop_id']);
                if (!is_array($st)) { continue; }

                $stocks[] = $st;
            }
        }

        if($options['select_stock']) {
            return [true, $stocks];
        }

        kernel::single('inventorydepth_shop')->doStockRequest($stocks,$shop['shop_id']);

        return [true];

    }

    public function getOfflineId($shopId) {
        $off = app::get('ome')->model('shop_onoffline')->getList('off_id', ['on_id'=>$shopId]);
        if(empty($off)) {
            return [];
        }
        $shop = app::get('ome')->model('shop')->getList('shop_bn', ['shop_id'=>array_column($off, 'off_id')]);
        $branch = app::get('ome')->model('branch')->getList('branch_id',['branch_bn' => array_column($shop, 'shop_bn'), 'b_type' => 2,]);
        $branch_id = array_column($branch, 'branch_id');
        return $branch_id;
    }
}