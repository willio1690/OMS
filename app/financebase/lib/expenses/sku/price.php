<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2020/11/25 11:06:02
 * @describe: 按价值权重费用拆分
 * ============================
 */
class financebase_expenses_sku_price extends financebase_expenses_sku_abstract {
    protected $failMsg = 'sku实付金额缺失'; //失败原因

    protected function _getPorthValue($skuList) {
        $needSaleItems = true;
        $porth = array();
        foreach ($skuList['sku'] as $v) {
            if($v['divide_order_fee']) {
                $needSaleItems = false;
                $porth[$v['bm_id']] = $v['divide_order_fee'];
            }
        }
        if(!$needSaleItems) {
            return $porth;
        }
        $saleFilter = array(
            'sale_time|bthan' => strtotime(date('Y-m-1')),
            'sale_time|sthan' => strtotime(date('Y-m-d 23:59:59')),
            'shop_id' => $skuList['bill']['shop_id']
        );
        $sales = app::get('ome')->model('sales')->getList('sale_id', $saleFilter);
        if(empty($sales)) {
            return array();
        }
        $sql = "select product_id, sum(sales_amount) as sales_all from sdb_ome_sales_items
                    where sale_id in('".implode("','", array_map('current', $sales))."')
                        and product_id in('".implode("','", $this->originalSkuId)."')
                    group by product_id";
        $saleItems = kernel::database()->select($sql);
        if(empty($saleItems)) {
            return array();
        }
        $porth = array();
        foreach ($saleItems as $v) {
            if($this->originalSkuCombinationItems[$v['product_id']]) {
                $tmpCI = $this->originalSkuCombinationItems[$v['product_id']];
                $options = array (
                    'part_total'  => $v['sales_all'],
                    'part_field'  => 'money',
                    'porth_field' => $tmpCI['porth'],
                );
                $items = kernel::single('ome_order')->calculate_part_porth($tmpCI['items'], $options);
                foreach ($items as $iv) {
                    $porth[$iv['bm_id']] += $iv['money'];
                }
            } else {
                $porth[$v['product_id']] += $v['sales_all'];
            }
        }
        return $porth;
    }
}