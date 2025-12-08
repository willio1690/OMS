<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0 
 * @DateTime: 2022/7/7 18:24:44
 * @describe: 类
 * ============================
 */

class erpapi_shop_matrix_tmall_response_maochao_aftersalev2 extends erpapi_shop_matrix_tmall_response_aftersalev2
{
    protected function _formatAddParams($params) {
        $sdf = parent::_formatAddParams($params);
        $extend_field = json_decode($sdf['extend_field'], 1);
        if(is_array($sdf['refund_item_list'])) {
            $itemList = $sdf['refund_item_list']['return_item'];
            foreach ($itemList as $key => $value) {
                $extend_field['items'][$value['item_id']] = $value['oid'];
            }
        }
        $sdf['extend_field'] = json_encode($extend_field, JSON_UNESCAPED_UNICODE);
        return $sdf;
    }

    protected function _formatAddItemList($sdf, $convert = array()) {
        $convert = array(
            'sdf_field'=>'item_id',
            'order_field'=>'shop_goods_id',
            'default_field'=>'item_id'
        );
        return parent::_formatAddItemList($sdf, $convert);
    }
}
