<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 店铺库存回写,RPC调用类
 * 
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_service_shop_stock {

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 库存回写 异步
     *
     * @return void
     * @author 
     **/
    public function items_quantity_list_update($stocks,$shop_id,$dorelease = false)
    {
        # 如果关闭，则不向前端店铺请求
        if ($dorelease === false ) {
            $request = kernel::single('inventorydepth_shop')->getStockConf($shop_id);
            if($request !== 'true') return false;
        }
        
        //天猫优仓
        $isDchain = false;
        $shop_type = '';
        if(app::get('dchain')->is_installed()){
            $shopInfo = app::get('ome')->model('shop')->dump(array('shop_id'=>$shop_id), '*');
        
            $channelInfo = app::get('channel')->model('channel')->db_dump(array('node_id'=>$shopInfo['node_id'], 'channel_type'=>'dchain','disabled'=>'false'), 'channel_id,channel_bn,config');
            $isDchain = ($channelInfo ? true : false);

            $shop_type = $shopInfo['shop_type'];
        }
        
        //天猫优仓库存回写
        if($isDchain){
            $result = array();
            $newStocks = $this->dchainFormatParams($stocks,$channelInfo);
            $params = array_chunk($newStocks, 30);
            foreach ($params as $v) {
                $rs = kernel::single('erpapi_router_request')->set('dchain', $shop_id)->product_updateStock($v, $dorelease);
                if ($rs['rsp'] != 'running') {
                    $result = array_merge_recursive($result, (array)$rs['data']);
                }
            }
        }else{
            //回写库存
            $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_updateStock($stocks, $dorelease);
        }
        
        return $result;
    }
    
    /**
     * 优仓库存更新格式化参数
     * @Author: xueding
     * @Vsersion: 2022/7/11 下午8:38
     * @param $stocks
     * @param $channelInfo
     */
    public function dchainFormatParams($stocks, $channelInfo)
    {
        $logicStockLib         = kernel::single('inventorydepth_logic_stock');
        $salesMaterialMdl      = app::get('material')->model('sales_material');
        $salesBasicMaterialMdl = app::get('material')->model('sales_basic_material');
        $shopConfig            = @unserialize($channelInfo['config']);
        
        //查询销售物料信息
        $shopId    = $shopConfig['shop_id'];
        $salesMaterialBn = array_column($stocks, 'bn');
        $delivery_mode = app::get('ome')->model('shop')->db_dump(['shop_id'=>$shopId], 'delivery_mode')['delivery_mode'];
        if($delivery_mode == 'shopyjdf') {
            $products = app::get('dealer')->model('sales_material')->getList('sm_id,sales_material_name,sales_material_bn,sales_material_type,shop_id',array('sales_material_bn'=>$salesMaterialBn, 'shop_id'=>$shopId));
        } else {
            $field           = 'sm_id,sales_material_name,sales_material_bn, sales_material_type,shop_id,class_id';
            $products        = $salesMaterialMdl->getList($field, array('sales_material_bn' => $salesMaterialBn));
        }
        
        $shop_sku_id_list = [];

        if (!isset($stocks[0]['regulation'])) {
            $shopId    = $shopConfig['shop_id'];
            $shopModel = app::get('inventorydepth')->model('shop');
            $shop      = $shopModel->select()->columns('*')->where('shop_id=?', $shopId)->instance()->fetch_row();
    
            kernel::single('inventorydepth_calculation_basicmaterial')->init($products);
            kernel::single('inventorydepth_calculation_salesmaterial')->init($products);
            
            foreach ($products as $product) {
                $st = $logicStockLib->getStock($product, $shop['shop_id'], $shop['shop_bn']);
                if ($st === false) {
                    continue;
                }
                $stockList[] = $st;

                // $shop_sku_id_list[$product['sales_material_bn']] = $shop_sku_id;
            }
    
            $stockList = kernel::single('inventorydepth_logic_stock')->resetChangeStocks($stockList, $shop, array_column($products,'sales_material_bn'), $shop_sku_id_list);
        }else{
            $stockList = $stocks;
        }
        //查询仓库与优仓编码映射关系
        $warehouseMapping = $shopConfig['warehouse_mapping'];
        
        $productBn = array_column($products, null, 'sales_material_bn');
        $newStocks = array();
        foreach ($stockList as $key => $val) {
            if ($val['regulation']) {
                $bnStockList       = $val['regulation']['detail']['可售库存']['info']['basic'];
                $salesMaterialInfo = $productBn[$val['bn']];
                $newBranchList = array();
                foreach ($bnStockList as $stK => $stV) {
                    //普通商品
                    if ($salesMaterialInfo['sales_material_type'] == 1) {
                        $branchList = $stV['info']['detail'];
                        foreach ($branchList as $branchBn => $branchV) {
                            $quantity       = $branchV['库存'] - $branchV['仓库预占'] - $branchV['指定仓预占'];
                            $warehouse_code = $warehouseMapping[$branchBn];
                            if ($warehouse_code) {
                                $stockItem = [
                                    'bn'             => $val['bn'],
                                    'warehouse_code' => $warehouse_code,
                                    'quantity'       => $quantity,
                                    'lastmodify'     => time(),
                                ];
                                $newStocks[$stK .''. $warehouse_code] = $stockItem;
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
                                    $warehouse_code = $warehouseMapping[$branchBn];
                                    if ($warehouse_code) {
                                        $stockItem = [
                                            'bn'             => $smBn['sales_material_bn'],
                                            'warehouse_code' => $warehouse_code,
                                            'quantity'       => $quantity,
                                            'lastmodify'     => time(),
                                        ];
                                        $newStocks[$smBn['sales_material_bn'] .''. $warehouse_code] = $stockItem;
                                    }
                                }
                            }
                        }
                        //多选一
                        if ($productBn[$val['bn']]['sales_material_type'] == 5) {
                            $branchList = $stV['info']['detail'];
                            foreach ($branchList as $branchBn => $branchV) {
                                $warehouse_code = $warehouseMapping[$branchBn];
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
                        $warehouse_code = $warehouseMapping[$branchBn];
                        if ($warehouse_code) {
                            $stockItem = [
                                'bn'             => $val['bn'],
                                'warehouse_code' => $warehouse_code,
                                'quantity'       => $quantity,
                                'lastmodify'     => time(),
                            ];
                            $newStocks[$val['bn'] .''. $warehouse_code] = $stockItem;
                        }
                    }
                }
            }
        }
        return $newStocks;
    }

    
}
