<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 华为商品处理
 */
class inventorydepth_service_shop_kuaishou extends inventorydepth_service_shop_common
{
    public $customLimit = 5;

    /**
     * 下载全部
     **/
    public function downloadList($filter, $shop_id, $offset = 0, $limit = 20, &$errormsg = null)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');

        //开始拉取商品
        $result = $shopService->items_all_get($filter, $shop_id, $offset, $limit);
        if ($result === false) {
            $errormsg = $shopService->get_err_msg();
            return false;
        }

        //商品列表
        $itemResult = $result['items']['item'];

        //数据为空
        if (empty($itemResult)) {
            $this->totalResults = 0;
            return array();
        }

        //平台商品总数
        $this->totalResults = intval($result['total']);

        //items
        $data = array();
        foreach ($itemResult as $itemKey => $itemVal) {
            //SKU列表
            $skuArr    = array();
            $goods_num = 0;
            foreach ($itemVal['skuList'] as $k => $v) {
                $skuArr[] = [
                    'num'        => $v['skuStock'],
                    'price'      => $v['skuSalePrice'] / 100,
                    'sku_id'     => $v['kwaiSkuId'],
                    'outer_id'   => $v['skuNick'],
                    'quantity'   => $v['skuStock'],
                    'properties' => $v['specification'],
                    'properties_name' => $v['specification'],
                ];

                $goods_num += $v['skuStock'];
            }

            $data[] = [
                'outer_id'       => '', //商品编码
                'price'          => $itemVal['price'] / 100,
                'num'            => intval($goods_num), //店铺库存
                'iid'            => $itemVal['kwaiItemId'],
                'title'          => $itemVal['details'],
                'approve_status' => $itemVal['shelfStatus'] == '1' ? 'onsale' : 'instock',
                'skus'           => ['sku' => $skuArr],
            ];

        }

        unset($result);

        return $data;
    }
}
