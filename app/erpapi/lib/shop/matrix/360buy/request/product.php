<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @author sunjing@shopex.cn
 * @describe 京东商品接口
 */
class erpapi_shop_matrix_360buy_request_product extends erpapi_shop_request_product {

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
        $timeout = 10;
        $param = array(
            'page_no'        => $offset,
            'page_size'      => $limit,
            'fields'         => 'iid,outer_id,bn,num,title,default_img_url,modified,detail_url,approve_status,skus,price,barcode ',
        );
        $param = array_merge((array)$param,(array)$filter);
        $title = "获取店铺(" . $this->__channelObj->channel['name'] .')商品';
        $result = $this->__caller->call(SHOP_GET_ITEMS_VALID_RPC,$param,array(),$title,$timeout);
        if ($result['res_ltype'] > 0) {
            for ($i=0;$i<3;$i++) {
                $result = $this->__caller->call(SHOP_GET_ITEMS_VALID_RPC,$param,array(),$title,$timeout);
                if ($result['res_ltype'] == 0) {
                    break;
                }
            }
        }
        if($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }
        return $result;
    }

    #根据IID获取单个商品
    /**
     * item_get
     * @param mixed $iid ID
     * @return mixed 返回值
     */
    public function item_get($iid) {
        $title = '单拉商品[' . $iid . ']';
        $params = array(
            'iid' => $iid,
        );
        for ($i=0; $i<3; $i++) {
            $result = $this->__caller->call(SHOP_ITEM_I_GET,$params,array(),$title, 10, $iid);
            if ($result['rsp'] == 'succ') break;
        }

        if ($result['rsp'] != 'succ' || !$result['data']) return array();

        if ($result['data']) $result['data'] = @json_decode($result['data'],true);

        return $result;
    }


    /**
     * item_sku_get
     * @param mixed $sku sku
     * @return mixed 返回值
     */
    public function item_sku_get($sku) {
        $title = '单拉商品SKU[' . ($sku['sku_id'] ? $sku['sku_id'] : $sku['iid']) . ']';
        $params = array(
            'sku_id' => $sku['sku_id'],
            'iid' => $sku['iid'],
            'num_iid' => $sku['iid'],
        );
        if ($sku['seller_uname']) $params['seller_uname'] = $sku['seller_uname'];
        for ($i=0; $i<3; $i++) {
            $result = $this->__caller->call(SHOP_ITEM_SKU_I_GET,$params,array(),$title, 10, ($sku['sku_id'] ? $sku['sku_id'] : $sku['iid']));
            if ($result['rsp'] == 'succ') break;
        }
        if ($result['rsp'] != 'succ' || !$result['data']) return array();

        if ($result['data']) $result['data'] = @json_decode($result['data'],true);

        //if ($result['data']['sku']) $result['data']['sku'] = @json_decode($result['data']['sku'],true);

        return $result;
    }
}
