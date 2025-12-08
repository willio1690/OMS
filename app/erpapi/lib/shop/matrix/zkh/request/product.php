<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 库存同步处理
 * Class erpapi_shop_matrix_zkh_request_product
 */
class erpapi_shop_matrix_zkh_request_product extends erpapi_shop_request_product
{
    protected function getUpdateStockApi()
    {
        return SHOP_UPDATE_BIDDING_QUANTITY;
    }
    
    /**
     * 回传库存
     * 
     * @param array $stocks
     * @param string $dorelease
     * @return array
     */

    public function updateStock($stocks, $dorelease = false)
    {
        $rs = array('rsp' => 'fail', 'msg' => '', 'data' => '');
        if (!$stocks) {
            $rs['msg'] = 'no stocks';
            return $rs;
        }
        
        $shop_id = $this->__channelObj->channel['shop_id'];
        $skuIds  = array_keys($stocks);
        sort($stocks);
        $logData = ['list_quantity' => '', 'original' => json_encode($stocks, JSON_UNESCAPED_UNICODE)];
//        $newStocks = $stocks;
//        foreach ($stocks as $key => $value) {
//            if($value['regulation']) {
//                unset($stocks[$key]['regulation']);
//            }
//        }
        //格式化库存参数
        $stocks = $this->format_stocks($stocks);
        if (!$stocks) {
            return $this->error('没有可回写的库存数据', '102');
        }
        
        //保存库存同步管理日志
        $oApiLogToStock = kernel::single('ome_api_log_to_stock');
        $stocks         = $oApiLogToStock->save($stocks, $shop_id);
        $params         = $this->_getUpdateStockParams($stocks);
        $logData        = array_merge($logData, $params);
        
        //api_name
        $stockApi = $this->getUpdateStockApi($stocks);
        $callback = array(
            'class'  => get_class($this),
            'method' => 'updateStockCallback',
            'params' => array(
                'shop_id'        => $shop_id,
                'request_params' => $params,
                'api_name'       => $stockApi
            )
        );
        
        $title     = '批量更新店铺(' . $this->__channelObj->channel['name'] . ')的库存(共' . count($stocks) . '个)';
        $primaryBn = $this->__channelObj->channel['shop_bn'] . 'UpdateStock';
        $return    = $this->__caller->call($stockApi, $params, $callback, $title, 10, $primaryBn, true, '', $logData);
        
        if ($return !== false) {
            if ($dorelease === true) {
                if ($skuIds && app::get('inventorydepth')->is_installed()) {
                    app::get('inventorydepth')->model('shop_adjustment')->update(array('release_status' => 'running'), array('id' => $skuIds));
                }
            }
            app::get('ome')->model('shop')->update(array('last_store_sync_time' => time()), array('shop_id' => $shop_id));
        }
        
        $rs['rsp'] = 'success';
        
        return $rs;
    }
    
    /**
     * 震坤行库存更新格式化参数
     * @param $stockList
     * @param $warehouseList
     * @return array
     * @author db
     * @date 2023-12-19 5:05 下午
     */
    protected function _getUpdateStockParams($stockList)
    {
        $branchList    = app::get('ome')->model('branch')->getList('branch_id,branch_bn', ['b_type' => '1']);
        $branch_ids    = array_column($branchList, 'branch_id');
        $relationList  = app::get('ome')->model('branch_relation')->getList('branch_id,relation_branch_bn', ['branch_id' => $branch_ids, 'type' => 'zkh']);
        $relationList  = array_column($relationList, null, 'branch_id');
        $warehouseList = [];
        foreach ($branchList as $k => $branch) {
            $info                                                      = $relationList[$branch['branch_id']];
            $warehouseList[$branch['branch_bn']]['branch_bn']          = $branch['branch_bn'];
            $warehouseList[$branch['branch_bn']]['relation_branch_bn'] = $info ? $info['relation_branch_bn'] : '';
        }
        
        $salesMaterialMdl      = app::get('material')->model('sales_material');
        $salesBasicMaterialMdl = app::get('material')->model('sales_basic_material');
        
        //查询销售物料信息
        $salesMaterialBn = array_column($stockList, 'bn');
        $field           = 'sm_id,sales_material_name,sales_material_bn, sales_material_type,shop_id';
        $products        = $salesMaterialMdl->getList($field, array('sales_material_bn' => $salesMaterialBn));
        
        //获取震坤行物料号
        $shopSkusList = app::get('inventorydepth')->model('shop_skus')->getList('shop_sku_id,shop_product_bn', ['shop_product_bn' => $salesMaterialBn, 'shop_type' => 'zkh']);
        $newShopSkusList = [];
        foreach($shopSkusList as $k => $val){
            $newShopSkusList[$val['shop_product_bn']][] = $val['shop_sku_id'];
        }
        
        $productBn = array_column($products, null, 'sales_material_bn');
        $newStocks = array();
        foreach ($stockList as $key => $val) {
            $newQuantity = $val['quantity'];
            $log_id = $val['log_id'];
            
            if ($val['regulation']) {
                $bnStockList       = $val['regulation']['detail']['可售库存']['info']['basic'];
                $salesMaterialInfo = $productBn[$val['bn']];
                $newBranchList     = array();
                foreach ($bnStockList as $stK => $stV) {
                    //普通商品
                    if ($salesMaterialInfo['sales_material_type'] == 1) {
                        $branchList = $stV['info']['detail'];
                        foreach ($branchList as $branchBn => $branchV) {
                            $quantity       = $branchV['库存'] - $branchV['仓库预占'] - $branchV['指定仓预占'];
                            $warehouse_code = $warehouseList[$branchBn]['relation_branch_bn'];
                            $shopSkuIds    = $newShopSkusList[$val['bn']];
                            foreach($shopSkuIds as $shop_sku_id){
                                if ($warehouse_code && $shop_sku_id) {
                                    $stockItem                              = [
                                        'bn'        => $shop_sku_id,
                                        'warehouse' => $warehouse_code,
                                        'quantity'  => intval($newQuantity),
                                        'log_id'    => $log_id,
                                    ];
                                    $newStocks[$stK . '' . $warehouse_code . '' . $shop_sku_id] = $stockItem;
                                }
                            }
                        }
                    } else {
                        if ($productBn[$val['bn']]['sales_material_type'] == 2) {
                            //组合商品
                            $pkgItems     = kernel::single('material_sales_material')->getBasicMBySalesMId($salesMaterialInfo['sm_id']);
                            $items        = array_column($pkgItems, null, 'material_bn');
                            $materialInfo = $items[$stK];
                            
                            $branchList = $stV['info']['detail'];
                            
                            $pkgItemsSmId = $salesBasicMaterialMdl->getList('sm_id', array('bm_id' => $materialInfo['bm_id']));
                            //查询对应的普通销售物料
                            $pkgItemsSmList = $salesMaterialMdl->getList('sales_material_bn', array('sales_material_type' => 1, 'sm_id' => array_column($pkgItemsSmId, 'sm_id')));
                            
                            foreach ($pkgItemsSmList as $smKey => $smBn) {
                                foreach ($branchList as $branchBn => $branchV) {
                                    $quantity       = $branchV['库存'] - $branchV['仓库预占'] - $branchV['指定仓预占'];
                                    $warehouse_code = $warehouseList[$branchBn]['relation_branch_bn'];
                                    $shopSkuIds    = $newShopSkusList[$val['bn']];
                                    foreach($shopSkuIds as $shop_sku_id){
                                        if ($warehouse_code && $shop_sku_id) {
                                            $stockItem                              = [
                                                'bn'        => $shop_sku_id,
                                                'warehouse' => $warehouse_code,
                                                'quantity'  => intval($newQuantity),
                                                'log_id'    => $log_id,
                                            ];
                                            $newStocks[$smBn['sales_material_bn'] . '' . $warehouse_code . '' . $shop_sku_id] = $stockItem;
                                        }
                                    }
                                }
                            }
                        }
                        //多选一
                        if ($productBn[$val['bn']]['sales_material_type'] == 5) {
                            $branchList = $stV['info']['detail'];
                            foreach ($branchList as $branchBn => $branchV) {
                                $warehouse_code = $warehouseList[$branchBn]['relation_branch_bn'];
                                if ($warehouse_code) {
                                    if ($newBranchList[$branchBn]) {
                                        $newBranchList[$branchBn]['库存']    += $branchV['库存'];
                                        $newBranchList[$branchBn]['仓库预占']  += $branchV['仓库预占'];
                                        $newBranchList[$branchBn]['指定仓预占'] += $branchV['指定仓预占'];
                                    } else {
                                        $newBranchList[$branchBn] = $branchV;
                                    }
                                }
                            }
                            unset($branchList);
                        }
                    }
                }
                if ($newBranchList) {
                    foreach ($newBranchList as $branchBn => $branchV) {
                        $quantity       = $branchV['库存'] - $branchV['仓库预占'] - $branchV['指定仓预占'];
                        $warehouse_code = $warehouseList[$branchBn]['relation_branch_bn'];
                        $shopSkuIds    = $newShopSkusList[$val['bn']];
                        foreach($shopSkuIds as $shop_sku_id){
                            if ($warehouse_code && $shop_sku_id) {
                                $stockItem                              = [
                                    'bn'        => $shop_sku_id,
                                    'warehouse' => $warehouse_code,
                                    'quantity'  => intval($newQuantity),
                                    'log_id'    => $log_id,
                                ];
                                $newStocks[$val['bn'] . '' . $warehouse_code . '' . $shop_sku_id] = $stockItem;
                            }
                        }
                    }
                }
            }
        }
        
        $listQuantity = array_values($newStocks);
        
        //待更新库存BN
        $params = array(
            'list_quantity' => json_encode($listQuantity),
        );
        return $params;
    }
    
    /**
     * 下载全部商品
     */
    public function itemsAllGet($filter, $offset = 0, $limit = 100)
    {
        $timeout = 20;
        $param   = array(
            'pageNo'   => $offset,
            'pageSize' => $limit,
        );
        
        $param = array_merge((array)$param, (array)$filter);
        
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
}