<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 得物平台商品处理
*/
class inventorydepth_service_shop_dewu extends inventorydepth_service_shop_common
{
    public $approve_status = array(
            array('filter'=>array('approve_status'=>'onsale'),'name'=>'全部','flag'=>'onsale','alias'=>'在架'),
    );
    
    //定义每页拉取数据(平台限制每页最多30条)
    public $customLimit = 30;
    
    function __construct(&$app)
    {
        $this->app = $app;
    }
    
    /**
     * 下载全部商品(包括普通现货、专供现货)
     * 
     * @param array $filter
     * @param string $shop_id
     * @param int $offset
     * @param int $limit
     * @param string $limit
     * @return array
     */
    public function downloadList($filter, $shop_id, $offset=0, $limit=30, &$errormsg=null)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');
        
        //出价类型
        $filter['biddingType'] = ($filter['biddingType'] ? $filter['biddingType'] : 'normal');
        
        //开始执行
        $result = $shopService->items_all_get($filter, $shop_id, $offset, $limit);
        if($result === false){
            $errormsg = $shopService->get_err_msg();
            return false;
        }
        
        //矩阵数据为空
        if(empty($result['data'])){
            $this->totalResults = 0;
            return array();
        }
        
        $this->totalResults = $result['data']['total_results']; //平台商品总数
        
        $biddingList = $result['data']['biddings'];
        if(empty($biddingList)){
            $this->totalResults = 0;
            return array();
        }
        
        //出价列表
        $spuList = array();
        foreach ($biddingList as $key => $val)
        {
            $spu_id = $val['spu_id'];
            $sku_bn = (empty($val['merchant_sku_code']) ? '' : $val['merchant_sku_code']); //SKU货号(得物后台商家不设置,会是null)
            $spu_bn = $val['article_number']; //spu款号(商家编码)
            $sku_id = $val['sku_id'];
            $price = $val['price']; //格式化:sprintf('%.2f', $val['price']);
            
            //check
            if(empty($spu_id) || empty($spu_bn) || empty($sku_id)){
                continue;
            }
            
            if(empty($sku_bn)){
                continue; //过滤空货号
            }
            
            //bidding_no为空则跳过
            if(empty($val['bidding_no'])){
                continue;
            }
            
            if(empty($spuList[$spu_id])){
                //spu
                $spuList[$spu_id] = array(
                        'spu_id' => $spu_id,
                        'spu_bn' => $spu_bn,
                        'price' => $price,
                );
                
                //sku
                $spuList[$spu_id]['skus'][$sku_id] = array(
                        'sku_id' => $sku_id,
                        'sku_bn' => $sku_bn, //sku货号
                        'price' => $price,
                        'qty' => isset($val['qty']) ? intval($val['qty']) : intval($val['bid_qty']), //出价数量
                        'qty_sold' => intval($val['qty_sold']), //已售数量
                        'qty_remain' => intval($val['qty_remain']), //剩余数量
                        'bidding_type' => intval($val['bidding_type']), //出价类型
                        'bidding_no' => $val['bidding_no'], //出价编号
                );
            }else{
                $spuList[$spu_id]['skus'][$sku_id] = array(
                        'sku_id' => $sku_id,
                        'sku_bn' => $sku_bn, //sku货号
                        'price' => $price,
                        'qty' => isset($val['qty']) ? intval($val['qty']) : intval($val['bid_qty']), //出价数量
                        'qty_sold' => intval($val['qty_sold']), //已售数量
                        'qty_remain' => intval($val['qty_remain']), //剩余数量
                        'bidding_type' => intval($val['bidding_type']), //出价类型
                        'bidding_no' => $val['bidding_no'], //出价编号
                );
            }
        }
        
        //check
        if(empty($spuList)){
            $errormsg = '没有可用的数据';
            return false;
        }
        
        //格式化
        $data = array();
        foreach ($spuList as $spu_id => $spuInfo)
        {
            $shopStore = array(); //店铺库存
            
            //skus
            $skuList = array();
            $skuList['sku'] = array();
            foreach ($spuInfo['skus'] as $sku_id => $skuInfo)
            {
                $skuList['sku'][] = array(
                        'outer_id' => $skuInfo['sku_bn'], //sku货号
                        'sku_id' => $skuInfo['sku_id'],
                        'title' => $skuInfo['sku_bn'].'('. $skuInfo['sku_id'] .')', //todo:接口上没有商品标题
                        'price' => $skuInfo['price'],
                        'quantity' => $skuInfo['qty_remain'], //店铺库存(剩余出价数量)
                        'bidding_type' => $skuInfo['bidding_type'], //出价类型
                        'bidding_no' => $skuInfo['bidding_no'], //出价编号
                );
                
                $shopStore[] = $skuInfo['qty_remain'];
            }
            
            //店铺库存取最小值
            $shop_store = min($shopStore);
            
            //spu
            $data[] = array(
                    'outer_id' => $spuInfo['spu_bn'], //spu商品编号
                    'iid' => $spuInfo['spu_id'], //spu_id
                    'title' => $spuInfo['spu_bn'].'('. $spuInfo['spu_id'] .')', //todo:接口上没有商品标题
                    'approve_status' => 'onsale', //上下架
                    'price' => $spuInfo['price'],
                    'num' => $shop_store, //店铺库存
                    'simple' => 'false',
                    'skus' => $skuList,
            );
        }
        
        //销毁
        unset($result, $biddingList, $spuList);
        
        return $data;
    }

    /**
     * 通过IID批量下载商品
     *
     * @param array $iids
     * @param string $shop_id
     * @param string $errormsg
     * @return array
     */
    public function downloadByIIds($iids, $shop_id, &$errormsg=null)
    {
        $errormsg = '不支持通过IID批量下载';
        
        return false;
    }

    /**
     * 通过IID下载单个商品
     * 
     * @param array $iid
     * @param string $shop_id
     * @param string $errormsg
     * @return array
     */
    public function downloadByIId($iid,$shop_id,&$errormsg=null)
    {
        $errormsg = '不支持通过IID下载 单个';
        
        return false;
    }
}