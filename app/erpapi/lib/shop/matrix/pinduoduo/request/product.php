<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author wangjianjun 2019-2-27
 * @describe 处理店铺商品相关类
 */
class erpapi_shop_matrix_pinduoduo_request_product extends erpapi_shop_request_product {

    #实时下载店铺商品
    /**
     * itemsAllGet
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @return mixed 返回值
     */

    public function itemsAllGet($filter,$offset=0,$limit=100)
    {
        $result = parent::itemsAllGet($filter,$offset,$limit);
        // 删除goods_list数据，解决内存溢出问题
        if($result['data'] && isset($result['data']['goods_list'])) {
            $result['data']['goods_list'] = [];
        }
        if ($result['response']) {
        	$result['response'] = '';
        }
        return $result;
    }
}