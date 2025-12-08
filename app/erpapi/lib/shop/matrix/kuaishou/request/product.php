<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_kuaishou_request_product extends erpapi_shop_request_product
{
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

        $title = "获取店铺[" . $this->__channelObj->channel['name'] . ']商品';

        $result = $this->__caller->call(SHOP_GET_ITEMS_LIST_RPC, $param, array(), $title, $timeout);
        if ($result['res_ltype'] > 0) {
            for ($i = 0; $i < 3; $i++) {
                $result = $this->__caller->call(SHOP_GET_ITEMS_LIST_RPC, $param, array(), $title, $timeout);
                if ($result['res_ltype'] == 0) {
                    break;
                }
            }
        }
        if ($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }
        return $result;
    }

    /**
     * 获取ItemStore
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function getItemStore($data) {
        $timeout = 20;
        $param   = array(
            'item_id'   => $data['shop_iid'],
            //'relSkuId'   => $data['shop_product_bn'],
        );

        $title = "获取店铺[" . $this->__channelObj->channel['name'] . ']商品库存';

        $result = $this->__caller->call(SHOP_ITEM_SKU_LIST, $param, array(), $title, $timeout, $data['shop_product_bn']);
        if ($result['res_ltype'] > 0) {
            for ($i = 0; $i < 3; $i++) {
                $result = $this->__caller->call(SHOP_ITEM_SKU_LIST, $param, array(), $title, $timeout, $data['shop_product_bn']);
                if ($result['res_ltype'] == 0) {
                    break;
                }
            }
        }
        if ($result['data']) {
            $data = json_decode($result['data'], true);
            is_array($data) && $result['data'] = $data['data'];
        }
        return $result;
    }

    /**
     * format_stocks
     * @param mixed $stocks stocks
     * @return mixed 返回值
     */
    public function format_stocks($stocks){
        $skuObj = app::get('inventorydepth')->model('shop_skus');
        
        $shop_id = $this->__channelObj->channel['shop_id'];
        
        //bns
        $bns = array();
        foreach ($stocks as $key => $val)
        {
            $product_bn = trim($val['bn']);
            
            $bns[$product_bn] = $val;
        }
        
        //按店铺+货号查询
        $tempList = $skuObj->getList('shop_iid,shop_product_bn', array('shop_id'=>$shop_id, 'shop_product_bn'=>array_keys($bns)));
        if(empty($tempList)){
            return false;
        }

        // 按shop_iid分组
        $tempList2 = [];
        foreach ($tempList as $value) {
            $tempList2[$value['shop_iid']][$value['shop_product_bn']] = $value['shop_product_bn'];
        }
        unset($tempList);

        $skuStore = [];
        foreach ($tempList2 as $shop_iid => $value) {

            $rs = $this->getItemStore(['shop_iid' => $shop_iid]);
            if(is_array($rs['data']) && is_array($rs['data']['skuList'])) {
                foreach ($rs['data']['skuList'] as $v) {
                    if (in_array($v['skuNick'], $value)) {
                        $skuStore[$v['skuNick']] = $v['skuStock'];
                    }
                }
            }
        }

        $itemStocks = [];
        foreach ($skuStore as $key => $value) {
            $tmp = $bns[$key];
            if (!isset($tmp)) {
                continue;
            }

            $tmp['shop_store'] = $value;
            $tmp['inc_quantity'] = $tmp['quantity'] - $value;
            $tmp['quantity_type'] = 'inc';
            $itemStocks[] = $tmp;
        }
        return $itemStocks;
    }
}
