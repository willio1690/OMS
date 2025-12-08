<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 爱库存平台对接
 *
 * @author wangbiao@shopex.cn
 * @version 2022.06.15
 */
class erpapi_shop_matrix_aikucun_request_product extends erpapi_shop_request_product
{
    //请求矩阵版本号
    protected $__version = '2.0';
    
    /**
     * 下载全部商品(未使用,矩阵还没有对接)
     */

    public function itemsAllGet($filter, $offset = 0, $limit = 100)
    {
        $timeout = 20;
        $param   = array(
            'page_no'   => $offset,
            'page_size' => $limit,
        );

        $param = array_merge((array) $param, (array) $filter);

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
    
    /**
     * 根据IID获取单个商品(未使用,矩阵还没有对接)
     * 
     * @param string $iid
     * @return array
     */
    public function item_get($iid)
    {
        $title = '单拉商品[' . $iid . ']';
        
        $params = array(
            'product_id' => $iid,
        );
        
        //失败重试3次
        for ($i=0; $i<3; $i++)
        {
            $result = $this->__caller->call(SHOP_ITEM_GET, $params, array(), $title, 20, $iid);
            if ($result['rsp'] == 'succ') break;
        }
        
        //empty
        if ($result['rsp'] != 'succ' || empty($result['data'])){
            return array();
        }
        
        //json_decode
        if ($result['data']){
            $result['data'] = @json_decode($result['data'],true);
        }
        
        return $result;
    }
    
    /**
     * 格式化回传库存参数
     * 
     * @param array $stocks
     * @return array
     */
    public function format_stocks($stocks)
    {
        $basicMaterialObj = app::get('material')->model('basic_material');
        $codebaseMdl = app::get('material')->model('codebase');
        
        //bns
        $bns = array();
        foreach($stocks as $key => $val)
        {
            $product_bn = trim($val['bn']);
            $quantity = intval($val['quantity']);
            
            $bns[$product_bn] = array(
                    'operType' => '0', //0:覆盖式更新，1:增量更新
                    'bn' => $product_bn,
                    'quantity' => $quantity,
            );
        }
        
        //bm_id
        $tempList = $basicMaterialObj->getList('bm_id,material_bn', array('material_bn'=>array_keys($bns)));
        $bmList = array_column($tempList, null, 'material_bn');
        $bmIds = array_column($tempList, 'bm_id');
        
        //barcode
        $codeList = $codebaseMdl->getList('bm_id,code', array('bm_id'=>$bmIds));
        $codeList = array_column($codeList, null, 'bm_id');
        
        //stock
        $stockList = array();
        foreach($bns as $product_bn => $val)
        {
            $bm_id = $bmList[$product_bn]['bm_id'];
            $barcode = $codeList[$bm_id]['code'];
            
            //[兼容]没有条形码,直接使用货号
            //if(empty($barcode)){
            //    $barcode = $product_bn;
            //}
            
            //没有条形码,直接跳过
            if(empty($barcode)){
                continue;
            }
            
            $val['barcode'] = $barcode;
            
            $stockList[] = $val;
        }
        
        return $stockList;
    }
}