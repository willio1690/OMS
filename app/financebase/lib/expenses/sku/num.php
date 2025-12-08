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
 * @describe: 按数量权重费用拆分
 * ============================
 */
class financebase_expenses_sku_num extends financebase_expenses_sku_abstract {
    protected $failMsg = 'sku数量缺失'; //失败原因

    protected function _getPorthValue($skuList) {
        $porth = array();
        foreach ($skuList['sku'] as $v) {
            $porth[$v['bm_id']] = $v['nums'];
        }
        return $porth;
    }
}