<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 天猫定制订单Lib类
 *
 * @author wangbiao@shopex.cn
 * @version 2025.02.28
 */
class ome_order_customization extends ome_order_abstract
{
    /**
     * 转换定制订单
     *
     * @param $orderInfo
     * @param $error_msg
     * @return bool
     */
    public function transformCustomOrder($orderInfo)
    {
        $error_msg = '';
        
        //check
        if(empty($orderInfo) || empty($orderInfo['order_objects'])){
            $error_msg = '无效的订单objects明细';
            return $this->error($error_msg);
        }
        
        //shop_id
        if(empty($orderInfo['shop_id'])){
            $error_msg = '订单信息没有shop_id';
            return $this->error($error_msg);
        }
        
        $newObjects = $orderInfo['order_objects'];
        $coupons = [];
        $shop_id = $orderInfo['shop_id'];
        $payed = floatval($orderInfo['payed']);
        
        //check order_objects
        $is_custom = false;
        foreach($orderInfo['order_objects'] as $objKey => $objInfo)
        {
            //check
            if(empty($objInfo['order_items'])){
                continue;
            }
            
            //items
            foreach($objInfo['order_items'] as $itemKey => $itemInfo)
            {
                if(!isset($itemInfo['customization']) || empty($itemInfo['customization'])) {
                    continue;
                }
                
                $is_custom = true;
            }
        }
        
        //check
        if(!$is_custom){
            $error_msg = '订单objects层没有定制信息,无需转换';
            return $this->error($error_msg);
        }
        
        //开始转换
        $is_transform_fail = false;
        $is_transform_succ = false;
        $order_amount = 0;
        $customCodes = [];
        $customOrderIds = [];
        $itemErrorMsg = [];
        foreach($orderInfo['order_objects'] as $objKey => $objInfo)
        {
            //check
            if(empty($objInfo['order_items'])){
                continue;
            }
            
            //items
            foreach($objInfo['order_items'] as $itemKey => $itemInfo)
            {
                $quantity = intval($itemInfo['quantity']);
                $item_amount = floatval($itemInfo['amount']);
                
                if(!isset($itemInfo['customization']) || empty($itemInfo['customization'])) {
                    
                    //item层物料总额
                    $order_amount += $item_amount;
                    
                    continue;
                }
                
                //oid
                $itemInfo['oid'] = $objInfo['oid'];
                
                //payed
                $itemInfo['payed'] = $payed;
                
                //格式化定制信息中：outer_id为销售物料
                $transformInfo = $this->_transformOrderObject($itemInfo, $shop_id, $error_msg);
                
                //转换失败,跳过
                if(!$transformInfo || empty($transformInfo['newItemInfo'])){
                    $itemErrorMsg[] = $error_msg;
                    
                    $is_transform_fail = true;
                    continue;
                }
                
                //code
                if(isset($transformInfo['code'])){
                    $customCodes[] = $transformInfo['code'];
                }
                
                //orderId
                if(isset($transformInfo['orderId'])){
                    $customOrderIds[] = $transformInfo['orderId'];
                }
                
                //amount
                if(isset($transformInfo['item_total_amount'])){
                    //item层物料总额
                    $order_amount += $transformInfo['item_total_amount'];
                }
                
                //coupon
                $couponList = $transformInfo['couponList'];
                if($couponList){
                    $coupons = array_merge($coupons, $couponList);
                }
                
                //obj
                $newItemInfo = $transformInfo['newItemInfo'];
                $newObjects[$objKey]['order_items'] = $newItemInfo;
                
                //打标：转换object标记
                $newObjects[$objKey]['is_transform_obj'] = true;
                
                //flag
                $is_transform_succ = true;
            }
        }
        
        //check
        if(!$is_transform_succ || $is_transform_fail || empty($newObjects)){
            $error_msg = '转换定制订单失败';
            
            if($itemErrorMsg){
                $error_msg .= '：'. implode(',', $itemErrorMsg);
            }
            
            return $this->error($error_msg);
        }
        
        //data
        $data = [
            'newObjects' => $newObjects,
            'coupons' => $coupons,
            'order_amount' => $order_amount,
            'code' => ($customCodes ? $customCodes[0] : ''),
            'orderId' => ($customOrderIds ? $customOrderIds[0] : ''),
        ];
        
        $msg = '转换定制订单成功';
        return $this->succ($msg, $data);
    }
    
    /**
     * 转换订单object层明细
     *
     * @param $itemInfo
     * @param $shop_id
     * @param $error_msg
     * @return false|void
     */
    public function _transformOrderObject($itemInfo, $shop_id, &$error_msg=null)
    {
        $salesMaterialObj = app::get('material')->model('sales_material');
        
        $couponList = [];
        $newItemInfo = [];
        $newItemInfo[0] = $itemInfo;
        
        //设置原兑换券商品为：关闭状态(OMS系统里不会创建此商品)
        $newItemInfo[0]['status'] = 'close';
        
        //oid
        $oid = $itemInfo['oid'];
        $payed = $itemInfo['payed'];
        
        //check
        if(!isset($itemInfo['customization']) || empty($itemInfo['customization'])) {
            $error_msg = '商品明细没有定制信息';
            return false;
        }
        
        if(empty($shop_id)){
            $error_msg = 'shop_id字段没有值,无法查询销售物料';
            return false;
        }
        
        //format
        if(is_string($itemInfo['customization'])){
            $customInfo = json_decode($itemInfo['customization'], true);
        }else{
            $customInfo = $itemInfo['customization'];
        }
        
        if(empty($customInfo) && is_string($itemInfo['customization'])){
            $itemInfo['customization'] = str_replace('\"', '"', $itemInfo['customization']);
            $itemInfo['customization'] = str_replace('\/', '/', $itemInfo['customization']);
            
            $customInfo = json_decode($itemInfo['customization'], true);
        }
        
        //check
        if(!is_array($customInfo)){
            $error_msg = '商品定制信息decode失败';
            return false;
        }
        
        if(!isset($customInfo['items']) || empty($customInfo['items'])){
            $error_msg = '商品定制信息items为空';
            return false;
        }
        
        //code
        $code = $customInfo['code'];
        $orderId = $customInfo['orderId'];
        
        //outer_id
        $outerIds = array_column($customInfo['items'], 'outer_id');
        $outerIds = array_unique($outerIds);
        if(empty($outerIds)){
            $error_msg = '商品定制信息outer_id为空';
            return false;
        }
        
        //获取OMS销售物料
        $filter = [
            'sales_material_bn' => $outerIds,
            'shop_id' => [$shop_id, '_ALL_'],
        ];
        
        //material
        $saleMaterials = $salesMaterialObj->getList('sm_id,sales_material_bn,sales_material_name,sales_material_type', $filter);
        if(empty($saleMaterials)){
            $error_msg = '商品定制信息所有outer_id都没有对应的销售物料';
            return false;
        }
        
        $saleMaterials = array_column($saleMaterials, null, 'sales_material_bn');
        $smBns = array_keys($saleMaterials);
        
        //diff
        $diffBns = array_diff($outerIds, $smBns);
        if($diffBns){
            $error_msg = 'outer_id对应的销售物料编码：'. implode(',', $diffBns) .'不存在';
            return false;
        }
        
        //平台实付金额
        $total_amount = 0;
        if(isset($itemInfo['divide_order_fee']) && $itemInfo['divide_order_fee'] != ''){
            $total_amount = $itemInfo['divide_order_fee'];
        }elseif(isset($itemInfo['sale_amount']) && $itemInfo['sale_amount'] != ''){
            $total_amount = $itemInfo['sale_amount'];
        }
        $temp_total_amount = $total_amount;
        
        //获取第一个item上的兑换券金额
        $parValue = 0;
        $fisrItemInfo = current($customInfo['items']);
        if(isset($fisrItemInfo['parValue'])){
            $fisrItemInfo['parValue'] = floatval($fisrItemInfo['parValue']);
            
            if($fisrItemInfo['parValue'] > 0){
                $parValue = $fisrItemInfo['parValue'];
            }
        }
        
        //格式化兑换券分摊金额到coupon
        if($parValue > 0){
            //平台承担金额 = 兑换券金额 - 实际支付金额
            $total_sale_price = $parValue - $payed;
            $temp_sale_price = $total_sale_price;
            
            $line_i = 0;
            $item_count = count($customInfo['items']);
            $outerIdLines = [];
            foreach ($customInfo['items'] as $itemKey => $itemVal)
            {
                $line_i++;
                
                $outer_id = $itemVal['outer_id'];
                $quantity = intval($itemVal['quantity']);
                
                //oid保持唯一性(支持多个相同SKU的场景)
                if(!isset($outerIdLines[$outer_id])){
                    $outerIdLines[$outer_id] = 1;
                    
                    //new_oid = oid + 销售物料编码
                    $new_oid = $oid .'-'. $outer_id;
                }else{
                    $outerIdLines[$outer_id]++;
                    
                    //new_oid = oid + 销售物料编码 + SKU重复行数
                    $new_oid = $oid .'-'. $outer_id .'-'. $outerIdLines[$outer_id];
                }
                
                //销售物料信息
                $salematerialInfo = $saleMaterials[$outer_id];
                
                //均摊销售金额
                if($line_i == $item_count){
                    $avg_sale_price = $temp_sale_price;
                }else{
                    //高精度--除法(保留两位小数)
                    $avg_sale_price = bcdiv($total_sale_price, $item_count, 2) ;
                    
                    //高精度--减法(保留两位小数)
                    $temp_sale_price = bcsub($temp_sale_price, $avg_sale_price, 2);
                }
                
                //coupon
                $couponList[] = array(
                    'num'           => $quantity,
                    'material_bn'   => $outer_id,
                    'oid'           => $new_oid,
                    'material_name' => $salematerialInfo['sales_material_name'],
                    'type'          => 'tmallCustomization',
                    'type_name'     => '兑换券定制信息',
                    'coupon_type'   => '1',
                    'amount'        => $avg_sale_price / $quantity,
                    'total_amount'  => $avg_sale_price,
                    'create_time'   => kernel::single('ome_func')->date2time($this->_ordersdf['createtime']),
                    'pay_time'      => kernel::single('ome_func')->date2time($this->_ordersdf['payment_detail']['pay_time']),
                    'shop_type'     => 'taobao',
                    'source'        => 'push',
                );
            }
        }
        
        //按定制订单items明细纬度执行
        $item_count = count($customInfo['items']);
        $item_total_amount = 0;
        $line_i = 0;
        $outerIdLines = [];
        foreach ($customInfo['items'] as $itemKey => $itemVal)
        {
            $line_i++;
            
            $outer_id = $itemVal['outer_id'];
            $quantity = intval($itemVal['quantity']);
            $price = floatval($itemVal['price']);
            
            //oid保持唯一性(支持多个相同SKU的场景)
            if(!isset($outerIdLines[$outer_id])){
                $outerIdLines[$outer_id] = 1;
                
                //new_oid = oid + 销售物料编码
                $new_oid = $oid .'-'. $outer_id;
            }else{
                $outerIdLines[$outer_id]++;
                
                //new_oid = oid + 销售物料编码 + SKU重复行数
                $new_oid = $oid .'-'. $outer_id .'-'. $outerIdLines[$outer_id];
            }
            
            //销售价：直接使用price金额,parValue会放到coupon里均摊;
            $sale_price = $price;
            
            //销售物料信息
            $salematerialInfo = $saleMaterials[$outer_id];
            
            //销售物料未找到,直接返回
            if(empty($salematerialInfo)){
                $error_msg = '销售物料编码：'. $outer_id .'未找到';
                return false;
            }
            
            //amount
            $amount = $price * $quantity;
            
            $item_total_amount += $amount;
            
            //均摊实付金额
            if($line_i == $item_count){
                $avg_amount = $temp_total_amount;
            }else{
                //高精度--除法(保留两位小数)
                $avg_amount = bcdiv($total_amount, $item_count, 2) ;
                
                //高精度--减法(保留两位小数)
                $temp_total_amount = bcsub($temp_total_amount, $avg_amount, 2);
            }
            
            //amount
            $pmt_price = 0;
            $part_mjz_discount = 0;
            if($sale_price > 0){
                //pmt_price
                if($price >= $sale_price){
                    $pmt_price = $price - $sale_price;
                }
                
                //part_mjz_discount
                $part_mjz_discount = $sale_price - $avg_amount;
            }else{
                $sale_price = $avg_amount;
            }
            
            //商品规格
            $productAttrs = $this->_formatProper($itemVal['propertiesName']);
            
            //添加定制的商品明细
            $newItemInfo[] = [
                'status' => 'active',
                'shop_goods_id' => $itemVal['itemid'],
                'shop_product_id' => $itemVal['skuid'],
                'item_type' => 'product',
                'oid' => $new_oid,
                'bn' => $outer_id,
                'name' => $salematerialInfo['sales_material_name'],
                'quantity' => $quantity,
                'price' => $price,
                'pmt_price' => $pmt_price,
                'sale_price' => $sale_price,
                'amount' => $amount,
                'part_mjz_discount' => $part_mjz_discount,
                'divide_order_fee' => $avg_amount,
                'cost' => 0,
                'product_attr' => $productAttrs, //货品属性(array数组类型)
                'original_str' => $itemVal['propertiesName'], //货品属性(string字符串类型)
            ];
        }
        
        //result
        $result = [
            'newItemInfo' => $newItemInfo,
            'couponList' => $couponList,
            'item_total_amount' => $item_total_amount,
            'code' => $code,
            'orderId' => $orderId,
        ];
        
        return $result;
    }
    
    /**
     * 格式化定制信息中商品属性
     * 例如：$propertiesName = '1627207:28642813830:颜色分类:质感金色星星;规格:这是规格信息';
     *
     * @param $propertiesName
     * @return void
     */
    public function _formatProper($propertiesName)
    {
        //check
        if(empty($propertiesName)){
            return [];
        }
        
        $tempList = explode(';', $propertiesName);
        if(empty($tempList)){
            return [];
        }
        
        $properList = [];
        foreach ($tempList as $properKey => $properVal)
        {
            if(empty($properVal)){
                continue;
            }
            
            $specList = explode(':', $properVal);
            
            //颜色分类ID
            if(isset($specList[0]) && is_numeric($specList[0])){
                unset($specList[0]);
            }
            
            //颜色分类value
            if(isset($specList[1]) && is_numeric($specList[1])){
                unset($specList[1]);
            }
            
            //check
            if(empty($specList)){
                continue;
            }
            
            $properList[] = array_values($specList);
        }
        
        //check
        if(empty($properList)){
            return [];
        }
        
        //format
        $productAttrs = [];
        foreach ($properList as $proKey => $proVal)
        {
            //check
            if(empty($proVal[0]) || empty($proVal[1])){
                continue;
            }
            
            $productAttrs[] = ['label'=>$proVal[0], 'value'=>$proVal[1]];
        }
        
        return $productAttrs;
    }
}
