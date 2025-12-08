<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * [得物]店铺商品处理
 *
 * @author wangbiao@shopex.cn
 * @version $Id: Z
 */
class erpapi_shop_matrix_dewu_request_product extends erpapi_shop_request_product
{
    protected $bidding_type_list = array(
        // 0   =>  '普通现货',
        // 1   =>  '普通预售',
        // 3   =>  '跨境',
        // 5   =>  '寄售',
        // 6   =>  '入仓',
        7   =>  '极速现货',
        // 8   =>  '极速预售',
        // 12  =>  '品牌专供现货',
        // 13  =>  '品牌专供入仓',
        // 14  =>  '品牌直发',
        // 15  =>  '虚拟商品',
        // 23  =>  '保税仓',
        // 25  =>  '跨境寄售',
    );

    /**
     * 实时下载得物平台出价列表
     * 
     * @param array $filter
     * @param int $offset
     * @param int $limit
     * @return array
     */

    public function itemsAllGet($filter, $offset=0, $limit=30)
    {
        $timeout = 30;
        
        //出价类型
        $biddingType = ($filter['biddingType'] ? $filter['biddingType'] : 'normal');
        unset($filter['biddingType']);
        
        //接口名
        $bidding_type = '';
        if($biddingType == 'brand'){
            $api_method = SHOP_GET_BIDDING_BRAND_ALL; //品牌专供
            
            $label = '品牌专供';
        }elseif($biddingType == 'normal'){
            $api_method = SHOP_GET_BIDDING_ALL; //普通现货
            
            $label = '普通现货';
        } elseif (in_array($biddingType, array_keys($this->bidding_type_list))) {
            $api_method = SHOP_GET_SKU_PRICE_ALL; // 按出价类型获取出价列表（极速现货）

            $bidding_type = $biddingType;
            $label = $this->bidding_type_list[$bidding_type];
        } else {
            $api_method = SHOP_GET_BIDDING_BRAND_DELI_ALL; //品牌直发
            
            $label = '品牌直发';
        }
        
        //请求参数
        $param = array(
                'page_no' => $offset,
                'page_size' => $limit,
        );
        if ($bidding_type) {
            $param['start_modified'] = date('Y-m-d H:i:s', strtotime('-59 days'));
            $param['end_modified']   = date('Y-m-d H:i:s');
            $param['bidding_type']   = $bidding_type;
        }
        
        //title
        $primary_bn = date('Ymd');
        $title = '获取店铺(' . $this->__channelObj->channel['name'] .')'. $label .'出价列表';
        
        //request
        $result = $this->__caller->call($api_method, $param, array(), $title, $timeout, $primary_bn);
        
        if($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }
        
        return $result;
    }
    
    /**
     * 更新库存接口名
     */
    protected function getUpdateStockApi()
    {
        return SHOP_UPDATE_BIDDING_QUANTITY;
    }
    
    /**
     * 格式化库存数据
     * @todo：得物更新库存时用的是bidding_no出价编号;
     * 
     * @param array $stocks
     * @return array
     */
    public function format_stocks($stocks)
    {
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
        $tempList = $skuObj->getList('shop_sku_id,shop_product_bn,bidding_type,bidding_no', array('shop_id'=>$shop_id, 'shop_product_bn'=>array_keys($bns)));
        if(empty($tempList)){
            return false;
        }
        
        //获取出价编号记录
        $productList = array();
        foreach ($tempList as $key => $val)
        {
            $product_bn = $val['shop_product_bn'];
            $sku_id = $val['shop_sku_id'];
            $bidding_type = ($val['bidding_type'] ? $val['bidding_type'] : 0);
            
            //check
            if(empty($product_bn) || empty($sku_id) || empty($val['bidding_no'])){
                continue;
            }
            
            //一个SKU货号对应多个出价编号(类型分为：普通现货、品牌专供、品牌直发)
            $productList[$product_bn][$sku_id][$bidding_type] = $val['bidding_no'];
        }
        
        //通过商品sku_id,获取所有出价列表
        $skuBiddingList = array();
        foreach($productList as $product_bn => $skuItem)
        {
            foreach($skuItem as $sku_id => $skuInfo)
            {
                foreach($skuInfo as $bidding_type => $bidding_no)
                {
                    $error_msg = '';
                    
                    //params
                    $params = array(
                        'product_bn' => $product_bn,
                        'sku_id' => $sku_id,
                        'bidding_type' => $bidding_type,
                    );
                    // $tempList = $this->getSkuBiddingList($params, $error_msg);
                    $tempList = $this->getSkuInventoryList($params, $error_msg);
                    if(!$tempList){
                        //没有出价列表,则跳过
                        continue;
                    }
                    
                    $skuBiddingList[$product_bn][$sku_id] = $tempList;
                }
            }
        }
        
        //empty
        if(empty($skuBiddingList)){
            return false;
        }
        
        //组织出价编号回写库存列表
        $stockList = array();
        foreach($skuBiddingList as $product_bn => $skuItem)
        {
            $bnInfo = $bns[$product_bn];
            if(empty($bnInfo)){
                continue;
            }
            $omsQuantity = $bnInfo['quantity'];
            
            //skus
            foreach($skuItem as $sku_id => $skuInfo)
            {
                foreach($skuInfo as $bidding_no => $biddingInfo)
                {
                    //得物平台库存(出价剩余数量)
                    // $dewuQuantity = intval($biddingInfo['qty_remain']);
                    $dewuUseQuantity = intval($biddingInfo['use_quantity']); // 使用数量
                    $dewuQuantity = intval($biddingInfo['realtime_qty']); // 实时库存数量

                    // 状态 1:上架，10:下架（ 永久下架 ），11:保证金不足下架 （ 临时下架, 可恢复上架 ），20:售空
                    if(!in_array($biddingInfo['status'], [1])){
                        // 只有上架状态才会回写库存
                        continue;
                    }
                    
                    //得物平台与OMS系统库存相同,则跳过
                    if($omsQuantity == $dewuQuantity){
                        continue;
                    } elseif ($omsQuantity<$dewuUseQuantity) {
                        // 如果OMS数量小于冻结（使用）数量，给0库存，避免超卖，强制下架
                        $omsQuantity = 0;
                    }
                    
                    $stockList[] = array(
                            'bn' => $product_bn,
                            'bidding_no' => $bidding_no,
                            'quantity' => $omsQuantity,
                    );
                }
            }
        }
        
        //empty
        if(empty($stockList)){
            return false;
        }
        
        return $stockList;
    }

    /**
     * 获取SKU库存列表 https://open.dewu.com/#/api/body?id=3&apiId=1241&title=%E5%87%BA%E4%BB%B7%E6%9C%8D%E5%8A%A1
     * @param array $data 包含商品信息的数据数组，预期包含商品条形码（product_bn）、SKU ID（sku_id）和出价类型（bidding_type）
     * @param string &$error_msg 可选，如果获取库存失败，将错误信息赋值给此参数
     * @return array 返回一个包含SKU库存信息的数组；如果失败，返回false
     */
    public function getSkuInventoryList($data, &$error_msg=null){
        // 提取所需数据
        $product_bn     = $data['product_bn'];
        $sku_id         = $data['sku_id'];
        $bidding_type   = $data['bidding_type'];

        // 根据出价类型设置标签
        if ($bidding_type == 14) {
            $label = '品牌直发';
        }elseif($bidding_type == 12){
            $label = '品牌专供';
        }elseif ($bidding_type == 7) {
            $label = '极速现货';
        } else{
            $label = '普通现货';
        }
        $api_method = SHOP_GET_INVENTORY_QUERY;

        // 设置请求参数
        $param = array(
            'sku_id'        =>  $sku_id,
            'bidding_types' =>  json_encode([$bidding_type]),
            'sell_out_flag' =>  1, // 售罄数据标识 0:只查询在售数据（默认） 1:查询在售&售罄数据
        );
        
        // 构造请求标题
        $title = '获取店铺(' . $this->__channelObj->channel['name'] .')'. $label .'sku_id：'. $sku_id .'实时库存';
        
        $callBack = array();
        // 调用API请求库存数据
        $result = $this->__caller->call($api_method, $param, $callBack, $title, 15, $product_bn);
        // 检查请求是否成功
        if($result['rsp'] != 'succ'){
            $error_msg = '获取SKU实时库存失败：'.$result['err_msg'];
            return false;
        }
        
        // 解析返回的库存数据
        if(is_string($result['data'])) {
            $result['data'] = json_decode($result['data'], true);
        }
        $reSdf = $result['data']['data'];
        if($reSdf){
            // 如果有数据，按照出价编号整理数据
            $dataList = array_column($reSdf, null, 'bidding_no');
        } else {
            // 无数据时返回空数组
            $dataList = [];
        }

        return $dataList;
    }
    
    /**
     * 根据sku_id获取出价列表
     * 
     * @paramarray $data
     * @return array
     * 《***已经弃用***》，改用 getskuInventoryLit
     * 《***已经弃用***》，改用 getskuInventoryLit
     * 《***已经弃用***》，改用 getskuInventoryLit
     * 《***已经弃用***》，改用 getskuInventoryLit
     * 《***已经弃用***》，改用 getskuInventoryLit
     */
    public function getSkuBiddingList($data, &$error_msg=null)
    {
        $product_bn = $data['product_bn'];
        $sku_id = $data['sku_id'];
        $bidding_type = $data['bidding_type'];
        
        //出价类型
        if($bidding_type > 0){
            //品牌专供
            $api_method = SHOP_GET_BIDDING_BRAND_SKUS;
            $label = '品牌专供';
        }else{
            //普通现货
            $api_method = SHOP_GET_BIDDING_NORMAL_SKUS;
            $label = '普通现货';
        }
        
        //请求参数
        $param = array(
            'sku_id' => $sku_id,
        );
        
        //title
        $title = '获取店铺(' . $this->__channelObj->channel['name'] .')'. $label .'sku_id：'. $sku_id .'出价列表';
        
        //第一步：通过商品sku_id,获取所有出价列表
        $callBack = array();
        $result = $this->__caller->call($api_method, $param, $callBack, $title, 15, $product_bn);
        if($result['rsp'] != 'succ'){
            $error_msg = '获取SKU出价列表失败：'.$result['err_msg'];
            return false;
        }
        
        //data
        if(is_string($result['data'])) {
            $result['data'] = json_decode($result['data'], true);
        }
        $reSdf = $result['data']['data'];
        
        //list
        $biddingList = array();
        foreach ($reSdf as $key => $val)
        {
            $bidding_no = $val['bidding_no'];
            $bidding_type = intval($val['bidding_type']);
            
            $biddingList[$bidding_no] = array(
                    'product_bn' => $product_bn,
                    'sku_id' => $val['sku_id'], //商品sku_id
                    'bidding_no' => $bidding_no, //出价编号
                    'bidding_type' => $bidding_type, //出价类型
                    //'realtime_qty' => $val['realtime_qty'], //实时库存
            );
        }
        
        //check
        if(empty($biddingList)){
            $error_msg = 'SKU出价列表为空';
            return false;
        }
        
        //第二步：通过出价编号,获取上架状态及出价数量
        $dataList = array();
        foreach ($biddingList as $bidding_no => $biddingInfo)
        {
            $tempList = $this->getBiddingStatus($biddingInfo, $error_msg);
            if(!$tempList){
                continue;
            }
            
            $dataList[$bidding_no] = $tempList;
            
            break;
        }
        
        return $dataList;
    }
    
    /**
     * 根据bidding_no获取出价编号上架状态
     * 
     * @paramarray $data
     * @return array
     */
    public function getBiddingStatus($data, &$error_msg=null)
    {
        $product_bn = $data['product_bn'];
        $sku_id = $data['sku_id'];
        $bidding_no = $data['bidding_no'];
        $bidding_type = $data['bidding_type'];
        
        //出价类型
        if($bidding_type > 0){
            //品牌专供
            $api_method = SHOP_GET_BIDDING_BRAND_DETAIL;
            $label = '品牌专供';
        }else{
            //普通现货
            $api_method = SHOP_GET_BIDDING_NORMAL_DETAIL;
            $label = '普通现货';
        }
        
        //请求参数
        $param = array(
            'bidding_no' => $bidding_no,
        );
        
        //title
        $title = '获取店铺(' . $this->__channelObj->channel['name'] .')'. $label .'bidding_no：'. $bidding_no .'SKU出价状态';
        
        //通过出价编号,获取上架状态及出价数量
        $callBack = array();
        $result = $this->__caller->call($api_method, $param, $callBack, $title, 15, $product_bn);
        if($result['rsp'] != 'succ'){
            $error_msg = '获取SKU出价状态失败：'.$result['err_msg'];
            return false;
        }
        
        //data
        if(is_string($result['data'])) {
            $result['data'] = json_decode($result['data'], true);
        }
        $reSdf = $result['data']['data'];
        
        //出价状态
        if($reSdf['status'] != '1'){
            $error_msg = '出价编号不是上架状态status:'. $reSdf['status'];
            return false;
        }
        
        //sdf
        $sdf = array(
                //'bidding_type' => $result['data']['bidding_type'],
                //'bidding_time' => $result['data']['bidding_time'],
                'qty_remain' => intval($reSdf['qty_remain']), //出价剩余数量(实时库存数)
                'qty' => intval($reSdf['qty']), //出价数量
                'status' => $reSdf['status'],
        );
        
        return $sdf;
    }
}