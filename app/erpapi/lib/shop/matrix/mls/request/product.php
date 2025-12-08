<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * @desc
 * @author: jintao
 * @since: 2016/8/25
 */
class erpapi_shop_matrix_mls_request_product extends erpapi_shop_request_product {

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
}