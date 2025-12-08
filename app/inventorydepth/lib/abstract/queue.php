<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 *  队列抽象类
 * 
 * @author chenping<chenping@shopex.cn>
 */

abstract class inventorydepth_abstract_queue {

    /**
     * 保存发布队列
     *
     * @param $params Array 货品的过滤条件，沿用FINDER中的FILTER
     * @return void
     * @author 
     **/
    public function insert_release_queue($title,$params)
    {
        $queueData = array(
            'queue_title'=>$title,
            'start_time'=>time(),
            'params'=>$params,
            'worker'=>'inventorydepth_queue.exec_release_queue',
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }

    /**
     * 执行发布队列
     *
     * @param $params Array 货品的过滤条件，沿用FINDER中的FILTER
     * @return void
     * @author 
     **/
    public function exec_release_queue($cursor_id,$params,$errormsg)
    {
        $offset  = $params['offset']; unset($params['offset']);
        $limit   = $params['limit']; unset($params['limit']);
        $operInfo = $params['operInfo'];unset($params['operInfo']);
        $shop_id = $params['shop_id'];

        $optLogModel = app::get('inventorydepth')->model('operation_log');
        $adjustmentModel = app::get('inventorydepth')->model('shop_adjustment');

        $skus = $adjustmentModel->getList('id,shop_product_bn,release_stock,shop_type,shop_sku_id',$params,$offset,$limit);
        $memo = array('last_modified'=>time());
        foreach ($skus as $key => $sku) {
            $stocks[$sku['id']] = array(
                'bn' => $sku['shop_product_bn'],
                'quantity' => $sku['release_stock'],
                'memo' => json_encode($memo),
                'sku_id' => $sku['shop_sku_id'],
            );
            if ($sku['shop_type'] == 'vop') {
                 $stocks[$sku['id']]['barcode'] = $sku['shop_sku_id'];
            }

            // 查询增量库存
            if (kernel::single('inventorydepth_sync_set')->isModeSupportInc($sku['shop_type'])) {
                $stockLogMdl   = app::get('ome')->model('api_stock_log');
                $last_quantity = $stockLogMdl->getLastStockLog($shop_id, $sku['shop_product_bn']);
                if ($last_quantity) {
                    $stocks[$sku['id']]['inc_quantity'] = $stocks[$sku['id']]['quantity'] - $last_quantity['store'];
                }
            }

            $optLogModel->write_log('sku',$sku['id'],'stockup','批量发布库存：'.$sku['release_stock'],$operInfo);
        }

        if($stocks){
             kernel::single('inventorydepth_shop')->doStockRequest($stocks,$shop_id,true);
        }
        return false;
    }


    /**
     * 保存发布队列
     *
     * @param $params Array 商品的过滤条件，沿用FINDER中的FILTER
     * @return void
     * @author 
     **/
    public function insert_shop_item_queue($title,$params)
    {
        $queueData = array(
            'queue_title'=>$title,
            'start_time'=>time(),
            'params'=>$params,
            'worker'=>'inventorydepth_queue.exec_shop_item_queue',
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }

    /**
     * @description 执行店铺商品插队列
     * @access public
     * @param $params Array 商品的过滤条件，沿用FINDER中的FILTER
     * @return void
     */
    public function exec_shop_item_queue($cursor_id,$params,$errormsg) 
    {
        $itemsModel = app::get('inventorydepth')->model('shop_items');
        if (is_array($params)) {
            foreach ($params as $param) {
                $itemsModel->saveItem($param['items'],$param['shop']);
            }
        }
        return false;
    }

    /**
     * 保存SKU信息,文件导入发布库存
     *
     * @return void
     * @author 
     **/
    public function insert_shop_skus_queue($title,$params)
    {
        $queueData = array(
            'queue_title'=>$title,
            'start_time'=>time(),
            'params'=>$params,
            'worker'=>'inventorydepth_queue.exec_shop_skus_queue',
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }

    /**
     * @description 执行保存SKU队列
     * @access public
     * @param void
     * @return void
     */
    public function exec_shop_skus_queue($cursor_id,$params,$errormsg) 
    {
        $skuModel = app::get('inventorydepth')->model('shop_skus');
        if (is_array($params)) {
            $stocks = array(); $i = 0;
            foreach ($params as $param) {
                //$skuModel->save($param);

                $tmp = array(
                    'bn' => $param['shop_product_bn'],
                    'quantity' => $param['release_stock'],
                );

                // 查询增量库存
                if (kernel::single('inventorydepth_sync_set')->isModeSupportInc($param['shop_type'])) {
                    $stockLogMdl   = app::get('ome')->model('api_stock_log');
                    $last_quantity = $stockLogMdl->getLastStockLog($param['shop_id'], $param['shop_product_bn']);
                    if ($last_quantity) {
                        $tmp['inc_quantity'] = $tmp['quantity'] - $last_quantity['store'];
                    }
                }
                $stocks[$param['shop_id']][] = $tmp;
            }

            foreach($stocks as $shop_id => $stock){
                $new_stock = array_chunk($stock,50);
                foreach ($new_stock as $value) {
                    kernel::single('inventorydepth_shop')->doStockRequest($value,$shop_id);
                }
            }
            
        }
        return false;
    }

    /**
     * 批量上下架，放入队列
     *
     * @param $params Array 商品的过滤条件，沿用FINDER中的FILTER
     * @return void
     * @author 
     **/
    public function insert_approve_queue($title,$params)
    {
        $queueData = array(
            'queue_title'=>$title,
            'start_time'=>time(),
            'params'=>$params,
            'worker'=>'inventorydepth_queue.exec_approve_queue',
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }

    /**
     * 上下架执行队列
     *
     * @return void
     * @author 
     **/
    public function exec_approve_queue($cursor_id,$params,$errormsg)
    {
        # 上下架处理
        $approve_status = $params['do_approve']; unset($params['do_approve']);
        $offset = $params['offset']; unset($params['offset']);
        $limit = $params['limit']; unset($params['limit']);
        $operInfo = $params['operInfo'];unset($params['operInfo']);

        kernel::single('inventorydepth_shop')->doApproveBatch($params,$approve_status,$offset,$limit,$operInfo);
        
        return false; 
    }

    /**
     * @description 批量更新库存进队列,店铺级操作，更新所有货品库存
     * @access public
     * @param void
     * @return void
     */
    public function insert_stock_update_queue($title,$params) 
    {
        $queueData = array(
            'queue_title'=>$title,
            'start_time'=>time(),
            'params'=>$params,
            'worker'=>'inventorydepth_queue.exec_stock_update_queue',
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }

    /**
     * @description 执行更新库存进队列
     * @access public
     * @param $params Array 货品的过滤条件
     * @return void
     */
    public function exec_stock_update_queue($cursor_id,$params,$errormsg)
    {
        $salesMaterialObj = app::get('material')->model('sales_material');
        $skusObj = app::get('inventorydepth')->model('shop_skus');
        
        $offset  = $params['offset']; unset($params['offset']);
        $limit   = $params['limit']; unset($params['limit']);
        $shop_id = $params['shop_id'];


        $filter = array('is_bind'=>1, 'sales_material_type|notin'=>array('4'), 'shop_id'=>array($shop_id,'_ALL_'));

        if(app::get('ome')->model('shop')->db_dump(['shop_id'=>$shop_id], 'delivery_mode')['delivery_mode'] == 'shopyjdf') {
            $salesMaterialObj = app::get('dealer')->model('sales_material');
            $filter = array('shop_id'=>array($shop_id));
        }

        if($params['sales_material_bn']) {
            $filter['sales_material_bn'] = $params['sales_material_bn'];
        }
    
        // 只回写前端店铺SKU
        if ($params['skutype'] == '2') {
            $shopSkuList                 = $skusObj->getList('shop_product_bn,shop_sku_id', array('shop_id' => $shop_id, 'mapping' => '1'), $offset, $limit);
            $filter['sales_material_bn'] = $shopSkuList ? array_column($shopSkuList, 'shop_product_bn') : '000000';
            $offset                      = 0;
        }
    
        // 获取销售物料
        $field    = 'sm_id,sales_material_name,sales_material_bn, sales_material_type,shop_id,class_id';
        $products = $salesMaterialObj->getList($field, $filter,$offset,$limit);

        $shopIds = [$shop_id];
        kernel::single('inventorydepth_logic_stock')->do_sync_products_stock($products, $shopIds);
        
        return false;
    }

    /**
     * @description 插入队列共用方法
     * @access public
     * @param void
     * @return void
     */
    public function insert_queue($title,$params,$worker) 
    {
        $queueData = array(
            'queue_title'=>$title,
            'start_time'=>time(),
            'params'=>$params,
            'worker'=>$worker,
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }
    
    //定时变化库存自动回写 
    public function timed_stock_sync_queue($title,$params){
        $queueData = array(
                'queue_title'=>$title,
                'start_time'=>time(),
                'params'=>$params,
                'worker'=>'inventorydepth_queue.exec_timed_stock_sync_queue',
        );
        $queueModel = app::get('base')->model('queue');
        $result = $queueModel->save($queueData);
        return $result;
    }
    
    //执行定时变化库存自动回写队列
    public function exec_timed_stock_sync_queue($cursor_id,$params,$errormsg){
        @set_time_limit(0);
        @ini_set('memory_limit','1024M');
        @ignore_user_abort(true);
        $salesMaterialObj = app::get('material')->model('sales_material');
        $sm_ids = $params["sm_ids"];
        $filter = array('sm_id'=>$sm_ids);
        if (isset($params['visibled'])) {
            $filter['visibled'] = $params['visibled'];
        }
        if(!empty($sm_ids)){
            $products = $salesMaterialObj->getList('sm_id,sales_material_name,sales_material_bn, sales_material_type,shop_id,class_id',$filter);
            if(!empty($products)){
                // shop
                $shopIds = [];
                $shopFilter = array(
                    'filter_sql' =>'{table}node_id is not null AND {table}node_id !="" AND {table}delivery_mode="self"',
                );
                $shopList = app::get('ome')->model('shop')->getList('shop_id',$shopFilter);
                if($shopList){
                    $shopIds = array_column($shopList, 'shop_id');
                }
                
                kernel::single('inventorydepth_logic_stock')->set_readStoreLastmodify($params['read_store_lastmodify'])->do_sync_products_stock($products, $shopIds);
            }
        }
        return false;
    }
    
    /**
     * 翱象同步库存
     *
     * @param $products
     * @param $shopInfo
     * @params $error_msg
     * @return array|false
     */
    public function aoxiang_store_update($products, $shopInfo, &$error_msg=null)
    {
        $aoxiangLib = kernel::single('dchain_aoxiang');
        
        //params
        $shop_id = $shopInfo['shop_id'];
        $shop_type = $shopInfo['shop_type'];
        $aoxiang_signed = $shopInfo['aoxiang_signed'];
        
        //店铺类型
        if(!in_array($shop_type, array('tmall', 'taobao'))){
            $error_msg = '不是淘宝系店铺';
            return false;
        }
        
        //是否安装了应用
        if(!app::get('dchain')->is_installed()){
            $error_msg = '未安装dchain应用';
            return false;
        }
        
        //翱象签约店铺
        if($aoxiang_signed != '1'){
            $error_msg = '店铺未签约翱象';
            return false;
        }
        
        //get config
        $aoxiangConfig = $aoxiangLib->getAoxiangSyncConfig($shop_id);
        if($aoxiangConfig['sync_stock'] == 'false') {
            $error_msg = '未开启同步翱象库存开关';
            return false;
        }
        
        //获取店铺对应的仓库
        $branchList = kernel::single('inventorydepth_shop')->getBranchByshop($shopInfo['shop_bn']);
        if (!$branchList){
            $error_msg = '店铺没有绑定仓库';
            return false;
        }
        
        //format
        $stocks = array();
        foreach($products as $product)
        {
            $product_bn = $product['bn'];
            
            //默认库存为0(后面会按仓库级获取库存)
            $quantity = 0;
            
            //stocks
            $stocks[$product_bn] = array(
                'bn' => $product_bn,
                'quantity' => $quantity,
                'product_type' => 'normal',
            );
        }
        
        //捆绑商品
        if (is_array(inventorydepth_stock_pkg::$pkg)) {
            foreach (inventorydepth_stock_pkg::$pkg as $pkgValue)
            {
                $pkg_bn = $pkgValue['pkg_bn'];
                
                //默认库存为0(后面会按仓库级获取库存)
                $quantity = 0;
                
                //stocks
                $stocks[$pkg_bn] = array(
                    'bn' => $pkg_bn,
                    'quantity' => $quantity,
                    'product_type' => 'pkg',
                );
                
                //PKG捆绑商品没有绑定子商品则跳过
                if(empty($pkgValue['products'])){
                    continue;
                }
                
                //翱象平台要求PKG捆绑商品要回传绑定的子商品的库存
                foreach ($pkgValue['products'] as $productKey => $productVal)
                {
                    $product_bn = $productVal['bn'];
                    
                    //默认库存为0(后面会按仓库级获取库存)
                    $quantity = 0;
                    
                    //stocks
                    $stocks[$product_bn] = array(
                        'bn' => $product_bn,
                        'quantity' => $quantity,
                        'product_type' => 'normal',
                    );
                }
                
            }
        }
        
        //check
        if(empty($stocks)){
            $error_msg = '没有可回写的商品';
            return false;
        }
        
        //page size
        $page_size = 50;
        
        //page
        $newStocks = array_chunk($stocks, $page_size);
        $aoxiangResult = array();
        foreach ($newStocks as $stockItem)
        {
            //推送仓库级库存
            $stockList = $aoxiangLib->getStocks($stockItem, $shopInfo, $error_msg);
            if(!$stockList){
                continue;
            }
            
            //request
            foreach ($stockList as $branch_bn => $branchStock)
            {
                $aoxiangResult = kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_stockAoxiangUpdate($branchStock);
            }
        }
        
        return (empty($aoxiangResult) ? false : $aoxiangResult);
    }
    
}
