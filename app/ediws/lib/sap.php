<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * sap业务处理Lib方法
 *
 * @author wangbiao@shopex.cn
 * @version 2023.12.25
 */
class ediws_sap 
{
    /**
     * 生成Sap销售单记录
     * 
     * @param array $params
     * @return array
     */

    public function createSapSales($params)
    {
        $saleObj = app::get('vfapi')->model('sales');
        $sapSaleObj = app::get('vfapi')->model('sap_sales');
        
        $funcLib = kernel::single('vfapi_func');
        $syncSaleLib = kernel::single('vfapi_tasks_syncsales');
        
        //货号对照表
        base_kvstore::instance('vfapi')->fetch('goodsmap.erp', $erpProductList);
        
        //filter
        $filter = $params['filter'];
        if(empty($filter)){
            $error_msg = '没有指定查询条件!';
            return $this->error($error_msg);
        }
        
        //page_size
        $page_size = $this->_page_size;
        
        //count
        $countNum = $saleObj->count($filter);
        if(empty($countNum)){
            $error_msg = '没有可操作的销售单!';
            return $this->error($error_msg);
        }
        
        //以shop店铺编码为下标,获取TTPOS列表
        $shopTtposList = $funcLib->getShopTtposList();
        
        //page
        $pageNum = ceil($countNum / $page_size);
        for($page=1; $page<=$pageNum; $page++)
        {
            //是否读取固定折扣价
            $is_read_price = false;
            
            //getList
            $tempList = $saleObj->getList('*', $filter, 0, $page_size, 'id ASC');
            if(empty($tempList)){
                //没有查询到销售单数据
                continue;
            }
            
            //format
            $soldList = array();
            $dataList = array();
            $productBns = array();
            foreach($tempList as $key => $val)
            {
                $id = $val['id'];
                $sale_bn = $val['sale_bn'];
                $ttpos_store = $val['ttpos_store'];
                $shop_bn = $val['erp_store'];
                
                //check
                if(in_array($val['status'], array('1'))){
                    //单据已经处理,不允许重复操作
                    $error_msg = '单据已经处理,不允许重复操作!';
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                if(empty($ttpos_store)){
                    $error_msg = '没有ttpos_store编码';
                    
                    //update
                    $saleObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                //shop
                $shopInfo = $shopTtposList[$shop_bn];
                if(empty($shopInfo)){
                    $error_msg = 'ttpos_store编码没有配置店铺对照关系';
                    
                    //update
                    $saleObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                //是否生成销售文件配置开关
                if($shopInfo['is_sale_file'] == 'false'){
                    $error_msg = '是否生成销售文件：未启用';
                    
                    //update
                    $saleObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                //unserialize
                $addonData = unserialize($val['addon']);
                unset($val['addon']);
                
                //check
                if(empty($addonData['sale_items']) || !is_array($addonData['sale_items'])){
                    $error_msg = '没有销售明细';
                    
                    //update
                    $saleObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                //收货人信息
                $val['consignee'] = $addonData['consignee'];
                $val['consignee_area'] = $addonData['consignee_area'];
                $val['consignee_mobile'] = $addonData['consignee_mobile'];
                $val['consignee_tel'] = $addonData['consignee_tel'];
                
                //[渠道类型]是否京东云仓类型
                if($addonData['shopinfo']['shop_type'] == '360buy' && $addonData['shopinfo']['business_type'] == 'jdlvmi'){
                    //京东云仓
                    $val['channel_type'] = 'jd_cloud';
                    
                    //读取读取固定折扣价
                    $is_read_price = true;
                }else{
                    $val['channel_type'] = 'oms'; //OMS普通销售单
                }
                
                //items
                $saleItems = $addonData['sale_items'];
                
                //格式化赠品数据
                $saleItems = $this->formatSaleItems($saleItems, $erpProductList);
                if(empty($saleItems)){
                    $error_msg = '格式化销售明细为空(只有一个SKU并且是赠品);';
                    
                    //update
                    $saleObj->update(array('status'=>4, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    continue;
                }
                
                //sole_bn
                foreach ($saleItems as $item_id => $itemVal)
                {
                    $product_bn = $itemVal['bn'];
                    
                    //唯一性编码 = 销售单号 + 货号 + item_id
                    $sole_bn = $sale_bn .'_'. $product_bn .'_'. $item_id;;
                    $soldList[$sole_bn] = $sole_bn;
                    
                    //bns
                    $productBns[$product_bn] = $product_bn;
                }
                
                //merge
                $val['sale_items'] = $saleItems;
                
                //data
                $dataList[$id] = $val;
            }
            
            //unset
            unset($tempList);
            
            //check
            if(empty($dataList)){
                //没有有效的数据
                $error_msg = '没有有效的数据!';
                
                //return error
                if($countNum == 1){
                    return $this->error($error_msg);
                }
                
                continue;
            }
            
            //已经存在的数据
            $existData = array();
            if($soldList){
                $existData = $sapSaleObj->getList('id,sole_bn', array('sole_bn'=>$soldList));
                if($existData){
                    $existData = $funcLib->_array_column($existData, null, 'sole_bn');
                }
            }
            
            //固定折扣价格(获取导入的固定折扣价格)
            $getPriceList = array();
            if($is_read_price && $productBns){
                $getPriceList = $this->getImportPriceList($productBns);
            }
            
            //list
            foreach ($dataList as $saleId => $saleVal)
            {
                $id = $saleVal['id'];
                $sale_bn = $saleVal['sale_bn'];
                $sale_time = $saleVal['sale_time'];
                //$ttpos_store = $saleVal['ttpos_store'];
                $shop_bn = $saleVal['erp_store'];
                
                //渠道类型
                $channel_type = $saleVal['channel_type'];
                
                //店铺配置信息
                $shopInfo = $shopTtposList[$shop_bn];
                
                //items
                $linenum = 0;
                foreach ($saleVal['sale_items'] as $item_id => $itemVal)
                {
                    $product_bn = $itemVal['bn'];
                    
                    //唯一性编码 = 销售单号 + 货号 + item_id
                    $sole_bn = $sale_bn .'_'. $product_bn .'_'. $item_id;;
                    
                    //check
                    if($existData[$sole_bn]){
                        $error_msg = '已经生成过Sap销售记录';
                        
                        //update
                        $saleObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                        
                        //return error
                        if($countNum == 1){
                            return $this->error($error_msg);
                        }
                        
                        continue;
                    }
                    
                    $linenum++;
                    
                    //format
                    $saleVal['operation'] = 'create_sap_sale'; //标记为创建Sap销售记录
                    $saleVal['sale_time'] = date('Y-m-d H:i:s', $sale_time);
                    $saleVal['sale_no'] = $sale_bn;
                    $saleVal['order_no'] = $saleVal['order_bn'];
                    
                    //读取固定折扣价
                    $is_oms_price = true;
                    if(in_array($channel_type, array('jd_cloud', 'jd_account'))){
                        $priceInfo = $getPriceList[$product_bn];
                        if($priceInfo){
                            $sale_price = ($priceInfo['sale_price'] ? $priceInfo['sale_price'] : 0);
                            
                            $itemVal['price'] = ($priceInfo['price'] ? $priceInfo['price'] : $itemVal['price']); //货品单价
                            $itemVal['sales_amount'] = ($sale_price * $itemVal['nums']); //销售金额 = 销售单价 * 数量
                        }else{
                            $itemVal['price'] = 0; //货品单价
                            $itemVal['sales_amount'] = 0; //销售金额 = 销售单价 * 数量
                            
                            $is_oms_price = false;
                        }
                    }
                    
                    //getData
                    $mainSdf = $syncSaleLib->get_row($saleVal, $itemVal, $linenum, $shopInfo);
                    
                    //merge
                    $mainSdf['sole_bn'] = $sole_bn; //唯一编码
                    $mainSdf['shop_bn'] = $saleVal['erp_store']; //OMS店铺编码
                    $mainSdf['channel_type'] = $channel_type; //来源渠道：oms、jd_edi、vip_edi
                    $mainSdf['sale_time'] = $sale_time;
                    $mainSdf['create_time'] = time();
                    
                    //渠道类型
                    $mainSdf['channel_type'] = $channel_type;
                    
                    //店铺商品ID
                    if($priceInfo['shop_sku_id']){
                        $mainSdf['sku_bn'] = $priceInfo['shop_sku_id'];
                    }
                    
                    //读取固定折扣价格失败
                    if(!$is_oms_price){
                        //读取价格失败,标记异常
                        $mainSdf['is_abnormal'] = 'true';
                        $mainSdf['error_msg'] = '读取导入固定折扣价格失败';
                    }elseif($mainSdf['XF_ORGUPRICE'] <= 0){
                        //货品price销售价在OMS系统里未维护
                        $mainSdf['is_abnormal'] = 'true';
                        $mainSdf['error_msg'] = '读取OMS货品销售价为0元';
                    }else{
                        $mainSdf['is_abnormal'] = 'false';
                    }
                    
                    //insert
                    $insert_id = $sapSaleObj->insert($mainSdf);
                    if(!$insert_id){
                        $error_msg = '创建Sap销售记录失败';
                        
                        //update
                        $saleObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                        
                        //return error
                        if($countNum == 1){
                            return $this->error($error_msg);
                        }
                        
                        continue;
                    }
                    
                    //update
                    $saleObj->update(array('status'=>1, 'last_modified'=>time()), array('id'=>$id));
                }
            }
        }
        
        return $this->succ();
    }
    
    /**
     * 格式化赠品数据
     * 
     * @param $saleItems
     * @param $erpProductList
     * @return void
     */
    public function formatSaleItems($saleItems, $erpProductList)
    {
        //check
        if(empty($saleItems) || empty($erpProductList)){
            return $saleItems; //直接返回
        }
        
        //count
        $itemCount = count($saleItems);
        
        //items
        $giftData = array();
        $giftBns = array();
        foreach ($saleItems as $giftKey => $giftItem)
        {
            $product_bn = $giftItem['bn'];
            
            //check
            if(!isset($erpProductList[$product_bn])){
                continue;
            }
            
            //设置了TTPOS货号对照表
            if($erpProductList[$product_bn][0]){
                //通过[货号对照表]替换成设置的TTPOS货号
                $giftItem['bn'] = $erpProductList[$product_bn][0];
                
                $saleItems[$giftKey] = $giftItem;
            }elseif($erpProductList[$product_bn][1] == 'true'){
                //配置货号是赠品类型
                if($itemCount <= 1){
                    //如果只有一行数据，并且是赠品，则不用传给ttpos,直接跳过
                }else{
                    //有多行数据，并且是赠品，则需要把赠品的销售额加到第一个货品上
                    $giftBns[] = $product_bn;
                    
                    $giftData['gift_amount'] += number_format($giftItem['sales_amount'], 2, '.', '');
                }
                
                //unset
                unset($saleItems[$giftKey]);
            }
            
            //匹配的赠品数据
            if($giftBns){
                $giftData['gift_bn'] = implode(',', $giftBns);
            }
            
            //处理替换bn与礼品逻辑
            $item_i = 0;
            foreach ($saleItems as $k => $item)
            {
                //如果有赠品,且TTPOS无对应的bn，将其销售额加到第一个商品上
                if($item_i == 0 && $giftData){
                    $saleItems[$k]['gift_bn'] = $giftData['gift_bn'];
                    $saleItems[$k]['gift_amount'] = $giftData['gift_amount'];
                    $saleItems[$k]['sales_amount'] += $giftData['gift_amount'];
                }
                
                $item_i++;
            }
            
            //价格无法均摊的，新加一行明细商品
            //@todo：金额除不尽的拆成2行：一行为(n-1)*单价，另一行为总价-(n-1)*单价；
            foreach ($saleItems as $k => $item)
            {
                if ($item['nums'] > 1) {
                    $sales_amount = $item['sales_amount'];
                    $nums = $item['nums'];
                    $unit_price = number_format($sales_amount / $nums, 2, '.', '');
                    
                    //无法均摊的，新加一行明细商品
                    if ($unit_price * $nums != $sales_amount) {
                        $saleItems[$k]['nums'] = $nums - 1;
                        $saleItems[$k]['sales_amount'] = $unit_price * ($nums - 1);
                        
                        //add
                        $saleItems[$k.'new'] = $item;
                        $saleItems[$k.'new']['nums'] = 1;
                        $saleItems[$k.'new']['sales_amount'] = $sales_amount - $saleItems[$k]['sales_amount'];
                    }
                }
            }
        }
        
        return $saleItems;
    }
    
    /**
     * 生成Sap售后单记录
     * 
     * @param array $params
     * @return array
     */
    public function createSapAftersales($params)
    {
        $aftersalesObj = app::get('vfapi')->model('aftersales');
        $sapAftersaleObj = app::get('vfapi')->model('sap_aftersales');
        
        $funcLib = kernel::single('vfapi_func');
        $syncAftersaleLib = kernel::single('vfapi_tasks_syncaftersales');
        
        //货号对照表
        base_kvstore::instance('vfapi')->fetch('goodsmap.erp', $erpProductList);
        
        //filter
        $filter = $params['filter'];
        if(empty($filter)){
            $error_msg = '没有指定售后查询条件!';
            return $this->error($error_msg);
        }
        
        //page_size
        $page_size = $this->_page_size;
        
        //count
        $countNum = $aftersalesObj->count($filter);
        if(empty($countNum)){
            $error_msg = '没有可操作的售后单!';
            return $this->error($error_msg);
        }
        
        //以shop店铺编码为下标,获取TTPOS列表
        $shopTtposList = $funcLib->getShopTtposList();
        
        //page
        $pageNum = ceil($countNum / $page_size);
        for($page=1; $page<=$pageNum; $page++)
        {
            //是否读取固定折扣价
            $is_read_price = false;
            
            //getList
            $tempList = $aftersalesObj->getList('*', $filter, 0, $page_size, 'id ASC');
            if(empty($tempList)){
                //没有查询到销售单数据
                continue;
            }
            
            //format
            $soldList = array();
            $dataList = array();
            $productBns = array();
            foreach($tempList as $key => $val)
            {
                $id = $val['id'];
                $aftersale_bn = $val['aftersale_bn'];
                $ttpos_store = $val['ttpos_store'];
                $shop_bn = $val['erp_store'];
                
                //check
                if(in_array($val['status'], array('1'))){
                    //单据已经处理,不允许重复操作
                    $error_msg = '售后单据已经处理,不允许重复操作!';
                    
                    //只有一条记录时,直接返回报错信息
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                if(empty($ttpos_store)){
                    $error_msg = '售后单没有ttpos_store编码!';
                    
                    //update
                    $aftersalesObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                //shop
                $shopInfo = $shopTtposList[$shop_bn];
                if(empty($shopInfo)){
                    $error_msg = '售后单中ttpos_store编码没有配置店铺对照关系!';
                    
                    //update
                    $aftersalesObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                //是否生成售后文件配置开关
                if($shopInfo['is_aftersale_file'] == 'false'){
                    $error_msg = '是否生成售后文件：未启用';
                    
                    //update
                    $aftersalesObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                //unserialize
                $addonData = unserialize($val['addon']);
                unset($val['addon']);
                
                //check
                if(empty($addonData['aftersale_items']) || !is_array($addonData['aftersale_items'])){
                    $error_msg = '没有售后明细!';
                    
                    //update
                    $aftersalesObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                //收货人信息
                $val['consignee'] = $addonData['consignee'];
                $val['consignee_area'] = $addonData['consignee_area'];
                $val['consignee_mobile'] = $addonData['consignee_mobile'];
                $val['consignee_tel'] = $addonData['consignee_tel'];
                
                //[渠道类型]是否京东云仓类型
                if($addonData['shopinfo']['shop_type'] == '360buy' && $addonData['shopinfo']['business_type'] == 'jdlvmi'){
                    //京东云仓
                    $val['channel_type'] = 'jd_cloud';
                    
                    //读取读取固定折扣价
                    $is_read_price = true;
                }else{
                    $val['channel_type'] = 'oms'; //OMS普通销售单
                }
                
                //items
                $aftersaleItems = $addonData['aftersale_items'];
                
                //格式化赠品数据
                $aftersaleItems = $this->formatAftersaleItems($aftersaleItems, $erpProductList);
                if(empty($aftersaleItems)){
                    $error_msg = '格式化售后明细为空';
                    
                    //update
                    $aftersalesObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    continue;
                }
                
                //sole_bn
                foreach ($aftersaleItems as $item_id => $itemVal)
                {
                    $product_bn = $itemVal['bn'];
                    
                    //唯一性编码 = 销售单号 + 货号 + item_id
                    $sole_bn = $aftersale_bn .'_'. $product_bn .'_'. $item_id;
                    $soldList[$sole_bn] = $sole_bn;
                    
                    //bns
                    $productBns[$product_bn] = $product_bn;
                }
                
                //merge
                $val['aftersale_items'] = $aftersaleItems;
                
                //data
                $dataList[$id] = $val;
            }
            
            //unset
            unset($tempList);
            
            //check
            if(empty($dataList)){
                //没有有效的数据
                $error_msg = '没有有效的售后数据!';
                
                //return error
                if($countNum == 1){
                    return $this->error($error_msg);
                }
                
                continue;
            }
            
            //已经存在的数据
            $existData = array();
            if($soldList){
                $existData = $sapAftersaleObj->getList('id,sole_bn', array('sole_bn'=>$soldList));
                if($existData){
                    $existData = $funcLib->_array_column($existData, null, 'sole_bn');
                }
            }
            
            //固定折扣价格(获取导入的固定折扣价格)
            $getPriceList = array();
            if($is_read_price && $productBns){
                $getPriceList = $this->getImportPriceList($productBns);
            }
            
            //list
            foreach ($dataList as $saleId => $saleVal)
            {
                $id = $saleVal['id'];
                $aftersale_bn = $saleVal['aftersale_bn'];
                $aftersale_time = $saleVal['aftersale_time'];
                //$ttpos_store = $saleVal['ttpos_store'];
                $shop_bn = $saleVal['erp_store'];
                
                //渠道类型
                $channel_type = $saleVal['channel_type'];
                
                //店铺配置信息
                $shopInfo = $shopTtposList[$shop_bn];
                
                //items
                $linenum = 0;
                foreach ($saleVal['aftersale_items'] as $item_id => $itemVal)
                {
                    $product_bn = $itemVal['bn'];
                    
                    //唯一性编码 = 销售单号 + 货号 + item_id
                    $sole_bn = $aftersale_bn .'_'. $product_bn .'_'. $item_id;
                    
                    //check
                    if($existData[$sole_bn]){
                        $error_msg = '已经生成过Sap售后记录!';
                        
                        //update
                        $aftersalesObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                        
                        //return error
                        if($countNum == 1){
                            return $this->error($error_msg);
                        }
                        
                        continue;
                    }
                    
                    $linenum++;
                    
                    //format
                    $saleVal['operation'] = 'create_sap_sale'; //标记为创建Sap销售记录
                    $saleVal['aftersale_time'] = date('Y-m-d H:i:s', $aftersale_time);
                    $saleVal['aftersale_no'] = $aftersale_bn;
                    $saleVal['order_no'] = $saleVal['order_bn'];
                    
                    //读取固定折扣价
                    $is_oms_price = true;
                    if(in_array($channel_type, array('jd_cloud', 'jd_account'))){
                        $priceInfo = $getPriceList[$product_bn];
                        if($priceInfo){
                            $sale_price = ($priceInfo['sale_price'] ? $priceInfo['sale_price'] : 0);
                            
                            $itemVal['price'] = ($priceInfo['price'] ? $priceInfo['price'] : $itemVal['price']); //货品单价
                            $itemVal['sales_amount'] = ($sale_price * $itemVal['nums']); //销售金额 = 销售单价 * 数量
                        }else{
                            $itemVal['price'] = 0; //货品单价
                            $itemVal['sales_amount'] = 0; //销售金额 = 销售单价 * 数量
                            
                            $is_oms_price = false;
                        }
                    }
                    
                    //getData
                    $mainSdf = $syncAftersaleLib->get_row($saleVal, $itemVal, $linenum, $shopInfo);
                    
                    //merge
                    $mainSdf['sole_bn'] = $sole_bn; //唯一编码
                    $mainSdf['shop_bn'] = $saleVal['erp_store']; //OMS店铺编码
                    $mainSdf['channel_type'] = $channel_type; //来源渠道：oms、jd_edi、vip_edi
                    $mainSdf['aftersale_time'] = $aftersale_time;
                    $mainSdf['create_time'] = time();
                    
                    //渠道类型
                    $mainSdf['channel_type'] = $channel_type;
                    
                    //店铺商品ID
                    if($priceInfo['shop_sku_id']){
                        $mainSdf['sku_bn'] = $priceInfo['shop_sku_id'];
                    }
                    
                    //读取固定折扣价格失败
                    if(!$is_oms_price){
                        //读取价格失败,标记异常
                        $mainSdf['is_abnormal'] = 'true';
                        $mainSdf['error_msg'] = '读取固定折扣价格失败';
                    }elseif($mainSdf['XF_ORGUPRICE'] <= 0){
                        //货品price销售价在OMS系统里未维护
                        $mainSdf['is_abnormal'] = 'true';
                        $mainSdf['error_msg'] = '获取OMS货品销售价为0元';
                    }else{
                        $mainSdf['is_abnormal'] = 'false';
                    }
                    
                    //insert
                    $insert_id = $sapAftersaleObj->insert($mainSdf);
                    if(!$insert_id){
                        $error_msg = '创建Sap售后记录失败!';
                        
                        //update
                        $aftersalesObj->update(array('status'=>2, 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                        
                        //return error
                        if($countNum == 1){
                            return $this->error($error_msg);
                        }
                        
                        continue;
                    }
                    
                    //update
                    $aftersalesObj->update(array('status'=>1, 'last_modified'=>time()), array('id'=>$id));
                }
            }
        }
    
        return $this->succ();
    }
    
    /**
     * 格式化赠品数据
     * 
     * @param $aftersaleItems
     * @param $erpProductList
     * @return void
     */
    public function formatAftersaleItems($aftersaleItems, $erpProductList)
    {
        //check
        if(empty($aftersaleItems) || empty($erpProductList)){
            return $aftersaleItems; //直接返回
        }
        
        //count
        $itemCount = count($aftersaleItems);
        
        //items
        $giftData = array();
        $giftBns = array();
        foreach ($aftersaleItems as $giftKey => $giftItem)
        {
            $product_bn = $giftItem['bn'];
            
            //check
            if(!isset($erpProductList[$product_bn])){
                continue;
            }
            
            //设置了TTPOS货号对照表
            if($erpProductList[$product_bn][0]){
                //通过[货号对照表]替换成设置的TTPOS货号
                $giftItem['bn'] = $erpProductList[$product_bn][0];
                
                $aftersaleItems[$giftKey] = $giftItem;
            }elseif($erpProductList[$product_bn][1] == 'true'){
                //配置货号是赠品类型
                if($itemCount <= 1){
                    //如果只有一行数据，并且是赠品，则不用传给ttpos,直接跳过
                }else{
                    //有多行数据，并且是赠品，则需要把赠品的销售额加到第一个货品上
                    $giftBns[] = $product_bn;
                    
                    $giftData['gift_amount'] += number_format($giftItem['sales_amount'], 2, '.', '');
                }
                
                //unset
                unset($aftersaleItems[$giftKey]);
            }
            
            //匹配的赠品数据
            if($giftBns){
                $giftData['gift_bn'] = implode(',', $giftBns);
            }
            
            //处理替换bn与礼品逻辑
            $item_i = 0;
            foreach ($aftersaleItems as $k => $item)
            {
                //如果有赠品,且TTPOS无对应的bn，将其销售额加到第一个商品上
                if($item_i == 0 && $giftData){
                    $aftersaleItems[$k]['gift_bn'] = $giftData['gift_bn'];
                    $aftersaleItems[$k]['gift_amount'] = $giftData['gift_amount'];
                    $aftersaleItems[$k]['sales_amount'] += $giftData['gift_amount'];
                }
                
                $item_i++;
            }
            
            //价格无法均摊的，新加一行明细商品
            //@todo：金额除不尽的拆成2行：一行为(n-1)*单价，另一行为总价-(n-1)*单价；
            foreach ($aftersaleItems as $k => $item)
            {
                if ($item['nums'] > 1) {
                    $sales_amount = $item['sales_amount'];
                    $nums = $item['nums'];
                    $unit_price = number_format($sales_amount / $nums, 2, '.', '');
                    
                    //无法均摊的，新加一行明细商品
                    if ($unit_price * $nums != $sales_amount) {
                        $aftersaleItems[$k]['nums'] = $nums - 1;
                        $aftersaleItems[$k]['sales_amount'] = $unit_price * ($nums - 1);
                        
                        //add
                        $aftersaleItems[$k.'new'] = $item;
                        $aftersaleItems[$k.'new']['nums'] = 1;
                        $aftersaleItems[$k.'new']['sales_amount'] = $sales_amount - $aftersaleItems[$k]['sales_amount'];
                    }
                }
            }
        }
        
        return $aftersaleItems;
    }
    
    /**
     * SAP销售单记录生成txt文件并上传FTP(每1000条记录生成一个txt文件)
     * 
     * @param array $params
     * @return boolean
     */
    public function disposeSaleFile($params)
    {
        $sapSaleObj = app::get('vfapi')->model('sap_sales');
        $fileObj = app::get('vfapi')->model('filelist');
        
        $cronSaleLib = kernel::single('erpapi_autotask_timer_accountsales');
        $filedata = new vfapi_ttposfile;
        $funcLib = kernel::single('vfapi_func');
        
        //page_size文件生成记录行数
        $page_size = $cronSaleLib::$file_page_size;
        if(empty($page_size)){
            $error_msg = '没有配置文件生成记录行数';
            return $this->error($error_msg);
        }
        
        //TTpos店铺对照列表
        base_kvstore::instance('vfapi')->fetch('storemap.ttpos', $ttposList);
        if(empty($ttposList)){
            $error_msg = '没有配置TTPOS店铺对照关系!';
            return $this->error($error_msg);
        }
        
        //filter
        $filter = $params['filter'];
        if(empty($filter)){
            $error_msg = '没有传指定的filter条件';
            return $this->error($error_msg);
        }
        
        //count
        $countNum = $sapSaleObj->count($filter);
        if(empty($countNum)){
            $error_msg = '没有可操作的SAP销售单记录!';
            return $this->error($error_msg);
        }
        
        //page
        $pageNum = ceil($countNum / $page_size);
        for($page=1; $page<=$pageNum; $page++)
        {
            //渠道类型为[京东云仓]时,按照[销售单号]排序生成txt文件
            //@todo：京东结算单明细接口,订单有多个SKU时没有按订单排序给到中间件;
            if($filter['channel_type'] == 'jd_cloud' || $params['operation'] == 'manual'){
                $saleList = $sapSaleObj->getList('*', $filter, 0, $page_size, 'XF_DOCNO ASC, XF_SALESLINENUM ASC');
            }else{
                $saleList = $sapSaleObj->getList('*', $filter, 0, $page_size, 'id ASC');
            }
            
            //check
            if(empty($saleList)){
                //$error_msg = '没有可以处理的SAP销售单数据';
                continue;
            }
            
            //获取异常订单列表
            $abnormalOrders = array();
            if(in_array($saleList[0]['channel_type'], array('jd_account', 'jd_cloud'))){
                $orderBns = $funcLib->_array_column($saleList, 'XF_ORGDOCNO');
                $orderBns = array_unique($orderBns);
                
                //list
                $abnormalOrders = $sapSaleObj->getList('id,XF_ORGDOCNO,XF_DOCNO', array('XF_ORGDOCNO'=>$orderBns, 'is_abnormal'=>'true'));
                if($abnormalOrders){
                    $abnormalOrders = $funcLib->_array_column($abnormalOrders, null, 'XF_ORGDOCNO');
                }
            }
            
            //ids
            $ids = $funcLib->_array_column($saleList, 'id');
            
            //setting
            $ttpos_bn = $saleList[0]['XF_STORECODE']; //ttpos店铺编码
            $channel_type = $saleList[0]['channel_type']; //渠道类型(jd_account：京东入仓,jd_cloud：京东云仓)
            $business_type = $saleList[0]['business_type']; //冲红类型(duichong：对冲单据,jiesuan：结算单据)
            $file_line_num = 0;
            $todayDate = date('YmdHis', time());
            
            //按照渠道和业务设置不同的页码格式
            $file_page = $page;
            if($channel_type == 'jd_account'){
                if($business_type == 'duichong'){
                    $file_page = 110000 + $page;
                }elseif($business_type == 'jiesuan'){
                    $file_page = 120000 + $page;
                }else{
                    $file_page = 100000 + $page;
                }
            }elseif($channel_type == 'jd_cloud'){
                if($business_type == 'duichong'){
                    $file_page = 210000 + $page;
                }elseif($business_type == 'jiesuan'){
                    $file_page = 220000 + $page;
                }else{
                    $file_page = 200000 + $page;
                }
            }
            
            //获取文件名
            $fileParams = array(
                'ttpos_store' => $ttpos_bn, //TTpos编码
                'bill_type' => 'SO', //单据业务类型(SO：销售单,SR：售后单)
                'date' => $todayDate, //日期：年月日时分秒
                'file_page' => $file_page, //文件页码
            );
            $file_name = vfapi_filename::get_sap_sales_filename($fileParams);
            
            //file(文件名带目录路径)
            $filedata->setfile($file_name);
            $filedata->open();
            
            //list
            $saleIds = array();
            foreach($saleList as $saleKey => $saleInfo)
            {
                $id = $saleInfo['id'];
                $order_bn = $saleInfo['XF_ORGDOCNO'];
                
                //check
                if($saleInfo['status']  == 'succ'){
                    continue;
                }
                
                if($saleInfo['is_abnormal']  == 'true'){
                    $error_msg = '单据是异常状态,请检查商品固定折扣价格';
                    
                    //update
                    $sapSaleObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    continue;
                }
                
                //检查订单是否有异常的SKU商品行
                //@todo：订单有多个SKU行，必须生成到同一个txt文件中，否则SAP财务会报错；
                if($abnormalOrders && $abnormalOrders[$order_bn]){
                    continue;
                }
                
                //文件行数据
                $line = $this->getSaleRowLine($saleInfo);
                
                //写入内容
                $filedata->writeline($line);
                
                //line
                $file_line_num++;
                
                $saleIds[$id] = $id;
            }
            
            //check
            if($file_line_num == 0){
                if($abnormalOrders){
                    $error_msg = '订单有多个SKU,并且SKU部分异常';
                }else{
                    $error_msg = '没有可生成文件的销售行数据,或者订单有部分SKU异常';
                }
                
                //update
                $sapSaleObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$ids));
                
                //close
                $filedata->close();
                
                continue;
            }
            
            //过滤掉文件公共路径
            $file_name = substr($file_name, strlen(DATA_DIR));
            
            //保存FTP文件信息
            $fileSdf = array('file'=>$file_name, 'start_time'=>time(), 'end_time'=>time(), 'type'=>'sale', 'create_time'=>time());
            $file_id = $fileObj->insert($fileSdf);
            $filedata->close();
            
            //check
            if(!$file_id){
                $error_msg = '保存文件名：'. $file_name .'信息失败';
                
                //update
                $sapSaleObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$saleIds));
                
                continue;
            }
            
            //succ
            $sapSaleObj->update(array('status'=>'succ', 'filename'=>$file_name, 'last_modified'=>time()), array('id'=>$saleIds));
        }
        
        //unset
        unset($ttposList, $saleList, $ids, $saleIds);
        
        return $this->succ();
    }
    
    /**
     * SAP售后单记录生成xml文件并上传FTP(每1000条记录生成一个txt文件)
     * 
     * @param array $params
     * @return boolean
     */
    public function disposeAftersaleFile($params)
    {
        $sapAftersaleObj = app::get('vfapi')->model('sap_aftersales');
        $fileObj = app::get('vfapi')->model('filelist');
        
        $cronSaleLib = kernel::single('erpapi_autotask_timer_accountsales');
        $filedata = new vfapi_ttposfile;
        $funcLib = kernel::single('vfapi_func');
        
        //page_size文件生成记录行数
        $page_size = $cronSaleLib::$file_page_size;
        if(empty($page_size)){
            $error_msg = '没有配置文件生成记录行数;';
            return $this->error($error_msg);
        }
        
        //TTpos店铺对照列表
        base_kvstore::instance('vfapi')->fetch('storemap.ttpos', $ttposList);
        if(empty($ttposList)){
            $error_msg = '请先配置TTPOS店铺对照关系;';
            return $this->error($error_msg);
        }
        
        //filter
        $filter = $params['filter'];
        if(empty($filter)){
            $error_msg = '没有传指定的filter条件;';
            return $this->error($error_msg);
        }
        
        //count
        $countNum = $sapAftersaleObj->count($filter);
        if(empty($countNum)){
            $error_msg = '没有可操作的SAP销售单记录;';
            return $this->error($error_msg);
        }
        
        //page
        $pageNum = ceil($countNum / $page_size);
        for($page=1; $page<=$pageNum; $page++)
        {
            //渠道类型为[京东云仓]时,按照[售后单号]排序生成txt文件
            //@todo：京东结算单明细接口,订单有多个SKU时没有按订单排序给到中间件;
            if($filter['channel_type'] == 'jd_cloud' || $params['operation'] == 'manual'){
                $aftersaleList = $sapAftersaleObj->getList('*', $filter, 0, $page_size, 'XF_DOCNO ASC, XF_SALESLINENUM ASC');
            }else{
                $aftersaleList = $sapAftersaleObj->getList('*', $filter, 0, $page_size, 'id ASC');
            }
            
            //check
            if(empty($aftersaleList)){
                //$error_msg = '没有可以处理的SAP销售单数据';
                continue;
            }
            
            //获取异常订单列表
            $abnormalOrders = array();
            if(in_array($aftersaleList[0]['channel_type'], array('jd_account', 'jd_cloud'))){
                $orderBns = $funcLib->_array_column($aftersaleList, 'XF_ORGDOCNO');
                $orderBns = array_unique($orderBns);
                
                //list
                $abnormalOrders = $sapAftersaleObj->getList('id,XF_ORGDOCNO,XF_DOCNO', array('XF_ORGDOCNO'=>$orderBns, 'is_abnormal'=>'true'));
                if($abnormalOrders){
                    $abnormalOrders = $funcLib->_array_column($abnormalOrders, null, 'XF_ORGDOCNO');
                }
            }
            
            //ids
            $ids = $funcLib->_array_column($aftersaleList, 'id');
            
            //setting
            $ttpos_bn = $aftersaleList[0]['XF_STORECODE']; //ttpos店铺编码
            $channel_type = $aftersaleList[0]['channel_type']; //渠道类型(jd_account：京东入仓,jd_cloud：京东云仓)
            $business_type = $aftersaleList[0]['business_type']; //冲红类型(duichong：对冲单据,jiesuan：结算单据)
            $file_line_num = 0;
            $todayDate = date('YmdHis', time());
            
            //按照渠道和业务设置不同的页码格式
            $file_page = $page;
            if($channel_type == 'jd_account'){
                if($business_type == 'duichong'){
                    $file_page = 110000 + $page;
                }elseif($business_type == 'jiesuan'){
                    $file_page = 120000 + $page;
                }else{
                    $file_page = 100000 + $page;
                }
            }elseif($channel_type == 'jd_cloud'){
                if($business_type == 'duichong'){
                    $file_page = 210000 + $page;
                }elseif($business_type == 'jiesuan'){
                    $file_page = 220000 + $page;
                }else{
                    $file_page = 200000 + $page;
                }
            }
            
            //获取文件名
            $fileParams = array(
                'ttpos_store' => $ttpos_bn, //TTpos编码
                'bill_type' => 'SR', //单据业务类型(SO：销售单,SR：售后单)
                'date' => $todayDate, //日期：年月日时分秒
                'file_page' => $file_page, //文件页码
            );
            $file_name = vfapi_filename::get_sap_sales_filename($fileParams);
            
            //file(文件名带目录路径)
            $filedata->setfile($file_name);
            $filedata->open();
            
            //list
            $aftersaleIds = array();
            foreach($aftersaleList as $aftersaleKey => $aftersaleInfo)
            {
                $id = $aftersaleInfo['id'];
                $order_bn = $aftersaleInfo['XF_ORGDOCNO'];
                
                //check
                if($aftersaleInfo['status']  == 'succ'){
                    continue;
                }
                
                if($aftersaleInfo['is_abnormal']  == 'true'){
                    $error_msg = '单据是异常状态,请检查';
                    
                    //update
                    $sapAftersaleObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    continue;
                }
                
                //检查订单是否有异常的SKU商品行
                //@todo：订单有多个SKU行，必须生成到同一个txt文件中，否则SAP财务会报错；
                if($abnormalOrders && $abnormalOrders[$order_bn]){
                    continue;
                }
                
                //文件行数据
                $line = $this->getAftersaleRowLine($aftersaleInfo);
                
                //写入内容
                $filedata->writeline($line);
                
                //line
                $file_line_num++;
                
                $aftersaleIds[$id] = $id;
            }
            
            //check
            if($file_line_num == 0){
                $error_msg = '没有可生成文件的售后行数据';
                
                //update
                $sapAftersaleObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$ids));
                
                //close
                $filedata->close();
                
                continue;
            }
    
            //过滤掉文件公共路径
            $file_name = substr($file_name, strlen(DATA_DIR));
            
            //保存FTP文件信息
            $fileSdf = array('file'=>$file_name, 'start_time'=>time(), 'end_time'=>time(), 'type'=>'aftersale', 'create_time'=>time());
            $file_id = $fileObj->insert($fileSdf);
            $filedata->close();
            
            //check
            if(!$file_id){
                $error_msg = '保存文件名：'. $file_name .'信息失败';
                
                //update
                $sapAftersaleObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$ids));
                
                continue;
            }
            
            //succ
            $sapAftersaleObj->update(array('status'=>'succ', 'filename'=>$file_name, 'last_modified'=>time()), array('id'=>$aftersaleIds));
        }
        
        //unset
        unset($ttposList, $aftersaleList, $ids, $aftersaleIds);
        
        return $this->succ();
    }
    
    /**
     * 获取销售单行数据
     * 
     * @param array $saleInfo
     * @return string
     */
    public function getSaleRowLine($saleInfo)
    {
        $mapper = array(
            'XF_TXDATE' => $saleInfo['XF_TXDATE'], //取sale_time中的日期
            'XF_TXTIME' => $saleInfo['XF_TXTIME'], //取sale_time中的时间
            'XF_STORECODE' => $saleInfo['XF_STORECODE'],
            'XF_TILLID' => $saleInfo['XF_TILLID'], //默认值00
            'XF_DOCNO' => $saleInfo['XF_DOCNO'],
            'XF_VIPCODE' => $saleInfo['XF_VIPCODE'], //默认空
            'XF_CASHIERSTAFFCODE' => $saleInfo['XF_CASHIERSTAFFCODE'], //默认值ECOMM
            'XF_SALESMAN' => $saleInfo['XF_SALESMAN'], //销售员工号 默认空
            'XF_SALESLINENUM' => $saleInfo['XF_SALESLINENUM'], //货品行号，自动生成
            'XF_PLU' => $saleInfo['XF_PLU'],
            'XF_ITEMLOTNUM' => $saleInfo['XF_ITEMLOTNUM'], //货品条码 默认* //@todo
            'XF_INVTTYPE' => $saleInfo['XF_INVTTYPE'], //默认值0
            'XF_QTYSOLD' => $saleInfo['XF_QTYSOLD'], //销售数量
            'XF_AMTSOLD' => $saleInfo['XF_AMTSOLD'], //销售金额,销售商品最终成交金 额
            'XF_MARKDOWNAMT' => $saleInfo['XF_MARKDOWNAMT'], //货品级的优惠总额/数量
            'XF_DISCOUNTAMT' => $saleInfo['XF_DISCOUNTAMT'], //折扣金额 默认0
            'XF_VIPDISCOUNTLESS' => $saleInfo['XF_VIPDISCOUNTLESS'], //贵宾折扣金额 默认0
            'XF_BONUS' => $saleInfo['XF_BONUS'], //贵宾积分 默认0
            'XF_SELUPRICE' => $saleInfo['XF_SELUPRICE'], //货品现销售价
            'XF_ORGUPRICE' => $saleInfo['XF_ORGUPRICE'], //原销售价
            'XF_ORGDOCNO' => $saleInfo['XF_ORGDOCNO'], //原始订单号
            'XF_PROMID' => $saleInfo['XF_PROMID'], //TTPOS 促销ID
            'XF_TAOBAO_PROMID' => $saleInfo['XF_TAOBAO_PROMID'], //淘宝促销ID
            'XF_REFUNDREASONCODE' => $saleInfo['XF_REFUNDREASONCODE'], //退货原因代码
            'XF_REMARK' => $saleInfo['XF_REMARK'], //备注
            'XF_TENDER' => $saleInfo['XF_TENDER'], //付款方式CA
            'XF_DISTRICT' => $saleInfo['XF_DISTRICT'], //收货人地区(省、市、区)
            'XF_TELEPHONE' => '',  //收货人电话(固定为空,平台手机号是加密的)
            //'XF_FULLNAME' => '', //收货人姓名(固定为空,平台手机号是加密的)
        );
        
        return $mapper;
    }
    
    /**
     * 获取销售单行数据
     * 
     * @param array $aftersaleInfo
     * @return string
     */
    public function getAftersaleRowLine($aftersaleInfo)
    {
        $mapper = array(
            'XF_TXDATE' => $aftersaleInfo['XF_TXDATE'], //取sale_time中的日期
            'XF_TXTIME' => $aftersaleInfo['XF_TXTIME'], //取sale_time中的时间
            'XF_STORECODE' => $aftersaleInfo['XF_STORECODE'],
            'XF_TILLID' => $aftersaleInfo['XF_TILLID'], //默认值00
            'XF_DOCNO' => $aftersaleInfo['XF_DOCNO'],
            'XF_VIPCODE' => $aftersaleInfo['XF_VIPCODE'], //默认空
            'XF_CASHIERSTAFFCODE' => $aftersaleInfo['XF_CASHIERSTAFFCODE'], //默认值ECOMM
            'XF_SALESMAN' => $aftersaleInfo['XF_SALESMAN'], //销售员工号 默认空
            'XF_SALESLINENUM' => $aftersaleInfo['XF_SALESLINENUM'], //货品行号，自动生成
            'XF_PLU' => $aftersaleInfo['XF_PLU'],
            'XF_ITEMLOTNUM' => $aftersaleInfo['XF_ITEMLOTNUM'], //货品条码 默认* //@todo
            'XF_INVTTYPE' => $aftersaleInfo['XF_INVTTYPE'], //默认值0
            'XF_QTYSOLD' => $aftersaleInfo['XF_QTYSOLD'], //销售数量
            'XF_AMTSOLD' => $aftersaleInfo['XF_AMTSOLD'], //销售金额,销售商品最终成交金 额
            'XF_MARKDOWNAMT' => $aftersaleInfo['XF_MARKDOWNAMT'], //货品级的优惠总额/数量
            'XF_DISCOUNTAMT' => $aftersaleInfo['XF_DISCOUNTAMT'], //折扣金额 默认0
            'XF_VIPDISCOUNTLESS' => $aftersaleInfo['XF_VIPDISCOUNTLESS'], //贵宾折扣金额 默认0
            'XF_BONUS' => $aftersaleInfo['XF_BONUS'], //贵宾积分 默认0
            'XF_SELUPRICE' => $aftersaleInfo['XF_SELUPRICE'], //货品现销售价
            'XF_ORGUPRICE' => $aftersaleInfo['XF_ORGUPRICE'], //原销售价
            'XF_ORGDOCNO' => $aftersaleInfo['XF_ORGDOCNO'], //原始订单号
            'XF_PROMID' => $aftersaleInfo['XF_PROMID'], //TTPOS 促销ID
            'XF_TAOBAO_PROMID' => $aftersaleInfo['XF_TAOBAO_PROMID'], //淘宝促销ID
            'XF_REFUNDREASONCODE' => $aftersaleInfo['XF_REFUNDREASONCODE'], //退货原因代码
            'XF_REMARK' => $aftersaleInfo['XF_REMARK'], //备注
            'XF_TENDER' => $aftersaleInfo['XF_TENDER'], //付款方式CA
            'XF_DISTRICT' => $aftersaleInfo['XF_DISTRICT'], //收货人地区(省、市、区)
            'XF_TELEPHONE' => '',  //收货人电话(固定为空,平台手机号是加密的)
            //'XF_FULLNAME' => '', //收货人姓名(固定为空,平台手机号是加密的)
        );
        
        return $mapper;
    }
    
    /**
     * 生成Sap销售单记录
     * 
     * @param array $params
     * @param int $page
     * @return array
     */
    public function createJdSapSales($params)
    {
        $sapSaleObj = app::get('vfapi')->model('sap_sales');
        $saleObj = app::get('vfapi')->model('account_sales');
        $saleItemMdl = app::get('vfapi')->model('account_sales_items');
        
        $syncSaleLib = kernel::single('vfapi_tasks_syncsales');
        $accountLib = kernel::single('vfapi_account');
        $funcLib = kernel::single('vfapi_func');
        
        //[config配置]京东账单店铺列表
        $error_msg = '';
        $jdShopList = $this->getJdCloudShop($error_msg);
        if(empty($jdShopList)){
            $error_msg = '获取云店失败：'. $error_msg;
            return $this->error($error_msg);
        }
        
        //setting
        $channel_type = 'jd_account';
        $fields = '*';
        $item_fields = '*';
        
        //filter
        $filter = $params['filter'];
        if(empty($filter)){
            $error_msg = '没有指定JD销售单查询条件';
            return $this->error($error_msg);
        }
        
        //page_size
        $page_size = $this->_page_size;
        
        //count
        $countNum = $saleObj->count($filter);
        if(empty($countNum)){
            $error_msg = '没有可操作的JD销售单数据';
            return $this->error($error_msg);
        }
        
        //page
        $pageNum = ceil($countNum / $page_size);
        for($page=1; $page<=$pageNum; $page++)
        {
            //getList
            $saleList = $saleObj->getList($fields, $filter, 0, $page_size, 'sale_id ASC');
            if(empty($saleList)){
                //没有查询到销售单数据
                continue;
            }
            
            //ids
            $saleIds = $funcLib->_array_column($saleList, 'sale_id');
            
            //items
            $itemList = array();
            $tempList = $saleItemMdl->getList($item_fields, array('sale_id'=>$saleIds), 0, -1);
            foreach((array)$tempList as $itemKey => $itemVal)
            {
                $sale_id = $itemVal['sale_id'];
                $item_id = $itemVal['item_id'];
                
                $itemList[$sale_id][$item_id] = $itemVal;
            }
            
            //list
            foreach($saleList as $saleKey => $saleVal)
            {
                $sale_id = $saleVal['sale_id'];
                
                //已经生成过SAP销售记录
                if($saleVal['status'] == 'succ'){
                    unset($saleList[$saleKey]);
                    
                    //$error_msg = '销售单号：'. $saleVal['sale_no'] .'已经生成过SAP销售记录;';
                    continue;
                }
                
                //check商品明细
                if(empty($itemList[$sale_id])){
                    unset($saleList[$saleKey]);
                    
                    $error_msg = 'JD销售单号：'. $saleVal['sale_no'] .'没有商品明细;';
                    
                    //update
                    $saleObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('sale_id'=>$sale_id));
                    
                    continue;
                }
                
                //items
                $saleList[$saleKey]['sales_items'] = $itemList[$sale_id];
            }
            
            //unset
            unset($tempList, $itemList);
            
            //check
            if(empty($saleList)){
                $error_msg = '没有有效的JD销售数据;';
                
                continue;
            }
            
            //format
            $soldList = array();
            $dataList = array();
            $skuBns = array();
            foreach($saleList as $key => $val)
            {
                $sale_id = $val['sale_id'];
                $sale_bn = $val['sale_no'];
                $order_no = $val['order_no'];
                $shop_bn = $val['shop_bn'];
                
                //shopInfo(获取ttpos编码)
                $shopInfo = $jdShopList[$shop_bn];
                $val['ttpos_store'] = $shopInfo['ttpos_store'];
                
                //check
                if(in_array($val['status'], array('succ'))){
                    //单据已经处理,不允许重复操作
                    $error_msg = 'JD销售单据已经处理,不允许重复操作!';
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                //ttpos_store编码
                if(empty($val['ttpos_store'])){
                    $error_msg = 'JD销售单没有ttpos_store编码!';
                    
                    //update
                    $saleObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('sale_id'=>$sale_id));
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                //是否生成销售文件配置开关
                if($shopInfo['is_sale_file'] == 'false'){
                    $error_msg = '是否生成销售文件：未启用';
                    
                    //update
                    $saleObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('sale_id'=>$sale_id));
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                //items
                $saleItems = $val['sales_items'];
                
                //格式化赠品数据
                //$saleItems = $this->formatSaleItems($saleItems, $erpProductList);
                
                //check
                if(empty($saleItems)){
                    $error_msg = '格式化销售单明细为空!';
                    
                    //update
                    $saleObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('sale_id'=>$sale_id));
                    
                    continue;
                }
                
                //sole_bn
                foreach ($saleItems as $itemKey => $itemVal)
                {
                    $sku_bn = $itemVal['bn'];
                    
                    //唯一性编码 = 销售单号 + 订单号 + sku_bn
                    $sole_bn = $sale_bn .'_'. $order_no .'_'. $sku_bn;
                    $soldList[$sole_bn] = $sole_bn;
                    
                    //skus
                    $skuBns[$sku_bn] = $sku_bn;
                }
                
                //merge
                $val['sales_items'] = $saleItems;
                
                //data
                $dataList[$sale_id] = $val;
            }
            
            //unset
            unset($saleList, $saleItems);
            
            //check
            if(empty($dataList)){
                //没有有效的数据
                $error_msg = 'JD销售单没有有效的数据';
                
                //return error
                if($countNum == 1){
                    return $this->error($error_msg);
                }
                
                continue;
            }
            
            //已经存在的数据
            $existData = array();
            if($soldList){
                $existData = $sapSaleObj->getList('id,sole_bn', array('sole_bn'=>$soldList));
                if($existData){
                    $existData = $funcLib->_array_column($existData, null, 'sole_bn');
                }
            }
            
            //固定折扣价格(这里修改相关判断条件，查询获取导入的固定折扣价格)
            $getPriceList = $this->getOmsSkuPriceList($skuBns, 'shop_sku_id');
            
            //[京东云仓]按订单号获取已经存在的销售单
            $orderBns = $funcLib->_array_column($dataList, 'order_no');
            $sapSaleList = $sapSaleObj->getList('id,XF_ORGDOCNO,channel_type', array('channel_type'=>'jd_cloud', 'XF_ORGDOCNO'=>$orderBns));
            if($sapSaleList){
                $sapSaleList = $funcLib->_array_column($sapSaleList, null, 'XF_ORGDOCNO');
            }
            
            //list
            foreach ($dataList as $saleId => $saleVal)
            {
                $sale_id = $saleVal['sale_id'];
                $sale_bn = $saleVal['sale_no'];
                $order_no = $saleVal['order_no'];
                $shop_bn = $saleVal['shop_bn'];
                
                //shopInfo
                $shopInfo = $jdShopList[$shop_bn];
                
                //format
                $saleVal['operation'] = 'create_sap_sale'; //标记为创建Sap销售记录
                
                //销售时间使用当前操作的时间戳
                $sale_time = time();
                $saleVal['sale_time'] = date('Y-m-d H:i:s', $sale_time);
                
                //check京东云仓已经存在销售单
                if($sapSaleList[$order_no]){
                    $error_msg = '京东云仓已经存在销售单,不允许重复生成';
                    
                    //update
                    $saleObj->update(array('channel_type'=>'jd_cloud', 'status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('sale_id'=>$sale_id));
                    
                    //return error
                    if($countNum == 1){
                        return $this->error($error_msg);
                    }
                    
                    continue;
                }
                
                //items
                $linenum = 0;
                foreach ($saleVal['sales_items'] as $itemKey => $itemVal)
                {
                    $sku_bn = $itemVal['bn'];
                    
                    //唯一性编码 = 销售单号 + 订单号 + sku_bn
                    $sole_bn = $sale_bn .'_'. $order_no .'_'. $sku_bn;
                    
                    //check
                    if($existData[$sole_bn]){
                        $error_msg = 'JD销售单已经生成过Sap销售记录';
                        
                        //update
                        $saleObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('sale_id'=>$sale_id));
                        
                        //return error
                        if($countNum == 1){
                            return $this->error($error_msg);
                        }
                        
                        continue;
                    }
                    
                    $linenum++;
                    
                    //固定折扣价格(获取导入的固定折扣价格)
                    $is_oms_price = true;
                    $priceInfo = $getPriceList[$sku_bn];
                    $sale_price = 0;
                    if($priceInfo){
                        $sale_price = ($priceInfo['sale_price'] ? $priceInfo['sale_price'] : 0);
                        
                        $itemVal['bn'] = $priceInfo['product_bn']; //OMS货号
                        $itemVal['barcode'] = $priceInfo['barcode']; //条形码
                        $itemVal['price'] = $priceInfo['price']; //货品单价
                        $itemVal['sales_amount'] = ($sale_price * $itemVal['nums']); //销售金额 = 销售单价 * 数量
                    }else{
                        $itemVal['bn'] = ''; //OMS货号
                        $itemVal['barcode'] = ''; //条形码
                        $itemVal['price'] = 0; //货品单价
                        $itemVal['sales_amount'] = $sale_price; //销售金额 = 销售单价 * 数量
                        
                        $is_oms_price = false;
                    }
                    
                    //getData
                    $mainSdf = $syncSaleLib->get_row($saleVal, $itemVal, $linenum, $shopInfo);
                    
                    //merge
                    $mainSdf['sole_bn'] = $sole_bn; //唯一编码
                    $mainSdf['sku_bn'] = $sku_bn; //店铺商品ID
                    $mainSdf['channel_type'] = $channel_type; //来源渠道：oms、jd_edi、vip_edi
                    $mainSdf['shop_bn'] = $shop_bn; //店铺编码
                    $mainSdf['sale_time'] = $sale_time;
                    $mainSdf['create_time'] = time();
                    
                    //渠道类型
                    $mainSdf['channel_type'] = 'jd_account'; //京东入仓
                    
                    //标记固定折扣价格
                    if(!$is_oms_price){
                        //读取价格失败,标记异常
                        $mainSdf['is_abnormal'] = 'true';
                        $mainSdf['error_msg'] = '获取导入固定折扣价格失败';
                    }elseif($mainSdf['XF_ORGUPRICE'] <= 0){
                        //货品price销售价在OMS系统里未维护
                        $mainSdf['is_abnormal'] = 'true';
                        $mainSdf['error_msg'] = '读取OMS货品销售价为0元';
                    }else{
                        $mainSdf['is_abnormal'] = 'false';
                    }
                    
                    //insert
                    $insert_id = $sapSaleObj->insert($mainSdf);
                    if(!$insert_id){
                        $error_msg = 'JD销售单创建Sap销售记录失败';
                        
                        //update
                        $saleObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('sale_id'=>$sale_id));
                        
                        //return error
                        if($countNum == 1){
                            return $this->error($error_msg);
                        }
                        
                        continue;
                    }
                    
                    //update
                    $saleObj->update(array('status'=>'succ', 'last_modified'=>time()), array('sale_id'=>$sale_id));
                }
            }
        }
        
        return $this->succ();
    }
    
    /**
     * 获取SKU导入的固定折扣价格(SKU不限制所属渠道类型)
     * 
     * @param $saleInfo
     * @return void
     */
    public function getImportPriceList($productBns, $channel_type='京东云仓')
    {
        $skuObj = app::get('vfapi')->model('shop_skus');
        $priceObj = app::get('edi')->model('productprice');
        
        $funcLib = kernel::single('vfapi_func');
        
        //check
        if(empty($productBns)){
            return array();
        }
        
        //getList
        //$filter = array('channel_type'=>$channel_type, 'product_bn'=>$productBns);
        $filter = array('product_bn'=>$productBns);
        $priceList = $priceObj->getList('id,product_bn,price', $filter);
        if(empty($priceList)){
            return array();
        }
        
        //获取OMS货品信息
        $skuList = array();
        $filter = array('product_bn'=>$productBns);
        $tempList = $skuObj->getList('id,shop_sku_id,product_bn,barcode,price', $filter);
        if($tempList){
            $skuList = $funcLib->_array_column($tempList, null, 'product_bn');
        }
        
        //format
        $dataList = array();
        foreach($priceList as $key => $val)
        {
            $product_bn = $val['product_bn'];
            $skuInfo = $skuList[$product_bn];
            
            //sku_id
            $dataList[$product_bn] = array(
                'product_bn' => $product_bn,
                'sale_price' => $val['price'], //单个商品的销售价
            );
            
            //OMS货品信息
            if($skuInfo){
                $dataList[$product_bn]['shop_sku_id'] = $skuInfo['shop_sku_id']; //店铺货品ID
                $dataList[$product_bn]['barcode'] = $skuInfo['barcode']; //条形码
                $dataList[$product_bn]['price'] = $skuInfo['price']; //货品单价
            }
        }
        
        //unset
        unset($productBns, $priceList, $tempList, $skuList);
        
        return $dataList;
    }
    
    /**
     * 批量获取OMS系统里的货品价格
     * 
     * @param $saleInfo
     * @param $mode 读取模式(shop_sku_id：按店铺货号ID，product_bn：按货品编码)
     * @return void
     */
    public function getOmsSkuPriceList($skuBns, $mode='shop_sku_id')
    {
        $skuObj = app::get('vfapi')->model('shop_skus');
        $priceObj = app::get('edi')->model('productprice');
        
        $funcLib = kernel::single('vfapi_func');
        
        //check
        if(empty($skuBns)){
            return array();
        }
        
        //获取OMS货品信息
        if($mode == 'shop_sku_id'){
            $filter = array('shop_sku_id'=>$skuBns);
        }else{
            $filter = array('product_bn'=>$skuBns);
        }
        
        $skuList = $skuObj->getList('id,shop_sku_id,product_bn,barcode,price', $filter);
        if(empty($skuList)){
            return array();
        }
        
        //product_bn
        $productBns = $funcLib->_array_column($skuList, 'product_bn');
        
        //filter
        $filter = array('product_bn'=>$productBns);
        //$filter['channel_type'] = '';
        
        //getList
        $priceList = $priceObj->getList('id,channel_type,product_bn,price', $filter);
        if(empty($priceList)){
            return array();
        }
        
        $priceList = $funcLib->_array_column($priceList, null, 'product_bn');
        
        //format
        $dataList = array();
        foreach($skuList as $key => $val)
        {
            $shop_sku_id = $val['shop_sku_id'];
            $product_bn = $val['product_bn'];
            $priceInfo = $priceList[$product_bn];
            
            //check
            if(empty($priceInfo)){
                continue;
            }
            
            //[渠道类型]优先使用京东云仓类型
            $channel_type = '';
            if($priceInfo['channel_type'] == '京东入仓' && $dataList[$shop_sku_id]['channel_type'] != 'jd_cloud'){
                $channel_type = 'jd_account';
            }elseif($priceInfo['channel_type'] == '京东云仓'){
                $channel_type = 'jd_cloud';
            }elseif($priceInfo['channel_type'] == '唯品会'){
                $channel_type = 'vip';
            }
            
            //sku_id
            $dataList[$shop_sku_id] = array(
                'shop_sku_id' => $shop_sku_id,
                'product_bn' => $product_bn,
                'barcode' => $val['barcode'],
                'price' => $val['price'], //货品单价
                'sale_price' => $priceInfo['price'], //单个商品的销售价
                'channel_type' => $channel_type, //渠道类型
            );
        }
        
        //unset
        unset($skuIds, $skuList, $productBns, $priceList);
        
        return $dataList;
    }
    
    /**
     * 批量获取京东实销实结SKU货品属于渠道类型(京东入仓、京东云仓)
     * @todo：当SKU既是：京东入仓，又是京东云仓，则优先SKU为：京东入仓；
     * 
     * @param $saleInfo
     * @param $mode 读取模式(shop_sku_id：按店铺货号ID，product_bn：按货品编码)
     * @return void
     */
    public function getSapProductTypes($skuBns, $mode='shop_sku_id')
    {
        $skuObj = app::get('vfapi')->model('shop_skus');
        $priceObj = app::get('edi')->model('productprice');
        
        $funcLib = kernel::single('vfapi_func');
        
        //check
        if(empty($skuBns)){
            return array();
        }
        
        //获取OMS货品信息
        if($mode == 'shop_sku_id'){
            $filter = array('shop_sku_id'=>$skuBns);
        }else{
            $filter = array('product_bn'=>$skuBns);
        }
        
        $skuList = $skuObj->getList('id,shop_sku_id,product_bn,barcode,price', $filter);
        if(empty($skuList)){
            return array();
        }
        
        //product_bn
        $productBns = $funcLib->_array_column($skuList, 'product_bn');
        
        //filter
        $filter = array('product_bn'=>$productBns);
        //$filter['channel_type'] = '';
        
        //getList
        $priceList = $priceObj->getList('id,product_bn,price,channel_type', $filter);
        if(empty($priceList)){
            return array();
        }
        
        $priceList = $funcLib->_array_column($priceList, null, 'product_bn');
        
        //format
        $dataList = array();
        foreach($skuList as $key => $val)
        {
            $shop_sku_id = $val['shop_sku_id'];
            $product_bn = $val['product_bn'];
            $priceInfo = $priceList[$product_bn];
            
            //check
            if(empty($priceInfo)){
                continue;
            }
            
            //check渠道类型
//            if(!in_array($priceInfo['channel_type'], array('京东入仓', '京东云仓'))){
//                continue;
//            }
            
            //优先使用【京东入仓】类型
            if($priceInfo['channel_type'] == '京东入仓'){
                $val['channel_type'] = 'jd_account';
            }elseif($priceInfo['channel_type'] == '京东云仓' && $dataList[$shop_sku_id]['channel_type'] != 'jd_account'){
                $val['channel_type'] = 'jd_cloud';
            }else{
                //无效的类型,跳过
                continue;
            }
            
            //sku_id
            $dataList[$shop_sku_id] = array(
                'channel_type' => $val['channel_type'], //渠道类型：京东入仓、京东云仓
                'shop_sku_id' => $shop_sku_id,
                'product_bn' => $product_bn,
                'barcode' => $val['barcode'],
                //'price' => $val['price'], //货品单价
                //'sale_price' => $priceList[$product_bn]['price'], //单个商品的销售价
            );
        }
        
        //unset
        unset($skuIds, $skuList, $productBns, $priceList);
        
        return $dataList;
    }
    
    /**
     * [对冲]生成对冲销售单
     * 
     * @param array $params
     * @return array
     */
    public function blushSapSales($params)
    {
        $sapSaleObj = app::get('vfapi')->model('sap_sales');
        $sapAftersaleObj = app::get('vfapi')->model('sap_aftersales');
        
        //aftersaleInfo
        $sole_bn = $params['sole_bn'];
        $aftersaleInfo = $sapAftersaleObj->dump(array('sole_bn'=>$sole_bn), '*');
        if(empty($aftersaleInfo)){
            $error_msg = '没有找到售后单数据';
            return $this->error($error_msg);
        }
        
        //对冲必须使用当前操作的时间
        $dateline = time();
        
        //对冲单据号
        //@todo：SAP支持20个字符，但SAP自己会加上4位店铺编码，中间件只能16位字符；
        $dc_bill_bn = $this->getArdcBillBn($aftersaleInfo['XF_DOCNO']);
        
        //aftersale
        //$aftersaleInfo['XF_DOCNO'] = 'ARDC'. $aftersaleInfo['XF_DOCNO'];
        $aftersaleInfo['XF_DOCNO'] = $dc_bill_bn;
    
        $aftersaleInfo['business_type'] = 'duichong'; //对冲单据
        $aftersaleInfo['sale_time'] = $dateline;
        $aftersaleInfo['XF_TXDATE'] = date('Ymd', $dateline);
        $aftersaleInfo['XF_TXTIME'] = date('His', $dateline);
        $aftersaleInfo['create_time'] = $dateline;
        $aftersaleInfo['last_modified'] = $dateline;
        
        //唯一性编码 = 对冲售后单号 + 货号
        $sole_bn = $aftersaleInfo['XF_DOCNO'] .'_'. $aftersaleInfo['XF_PLU'];
        $aftersaleInfo['sole_bn'] = $sole_bn;
        
        //销售数量及销售总金额转换为正数
        $aftersaleInfo['XF_QTYSOLD'] = abs($aftersaleInfo['XF_QTYSOLD']);
        $aftersaleInfo['XF_AMTSOLD'] = abs($aftersaleInfo['XF_AMTSOLD']);
        $aftersaleInfo['XF_MARKDOWNAMT'] = abs($aftersaleInfo['XF_MARKDOWNAMT']);
        $aftersaleInfo['XF_DISCOUNTAMT'] = abs($aftersaleInfo['XF_DISCOUNTAMT']);
        $aftersaleInfo['XF_SELUPRICE'] = abs($aftersaleInfo['XF_SELUPRICE']);
        $aftersaleInfo['XF_ORGUPRICE'] = abs($aftersaleInfo['XF_ORGUPRICE']);
        
        //unset
        unset($aftersaleInfo['id'], $aftersaleInfo['status'], $aftersaleInfo['is_abnormal']);
        unset($aftersaleInfo['filename'], $aftersaleInfo['error_msg'], $aftersaleInfo['aftersale_time']);
        
        //查询是否已存在
        $sapSaleInfo = $sapSaleObj->dump(array('sole_bn'=>$aftersaleInfo['sole_bn']), 'id');
        if($sapSaleInfo){
            $error_msg = '对冲销售单号'. $aftersaleInfo['XF_DOCNO'] .'已经存在';
            return $this->error($error_msg);
        }
        
        //insert
        $insert_id = $sapSaleObj->insert($aftersaleInfo);
        if(!$insert_id){
            $error_msg = '创建对冲Sap销售单失败!';
            
            return $this->error($error_msg);
        }
        
        return $this->succ();
    }
    
    /**
     * [对冲]生成对冲售后单
     * 
     * @param array $params
     * @return array
     */
    public function blushSapAftersales($params)
    {
        $sapSaleObj = app::get('vfapi')->model('sap_sales');
        $sapAftersaleObj = app::get('vfapi')->model('sap_aftersales');
        
        //saleInfo
        $sole_bn = $params['sole_bn'];
        $saleInfo = $sapSaleObj->dump(array('sole_bn'=>$sole_bn), '*');
        if(empty($saleInfo)){
            $error_msg = '没有找到售后单数据';
            return $this->error($error_msg);
        }
        
        //对冲必须使用当前操作的时间
        $dateline = time();
        
        //对冲单据号
        //@todo：SAP支持20个字符，但SAP自己会加上4位店铺编码，中间件只能16位字符；
        $dc_bill_bn = $this->getArdcBillBn($saleInfo['XF_DOCNO']);
        
        //sale
        //$saleInfo['XF_DOCNO'] = 'ARDC'. $saleInfo['XF_DOCNO'];
        $saleInfo['XF_DOCNO'] = $dc_bill_bn;
        
        $saleInfo['business_type'] = 'duichong'; //对冲单据
        $saleInfo['aftersale_time'] = $dateline;
        $saleInfo['XF_TXDATE'] = date('Ymd', $dateline);
        $saleInfo['XF_TXTIME'] = date('His', $dateline);
        $saleInfo['create_time'] = $dateline;
        $saleInfo['last_modified'] = $dateline;
        
        //唯一性编码 = 对冲售后单号 + 货号
        $sole_bn = $saleInfo['XF_DOCNO'] .'_'. $saleInfo['XF_PLU'];
        $saleInfo['sole_bn'] = $sole_bn;
        
        //销售数量及销售总金额转换为负数
        if($saleInfo['XF_QTYSOLD'] > 0){
            $saleInfo['XF_QTYSOLD'] = $saleInfo['XF_QTYSOLD'] * -1;
        }
        
        if($saleInfo['XF_AMTSOLD']){
            $saleInfo['XF_AMTSOLD'] = $saleInfo['XF_AMTSOLD'] * -1;
        }
        
        //unset
        unset($saleInfo['id'], $saleInfo['status'], $saleInfo['is_abnormal']);
        unset($saleInfo['filename'], $saleInfo['error_msg'], $saleInfo['sale_time']);
        
        //查询是否已存在
        $sapAfterSaleInfo = $sapAftersaleObj->dump(array('sole_bn'=>$saleInfo['sole_bn']), 'id');
        if($sapAfterSaleInfo){
            $error_msg = '对冲售后单号'. $saleInfo['XF_DOCNO'] .'已经存在';
            return $this->error($error_msg);
        }
        
        //insert
        $insert_id = $sapAftersaleObj->insert($saleInfo);
        if(!$insert_id){
            $error_msg = '生成对冲SAP售后单失败!';
            
            return $this->error($error_msg);
        }
        
        return $this->succ();
    }
    
    /**
     * [结算]创建对冲结算的新销售单
     * 
     * @param array $params
     * @return array
     */
    public function createJsSales($params)
    {
        $sapSaleObj = app::get('vfapi')->model('sap_sales');
        
        //saleInfo
        $sole_bn = $params['sole_bn'];
        $saleInfo = $sapSaleObj->dump(array('sole_bn'=>$sole_bn), '*');
        if(empty($saleInfo)){
            $error_msg = '没有找到销售单数据';
            return $this->error($error_msg);
        }
        
        //结算必须使用当前操作的时间
        $dateline = time();
        
        //quantity
        $quantity = intval($params['quantity']);
        
        //格式化金额
        $price = $saleInfo['XF_ORGUPRICE'];
        $sale_price = ($params['bills_amount'] / $quantity); //单件销售价格 = 单据金额 / 数量
        $sale_price = number_format($sale_price, 3, '.', '');
        $pmt_price = $price - $sale_price;
        
        //获取结算单号
        //@todo：SAP支持20个字符，但SAP自己会加上4位店铺编码，中间件只能16位字符；
        $js_bill_bn = $this->getArjsBillBn($saleInfo['XF_DOCNO']);
        
        //sale
        //$saleInfo['XF_DOCNO'] = 'ARJS'. $saleInfo['XF_DOCNO'];
        $saleInfo['XF_DOCNO'] = $js_bill_bn;
        
        $saleInfo['business_type'] = 'jiesuan'; //对冲单据
        $saleInfo['sale_time'] = $dateline;
        $saleInfo['create_time'] = $dateline;
        $saleInfo['last_modified'] = $dateline;
        
        //日期时间
        $saleInfo['XF_TXDATE'] = date('Ymd', $dateline);
        $saleInfo['XF_TXTIME'] = date('His', $dateline);
        
        //数量
        $saleInfo['XF_QTYSOLD'] = $quantity;
        
        //金额
        $saleInfo['XF_AMTSOLD'] = $params['bills_amount']; //销售总金额(必须使用单据金额)
        $saleInfo['XF_MARKDOWNAMT'] = $pmt_price; //单件货品优惠 = 货品级的优惠总额 / 数量
        $saleInfo['XF_DISCOUNTAMT'] = 0; //折扣金额 默认0
        $saleInfo['XF_VIPDISCOUNTLESS'] = 0; //贵宾折扣金额 默认0
        $saleInfo['XF_SELUPRICE'] = $sale_price; //销售单价 = 销售总金额 / 数量
        $saleInfo['XF_ORGUPRICE'] = $price; //原销售价(单价)
        
        //唯一性编码 = 对冲售后单号 + 货号
        $sole_bn = $saleInfo['XF_DOCNO'] .'_'. $saleInfo['XF_PLU'];
        $saleInfo['sole_bn'] = $sole_bn;
        
        //unset
        unset($saleInfo['id'], $saleInfo['status'], $saleInfo['is_abnormal']);
        unset($saleInfo['filename'], $saleInfo['error_msg']);
        
        //查询是否已存在
        $sapSaleInfo = $sapSaleObj->dump(array('sole_bn'=>$saleInfo['sole_bn']), 'id');
        if($sapSaleInfo){
            $error_msg = '结算销售单号'. $saleInfo['XF_DOCNO'] .'已经存在';
            return $this->error($error_msg);
        }
        
        //insert
        $insert_id = $sapSaleObj->insert($saleInfo);
        if(!$insert_id){
            $error_msg = '创建对冲Sap销售单失败!';
            
            return $this->error($error_msg);
        }
        
        return $this->succ();
    }
    
    /**
     * [结算]创建对冲结算的新售后单
     * 
     * @param array $params
     * @return array
     */
    public function createJsAftersales($params)
    {
        $sapAftersaleObj = app::get('vfapi')->model('sap_aftersales');
        
        //aftersaleInfo
        $sole_bn = $params['sole_bn'];
        $aftersaleInfo = $sapAftersaleObj->dump(array('sole_bn'=>$sole_bn), '*');
        if(empty($aftersaleInfo)){
            $error_msg = '没有找到售后单数据';
            return $this->error($error_msg);
        }
        
        //对冲必须使用当前操作的时间
        $dateline = time();
        
        //quantity
        $quantity = intval($params['quantity']);
        
        //格式化金额
        $price = $aftersaleInfo['XF_ORGUPRICE'];
        $sale_price = ($params['bills_amount'] / $quantity); //单件销售价格 = 单据金额 / 数量
        $sale_price = number_format($sale_price, 3, '.', '');
        $pmt_price = $price - $sale_price;
        
        //获取结算单号
        //@todo：SAP支持20个字符，但SAP自己会加上4位店铺编码，中间件只能16位字符；
        $js_bill_bn = $this->getArjsBillBn($aftersaleInfo['XF_DOCNO']);
        
        //sale
        //$aftersaleInfo['XF_DOCNO'] = 'ARJS'. $aftersaleInfo['XF_DOCNO'];
        $aftersaleInfo['XF_DOCNO'] = $js_bill_bn;
        
        $aftersaleInfo['business_type'] = 'jiesuan'; //对冲单据
        $aftersaleInfo['aftersale_time'] = $dateline;
        $aftersaleInfo['create_time'] = $dateline;
        $aftersaleInfo['last_modified'] = $dateline;
        
        //日期时间
        $aftersaleInfo['XF_TXDATE'] = date('Ymd', $dateline);
        $aftersaleInfo['XF_TXTIME'] = date('His', $dateline);
        
        //数量(必须为负数)
        if($quantity > 0){
            $aftersaleInfo['XF_QTYSOLD'] = $quantity * -1;
        }else{
            $aftersaleInfo['XF_QTYSOLD'] = $quantity;
        }
        
        //退货总金额(必须为负数，并且必须使用单据金额)
        if($params['bills_amount'] > 0){
            $aftersaleInfo['XF_AMTSOLD'] = $params['bills_amount'] * -1;
        }else{
            $aftersaleInfo['XF_AMTSOLD'] = $params['bills_amount'];
        }
        
        //金额
        $aftersaleInfo['XF_MARKDOWNAMT'] = $pmt_price; //单件货品 = 货品级的优惠总额 / 数量
        $aftersaleInfo['XF_DISCOUNTAMT'] = 0; //折扣金额 默认0
        $aftersaleInfo['XF_VIPDISCOUNTLESS'] = 0; //贵宾折扣金额 默认0
        $aftersaleInfo['XF_SELUPRICE'] = $sale_price; //销售单价 = 销售总金额 / 数量
        $aftersaleInfo['XF_ORGUPRICE'] = $price; //原销售价(单价)
        
        //唯一性编码 = 对冲售后单号 + 货号
        $sole_bn = $aftersaleInfo['XF_DOCNO'] .'_'. $aftersaleInfo['XF_PLU'];
        $aftersaleInfo['sole_bn'] = $sole_bn;
        
        //unset
        unset($aftersaleInfo['id'], $aftersaleInfo['status'], $aftersaleInfo['is_abnormal']);
        unset($aftersaleInfo['filename'], $aftersaleInfo['error_msg']);
        
        //查询是否已存在
        $sapAfterSaleInfo = $sapAftersaleObj->dump(array('sole_bn'=>$aftersaleInfo['sole_bn']), 'id');
        if($sapAfterSaleInfo){
            $error_msg = '结算售后单号'. $aftersaleInfo['XF_DOCNO'] .'已经存在';
            return $this->error($error_msg);
        }
        
        //insert
        $insert_id = $sapAftersaleObj->insert($aftersaleInfo);
        if(!$insert_id){
            $error_msg = '创建对冲Sap售后单失败!';
            
            return $this->error($error_msg);
        }
        
        return $this->succ();
    }
    
    /**
     * 通过订单号获取SAP销售单渠道类型
     * 
     * @param array $params
     * @return array
     */
    public function getSaleChannelType($settOrderInfo)
    {
        $sapSaleObj = app::get('vfapi')->model('sap_sales');
        
        //order_bn
        $order_bn = $settOrderInfo['orderNo'];
        
        //check
        if(!empty($settOrderInfo['channel_type']) && $settOrderInfo['channel_type'] != 'none'){
            return $settOrderInfo['channel_type'];
        }
        
        //获取最新的销售单据的渠道类型
        $sapSaleInfo = $sapSaleObj->getList('id,XF_ORGDOCNO,channel_type', array('XF_ORGDOCNO'=>$order_bn), 0, 1, 'sale_time DESC');
        if($sapSaleInfo[0]['channel_type']){
            return $sapSaleInfo[0]['channel_type'];
        }
        
        //采购类型(售后退货时没有值)
        if($settOrderInfo['xniName'] == 'VMI共享库存单'){
            return 'jd_cloud';
        }
        
        return 'none';
    }
    
    /**
     * 修复异常Sap销售单
     * 
     * @param array $params
     * @return array
     */
    public function repairSalesAbnormal($params)
    {
        $sapSaleObj = app::get('vfapi')->model('sap_sales');
        $operLogObj = app::get('erpapi')->model('operation_log');
        
        $funcLib = kernel::single('vfapi_func');
        
        //filter
        $filter = $params['filter'];
        if(empty($filter)){
            $error_msg = '没有指定查询条件!';
            return $this->error($error_msg);
        }
        
        //page_size
        $page_size = $this->_page_size;
        
        //count
        $countNum = $sapSaleObj->count($filter);
        if(empty($countNum)){
            $error_msg = '没有可操作的SAP销售单记录!';
            return $this->error($error_msg);
        }
        
        //page
        $pageNum = ceil($countNum / $page_size);
        $fail_count = 0;
        $succ_count = 0;
        for($page=1; $page<=$pageNum; $page++)
        {
            $offset = ($page - 1) * $page_size;
            
            //list
            $saleList = $sapSaleObj->getList('*', $filter, $offset, $page_size, 'id ASC');
            if(empty($saleList)){
                $fail_count++;
                
                //$error_msg = '没有可以处理的SAP销售单数据';
                continue;
            }
            
            //京东SKU导入的固定折扣
            //@todo：目前导入的固定折扣的SKU不管属于哪个渠道，只要有导入价格就可以;
            $skuBns = $funcLib->_array_column($saleList, 'sku_bn');
            $skuList = $this->getOmsSkuPriceList($skuBns, 'shop_sku_id');
            
            //通过货号获取导入的固定折扣金额
            //@todo：SKU不限制属于哪个渠道，只要有导入价格就可以;
            $productBns = $funcLib->_array_column($saleList, 'XF_PLU');
            $getPriceList = $this->getImportPriceList($productBns);
            
            //list
            foreach ($saleList as $key => $val)
            {
                $id = $val['id'];
                $sku_bn = $val['sku_bn'];
                $product_bn = $val['XF_PLU'];
                $nums = intval($val['XF_QTYSOLD']);
                $order_bn = $val['XF_ORGDOCNO'];
                
                //check
                if($val['is_abnormal'] != 'true'){
                    $fail_count++;
                    continue;
                }
                
                if(empty($sku_bn) && empty($product_bn)){
                    $fail_count++;
                    
                    //update
                    $error_msg = 'XF_PLU货号、sku_bn店铺商品ID都为空,请检查!';
                    $sapSaleObj->update(array('error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    continue;
                }
                
                //check
                if(!in_array($val['channel_type'], array('jd_account', 'jd_cloud'))){
                    $fail_count++;
                    
                    //update
                    $error_msg = '渠道类型为：'. $val['channel_type'] .'无法处理';
                    $sapSaleObj->update(array('error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    continue;
                }
                
                //检查货号、价格、数量、条形码、price单价、销售总金额是否存在&&并且原价大于0元
                if($val['XF_PLU'] && $val['XF_ITEMLOTNUM'] && $val['XF_QTYSOLD'] && $val['XF_ORGUPRICE'] && $val['XF_AMTSOLD'] && $val['XF_ORGUPRICE'] > 0){
                    $succ_count++; //成功更新标识
                    
                    //update
                    $error_msg = '货号、价格、数量、条形码都存在,去除异常标识;';
                    
                    //更新为当前年月日+时分秒
                    $updateSdf = array(
                        'is_abnormal' => 'false',
                        'status' => 'none',
                        'XF_TXDATE' => date('Ymd', time()),
                        'XF_TXTIME' => date('His', time()),
                        'error_msg' => $error_msg,
                        'last_modified' => time(),
                    );
                    
                    $sapSaleObj->update($updateSdf, array('id'=>$id));
                    
                    //logs
                    $operLogObj->write_log('sap_sales@vfapi', $id, $error_msg);
                    
                    continue;
                }
                
                //依据渠道类型获取货品信息
                if($getPriceList[$product_bn]){
                    //bn
                    $skuInfo = $getPriceList[$product_bn];
                }else{
                    //sku
                    $skuInfo = $skuList[$sku_bn];
                }
                
                //check
                if(empty($skuInfo)){
                    $fail_count++;
                    $error_msg = 'product_bn：'. $product_bn .'获取固定折扣价、店铺货品映射信息失败!';
                    
                    //update
                    $sapSaleObj->update(array('error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    continue;
                }
                
                if(empty($skuInfo['product_bn']) || empty($skuInfo['barcode']) || empty($skuInfo['shop_sku_id'])){
                    $fail_count++;
                    $error_msg = 'product_bn：'. $product_bn .'获取店铺货品ID、条形码、固定折扣价失败,请检查!';
                    
                    //update
                    $sapSaleObj->update(array('error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    continue;
                }
                
                //金额
                $sales_amount = $skuInfo['sale_price'] * $nums;
                $pmt_price = $skuInfo['price'] - $skuInfo['sale_price'];
                
                //save
                $saveData = array(
                    'XF_PLU' => $skuInfo['product_bn'], //货号
                    'XF_ITEMLOTNUM' => $skuInfo['barcode'], //条形码
                    'XF_ORGUPRICE' => $skuInfo['price'], //Price原价
                    'XF_SELUPRICE' => $skuInfo['sale_price'], //实际销售单价(单件)
                    'XF_AMTSOLD' => $sales_amount, //销售总金额
                    'XF_MARKDOWNAMT' => $pmt_price, //优惠金额(单件)
                    'is_abnormal' => 'false',
                    'error_msg' => '',
                    'last_modified' => time(),
                );
                
                //再次检查数据
                if(empty($nums) || empty($saveData['XF_PLU']) || empty($saveData['XF_ITEMLOTNUM']) || empty($saveData['XF_ORGUPRICE']) || empty($saveData['XF_SELUPRICE'])){
                    //设置为异常状态
                    $saveData['is_abnormal'] = 'true';
                    $saveData['error_msg'] = '再次检查数据还是异常!';
                }elseif($saveData['XF_ORGUPRICE'] <= 0){
                    //货品price销售价在OMS系统里未维护
                    $saveData['is_abnormal'] = 'true';
                    $saveData['error_msg'] = '再次检查异常：price货品价格未维护,不能为0元!';
                }
                
                //异常已修复，更新状态为默认(失败状态task队列任务不会自动生成文件)
                $date_msg = '';
                if($saveData['is_abnormal']=='false' && $val['status']=='fail'){
                    $saveData['status'] = 'none';
                    
                    //更新为当前年月日+时分秒（客户微信群里确认这样修改，财务月日期是按照美账时间决定的）
                    $saveData['XF_TXDATE'] = date('Ymd', time());
                    $saveData['XF_TXTIME'] = date('His', time());
                    
                    $date_msg = '(更新销售日期为：'. date('Y-m-d H:i:s', time()) .')';
                }
                
                //update
                $sapSaleObj->update($saveData, array('id'=>$id));
                
                //更新失败订单号为：当前年月日+时分秒
                if($saveData['is_abnormal']=='false' && $order_bn){
                    //更新为当前年月日+时分秒
                    $updateSdf = array(
                        'status' => 'none',
                        'XF_TXDATE' => date('Ymd', time()),
                        'XF_TXTIME' => date('His', time()),
                    );
                    $sapSaleObj->update($updateSdf, array('XF_ORGDOCNO'=>$order_bn, 'status'=>array('fail')));
                }
                
                //logs
                $flag_msg = ($saveData['is_abnormal'] == 'true' ? '失败：'. $saveData['error_msg'] : '成功');
                $operLogObj->write_log('sap_sales@vfapi', $id, '处理销售单异常'. $flag_msg . $date_msg);
                
                $succ_count++;
            }
        }
        
        $data = array('fail_count'=>$fail_count, 'succ_count'=>$succ_count);
        
        return $this->succ('执行成功', $data);
    }
    
    /**
     * 修复异常Sap售后单
     * 
     * @param array $params
     * @return array
     */
    public function repairAftersalesAbnormal($params)
    {
        $sapSaleObj = app::get('vfapi')->model('sap_sales');
        $aftersaleObj = app::get('vfapi')->model('sap_aftersales');
        $operLogObj = app::get('erpapi')->model('operation_log');
        
        $funcLib = kernel::single('vfapi_func');
        
        //filter
        $filter = $params['filter'];
        if(empty($filter)){
            $error_msg = '没有指定查询条件!';
            return $this->error($error_msg);
        }
        
        //page_size
        $page_size = $this->_page_size;
        
        //count
        $countNum = $aftersaleObj->count($filter);
        if(empty($countNum)){
            $error_msg = '没有可操作的SAP销售单记录!';
            return $this->error($error_msg);
        }
        
        //page
        $pageNum = ceil($countNum / $page_size);
        $fail_count = 0;
        $succ_count = 0;
        for($page=1; $page<=$pageNum; $page++)
        {
            $offset = ($page - 1) * $page_size;
            
            //list
            $aftersaleList = $aftersaleObj->getList('*', $filter, $offset, $page_size, 'id ASC');
            if(empty($aftersaleList)){
                $fail_count++;
                
                //$error_msg = '没有可以处理的SAP销售单数据';
                continue;
            }
            
            //skus
            $skuBns = $funcLib->_array_column($aftersaleList, 'sku_bn');
//            if(empty($skuBns[0])){
//                //通过product_bn货号反查找
//                $productBns = $funcLib->_array_column($aftersaleList, 'XF_PLU');
//                $skuList = $this->getImportPriceList($productBns, array('京东入仓', '京东云仓'));
//                if($skuList){
//                    $skuBns = $funcLib->_array_column($skuList, 'shop_sku_id');
//                }
//            }
            
            //渠道类型
            //@todo：目前导入的固定折扣的SKU只能属于一个渠道;
            $skuList = $this->getOmsSkuPriceList($skuBns, 'shop_sku_id');
            
            //list
            foreach ($aftersaleList as $key => $val)
            {
                $id = $val['id'];
                $order_bn = $val['XF_ORGDOCNO'];
                $sku_bn = $val['sku_bn'];
                $nums = intval($val['XF_QTYSOLD']);
                
                //check
                if($val['is_abnormal'] != 'true'){
                    $fail_count++;
                    
                    continue;
                }
                
                if(empty($sku_bn) && empty($val['XF_PLU'])){
                    $fail_count++;
                    
                    //update
                    $error_msg = 'XF_PLU货号、sku_bn店铺商品ID都为空,请检查;';
                    $aftersaleObj->update(array('error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    continue;
                }
                
                //[兼容]获取销售单渠道类型
                if(empty($val['channel_type']) || $val['channel_type']=='none'){
                    $sapSaleInfo = $sapSaleObj->dump(array('XF_ORGDOCNO'=>$order_bn), 'id,XF_ORGDOCNO,channel_type');
                    if($sapSaleInfo){
                        $val['channel_type'] = $sapSaleInfo['channel_type'];
                        
                        //update
                        $aftersaleObj->update(array('channel_type'=>$val['channel_type'], 'last_modified'=>time()), array('id'=>$id));
                        
                        //logs
                        $operLogObj->write_log('sap_aftersales@vfapi', $id, '更新为销售单上的渠道类型：'. $val['channel_type']);
                    }
                }
                
                //check
                if(!in_array($val['channel_type'], array('jd_account', 'jd_cloud', 'vip'))){
                    $fail_count++;
                    
                    //update
                    $error_msg = '渠道类型为：'. $val['channel_type'] .'无法处理;';
                    $aftersaleObj->update(array('error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    continue;
                }
                
                //检查货号、价格、数量、条形码、price单价、销售总金额是否存在
                if($val['XF_PLU'] && $val['XF_ITEMLOTNUM'] && $val['XF_QTYSOLD'] && $val['XF_ORGUPRICE'] && $val['XF_AMTSOLD'] && $val['XF_ORGUPRICE'] > 0){
                    $succ_count++; //成功更新标识
                    
                    //update
                    $error_msg = '货号、价格、数量、条形码都存在,去除异常标识;';
                    
                    //更新为当前年月日+时分秒
                    $updateSdf = array(
                        'is_abnormal' => 'false',
                        'status' => 'none',
                        'XF_TXDATE' => date('Ymd', time()),
                        'XF_TXTIME' => date('His', time()),
                        'error_msg' => $error_msg,
                        'last_modified' => time(),
                    );
                    
                    $aftersaleObj->update($updateSdf, array('id'=>$id));
                    
                    //logs
                    $operLogObj->write_log('sap_aftersales@vfapi', $id, $error_msg);
                    
                    continue;
                }
                
                //sku
                $skuInfo = $skuList[$sku_bn];
                if(empty($skuInfo)){
                    $fail_count++;
                    $error_msg = 'sku_bn：'. $sku_bn .'获取SKU价格信息失败;';
                    
                    //update
                    $aftersaleObj->update(array('error_msg'=>$error_msg, 'last_modified'=>time()), array('id'=>$id));
                    
                    continue;
                }
                
                //金额
                $sales_amount = $skuInfo['sale_price'] * $nums;
                $pmt_price = $skuInfo['price'] - $skuInfo['sale_price'];
                
                //save
                $saveData = array(
                    'XF_PLU' => $skuInfo['product_bn'], //货号
                    'XF_ITEMLOTNUM' => $skuInfo['barcode'], //条形码
                    'XF_ORGUPRICE' => $skuInfo['price'], //Price原价
                    'XF_SELUPRICE' => $skuInfo['sale_price'], //实际销售单价(单件)
                    'XF_AMTSOLD' => $sales_amount, //销售总金额
                    'XF_MARKDOWNAMT' => $pmt_price, //优惠金额(单件)
                    'is_abnormal' => 'false',
                    'error_msg' => '',
                    'last_modified' => time(),
                );
                
                //再次检查数据
                if(empty($nums) || empty($saveData['XF_PLU']) || empty($saveData['XF_ITEMLOTNUM']) || empty($saveData['XF_ORGUPRICE']) || empty($saveData['XF_SELUPRICE'])){
                    //设置为异常状态
                    $saveData['is_abnormal'] = 'true';
                    $saveData['error_msg'] = '再次检查数据还是异常;';
                }elseif($saveData['XF_ORGUPRICE'] <= 0){
                    //货品price销售价在OMS系统里未维护
                    $saveData['is_abnormal'] = 'true';
                    $saveData['error_msg'] = '再次异常：price货品价格未维护,不能为0元;';
                }
                
                //异常已修复，更新状态为默认(失败状态task队列任务不会自动生成文件)
                $date_msg = '';
                if($saveData['is_abnormal']=='false' && $val['status']=='fail'){
                    $saveData['status'] = 'none';
                    
                    //更新为当前年月日+时分秒（客户微信群里确认这样修改，财务月日期是按照美账时间决定的）
                    $saveData['XF_TXDATE'] = date('Ymd', time());
                    $saveData['XF_TXTIME'] = date('His', time());
                    
                    $date_msg = '(更新销售日期为：'. date('Y-m-d H:i:s', time()) .')';
                }
                
                //update
                $aftersaleObj->update($saveData, array('id'=>$id));
                
                //更新失败订单号为：当前年月日+时分秒
                if($saveData['is_abnormal']=='false' && $order_bn){
                    //更新为当前年月日+时分秒
                    $updateSdf = array(
                        'status' => 'none',
                        'XF_TXDATE' => date('Ymd', time()),
                        'XF_TXTIME' => date('His', time()),
                    );
                    $sapSaleObj->update($updateSdf, array('XF_ORGDOCNO'=>$order_bn, 'status'=>array('fail')));
                }
                
                //logs
                $flag_msg = ($saveData['is_abnormal'] == 'true' ? '失败：'. $saveData['error_msg'] : '成功');
                $operLogObj->write_log('sap_aftersales@vfapi', $id, '处理售后单异常'.$flag_msg . $date_msg);
                
                $succ_count++;
            }
        }
        
        $data = array('fail_count'=>$fail_count, 'succ_count'=>$succ_count);
        
        return $this->succ('执行成功', $data);
    }
    
    /**
     * 出入库明细生成txt文件并上传FTP
     * @todo：每500条记录生成一个txt文件;
     * 
     * @param array $params
     * @return boolean
     */
    public function disposeIostockFile($params)
    {
        $sapIostockObj = app::get('vfapi')->model('sap_iostock');
        $sapIostockItemObj = app::get('vfapi')->model('sap_iostock_items');
        
        $fileObj = app::get('vfapi')->model('filelist');
        
        $cronIostockLib = kernel::single('erpapi_autotask_timer_iostocklist');
        $funcLib = kernel::single('vfapi_func');
        
        //page_size文件生成记录行数
        $page_size = $cronIostockLib::$xml_page_size;
        if(empty($page_size)){
            $error_msg = '没有配置XML文件生成记录行数';
            return $this->error($error_msg);
        }
        
        //filter
        $filter = $params['filter'];
        if(empty($filter)){
            $error_msg = '没有传指定的filter条件';
            return $this->error($error_msg);
        }
        
        //count
        $countNum = $sapIostockObj->count($filter);
        if(empty($countNum)){
            $error_msg = '没有可操作的SAP出入库记录!';
            return $this->error($error_msg);
        }
        
        //货号对照表
        base_kvstore::instance('vfapi')->fetch('goodsmap.erp', $erpProductList);
        
        //出入库类型列表
        $typeList = $this->getIostckTypeList();
        
        //page
        $pageNum = ceil($countNum / $page_size);
        for($page=1; $page<=$pageNum; $page++)
        {
            //filter条件为未读状态,所以每次读取都得从0开始
            $iostockList = $sapIostockObj->getList('*', $filter, 0, $page_size);
            if(empty($iostockList)){
                //$error_msg = '没有可以处理的出入库明细';
                continue;
            }
            
            //ids
            $sapIds = $funcLib->_array_column($iostockList, 'sap_id');
            $iostock_type = $iostockList[0]['iostock_type'];
            $type_id = $iostockList[0]['type_id'];
            
            //出入库类型信息
            $typeRow = $typeList[$type_id];
            $group_id = intval($typeRow['group']);
            
            //按照出入库类型,生成不同的页码格式
            if($iostock_type == 'in_stock'){
                $file_page = 1000 + $page; //入库
            }else{
                $file_page = 5000 + $page; //出库
            }
            
            //出入库种类,用于区别文件名
            if($group_id > 0){
                $file_page = $file_page + $group_id * 100;
            }
            
            //items
            $tempItems = $sapIostockItemObj->getList('*', array('sap_id'=>$sapIds));
            if(empty($tempItems)){
                $error_msg = '没有出入库明细,请检查';
                
                //update
                $sapIostockObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('sap_id'=>$sapIds));
                
                continue;
            }
            
            //format
            $itemList = array();
            foreach ($tempItems as $itemKey => $itemVal)
            {
                $sap_id = $itemVal['sap_id'];
                $product_bn = $itemVal['product_bn'];
                
                //check过滤出入库数量为0的明细
                if(empty($itemVal['nums'])){
                    continue;
                }
                
                //过滤赠品
                if($erpProductList[$product_bn][1] == 'true'){
                    continue;
                }
                
                $itemList[$sap_id][] = $itemVal;
            }
            
            //XML公共头标签
            $doc = new DOMDocument('1.0', 'utf-8');
            $doc->formatOutput = true; //格式化输出xml格式
            
            //ns0根标签
            $xmlRoot = $doc->createElement('ns0:MT_ShipmentDetails_I0552_OMS');
            
            //ns0根标签的属性值
            $xmlns = $doc->createAttribute('xmlns:ns0');
            $xmlnsVal = $doc->createTextNode("urn:KATALYST.com:PTP:ShipmentDetails");
            $xmlns->appendChild($xmlnsVal);
            $xmlRoot->appendChild($xmlns);
            
            //Shipment标签
            $Shipment = $doc->createElement('Shipment');
            
            //first
            $masterFirstInfo = current($iostockList);
            
            //XML文件中REF_DOC_NO字段值
            $fileParams = array(
                'file_page' => $file_page, //文件页码
            );
            $masterFirstInfo['ref_doc_no'] = vfapi_filename::get_iostock_ref_doc_no($fileParams);
            
            //格式化数据(XML文件中公共头部节点)
            $sdf = $this->getFormatShipmentHead($masterFirstInfo);
            foreach ($sdf as $keyCode => $keyVal)
            {
                $itemType = $doc->createElement($keyCode);
                $itemText = $doc->createTextNode($keyVal);
                
                $itemType->appendChild($itemText);
                $Shipment->appendChild($itemType);
            }
            
            //list
            $file_line_num = 0;
            foreach ($iostockList as $marterKey => $masterInfo)
            {
                $sap_id = $masterInfo['sap_id'];
                
                //check
                if(in_array($masterInfo['status'], array('succ'))){
                    continue;
                }
                
                //items
                $iostockItems = $itemList[$sap_id];
                if(empty($iostockItems)){
                    $error_msg = '出入库明细为空,请检查';
                    
                    //update
                    $sapIostockObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('sap_id'=>$sap_id));
                    
                    continue;
                }
                
                //items
                foreach ($iostockItems as $itemKey => $itemInfo)
                {
                    //ShipmentDetails标签
                    $shipDetail = $doc->createElement('ShipmentDetails');
                    $sdf = $this->getFormatShipmentDetails($masterInfo, $itemInfo);
                    foreach ($sdf as $keyCode => $keyVal)
                    {
                        $itemType = $doc->createElement($keyCode);
                        $itemText = $doc->createTextNode($keyVal);
                        
                        $itemType->appendChild($itemText);
                        $shipDetail->appendChild($itemType);
                    }
                    
                    $Shipment->appendChild($shipDetail);
                }
                
                $file_line_num++;
            }
            
            //将标签内容赋给root标签
            $xmlRoot->appendChild($Shipment);
            
            //创建ns0根标签
            $doc->appendChild($xmlRoot);
            
            //生成XML内容
            $content = $doc->saveXML();
            
            //生成XML文件名
            $fileParams = array(
                'file_page' => $file_page, //文件页码
            );
            $file_name = vfapi_filename::get_sap_iostocklist_filename($fileParams);
            
            //file_name是完整的路径及文件名
            $local_address = $file_name;
            
            //本地临时文件夹写文件
            $handle = fopen($local_address, "a");
            fwrite($handle, $content);
            fclose($handle);
            
            //check
            if($file_line_num == 0){
                $error_msg = '没有可生成文件的行数据';
                
                //update
                $sapIostockObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('sap_id'=>$sapIds));
                
                continue;
            }
            
            //过滤掉文件公共路径
            $file_name = substr($file_name, strlen(DATA_DIR));
            
            //保存FTP文件信息
            $fileSdf = array('file'=>$file_name, 'start_time'=>time(), 'end_time'=>time(), 'type'=>'iostocklist', 'create_time'=>time());
            $file_id = $fileObj->insert($fileSdf);
            if(!$file_id){
                $error_msg = '保存文件名：'. $file_name .'信息失败';
                
                //update
                $sapIostockObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('sap_id'=>$sapIds));
                
                continue;
            }
            
            //succ
            $sapIostockObj->update(array('status'=>'succ', 'filename'=>$file_name, 'last_modified'=>time()), array('sap_id'=>$sapIds));
        }
        
        //unset
        unset($iostockList, $sapIds, $itemList);
        
        return $this->succ();
    }
    
    /**
     * 格式化Shipment节点字段
     * 
     * @param $masterInfo
     * @return array
     */
    public function getFormatShipment($masterInfo)
    {
        $mapper = array(
            'PSTNG_DATE' => 'YYYYMMDD', //生成XML文件时间：年月日
            'DOC_DATE' => 'YYYYMMDD', //OMS创建出入库明细时间：年月日
            'REF_DOC_NO' => 'iostock_bn', //SALES：销售单,SALES RETURN：退货单;
            'HEADER_TXT' => 'OMS reference', //原始单据号，例如：发货单号、退货单号
            'GM_CODE' => '04', //固定值
        );
        
        //格式化数据
        foreach ($mapper as $field => &$value)
        {
            switch ($field) {
                case 'PSTNG_DATE':
                    $value = date('Ymd', $masterInfo['create_time']);
                    break;
                case 'DOC_DATE':
                    $value = date('Ymd', $masterInfo['iostock_time']);
                    break;
                case 'REF_DOC_NO':
                    //sales OR sales return
                    //@todo：客户要求在REF_DOC_NO字段值后面加上random不重复的数字
                    if($masterInfo['iostock_type'] == 'out_stock'){
                        $value = 'SALES '. $masterInfo['sap_id'];
                    }else{
                        $value = 'SALES RETURN '. $masterInfo['sap_id'];
                    }
                    break;
                case 'HEADER_TXT':
                    $value = $masterInfo['original_bn'];
                    break;
                default:
                    //--
                    break;
            }
        }
        
        return $mapper;
    }
    
    /**
     * 格式化Shipment节点字段(XML文件中公共头部节点)
     * 
     * @param $masterInfo
     * @return array
     */
    public function getFormatShipmentHead($masterInfo)
    {
        $mapper = array(
            'PSTNG_DATE' => 'YYYYMMDD', //生成XML文件时间：年月日
            'DOC_DATE' => 'YYYYMMDD', //OMS创建出入库明细时间：年月日
            'REF_DOC_NO' => '', //以XML文件名中后16位字符
            'HEADER_TXT' => '', //sales OR sales return
            'GM_CODE' => '04', //固定值
        );
        
        //格式化数据
        foreach ($mapper as $field => &$value)
        {
            switch ($field) {
                case 'PSTNG_DATE':
                    $value = date('Ymd', $masterInfo['create_time']);
                    break;
                case 'DOC_DATE':
                    $value = date('Ymd', $masterInfo['iostock_time']);
                    break;
                case 'REF_DOC_NO':
                    //以XML文件名中后16位字符
                    $value = $masterInfo['ref_doc_no'];
                    break;
                case 'HEADER_TXT':
                    //sales OR sales return
                    //@todo：客户要求在REF_DOC_NO字段值后面加上random不重复的数字
                    if($masterInfo['iostock_type'] == 'out_stock'){
                        $value = 'SALES '. $masterInfo['sap_id'];
                    }else{
                        $value = 'SALES RETURN '. $masterInfo['sap_id'];
                    }
                    break;
                default:
                    //--
                    break;
            }
        }
        
        return $mapper;
    }
    
    /**
     * 格式化ShipmentDetails节点字段
     * 备注信息：
     * 1、销售出库时：
     *    PLANT字段：1161（LEE品牌）/1217（WRG品牌）
     *    MOVE_PLANT字段：TTPOS店铺，例如：00Z575、1439、1500、1581;
     * 2、退货入库时：
     *    PLANT字段：TTPOS店铺，例如：00Z575、1439、1500、1581;
     *    MOVE_PLANT字段：1161（LEE品牌）/1217（WRG品牌）
     * 
     * @param $masterInfo
     * @param $itemInfo
     * @return string[]
     */
    public function getFormatShipmentDetails($masterInfo, $itemInfo)
    {
        $mapper = array(
            'MATERIAL' => 'product_bn', //货号
            'PLANT' => '1161', //LEE发1161,WRG发1217
            'STGE_LOC' => '0001', //固定值
            'MOVE_TYPE' => '301', //固定值
            'ENTRY_QNT' => '1', //出入库数量
            'MOVE_MAT' => 'product_bn', //货号,与MATERIAL字段保持一致
            'MOVE_PLANT' => 'store code', //ttpos_store
            'MOVE_STLOC' => '0001', //固定值
        );
        
        //brand code
        $brand_code = '';
        if(strtolower(ERP_BRAND) == 'lee'){
            $brand_code = '1161';
        }elseif(strtolower(ERP_BRAND) == 'wrg' || strtolower(ERP_BRAND) == 'wrangler'){
            $brand_code = '1217';
        }
        
        //入库 OR 出库
        if($masterInfo['iostock_type'] == 'in_stock'){
            $plant = $masterInfo['ttpos_store'];
            $move_plant = $brand_code;
        }else{
            $plant = $brand_code;
            $move_plant = $masterInfo['ttpos_store'];
        }
        
        //格式化数据
        foreach ($mapper as $field => &$value)
        {
            switch ($field) {
                case 'MATERIAL':
                    $value = $itemInfo['product_bn'];
                    break;
                case 'PLANT':
                    $value = $plant;
                    break;
                case 'ENTRY_QNT':
                    $value = $itemInfo['nums'];
                    break;
                case 'MOVE_MAT':
                    $value = $itemInfo['product_bn'];
                    break;
                case 'MOVE_PLANT':
                    $value = $move_plant;
                    break;
                default:
                    //--
                    break;
            }
        }
        
        return $mapper;
    }
    
    /**
     * 生成Sap出入库明细记录
     * 
     * @param array $params
     * @return array
     */
    public function createSapIostock($params)
    {
        $iostockObj = app::get('vfapi')->model('iostocklist');
        $sapIostockObj = app::get('vfapi')->model('sap_iostock');
        $sapIostockItemObj = app::get('vfapi')->model('sap_iostock_items');
        
        $funcLib = kernel::single('vfapi_func');
        
        //filter
        $filter = $params['filter'];
        if(empty($filter)){
            $error_msg = '没有指定查询条件!';
            return $this->error($error_msg);
        }
        
        //page_size
        $page_size = $this->_page_size;
        
        //count
        $countNum = $iostockObj->count($filter);
        if(empty($countNum)){
            $error_msg = '没有可操作的出入库记录!';
            return $this->error($error_msg);
        }
        
        //货号对照表
        base_kvstore::instance('vfapi')->fetch('goodsmap.erp', $erpProductList);
        
        //page
        $pageNum = ceil($countNum / $page_size);
        $tempList = $iostockBns = $iostockList = $existData = array();
        for($page=1; $page<=$pageNum; $page++)
        {
            //getList
            $tempList = $iostockObj->getList('id,iostock_bn,type_id,bill_type,branch_bn,product_bn,nums', $filter, 0, $page_size, 'id ASC');
            if(empty($tempList)){
                //没有查询到销售单数据
                continue;
            }
            
            $iostockBns = array();
            foreach ($tempList as $tempKey => $tempVal)
            {
                $id = $tempVal['id'];
                $type_id = $tempVal['type_id'];
                $bill_type = $tempVal['bill_type'];
                $iostock_bn = $tempVal['iostock_bn'];
                $branch_bn = $tempVal['branch_bn'];
                $product_bn = trim($tempVal['product_bn']);
                
                //check
                if(in_array($tempVal['status'], array('succ', 'needless'))){
                    continue;
                }
                
                //检查是否需要生成SAP单据
                $isDispose = $this->checkTypeidIsDispose($type_id, $bill_type, $branch_bn);
                if(!$isDispose){
                    //update
                    $iostockObj->update(array('status'=>'needless', 'last_modified'=>time()), array('id'=>$id));
                    continue;
                }
                
                //check出入库数量为0
                if(empty($tempVal['nums'])){
                    //update
                    $iostockObj->update(array('status'=>'needless', 'last_modified'=>time(), 'error_msg'=>'出入库数量为0'), array('id'=>$id));
                    continue;
                }
                
                //过滤赠品
                if($erpProductList[$product_bn][1] == 'true'){
                    //update
                    $iostockObj->update(array('status'=>'needless', 'last_modified'=>time(), 'error_msg'=>'出入库为赠品'), array('id'=>$id));
                    continue;
                }
                
                //iostock_bn
                $iostockBns[$iostock_bn] = $iostock_bn;
            }
            
            //获取组织好的出入库明细列表
            $iostockList = $this->_formatIostockList($iostockBns);
            if(empty($iostockList)){
                continue;
            }
            
            //已经存在的数据
            $existData = $sapIostockObj->getList('sap_id,iostock_bn', array('iostock_bn'=>$iostockBns));
            if($existData){
                $existData = $funcLib->_array_column($existData, null, 'iostock_bn');
            }
            
            //list
            $iostockBns = array();
            foreach ($iostockList as $iostockKey => $iostockInfo)
            {
                $iostock_bn = $iostockInfo['iostock_bn'];
                
                //check
                if($existData[$iostock_bn]){
                    $error_msg = '已经生成过Sap出入库记录';
                    
                    //update
                    $iostockObj->update(array('status'=>'fail', 'is_abnormal'=>'true', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('iostock_bn'=>$iostock_bn));
                    
                    continue;
                }
                
                //items
                $iostockItems = $iostockInfo['items'];
                if(empty($iostockItems)){
                    $error_msg = '格式化出入库明细记录为空';
                    
                    //update
                    $iostockObj->update(array('status'=>'fail', 'is_abnormal'=>'true', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('iostock_bn'=>$iostock_bn));
                    
                    continue;
                }
                unset($iostockInfo['items']);
                
                //检查ttpos_store编码
                if(empty($iostockInfo['ttpos_store'])){
                    $error_msg = 'TTPOS店铺号为空,请检查!';
                    
                    //update
                    $iostockObj->update(array('status'=>'fail', 'is_abnormal'=>'true', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('iostock_bn'=>$iostock_bn));
                    
                    continue;
                }
                
                $iostockInfo['create_time'] = time();
                $iostockInfo['last_modified'] = time();
                
                //save master
                $sap_id = $sapIostockObj->insert($iostockInfo);
                if(!$sap_id){
                    $error_msg = '创建Sap出入库记录失败';
                    
                    //update
                    $iostockObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('iostock_bn'=>$iostock_bn));
                    
                    continue;
                }
                
                //save items
                $isSaveItems = true;
                foreach ($iostockItems as $itemKey => $itemVal)
                {
                    $itemVal['sap_id'] = $sap_id;
                    $product_bn = $itemVal['product_bn'];
                    
                    //check出入库数量为0,则跳过
                    if(empty($itemVal['nums'])){
                        continue;
                    }
                    
                    //过滤赠品
                    if($erpProductList[$product_bn][1] == 'true'){
                        continue;
                    }
                    
                    //insert
                    $insert_id = $sapIostockItemObj->insert($itemVal);
                    if(!$insert_id){
                        $error_msg = '创建item出入库明细失败';
                        
                        //update
                        $iostockObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('iostock_bn'=>$iostock_bn));
                        
                        $isSaveItems = false;
                    }
                }
                
                //fail
                if(!$isSaveItems){
                    $error_msg = '保存item出入库明细失败';
                    
                    //update
                    $iostockObj->update(array('status'=>'fail', 'error_msg'=>$error_msg, 'last_modified'=>time()), array('iostock_bn'=>$iostock_bn));
                    
                    continue;
                }
                
                //merge
                $iostockBns[$iostock_bn] = $iostock_bn;
            }
            
            //update
            $iostockObj->update(array('status'=>'succ', 'last_modified'=>time()), array('iostock_bn'=>$iostockBns));
        }
        
        //unset
        unset($tempList, $iostockBns, $iostockList, $existData);
        
        return $this->succ();
    }
    
    /**
     * 按照出入库单号组织数据
     * 
     * @param $iostockBns
     * @return void
     */
    public function _formatIostockList($iostockBns)
    {
        $iostockObj = app::get('vfapi')->model('iostocklist');
        
        $funcLib = kernel::single('vfapi_func');
        
        //getList
        $dataList = $iostockObj->getList('*', array('iostock_bn'=>$iostockBns));
        if(empty($dataList)){
            return array();
        }
        
        //货号对照表
        base_kvstore::instance('vfapi')->fetch('goodsmap.erp', $erpProductList);
        
        //以shop店铺编码为下标,获取TTPOS列表
        $shopTtposList = $funcLib->getShopTtposList();
        
        //format
        $iostockList = array();
        foreach ($dataList as $key => $val)
        {
            $iostock_id = $val['iostock_id'];
            $iostock_bn = $val['iostock_bn'];
            $product_bn = $val['product_bn'];
            
            //OMS外部仓库编码(与OMS店铺编码一致)
            $shop_bn = $val['extrabranch_bn'];
            
            //check
            if(in_array($val['status'], array('needless', 'succ'))){
                continue;
            }
            
            //check出入库数量为0,则跳过
            if(empty($val['nums'])){
                continue;
            }
            
            //过滤赠品
            if($erpProductList[$product_bn][1] == 'true'){
                continue;
            }
            
            //item Info
            $itemInfo = array(
                'iostock_id' => $iostock_id,
                'iostock_bn' => $iostock_bn,
                'product_bn' => $product_bn,
                'barcode' => $val['barcode'],
                'product_name' => $val['product_name'],
                'nums' => $val['nums'],
            );
            
            //master info
            if(empty($iostockList[$iostock_bn])){
                //sales OR sales return
                if($val['iostock_type'] == 'out_stock'){
                    $sap_type = 'SALES';
                }else{
                    $sap_type = 'SALES RETURN';
                }
                
                //ttpos_store
                $branch_ttpos_code = $shopTtposList[$shop_bn]['branch_ttpos_code'];
                if(empty($branch_ttpos_code)){
                    $branch_ttpos_code = $shopTtposList[$shop_bn]['ttpos_store'];
                }
                
                //master
                $iostockList[$iostock_bn] = array(
                    'iostock_bn' => $iostock_bn,
                    'iostock_type' => $val['iostock_type'],
                    'stock_type' => ($val['stock_type'] ? $val['stock_type'] : 'normal'), //库存类型(良品 or 不良品)
                    'type_id' => $val['type_id'],
                    'bill_type' => $val['bill_type'],
                    'sap_type' => $sap_type,
                    'branch_bn' => $val['branch_bn'],
                    'extrabranch_bn' => $val['extrabranch_bn'],
                    'original_bn' => $val['original_bn'],
                    'ttpos_store' => $branch_ttpos_code,
                    'iostock_time' => $val['iostock_time'],
                );
            }
            
            //items
            $iostockList[$iostock_bn]['items'][$iostock_id] = $itemInfo;
        }
        
        //unset
        unset($iostockBns, $dataList);
        
        return $iostockList;
    }
    
    /**
     * 检测出入库类型,是否需要处理
     * 
     * @param $type_id
     * @param $bill_type
     * @param $branch_bn
     * @return true
     */
    public function checkTypeidIsDispose($type_id, $bill_type='', $branch_bn='')
    {
        //出入库类型列表
        $typeList = $this->getIostckTypeList();
        
        //获取所有出入库类型ID
        $typeIds = array_keys($typeList);
        if(empty($typeIds)){
            return true;
        }
        
        //检查出入库类型是否需要生成SAP单据
        if(!in_array($type_id, $typeIds)){
            return false;
        }
        
        //出入库类型信息
        $typeRow = $typeList[$type_id];
        
        //检查相应的场景
        if($typeRow['bill_types']){
            //检查bill_type出入库业务类型,是否需要生成SAP单据
            if(!in_array($bill_type, $typeRow['bill_types'])){
                return false;
            }
        }elseif($typeRow['not_branch_bns']){
            //指定仓库编码：无需生成SAP单据
            if(in_array($branch_bn, $typeRow['not_branch_bns'])){
                return false;
            }
        }
        
        return true;
    }
    
}