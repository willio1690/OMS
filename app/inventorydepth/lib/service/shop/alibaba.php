<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
* 阿里巴巴商品处理
*
*/
class inventorydepth_service_shop_alibaba extends inventorydepth_service_shop_common
{
    public $err_msg;

    public $approve_status = array(
        array('filter'=>array('approve_status'=>'onsale'),'name'=>'全部','flag'=>'onsale','alias'=>'在架'),
    );
    
    //定义每页拉取数据
    public $customLimit = 20;
    
    function __construct(&$app){
        $this->app = $app;
    }

    public function get_err_msg(){
        return $this->err_msg;
    }

    public function set_err_msg($err_msg){
        return $this->err_msg = $err_msg;
    }

    /**
     * 下载全部商品信息
     * 
     * @return array
     */
    public function downloadList($filter,$shop_id,$offset=0,$limit=50,&$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');

        // 多次尝试调用确保请求成功
        $count = 0;

        do {
            if ($count>60) {
                $errormsg = '超出最大循环次数(Max:60页)';
                return false;
            }

            $result = $shopService->items_all_get($filter,$shop_id,$offset,$limit);
            if ($result === false) {
                $errormsg = $shopService->get_err_msg();
                if (false !== strpos($errormsg,'This ban will last for 1 more seconds') ) {
                    $errormsg = '';
                } elseif (false !== strpos($errormsg,'Invalid arguments:page_no') ) {
                    $errormsg = '';
                    break;
                } else {
                    return false;
                }
            } else {
                break;
            }
            $count++;
        }while(true);
        
        // 数据处理
        $result = json_decode($result['msg'],true);
        
        //数据为空
        if(empty($result['result']['pageResult']['resultList'])){
            $this->totalResults = 0;
            $errormsg = '抓取数据为空！';
            return false;
        }
        
        $this->totalResults = $result['result']['pageResult']['totalRecords']; //商品总数

        // 获取商品id
        $data = array();
        foreach ($result['result']['pageResult']['resultList'] as $key => $value)
        {
            // 过滤删除商品
            //if(!in_array($value['status'], array('online', 'published'))){
            //    continue;
            //}
            
            //[兼容]商品在架状态
            if(empty($value['approve_status']) && $value['status']){
                $value['approve_status'] = (in_array($value['status'], array('online', 'published')) ? 'onsale' : 'instock');
            }
            
            $goods_num = ($value['saleInfo']['amountOnSale'] ? $value['saleInfo']['amountOnSale'] : $value['amountOnSale']);
            $data[$key] = array(
                'outer_id'       => $value['productCargoNumber'], //商品编码
                'price'          => $value['saleInfo']['retailprice'] ? $value['saleInfo']['retailprice'] : '',
                'num'            => intval($goods_num), //店铺库存
                'iid'            => $value['productID'] ? $value['productID'] : '',
                'title'          => $value['subject'] ? $value['subject'] : $value['title'],
                'approve_status' => $value['approve_status'] ? $value['approve_status'] : $value['status'],
            );
            
            //SKU列表
            if(isset($value['skuInfos']) && count($value['skuInfos'])){
                $skuArr = array();
                foreach ($value['skuInfos'] as $k => $v)
                {
                    $skuArr[$k]['num']        = $v['amountOnSale'];
                    $skuArr[$k]['price']      = $v['consignPrice'];
                    $skuArr[$k]['sku_id']     = $v['skuId'];
                    $skuArr[$k]['outer_id']   = $v['cargoNumber']; //货品编码
                    $skuArr[$k]['quantity']   = $v['amountOnSale'];
                    $skuArr[$k]['properties'] = $v['attributes'][0]['attributeValue'];
                    
                    //获取属性名
                    foreach ($value['attributes'] as $kk => $vv)
                    {
                        if($vv['attributeID'] == $v['attributes'][0]['attributeID']){
                            $skuArr[$k]['properties_name'] = $vv['attributeName'];
                        }
                    }
                }
                
                $data[$key]['skus']['sku'] = $skuArr;
            }elseif($value['attributes']){
                foreach ($value['attributes'] as $kkk => $vvv)
                {
                    if($vvv['attributeName'] == '货号'){
                        $data[$key]['outer_id'] = $vvv['value'];
                    }
                }
            }
        }
        
        unset($result);
        
        return $data;
    }
    
    /**
     * 通过IID下载 单个
     *
     * @return void
     * @author
     **/
    public function downloadByIId($iid, $shop_id, &$errormsg)
    {
        $shopService = kernel::single('inventorydepth_rpc_request_shop_items');
        
        $result = $shopService->item_get($iid, $shop_id);
        
        if ($result === false) {
            $errormsg = $shopService->get_err_msg();
            return false;
        }
        
        //check
        if(empty($result['msg'])){
            $errormsg = $iid.'没有获取到数据！';
            return array();
        }
        
        $result = json_decode($result['msg'],true);
        
        $goodsInfo = $result['productInfo'];
        if(empty($goodsInfo)){
            $errormsg = $iid.'没有获取到商品数据！';
            return array();
        }
        
        //[兼容]商品在架状态
        if(empty($goodsInfo['approve_status']) && $goodsInfo['status']){
            $goodsInfo['approve_status'] = (in_array($goodsInfo['status'], array('online', 'published')) ? 'onsale' : 'instock');
        }
        
        //goods
        $goods_num = ($goodsInfo['amountOnSale'] ? $goodsInfo['amountOnSale'] : $goodsInfo['saleInfo']['amountOnSale']);
        $data = array(
                'outer_id' => $goodsInfo['productCargoNumber'],
                'iid' => $goodsInfo['productID'] ? $goodsInfo['productID'] : '',
                'title' => $goodsInfo['subject'] ? $goodsInfo['subject'] : $goodsInfo['title'],
                'approve_status' => $goodsInfo['approve_status'] ? $goodsInfo['approve_status'] : $goodsInfo['status'],
                'price' => $goodsInfo['retailprice'],
                'num' => intval($goods_num), //店铺库存
                'detail_url' => $data['detail_url'], //没有值
                'default_img_url' => $data['default_img_url'], //没有值
                'props' => $goodsInfo['props'], //没有值
        );
        
        //SKU列表
        if($goodsInfo['skuInfos']){
            $skuArr = array();
            foreach($goodsInfo['skuInfos'] as $key => $val)
            {
                $skuArr[$key]['num']        = $val['amountOnSale'];
                $skuArr[$key]['price']      = $val['consignPrice'];
                $skuArr[$key]['sku_id']     = $val['skuId'];
                $skuArr[$key]['outer_id']   = $val['cargoNumber']; //货品编码
                $skuArr[$key]['quantity']   = intval($val['amountOnSale']); //店铺库存
                $skuArr[$key]['properties'] = $val['attributes'][0]['attributeValue'];
                $skuArr[$key]['properties_name'] = $val['attributes'][0]['attributeName'];
            }
            
            $data['simple'] = ($skuArr ? 'false' : 'true');
            $data['skus']['sku'] = $skuArr;
        }
        
        return $data;
    }
    
    /**
     * [未使用]批量获取商品信息
     * 
     * @param array $iids
     * @param string $shop_id
     * @param string $errormsg
     * @return array
     */
    public function downloadByIIds($iids, $shop_id, &$errormsg=null)
    {
        set_time_limit(0);
        
        // 声明变量
        $tmpData = array();
        foreach($iids as $shop_iid)
        {
            $items = $this->get_alibaba_item_by_iid($shop_id,$shop_iid);

            if ($items === false) {
                $errormsg[] = $shop_iid . '：' . $this->get_err_msg();
                continue;
            }

            if(empty($items[$shop_iid])){
                $errormsg[]  = $shop_iid . '不存在！';
                continue;
            }
            
            // 组装数据
            if($items[$shop_iid]){
                $tmpData[] = $items[$shop_iid];
            }
        }
        unset($items);
        
        return $tmpData;
    }

    /**
     * 获取阿里巴巴商品信息
     * 
     * @param $shop_id 店铺id
     * @param $shop_iid 商品id
     * @return array
     */
    public function get_alibaba_item_by_iid($shop_id,$shop_iid)
    {
        // 定义一个静态变量
        static $goods;
        
        // 判断数据是否存在，存在即返回
        if (isset($goods[$shop_iid])){
            return $goods;
        }
        
        //调用接口获取商品信息
        //$result = kernel::single('erpapi_router_request')->set('shop',$shop_id)->product_item_get_new($shop_iid);
        
        //矩阵要求用老接口(item_get)
        $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_item_get($shop_iid);
        
        if ($result === false) {
            $this->set_err_msg('请求失败!');
            return false;
        } elseif ($result['rsp'] !== 'succ'){
            $this->set_err_msg('请求失败：'.$result['err_msg'] . '('. $result['msg_id'] .')');
            return false;
        }

        if($result['rsp'] == 'succ' && $result['data']['toReturn']){
            $item_info = $result['data']['toReturn'];

            $goods[$shop_iid] = array(
                'outer_id'       => '',
                'price'          => $item_info['saleInfo']['retailprice'] ? $item_info['saleInfo']['retailprice'] : '',
                'num'            => $item_info['saleInfo']['amountOnSale'] ? $item_info['saleInfo']['amountOnSale'] : '',
                'iid'            => $item_info['productID'] ? $item_info['productID'] : '',
                'title'          => $item_info['subject'] ? $item_info['subject'] : '',
                'approve_status' => $item_info['approve_status'] ? $item_info['approve_status'] : $item_info['status'],
                'simple'         => 'true',
            );

            if(isset($item_info['skuInfos']) && count($item_info['skuInfos'])){
                $skuArr = array();
                foreach ($item_info['skuInfos'] as $k=>$v)
                {
                    $skuArr[$k]['num']        = $v['amountOnSale'];
                    $skuArr[$k]['price']      = $v['price'];
                    $skuArr[$k]['sku_id']     = $v['skuId'];
                    $skuArr[$k]['outer_id']   = $v['cargoNumber'];
                    $skuArr[$k]['quantity']   = $v['amountOnSale'];
                    $skuArr[$k]['properties'] = $v['attributes'][0]['attributeValue'];
                    
                    // 获取属性名
                    foreach ($item_info['attributes'] as $kk=>$vv)
                    {
                        if($vv['attributeID'] == $v['attributes'][0]['attributeID']){
                            $skuArr[$k]['properties_name'] = $vv['attributeName'];
                        }
                    }
                    
                    // 总库存
                    $goods[$shop_iid]['num'] += $v['amountOnSale'];
                }
                $goods[$shop_iid]['skus']['sku'] = $skuArr;
            }else{
                foreach ($item_info['attributes'] as $kkk=>$vvv)
                {
                    if($vvv['attributeName'] == '货号'){
                        $goods[$shop_iid]['outer_id'] = $vvv['value'];
                    }
                }
            }
        }
        
        return $goods;
    }
}