<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author ykm 2016-04-29
 * @describe 处理店铺商品相关类
 */
class erpapi_shop_matrix_taobao_request_product extends erpapi_shop_request_product {

    protected function getUpdateStockApi() {
        switch($this->__channelObj->channel['business_type']){
            case 'fx':
                $api_name = SHOP_UPDATE_FENXIAO_ITEMS_QUANTITY_LIST_RPC;
                break;
            default:
                $api_name = SHOP_UPDATE_ITEMS_QUANTITY_LIST_RPC;
                break;
        }

        return $api_name;
    }

    /**
     * format_stocks
     * @param mixed $stocks stocks
     * @return mixed 返回值
     */

    public function format_stocks($stocks)
    {
        foreach ($stocks as $k => $v) {
            if (isset($v['inc_quantity'])) {
                $stocks[$k]['quantity_type'] = 'inc'; // 增量库存标识
            }
        }
        return $stocks;
    }
}