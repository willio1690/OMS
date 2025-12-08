<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 震坤行
 */
class inventorydepth_service_shop_zkh extends inventorydepth_service_shop_common
{
    public $customLimit = 10;
    
    function __construct(&$app)
    {
        $this->app = $app;
    }
    
    /**
     * 下载全部商品
     */
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
        $itemResult = $result['data'];
        
        //商品总数及分页
        $count = $result['count'];
        
        //数据为空
        if (empty($itemResult)) {
            $this->totalResults = 0;
            return array();
        }
        
        //平台商品总数
        $this->totalResults = intval($count);
        
        //items
        $data = array();
        foreach ($itemResult as $itemKey => $itemVal) {
            $skuCode = $itemVal['zkhSku'];
            if (empty($skuCode)) {
                continue;
            }
            
            //上下架状态
            $approve_status = ($itemVal['skuStatus'] == '1' ? 'onsale' : 'instock');
            
            //sku信息
            $skuList          = array();
            $skuList['sku'][] = array(
                'outer_id' => $itemVal['supplierSkuNo'], //供应商物料号
                'sku_id'   => $itemVal['zkhSku'],
                'title'    => $itemVal['skuName'], //货品名称
            );
            
            //spu信息
            $data[] = array(
                'outer_id'       => $itemVal['supplierSkuNo'] ?? $itemVal['zkhSku'], //spu商品编号
                'iid'            => $itemVal['supplierSkuNo'] ?? $itemVal['zkhSku'],
                'title'          => $itemVal['skuName'], //商品名称
                'approve_status' => $approve_status, //上下架状态
                'simple'         => 'false',
                'skus'           => $skuList,
            );
        }
        
        unset($result);
        
        return $data;
    }
}