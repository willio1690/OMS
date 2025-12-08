<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class material_event_receive_goods extends material_event_response
{
    /**
     * 
     * 云交易商品信息变更MQ
     * @param array $data
     */
    public function update($data)
    {
        //参数检查
        if (!isset($data['data']) || !isset($data['channel_id'])) {
            return $this->send_error('必要参数缺失', $msg_code, $data);
        }
        
        //[防止频繁请求]限制每3分钟请求一次
        //@todo：防止频繁请求京东云交易，导致接口报错：key被限制,访问接口超过300000次;
        $cacheKeyName = '';
        $error_msg = '';
        $isCheck = $this->_repetitionRequest($data, $cacheKeyName, $error_msg);
        if(!$isCheck){
            return $this->send_error($error_msg, '405', $data);
        }
        
        //reqeust
        $good_detail_result = kernel::single('erpapi_router_request')->set('wms',
            $data['wms_id'])->goods_syncDetail($data);
        if ($good_detail_result['rsp'] != 'succ') {
            return $this->send_error('商品上下架状态修改失败：' . $good_detail_result['err_msg'], $msg_code, $data);
        }
        
        //记录缓存记录
        if($cacheKeyName){
            //[防止频繁请求]限制每3分钟请求一次
            cachecore::store($cacheKeyName, date('YmdHis', time()), 180);
        }
        
        //result
        $sourceMappingObj = app::get('material')->model('basic_material_channel');
        if (isset($good_detail_result['data']['items'][0]['base_info']['skuStatus'])) {
            $sku_status = $good_detail_result['data']['items'][0]['base_info']['skuStatus'];
        }
        if (!isset($sku_status)) {
            return $this->send_error('商品上下架状态修改失败：获取商品上下架状态失败', $msg_code, $data);
        }
        $mappingData = array(
            'approve_status' => $sku_status,
            'last_modify'    => time()
        );
        $info = $sourceMappingObj->db_dump(array('channel_id' => $data['channel_id'], 'outer_product_id' => $data['material_bn']),'approve_status');
        if ($info['approve_status'] == $sku_status) {
            return $this->send_succ('商品上下架状态无变化');
        }
        $sourceMappingObj->update($mappingData,
            ['channel_id' => $data['channel_id'], 'outer_product_id' => $data['material_bn']]);
        
        //是否有上架状态
        $approve_status = $sourceMappingObj->getList('bm_id,channel_id', array(
            'outer_product_id'    => $data['material_bn'],
            'approve_status' => '1',
        ));
        
        if (count($approve_status) > 1) {
	        $sourceMappingObj->update(['is_error'=>'1','last_modify'=>time()],
		        ['outer_product_id' => $data['material_bn'],'approve_status'=>'1']);
        } else {
	        $sourceMappingObj->update(['is_error'=>'0','last_modify'=>time()],
		        ['outer_product_id' => $data['material_bn']]);
        }
        return $this->send_succ('商品上下架状态修改成功');
    }
    
    /**
     * [防止频繁请求]限制每3分钟请求一次
     * @todo：防止频繁请求京东云交易，导致接口报错：key被限制,访问接口超过300000次;
     * 
     * @param array $data
     * @param string $cacheKeyName
     * @return bool
     */
    public function _repetitionRequest(&$data, &$cacheKeyName=null, &$error_msg=null)
    {
        $bmChannelMdl = app::get('material')->model('basic_material_channel');
        
        //check
        if(empty($data['data'])){
            $error_msg = '没有可处理的数据';
            return false;
        }
        
        $skuId = array();
        foreach ($data['data'] as $key => $val)
        {
            $outer_sku = $val['outer_sku'];
            if(empty($outer_sku)){
                continue;
            }
            
            $skuId[$outer_sku] = $outer_sku;
        }
        
        if(empty($skuId)){
            $error_msg = '没有可请求的outer_sku商品';
            return false;
        }
        
        //prams
        $params = array(
                'skus' => json_encode(array_values($skuId)),
                'warehouse_code' => $data['channel_id'],
        );
        
        //cache
        $cacheKeyName = md5('goods_syncDetail_'. $params['skus'] . $params['warehouse_code']);
        $cacheData = cachecore::fetch($cacheKeyName);
        if($cacheData){
            $error_msg = '京东云交易商品回传过于频繁,禁止请求(限制每5分钟请求一次)';
            return false;
        }
        
        //过滤掉[京东直营]客户域名
        $base_host = kernel::single('base_request')->get_host();
        if(strpos($base_host, 'jdzy') !== false){
            //允许请求商品详情接口
            return true;
        }
        
        //如果渠道商品只有一个渠道并且是上架状态,则无需获取商品详情接口
        $tempList = $bmChannelMdl->getList('*', array('outer_product_id'=>$skuId));
        if(empty($tempList)){
            $error_msg = '渠道商品不存在(可以初始化拉取商品)';
            return false;
        }
        
        $skuList = array();
        foreach ($tempList as $key => $val)
        {
            $outer_sku = $val['outer_product_id'];
            $channel_id = $val['channel_id'];
            
            $skuList[$outer_sku][$channel_id] = $val;
        }
        
        //过滤只有一个渠道并且是上架的商品
        foreach ($data['data'] as $key => $val)
        {
            $outer_sku = $val['outer_sku'];
            
            //多渠道
            if(count($skuList[$outer_sku]) > 1){
                continue;
            }
            
            //单渠道并且商品为[上架]状态,则过滤掉
            $skuInfo = reset($skuList[$outer_sku]);
            if($skuInfo['approve_status'] == '1' || $skuInfo['approve_status'] == '上架'){
                unset($data['data'][$key]);
            }
        }
        
        //check
        if(empty($data['data'])){
            $error_msg = '没有需要处理的数据(商品只有一个渠道并且是上架状态,无需请求商品详情接口)';
            return false;
        }
        
        return true;
    }
}
