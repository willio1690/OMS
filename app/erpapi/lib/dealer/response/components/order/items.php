<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 订单商品明细信息
 *
 * @author wangbiao@shopex.cn
 * @version 2024.04.11
 */
class erpapi_dealer_response_components_order_items extends erpapi_dealer_response_components_order_abstract
{
    private $_obj_alias = array(
        'goods'       => '商品',
        'pkg'         => '捆绑商品',
        'gift'        => '赠品',
        'giftpackage' => '礼包',
        'lkb'         => '福袋',
        'pko'         => '多选一',
    );
    
    /**
     * 添加订单商品明细
     *
     * @return void
     **/

    public function convert()
    {
        $jxOrderLib = kernel::single('dealer_platform_orders');
        $deMaterialLib = kernel::single('dealer_material');
        
        //shop_id
        $shop_id = $this->_platform->__channelObj->channel['shop_id'];
        
        //经销商ID、组织架构ID
        $bs_id = isset($this->_platform->__channelObj->channel['bs_id']) ? $this->_platform->__channelObj->channel['bs_id'] : 0;
        $cos_id = isset($this->_platform->__channelObj->channel['cos_id']) ? $this->_platform->__channelObj->channel['cos_id'] : 0;
        
        //order_objects
        $is_fail_order = false;
        $presaleNum = 0;
        $productList = array();
        foreach ($this->_platform->_ordersdf['order_objects'] as $object)
        {
            $order_oid = $object['oid'];
            
            $obj_quantity = $object['quantity'] ? $object['quantity'] : 1;
            $obj_amount = $object['amount'] ? $object['amount'] : bcmul($obj_quantity, $object['price'], 3);
            
            $obj_sale_price = (isset($object['sale_price']) && is_numeric($object['sale_price']) && -1 != bccomp($object['sale_price'], 0, 3)) ? $object['sale_price'] : bcsub($obj_amount, $object['pmt_price'], 3);
            
            //检查货品是否存在销售物料中
            $salesMInfo = $deMaterialLib->getSaleMaterialInfo($shop_id, $object['bn']);
            if (!$salesMInfo) {
                $is_fail_order = true;
            }
            
            //items
            $order_items = array();
            $obj_type = 'goods';
            if ($salesMInfo) {
                $smIds = array($salesMInfo['sm_id']);
                $bmList = $deMaterialLib->getBasicMatBySmIds($smIds);
                if(empty($bmList)) {
                    $is_fail_order = true;
                }
                
                if ($bmList) {
                    switch ($salesMInfo['sales_material_type']) {
                        case "2":
                            $obj_type = 'pkg';
                            
                            //根据促销总价格计算每个物料的贡献金额值
                            $deMaterialLib->calProSaleMatPriceByRate($obj_sale_price, $bmList);
                            
                            //根据优惠价格计算每个物料的贡献金额值
                            $pmt_price_rate = $deMaterialLib->getPmtPriceByRate($object['pmt_price'], $bmList);
                            break;
                        case "3":
                            $obj_type = 'gift';
                            break;
                    }
                    
                    foreach ($bmList as $k => $basicMInfo)
                    {
                        $product_id = ($basicMInfo['bm_id'] ? $basicMInfo['bm_id'] : 0);
                        $material_bn = $basicMInfo['material_bn'];
                        $item_nums = $basicMInfo['number'] * $obj_quantity;
                        
                        if ($obj_type == 'pkg') {
                            $item_type = 'pkg';
                            $shop_product_id = 0;
                            
                            $cost = $basicMInfo['cost'];
                            $pmt_price = $pmt_price_rate[$material_bn] ? ($pmt_price_rate[$material_bn]['rate_price'] > 0 ? $pmt_price_rate[$material_bn]['rate_price'] : 0) : 0.00;
                            $sale_price        = $basicMInfo['rate_price'] > 0 ? $basicMInfo['rate_price'] : 0;
                            $amount            = bcadd((float)$pmt_price, (float)$sale_price, 2);
                            $price             = bcdiv($amount, $basicMInfo['number'] * $obj_quantity, 2);
                            
                            $weight            = $basicMInfo['weight'];
                            $divide_order_fee  = 0;
                            $part_mjz_discount = 0;
                        }else {
                            $item_type = $obj_type == 'goods' ? 'product' : $obj_type;
                            $shop_product_id = $object['shop_product_id'] ? $object['shop_product_id'] : 0;
                            
                            $cost              = (float) $object['cost'] ? $object['cost'] : $basicMInfo['cost'];
                            $price             = (float) $object['price'];
                            $pmt_price         = (float) $object['pmt_price'];
                            $sale_price        = (isset($object['sale_price']) && is_numeric($object['sale_price']) && -1 != bccomp($object['sale_price'], 0, 3)) ? $object['sale_price'] : bcsub($obj_amount, (float) $object['pmt_price'], 3);
                            $amount            = $obj_amount;
                            
                            $weight            = (float) $object['weight'] ? $object['weight'] : ($basicMInfo['weight'] ? $basicMInfo['weight'] : 0.00);
                            $divide_order_fee  = $object['divide_order_fee'];
                            $part_mjz_discount = $object['part_mjz_discount'];
                        }
                        
                        $order_items[] = array(
                            'shop_goods_id'     => $object['shop_goods_id'] ? $object['shop_goods_id'] : 0,
                            'product_id'        => $product_id,
                            'shop_product_id'   => $shop_product_id,
                            'bn'                => $material_bn,
                            'name'              => $basicMInfo['material_name'],
                            'cost'              => $cost ? $cost : 0.00,
                            'price'             => $price ? $price : 0.00,
                            'pmt_price'         => $pmt_price,
                            'sale_price'        => $sale_price ? $sale_price : 0.00,
                            'amount'            => $amount ? $amount : 0.00,
                            'weight'            => $weight ? $weight : 0.00,
                            'nums'              => $item_nums, //购买数量
                            'addon'             => '',
                            'item_type'         => $item_type,
                            'is_delete'         => ($object['status'] == 'close') ? 'true' : 'false', //经销订单明细删除状态
                            'divide_order_fee'  => $divide_order_fee,
                            'part_mjz_discount' => $part_mjz_discount,
                            'product_attr'      => $object['product_attr'] ? $object['product_attr'] : "",
                        );
                        
                        //products
                        $productList[$product_id] = array(
                            'product_id' => $product_id,
                            'product_bn' => $material_bn,
                            'betc_id' => 0, //贸易公司ID
                            'is_shopyjdf_type' => '0', //发货方式
                        );
                    }
                }
            }
            
            //预售状态
            $presaleStatus = '0';
            if((is_numeric($object['estimate_con_time']) && $object['estimate_con_time'] > 0) 
                || $this->_platform->_ordersdf['order_type'] == 'presale') {
                $presaleNum += 1;
                $presaleStatus = '1';
            }
            
            $this->_platform->_newOrder['order_objects'][] = array(
                'plat_oid'          => $order_oid,
                'obj_type'          => $obj_type,
                'obj_alias'         => $object['obj_alias'] ? $object['obj_alias'] : $this->_obj_alias[$obj_type],
                'shop_goods_id'     => $object['shop_goods_id'] ? $object['shop_goods_id'] : 0,
                'goods_id'          => $salesMInfo['sm_id'] ? $salesMInfo['sm_id'] : 0,
                'bn'                => $object['bn'] ? $object['bn'] : null,
                'name'              => $object['name'],
                'price'             => $object['price'] ? (float) $object['price'] : bcdiv($obj_amount, $obj_quantity, 3),
                'amount'            => $obj_amount,
                'quantity'          => $obj_quantity, //购买数量
                'weight'            => (float) $object['weight'],
                'score'             => (float) $object['score'],
                'pmt_price'         => (float) $object['pmt_price'],
                'sale_price'        => $obj_sale_price,
                'is_oversold'       => ($object['is_oversold'] == true) ? 1 : 0,
                'is_delete'         => ($object['status'] == 'close') ? 'true' : 'false', //经销订单明细删除状态
                'original_str'      => $object['original_str'],
                'product_attr'      => $object['product_attr'],
                'promotion_id'      => $object['promotion_id'],
                'divide_order_fee'  => $object['divide_order_fee'],
                'part_mjz_discount' => $object['part_mjz_discount'],
                'ship_status'       => (int) $object['ship_status'],
                'presale_status'    => $presaleStatus,
                'author_id'         => $object['authod_id'], //主播ID
                'author_name'       => $object['author_name'], //主播姓名
                'warehouse_ids'     => $object['warehouse_ids'], //抖音平台仓库编码
                'out_warehouse_ids' => $object['out_warehouse_ids'], //指定区域仓编码
                'is_sh_ship'        => $object['is_sh_ship'] == 'true' ? 'true' : 'false',
                's_type'            => $object['is_daixiao'] == 'true' ? 'dx' : 'zx',
                'order_items'       => $order_items,
            );
            
            unset($order_items);
        }
        
        //是否预售订单
        if($presaleNum > 0) {
            if($presaleNum == count($this->_platform->_ordersdf['order_objects'])) {
                $this->_platform->_newOrder['presale_status'] = '1';
            } else {
                $this->_platform->_newOrder['presale_status'] = '2';
            }
        }
        
        //是否失败订单(items层基础物料不存在)
        if ($is_fail_order) {
            $this->_platform->_newOrder['is_fail']     = 'true';
            $this->_platform->_newOrder['edit_status'] = 'true';
            $this->_platform->_newOrder['archive']     = '1';
        }
        
        //没有经销商ID、组织架构ID、基础物料列表,直接返回
        if(empty($cos_id) || empty($bs_id) || empty($productList)){
            return true;
        }
        
        //通过基础物料获取发货方式：自发、代发，所属贸易公司ID；
        //$businessInfo = array('shop_id'=>$shop_id, 'cos_id'=>$cos_id, 'bs_id'=>$bs_id);
        $businessInfo = array('shop_id'=>$shop_id);
        $productList = $jxOrderLib->getProductDespatchType($productList, $businessInfo);
        
        //format
        $orderYjdfTypes = array();
        foreach ($this->_platform->_newOrder['order_objects'] as $objKey => $objVal)
        {
            //check
            if(empty($objVal['order_items'])){
                continue;
            }
            
            //items
            $shopyjdf_types = array();
            foreach ($objVal['order_items'] as $itemKey => $itemVal)
            {
                $product_id = $itemVal['product_id'];
                $betc_id = 0; //贸易公司ID
                $is_shopyjdf_type = '1'; //发货方式(默认自发)
                
                //发货方式
                if(isset($productList[$product_id])){
                    $betc_id = intval($productList[$product_id]['betc_id']);
                    $is_shopyjdf_type = $productList[$product_id]['is_shopyjdf_type'];
                }
                
                $itemVal['betc_id'] = $betc_id;
                $itemVal['is_shopyjdf_type'] = $is_shopyjdf_type;
                
                //[object层]汇总发货方式
                $shopyjdf_types[$is_shopyjdf_type] = $is_shopyjdf_type;
                
                //[订单层]汇总发货方式
                $orderYjdfTypes[$is_shopyjdf_type] = $is_shopyjdf_type;
                
                //merge
                $objVal['order_items'][$itemKey] = $itemVal;
            }
            
            //object层发货方式
            if(count($shopyjdf_types) > 1){
                $objVal['is_shopyjdf_type'] = '3'; //部分代发货，即有自发货，也有代发货；
            }else{
                $objVal['is_shopyjdf_type'] = current($shopyjdf_types);
            }
            
            //转换状态
            if($objVal['is_shopyjdf_type'] == '' || $objVal['is_shopyjdf_type'] == '0'){
                $objVal['is_shopyjdf_step'] = '0';
            }else{
                $objVal['is_shopyjdf_step'] = '2';
            }
            
            //merge
            $this->_platform->_newOrder['order_objects'][$objKey] = $objVal;
        }
        
        //订单转换状态
        $dispose_status = 'fail';
        if(count($orderYjdfTypes) > 1){
            $dispose_status = 'part_daifa'; //部分代发货
        }else{
            $is_shopyjdf_type = current($orderYjdfTypes);
            if($is_shopyjdf_type == '2'){
                $dispose_status = 'all_daifa'; //全部代发货
            }elseif($is_shopyjdf_type == '1'){
                $dispose_status = 'all_zifa'; //全部自发货
            }
        }
        $this->_platform->_newOrder['dispose_status'] =  $dispose_status;
        
        return true;
    }
    
    /**
     * 更新订单商品明细
     *
     * @return void
     **/
    public function update()
    {
        $deMaterialLib = kernel::single('dealer_material');
        $basicMStockLib = kernel::single('material_basic_material_stock');
        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        
        //库存预占类型
        $freeze_obj_type = material_basic_material_stock_freeze::__ORDER;
        $bmsq_id = material_basic_material_stock_freeze::__SHARE_STORE;
        $bill_type = material_basic_material_stock_freeze::__DEALER_ORDER;
        
        //shop_id
        $shop_id = $this->_platform->__channelObj->channel['shop_id'];
        
        //接收平台的订单
        $ordersdf = $this->_platform->_ordersdf;
        
        //后期修改
        if ($this->_platform->_tgOrder['ship_status'] == '0') {
            //格式化ERP经销订单明细
            $tgOrder_object = array();
            foreach ((array) $this->_platform->_tgOrder['order_objects'] as $object)
            {
                //ERP赠品
                if($object['obj_type'] == 'gift' && $object['shop_goods_id'] == '-1'){
                    continue;
                }
                
                //key
                $objkey = $this->_get_obj_key($object);
                
                $tgOrder_object[$objkey] = $object;
                
                //items
                $order_items = array();
                foreach ((array) $object['order_items'] as $item)
                {
                    //key
                    $itemkey = $this->_get_item_key($item);
                    
                    $order_items[$itemkey] = $item;
                }
                
                $tgOrder_object[$objkey]['order_items'] = $order_items;
            }
            
            //组织平台推送的订单明细
            $sky_ordersdf_is_fail_order = false;
            
            //格式化平台推送的订单明细
            $ordersdf_object = array();
            $productList = array();
            foreach ((array) $ordersdf['order_objects'] as $object)
            {
                $order_oid = $object['oid'];
                $obj_quantity = $object['quantity'];
                
                //amount
                $obj_amount = $object['amount'] ? $object['amount'] : bcmul($obj_quantity, $object['price'], 3);
                
                //sale_price
                if(isset($object['sale_price']) && is_numeric($object['sale_price']) && -1 != bccomp($object['sale_price'], 0, 3)){
                    $obj_sale_price = $object['sale_price'];
                }else{
                    $obj_sale_price = bcsub($obj_amount, $object['pmt_price'], 3);
                }
                
                //检查货品是否存在销售物料中
                $salesMInfo = $deMaterialLib->getSaleMaterialInfo($shop_id, $object['bn']);
                if (!$salesMInfo) {
                    $sky_ordersdf_is_fail_order = true;
                }
                
                $order_items = array();
                $obj_type = 'goods';
                if ($salesMInfo) {
                    $smIds = array($salesMInfo['sm_id']);
                    $bmList = $deMaterialLib->getBasicMatBySmIds($smIds);
                    if (empty($bmList)) {
                        $sky_ordersdf_is_fail_order = true;
                    }
                    
                    if ($bmList) {
                        switch ($salesMInfo['sales_material_type']) {
                            case "2":
                                $obj_type = 'pkg';
                                
                                //根据促销总价格计算每个物料的贡献金额值
                                $deMaterialLib->calProSaleMatPriceByRate($obj_sale_price, $bmList);
                                
                                //根据优惠价格计算每个物料的贡献金额值
                                $pmt_price_rate = $deMaterialLib->getPmtPriceByRate($object['pmt_price'], $bmList);
                                break;
                            case "3":
                                $obj_type = 'gift';
                                break;
                        }
                        
                        //组织item数据
                        foreach ($bmList as $k => $basicMInfo)
                        {
                            $product_id = $basicMInfo['bm_id'] ? $basicMInfo['bm_id'] : 0;
                            $material_bn = $basicMInfo['material_bn'];
                            $item_nums = $basicMInfo['number'] * $obj_quantity;
                            
                            //type
                            if ($obj_type == 'pkg') {
                                $item_type = 'pkg';
                                
                                $price = bcdiv($obj_amount, $basicMInfo['number'] * $obj_quantity, 2);
                                $cost = $basicMInfo['cost'];
                                $pmt_price = $pmt_price_rate[$material_bn] ? ($pmt_price_rate[$material_bn]['rate_price'] > 0 ? $pmt_price_rate[$material_bn]['rate_price'] : 0) : 0.00;
                                $sale_price = $basicMInfo['rate_price'] > 0 ? $basicMInfo['rate_price'] : 0;
                                $amount = bcadd((float)$pmt_price, (float)$sale_price, 2);
                                
                                $weight = $basicMInfo['weight'];
                                $shop_product_id   = 0;
                                $divide_order_fee  = 0;
                                $part_mjz_discount = 0;
                            }else{
                                $item_type = ($obj_type == 'goods' ? 'product' : $obj_type);
                                
                                $cost              = (float) $object['cost'] ? $object['cost'] : $basicMInfo['cost'];
                                $price             = (float) $object['price'];
                                $pmt_price         = (float) $object['pmt_price'];
                                $sale_price        = $obj_sale_price;
                                $amount            = $obj_amount;
                                
                                $weight = (float) $object['weight'] ? $object['weight'] : ($basicMInfo['weight'] ? $basicMInfo['weight'] : 0.00);
                                $shop_product_id   = $object['shop_product_id'];
                                $divide_order_fee  = $object['divide_order_fee'];
                                $part_mjz_discount = $object['part_mjz_discount'];
                            }
                            
                            //itemInfo
                            $itemtmp = array(
                                'plat_order_id'     => $this->_platform->_tgOrder['plat_order_id'],
                                'shop_goods_id'     => $object['shop_goods_id'] ? $object['shop_goods_id'] : 0,
                                'product_id'        => $product_id,
                                'shop_product_id'   => $shop_product_id,
                                'bn'                => $material_bn,
                                'name'              => $basicMInfo['material_name'],
                                'cost'              => $cost,
                                'price'             => $price,
                                'pmt_price'         => $pmt_price,
                                'sale_price'        => $sale_price,
                                'amount'            => $amount,
                                'weight'            => $weight,
                                'nums'              => $item_nums, //购买数量
                                'addon'             => '',
                                'item_type'         => $item_type,
                                'is_delete'         => ($object['status'] == 'close') ? 'true' : 'false', //经销订单明细删除状态
                                'divide_order_fee'  => $divide_order_fee,
                                'part_mjz_discount' => $part_mjz_discount,
                            );
                            
                            //key
                            $itemkey = $this->_get_item_key($itemtmp);
                            
                            //items
                            $order_items[$itemkey] = $itemtmp;
                            
                            //products
                            $productList[$product_id] = array(
                                'product_id' => $product_id,
                                'product_bn' => $material_bn,
                                'betc_id' => 0, //贸易公司ID
                                'is_shopyjdf_type' => '0', //发货方式
                            );
                        }
                    }
                }
                
                //object
                $objecttmp = array(
                    'plat_order_id'     => $this->_platform->_tgOrder['plat_order_id'],
                    'plat_oid'          => $order_oid,
                    'obj_type'          => $obj_type,
                    'obj_alias'         => $object['obj_alias'] ? $object['obj_alias'] : $this->_obj_alias[$obj_type],
                    'shop_goods_id'     => $object['shop_goods_id'] ? $object['shop_goods_id'] : 0,
                    'goods_id'          => $salesMInfo['sm_id'] ? $salesMInfo['sm_id'] : 0,
                    'bn'                => $object['bn'] ? $object['bn'] : null,
                    'name'              => $object['name'],
                    'quantity'          => $obj_quantity, //购买数量
                    'price'             => $object['price'] ? (float) $object['price'] : bcdiv($obj_amount, $obj_quantity, 3),
                    'amount'            => $obj_amount,
                    'weight'            => (float) $object['weight'],
                    'score'             => (float) $object['score'],
                    'pmt_price'         => (float) $object['pmt_price'],
                    'sale_price'        => (float) $obj_sale_price,
                    'is_oversold'       => ($object['is_oversold'] == true) ? 1 : 0,
                    'is_delete'         => ($object['status'] == 'close') ? 'true' : 'false', //经销订单明细删除状态
                    'divide_order_fee'  => $object['divide_order_fee'],
                    'part_mjz_discount' => $object['part_mjz_discount'],
                    'order_items'       => $order_items,
                );
                
                unset($order_items);
                
                //key
                $objkey = $this->_get_obj_key($objecttmp);
                
                //merge
                $ordersdf_object[$objkey] = $objecttmp;
            }
            
            //判断不存在的items
            foreach ($tgOrder_object as $objkey => $object)
            {
                //check
                if(empty($object['order_items'])){
                    continue;
                }
                
                //items
                foreach ($object['order_items'] as $itemkey => $item)
                {
                    // 如果已经被删除，则跳过
                    if ($item['is_delete'] == 'true') {
                        continue;
                    }
                    
                    // ITEM被删除
                    if (!$ordersdf_object[$objkey]['order_items'][$itemkey]) {
                        $this->_platform->_newOrder['order_objects'][$objkey]['plat_obj_id'] = $object['plat_obj_id'];
                        $this->_platform->_newOrder['order_objects'][$objkey]['is_delete'] = 'true';
                        $this->_platform->_newOrder['order_objects'][$objkey]['is_delete'] = 'true';
                        $this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey] = array(
                            'plat_item_id' => $item['plat_item_id'],
                            'is_delete' => 'true',
                        );
                        
                        //扣库存
                        if ($item['product_id']) {
                            $basicMStockLib->unfreeze($item['product_id'], $item['nums']);
                            
                            //释放基础物料库存冻结流水
                            $basicMStockFreezeLib->unfreeze($item['product_id'], $freeze_obj_type, $bill_type, $this->_platform->_tgOrder['plat_order_id'], '', $bmsq_id, $item['nums']);
                        }
                    }
                }
            }
            
            // 字段比较
            foreach ($ordersdf_object as $objkey => $object)
            {
                $obj_id = $tgOrder_object[$objkey]['plat_obj_id'];
                
                //items
                $order_items = $object['order_items'];
                unset($object['order_items']);
                
                //过滤空值
                $object = array_filter($object, array($this, 'filter_null'));
                
                //删除不用比较的字段
                unset($object['weight'], $object['score'], $object['is_oversold'], $object['name'], $object['obj_alias']);
                unset($tgOrder_object[$objkey]['weight'], $tgOrder_object[$objkey]['score'], $tgOrder_object[$objkey]['is_oversold'], $tgOrder_object[$objkey]['name']);
                unset($tgOrder_object[$objkey]['obj_alias']);
                
                //object数据比较
                $diff_obj = array_udiff_assoc((array) $object, (array) $tgOrder_object[$objkey], array($this, 'comp_array_value'));
                if ($diff_obj) {
                    $diff_obj['plat_obj_id'] = $obj_id;
                    
                    $this->_platform->_newOrder['order_objects'][$objkey] = array_merge((array) $this->_platform->_newOrder['order_objects'][$objkey], (array) $diff_obj);
                }
                
                //items
                foreach ($order_items as $itemkey => $item)
                {
                    //过滤空值
                    $item = array_filter($item, array($this, 'filter_null'));
                    
                    //porth_field
                    if(isset($item['porth_field'])) {
                        unset($item['porth_field']);
                    }
                    
                    //删除不用比较的字段
                    unset($item['shop_product_id'], $item['name'], $item['weight'], $item['addon'], $item['product_attr'], $item['cost']);
                    unset($tgOrder_object[$objkey]['order_items'][$itemkey]['shop_product_id'], $tgOrder_object[$objkey]['order_items'][$itemkey]['name']);
                    unset($tgOrder_object[$objkey]['order_items'][$itemkey]['weight'], $tgOrder_object[$objkey]['order_items'][$itemkey]['addon']);
                    unset($tgOrder_object[$objkey]['order_items'][$itemkey]['product_attr'], $tgOrder_object[$objkey]['order_items'][$itemkey]['cost']);
                    
                    //ITEM比较
                    $item_id = $tgOrder_object[$objkey]['order_items'][$itemkey]['plat_item_id'];
                    $diff_item = array_udiff_assoc((array) $item, (array) $tgOrder_object[$objkey]['order_items'][$itemkey], array($this, 'comp_array_value'));
                    if ($diff_item) {
                        $newItemInfo = $this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey];
                        $omsItemInfo = $tgOrder_object[$objkey]['order_items'][$itemkey];
                        
                        $plat_order_id = $this->_platform->_tgOrder['plat_order_id'];
                        
                        $diff_item['plat_item_id'] = $item_id;
                        
                        //merge
                        $this->_platform->_newOrder['order_objects'][$objkey]['order_items'][$itemkey] = array_merge((array)$newItemInfo, (array)$diff_item);
                        
                        //diff
                        if ($diff_item['is_delete'] == 'false' && $item['product_id']) {
                            //freeze
                            $basicMStockLib->freeze($item['product_id'], $item['nums']);
                            
                            //增加预占库存流水
                            $freezeData = [];
                            $freezeData['bm_id'] = $item['product_id'];
                            $freezeData['obj_type'] = $freeze_obj_type;
                            $freezeData['bill_type'] = $bill_type;
                            $freezeData['obj_id'] = $plat_order_id;
                            $freezeData['shop_id'] = $this->_platform->_tgOrder['shop_id'];
                            $freezeData['branch_id'] = 0;
                            $freezeData['bmsq_id'] = $bmsq_id;
                            $freezeData['num'] = $item['nums'];
                            $freezeData['obj_bn'] = $this->_platform->_tgOrder['order_bn'];
                            
                            $basicMStockFreezeLib->freeze($freezeData);
                        } elseif ($diff_item['is_delete'] == 'true' && $item['product_id']) {
                            //unfreeze
                            $basicMStockLib->unfreeze($item['product_id'], $omsItemInfo['nums']);
                            
                            //释放基础物料库存冻结流水
                            $basicMStockFreezeLib->unfreeze($item['product_id'], $freeze_obj_type, $bill_type, $plat_order_id, '', $bmsq_id, $omsItemInfo['nums']);
                        } elseif (isset($diff_item['nums']) && $item['product_id']) {
                            // 如果库存发生变化，
                            $diff_quantity = bcsub($diff_item['nums'], $omsItemInfo['nums']);
                            if ($diff_quantity > 0) {
                                //freeze
                                $basicMStockLib->freeze($item['product_id'], abs($diff_quantity));
                                
                                //增加预占库存流水
                                $freezeData = [];
                                $freezeData['bm_id'] = $item['product_id'];
                                $freezeData['obj_type'] = $freeze_obj_type;
                                $freezeData['bill_type'] = $bill_type;
                                $freezeData['obj_id'] = $plat_order_id;
                                $freezeData['shop_id'] = $this->_platform->_tgOrder['shop_id'];
                                $freezeData['branch_id'] = 0;
                                $freezeData['bmsq_id'] = $bmsq_id;
                                $freezeData['num'] = abs($diff_quantity);
                                $freezeData['obj_bn'] = $this->_platform->_tgOrder['order_bn'];
                                
                                $basicMStockFreezeLib->freeze($freezeData);
                            } elseif ($diff_quantity < 0) {
                                //unfreeze
                                $basicMStockLib->unfreeze($item['product_id'], abs($diff_quantity));
                                
                                //释放基础物料库存冻结流水
                                $basicMStockFreezeLib->unfreeze($item['product_id'], $freeze_obj_type, $bill_type, $plat_order_id, '', $bmsq_id, abs($diff_quantity));
                            }
                        }
                        
                        $this->_platform->_newOrder['order_objects'][$objkey]['plat_obj_id'] = $obj_id;
                    }
                }
            }
            
            //更新为失败订单
            if ($sky_ordersdf_is_fail_order) {
                $this->_platform->_newOrder['is_fail'] = 'true'; //打标：失败状态
                $this->_platform->_newOrder['edit_status'] = 'true';
                $this->_platform->_newOrder['archive'] = '1';
            }
            
            //去除失败状态
            if ($this->_platform->_newOrder['is_fail'] != 'true' && $this->_platform->_tgOrder['is_fail'] == 'true') {
                $this->_platform->_newOrder['is_fail'] = 'false';
                $this->_platform->_newOrder['edit_status'] = 'false';
                $this->_platform->_newOrder['archive'] = '0';
            }
        }
    }
    
    private function _get_obj_key($object)
    {
        $objkey = '';
        foreach (explode('-', $this->_platform->object_comp_key) as $field) {
            $objkey .= ($object[$field] ? trim($object[$field]) : '') . '-';
        }
        
        return sprintf('%u', crc32(ltrim($objkey, '-')));
    }
    
    private function _get_item_key($item)
    {
        $itemkey = '';
        foreach (explode('-', $this->_platform->item_comp_key) as $field) {
            if ($field == 'unit_sale_price') {
                $itemkey .= bcdiv((float) $item['sale_price'], $item['nums'], 3) . '-';
            } else {
                $itemkey .= ($item[$field] ? $item[$field] : '') . '-';
            }
        }
        
        return sprintf('%u', crc32(ltrim($itemkey, '-')));
    }
}
