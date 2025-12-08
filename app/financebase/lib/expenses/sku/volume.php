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
 * @describe: 按体积权重费用拆分
 * ============================
 */
class financebase_expenses_sku_volume extends financebase_expenses_sku_abstract {
    protected $failMsg = 'sku长宽高缺失'; //失败原因

    protected function _getPorthValue($skuList) {
        $bmIds = array();
        $bmNum = array();
        foreach ($skuList['sku'] as $v) {
            $bmIds[] = $v['bm_id'];
            $bmNum[$v['bm_id']] = $v['nums'];
        }
        $bmExt = app::get('material')->model('basic_material_ext')->getList('bm_id,length,width,high', array('bm_id'=>$bmIds));
        $porth = array();
        foreach ($bmExt as $v) {
            $volume = $v['length'] * $v['width'] * $v['high'];
            if($volume) {
                $porth[$v['bm_id']] = bcmul($volume, $bmNum[$v['bm_id']], 2);
            }
        }
        return $porth;
    }
}