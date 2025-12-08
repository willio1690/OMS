<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * @Author: xueding@shopex.cn
 * @Datetime: 2022/4/18
 * @Describe: 商品相关接口
 */
class erpapi_dchain_request_product extends erpapi_dchain_request_abstract
{
    
    /**
     * 创建
     * @param mixed $params 参数
     * @return mixed 返回值
     */

    public function create($params)
    {
        if (!$params) {
            return false;
        }
        
        $title = "外部商品创建(" . $this->__channelObj->channel['name'] . ')';
    
        $method = STORE_TMYC_SCITEM_BATCH_CREATE;
        if (isset($params['type']) && $params['type'] == 'update') {
            $method = STORE_TMYC_SCITEM_BATCH_UPDATE;
            $title  = "外部商品更新(" . $this->__channelObj->channel['name'] . ')';
        }
        
        $params = $this->_formatProductParams($params);
        
        $result = $this->__caller->call($method, $params, array(), $title, 10, $this->__channelObj->channel['node_id']);
        
        if ($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }
        return $result;
    }
    
    /**
     * 创建更新product重组参数
     * @param $params
     * @return mixed
     */
    protected function _formatProductParams($params)
    {
        return $params;
    }
    
    public function create_pkg($params)
    {
        if (!$params) {
            return false;
        }
        
        $title = "外部组合商品创建(" . $this->__channelObj->channel['name'] . ')';
        
        $method = STORE_TMYC_COMBINESCITEM_BATCH_CREATE;
        if (isset($params['type']) && $params['type'] == 'update') {
            $method = STORE_TMYC_COMBINESCITEM_BATCH_UPDATE;
            $title  = "外部组合商品更新(" . $this->__channelObj->channel['name'] . ')';
        }
    
        $params = $this->_formatPkgParams($params);
    
        $result = $this->__caller->call($method, $params, array(), $title, 10, $this->__channelObj->channel['node_id']);
        
        if ($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }
        return $result;
    }
    
    /**
     * 创建更新pkg重组参数
     * @param $params
     * @return mixed
     */
    protected function _formatPkgParams($params)
    {
        return $params;
    }
    
    /**
     * 创建编辑商货品关联
     * @param $params
     * @return array|bool
     */
    public function item_mapping($params)
    {
        if (!$params) {
            return false;
        }
        
        $title = "外部商货品关联 (" . $this->__channelObj->channel['name'] . ')';
        
        $method = STORE_TMYC_ITEMMAPPING_BATCH_CREATE;
        
        $params = $this->_formatItemMappingParams($params);
        
        $result = $this->__caller->call($method, $params, array(), $title, 10, $this->__channelObj->channel['node_id']);
        
        if ($result['data']) {
            $result['data'] = json_decode($result['data'], true);
        }
        return $result;
    }
    
    /**
     * 创建更新商货品关联重组参数
     * @param $params
     * @return mixed
     */
    protected function _formatItemMappingParams($params)
    {
        return $params;
    }
    
    /**
     * 库存同步API接口名
     * 
     * @return string
     */
    protected function getUpdateStockApi()
    {
        $api_name = STORE_TMYC_INVENTORY_BATCH_UPLOAD;
        
        return $api_name;
    }
    
    /**
     * 优仓库存同步
     * 
     * @param array $stocks 回写库存列表
     * @param bool $dorelease true为手动回写库存
     * @return array
     */
    public function updateStock($stocks, $dorelease=false)
    {
        $node_id = $this->__channelObj->channel['node_id'];
        $shop_id = $this->__channelObj->channel['shop_id'];
        $primaryBn = $this->__channelObj->channel['shop_bn'] . 'UpdateStock';
        
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        
        if(!$stocks){
            $rs['msg'] = 'stocks empty!';
            return $rs;
        }
    
        $logData = ['channels_inventory'=>'','original'=>json_encode($stocks, JSON_UNESCAPED_UNICODE)];
        foreach ($stocks as $key => $value) {
            if($value['regulation']) {
                unset($stocks[$key]['regulation']);
            }
        }
    
        //格式化库存参数
        $error_msg = '';
        $stocks = $this->_format_stocks($stocks, $error_msg);
        if(!$stocks){
            return $this->error('没有优仓可回写的库存数据('. $error_msg .')', '102');
        }
        
        //去除数组下标
        //$stocks = array_values($stocks);
        
        //保存库存同步管理日志
        $oApiLogToStock = kernel::single('ome_api_log_to_stock');
        $oApiLogToStock->save($stocks, $shop_id);
        
        //Api接口名
        $method = $this->getUpdateStockApi($stocks);
        
        //params
        $params = array(
                'channels_inventory' => json_encode($stocks),
        );
        $logData['channels_inventory'] = $params['channels_inventory'];
    
        //call back
        $callback = array(
                'class' => get_class($this),
                'method' => 'updateStockCallback',
                'params' => array(
                        'shop_id' => $shop_id,
                        'node_id' => $node_id,
                        'api_name' => $method,
                        'request_params' => $params,
                )
        );
        
        $title = '批量更新外部优仓店铺['. $this->__channelObj->channel['name'] .']的库存(共'. count($stocks) .'个)';

        //request
        $rs = $this->__caller->call($method, $params, $callback, $title, 30, $primaryBn,true,'',$logData);
        if ($rs !== false){
            app::get('ome')->model('shop')->update(array('last_store_sync_time'=>time()), array('shop_id'=>$shop_id));
        }
        
        $rs['rsp'] = 'success';
        
        return $rs;
    }
    
    /**
     * 格式化库存数据
     * 
     * @param array $stockList
     * @param string $error_msg
     * @return array
     */
    public function _format_stocks($stockList, &$error_msg=null)
    {
        $skuMdl = app::get('dchain')->model('foreign_sku');
        $node_id = $this->__channelObj->channel['node_id'];
        
        //channel
        $channelInfo = app::get('channel')->model('channel')->db_dump(array('node_id'=>$node_id, 'channel_type'=>'dchain'), 'channel_id,channel_bn');
        if(empty($channelInfo)){
            $error_msg = '优仓应用信息不存在';
            return false;
        }
        
        $channel_id = $channelInfo['channel_id'];

        $bnList = array_unique(array_column($stockList,'bn'));
        
        //获取优仓商品
        $fields = 'id,inner_sku,inner_product_id,inner_type,outer_sku,outer_sku_id';
        $dataList = $skuMdl->getList($fields, array('inner_sku'=>$bnList, 'dchain_id'=>$channel_id)); //商品同步状态：'sync_status'=>'3'
        if(empty($dataList)){
            $error_msg = '优仓商品不存在';
            return false;
        }
        $foreignSkus = array_column($dataList,null,'inner_sku');
        
        //format
        $stocks = array();
        foreach ($stockList as $key => $val)
        {
            $inner_sku = $val['bn'];
            $quantity = $val['quantity'];
            
            //data
            if ($foreignSkus[$val['bn']]['outer_sku']) {
                $stocks[] = array(
                    'quantity'       => $quantity, //库存数量
                    'bn'             => $inner_sku, //OMS商品编码
                    'sc_item_code'   => $foreignSkus[$val['bn']]['outer_sku'], //外部货品ID
                    'channel'        => '1000', //渠道(默认1000代表淘系渠道)
                    'warehouse_code' => $val['warehouse_code'], //仓库编码
                    'lastmodify'     => ($val['lastmodify'] ? $val['lastmodify'] : time()),
                );
            }
        }
        
        return $stocks;
    }
    
    /**
     * 回写库存回调结果
     * 
     * @param array $ret
     * @param array $callback_params
     * @return array
     */
    public function updateStockCallback($ret, $callback_params)
    {
        $oApiLogToStock = kernel::single('ome_api_log_to_stock');
        
        $shop_id = $callback_params['shop_id'];
        $node_id = $callback_params['node_id'];
        $apiName = $callback_params['api_name'];
        $request_params = $callback_params['request_params'];
        
        //组合商品
        $pkgSkuList = $callback_params['pkgSkuList'];
        
        //result
        $status = $ret['rsp'];
        $res = $ret['res'];
        $msg_id = $ret['msg_id'];
        $data = json_decode($ret['data'], true);
        
        //log
        $log_params = array($apiName, $request_params, array(get_class($this), 'updateStockCallback', $callback_params));
        $log_detail = array(
                'msg_id' => $msg_id,
                'params' => serialize($log_params),
        );
        
        //返回的回写结果列表
        $updateItems = $data['detail']['detail_item'];
        
        //更新的货号列表
        $succBns = array();
        $errorBns = array();
        if($updateItems){
            foreach ($updateItems as $key => $val)
            {
                $bn = $val['sc_item_code'];
                
                if($val['success']){
                    $succBns[$bn] = $bn;
                }else{
                    $errorBns[$bn] = $bn;
                }
            }
        }
        
        //库存更新失败
        if($errorBns){
            $new_itemsnum = array();
            foreach ($errorBns as $key => $bn)
            {
                $new_itemsnum[] = array('bn'=>$bn);
            }
            
            //更新失败的货号
            $oApiLogToStock->save_callback($new_itemsnum, 'fail', $shop_id, $res, $log_detail);
        }
        
        //库存更新成功
        if($succBns){
            //[兼容]匹配加入组合商品
            $this->_addStockPkgSkus($succBns);
            
            //true
            $true_itemsnum = array();
            foreach($succBns as $key => $bn)
            {
                $true_itemsnum[] = array('bn'=>$bn);
            }
            
            //更新成功的货号
            $oApiLogToStock->save_callback($true_itemsnum, 'success', $shop_id, $res, $log_detail);
            
            //更新商货品关联信息
            $dchainProLib = kernel::single('dchain_branch_product');
            $error_msg = '';
            $dchainProLib->requestMappingProduct($node_id, $succBns, $error_msg);
        }
        
        return $this->callback($ret, $callback_params);
    }
    
    /**
     * 根据销售物料查询相关组合商品货号
     * 
     * @param array $succBns
     * @return array
     */
    public function _addStockPkgSkus(&$succBns=null)
    {
        //check
        if(empty($succBns)){
            return false;
        }
    
        $salesMaterialMdl = app::get('material')->model('sales_material');
        $salesBasicMaterialMdl = app::get('material')->model('sales_basic_material');
    
        $salesLists  = $salesMaterialMdl->getList('sm_id', array('sales_material_bn' => array_keys($succBns)));
        $bmIds       = $salesBasicMaterialMdl->getList('bm_id', array('sm_id' => array_column($salesLists,'sm_id')));
        $smIds  = $salesBasicMaterialMdl->getList('sm_id', array('bm_id' => array_column($bmIds,'bm_id')));
        $salesLists  = $salesMaterialMdl->getList('sales_material_bn', array('sm_id' => array_column($smIds,'sm_id'),'sales_material_type'=>2,'is_bind'=>1));

        foreach ($salesLists as $bn => $v) {
            $succBns[$v['sales_material_bn']] = $v['sales_material_bn'];
        }
    }
}
