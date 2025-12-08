<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_meituan4bulkpurchasing_request_product extends erpapi_shop_request_product
{
    #实时下载店铺商品
    /**
     * itemsAllGet
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回值
     */
    public function itemsAllGet($filter, $offset = 0, $limit = 100)
    {
        $timeout = 20;
        $param   = array(
            'page_no'   => $offset,
            'page_size' => $limit,
        );
        $param = array_merge((array) $param, (array) $filter);
        $title = "获取店铺(" . $this->__channelObj->channel['name'] . ')商品';
        $rsp   = $this->__caller->call(SHOP_GET_ITEMS_LIST_RPC, $param, array(), $title, $timeout);

        if ($rsp['data']) {
            $data = json_decode($rsp['data'], 1);
            $rsp['data'] = [];
            if (is_array($data)) {
                $rsp['data'] = $data['data'];
            }
        }

        return $rsp;
    }
}